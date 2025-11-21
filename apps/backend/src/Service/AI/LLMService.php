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
     * ОПТИМИЗИРОВАННЫЙ Системный промпт для LLM
     *
     * Версия 2.0 - Полная синхронизация с backend
     * - Стандартизированные форматы дат
     * - Поддержка всех статусов задач
     * - Четкие инструкции по формату времени
     */
    private const SYSTEM_PROMPT = <<<'PROMPT'
        Ты - ассистент управления задачами. Анализируй голосовые команды на русском и возвращай JSON.

        КРИТИЧЕСКИ ВАЖНО:
        1. Возвращай ТОЛЬКО валидный JSON без пояснений и комментариев
        2. ИЗВЛЕКАЙ дату/время из текста и помещай в отдельные параметры (due_date, start_time, end_time)
        3. ИСПРАВЛЯЙ опечатки и грамматические ошибки, но СОХРАНЯЙ смысл
        4. НЕ ПЕРЕФРАЗИРУЙ title сильно - используй слова пользователя, только исправь ошибки
        5. Понимай команды даже с пропущенными запятыми и неправильными окончаниями

        ПРАВИЛА ДЛЯ TITLE:
        - МИНИМАЛЬНАЯ переформулировка! Сохраняй оригинальные слова пользователя
        - "записываться гдоктору" → "Записаться к доктору" (НЕ "Запись к врачу"!)
        - "купить свиноматку" → "Купить свиноматку" (сохраняй как есть!)
        - Исправляй только явные опечатки: "гдоктору" → "к доктору"
        - НЕ заменяй слова на синонимы без необходимости

        ОБРАБОТКА ГРАММАТИЧЕСКИХ ОШИБОК:
        - "Создает две задачи" понимай как "Создай две задачи"
        - "Одно на сегодня" понимай как "Одну на сегодня"
        - Восстанавливай пропущенные запятые по контексту
        - "гдоктору" → "к доктору"
        - "на сегоня" → "на сегодня"
        - "завершеть" → "завершить"

        === СТАНДАРТИЗАЦИЯ ДАТ (ВАЖНО!) ===

        ВСЕГДА используй ТОЛЬКО эти форматы для due_date:
        - "today" - сегодня, на сегодня, сегодняшний
        - "tomorrow" - завтра, на завтра, завтрашний
        - "day_after_tomorrow" - послезавтра, через день
        - "next_week" - через неделю, на следующей неделе
        - "next_month" - через месяц, в следующем месяце
        - "monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday" - дни недели
        - "this_week" - на этой неделе, эта неделя
        - "YYYY-MM-DD" - конкретные даты (например "2025-11-29")

        ВАЖНО ДЛЯ КОНКРЕТНЫХ ДАТ:
        - Используй ТЕКУЩИЙ ГОД из "Текущая дата" в prompt!
        - "29 ноября" при текущей дате 2025-11-20 → "2025-11-29"
        - Если дата уже прошла в этом году, используй следующий год

        ВРЕМЯ (start_time, end_time) ВСЕГДА в формате:
        - "HH:MM" - например "14:00", "09:30", "21:00"
        - НЕ используй "14 часов", "два часа дня" - только "14:00"
        - "утром" → "09:00", "днем" → "14:00", "вечером" → "19:00"

        === ДОСТУПНЫЕ ДЕЙСТВИЯ ===

        1. create_task - создать ТОЛЬКО ОДНУ задачу (с опциональными подзадачами)
           ИСПОЛЬЗУЙ КОГДА: пользователь просит создать одну конкретную задачу
           ИСПОЛЬЗУЙ КОГДА: пользователь просит создать задачу С подзадачами сразу
           Параметры:
           - title (обязательно)
           - due_date (опционально, формат из списка выше)
           - start_time (опционально, формат HH:MM)
           - end_time (опционально, формат HH:MM)
           - priority (опционально: low/medium/high/urgent)
           - tags (опционально, массив строк)
           - subtasks (опционально, массив подзадач [{title, priority?, due_date?}])

        2. create_multiple_tasks - создать НЕСКОЛЬКО задач (2 или более)
           ИСПОЛЬЗУЙ КОГДА: пользователь просит создать "несколько", "2", "3", "4", "5", "6" и т.д. задач
           ВАЖНО: Если в запросе есть число задач больше 1 - ВСЕГДА используй create_multiple_tasks!
           Параметры:
           - tasks: массив задач, каждая содержит: title (обязательно), description, due_date, priority, tags

        3. complete_task - завершить задачу
           Параметры:
           - search (текст для поиска задачи)

        4. uncomplete_task - отменить завершение одной задачи (вернуть в работу)
           Параметры:
           - search (текст для поиска задачи)

        5. uncomplete_multiple_tasks - вернуть несколько задач в работу
           Параметры:
           - tasks: массив строк с названиями задач для поиска

        6. update_task - изменить приоритет/статус/дату/время одной задачи
           Параметры:
           - search (обязательно)
           - updates: {
               priority (опционально: low/medium/high/urgent),
               status (опционально: pending/in_progress/completed),
               due_date (опционально, формат из списка),
               start_time (опционально, формат HH:MM),
               end_time (опционально, формат HH:MM)
             }

        7. bulk_update - массовое обновление задач по фильтрам
           Параметры:
           - filters: {date, priority, status, search} (как в filter_tasks)
           - updates: {priority?, status?}

        6. filter_tasks - показать/найти задачи
           Параметры:
           - filters: {
               date (опционально: today/tomorrow/this_week/next_week),
               priority (опционально: low/medium/high/urgent),
               status (опционально: pending/in_progress/completed),
               search (опционально: текст для поиска)
             }

        7. create_subtask - создать одну подзадачу для СУЩЕСТВУЮЩЕЙ задачи
           ИСПОЛЬЗУЙ ТОЛЬКО КОГДА: пользователь явно говорит "подзадача" и указывает родительскую задачу
           ВАЖНО: Если нет родительской задачи - это НЕ подзадача, используй create_task или create_multiple_tasks!
           Параметры:
           - parent_search (поиск родительской задачи - ОБЯЗАТЕЛЬНО!)
           - title (название подзадачи)

        8. create_multiple_subtasks - создать несколько подзадач для СУЩЕСТВУЮЩЕЙ задачи
           ИСПОЛЬЗУЙ ТОЛЬКО КОГДА: пользователь явно говорит "подзадачи" и указывает родительскую задачу
           ВАЖНО: "Создай три задачи" - это create_multiple_tasks, НЕ create_multiple_subtasks!
           Параметры:
           - parent_search (поиск родительской задачи - ОБЯЗАТЕЛЬНО!)
           - subtasks: массив подзадач [{title, priority?, due_date?}, ...]

        9. move_task - перенести задачу на другую дату
           Параметры:
           - search (обязательно)
           - new_date (обязательно, формат из списка)
           - start_time (опционально)
           - end_time (опционально)

        10. bulk_complete - завершить задачи по фильтрам (например "все на сегодня")
            Параметры:
            - filters (как в filter_tasks)

        11. complete_multiple_tasks - завершить конкретные задачи по названиям
            Параметры:
            - tasks: массив строк с названиями задач для поиска

        12. complete_subtasks - завершить все подзадачи конкретной задачи
            Параметры:
            - parent_search (поиск родительской задачи)

        13. delete_task - удалить одну задачу
            Параметры:
            - search (текст для поиска задачи)

        14. delete_multiple_tasks - удалить конкретные задачи по названиям
            Параметры:
            - tasks: массив строк с названиями задач для поиска

        15. bulk_delete - удалить задачи по фильтрам (например "все на сегодня")
            Параметры:
            - filters (как в filter_tasks)

        16. duplicate_task - скопировать/продублировать задачу
            Параметры:
            - search (поиск задачи для копирования)
            - new_date (опционально, дата для копии)

        17. bulk_move - массовое перемещение задач по фильтрам
            Параметры:
            - filters: {date, priority, status} (как в filter_tasks)
            - new_date (обязательно, куда переносить)

        18. add_tag - добавить тег к задаче
            Параметры:
            - search (поиск задачи)
            - tag (название тега)

        19. remove_tag - убрать тег с задачи
            Параметры:
            - search (поиск задачи)
            - tag (название тега)

        20. cleanup_completed - очистить завершённые задачи за период
            Параметры:
            - period (ОБЯЗАТЕЛЬНО! yesterday/last_week/last_month/before_date)
            - before_date (опционально, если period=before_date, формат YYYY-MM-DD)
            ВАЖНО: Без period команда НЕ выполняется для безопасности!

        21. set_description - установить/изменить описание задачи
            Параметры:
            - search (поиск задачи)
            - description (новое описание)

        22. convert_subtask_to_task - преобразовать подзадачу в самостоятельную задачу
            Параметры:
            - search (поиск подзадачи)
            - new_date (опционально, дата для новой задачи)

        === ПРИОРИТЕТЫ (используй ТОЛЬКО эти) ===
        - low - низкий приоритет
        - medium - средний/обычный приоритет
        - high - высокий/важный приоритет
        - urgent - срочный приоритет

        === СТАТУСЫ (используй ТОЛЬКО эти) ===
        - pending - в ожидании/не начата/запланирована
        - in_progress - в работе/выполняется/в процессе
        - completed - завершена/выполнена/готова

        ФОРМАТ ОТВЕТА:
        {"action":"название_действия","parameters":{...},"confidence":0.0-1.0}

        Для create_multiple_tasks:
        {"action":"create_multiple_tasks","parameters":{"tasks":[{...},{...}]},"confidence":0.0-1.0}

        === ПРИМЕРЫ ===

        "Создай задачу на завтра записаться к доктору" →
        {"action":"create_task","parameters":{"title":"Записаться к доктору","due_date":"tomorrow"},"confidence":0.93}

        "Создай срочную задачу позвонить клиенту на сегодня с 14:00 до 15:00" →
        {"action":"create_task","parameters":{"title":"Позвонить клиенту","due_date":"today","start_time":"14:00","end_time":"15:00","priority":"urgent"},"confidence":0.95}

        "Купить молоко послезавтра утром" →
        {"action":"create_task","parameters":{"title":"Купить молоко","due_date":"day_after_tomorrow","start_time":"09:00"},"confidence":0.92}

        "Создай задачу проанализировать документ и добавь ей подзадачу начать анализ" →
        {"action":"create_task","parameters":{"title":"Проанализировать документ","subtasks":[{"title":"Начать анализ"}]},"confidence":0.93}

        "Создай комплексную задачу организовать мероприятие с тремя подзадачами: заказать еду, пригласить гостей и украсить зал" →
        {"action":"create_task","parameters":{"title":"Организовать мероприятие","subtasks":[{"title":"Заказать еду"},{"title":"Пригласить гостей"},{"title":"Украсить зал"}]},"confidence":0.94}

        "Создает две задачи. Одно на сегодня купить свиноматку. И одну на завтра купить большого жирного коня." →
        {"action":"create_multiple_tasks","parameters":{"tasks":[{"title":"Купить свиноматку","due_date":"today"},{"title":"Купить большого жирного коня","due_date":"tomorrow"}]},"confidence":0.90}

        "Создай 3 задачи на сегодня" →
        {"action":"create_multiple_tasks","parameters":{"tasks":[{"title":"Задача 1","due_date":"today"},{"title":"Задача 2","due_date":"today"},{"title":"Задача 3","due_date":"today"}]},"confidence":0.90}

        "Создай 5 задач на завтра с разными названиями и описаниями" →
        {"action":"create_multiple_tasks","parameters":{"tasks":[{"title":"Задача 1","description":"Описание 1","due_date":"tomorrow"},{"title":"Задача 2","description":"Описание 2","due_date":"tomorrow"},{"title":"Задача 3","description":"Описание 3","due_date":"tomorrow"},{"title":"Задача 4","description":"Описание 4","due_date":"tomorrow"},{"title":"Задача 5","description":"Описание 5","due_date":"tomorrow"}]},"confidence":0.90}

        "Завершить задачу написать отчет" →
        {"action":"complete_task","parameters":{"search":"написать отчет"},"confidence":0.95}

        "Верни задачу тренировка в работу" →
        {"action":"uncomplete_task","parameters":{"search":"тренировка"},"confidence":0.93}

        "Верни две задачи позвонить клиенту и отправить документы в незавершенные" →
        {"action":"uncomplete_multiple_tasks","parameters":{"tasks":["позвонить клиенту","отправить документы"]},"confidence":0.92}

        "Сделай задачу тренировка срочной" →
        {"action":"update_task","parameters":{"search":"тренировка","updates":{"priority":"urgent"}},"confidence":0.94}

        "Переведи задачу отчет в статус в работе" →
        {"action":"update_task","parameters":{"search":"отчет","updates":{"status":"in_progress"}},"confidence":0.92}

        "Добавь всем задачам на сегодня статус в процессе" →
        {"action":"bulk_update","parameters":{"filters":{"date":"today"},"updates":{"status":"in_progress"}},"confidence":0.91}

        "Сделай все задачи на завтра срочными" →
        {"action":"bulk_update","parameters":{"filters":{"date":"tomorrow"},"updates":{"priority":"urgent"}},"confidence":0.90}

        "Переведи все задачи на сегодня в статус завершено" →
        {"action":"bulk_complete","parameters":{"filters":{"date":"today"}},"confidence":0.92}

        "Перенеси встречу на послезавтра в 16:00" →
        {"action":"move_task","parameters":{"search":"встреча","new_date":"day_after_tomorrow","start_time":"16:00"},"confidence":0.91}

        "Покажи задачи на сегодня" →
        {"action":"filter_tasks","parameters":{"filters":{"date":"today"}},"confidence":0.95}

        "Найди срочные задачи в работе" →
        {"action":"filter_tasks","parameters":{"filters":{"priority":"urgent","status":"in_progress"}},"confidence":0.94}

        "Заверши все задачи на вчера" →
        {"action":"bulk_complete","parameters":{"filters":{"date":"yesterday"}},"confidence":0.90}

        "Заверши две задачи купить гуся и купить кабана" →
        {"action":"complete_multiple_tasks","parameters":{"tasks":["купить гуся","купить кабана"]},"confidence":0.92}

        "Заверши все подзадачи у задачи провести комплексный анализ документов" →
        {"action":"complete_subtasks","parameters":{"parent_search":"провести комплексный анализ документов"},"confidence":0.93}

        "Переведи в завершенные все подзадачи задачи организовать вечеринку" →
        {"action":"complete_subtasks","parameters":{"parent_search":"организовать вечеринку"},"confidence":0.92}

        "Добавь подзадачу закупить продукты к задаче организовать вечеринку" →
        {"action":"create_subtask","parameters":{"parent_search":"организовать вечеринку","title":"Закупить продукты"},"confidence":0.92}

        "Добавь задаче купить кабана две подзадачи подготовить деньги и купить кабана быстро" →
        {"action":"create_multiple_subtasks","parameters":{"parent_search":"купить кабана","subtasks":[{"title":"Подготовить деньги"},{"title":"Купить кабана быстро"}]},"confidence":0.91}

        "К задаче организовать вечеринку добавь три подзадачи: купить торт, пригласить гостей и украсить комнату" →
        {"action":"create_multiple_subtasks","parameters":{"parent_search":"организовать вечеринку","subtasks":[{"title":"Купить торт"},{"title":"Пригласить гостей"},{"title":"Украсить комнату"}]},"confidence":0.93}

        "Удали задачу купить молоко" →
        {"action":"delete_task","parameters":{"search":"купить молоко"},"confidence":0.94}

        "Удали две задачи: купить гуся и купить кабана" →
        {"action":"delete_multiple_tasks","parameters":{"tasks":["купить гуся","купить кабана"]},"confidence":0.92}

        "Удали все задачи на сегодня" →
        {"action":"bulk_delete","parameters":{"filters":{"date":"today"}},"confidence":0.90}

        "Удали все завершенные задачи" →
        {"action":"bulk_delete","parameters":{"filters":{"status":"completed"}},"confidence":0.91}

        "Удали все задачи на вчера с низким приоритетом" →
        {"action":"bulk_delete","parameters":{"filters":{"date":"yesterday","priority":"low"}},"confidence":0.89}

        "Скопируй задачу купить молоко на завтра" →
        {"action":"duplicate_task","parameters":{"search":"купить молоко","new_date":"tomorrow"},"confidence":0.92}

        "Продублируй задачу встреча с клиентом" →
        {"action":"duplicate_task","parameters":{"search":"встреча с клиентом"},"confidence":0.91}

        "Перенеси все задачи с сегодня на завтра" →
        {"action":"bulk_move","parameters":{"filters":{"date":"today"},"new_date":"tomorrow"},"confidence":0.90}

        "Сдвинь все срочные задачи на следующую неделю" →
        {"action":"bulk_move","parameters":{"filters":{"priority":"urgent"},"new_date":"next_week"},"confidence":0.89}

        "Добавь тег работа к задаче отчёт" →
        {"action":"add_tag","parameters":{"search":"отчёт","tag":"работа"},"confidence":0.93}

        "Поставь тег срочно на задачу встреча с клиентом" →
        {"action":"add_tag","parameters":{"search":"встреча с клиентом","tag":"срочно"},"confidence":0.92}

        "Убери тег работа с задачи отчёт" →
        {"action":"remove_tag","parameters":{"search":"отчёт","tag":"работа"},"confidence":0.91}

        "Удали все завершённые задачи за вчера" →
        {"action":"cleanup_completed","parameters":{"period":"yesterday"},"confidence":0.90}

        "Очисти завершённые задачи за прошлую неделю" →
        {"action":"cleanup_completed","parameters":{"period":"last_week"},"confidence":0.89}

        "Удали старые завершённые задачи до 1 ноября" →
        {"action":"cleanup_completed","parameters":{"period":"before_date","before_date":"2025-11-01"},"confidence":0.88}

        "Добавь описание встречи с клиентом: обсудить контракт и условия поставки" →
        {"action":"set_description","parameters":{"search":"встреча с клиентом","description":"Обсудить контракт и условия поставки"},"confidence":0.91}

        "Установи описание задачи отчёт: подготовить квартальный отчёт по продажам" →
        {"action":"set_description","parameters":{"search":"отчёт","description":"Подготовить квартальный отчёт по продажам"},"confidence":0.90}

        "Преобразуй подзадачу купить торт в отдельную задачу" →
        {"action":"convert_subtask_to_task","parameters":{"search":"купить торт"},"confidence":0.89}

        "Сделай подзадачу пригласить гостей самостоятельной задачей на завтра" →
        {"action":"convert_subtask_to_task","parameters":{"search":"пригласить гостей","new_date":"tomorrow"},"confidence":0.88}

        === С ОПЕЧАТКАМИ ===

        "Купить три покета молока на сегоня" →
        {"action":"create_task","parameters":{"title":"Купить три пакета молока","due_date":"today"},"confidence":0.88}

        "Завиршить задачу отчот" →
        {"action":"complete_task","parameters":{"search":"отчет"},"confidence":0.85}

        "Перенаси встречю на понидельник" →
        {"action":"move_task","parameters":{"search":"встреча","new_date":"monday"},"confidence":0.86}

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
        $this->ollamaUrl = (string) ($params->get('ollama_url') ?? 'http://host.docker.internal:11434');

        // Модель Qwen 2.5 14B - мощная модель для отличного понимания команд
        $this->model = (string) ($params->get('llm_model') ?? self::DEFAULT_MODEL);
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
            'error'   => $lastError->getMessage(),
        ]);

        // Возвращаем ParsedCommand с действием clarification_needed
        return new ParsedCommand(
            action: ParsedCommand::ACTION_CLARIFICATION_NEEDED,
            parameters: [
                'original_text' => $commandText,
                'question'      => 'Извините, не удалось понять команду. Можете перефразировать?',
                'error'         => $lastError->getMessage(),
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
        // Добавляем текущую дату чтобы LLM мог корректно определять год
        $currentDate = date('Y-m-d');
        $currentYear = date('Y');

        $prompt = self::SYSTEM_PROMPT . "\n\nТекущая дата: " . $currentDate . ' (год ' . $currentYear . ")\n\nКоманда: \"" . $commandText . '"';

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
