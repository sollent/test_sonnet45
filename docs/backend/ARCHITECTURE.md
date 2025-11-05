# 🏗 Backend Architecture - Layered Design & Patterns

> **TL;DR**: Clean layered architecture following SOLID principles. Controller → Service → Repository pattern with strict separation of concerns. Every class has a single responsibility, uses dependency injection, and follows established design patterns.

---

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Layered Architecture](#layered-architecture)
- [SOLID Principles Applied](#solid-principles-applied)
- [Design Patterns](#design-patterns)
- [Dependency Injection](#dependency-injection)
- [DTO Pattern](#dto-pattern)
- [Request Flow](#request-flow)
- [Code Examples](#code-examples)
- [Best Practices](#best-practices)

---

## Architecture Overview

### High-Level Architecture

```
┌──────────────────────────────────────────────────────────────┐
│                      HTTP REQUEST                            │
│                    (from Frontend)                           │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                 🛡 SECURITY LAYER                            │
│  - JWT Authentication                                        │
│  - Authorization (Voters)                                    │
│  - CORS Handling                                             │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                 📥 CONTROLLER LAYER                          │
│  - Route mapping                                             │
│  - Request validation (MapRequestPayload)                    │
│  - Response formatting                                       │
│  - NO business logic                                         │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                 ⚙️ SERVICE LAYER                             │
│  - Business logic                                            │
│  - Transaction management                                    │
│  - Data transformation                                       │
│  - Cache coordination                                        │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                 💾 REPOSITORY LAYER                          │
│  - Database queries                                          │
│  - Data access logic                                         │
│  - Query optimization                                        │
│  - Cache integration                                         │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                 📊 DATA LAYER                                │
│  - PostgreSQL (entities)                                     │
│  - Redis (cache)                                             │
└──────────────────────────────────────────────────────────────┘
```

---

## Layered Architecture

### 1. Controller Layer

**Location:** `/backend/src/Controller/Api/`

**Responsibility:** HTTP handling ONLY

#### What Controllers DO:
✅ Map routes to methods
✅ Validate request data (via attributes)
✅ Call service layer
✅ Return HTTP responses
✅ Handle HTTP errors

#### What Controllers DON'T DO:
❌ Business logic
❌ Database queries
❌ Cache management
❌ Data transformation
❌ Complex calculations

#### Example: TaskController

```php
<?php
// src/Controller/Api/TaskController.php

#[Route('/api/tasks', name: 'task_')]
#[IsGranted('ROLE_USER')]
class TaskController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,          // ✅ Service injected
        private readonly TranslationService $translationService
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        #[MapQueryString] ?TaskFilterDto $filters,          // ✅ Auto-validated
        #[CurrentUser] User $user                           // ✅ Auto-injected
    ): JsonResponse {
        // ✅ GOOD: Delegate to service
        $tasks = $this->taskService->getUserTasks($user, $filters);

        return $this->json($tasks);                         // ✅ Return response
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateTaskDto $dto,            // ✅ Validated DTO
        #[CurrentUser] User $user
    ): JsonResponse {
        $task = $this->taskService->createTask($dto, $user); // ✅ Service handles logic

        return $this->json($task, Response::HTTP_CREATED);
    }
}
```

#### ❌ BAD Controller Example (DON'T DO THIS):

```php
// ❌ BAD: Business logic in controller
public function create(Request $request): JsonResponse
{
    // ❌ Manual request parsing
    $data = json_decode($request->getContent(), true);

    // ❌ Direct database access
    $task = new Task();
    $task->setTitle($data['title']);
    $task->setUser($this->getUser());

    // ❌ Business logic in controller
    if ($data['parentId']) {
        $parent = $this->em->find(Task::class, $data['parentId']);
        if ($parent->getUser() !== $this->getUser()) {
            throw new AccessDeniedException();
        }
        $task->setParent($parent);
    }

    // ❌ Cache management in controller
    $this->redis->del("user_tasks_{$this->getUser()->getId()}");

    $this->em->persist($task);
    $this->em->flush();

    return $this->json($task);
}
```

**Why is this BAD?**
- Controller knows about database (EntityManager)
- Controller knows about cache (Redis)
- Business logic (parent validation) in controller
- Hard to test (coupled to HTTP)
- No DTO validation
- Manual request parsing (error-prone)

---

### 2. Service Layer

**Location:** `/backend/src/Service/`

**Responsibility:** Business logic & orchestration

#### What Services DO:
✅ Implement business rules
✅ Orchestrate multiple operations
✅ Manage transactions
✅ Transform data (Entity ↔ DTO)
✅ Coordinate caching
✅ Validate business constraints

#### What Services DON'T DO:
❌ Direct HTTP handling
❌ Know about request/response
❌ Direct database queries (use repositories)
❌ Know about presentation layer

#### Example: TaskService

```php
<?php
// src/Service/TaskService.php

final class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TagRepository $tagRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TaskCacheService $taskCache,
        private readonly AnalyticsCacheService $analyticsCache
    ) {}

    /**
     * Create a new task with all business rules applied
     */
    public function createTask(CreateTaskDto $dto, User $user): Task
    {
        // ✅ Business logic: Create entity
        $task = new Task();
        $task->setTitle($dto->title)
            ->setDescription($dto->description)
            ->setStatus($dto->status)
            ->setPriority($dto->priority)
            ->setStartDate($dto->startDate ? new \DateTimeImmutable($dto->startDate) : null)
            ->setDueDate($dto->dueDate ? new \DateTimeImmutable($dto->dueDate) : null)
            ->setUser($user);

        // ✅ Business rule: Handle parent task relationship
        if ($dto->parentTaskId !== null) {
            $parentTask = $this->taskRepository->find($dto->parentTaskId);

            // ✅ Business validation: User owns parent
            if ($parentTask && $parentTask->getUser() === $user) {
                $task->setParentTask($parentTask);
            }
        }

        // ✅ Business logic: Handle tags (find or create)
        if (!empty($dto->tags)) {
            $tags = $this->tagRepository->findOrCreateByNames($dto->tags, $user);
            foreach ($tags as $tag) {
                $task->addTag($tag);
            }
        }

        // ✅ Transaction: Persist
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // ✅ Cache coordination: Update cache (UPDATE strategy)
        $this->taskCache->updateAfterCreate($user, $task);

        // ✅ Cache coordination: Invalidate analytics
        $this->analyticsCache->invalidateUserAnalytics($user);

        return $task;
    }

    /**
     * Update existing task
     */
    public function updateTask(int $id, UpdateTaskDto $dto, User $user): Task
    {
        $task = $this->taskRepository->find($id);

        // ✅ Business validation: Task exists
        if (!$task) {
            throw new TaskNotFoundException();
        }

        // ✅ Business validation: User owns task
        if ($task->getUser() !== $user) {
            throw new TaskAccessDeniedException();
        }

        // ✅ Business logic: Update fields
        if ($dto->title !== null) {
            $task->setTitle($dto->title);
        }

        if ($dto->status !== null) {
            $task->setStatus($dto->status);
        }

        // ✅ Transaction: Flush changes
        $this->entityManager->flush();

        // ✅ Cache coordination
        $this->taskCache->updateAfterModify($user, $task);
        $this->analyticsCache->invalidateUserAnalytics($user);

        return $task;
    }
}
```

---

### 3. Repository Layer

**Location:** `/backend/src/Repository/Database/`

**Responsibility:** Data access ONLY

#### What Repositories DO:
✅ Execute database queries
✅ Build complex QueryBuilders
✅ Optimize queries (joins, indexes)
✅ Cache query results
✅ Return entities or arrays

#### What Repositories DON'T DO:
❌ Business logic
❌ Data validation
❌ Transaction management
❌ Know about HTTP layer

#### Example: TaskRepository

```php
<?php
// src/Repository/Database/TaskRepository.php

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly TaskCacheService $taskCache
    ) {
        parent::__construct($registry, Task::class);
    }

    /**
     * Find all tasks for a user with cache
     *
     * ✅ GOOD: Repository knows about caching
     * ✅ GOOD: Uses callback pattern for cache miss
     */
    public function findUserTasks(
        User $user,
        ?TaskStatus $status = null,
        ?bool $includeArchived = false,
        ?bool $onlyParentTasks = true
    ): array {
        // Build cache key filters
        $filters = [
            'status' => $status?->value,
            'includeArchived' => $includeArchived,
            'onlyParentTasks' => $onlyParentTasks,
        ];

        // ✅ Cache-through pattern
        return $this->taskCache->getTaskList($user, $filters, function () use ($user, $status, $includeArchived, $onlyParentTasks) {
            // Query executed ONLY on cache miss
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
        });
    }

    /**
     * Find tasks with specific filters (complex query)
     */
    public function findByFilters(User $user, TaskFilterDto $filters): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.tags', 'tag')
            ->where('t.user = :user')
            ->setParameter('user', $user);

        // ✅ Dynamic query building
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
     * Find tasks with recursive subtasks (PostgreSQL CTE)
     */
    public function findWithAllSubtasks(int $taskId): array
    {
        // ✅ Advanced SQL: Recursive Common Table Expression
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

### 4. Cache Layer

**Location:** `/backend/src/Service/Cache/`

**Responsibility:** Caching strategy & implementation

#### Cache Services:

```
Cache Layer Architecture:
├── SimpleRedisCache.php         → Low-level Redis operations
├── RedisKeyManager.php          → Key generation & management
├── TaskCacheService.php         → Task-specific caching (UPDATE strategy)
└── AnalyticsCacheService.php    → Analytics caching (INVALIDATE strategy)
```

#### Example: TaskCacheService

```php
<?php
// src/Service/Cache/TaskCacheService.php

final class TaskCacheService
{
    public function __construct(
        private readonly SimpleRedisCache $redis,
        private readonly RedisKeyManager $keyManager
    ) {}

    /**
     * Get task list with cache-through pattern
     */
    public function getTaskList(User $user, array $filters, callable $fetchCallback): array
    {
        $key = $this->keyManager->generateTaskListKey($user, $filters);

        // Try cache first
        $cached = $this->redis->get($key);
        if ($cached !== null) {
            return $cached; // ✅ Cache hit (0.5ms)
        }

        // Cache miss: execute callback
        $data = $fetchCallback(); // ✅ Query database (~100ms)

        // Store in cache
        $this->redis->set($key, $data, 900); // 15 min TTL

        return $data;
    }

    /**
     * UPDATE strategy: Modify cache after create
     */
    public function updateAfterCreate(User $user, Task $task): void
    {
        // Get current cached list
        $key = $this->keyManager->generateTaskListKey($user);
        $tasks = $this->redis->get($key);

        if ($tasks !== null) {
            // ✅ Add new task to cached list
            array_unshift($tasks, $this->transformToDto($task));
            $this->redis->set($key, $tasks, 900);
        }
        // If cache miss, do nothing (will be fetched on next request)
    }

    /**
     * UPDATE strategy: Modify cache after update
     */
    public function updateAfterModify(User $user, Task $task): void
    {
        $key = $this->keyManager->generateTaskListKey($user);
        $tasks = $this->redis->get($key);

        if ($tasks !== null) {
            // ✅ Find and update task in cached list
            foreach ($tasks as &$cachedTask) {
                if ($cachedTask['id'] === $task->getId()) {
                    $cachedTask = $this->transformToDto($task);
                    break;
                }
            }
            $this->redis->set($key, $tasks, 900);
        }
    }
}
```

---

## SOLID Principles Applied

### S - Single Responsibility Principle

**"A class should have one, and only one, reason to change."**

#### ✅ GOOD Examples:

```php
// ✅ TaskController: ONLY handles HTTP
class TaskController extends AbstractController
{
    // Single responsibility: Map HTTP → Service
}

// ✅ TaskService: ONLY handles business logic
class TaskService
{
    // Single responsibility: Business rules
}

// ✅ TaskRepository: ONLY handles data access
class TaskRepository extends ServiceEntityRepository
{
    // Single responsibility: Database queries
}

// ✅ TaskCacheService: ONLY handles caching
class TaskCacheService
{
    // Single responsibility: Cache management
}
```

#### ❌ BAD Example:

```php
// ❌ God class with multiple responsibilities
class TaskManager
{
    // ❌ HTTP handling
    public function handleRequest(Request $request) { }

    // ❌ Business logic
    public function createTask(array $data) { }

    // ❌ Database access
    public function saveToDatabase(Task $task) { }

    // ❌ Cache management
    public function updateCache(Task $task) { }

    // ❌ Email sending
    public function sendNotification(Task $task) { }
}
```

---

### O - Open/Closed Principle

**"Open for extension, closed for modification."**

#### ✅ GOOD: Strategy Pattern

```php
// ✅ Interface defines contract
interface RecurrenceStrategyInterface
{
    public function generateNextOccurrence(\DateTimeInterface $from): ?\DateTimeInterface;
}

// ✅ Each strategy is separate class
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

// ✅ Service uses strategy (closed for modification)
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

// ✅ Add new strategy WITHOUT modifying existing code
class CustomRecurrenceStrategy implements RecurrenceStrategyInterface
{
    public function generateNextOccurrence(\DateTimeInterface $from): ?\DateTimeInterface
    {
        // Custom logic
    }
}
```

---

### L - Liskov Substitution Principle

**"Derived classes must be substitutable for their base classes."**

```php
// ✅ Base repository
abstract class AbstractRepository
{
    abstract public function find(int $id): ?object;
    abstract public function findAll(): array;
}

// ✅ TaskRepository can replace AbstractRepository
class TaskRepository extends AbstractRepository
{
    public function find(int $id): ?object
    {
        return $this->_em->find(Task::class, $id); // ✅ Returns Task (is object)
    }

    public function findAll(): array
    {
        return $this->_em->getRepository(Task::class)->findAll(); // ✅ Returns array
    }
}

// ✅ Any code expecting AbstractRepository works with TaskRepository
function processRepository(AbstractRepository $repo): void
{
    $entity = $repo->find(1);    // ✅ Works with any repository
    $all = $repo->findAll();      // ✅ Works with any repository
}
```

---

### I - Interface Segregation Principle

**"Don't force clients to depend on interfaces they don't use."**

```php
// ✅ GOOD: Small, focused interfaces
interface CacheServiceInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl): bool;
    public function delete(string $key): bool;
}

interface CacheKeyManagerInterface
{
    public function generateTaskListKey(User $user, array $filters = []): string;
    public function generateAnalyticsKey(User $user, string $type): string;
}

// ❌ BAD: Fat interface
interface CacheInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl): bool;
    public function delete(string $key): bool;
    public function generateTaskListKey(User $user): string;
    public function generateAnalyticsKey(User $user): string;
    public function invalidateUser(User $user): void;
    public function warmup(): void;
    public function getStats(): array;
    // ... too many methods!
}
```

---

### D - Dependency Inversion Principle

**"Depend on abstractions, not concretions."**

```php
// ✅ GOOD: Depend on interface
class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,    // ✅ Interface
        private readonly CacheServiceInterface $cacheService         // ✅ Interface
    ) {}
}

// ❌ BAD: Depend on concrete class
class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,             // ❌ Concrete class
        private readonly SimpleRedisCache $redis                     // ❌ Concrete class
    ) {}
}
```

---

## Design Patterns

### 1. Repository Pattern

**Purpose:** Abstraction layer between business logic and data access.

```php
// Repository interface (contract)
interface TaskRepositoryInterface
{
    public function find(int $id): ?Task;
    public function findUserTasks(User $user): array;
    public function save(Task $task): void;
    public function delete(Task $task): void;
}

