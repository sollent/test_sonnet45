<?php

declare(strict_types=1);

namespace App\Service\AI;

use App\ValueObject\ParsedCommand;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Сервис для работы с Large Language Model (Qwen 2.5 1.5B через Ollama)
 *
 * Отвечает за парсинг голосовых команд и конвертацию их в структурированные действия.
 * Использует оптимизированную модель Qwen 2.5 1.5B для быстрого отклика на CPU.
 * Следует паттерну Adapter для изоляции внешнего API
 */
class LLMService
{
    private const DEFAULT_MODEL = 'qwen2.5:1.5b';

    private const DEFAULT_TIMEOUT = 60.0; // 1 минута для qwen2.5:1.5b на CPU (быстрее чем 3b)

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY_MS = 500;

    /**
     * Системный промпт для LLM - ОПТИМИЗИРОВАННЫЙ
     *
     * Для модели qwen2.5:1.5b - сокращено до ~1500 токенов для максимальной скорости
     * Включает обработку опечаток и автокоррекцию текста
     */
    private const SYSTEM_PROMPT = <<<'PROMPT'
        Ты - ассистент управления задачами. Анализируй команды и возвращай JSON.

        КРИТИЧЕСКИ ВАЖНО:
        1. Возвращай ТОЛЬКО валидный JSON без пояснений
        2. ИЗВЛЕКАЙ дату/время из текста и помещай в отдельные параметры
        3. ИСПРАВЛЯЙ опечатки и грамматические ошибки в тексте
        4. При опечатках понимай намерения пользователя (например "купить три по кетам молока" → "купить три пакета молока")

        ОБРАБОТКА ОПЕЧАТОК:
        - Автоматически исправляй опечатки в словах
        - Восстанавливай пропущенные буквы
        - Понимай искаженные слова из контекста
        - НЕ копируй опечатки в title задачи!

        Доступные действия:
        - create_task (создать одну задачу)
        - create_multiple_tasks (несколько задач)
        - complete_task (завершить)
        - filter_tasks (показать/найти)
        - update_task (изменить)

        Формат: {"action":"название","parameters":{...},"confidence":0.0-1.0}

        ПРИМЕРЫ:

        "Создай задачу купить молоко на завтра" →
        {"action":"create_task","parameters":{"title":"Купить молоко","due_date":"tomorrow"},"confidence":0.95}

        "Создай срочную задачу позвонить клиенту на сегодня с 14:00 до 15:00" →
        {"action":"create_task","parameters":{"title":"Позвонить клиенту","due_date":"today","start_time":"14:00","end_time":"15:00","priority":"high"},"confidence":0.95}

        "Завершить задачу написать отчет" →
        {"action":"complete_task","parameters":{"search":"написать отчет"},"confidence":0.95}

        "Покажи задачи на сегодня" →
        {"action":"filter_tasks","parameters":{"filters":{"date":"today"}},"confidence":0.95}

        "Сделай две задачи: купить продукты на сегодня и встреча с командой завтра в 10:00" →
        {"action":"create_multiple_tasks","tasks":[{"title":"Купить продукты","due_date":"today"},{"title":"Встреча с командой","due_date":"tomorrow","start_time":"10:00"}],"confidence":0.92}

        "Сделай задачу тренировка важной" →
        {"action":"update_task","parameters":{"search":"тренировка","updates":{"priority":"high"}},"confidence":0.93}

        "Купить три по кетам молока на сегоня" (с опечатками) →
        {"action":"create_task","parameters":{"title":"Купить три пакета молока","due_date":"today"},"confidence":0.90}

        "Создай задачу ремонт квартиры с подзадачами: купить краску, нанять мастера" →
        {"action":"create_task","parameters":{"title":"Ремонт квартиры","subtasks":["Купить краску","Нанять мастера"]},"confidence":0.89}

        Теперь обработай команду:
        PROMPT;

    private string $ollamaUrl;

    private string $model;

    private HttpClientInterface $httpClient;

    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        ParameterBagInterface $params,
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;

        // Получаем URL Ollama из параметров или используем дефолтный
        $this->ollamaUrl = $params->has('ollama_url')
            ? $params->get('ollama_url')
            : 'http://ollama:11434';

