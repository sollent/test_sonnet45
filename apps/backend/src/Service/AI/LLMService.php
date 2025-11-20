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
 * Сервис для работы с Large Language Model (Qwen 2.5 14B через Ollama)
 *
 * Отвечает за парсинг голосовых команд и конвертацию их в структурированные действия.
 * Использует мощную модель Qwen 2.5 14B для отличного понимания сложных команд.
 * Следует паттерну Adapter для изоляции внешнего API
 */
class LLMService
{
    private const DEFAULT_MODEL = 'qwen2.5:14b';

    private const DEFAULT_TIMEOUT = 120.0; // 2 минуты для qwen2.5:14b (мощная модель)

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY_MS = 500;

    /**
     * Системный промпт для LLM - РАСШИРЕННЫЙ
     *
     * Для модели qwen2.5:1.5b - расширен для лучшего понимания команд
     * Поддерживает все типы операций, множественные задачи, грамматические ошибки
     */
    private const SYSTEM_PROMPT = <<<'PROMPT'
        Ты - ассистент управления задачами. Анализируй голосовые команды на русском и возвращай JSON.

        КРИТИЧЕСКИ ВАЖНО:
        1. Возвращай ТОЛЬКО валидный JSON без пояснений и комментариев
        2. ИЗВЛЕКАЙ дату/время из текста и помещай в отдельные параметры (due_date, start_time)
        3. ИСПРАВЛЯЙ опечатки и грамматические ошибки, но СОХРАНЯЙ смысл
        4. НЕ ПЕРЕФРАЗИРУЙ title сильно - используй слова пользователя, только исправь ошибки
        5. Понимай команды даже с пропущенными запятыми и неправильными окончаниями

        ПРАВИЛА ДЛЯ TITLE:
        - МИНИМАЛЬНАЯ переформулировка! Сохраняй оригинальные слова пользователя
        - "записываться гдоктору" → "Записаться к доктору" (НЕ "Запись в кабинет врача"!)
        - "купить свиноматку" → "Купить свиноматку" (сохраняй как есть!)
        - Исправляй только явные опечатки: "гдоктору" → "к доктору"
        - НЕ заменяй слова на синонимы без необходимости

        ОБРАБОТКА ГРАММАТИЧЕСКИХ ОШИБОК:
        - "Создает две задачи" понимай как "Создай две задачи"
        - "Одно на сегодня" понимай как "Одну на сегодня"
        - Восстанавливай пропущенные запятые по контексту
        - "гдоктору" → "к доктору"
        - "на сегоня" → "на сегодня"

        ДОСТУПНЫЕ ДЕЙСТВИЯ:

        1. create_task - создать одну задачу
           Параметры: title, due_date, start_time, end_time, priority (low/medium/high)

        2. create_multiple_tasks - создать несколько задач
           Возвращай массив tasks с отдельными задачами

        3. complete_task - завершить задачу
           Параметры: search (текст для поиска задачи)

        4. uncomplete_task - отменить завершение (вернуть в работу)
           Параметры: search

        5. update_task - изменить приоритет/статус/название
           Параметры: search, updates: {priority, status, title}

        6. filter_tasks - показать/найти задачи
           Параметры: filters: {date, priority, status, search}

        7. create_subtask - создать подзадачу
           Параметры: parent_search, title

        ПРИОРИТЕТЫ: low (низкий), medium (средний), high (высокий/срочный/важный)
        СТАТУСЫ: pending (в ожидании), in_progress (в работе), completed (завершена)
        ДАТЫ: today, tomorrow, "2024-01-15", "понедельник", "через неделю"

        ФОРМАТ ОТВЕТА:
        {"action":"название","parameters":{...},"confidence":0.0-1.0}

        Для create_multiple_tasks:
        {"action":"create_multiple_tasks","tasks":[{...},{...}],"confidence":0.0-1.0}

        ПРИМЕРЫ:

        === Создание задач ===

        "Создай задачу на завтра записываться гдоктору" →
        {"action":"create_task","parameters":{"title":"Записаться к доктору","due_date":"tomorrow"},"confidence":0.93}

        "Создай срочную задачу позвонить клиенту на сегодня с 14:00 до 15:00" →
        {"action":"create_task","parameters":{"title":"Позвонить клиенту","due_date":"today","start_time":"14:00","end_time":"15:00","priority":"high"},"confidence":0.95}

        "Купить молоко завтра утром" →
        {"action":"create_task","parameters":{"title":"Купить молоко","due_date":"tomorrow","start_time":"09:00"},"confidence":0.92}

        === Несколько задач (ВАЖНО!) ===

        "Создает две задачи. Одно на сегодня купить свиноматку. И одну на завтра купить большого жирного коня." →
        {"action":"create_multiple_tasks","tasks":[{"title":"Купить свиноматку","due_date":"today"},{"title":"Купить большого жирного коня","due_date":"tomorrow"}],"confidence":0.90}

        "Сделай три задачи: купить хлеб на сегодня, позвонить маме завтра и написать отчет на пятницу" →
        {"action":"create_multiple_tasks","tasks":[{"title":"Купить хлеб","due_date":"today"},{"title":"Позвонить маме","due_date":"tomorrow"},{"title":"Написать отчет","due_date":"friday"}],"confidence":0.92}

        "Две срочные задачи - отправить документы и позвонить в банк" →
        {"action":"create_multiple_tasks","tasks":[{"title":"Отправить документы","priority":"high"},{"title":"Позвонить в банк","priority":"high"}],"confidence":0.91}

        === Завершение и отмена ===

        "Завершить задачу написать отчет" →
        {"action":"complete_task","parameters":{"search":"написать отчет"},"confidence":0.95}

        "Отметь задачу купить молоко как выполненную" →
        {"action":"complete_task","parameters":{"search":"купить молоко"},"confidence":0.94}

        "Верни задачу тренировка в работу" →
        {"action":"uncomplete_task","parameters":{"search":"тренировка"},"confidence":0.93}

        === Изменение приоритета/статуса ===

        "Сделай задачу тренировка срочной" →
        {"action":"update_task","parameters":{"search":"тренировка","updates":{"priority":"high"}},"confidence":0.94}

        "Понизь приоритет задачи купить молоко" →
        {"action":"update_task","parameters":{"search":"купить молоко","updates":{"priority":"low"}},"confidence":0.93}

        "Переведи задачу отчет в статус в работе" →
        {"action":"update_task","parameters":{"search":"отчет","updates":{"status":"in_progress"}},"confidence":0.92}

        === Фильтрация ===

        "Покажи задачи на сегодня" →
        {"action":"filter_tasks","parameters":{"filters":{"date":"today"}},"confidence":0.95}

        "Найди срочные задачи" →
        {"action":"filter_tasks","parameters":{"filters":{"priority":"high"}},"confidence":0.94}

        "Покажи незавершенные задачи на эту неделю" →
        {"action":"filter_tasks","parameters":{"filters":{"status":"pending","date":"this_week"}},"confidence":0.93}

        === С опечатками ===

        "Купить три по кетам молока на сегоня" →
        {"action":"create_task","parameters":{"title":"Купить три пакета молока","due_date":"today"},"confidence":0.88}

        "Завиршить задачу отчот" →
        {"action":"complete_task","parameters":{"search":"отчет"},"confidence":0.85}

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
        // Используем host.docker.internal для доступа к нативному Ollama на macOS
        $this->ollamaUrl = $params->has('ollama_url')
            ? $params->get('ollama_url')
            : 'http://host.docker.internal:11434';

        // Модель Qwen 2.5 14B - мощная модель для отличного понимания команд
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