// Concrete implementation
class TaskRepository implements TaskRepositoryInterface
{
    // Database-specific implementation
}

// ✅ Service depends on interface, not implementation
class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository
    ) {}
}
```

**Benefits:**
- Easy to switch databases (PostgreSQL → MySQL)
- Easy to mock in tests
- Centralized query logic

---

### 2. DTO (Data Transfer Object) Pattern

**Purpose:** Transfer data between layers without exposing entities.

```php
// Request DTO (from client)
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

// Response DTO (to client)
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
        public ?string $statusLabel,      // Translated
        public ?string $priorityLabel     // Translated
    ) {}

    // ✅ Factory method: Entity → DTO
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
            statusLabel: null,   // Set by controller
            priorityLabel: null  // Set by controller
        );
    }
}
```

**Benefits:**
- Validation at DTO level (via attributes)
- Never expose entities to HTTP layer
- Type-safe request/response
- Easy to version (CreateTaskDtoV2)

---

### 3. Dependency Injection (DI)

**Symfony's built-in DI container automatically wires dependencies.**

```php
// ✅ Constructor injection (recommended)
class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TagRepository $tagRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TaskCacheService $taskCache
    ) {}

    // All dependencies injected automatically by Symfony
}

// ✅ Register services in services.yaml
services:
    _defaults:
        autowire: true      # Auto-inject dependencies
        autoconfigure: true # Auto-configure services

    App\:
        resource: '../src/'
        exclude:
            - '../src/Entity/'
            - '../src/Kernel.php'