        // Модель Qwen 2.5 1.5B - оптимизирована для скорости на CPU
        $this->model = $params->has('llm_model')
            ? $params->get('llm_model')
            : self::DEFAULT_MODEL;
    }

    /**
     * Парсинг текстовой команды в структурированный объект
     *
     * @param string $commandText Текст команды от пользователя
     *
     * @throws RuntimeException При ошибке LLM или недоступности сервиса
     *
     * @return ParsedCommand Распарсенная команда
     */
    public function parseCommand(string $commandText): ParsedCommand
    {
        if (empty($commandText)) {
            throw new InvalidArgumentException('Command text cannot be empty');
        }

        $this->logger->info('Parsing command with LLM', [
            'model'   => $this->model,
            'command' => $commandText,
        ]);

        // Попытки с retry при сбоях
        $lastError = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $response = $this->callOllama($commandText);

                // Парсим JSON ответ от LLM
                $parsedData = $this->extractJsonFromResponse($response);

                // Создаем ParsedCommand объект
                $command = ParsedCommand::fromArray($parsedData, $commandText);

                $this->logger->info('Command parsed successfully', [
                    'action'     => $command->action,
                    'confidence' => $command->confidence,
                    'attempt'    => $attempt,
                ]);

                return $command;

            } catch (Exception $e) {
                $lastError = $e;
                $this->logger->warning('LLM parse attempt failed', [
                    'attempt' => $attempt,
                    'error'   => $e->getMessage(),
                ]);

                if ($attempt < self::MAX_RETRIES) {
                    // Задержка перед повторной попыткой
                    usleep(self::RETRY_DELAY_MS * 1000 * $attempt);
                }
            }
        }

        // Если все попытки провалились, возвращаем команду с низкой уверенностью
        $this->logger->error('All LLM parse attempts failed', [
            'command' => $commandText,
            'error'   => $lastError?->getMessage(),
        ]);

        // Возвращаем ParsedCommand с действием clarification_needed
        return new ParsedCommand(
            action: ParsedCommand::ACTION_CLARIFICATION_NEEDED,
            parameters: [
                'original_text' => $commandText,
                'question'      => 'Извините, не удалось понять команду. Можете перефразировать?',
                'error'         => $lastError?->getMessage(),
            ],
            confidence: 0.1,
            originalText: $commandText,
        );
    }

    /**
     * Проверка доступности Ollama сервиса
     */
    public function isAvailable(): bool
    {
        try {
            $response = $this->httpClient->request('GET', $this->ollamaUrl . '/api/tags', [
                'timeout' => 5.0,
            ]);

            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            $this->logger->warning('Ollama health check failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Получить список доступных моделей
     */
    public function getAvailableModels(): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->ollamaUrl . '/api/tags');
            $data = $response->toArray();

            return array_map(fn ($model) => $model['name'], $data['models'] ?? []);
        } catch (Exception $e) {
            $this->logger->error('Failed to fetch models list', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Вызов Ollama API
     *
     * @throws TransportExceptionInterface
     * @throws RuntimeException
     */
    private function callOllama(string $commandText): array
    {
        $prompt = self::SYSTEM_PROMPT . "\n\nКоманда: \"" . $commandText . '"';

        try {
            $response = $this->httpClient->request('POST', $this->ollamaUrl . '/api/generate', [
                'json' => [
                    'model'   => $this->model,
                    'prompt'  => $prompt,
                    'stream'  => false,
                    'format'  => 'json',
                    'options' => [
                        'temperature' => 0.1,  // Низкая температура для консистентности
                        'top_p'       => 0.9,
                        'num_predict' => 256,  // Ограничение длины ответа
                    ],
                ],
                'timeout' => self::DEFAULT_TIMEOUT,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                throw new RuntimeException(sprintf(
                    'Ollama API returned status %d',
                    $statusCode,
                ));
            }

            $data = $response->toArray();

            if (!isset($data['response'])) {
                throw new RuntimeException('Invalid Ollama response format');
            }

            return ['response' => $data['response']];

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Ollama API transport error', [
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Failed to connect to Ollama: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Извлечение JSON из ответа LLM
     *
     * @throws RuntimeException При невалидном JSON
     */
    private function extractJsonFromResponse(array $response): array
    {
        $text = $response['response'] ?? '';

        // Пытаемся найти JSON в ответе
        // LLM иногда добавляет текст вокруг JSON
        $jsonStart = strpos($text, '{');
        $jsonEnd = strrpos($text, '}');

        if ($jsonStart === false || $jsonEnd === false) {
            throw new RuntimeException('No JSON found in LLM response');
        }

        $jsonString = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);

        // Парсим JSON
        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Invalid JSON from LLM', [
                'raw_response' => $text,
                'extracted'    => $jsonString,
                'error'        => json_last_error_msg(),
            ]);

            throw new RuntimeException('Invalid JSON in LLM response: ' . json_last_error_msg());
        }

        // Валидация обязательных полей
        if (!isset($data['action']) || !isset($data['confidence'])) {
            throw new RuntimeException('Missing required fields in LLM response');
        }

        // Убеждаемся что parameters существует
        if (!isset($data['parameters'])) {
            $data['parameters'] = [];
        }

        return $data;
    }
}
