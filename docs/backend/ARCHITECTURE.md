# 🏗 Архитектура Backend - Слоистый дизайн и паттерны

> **TL;DR**: Чистая слоистая архитектура, следующая принципам SOLID. Паттерн Controller → Service → Repository со строгим разделением ответственности. Каждый класс имеет единственную ответственность, использует внедрение зависимостей и следует устоявшимся паттернам проектирования.

---

## Содержание

- [Обзор архитектуры](#обзор-архитектуры)
- [Слоистая архитектура](#слоистая-архитектура)
- [Применение принципов SOLID](#применение-принципов-solid)
- [Паттерны проектирования](#паттерны-проектирования)
- [Внедрение зависимостей](#внедрение-зависимостей)
- [Паттерн DTO](#паттерн-dto)
- [Поток запроса](#поток-запроса)
- [Примеры кода](#примеры-кода)
- [Лучшие практики](#лучшие-практики)

---

## Обзор архитектуры

### Архитектура высокого уровня

```
┌──────────────────────────────────────────────────────────────┐
│                      HTTP ЗАПРОС                             │
│                    (с Frontend)                              │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                 🛡 СЛОЙ БЕЗОПАСНОСТИ                         │
│  - JWT Аутентификация                                        │
│  - Авторизация (Voters)                                      │
│  - Обработка CORS                                            │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                 📥 СЛОЙ КОНТРОЛЛЕРОВ                         │
│  - Маршрутизация                                             │
│  - Валидация запроса (MapRequestPayload)                     │
│  - Форматирование ответа                                     │
│  - БЕЗ бизнес-логики                                         │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                 ⚙️ СЛОЙ СЕРВИСОВ                             │
│  - Бизнес-логика                                             │
│  - Управление транзакциями                                   │
│  - Трансформация данных                                      │
│  - Отправка событий                                          │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                 💾 СЛОЙ РЕПОЗИТОРИЕВ                         │
│  - Запросы к базе данных                                     │
│  - Логика доступа к данным                                   │
│  - Оптимизация запросов                                      │
│  - Гидратация сущностей                                      │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                 📊 СЛОЙ ДАННЫХ                               │
│  - PostgreSQL (сущности)                                     │
└──────────────────────────────────────────────────────────────┘
```

---

## Слоистая архитектура

### 1. Слой контроллеров

**Расположение:** `/backend/src/Controller/Api/`

**Ответственность:** ТОЛЬКО обработка HTTP

#### Что контроллеры ДЕЛАЮТ:
✅ Маршрутизируют пути к методам
✅ Валидируют данные запроса (через атрибуты)
✅ Вызывают слой сервисов
✅ Возвращают HTTP-ответы
✅ Обрабатывают HTTP-ошибки

#### Что контроллеры НЕ ДЕЛАЮТ:
❌ Бизнес-логика
❌ Запросы к базе данных
❌ Трансформация данных
❌ Сложные вычисления

#### Пример: TaskController

```php
<?php
// src/Controller/Api/TaskController.php

#[Route('/api/tasks', name: 'task_')]
#[IsGranted('ROLE_USER')]
class TaskController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,          // ✅ Сервис внедрен
        private readonly TranslationService $translationService
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        #[MapQueryString] ?TaskFilterDto $filters,          // ✅ Автоматическая валидация
        #[CurrentUser] User $user                           // ✅ Автоматическое внедрение
    ): JsonResponse {
        // ✅ ХОРОШО: Делегирование сервису
        $tasks = $this->taskService->getUserTasks($user, $filters);

        return $this->json($tasks);                         // ✅ Возврат ответа
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateTaskDto $dto,            // ✅ Валидированный DTO
        #[CurrentUser] User $user
    ): JsonResponse {
        $task = $this->taskService->createTask($dto, $user); // ✅ Сервис обрабатывает логику

        return $this->json($task, Response::HTTP_CREATED);
    }
}
```

#### ❌ ПЛОХОЙ пример контроллера (НЕ ДЕЛАЙТЕ ТАК):

```php
// ❌ ПЛОХО: Бизнес-логика в контроллере
public function create(Request $request): JsonResponse
{
    // ❌ Ручной парсинг запроса
    $data = json_decode($request->getContent(), true);

    // ❌ Прямой доступ к базе данных
    $task = new Task();
    $task->setTitle($data['title']);
    $task->setUser($this->getUser());

    // ❌ Бизнес-логика в контроллере
    if ($data['parentId']) {
        $parent = $this->em->find(Task::class, $data['parentId']);
        if ($parent->getUser() !== $this->getUser()) {
            throw new AccessDeniedException();
        }
        $task->setParent($parent);
    }

    // ❌ Управление кешем в контроллере
    $this->redis->del("user_tasks_{$this->getUser()->getId()}");

    $this->em->persist($task);
    $this->em->flush();

    return $this->json($task);
}
```

**Почему это ПЛОХО?**
- Контроллер знает о базе данных (EntityManager)
- Контроллер знает о кеше (Redis)
- Бизнес-логика (валидация родителя) в контроллере
- Сложно тестировать (связано с HTTP)
- Нет валидации DTO
- Ручной парсинг запроса (подвержен ошибкам)

---

### 2. Слой сервисов

**Расположение:** `/backend/src/Service/`

**Ответственность:** Бизнес-логика и оркестрация

#### Что сервисы ДЕЛАЮТ:
✅ Реализуют бизнес-правила
✅ Оркеструют множественные операции
✅ Управляют транзакциями
✅ Трансформируют данные (Entity ↔ DTO)
✅ Отправляют доменные события
✅ Валидируют бизнес-ограничения

#### Что сервисы НЕ ДЕЛАЮТ:
❌ Прямая обработка HTTP
❌ Знают о запросе/ответе
❌ Прямые запросы к базе данных (используют репозитории)
❌ Знают о слое представления

#### Пример: TaskService

```php
<?php
// src/Service/TaskService.php

final class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TagRepository $tagRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Создать новую задачу с применением всех бизнес-правил
     */
    public function createTask(CreateTaskDto $dto, User $user): Task
    {
        // ✅ Бизнес-логика: Создание сущности
        $task = new Task();
        $task->setTitle($dto->title)
            ->setDescription($dto->description)
            ->setStatus($dto->status)
            ->setPriority($dto->priority)
            ->setStartDate($dto->startDate ? new \DateTimeImmutable($dto->startDate) : null)
            ->setDueDate($dto->dueDate ? new \DateTimeImmutable($dto->dueDate) : null)
            ->setUser($user);

        // ✅ Бизнес-правило: Обработка связи с родительской задачей
        if ($dto->parentTaskId !== null) {
            $parentTask = $this->taskRepository->find($dto->parentTaskId);

            // ✅ Бизнес-валидация: Пользователь владеет родительской задачей
            if ($parentTask && $parentTask->getUser() === $user) {
                $task->setParentTask($parentTask);
            }
        }

        // ✅ Бизнес-логика: Обработка тегов (найти или создать)
        if (!empty($dto->tags)) {
            $tags = $this->tagRepository->findOrCreateByNames($dto->tags, $user);
            foreach ($tags as $tag) {
                $task->addTag($tag);
            }
        }

        // ✅ Транзакция: Сохранение
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // ✅ Отправка события: Уведомление слушателей
        $this->eventDispatcher->dispatch(new TaskCreatedEvent($task));

        // ✅ Логирование: Отслеживание создания задачи
        $this->logger->info('Task created', ['taskId' => $task->getId(), 'userId' => $user->getId()]);

        return $task;
    }

    /**
     * Обновить существующую задачу
     */
    public function updateTask(int $id, UpdateTaskDto $dto, User $user): Task
    {
        $task = $this->taskRepository->find($id);

        // ✅ Бизнес-валидация: Задача существует
        if (!$task) {
            throw new TaskNotFoundException();
        }

        // ✅ Бизнес-валидация: Пользователь владеет задачей
        if ($task->getUser() !== $user) {
            throw new TaskAccessDeniedException();
        }

        // ✅ Бизнес-логика: Обновление полей
        if ($dto->title !== null) {
            $task->setTitle($dto->title);
        }

        if ($dto->status !== null) {
            $task->setStatus($dto->status);
        }

        // ✅ Транзакция: Фиксация изменений
        $this->entityManager->flush();

        // ✅ Отправка события: Уведомление слушателей
        $this->eventDispatcher->dispatch(new TaskUpdatedEvent($task));

        return $task;
    }
}
```

---

### 3. Слой репозиториев

**Расположение:** `/backend/src/Repository/Database/`

**Ответственность:** ТОЛЬКО доступ к данным

#### Что репозитории ДЕЛАЮТ:
✅ Выполняют запросы к базе данных
✅ Строят сложные QueryBuilders
✅ Оптимизируют запросы (joins, индексы)
✅ Гидратируют сущности из базы данных
✅ Возвращают сущности или массивы

#### Что репозитории НЕ ДЕЛАЮТ:
❌ Бизнес-логика
❌ Валидация данных
❌ Управление транзакциями
❌ Знают о слое HTTP

#### Пример: TaskRepository

```php
<?php
// src/Repository/Database/TaskRepository.php

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry
    ) {
        parent::__construct($registry, Task::class);
    }

    /**
     * Найти все задачи пользователя
     *
     * ✅ ХОРОШО: Репозиторий обрабатывает только доступ к данным
     * ✅ ХОРОШО: Использует QueryBuilder для сложных запросов
     */
    public function findUserTasks(
        User $user,
        ?TaskStatus $status = null,
        ?bool $includeArchived = false,
        ?bool $onlyParentTasks = true
    ): array {
        // Построение запроса с критериями
        $qb = $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user);

        if ($onlyParentTasks) {
            $qb->andWhere('t.parentTask IS NULL');
        }

        if ($status !== null) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

        if (!$includeArchived) {
            $qb->andWhere('t.isArchived = :archived')
                ->setParameter('archived', false);
        }

        $qb->orderBy('t.sortOrder', 'ASC')
            ->addOrderBy('t.priority', 'DESC')
            ->addOrderBy('t.dueDate', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Найти задачи с конкретными фильтрами (сложный запрос)
     */
    public function findByFilters(User $user, TaskFilterDto $filters): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.tags', 'tag')
            ->where('t.user = :user')
            ->setParameter('user', $user);

        // ✅ Динамическое построение запроса
        if ($filters->status !== null) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $filters->status);
        }

        if ($filters->priority !== null) {
            $qb->andWhere('t.priority = :priority')
                ->setParameter('priority', $filters->priority);
        }

        if (!empty($filters->tags)) {
            $qb->andWhere('tag.id IN (:tags)')
                ->setParameter('tags', $filters->tags);
        }

        if ($filters->search !== null) {
            $qb->andWhere('t.title LIKE :search OR t.description LIKE :search')
                ->setParameter('search', '%' . $filters->search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Найти задачи с рекурсивными подзадачами (PostgreSQL CTE)
     */
    public function findWithAllSubtasks(int $taskId): array
    {
        // ✅ Продвинутый SQL: Рекурсивное общее табличное выражение
        $sql = "
            WITH RECURSIVE subtasks AS (
                SELECT * FROM task WHERE id = :taskId
                UNION ALL
                SELECT t.*
                FROM task t
                INNER JOIN subtasks s ON t.parent_task_id = s.id
            )
            SELECT * FROM subtasks
        ";

        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['taskId' => $taskId]);

        return $result->fetchAllAssociative();
    }
}
```

---

## Применение принципов SOLID

### S - Принцип единственной ответственности

**"Класс должен иметь одну, и только одну, причину для изменения."**

#### ✅ ХОРОШИЕ примеры:

```php
// ✅ TaskController: ТОЛЬКО обрабатывает HTTP
class TaskController extends AbstractController
{
    // Единственная ответственность: Маршрутизация HTTP → Service
}

// ✅ TaskService: ТОЛЬКО обрабатывает бизнес-логику
class TaskService
{
    // Единственная ответственность: Бизнес-правила
}

// ✅ TaskRepository: ТОЛЬКО обрабатывает доступ к данным
class TaskRepository extends ServiceEntityRepository
{
    // Единственная ответственность: Запросы к базе данных
}

// ✅ TranslationService: ТОЛЬКО обрабатывает переводы
class TranslationService
{
    // Единственная ответственность: i18n переводы
}
```

#### ❌ ПЛОХОЙ пример:

```php
// ❌ Божественный класс с множественными ответственностями
class TaskManager
{
    // ❌ Обработка HTTP
    public function handleRequest(Request $request) { }

    // ❌ Бизнес-логика
    public function createTask(array $data) { }

    // ❌ Доступ к базе данных
    public function saveToDatabase(Task $task) { }

    // ❌ Управление кешем
    public function updateCache(Task $task) { }

    // ❌ Отправка email
    public function sendNotification(Task $task) { }
}
```

---

### O - Принцип открытости/закрытости

**"Открыт для расширения, закрыт для модификации."**

#### ✅ ХОРОШО: Паттерн Стратегия

```php
// ✅ Интерфейс определяет контракт
interface RecurrenceStrategyInterface
{
    public function generateNextOccurrence(\DateTimeInterface $from): ?\DateTimeInterface;
}

// ✅ Каждая стратегия — отдельный класс
class DailyRecurrenceStrategy implements RecurrenceStrategyInterface
{
    public function generateNextOccurrence(\DateTimeInterface $from): ?\DateTimeInterface
    {
        return $from->modify('+1 day');
    }
}

class WeeklyRecurrenceStrategy implements RecurrenceStrategyInterface
{
    public function generateNextOccurrence(\DateTimeInterface $from): ?\DateTimeInterface
    {
        return $from->modify('+1 week');
    }
}

// ✅ Сервис использует стратегию (закрыт для модификации)
class RecurrenceService
{
    public function __construct(
        private readonly RecurrenceStrategyInterface $strategy
    ) {}

    public function getNext(\DateTimeInterface $from): ?\DateTimeInterface
    {
        return $this->strategy->generateNextOccurrence($from);
    }
}

// ✅ Добавить новую стратегию БЕЗ модификации существующего кода
class CustomRecurrenceStrategy implements RecurrenceStrategyInterface
{
    public function generateNextOccurrence(\DateTimeInterface $from): ?\DateTimeInterface
    {
        // Пользовательская логика
    }
}
```

---

### L - Принцип подстановки Барбары Лисков

**"Производные классы должны быть заменяемыми на свои базовые классы."**

```php
// ✅ Базовый репозиторий
abstract class AbstractRepository
{
    abstract public function find(int $id): ?object;
    abstract public function findAll(): array;
}

// ✅ TaskRepository может заменить AbstractRepository
class TaskRepository extends AbstractRepository
{
    public function find(int $id): ?object
    {
        return $this->_em->find(Task::class, $id); // ✅ Возвращает Task (является object)
    }

    public function findAll(): array
    {
        return $this->_em->getRepository(Task::class)->findAll(); // ✅ Возвращает array
    }
}

// ✅ Любой код, ожидающий AbstractRepository, работает с TaskRepository
function processRepository(AbstractRepository $repo): void
{
    $entity = $repo->find(1);    // ✅ Работает с любым репозиторием
    $all = $repo->findAll();      // ✅ Работает с любым репозиторием
}
```

---

### I - Принцип разделения интерфейса

**"Не заставляйте клиентов зависеть от интерфейсов, которые они не используют."**

```php
// ✅ ХОРОШО: Маленькие, сфокусированные интерфейсы
interface NotificationServiceInterface
{
    public function send(User $user, string $message): void;
    public function sendEmail(User $user, string $subject, string $body): void;
}

interface LoggerInterface
{
    public function info(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
}

// ❌ ПЛОХО: Толстый интерфейс
interface TaskManagerInterface
{
    public function create(array $data): Task;
    public function update(int $id, array $data): Task;
    public function delete(int $id): void;
    public function sendNotification(Task $task): void;
    public function log(string $message): void;
    public function cache(Task $task): void;
    public function validate(array $data): bool;
    public function transform(Task $task): array;
    // ... слишком много методов!
}
```

---

### D - Принцип инверсии зависимостей

**"Зависьте от абстракций, а не от конкретных реализаций."**

```php
// ✅ ХОРОШО: Зависимость от интерфейса
class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,    // ✅ Интерфейс
        private readonly EventDispatcherInterface $eventDispatcher   // ✅ Интерфейс
    ) {}
}

// ❌ ПЛОХО: Зависимость от конкретного класса
class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,             // ❌ Конкретный класс
        private readonly EventDispatcher $eventDispatcher            // ❌ Конкретный класс
    ) {}
}
```

---

## Паттерны проектирования

### 1. Паттерн Репозиторий

**Назначение:** Слой абстракции между бизнес-логикой и доступом к данным.

```php
// Интерфейс репозитория (контракт)
interface TaskRepositoryInterface
{
    public function find(int $id): ?Task;
    public function findUserTasks(User $user): array;
    public function save(Task $task): void;
    public function delete(Task $task): void;
}

// Конкретная реализация
class TaskRepository implements TaskRepositoryInterface
{
    // Реализация, специфичная для базы данных
}

// ✅ Сервис зависит от интерфейса, а не от реализации
class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository
    ) {}
}
```

**Преимущества:**
- Легко переключить базу данных (PostgreSQL → MySQL)
- Легко мокировать в тестах
- Централизованная логика запросов

---

### 2. Паттерн DTO (Объект передачи данных)

**Назначение:** Передача данных между слоями без раскрытия сущностей.

```php
// Request DTO (от клиента)
final readonly class CreateTaskDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $title,

        #[Assert\Length(max: 5000)]
        public ?string $description = null,

        public TaskStatus $status = TaskStatus::PENDING,
        public TaskPriority $priority = TaskPriority::MEDIUM,
        public ?string $startDate = null,
        public ?string $dueDate = null,
        public array $tags = [],
        public ?int $parentTaskId = null
    ) {}
}

// Response DTO (к клиенту)
final readonly class TaskResponseDto
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public TaskStatus $status,
        public TaskPriority $priority,
        public ?string $startDate,
        public ?string $dueDate,
        public array $tags,
        public array $subtasks,
        public bool $isOverdue,
        public ?string $statusLabel,      // Переведено
        public ?string $priorityLabel     // Переведено
    ) {}

    // ✅ Фабричный метод: Entity → DTO
    public static function fromEntity(Task $task): self
    {
        return new self(
            id: $task->getId(),
            title: $task->getTitle(),
            description: $task->getDescription(),
            status: $task->getStatus(),
            priority: $task->getPriority(),
            startDate: $task->getStartDate()?->format('Y-m-d'),
            dueDate: $task->getDueDate()?->format('Y-m-d'),
            tags: array_map(fn($tag) => TagResponseDto::fromEntity($tag), $task->getTags()->toArray()),
            subtasks: array_map(fn($sub) => self::fromEntity($sub), $task->getSubtasks()->toArray()),
            isOverdue: $task->isOverdue(),
            statusLabel: null,   // Устанавливается контроллером
            priorityLabel: null  // Устанавливается контроллером
        );
    }
}
```

**Преимущества:**
- Валидация на уровне DTO (через атрибуты)
- Никогда не раскрывайте сущности слою HTTP
- Типобезопасные запрос/ответ
- Легко версионировать (CreateTaskDtoV2)

---

### 3. Внедрение зависимостей (DI)

**Встроенный DI-контейнер Symfony автоматически связывает зависимости.**

```php
// ✅ Инъекция через конструктор (рекомендуется)
class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TagRepository $tagRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    // Все зависимости автоматически внедряются Symfony
}

// ✅ Регистрация сервисов в services.yaml
services:
    _defaults:
        autowire: true      # Автоматическое внедрение зависимостей
        autoconfigure: true # Автоматическая настройка сервисов

    App\:
        resource: '../src/'
        exclude:
            - '../src/Entity/'
            - '../src/Kernel.php'
```

**Преимущества:**
- Легкое тестирование (внедряйте моки)
- Слабая связанность
- Нет ручного создания экземпляров

---

### 4. Паттерн Фабрика

**Назначение:** Создание сложных объектов.

```php
// ✅ Статический фабричный метод
final readonly class TaskResponseDto
{
    public static function fromEntity(Task $task): self
    {
        return new self(
            id: $task->getId(),
            title: $task->getTitle(),
            // ... маппинг всех полей
        );
    }
}

// Использование в сервисе
$dto = TaskResponseDto::fromEntity($task);
```

---

### 5. Паттерн Подписчик событий

**Назначение:** Разделение побочных эффектов от бизнес-логики.

```php
// Подписчик событий для автоматических уведомлений и логирования
class TaskEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'task.created' => 'onTaskCreated',
            'task.updated' => 'onTaskUpdated',
            'task.deleted' => 'onTaskDeleted',
        ];
    }

    public function onTaskCreated(TaskCreatedEvent $event): void
    {
        $task = $event->getTask();
        $user = $task->getUser();

        // ✅ Автоматически отправить уведомление
        $this->notificationService->send($user, 'Task created: ' . $task->getTitle());

        // ✅ Залогировать событие
        $this->logger->info('Task created', ['taskId' => $task->getId()]);
    }
}
```

---

## Внедрение зависимостей

### Как работает DI в Symfony

```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    # Контроллеры регистрируются автоматически
    App\Controller\:
        resource: '../src/Controller/'
        tags: ['controller.service_arguments']

    # Сервисы регистрируются автоматически
    App\Service\:
        resource: '../src/Service/'

    # Репозитории регистрируются автоматически
    App\Repository\:
        resource: '../src/Repository/'
```

### Инъекция через конструктор (рекомендуется)

```php
class TaskController extends AbstractController
{
    // ✅ Зависимости внедряются через конструктор
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TranslationService $translationService,
        private readonly LoggerInterface $logger
    ) {}

    public function list(): JsonResponse
    {
        // Использование внедренных сервисов
        $tasks = $this->taskService->getUserTasks(...);
        $this->logger->info('Task list retrieved');
        return $this->json($tasks);
    }
}
```

---

## Паттерн DTO

### Request DTOs (валидация входных данных)

```php
final readonly class CreateTaskDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Title is required')]
        #[Assert\Length(
            min: 1,
            max: 255,
            minMessage: 'Title must be at least {{ limit }} characters',
            maxMessage: 'Title cannot be longer than {{ limit }} characters'
        )]
        public string $title,

        #[Assert\Length(max: 5000)]
        public ?string $description = null,

        public TaskStatus $status = TaskStatus::PENDING,
        public TaskPriority $priority = TaskPriority::MEDIUM
    ) {}
}
```

### Response DTOs (форматирование выходных данных)

```php
final readonly class TaskResponseDto implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public string $title,
        public TaskStatus $status,
        public TaskPriority $priority,
        public array $subtasks = []
    ) {}

    // ✅ Контроль JSON-сериализации
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status->value,      // Enum → string
            'priority' => $this->priority->value,
            'subtasks' => $this->subtasks
        ];
    }
}
```

---

## Поток запроса

### Полный цикл запрос/ответ

```
1. Поступает HTTP-запрос
   ↓
2. Маршрутизатор Symfony находит маршрут → TaskController::create()
   ↓
3. Компонент безопасности проверяет JWT-токен
   ↓
4. Voter проверяет авторизацию (TaskVoter)
   ↓
5. MapRequestPayload валидирует CreateTaskDto
   ↓
6. Контроллер вызывает TaskService::createTask()
   ↓
7. Сервис валидирует бизнес-правила
   ↓
8. Сервис вызывает TaskRepository::save()
   ↓
9. Репозиторий сохраняет в PostgreSQL
   ↓
10. Сервис отправляет TaskCreatedEvent
   ↓
11. Подписчики событий обрабатывают побочные эффекты (уведомления, логирование)
   ↓
12. Сервис возвращает сущность Task
   ↓
13. Контроллер трансформирует Task → TaskResponseDto
   ↓
14. Контроллер возвращает JsonResponse
   ↓
15. Symfony сериализует DTO → JSON
   ↓
16. HTTP-ответ отправляется клиенту
```

---

## Примеры кода

### Полный пример CRUD

```php
// Контроллер: HTTP-слой
class TaskController extends AbstractController
{
    #[Route('/api/tasks', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateTaskDto $dto,
        #[CurrentUser] User $user
    ): JsonResponse {
        $task = $this->taskService->createTask($dto, $user);
        $responseDto = TaskResponseDto::fromEntity($task);
        return $this->json($responseDto, Response::HTTP_CREATED);
    }
}

// Сервис: Слой бизнес-логики
class TaskService
{
    public function createTask(CreateTaskDto $dto, User $user): Task
    {
        // Бизнес-логика
        $task = new Task();
        $task->setTitle($dto->title);
        $task->setUser($user);

        // Сохранение
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // Отправка события
        $this->eventDispatcher->dispatch(new TaskCreatedEvent($task));

        return $task;
    }
}

// Репозиторий: Слой доступа к данным
class TaskRepository extends ServiceEntityRepository
{
    public function save(Task $task): void
    {
        $this->_em->persist($task);
        $this->_em->flush();
    }
}
```

---

## Лучшие практики

### ДЕЛАЙТЕ ✅

✅ **Держите контроллеры тонкими** - Только обработка HTTP
✅ **Используйте DTO** - Никогда не раскрывайте сущности HTTP
✅ **Внедряйте зависимости** - Инъекция через конструктор
✅ **Используйте типизацию** - Строгие типы везде
✅ **Следуйте соглашениям именования** - TaskService, TaskRepository
✅ **Единственная ответственность** - Один класс, одна цель
✅ **Используйте readonly-свойства** - Функция PHP 8.3
✅ **Используйте enums** - TaskStatus, TaskPriority
✅ **Оптимизируйте запросы** - Используйте индексы, joins, eager loading
✅ **События для побочных эффектов** - Уведомления, логирование через события

### НЕ ДЕЛАЙТЕ ❌

❌ **Бизнес-логика в контроллерах**
❌ **Прямой доступ к базе данных в контроллерах**
❌ **Раскрытие сущностей слою HTTP**
❌ **Ручной парсинг запросов**
❌ **Божественные классы** (один класс делает всё)
❌ **Жёсткая связанность** (зависьте от интерфейсов)
❌ **Глобальное состояние** (используйте внедрение зависимостей)
❌ **Магические числа** (используйте константы/enums)
❌ **Подавление ошибок** (позволяйте исключениям всплывать)
❌ **Прямой доступ к инфраструктуре в контроллерах** (Redis, очереди сообщений)

---

## Связанные документы

### Обязательно прочитать далее
- **[Database](DATABASE.md)** - Связи сущностей
- **[Authentication](AUTHENTICATION.md)** - JWT и OAuth2

### Для справки
- **[API Reference](API_REFERENCE.md)** - Все эндпоинты
- **[Coding Standards](../CODING_STANDARDS.md)** - Качество кода

---

*Последнее обновление: 2025-01-05*
*Версия архитектуры: 1.0*