```

**Benefits:**
- Easy testing (inject mocks)
- Loose coupling
- No manual instantiation

---

### 4. Factory Pattern

**Purpose:** Create complex objects.

```php
// ✅ Static factory method
final readonly class TaskResponseDto
{
    public static function fromEntity(Task $task): self
    {
        return new self(
            id: $task->getId(),
            title: $task->getTitle(),
            // ... map all fields
        );
    }
}

// Usage in service
$dto = TaskResponseDto::fromEntity($task);
```

---

### 5. Event Subscriber Pattern

**Purpose:** Decouple cache invalidation from business logic.

```php
// Event subscriber for automatic cache invalidation
class CacheInvalidationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TaskCacheService $taskCache,
        private readonly AnalyticsCacheService $analyticsCache
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

        // ✅ Automatically update cache
        $this->taskCache->updateAfterCreate($user, $task);
        $this->analyticsCache->invalidateUserAnalytics($user);
    }
}
```

---

## Dependency Injection

### How Symfony DI Works

```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    # Controllers auto-registered
    App\Controller\:
        resource: '../src/Controller/'
        tags: ['controller.service_arguments']

    # Services auto-registered
    App\Service\:
        resource: '../src/Service/'

    # Repositories auto-registered
    App\Repository\:
        resource: '../src/Repository/'
