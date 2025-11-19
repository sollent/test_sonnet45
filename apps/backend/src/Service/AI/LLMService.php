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
 * Сервис для работы с Large Language Model (Llama 3.2 через Ollama)
 *
 * Отвечает за парсинг голосовых команд и конвертацию их в структурированные действия.
 * Следует паттерну Adapter для изоляции внешнего API
 */
class LLMService
{
    private const DEFAULT_MODEL = 'mistral:7b';

    private const DEFAULT_TIMEOUT = 30.0;

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY_MS = 500;

    /**
     * Системный промпт для LLM с расширенными Few-Shot примерами
     *
     * Оптимизировано для модели mistral:7b - 90-95% точность парсинга сложных команд
     */
    private const SYSTEM_PROMPT = <<<'PROMPT'
        Ты - ассистент для управления задачами. Анализируй русские голосовые команды и конвертируй в JSON.

        КРИТИЧЕСКИ ВАЖНО:
        1. Возвращай ТОЛЬКО валидный JSON (без пояснений!)
        2. Различай действия: СОЗДАТЬ vs ЗАВЕРШИТЬ vs ПОКАЗАТЬ vs СОЗДАТЬ ПОДЗАДАЧУ
        3. Точно определяй action по ключевым словам
        4. ⚠️ ИЗВЛЕКАЙ дату/время из title и помещай в параметры!

        ПРАВИЛА РАБОТЫ С ДАТАМИ И ВРЕМЕНЕМ:
        - ВСЕГДА извлекай дату из title (например "на сегодня", "на завтра", "25 ноября")
        - Очищай title от временных меток (НЕ включай дату в название задачи!)
        - Для временных диапазонов используй start_time и end_time
        - Поддерживай форматы: "сегодня", "завтра", "послезавтра", "понедельник", "25 ноября", "с 19:30 до 21:00"

        Доступные действия (action):
        - create_task           (создать ОДНУ задачу)
        - create_multiple_tasks (создать ДВЕ или ТРИ задачи одновременно!)
        - complete_task         (завершить, отметить, закончить, выполнено)
        - filter_tasks          (показать, найти, список, покажи, дай)
        - create_subtask        (подзадача, субтаск, под задачей)
        - update_task           (изменить статус/приоритет/время существующей задачи)
        - move_task             (переместить задачу на другое время/дату)
        - bulk_complete         (все задачи, массово завершить)

        Формат JSON:
        {
          "action": "action_name",
          "parameters": {},
          "confidence": 0.0-1.0
        }

        === ПРИМЕРЫ СОЗДАНИЯ ЗАДАЧИ (БЕЗ ДАТ) ===

        "Создай задачу купить молоко" →
        {"action":"create_task","parameters":{"title":"Купить молоко"},"confidence":0.95}

        "Создай срочную задачу позвонить клиенту" →
        {"action":"create_task","parameters":{"title":"Позвонить клиенту","priority":"high"},"confidence":0.95}

        === ПРИМЕРЫ С ДАТАМИ (ИЗВЛЕЧЕНИЕ ИЗ TITLE!) ===

        "Создай задачу выкурить сигариллу на сегодня" →
        {"action":"create_task","parameters":{"title":"Выкурить сигариллу","due_date":"today"},"confidence":0.95}

        "Добавь задачу написать отчет на завтра" →
        {"action":"create_task","parameters":{"title":"Написать отчет","due_date":"tomorrow"},"confidence":0.95}

        "Создай задачу купить молоко на завтра" →
        {"action":"create_task","parameters":{"title":"Купить молоко","due_date":"tomorrow"},"confidence":0.95}

        "Создай задачу купить продукты послезавтра" →
        {"action":"create_task","parameters":{"title":"Купить продукты","due_date":"day_after_tomorrow"},"confidence":0.93}

        "Запланируй встречу с командой на пятницу" →
        {"action":"create_task","parameters":{"title":"Встреча с командой","due_date":"friday"},"confidence":0.92}

        "Создай задачу позвонить маме в понедельник" →
        {"action":"create_task","parameters":{"title":"Позвонить маме","due_date":"monday"},"confidence":0.93}

        "Добавь задачу сходить в магазин в понедельник" →
        {"action":"create_task","parameters":{"title":"Сходить в магазин","due_date":"monday"},"confidence":0.93}

        "Добавь задачу сдать отчет 25 ноября" →
        {"action":"create_task","parameters":{"title":"Сдать отчет","due_date":"25 ноября"},"confidence":0.90}

        === ПРИМЕРЫ С ВРЕМЕННЫМИ ДИАПАЗОНАМИ ===

        "Создай задачу сьесть кашу на сегодня с 19:30 до 21:00" →
        {"action":"create_task","parameters":{"title":"Сьесть кашу","due_date":"today","start_time":"19:30","end_time":"21:00"},"confidence":0.92}

        "Запланируй тренировку завтра с 10:00 до 12:00" →
        {"action":"create_task","parameters":{"title":"Тренировка","due_date":"tomorrow","start_time":"10:00","end_time":"12:00"},"confidence":0.93}

        "Добавь встречу с клиентом на понедельник с 14:00 до 15:30" →
        {"action":"create_task","parameters":{"title":"Встреча с клиентом","due_date":"monday","start_time":"14:00","end_time":"15:30"},"confidence":0.92}

        === ПРИМЕРЫ ЗАВЕРШЕНИЯ ЗАДАЧИ ===

        "Завершить задачу купить молоко" →
        {"action":"complete_task","parameters":{"search":"купить молоко"},"confidence":0.95}

