# Стандарты Кодирования и Принципы Проектирования

> **САМЫЙ ВАЖНЫЙ ДОКУМЕНТ!** Это определяет, как ВЕСЬ код в этом проекте должен быть написан. Каждый класс, каждый метод, каждая строка должны следовать этим принципам.

---

## Содержание

- [SOLID Принципы](#solid-принципы)
- [GRASP Принципы](#grasp-принципы)
- [GoF Паттерны Проектирования](#gof-паттерны-проектирования)
- [Стандарты Backend PHP 8.3](#стандарты-backend-php-83)
- [Стандарты Frontend TypeScript](#стандарты-frontend-typescript)
- [Правила Качества Кода](#правила-качества-кода)
- [Распространенные Антипаттерны, Которых Следует Избегать](#распространенные-антипаттерны-которых-следует-избегать)

---

## SOLID Принципы

SOLID - это фундамент нашей архитектуры. Каждый класс в этом проекте следует этим 5 принципам.

### S - Принцип Единственной Ответственности

**Правило:** Каждый класс должен иметь только ОДНУ причину для изменения. Одна ответственность, одна цель.

#### Пример из Проекта: TaskResponseDto

```php
// расположение: /backend/src/Dto/Response/Task/TaskResponseDto.php

// ✅ ХОРОШО - Единственная ответственность: Объект Передачи Данных
final class TaskResponseDto implements \JsonSerializable
{
    // ТОЛЬКО свойства данных
    public int $id;
    public string $title;
    public TaskStatus $status;

    // ТОЛЬКО методы преобразования данных
    public static function fromEntity(Task $task): self { }
    public static function fromArray(array $data): self { }
    public function jsonSerialize(): array { }

    // НЕТ бизнес-логики
    // НЕТ запросов к базе данных
    // НЕТ обработки HTTP
}
```

```php
// ❌ ПЛОХО - Множественные ответственности
class TaskDto
{
    public int $id;

    // НЕПРАВИЛЬНО! Логика базы данных в DTO
    public function saveToDatabase() { }

    // НЕПРАВИЛЬНО! Бизнес-логика в DTO
    public function validatePriority() { }

    // НЕПРАВИЛЬНО! Обработка HTTP в DTO
    public function sendEmailNotification() { }
}
```

**Почему это важно:** Когда вам нужно изменить способ валидации задач, вам не нужно модифицировать DTO. Каждый класс имеет одну четкую цель.

---

### O - Принцип Открытости/Закрытости

**Правило:** Классы должны быть ОТКРЫТЫ для расширения, но ЗАКРЫТЫ для модификации. Используйте интерфейсы и наследование.

#### Пример из Проекта: Стратегии Повторения

```php
// расположение: /backend/src/Service/Recurrence/RecurrenceStrategyInterface.php

// ✅ ХОРОШО - Интерфейс определяет контракт
interface RecurrenceStrategyInterface
{
    public function calculateNextOccurrence(
        \DateTimeInterface $currentDate,
        RecurrenceRule $rule
    ): ?\DateTimeInterface;
}

// ✅ ХОРОШО - Каждая стратегия реализует интерфейс
final class DailyRecurrenceStrategy implements RecurrenceStrategyInterface
{
    public function calculateNextOccurrence(
        \DateTimeInterface $currentDate,
        RecurrenceRule $rule
    ): ?\DateTimeInterface {
        return $currentDate->modify("+{$rule->getInterval()} days");
    }
}

final class WeeklyRecurrenceStrategy implements RecurrenceStrategyInterface
{
    public function calculateNextOccurrence(
        \DateTimeInterface $currentDate,
        RecurrenceRule $rule
    ): ?\DateTimeInterface {
        return $currentDate->modify("+{$rule->getInterval()} weeks");
    }
}

// РАСШИРЕНИЕ: Добавить новую стратегию БЕЗ модификации существующего кода
final class MonthlyRecurrenceStrategy implements RecurrenceStrategyInterface
{
    public function calculateNextOccurrence(
        \DateTimeInterface $currentDate,
        RecurrenceRule $rule
    ): ?\DateTimeInterface {
        return $currentDate->modify("+{$rule->getInterval()} months");
    }
}
```

```php
// ❌ ПЛОХО - Гигантский if/else, который требует модификации для каждого нового типа
class RecurrenceCalculator
{
    public function calculate($type, $date, $interval)
    {
        // НЕПРАВИЛЬНО! Нужно модифицировать этот метод для добавления новых типов
        if ($type === 'daily') {
            return $date->modify("+$interval days");
        } elseif ($type === 'weekly') {
            return $date->modify("+$interval weeks");
        } elseif ($type === 'monthly') {
            return $date->modify("+$interval months");
        }
        // Добавляете yearly? Нужно модифицировать этот класс!
    }
}
```

**Почему это важно:** Добавление нового типа повторения (yearly, custom) требует НУЛЕВЫХ изменений существующего кода. Просто создайте новый класс стратегии.

---

### L - Принцип Подстановки Барбары Лисков

**Правило:** Подтипы должны быть взаимозаменяемы со своими базовыми типами. Если S - подтип T, то объекты типа T могут быть заменены объектами типа S.

#### Пример из Проекта: Сервисы Уведомлений

```php
// расположение: /backend/src/Service/Notification/Interface/NotificationServiceInterface.php

// ✅ ХОРОШО - Базовый интерфейс
interface NotificationServiceInterface
{
    public function send(User $user, string $subject, string $message): bool;
    public function sendBulk(array $users, string $subject, string $message): int;
    public function supports(string $channel): bool;
}

// ✅ ХОРОШО - Реализация соблюдает контракт
final class EmailNotificationService implements NotificationServiceInterface
{
    public function send(User $user, string $subject, string $message): bool
    {
        // Работает точно так, как обещает интерфейс
        $email = (new Email())
            ->to($user->getEmail())
            ->subject($subject)
            ->html($message);

        $this->mailer->send($email);
        return true;
    }

    // Остальные методы точно следуют контракту
}

// Можно менять реализации без поломки кода
final class SmsNotificationService implements NotificationServiceInterface { }
final class PushNotificationService implements NotificationServiceInterface { }
```

```php
// ❌ ПЛОХО - Нарушает контракт
class BrokenNotification implements NotificationServiceInterface
{
    public function send(User $user, string $subject, string $message): bool
    {
        // НЕПРАВИЛЬНО! Выбрасывает исключение, когда интерфейс этого не обещает
        throw new \Exception("Not implemented");
    }

    public function sendBulk(array $users, string $subject, string $message): int
    {
        // НЕПРАВИЛЬНО! Возвращает void, когда интерфейс требует int
        foreach ($users as $user) {
            $this->send($user, $subject, $message);
        }
    }
}
```

**Почему это важно:** Вы можете заменить EmailNotificationService на SmsNotificationService без изменения ЛЮБОГО кода, который его использует.

---

### I - Принцип Разделения Интерфейса

**Правило:** Много специфичных интерфейсов лучше, чем один универсальный интерфейс. Не заставляйте классы реализовывать методы, которые им не нужны.

#### Пример из Проекта: Генерация Отчетов

```php
// расположение: /backend/src/Service/Report/Interface/ReportGeneratorInterface.php

// ✅ ХОРОШО - Маленький, сфокусированный интерфейс
interface ReportGeneratorInterface
{
    public function generate(User $user, array $data): string;
    public function supports(string $format): bool;
}

// ✅ ХОРОШО - Отдельный интерфейс для продвинутых функций (не все генераторы нуждаются в этом)
interface AdvancedReportGeneratorInterface extends ReportGeneratorInterface
{
    public function generateWithTemplate(User $user, array $data, string $template): string;
    public function setWatermark(string $text): void;
}

// Реализация выбирает, что реализовывать
final class PdfReportGenerator implements AdvancedReportGeneratorInterface
{
    public function generate(User $user, array $data): string { }
    public function supports(string $format): bool { }
    public function generateWithTemplate(User $user, array $data, string $template): string { }
    public function setWatermark(string $text): void { }
}

// Простая реализация не нуждается в продвинутых функциях
final class CsvReportGenerator implements ReportGeneratorInterface
{
    public function generate(User $user, array $data): string { }
    public function supports(string $format): bool { }
    // Не нужны шаблоны или водяные знаки!
}
```

```php
// ❌ ПЛОХО - Толстый интерфейс принуждает к ненужным реализациям
interface ReportManagerInterface
{
    public function generate(): string;
    public function generateWithTemplate(): string;   // Не всем это нужно
    public function setWatermark(): void;            // Не всем это нужно
    public function exportMetrics(): array;          // Не всем это нужно
    public function sendToAnalytics(): void;         // Не всем это нужно
}

// НЕПРАВИЛЬНО! Простая реализация вынуждена реализовывать всё
class CsvReportGenerator implements ReportManagerInterface
{
    public function generateWithTemplate(): string { return ''; } // Не используется
    public function setWatermark(): void { } // Не используется
    public function exportMetrics(): array { return []; } // Не используется
    public function sendToAnalytics(): void { } // Не используется
}
```

**Почему это важно:** Маленькие интерфейсы = гибкие реализации. Вы не платите за функции, которые не используете.

---

### D - Принцип Инверсии Зависимостей

**Правило:** Зависьте от абстракций (интерфейсов), а не от конкретных реализаций (классов). Высокоуровневые модули не должны зависеть от низкоуровневых модулей.

#### Пример из Проекта: TaskService

```php
// расположение: /backend/src/Service/TaskService.php

// ✅ ХОРОШО - Зависит от интерфейсов, а не от конкретных классов
final class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,              // Интерфейс
        private readonly TranslatorInterface $translator,             // Интерфейс
        private readonly EventDispatcherInterface $eventDispatcher,   // Интерфейс
        private readonly LoggerInterface $logger,                      // Интерфейс
    ) {
    }

    public function createTask(CreateTaskDto $dto, User $user): Task
    {
        // Работает с ЛЮБОЙ реализацией этих интерфейсов
        $task = new Task();
        // ... настройка задачи

        $this->taskRepository->save($task);  // Может быть Doctrine, MongoDB, и т.д.
        $this->translator->trans('task.created');   // Любая система переводов
        $this->eventDispatcher->dispatch();  // Любая система событий
        $this->logger->info();               // Любой логгер

        return $task;
    }
}
```

```php
// ❌ ПЛОХО - Зависит от конкретных классов
class TaskService
{
    private DoctrineTaskRepository $repository;     // НЕПРАВИЛЬНО! Конкретный класс
    private SymfonyTranslator $translator;          // НЕПРАВИЛЬНО! Конкретный класс
    private FileLogger $logger;                     // НЕПРАВИЛЬНО! Конкретный класс

    public function __construct()
    {
        // НЕПРАВИЛЬНО! Создание зависимостей внутри
        $this->repository = new DoctrineTaskRepository();
        $this->translator = new SymfonyTranslator('en');
        $this->logger = new FileLogger('/var/log/app.log');
    }

    // Теперь вы ПРИВЯЗАНЫ к этим конкретным реализациям
    // Не можете тестировать, не можете заменять, не можете расширять
}
```

**Почему это важно:** Легкое тестирование (мокайте интерфейсы), легкая замена (Redis → Memcached), легкое расширение (добавляйте новые реализации).

---

## GRASP Принципы

GRASP (General Responsibility Assignment Software Patterns) - паттерны для назначения ответственностей классам.

### 1. Информационный Эксперт

**Правило:** Назначайте ответственность классу, который имеет информацию, необходимую для её выполнения.

```php
// ✅ ХОРОШО - Сущность Task знает, как рассчитать свой собственный прогресс выполнения
final class Task
{
    public function getCompletionProgress(): float
    {
        // Этот класс имеет подзадачи, поэтому он эксперт
        $totalSubtasks = $this->subtasks->count();
        if ($totalSubtasks === 0) {
            return $this->isCompleted ? 100.0 : 0.0;
        }

        $completedSubtasks = $this->subtasks->filter(
            fn(Task $subtask) => $subtask->isCompleted()
        )->count();

        return ($completedSubtasks / $totalSubtasks) * 100;
    }
}

// ✅ ХОРОШО - Сущность User знает, как проверить права доступа
final class User
{
    public function canEditTask(Task $task): bool
    {
        return $task->getUser() === $this;
    }
}
```

```php
// ❌ ПЛОХО - Внешний класс рассчитывает прогресс без данных задачи
class TaskProgressCalculator
{
    public function calculate(int $taskId): float
    {
        // НЕПРАВИЛЬНО! Нужно получать данные задачи, неэффективно
        $task = $this->repository->find($taskId);
        $subtasks = $this->repository->findSubtasks($taskId);
        // Должно быть ответственностью Task!
    }
}
```

---

### 2. Создатель

**Правило:** Класс B должен создавать экземпляры класса A, если верно одно из условий:
- B содержит или агрегирует A
- B записывает экземпляры A
- B активно использует A
- B имеет инициализирующие данные для A

```php
// ✅ ХОРОШО - TaskService создает Tasks (имеет инициализирующие данные из DTO)
final class TaskService
{
    public function createTask(CreateTaskDto $dto, User $user): Task
    {
        // Сервис - СОЗДАТЕЛЬ - имеет данные DTO, ссылку на User
        $task = new Task();
        $task->setTitle($dto->title);
        $task->setDescription($dto->description);
        $task->setUser($user);
        $task->setPriority($dto->priority);

        return $task;
    }
}

// ✅ ХОРОШО - Task создает Subtasks (содержит/агрегирует их)
final class Task
{
    public function addSubtask(string $title): Task
    {
        // Task - СОЗДАТЕЛЬ - содержит подзадачи
        $subtask = new Task();
        $subtask->setTitle($title);
        $subtask->setParentTask($this);
        $subtask->setUser($this->user);

        $this->subtasks->add($subtask);
        return $subtask;
    }
}
```

```php
// ❌ ПЛОХО - Контроллер создает сущности (не его ответственность)
class TaskController
{
    public function create(Request $request): JsonResponse
    {
        // НЕПРАВИЛЬНО! Контроллер не имеет бизнес-логики
        $task = new Task();
        $task->setTitle($request->get('title'));
        // Должен делегировать Service!
    }
}
```

---

### 3. Контроллер (Тонкие Контроллеры)

**Правило:** Контроллеры обрабатывают системные события (HTTP запросы), но делегируют работу сервисам.

```php
// ✅ ХОРОШО - Тонкий контроллер (только HTTP забота)
// расположение: /backend/src/Controller/Api/TaskController.php

final class TaskController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,        // Делегировать сервису
        private readonly NotificationService $notificationService
    ) {
    }

    #[Route('/api/tasks', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateTaskDto $dto
    ): JsonResponse {
        // ТОЛЬКО HTTP заботы:
        // 1. Получить аутентифицированного пользователя
        $user = $this->getUser();

        // 2. Делегировать сервису (бизнес-логика)
        $task = $this->taskService->createTask($dto, $user);

        // 3. Конвертировать в DTO
        $responseDto = TaskResponseDto::fromEntity($task);

        // 4. Вернуть HTTP ответ
        return $this->json($responseDto, Response::HTTP_CREATED);
    }
}
```

```php
// ❌ ПЛОХО - Толстый контроллер (бизнес-логика внутри)
class TaskController
{
    public function create(Request $request): JsonResponse
    {
        // НЕПРАВИЛЬНО! Бизнес-логика в контроллере
        $task = new Task();
        $task->setTitle($request->get('title'));

        if (strlen($task->getTitle()) < 3) {
            throw new \Exception('Title too short');
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // НЕПРАВИЛЬНО! Логика уведомлений в контроллере
        $this->emailService->send($this->getUser(), 'Task created', 'Your task was created');

        // НЕПРАВИЛЬНО! Отправка событий в контроллере
        $this->eventDispatcher->dispatch(new TaskCreatedEvent($task));

        return $this->json($task);
    }
}
```

---

### 4. Низкая Связанность

**Правило:** Минимизируйте зависимости между классами. Классы должны быть настолько независимыми, насколько это возможно.

```php
// ✅ ХОРОШО - Низкая связанность через внедрение зависимостей
final class TaskService
{
    public function __construct(
        private readonly TaskRepository $repository,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        // Только 3 зависимости, все через интерфейсы
    }
}

// ✅ ХОРОШО - NotificationService не знает о TaskService
final readonly class EmailNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
    ) {
        // Только 2 зависимости, сфокусированы на email уведомлениях
    }
}
```

```php
// ❌ ПЛОХО - Высокая связанность (много зависимостей)
class TaskManager
{
    private TaskRepository $repository;
    private LoggerInterface $logger;
    private EventDispatcher $eventDispatcher;
    private TranslatorInterface $translator;
    private EmailService $emailService;           // Слишком много!
    private NotificationService $notificationService;
    private AnalyticsService $analyticsService;
    private SearchIndexer $searchIndexer;
    private AuditLogger $auditLogger;

    // НЕПРАВИЛЬНО! Слишком много ответственностей, слишком много зависимостей
}
```

---

### 5. Высокая Связность

**Правило:** Держите связанную функциональность вместе. Каждый класс должен иметь сфокусированный, связный набор ответственностей.

```php
// ✅ ХОРОШО - Высокая связность (все методы связаны с email уведомлениями)
final readonly class EmailNotificationService
{
    // Все методы об EMAIL УВЕДОМЛЕНИЯХ
    public function sendTaskCreatedNotification(User $user, Task $task): bool { }
    public function sendTaskDueNotification(User $user, Task $task): bool { }
    public function sendTaskCompletedNotification(User $user, Task $task): bool { }
    public function sendBulkNotification(array $users, string $subject, string $message): int { }
    public function queueNotification(User $user, string $subject, string $message): void { }
}

// ✅ ХОРОШО - Высокая связность (все методы связаны с расчетами аналитики)
final readonly class AnalyticsCalculationService
{
    // Все методы о РАСЧЕТАХ АНАЛИТИКИ
    public function calculateCompletionRate(User $user): float { }
    public function calculateProductivityTrend(User $user, int $days): array { }
    public function calculateTaskDistribution(User $user): array { }
}
```

```php
// ❌ ПЛОХО - Низкая связность (несвязанные методы в одном классе)
class TaskHelper
{
    public function sendEmail() { }          // Email
    public function logActivity() { }        // Логирование
    public function generatePDF() { }        // Генерация PDF
    public function translateMessage() { }   // Перевод
    public function calculateTax() { }       // Бизнес-логика
    // НЕПРАВИЛЬНО! Слишком много несвязанных ответственностей
}
```

---

### 6. Полиморфизм

**Правило:** Используйте полиморфизм для обработки альтернатив на основе типа.

```php
// ✅ ХОРОШО - Полиморфные стратегии повторения
interface RecurrenceStrategyInterface
{
    public function calculateNextOccurrence(
        \DateTimeInterface $currentDate,
        RecurrenceRule $rule
    ): ?\DateTimeInterface;
}

final class RecurrenceService
{
    /** @var array<string, RecurrenceStrategyInterface> */
    private array $strategies;

    public function __construct(
        DailyRecurrenceStrategy $daily,
        WeeklyRecurrenceStrategy $weekly,
        MonthlyRecurrenceStrategy $monthly,
        YearlyRecurrenceStrategy $yearly,
    ) {
        $this->strategies = [
            'daily' => $daily,
            'weekly' => $weekly,
            'monthly' => $monthly,
            'yearly' => $yearly,
        ];
    }

    public function calculateNext(RecurrenceRule $rule): ?\DateTimeInterface
    {
        // Полиморфизм - не нужно if/else!
        $strategy = $this->strategies[$rule->getRecurrenceType()->value];
        return $strategy->calculateNextOccurrence(
            $rule->getNextOccurrenceDate(),
            $rule
        );
    }
}
```

```php
// ❌ ПЛОХО - Нет полиморфизма, гигантский if/else
class RecurrenceCalculator
{
    public function calculate(RecurrenceRule $rule): ?\DateTimeInterface
    {
        // НЕПРАВИЛЬНО! If/else вместо полиморфизма
        if ($rule->getType() === 'daily') {
            return $this->calculateDaily($rule);
        } elseif ($rule->getType() === 'weekly') {
            return $this->calculateWeekly($rule);
        } elseif ($rule->getType() === 'monthly') {
            return $this->calculateMonthly($rule);
        }
        // Добавляете новый тип? Модифицируйте этот метод!
    }
}
```

---

### 7. Чистая Выдумка

**Правило:** Создавайте искусственные классы (не доменные объекты), когда это нужно для хорошего дизайна.

```php
// ✅ ХОРОШО - Чистая выдумка (не сущность домена, но нужна для архитектуры)
final readonly class ReportFileNameGenerator
{
    // Не реальная концепция, но критична для архитектуры генерации отчетов
    public function generateTaskReportName(User $user, string $format): string
    {
        return sprintf(
            'task_report_%s_%s.%s',
            $user->getId(),
            date('Y-m-d_His'),
            $format
        );
    }

    public function generateAnalyticsReportName(User $user, string $type, array $params = []): string
    {
        return sprintf('analytics_%s_%s_%s.pdf', $type, $user->getId(), date('Ymd'));
    }
}

// ✅ ХОРОШО - Чистая выдумка для преобразования DTO
final class TaskResponseDto implements \JsonSerializable
{
    // Не сущность домена, но критична для API слоя
    public static function fromEntity(Task $task): self { }
    public static function fromArray(array $data): self { }
    public function jsonSerialize(): array { }
}
```

---

### 8. Посредник

**Правило:** Используйте промежуточные объекты для уменьшения связанности и увеличения переиспользуемости.

```php
// ✅ ХОРОШО - Посредник через LoggerService (промежуточный слой)
final class TaskService
{
    public function __construct(
        private readonly LoggerInterface $logger,  // Посредник!
    ) {
    }

    public function createTask(CreateTaskDto $dto, User $user): Task
    {
        // Сервис не общается с конкретной реализацией логгера
        // Идет через LoggerInterface (слой посредника)
        $this->logger->info('Creating task', [
            'user_id' => $user->getId(),
            'title' => $dto->title,
        ]);

        $task = new Task();
        // ... создание задачи

        return $task;
    }
}

// ✅ ХОРОШО - EventDispatcher обеспечивает посредничество для обработки событий
final class TaskEventDispatcher
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,  // Посредник!
    ) {
    }

    // Абстрагирует сложность отправки событий
    public function dispatchTaskCreated(Task $task): void
    {
        // Сложная логика событий скрыта за простым интерфейсом
        $this->dispatcher->dispatch(new TaskCreatedEvent($task));
    }
}
```

```php
// ❌ ПЛОХО - Нет посредника (прямая связанность)
class TaskService
{
    private FileLogger $fileLogger;

    public function createTask(CreateTaskDto $dto, User $user): Task
    {
        // НЕПРАВИЛЬНО! Сервис общается напрямую с конкретным логгером
        $logMessage = sprintf(
            "[%s] Creating task for user %d: %s\n",
            date('Y-m-d H:i:s'),
            $user->getId(),
            $dto->title
        );
        file_put_contents('/var/log/tasks.log', $logMessage, FILE_APPEND);

        $task = new Task();
        // ... создание задачи

        return $task;
    }
}
```

---

### 9. Защита от Вариаций

**Правило:** Защищайте элементы от вариаций в других элементах, оборачивая их стабильным интерфейсом.

```php
// ✅ ХОРОШО - Защищены от изменений реализации файлового хранилища
interface FileStorageInterface
{
    public function store(string $path, string $content): bool;
    public function retrieve(string $path): string;
    public function delete(string $path): bool;
}

// Реализация 1: Локальная файловая система
final class LocalFileStorage implements FileStorageInterface { }

// Реализация 2: S3 (можно заменять без изменения кода!)
final class S3Storage implements FileStorageInterface { }

// Реализация 3: В памяти (для тестирования)
final class InMemoryStorage implements FileStorageInterface { }

// Сервисы ЗАЩИЩЕНЫ от вариаций
final class ReportService
{
    public function __construct(
        private readonly FileStorageInterface $storage  // Стабильный интерфейс!
    ) {
        // Не важно, Local, S3 или In-Memory
        // Интерфейс защищает от вариаций
    }
}
```

---

## GoF Паттерны Проектирования

### 1. Паттерн Репозиторий

**Цель:** Отделить логику доступа к данным от бизнес-логики.

```php
// ✅ ХОРОШО - Репозиторий абстрагирует базу данных
// расположение: /backend/src/Repository/Database/TaskRepository.php

interface TaskRepositoryInterface
{
    public function find(int $id): ?Task;
    public function findByUser(User $user): array;
    public function save(Task $task): void;
    public function remove(Task $task): void;
}

final class TaskRepository implements TaskRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function findActiveByUser(User $user, TaskFilterDto $filters): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.isArchived = :archived')
            ->setParameter('user', $user)
            ->setParameter('archived', false);

        // Сложная логика запросов изолирована здесь
        if ($filters->status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $filters->status);
        }

        return $qb->getQuery()->getResult();
    }
}

// Сервис использует репозиторий (не знает о Doctrine)
final class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository
    ) {
    }

    public function getActiveTasks(User $user, TaskFilterDto $filters): array
    {
        // Чисто! Нет SQL, нет Doctrine, только доменная логика
        return $this->repository->findActiveByUser($user, $filters);
    }
}
```

---

### 2. Паттерн Фабрика (Статические Фабричные Методы)

**Цель:** Инкапсулировать логику создания объектов.

```php
// ✅ ХОРОШО - Фабричные методы в DTO
// расположение: /backend/src/Dto/Response/Task/TaskResponseDto.php

final class TaskResponseDto implements \JsonSerializable
{
    // Фабричный метод: База данных → DTO
    public static function fromEntity(
        Task $task,
        bool $includeSubtasks = false,
        bool $includeMeta = true
    ): self {
        $dto = new self();
        $dto->id = $task->getId();
        $dto->title = $task->getTitle();
        $dto->status = $task->getStatus();

        if ($includeSubtasks) {
            $dto->subtasks = array_map(
                fn($subtask) => self::fromEntity($subtask, true, $includeMeta),
                $task->getSubtasks()->toArray()
            );
        }

        return $dto;
    }

    // Фабричный метод: Кэш Redis → DTO
    public static function fromArray(array $data): self
    {
        if (!isset($data['id'], $data['title'], $data['status'])) {
            throw new \InvalidArgumentException('Missing required fields');
        }

        $dto = new self();
        $dto->id = (int) $data['id'];
        $dto->title = (string) $data['title'];
        $dto->status = TaskStatus::from($data['status']);

        return $dto;
    }
}

// Использование чистое и выразительное
$dtoFromDb = TaskResponseDto::fromEntity($task);
$dtoFromCache = TaskResponseDto::fromArray($cachedData);
```

---

### 3. Паттерн Стратегия

**Цель:** Определить семейство алгоритмов, инкапсулировать каждый и сделать их взаимозаменяемыми.

```php
// ✅ ХОРОШО - Стратегии экспорта
// расположение: /backend/src/Service/Export/TaskExportService.php

// Интерфейс стратегии
interface ExportStrategyInterface
{
    public function export(array $tasks, User $user): string;
    public function getContentType(): string;
}

// СТРАТЕГИЯ 1: Экспорт CSV
final class CsvExportStrategy implements ExportStrategyInterface
{
    public function export(array $tasks, User $user): string
    {
        $csv = "ID,Title,Status,Priority\n";
        foreach ($tasks as $task) {
            $csv .= sprintf(
                "%d,%s,%s,%s\n",
                $task->getId(),
                $task->getTitle(),
                $task->getStatus()->value,
                $task->getPriority()->value
            );
        }
        return $csv;
    }

    public function getContentType(): string
    {
        return 'text/csv';
    }
}

// СТРАТЕГИЯ 2: Экспорт PDF
final class PdfExportStrategy implements ExportStrategyInterface
{
    public function export(array $tasks, User $user): string
    {
        // Генерация PDF с правильным форматированием
        return $this->pdfGenerator->generate($tasks);
    }

    public function getContentType(): string
    {
        return 'application/pdf';
    }
}

// Контекст использует стратегии
final class TaskExportService
{
    public function __construct(
        private CsvExportStrategy $csvStrategy,
        private PdfExportStrategy $pdfStrategy,
    ) {}

    public function export(array $tasks, User $user, string $format): string
    {
        $strategy = match($format) {
            'csv' => $this->csvStrategy,
            'pdf' => $this->pdfStrategy,
            default => throw new \InvalidArgumentException("Unsupported format: $format"),
        };

        return $strategy->export($tasks, $user);
    }
}
```

**Когда использовать каждую стратегию:**

- **CSV:** Для простого экспорта данных, совместимости с электронными таблицами
- **PDF:** Для форматированных отчетов, профессиональных документов

---

### 4. Паттерн Наблюдатель (Подписчики Событий)

**Цель:** Определить зависимость один-ко-многим. Когда один объект меняет состояние, все зависимые уведомляются.

```php
// ✅ ХОРОШО - Подписчик событий наблюдает за изменениями сущностей
// расположение: /backend/src/EventSubscriber/CacheInvalidationSubscriber.php

final readonly class CacheInvalidationSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        // Паттерн Наблюдатель: Слушать события Doctrine
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        // Реагировать на изменения
        if ($entity instanceof Task) {
            $this->invalidateTaskCache($entity, 'update');
        } elseif ($entity instanceof Tag) {
            $this->invalidateTagCache($entity, 'update');
        }
    }

    private function invalidateTaskCache(Task $task, string $operation): void
    {
        $user = $task->getUser();

        // Инвалидировать списки задач
        $this->taskCache->invalidateTaskLists($user);

        // Инвалидировать статистику
        $this->taskCache->invalidateStatistics($user);

        // Инвалидировать аналитику
        $this->analyticsCache->invalidateAll($user);
    }
}
```

**Преимущества:**
- Разделены: TaskService не знает об инвалидации кэша
- Автоматически: Кэш инвалидируется при любом изменении сущности
- Расширяемо: Добавляйте новых наблюдателей без модификации существующего кода

---

### 5. Паттерн Строитель (Конструирование DTO)

**Цель:** Отделить конструирование сложного объекта от его представления.

```php
// ✅ ХОРОШО - TaskResponseDto действует как строитель для сложного представления задачи
final class TaskResponseDto implements \JsonSerializable
{
    public static function fromEntity(
        Task $task,
        bool $includeSubtasks = false,
        bool $includeMeta = true
    ): self {
        $dto = new self();

        // Шаг 1: Базовые свойства
        $dto->id = $task->getId();
        $dto->title = $task->getTitle();
        $dto->status = $task->getStatus();

        // Шаг 2: Условные метаданные
        if ($includeMeta) {
            $dto->createdAt = $task->getCreatedAt();
            $dto->updatedAt = $task->getUpdatedAt();
        }

        // Шаг 3: Счетчики подзадач
        $subtasks = $task->getSubtasks();
        $dto->subtaskCount = $subtasks->count();
        $dto->completedSubtaskCount = $subtasks->filter(
            fn(Task $subtask) => $subtask->isCompleted()
        )->count();

        // Шаг 4: Условные вложенные подзадачи
        if ($includeSubtasks) {
            $dto->subtasks = array_map(
                fn($subtask) => self::fromEntity($subtask, true, $includeMeta),
                $subtasks->toArray()
            );
        }

        // Шаг 5: Преобразование тегов
        $dto->tags = array_map(
            static fn($tag) => [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
                'color' => $tag->getColor(),
            ],
            $task->getTags()->toArray()
        );

        return $dto;
    }
}

// Использование показывает гибкость
$lightDto = TaskResponseDto::fromEntity($task, false, false);     // Минимальный
$fullDto = TaskResponseDto::fromEntity($task, true, true);        // Полный
$listDto = TaskResponseDto::fromEntity($task, false, true);       // Для списков
```

---

## Стандарты Backend PHP 8.3

### Соглашения об Именовании

```php
// PascalCase для классов
final class TaskService { }
final class TaskResponseDto { }
final class EmailNotificationService { }

// camelCase для методов и переменных
public function createTask() { }
public function getActiveTasks() { }
private string $userName;
private int $taskCount;

// SCREAMING_SNAKE_CASE для констант
private const RETRY_DELAY_MS = 1000;
private const MAX_RETRY_ATTEMPTS = 3;
public const MAX_SUBTASKS_DEPTH = 5;

// snake_case для столбцов базы данных (файлы миграций)
$table->addColumn('created_at', 'datetime');
$table->addColumn('due_date', 'datetime_immutable');
$table->addColumn('is_completed', 'boolean');
```

### Типизация ВЕЗДЕ

```php
// ✅ ХОРОШО - Полная типизация
final class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,           // Типизация
        private readonly NotificationService $notificationService, // Типизация
        private readonly EventDispatcherInterface $eventDispatcher, // Типизация
        private readonly LoggerInterface $logger,                  // Типизация
    ) {
    }

    public function createTask(CreateTaskDto $dto, User $user): Task  // Возвращаемый тип
    {
        $task = new Task();
        // ...
        return $task;
    }

    public function getActiveTasks(User $user, TaskFilterDto $filters): array  // Возвращаемый тип
    {
        return $this->taskRepository->findActiveByUser($user, $filters);
    }
}
```

```php
// ❌ ПЛОХО - Нет типизации
class TaskService
{
    private $repository;  // НЕПРАВИЛЬНО! Нет типа
    private $cache;       // НЕПРАВИЛЬНО! Нет типа

    public function create($dto, $user)  // НЕПРАВИЛЬНО! Нет типов
    {
        // ...
        return $task;  // НЕПРАВИЛЬНО! Нет возвращаемого типа
    }
}
```

### Readonly Свойства (PHP 8.1+)

```php
// ✅ ХОРОШО - Readonly свойства предотвращают случайную мутацию
final class TaskResponseDto
{
    public readonly int $id;
    public readonly string $title;
    public readonly TaskStatus $status;

    // Или весь класс readonly (PHP 8.2+)
}

final readonly class EmailNotificationService
{
    // Все свойства автоматически readonly
    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
    ) {
    }
}
```

### Продвижение Свойств в Конструкторе (PHP 8.0+)

```php
// ✅ ХОРОШО - Современный синтаксис PHP 8.0+
final class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger,
    ) {
        // Свойства объявлены и инициализированы автоматически!
    }
}
```

```php
// ❌ ПЛОХО - Старый синтаксис PHP 7 (многословный)
class TaskService
{
    private TaskRepository $taskRepository;
    private NotificationService $notificationService;
    private LoggerInterface $logger;

    public function __construct(
        TaskRepository $taskRepository,
        NotificationService $notificationService,
        LoggerInterface $logger
    ) {
        $this->taskRepository = $taskRepository;
        $this->notificationService = $notificationService;
        $this->logger = $logger;
    }
}
```

### Перечисления Вместо Констант (PHP 8.1+)

```php
// ✅ ХОРОШО - Перечисление с методами
enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getColor(): string
    {
        return match($this) {
            self::PENDING => '#94a3b8',
            self::IN_PROGRESS => '#3b82f6',
            self::COMPLETED => '#22c55e',
            self::CANCELLED => '#ef4444',
        };
    }

    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'В ожидании',
            self::IN_PROGRESS => 'В работе',
            self::COMPLETED => 'Выполнена',
            self::CANCELLED => 'Отменена',
        };
    }
}

// Использование
$task->setStatus(TaskStatus::IN_PROGRESS);
$color = $task->getStatus()->getColor();
```

```php
// ❌ ПЛОХО - Константы (старый способ)
class TaskStatus
{
    public const PENDING = 'pending';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED = 'completed';

    // НЕПРАВИЛЬНО! Нет типобезопасности, нет методов
}

// НЕПРАВИЛЬНО! Можно использовать невалидные значения
$task->setStatus('invalid_status');  // Нет ошибки!
```

### Match Выражения (PHP 8.0+)

```php
// ✅ ХОРОШО - Match выражение (типобезопасное, исчерпывающее)
public function getTtl(string $type): int
{
    return match($type) {
        'task_list' => 300,
        'task_stats' => 300,
        'analytics_overview' => 600,
        'analytics_dashboard' => 900,
        'analytics_heatmap' => 1800,
        default => 900,
    };
}

// ✅ ХОРОШО - Match с перечислениями
public function getStatusColor(TaskStatus $status): string
{
    return match($status) {
        TaskStatus::PENDING => '#94a3b8',
        TaskStatus::IN_PROGRESS => '#3b82f6',
        TaskStatus::COMPLETED => '#22c55e',
        TaskStatus::CANCELLED => '#ef4444',
        // Не нужен default - исчерпывающее!
    };
}
```

```php
// ❌ ПЛОХО - Switch оператор (многословный, не исчерпывающий)
public function getStatusColor($status): string
{
    switch($status) {
        case 'pending':
            return '#94a3b8';
        case 'in_progress':
            return '#3b82f6';
        case 'completed':
            return '#22c55e';
        default:
            return '#000000';
    }
}
```

### Именованные Аргументы (PHP 8.0+)

```php
// ✅ ХОРОШО - Именованные аргументы для ясности
$notification = $this->notificationService->send(
    user: $user,
    subject: 'Задача Создана',
    message: $this->translator->trans('task.created', ['title' => $task->getTitle()])
);

$dto = TaskResponseDto::fromEntity(
    task: $task,
    includeSubtasks: true,
    includeMeta: false
);
```

```php
// ❌ ПЛОХО - Позиционные аргументы (неясно)
$notification = $this->notificationService->send(
    $user,
    'Задача Создана',
    $this->translator->trans('task.created', ['title' => $task->getTitle()])
);

$dto = TaskResponseDto::fromEntity($task, true, false);  // Что означают true/false?
```

---

## Стандарты Frontend TypeScript

### Строгий Режим (Без 'any')

```typescript
// tsconfig.json
{
  "compilerOptions": {
    "strict": true,
    "noImplicitAny": true,
    "strictNullChecks": true,
    "strictFunctionTypes": true
  }
}
```

```typescript
// ✅ ХОРОШО - Полная типизация
interface Task {
  id: number
  title: string
  status: TaskStatus
  priority: TaskPriority
  dueDate: string | null
  tags: Tag[]
  subtasks: Task[]
}

function updateTask(task: Task, updates: Partial<Task>): Task {
  return { ...task, ...updates }
}

const tasks = ref<Task[]>([])
const loading = ref<boolean>(false)
const error = ref<string | null>(null)
```

```typescript
// ❌ ПЛОХО - Использование 'any'
function updateTask(task: any, updates: any): any {  // НЕПРАВИЛЬНО!
  return { ...task, ...updates }
}

const tasks = ref([])  // НЕПРАВИЛЬНО! Нет типа
const data: any = {}   // НЕПРАВИЛЬНО! 'any' отключает проверку типов
```

### Типизируйте Всё (Props, Emits, State)

```vue
<script setup lang="ts">
// ✅ ХОРОШО - Типизированные props
interface Props {
  task: Task
  readonly?: boolean
  showSubtasks?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  readonly: false,
  showSubtasks: true
})

// ✅ ХОРОШО - Типизированные emits
interface Emits {
  (e: 'update:task', task: Task): void
  (e: 'delete', taskId: number): void
  (e: 'subtask-added', subtask: Task): void
}

const emit = defineEmits<Emits>()

// ✅ ХОРОШО - Типизированное реактивное состояние
const tasks = ref<Task[]>([])
const selectedTask = ref<Task | null>(null)
const filters = ref<TaskFilters>({
  status: 'all',
  priority: 'all',
  tags: []
})
</script>
```

```vue
<script setup lang="ts">
// ❌ ПЛОХО - Нет типов
const props = defineProps({
  task: Object,  // НЕПРАВИЛЬНО! Должно быть Task
  readonly: Boolean
})

const emit = defineEmits(['update', 'delete'])  // НЕПРАВИЛЬНО! Нет типов параметров

const tasks = ref([])  // НЕПРАВИЛЬНО! Нет типа
</script>
```

### Интерфейсы для Объектов

```typescript
// ✅ ХОРОШО - Интерфейсы определяют структуру
interface Task {
  id: number
  title: string
  description: string | null
  status: TaskStatus
  priority: TaskPriority
  dueDate: string | null
  completedAt: string | null
  tags: Tag[]
  subtasks: Task[]
  isCompleted: boolean
  isOverdue: boolean
}

interface TaskFilters {
  status: TaskStatus | 'all'
  priority: TaskPriority | 'all'
  tags: number[]
  search: string
  dateFrom: string | null
  dateTo: string | null
}

interface ApiResponse<T> {
  data: T
  message?: string
  errors?: Record<string, string[]>
}
```

### Защита Типов

```typescript
// ✅ ХОРОШО - Защита типов для проверки типов во время выполнения
function isTask(value: unknown): value is Task {
  return (
    typeof value === 'object' &&
    value !== null &&
    'id' in value &&
    'title' in value &&
    'status' in value
  )
}

function isTasks(value: unknown): value is Task[] {
  return Array.isArray(value) && value.every(isTask)
}

// Использование
const response = await api.getTasks()
if (isTasks(response)) {
  tasks.value = response
} else {
  console.error('Invalid tasks response')
}
```

### Только Composition API (Без Options API)

```vue
<script setup lang="ts">
// ✅ ХОРОШО - Composition API
import { ref, computed, onMounted } from 'vue'
import { useTaskStore } from '@/stores/taskStore'

const taskStore = useTaskStore()

const tasks = computed(() => taskStore.tasks)
const loading = ref(false)

onMounted(async () => {
  await taskStore.fetchTasks()
})

async function handleUpdate(task: Task) {
  await taskStore.updateTask(task)
}
</script>
```

```vue
<script lang="ts">
// ❌ ПЛОХО - Options API (не используйте)
export default {
  data() {
    return {
      tasks: [],
      loading: false
    }
  },
  computed: {
    completedTasks() {
      return this.tasks.filter(t => t.isCompleted)
    }
  },
  methods: {
    async handleUpdate(task) {
      // ...
    }
  }
}
</script>
```

### Умные/Глупые Компоненты

```vue
<!-- УМНЫЙ КОМПОНЕНТ (контейнер) -->
<!-- расположение: /frontend/src/views/TaskListView.vue -->
<script setup lang="ts">
// Умный: Имеет бизнес-логику, доступ к хранилищу, вызовы API
import { useTaskStore } from '@/stores/taskStore'
import TaskList from '@/components/tasks/TaskList.vue'

const taskStore = useTaskStore()

const tasks = computed(() => taskStore.tasks)
const loading = computed(() => taskStore.loading)

onMounted(async () => {
  await taskStore.fetchTasks()
})

async function handleTaskUpdate(task: Task) {
  await taskStore.updateTask(task)
}

async function handleTaskDelete(taskId: number) {
  await taskStore.deleteTask(taskId)
}
</script>

<template>
  <TaskList
    :tasks="tasks"
    :loading="loading"
    @update:task="handleTaskUpdate"
    @delete:task="handleTaskDelete"
  />
</template>
```

```vue
<!-- ГЛУПЫЙ КОМПОНЕНТ (представление) -->
<!-- расположение: /frontend/src/components/tasks/TaskList.vue -->
<script setup lang="ts">
// Глупый: Только получает props, эмитит события, нет бизнес-логики
interface Props {
  tasks: Task[]
  loading: boolean
}

const props = defineProps<Props>()

interface Emits {
  (e: 'update:task', task: Task): void
  (e: 'delete:task', taskId: number): void
}

const emit = defineEmits<Emits>()

// Нет доступа к хранилищу, нет вызовов API!
// Только представление и эмиссия событий
</script>

<template>
  <div class="task-list">
    <TaskCard
      v-for="task in tasks"
      :key="task.id"
      :task="task"
      @update="emit('update:task', $event)"
      @delete="emit('delete:task', $event)"
    />
  </div>
</template>
```

---

## Правила Качества Кода

### DRY (Не Повторяйтесь)

```php
// ✅ ХОРОШО - Извлечение в переиспользуемый метод
final class TaskResponseDto
{
    public static function fromEntity(Task $task, bool $includeSubtasks = false): self
    {
        $dto = new self();
        $dto->id = $task->getId();
        $dto->title = $task->getTitle();

        $dto->tags = self::mapTags($task->getTags());  // Переиспользуемый

        if ($includeSubtasks) {
            $dto->subtasks = array_map(
                fn($subtask) => self::fromEntity($subtask, true),
                $task->getSubtasks()->toArray()
            );
        }

        return $dto;
    }

    // Переиспользуемое преобразование тегов
    private static function mapTags(Collection $tags): array
    {
        return array_map(
            static fn($tag) => [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
                'color' => $tag->getColor(),
            ],
            $tags->toArray()
        );
    }
}
```

```php
// ❌ ПЛОХО - Повторяющийся код
final class TaskResponseDto
{
    public static function fromEntity(Task $task): self
    {
        $dto = new self();

        // НЕПРАВИЛЬНО! Преобразование тегов повторяется
        $dto->tags = array_map(
            static fn($tag) => [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
                'color' => $tag->getColor(),
            ],
            $task->getTags()->toArray()
        );

        // НЕПРАВИЛЬНО! Тот же код повторяется для подзадач
        $dto->subtasks = array_map(
            fn($subtask) => [
                'id' => $subtask->getId(),
                'tags' => array_map(
                    static fn($tag) => [
                        'id' => $tag->getId(),
                        'name' => $tag->getName(),
                        'color' => $tag->getColor(),
                    ],
                    $subtask->getTags()->toArray()
                ),
            ],
            $task->getSubtasks()->toArray()
        );
    }
}
```

### KISS (Держите Это Простым)

```php
// ✅ ХОРОШО - Просто и ясно
public function isOverdue(): bool
{
    return $this->dueDate !== null
        && $this->dueDate < new \DateTimeImmutable()
        && !$this->isCompleted;
}

public function getCompletionProgress(): float
{
    $totalSubtasks = $this->subtasks->count();

    if ($totalSubtasks === 0) {
        return $this->isCompleted ? 100.0 : 0.0;
    }

    $completedSubtasks = $this->subtasks->filter(
        fn(Task $subtask) => $subtask->isCompleted()
    )->count();

    return ($completedSubtasks / $totalSubtasks) * 100;
}
```

```php
// ❌ ПЛОХО - Излишне сложно
public function isOverdue(): bool
{
    // НЕПРАВИЛЬНО! Слишком сложно для простой проверки
    $now = new \DateTimeImmutable();
    $dueDate = $this->getDueDate();

    if ($dueDate instanceof \DateTimeImmutable) {
        $isBeforeNow = $dueDate->getTimestamp() < $now->getTimestamp();
        $isNotCompleted = !$this->getIsCompleted();

        if ($isBeforeNow && $isNotCompleted) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}
```

### YAGNI (Вам Это Не Понадобится)

```php
// ✅ ХОРОШО - Реализуйте только то, что нужно СЕЙЧАС
final class TaskService
{
    public function createTask(CreateTaskDto $dto, User $user): Task
    {
        $task = new Task();
        $task->setTitle($dto->title);
        $task->setDescription($dto->description);
        $task->setUser($user);

        $this->repository->save($task);

        return $task;
    }
}
```

```php
// ❌ ПЛОХО - Избыточное проектирование для будущих нужд
final class TaskService
{
    // НЕПРАВИЛЬНО! Нам пока не нужны эти функции
    public function createTaskWithAI(CreateTaskDto $dto, User $user): Task { }
    public function createTaskFromEmail(string $email, User $user): Task { }
    public function createTaskFromVoice(string $audioFile, User $user): Task { }
    public function scheduleTaskCreation(CreateTaskDto $dto, \DateTime $when): void { }
    public function bulkCreateTasksParallel(array $dtos, User $user): array { }
}
```

---

## Распространенные Антипаттерны, Которых Следует Избегать

### Божественные Объекты

```php
// ❌ ПЛОХО - Божественный объект (делает всё)
class TaskManager
{
    // База данных
    public function save() { }
    public function delete() { }
    public function query() { }

    // Валидация
    public function validate() { }

    // Уведомления
    public function sendEmail() { }
    public function sendSms() { }

    // Логирование
    public function logActivity() { }

    // Аналитика
    public function trackEvent() { }

    // Экспорт
    public function exportToPDF() { }
    public function exportToExcel() { }

    // Слишком много ответственностей!
}
```

**Решение:** Разделить на сфокусированные классы (TaskService, TaskRepository, NotificationService, TaskExporter)

### Анемичная Доменная Модель

```php
// ❌ ПЛОХО - Анемичная (только геттеры/сеттеры, нет поведения)
class Task
{
    private string $title;
    private bool $isCompleted;

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }
    public function isCompleted(): bool { return $this->isCompleted; }
    public function setIsCompleted(bool $isCompleted): void { $this->isCompleted = $isCompleted; }
}

// НЕПРАВИЛЬНО! Бизнес-логика вне сущности
class TaskService
{
    public function markAsComplete(Task $task): void
    {
        $task->setIsCompleted(true);
        $task->setCompletedAt(new \DateTimeImmutable());
    }
}
```

```php
// ✅ ХОРОШО - Богатая доменная модель (поведение в сущности)
class Task
{
    private string $title;
    private bool $isCompleted;
    private ?\DateTimeImmutable $completedAt = null;

    public function complete(): void
    {
        // Бизнес-логика принадлежит здесь!
        $this->isCompleted = true;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function isOverdue(): bool
    {
        return $this->dueDate !== null
            && $this->dueDate < new \DateTimeImmutable()
            && !$this->isCompleted;
    }
}

class TaskService
{
    public function markAsComplete(Task $task): void
    {
        $task->complete();  // Чисто!
        $this->repository->save($task);
    }
}
```

### Магические Числа

```php
// ❌ ПЛОХО - Магические числа
public function getRetryDelay(): int
{
    if ($this->attemptCount === 1) {
        return 1000;  // Что такое 1000?
    } elseif ($this->attemptCount === 2) {
        return 5000;  // Что такое 5000?
    }
}
```

```php
// ✅ ХОРОШО - Именованные константы
final readonly class NotificationRetryService
{
    private const RETRY_DELAY_FIRST = 1000;      // 1 секунда
    private const RETRY_DELAY_SECOND = 5000;     // 5 секунд
    private const RETRY_DELAY_THIRD = 15000;     // 15 секунд
    private const MAX_RETRY_ATTEMPTS = 3;

    public function getRetryDelay(int $attemptCount): int
    {
        return match($attemptCount) {
            1 => self::RETRY_DELAY_FIRST,
            2 => self::RETRY_DELAY_SECOND,
            3 => self::RETRY_DELAY_THIRD,
            default => self::RETRY_DELAY_THIRD,
        };
    }
}
```

---

## Контрольный Список Резюме

Перед коммитом кода проверьте:

### PHP Backend
- [ ] Все классы используют типизацию (параметры и возвращаемые типы)
- [ ] Используется продвижение свойств в конструкторе
- [ ] Readonly свойства где возможно
- [ ] Перечисления вместо констант
- [ ] Match выражения вместо switch
- [ ] Именованные аргументы для ясности
- [ ] Каждый класс имеет единственную ответственность
- [ ] Зависимости внедрены через конструктор
- [ ] Контроллеры тонкие (только HTTP логика)
- [ ] Бизнес-логика в сервисах
- [ ] Запросы к базе данных в репозиториях

### TypeScript Frontend
- [ ] Строгий режим включен
- [ ] Нет 'any' типов
- [ ] Props типизированы интерфейсами
- [ ] Emits типизированы интерфейсами
- [ ] Реактивное состояние типизировано
- [ ] Защита типов для неизвестных данных
- [ ] Только Composition API
- [ ] Разделение умных/глупых компонентов

### Общее
- [ ] Нет дублирования кода (DRY)
- [ ] Простые решения (KISS)
- [ ] Нет преждевременной оптимизации (YAGNI)
- [ ] Осмысленные имена переменных
- [ ] Функции делают одну вещь
- [ ] Комментарии объясняют ПОЧЕМУ, а не ЧТО

---

**Последнее Обновление:** Ноябрь 5, 2025