```

### Constructor Injection (Recommended)

```php
class TaskController extends AbstractController
{
    // ✅ Dependencies injected via constructor
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TranslationService $translationService
    ) {}

    public function list(): JsonResponse
    {
        // Use injected services
        $tasks = $this->taskService->getUserTasks(...);
        return $this->json($tasks);
    }
}
```

---

## DTO Pattern

### Request DTOs (Input Validation)

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

### Response DTOs (Output Formatting)

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

    // ✅ Control JSON serialization
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

## Request Flow

### Complete Request/Response Cycle

```
1. HTTP Request arrives
   ↓
2. Symfony Router matches route → TaskController::create()
   ↓
3. Security component checks JWT token
   ↓
4. Voter checks authorization (TaskVoter)
   ↓
5. MapRequestPayload validates CreateTaskDto
   ↓
6. Controller calls TaskService::createTask()
   ↓
7. Service validates business rules
   ↓
8. Service calls TaskRepository::save()
   ↓
9. Repository persists to PostgreSQL
   ↓
10. Service updates TaskCacheService
   ↓
11. Service invalidates AnalyticsCacheService
   ↓
12. Service returns Task entity
   ↓
13. Controller transforms Task → TaskResponseDto
   ↓
14. Controller returns JsonResponse
   ↓