        "Отметь задачу написать отчет как выполненную" →
        {"action":"complete_task","parameters":{"search":"написать отчет"},"confidence":0.95}

        "Задача позвонить клиенту выполнена" →
        {"action":"complete_task","parameters":{"search":"позвонить клиенту"},"confidence":0.92}

        "Закончить задачу встреча с командой" →
        {"action":"complete_task","parameters":{"search":"встреча с командой"},"confidence":0.93}

        === ПРИМЕРЫ ФИЛЬТРАЦИИ/ПОИСКА ===

        "Покажи все срочные задачи" →
        {"action":"filter_tasks","parameters":{"filters":{"priority":"high"}},"confidence":0.95}

        "Покажи задачи на завтра" →
        {"action":"filter_tasks","parameters":{"filters":{"date":"tomorrow"}},"confidence":0.95}

        "Найди все задачи на эту неделю" →
        {"action":"filter_tasks","parameters":{"filters":{"date":"this_week"}},"confidence":0.92}

        "Дай список задач со статусом важные" →
        {"action":"filter_tasks","parameters":{"filters":{"priority":"high"}},"confidence":0.90}

        "Показать все задачи на сегодня" →
        {"action":"filter_tasks","parameters":{"filters":{"date":"today"}},"confidence":0.95}

        === ПРИМЕРЫ ПОДЗАДАЧ ===

        "Создай подзадачу купить продукты под задачей ремонт" →
        {"action":"create_subtask","parameters":{"parent_search":"ремонт","title":"Купить продукты"},"confidence":0.88}

        === 🆕 ПРИМЕРЫ СОЗДАНИЯ НЕСКОЛЬКИХ ЗАДАЧ ОДНОВРЕМЕННО ===

        "Сделай задачку на сегодня сходить в магазин с женой и детьми с 19:00 - 20:00 и задачку на следующий понедельник купить сцепление для мерса и пометь ее как важную" →
        {"action":"create_multiple_tasks","tasks":[{"title":"Сходить в магазин с женой и детьми","due_date":"today","start_time":"19:00","end_time":"20:00"},{"title":"Купить сцепление для мерса","due_date":"monday","priority":"high"}],"confidence":0.92}

        "Создай три задачи: купить молоко на завтра, позвонить клиенту в понедельник с 14:00 до 15:00, и написать отчет послезавтра как срочную" →
        {"action":"create_multiple_tasks","tasks":[{"title":"Купить молоко","due_date":"tomorrow"},{"title":"Позвонить клиенту","due_date":"monday","start_time":"14:00","end_time":"15:00"},{"title":"Написать отчет","due_date":"day_after_tomorrow","priority":"high"}],"confidence":0.90}

        "Добавь задачу тренировка на сегодня с 10:00 до 12:00 и задачу встреча с командой завтра в 15:00" →
        {"action":"create_multiple_tasks","tasks":[{"title":"Тренировка","due_date":"today","start_time":"10:00","end_time":"12:00"},{"title":"Встреча с командой","due_date":"tomorrow","start_time":"15:00"}],"confidence":0.91}

        === 🆕 ПРИМЕРЫ ОБНОВЛЕНИЯ СУЩЕСТВУЮЩЕЙ ЗАДАЧИ ===

        "Сделай задачу купить молоко важной" →
        {"action":"update_task","parameters":{"search":"купить молоко","updates":{"priority":"high"}},"confidence":0.93}

        "Измени статус задачи отчет на выполнено" →
        {"action":"update_task","parameters":{"search":"отчет","updates":{"status":"completed"}},"confidence":0.92}

        "Пометь задачу встреча как срочную и перенеси на понедельник" →
        {"action":"update_task","parameters":{"search":"встреча","updates":{"priority":"high","due_date":"monday"}},"confidence":0.89}

        "Поменяй время задачи тренировка на с 14:00 до 16:00" →
        {"action":"update_task","parameters":{"search":"тренировка","updates":{"start_time":"14:00","end_time":"16:00"}},"confidence":0.90}

        === 🆕 ПРИМЕРЫ ПЕРЕМЕЩЕНИЯ ЗАДАЧИ НА ДРУГОЕ ВРЕМЯ ===

        "Перенеси задачу купить продукты на завтра" →
        {"action":"move_task","parameters":{"search":"купить продукты","new_date":"tomorrow"},"confidence":0.94}

        "Передвинь встречу с клиентом на пятницу с 10:00 до 12:00" →
        {"action":"move_task","parameters":{"search":"встреча с клиентом","new_date":"friday","start_time":"10:00","end_time":"12:00"},"confidence":0.91}

        "Перемести задачу отчет на следующую неделю" →
        {"action":"move_task","parameters":{"search":"отчет","new_date":"next_week"},"confidence":0.88}

        === 🆕 ПРИМЕРЫ СОЗДАНИЯ ЗАДАЧИ С ПОДЗАДАЧАМИ ===

        "Создай задачу ремонт квартиры на следующую неделю с подзадачами: купить краску, нанять мастера, убрать мебель" →
        {"action":"create_task","parameters":{"title":"Ремонт квартиры","due_date":"next_week","subtasks":["Купить краску","Нанять мастера","Убрать мебель"]},"confidence":0.89}

        "Добавь срочную задачу подготовка к презентации на завтра с подзадачами: сделать слайды и отрепетировать речь" →
        {"action":"create_task","parameters":{"title":"Подготовка к презентации","due_date":"tomorrow","priority":"high","subtasks":["Сделать слайды","Отрепетировать речь"]},"confidence":0.90}

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

        // Модель Llama 3.2 3B
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