15. Symfony serializes DTO → JSON
   ↓
16. HTTP Response sent to client
```

---

## Code Examples

### Complete CRUD Example

```php
// Controller: HTTP layer
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

// Service: Business logic layer
class TaskService
{
    public function createTask(CreateTaskDto $dto, User $user): Task
    {
        // Business logic
        $task = new Task();
        $task->setTitle($dto->title);
        $task->setUser($user);

        // Persist
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // Cache coordination
        $this->taskCache->updateAfterCreate($user, $task);

        return $task;
    }
}

// Repository: Data access layer
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

## Best Practices

### DO's ✅

✅ **Keep controllers thin** - Only HTTP handling
✅ **Use DTOs** - Never expose entities to HTTP
✅ **Inject dependencies** - Constructor injection
✅ **Use type hints** - Strict types everywhere
✅ **Follow naming conventions** - TaskService, TaskRepository
✅ **Single responsibility** - One class, one purpose
✅ **Use readonly properties** - PHP 8.3 feature
✅ **Use enums** - TaskStatus, TaskPriority
✅ **Cache in repositories** - Where queries happen
✅ **Events for side effects** - Cache invalidation via events

### DON'Ts ❌

❌ **Business logic in controllers**
❌ **Direct database access in controllers**
❌ **Expose entities to HTTP layer**
❌ **Manual request parsing**
❌ **God classes** (one class doing everything)
❌ **Tight coupling** (depend on interfaces)
❌ **Global state** (use dependency injection)
❌ **Magic numbers** (use constants/enums)
❌ **Suppressing errors** (let exceptions bubble up)
❌ **Manual cache management in controllers**

---

## Related Documents

### Must Read Next
- **[Cache System](CACHE_SYSTEM.md)** - Complete caching implementation
- **[Database](DATABASE.md)** - Entity relationships
- **[Authentication](AUTHENTICATION.md)** - JWT & OAuth2

### For Reference
- **[API Reference](API_REFERENCE.md)** - All endpoints
- **[Coding Standards](../CODING_STANDARDS.md)** - Code quality

---

*Last updated: 2025-01-05*
*Architecture version: 1.0*
