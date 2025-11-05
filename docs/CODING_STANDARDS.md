# Coding Standards & Design Principles

> **THE MOST IMPORTANT DOCUMENT!** This defines how ALL code in this project must be written. Every class, every method, every line must follow these principles.

---

## Table of Contents

- [SOLID Principles](#solid-principles)
- [GRASP Principles](#grasp-principles)
- [GoF Design Patterns](#gof-design-patterns)
- [Backend PHP 8.3 Standards](#backend-php-83-standards)
- [Frontend TypeScript Standards](#frontend-typescript-standards)
- [Code Quality Rules](#code-quality-rules)
- [Common Anti-Patterns to Avoid](#common-anti-patterns-to-avoid)

---

## SOLID Principles

SOLID is the foundation of our architecture. Every class in this project follows these 5 principles.

### S - Single Responsibility Principle

**Rule:** Each class should have only ONE reason to change. One responsibility, one purpose.

#### Example from Project: TaskResponseDto

```php
// location: /backend/src/Dto/Response/Task/TaskResponseDto.php

// GOOD - Single responsibility: Data Transfer Object
final class TaskResponseDto implements \JsonSerializable
{
    // ONLY data properties
    public int $id;
    public string $title;
    public TaskStatus $status;

    // ONLY data transformation methods
    public static function fromEntity(Task $task): self { }
    public static function fromArray(array $data): self { }
    public function jsonSerialize(): array { }

    // NO business logic
    // NO database queries
    // NO HTTP handling
}
```

```php
// BAD - Multiple responsibilities
class TaskDto
{
    public int $id;

    // WRONG! Database logic in DTO
    public function saveToDatabase() { }

    // WRONG! Business logic in DTO
    public function validatePriority() { }

    // WRONG! HTTP handling in DTO
    public function sendEmailNotification() { }
}
```

**Why it matters:** When you need to change how tasks are validated, you don't want to modify the DTO. Each class has one clear purpose.

---

### O - Open/Closed Principle

**Rule:** Classes should be OPEN for extension but CLOSED for modification. Use interfaces and inheritance.

#### Example from Project: Recurrence Strategies

```php
// location: /backend/src/Service/Recurrence/RecurrenceStrategyInterface.php

// GOOD - Interface defines contract
interface RecurrenceStrategyInterface
{
    public function calculateNextOccurrence(
        \DateTimeInterface $currentDate,
        RecurrenceRule $rule
    ): ?\DateTimeInterface;
}

// GOOD - Each strategy implements interface
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

// EXTENSION: Add new strategy WITHOUT modifying existing code
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
// BAD - Giant if/else that needs modification for each new type
class RecurrenceCalculator
{
    public function calculate($type, $date, $interval)
    {
        // WRONG! Need to modify this method to add new types
        if ($type === 'daily') {
            return $date->modify("+$interval days");
        } elseif ($type === 'weekly') {
            return $date->modify("+$interval weeks");
        } elseif ($type === 'monthly') {
            return $date->modify("+$interval months");
        }
        // Adding yearly? Must modify this class!
    }
}
```

**Why it matters:** Adding a new recurrence type (yearly, custom) requires ZERO changes to existing code. Just create a new strategy class.

---

### L - Liskov Substitution Principle

**Rule:** Subtypes must be substitutable for their base types. If S is a subtype of T, then objects of type T may be replaced with objects of type S.

#### Example from Project: Cache Services

```php
// location: /backend/src/Service/Cache/Interface/CacheServiceInterface.php

// GOOD - Base interface
interface CacheServiceInterface
{
    public function get(string $key, callable $callback, ?int $ttl = null): mixed;
    public function set(string $key, mixed $value, ?int $ttl = null): bool;
    public function delete(string $key): bool;
}

// GOOD - Implementation respects contract
final class SimpleRedisCache implements CacheServiceInterface
{
    public function get(string $key, callable $callback, ?int $ttl = null): mixed
    {
        // Works exactly as interface promises
        $value = $this->redis->get($this->prefix . $key);
        if ($value !== false) {
            return unserialize($value);
        }
        $computedValue = $callback();
        $this->set($key, $computedValue, $ttl);
        return $computedValue;
    }

    // Other methods follow contract exactly
}

// Can swap implementations without breaking code
final class MemcachedCache implements CacheServiceInterface { }
final class FilesystemCache implements CacheServiceInterface { }
```

```php
// BAD - Violates contract
class BrokenCache implements CacheServiceInterface
{
    public function get(string $key, callable $callback, ?int $ttl = null): mixed
    {
        // WRONG! Throws exception when interface doesn't promise it
        throw new \Exception("Not implemented");
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        // WRONG! Returns void when interface requires bool
        $this->storage[$key] = $value;
    }
}
```

**Why it matters:** You can swap SimpleRedisCache with MemcachedCache without changing ANY code that uses it.

---

### I - Interface Segregation Principle

**Rule:** Many specific interfaces are better than one general-purpose interface. Don't force classes to implement methods they don't need.

#### Example from Project: Cache Key Management

```php
// location: /backend/src/Service/Cache/Interface/CacheKeyManagerInterface.php

// GOOD - Small, focused interface
interface CacheKeyManagerInterface
{
    public function buildKey(string $namespace, array $params): string;
    public function buildPattern(string $namespace, array $params): string;
}

// GOOD - Separate interface for tagging (not all cache managers need this)
interface TaggableCacheKeyManagerInterface extends CacheKeyManagerInterface
{
    public function generateTags(string $namespace, array $params): array;
}

// Implementation chooses what to implement
final class RedisKeyManager implements TaggableCacheKeyManagerInterface
{
    public function buildKey(string $namespace, array $params): string { }
    public function buildPattern(string $namespace, array $params): string { }
    public function generateTags(string $namespace, array $params): array { }
}

// Simple implementation doesn't need tags
final class SimpleKeyManager implements CacheKeyManagerInterface
{
    public function buildKey(string $namespace, array $params): string { }
    public function buildPattern(string $namespace, array $params): string { }
    // No tags needed!
}
```

```php
// BAD - Fat interface forces unnecessary implementations
interface CacheManagerInterface
{
    public function buildKey(): string;
    public function generateTags(): array;        // Not all need this
    public function warmUp(): void;               // Not all need this
    public function exportMetrics(): array;       // Not all need this
    public function sendToAnalytics(): void;      // Not all need this
}

// WRONG! Simple implementation forced to implement everything
class SimpleKeyManager implements CacheManagerInterface
{
    public function generateTags(): array { return []; } // Unused
    public function warmUp(): void { } // Unused
    public function exportMetrics(): array { return []; } // Unused
    public function sendToAnalytics(): void { } // Unused
}
```

**Why it matters:** Small interfaces = flexible implementations. You don't pay for features you don't use.

---

### D - Dependency Inversion Principle

**Rule:** Depend on abstractions (interfaces), not concretions (classes). High-level modules should not depend on low-level modules.

#### Example from Project: TaskService

```php
// location: /backend/src/Service/TaskService.php

// GOOD - Depends on interfaces, not concrete classes
final class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,              // Interface
        private readonly TaskCacheService $cacheService,               // Interface
        private readonly EventDispatcherInterface $eventDispatcher,   // Interface
        private readonly LoggerInterface $logger,                      // Interface
    ) {
    }

    public function createTask(CreateTaskDto $dto, User $user): Task
    {
        // Works with ANY implementation of these interfaces
        $task = new Task();
        // ... setup task

        $this->taskRepository->save($task);  // Could be Doctrine, MongoDB, etc.
        $this->cacheService->invalidate();   // Could be Redis, Memcached, etc.
        $this->eventDispatcher->dispatch();  // Any event system
        $this->logger->info();               // Any logger

        return $task;
    }
}
```

```php
// BAD - Depends on concrete classes
class TaskService
{
    private DoctrineTaskRepository $repository;     // WRONG! Concrete class
    private RedisCache $cache;                      // WRONG! Concrete class
    private FileLogger $logger;                     // WRONG! Concrete class

    public function __construct()
    {
        // WRONG! Creating dependencies inside
        $this->repository = new DoctrineTaskRepository();
        $this->cache = new RedisCache('localhost', 6379);
        $this->logger = new FileLogger('/var/log/app.log');
    }

    // Now you're LOCKED to these specific implementations
    // Can't test, can't swap, can't extend
}
```

**Why it matters:** Easy testing (mock interfaces), easy swapping (Redis → Memcached), easy extending (add new implementations).

---

## GRASP Principles

GRASP (General Responsibility Assignment Software Patterns) - patterns for assigning responsibilities to classes.

### 1. Information Expert

**Rule:** Assign responsibility to the class that has the information needed to fulfill it.

```php
// GOOD - Task entity knows how to calculate its own completion progress
final class Task
{
    public function getCompletionProgress(): float
    {
        // This class has subtasks, so it's the expert
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

// GOOD - User entity knows how to check permissions
final class User
{
    public function canEditTask(Task $task): bool
    {
        return $task->getUser() === $this;
    }
}
```

```php
// BAD - External class calculating progress without task's data
class TaskProgressCalculator
{
    public function calculate(int $taskId): float
    {
        // WRONG! Needs to fetch task data, inefficient
        $task = $this->repository->find($taskId);
        $subtasks = $this->repository->findSubtasks($taskId);
        // Should be Task's responsibility!
    }
}
```

---

### 2. Creator

**Rule:** Class B should create instances of Class A if one of these is true:
- B contains or aggregates A
- B records instances of A
- B closely uses A
- B has initializing data for A

```php
// GOOD - TaskService creates Tasks (has initializing data from DTO)
final class TaskService
{
    public function createTask(CreateTaskDto $dto, User $user): Task
    {
        // Service is the CREATOR - has DTO data, User reference
        $task = new Task();
        $task->setTitle($dto->title);
        $task->setDescription($dto->description);
        $task->setUser($user);
        $task->setPriority($dto->priority);

        return $task;
    }
}

// GOOD - Task creates Subtasks (contains/aggregates them)
final class Task
{
    public function addSubtask(string $title): Task
    {
        // Task is the CREATOR - contains subtasks
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
// BAD - Controller creating entities (not its responsibility)
class TaskController
{
    public function create(Request $request): JsonResponse
    {
        // WRONG! Controller doesn't have business logic
        $task = new Task();
        $task->setTitle($request->get('title'));
        // Should delegate to Service!
    }
}
```

---

### 3. Controller (Thin Controllers)

**Rule:** Controllers handle system events (HTTP requests) but delegate work to services.

```php
// GOOD - Thin controller (only HTTP concern)
// location: /backend/src/Controller/Api/TaskController.php

final class TaskController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService  // Delegate to service
    ) {
    }

    #[Route('/api/tasks', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateTaskDto $dto
    ): JsonResponse {
        // ONLY HTTP concerns:
        // 1. Get authenticated user
        $user = $this->getUser();

        // 2. Delegate to service (business logic)
        $task = $this->taskService->createTask($dto, $user);

        // 3. Convert to DTO
        $responseDto = TaskResponseDto::fromEntity($task);

        // 4. Return HTTP response
        return $this->json($responseDto, Response::HTTP_CREATED);
    }
}
```

```php
// BAD - Fat controller (business logic inside)
class TaskController
{
    public function create(Request $request): JsonResponse
    {
        // WRONG! Business logic in controller
        $task = new Task();
        $task->setTitle($request->get('title'));

        if (strlen($task->getTitle()) < 3) {
            throw new \Exception('Title too short');
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // WRONG! Cache logic in controller
        $this->cache->invalidate('tasks');

        // WRONG! Event dispatching in controller
        $this->eventDispatcher->dispatch(new TaskCreatedEvent($task));

        return $this->json($task);
    }
}
```

---

### 4. Low Coupling

**Rule:** Minimize dependencies between classes. Classes should be as independent as possible.

```php
// GOOD - Low coupling via dependency injection
final class TaskService
{
    public function __construct(
        private readonly TaskRepository $repository,
        private readonly TaskCacheService $cache,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        // Only 3 dependencies, all through interfaces
    }
}

// GOOD - TaskCacheService doesn't know about TaskService
final readonly class TaskCacheService
{
    public function __construct(
        private SimpleRedisCache $cacheService,
        private RedisKeyManager $keyManager,
    ) {
        // Only 2 dependencies, focused on caching
    }
}
```

```php
// BAD - High coupling (many dependencies)
class TaskManager
{
    private TaskRepository $repository;
    private TaskCacheService $cache;
    private EventDispatcher $eventDispatcher;
    private Logger $logger;
    private EmailService $emailService;           // Too many!
    private NotificationService $notificationService;
    private AnalyticsService $analyticsService;
    private SearchIndexer $searchIndexer;
    private AuditLogger $auditLogger;

    // WRONG! Too many responsibilities, too many dependencies
}
```

---

### 5. High Cohesion

**Rule:** Keep related functionality together. Each class should have a focused, cohesive set of responsibilities.

```php
// GOOD - High cohesion (all methods related to task caching)
final readonly class TaskCacheService
{
    // All methods are about TASK CACHING
    public function getTaskList(User $user, array $filters, callable $callback): array { }
    public function getTask(User $user, int $taskId, callable $callback): mixed { }
    public function getTaskStatistics(User $user, callable $callback): mixed { }
    public function invalidateUserCache(User $user): int { }
    public function updateTaskListsCache(User $user, callable $fetchCallback): int { }
}

// GOOD - High cohesion (all methods related to analytics caching)
final readonly class AnalyticsCacheService
{
    // All methods are about ANALYTICS CACHING
    public function getOverview(User $user, callable $callback): mixed { }
    public function getDashboardData(User $user, array $params, callable $callback): mixed { }
    public function getCompletionTimeline(User $user, int $days, callable $callback): mixed { }
}
```

```php
// BAD - Low cohesion (unrelated methods in same class)
class TaskHelper
{
    public function cacheTask() { }          // Caching
    public function sendEmail() { }          // Email
    public function logActivity() { }        // Logging
    public function generatePDF() { }        // PDF generation
    public function calculateTax() { }       // Business logic
    // WRONG! Too many unrelated responsibilities
}
```

---

### 6. Polymorphism

**Rule:** Use polymorphism to handle alternatives based on type.

```php
// GOOD - Polymorphic recurrence strategies
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
        // Polymorphism - no if/else needed!
        $strategy = $this->strategies[$rule->getRecurrenceType()->value];
        return $strategy->calculateNextOccurrence(
            $rule->getNextOccurrenceDate(),
            $rule
        );
    }
}
```

```php
// BAD - No polymorphism, giant if/else
class RecurrenceCalculator
{
    public function calculate(RecurrenceRule $rule): ?\DateTimeInterface
    {
        // WRONG! If/else instead of polymorphism
        if ($rule->getType() === 'daily') {
            return $this->calculateDaily($rule);
        } elseif ($rule->getType() === 'weekly') {
            return $this->calculateWeekly($rule);
        } elseif ($rule->getType() === 'monthly') {
            return $this->calculateMonthly($rule);
        }
        // Adding new type? Modify this method!
    }
}
```

---

### 7. Pure Fabrication

**Rule:** Create artificial classes (not domain objects) when needed for good design.

```php
// GOOD - Pure fabrication (not a domain entity, but needed for architecture)
final readonly class RedisKeyManager
{
    // Not a real-world concept, but critical for Redis caching architecture
    public function buildTaskListKey(User $user, array $filters): string
    {
        return $this->buildUserKey($user, 'tasks_list', [
            'filters' => $filters
        ]);
    }

    public function buildAnalyticsKey(User $user, string $type, array $params = []): string
    {
        return $this->buildUserKey($user, "analytics_{$type}", $params);
    }
}

// GOOD - Pure fabrication for DTO transformation
final class TaskResponseDto implements \JsonSerializable
{
    // Not a domain entity, but critical for API layer
    public static function fromEntity(Task $task): self { }
    public static function fromArray(array $data): self { }
    public function jsonSerialize(): array { }
}
```

---

### 8. Indirection

**Rule:** Use intermediate objects to reduce coupling and increase reusability.

```php
// GOOD - Indirection via CacheService (intermediate layer)
final class TaskService
{
    public function __construct(
        private readonly TaskCacheService $cacheService,  // Indirection!
    ) {
    }

    public function getActiveTasks(User $user, TaskFilterDto $filters): array
    {
        // Service doesn't talk to Redis directly
        // Goes through TaskCacheService (indirection layer)
        return $this->cacheService->getTaskList(
            $user,
            ['filters' => $filters],
            fn() => $this->taskRepository->findActiveByUser($user, $filters)
        );
    }
}

// GOOD - TaskCacheService provides indirection to Redis
final readonly class TaskCacheService
{
    public function __construct(
        private SimpleRedisCache $cacheService,  // Another indirection!
    ) {
    }

    // Abstracts Redis complexity
    public function getTaskList(User $user, array $filters, callable $callback): array
    {
        // Complex caching logic hidden behind simple interface
    }
}
```

```php
// BAD - No indirection (direct coupling)
class TaskService
{
    private \Redis $redis;

    public function getActiveTasks(User $user): array
    {
        // WRONG! Service talks directly to Redis
        $key = "user:{$user->getId()}:tasks:active";
        $cached = $this->redis->get($key);

        if ($cached) {
            return unserialize($cached);
        }

        $tasks = $this->repository->findActiveByUser($user);
        $this->redis->setex($key, 300, serialize($tasks));

        return $tasks;
    }
}
```

---

### 9. Protected Variations

**Rule:** Protect elements from variations in other elements by wrapping them with stable interface.

```php
// GOOD - Protected from cache implementation changes
interface CacheServiceInterface
{
    public function get(string $key, callable $callback, ?int $ttl = null): mixed;
    public function set(string $key, mixed $value, ?int $ttl = null): bool;
}

// Implementation 1: Redis
final class SimpleRedisCache implements CacheServiceInterface { }

// Implementation 2: Memcached (can swap without changing code!)
final class MemcachedCache implements CacheServiceInterface { }

// Implementation 3: Filesystem (for testing)
final class FilesystemCache implements CacheServiceInterface { }

// Services are PROTECTED from variations
final class TaskService
{
    public function __construct(
        private readonly CacheServiceInterface $cache  // Stable interface!
    ) {
        // Don't care if it's Redis, Memcached, or Filesystem
        // Interface protects from variations
    }
}
```

---

## GoF Design Patterns

### 1. Repository Pattern

**Purpose:** Separate data access logic from business logic.

```php
// GOOD - Repository abstracts database
// location: /backend/src/Repository/Database/TaskRepository.php

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

        // Complex query logic isolated here
        if ($filters->status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $filters->status);
        }

        return $qb->getQuery()->getResult();
    }
}

// Service uses repository (doesn't know about Doctrine)
final class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository
    ) {
    }

    public function getActiveTasks(User $user, TaskFilterDto $filters): array
    {
        // Clean! No SQL, no Doctrine, just domain logic
        return $this->repository->findActiveByUser($user, $filters);
    }
}
```

---

### 2. Factory Pattern (Static Factory Methods)

**Purpose:** Encapsulate object creation logic.

```php
// GOOD - Factory methods in DTO
// location: /backend/src/Dto/Response/Task/TaskResponseDto.php

final class TaskResponseDto implements \JsonSerializable
{
    // Factory method: Database → DTO
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

    // Factory method: Redis Cache → DTO
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

// Usage is clean and expressive
$dtoFromDb = TaskResponseDto::fromEntity($task);
$dtoFromCache = TaskResponseDto::fromArray($cachedData);
```

---

### 3. Strategy Pattern

**Purpose:** Define a family of algorithms, encapsulate each one, and make them interchangeable.

```php
// GOOD - Cache invalidation strategies
// location: /backend/src/Service/Cache/TaskCacheService.php

final readonly class TaskCacheService
{
    // STRATEGY: UPDATE (proactive) - updates cache with fresh data
    public function updateTaskListsCache(User $user, callable $fetchCallback): int
    {
        // Fetch fresh data ONCE
        $freshTasks = $fetchCallback();

        // Convert to DTOs ONCE
        $taskDtos = array_map(
            fn(Task $task) => TaskResponseDto::fromEntity($task, false, true),
            $freshTasks
        );

        // Update ALL cache keys with same data
        $json = json_encode($taskDtos, JSON_THROW_ON_ERROR);

        $redis = $this->cacheService->getRedis();
        $keys = $redis->keys($this->cacheService->getPrefix() . $pattern);

        foreach ($keys as $fullKey) {
            $redis->setex($fullKey, self::TTL_TASK_LIST, $json);
        }

        return count($keys);
    }

    // STRATEGY: INVALIDATE (reactive) - deletes cache, lazy recompute
    public function invalidateTaskLists(User $user): int
    {
        $pattern = $this->keyManager->buildUserPattern($user, 'tasks_list');
        return $this->cacheService->deleteByPattern($pattern);
    }
}

// Why UPDATE is better than INVALIDATE:
// - UPDATE: User gets fresh data instantly (no delay)
// - INVALIDATE: First request after invalidation is slow (cache miss)
// - UPDATE: Fetch once, update many caches
// - INVALIDATE: Each cache miss fetches from DB separately
```

**When to use each strategy:**

- **UPDATE:** For frequently accessed data (tasks, analytics dashboard)
- **INVALIDATE:** For rarely accessed or parameter-heavy data (specific filters)

---

### 4. Observer Pattern (Event Subscribers)

**Purpose:** Define one-to-many dependency. When one object changes state, all dependents are notified.

```php
// GOOD - Event subscriber observes entity changes
// location: /backend/src/EventSubscriber/CacheInvalidationSubscriber.php

final readonly class CacheInvalidationSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        // Observer pattern: Listen to Doctrine events
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        // React to changes
        if ($entity instanceof Task) {
            $this->invalidateTaskCache($entity, 'update');
        } elseif ($entity instanceof Tag) {
            $this->invalidateTagCache($entity, 'update');
        }
    }

    private function invalidateTaskCache(Task $task, string $operation): void
    {
        $user = $task->getUser();

        // Invalidate task lists
        $this->taskCache->invalidateTaskLists($user);

        // Invalidate statistics
        $this->taskCache->invalidateStatistics($user);

        // Invalidate analytics
        $this->analyticsCache->invalidateAll($user);
    }
}
```

**Benefits:**
- Decoupled: TaskService doesn't know about cache invalidation
- Automatic: Cache invalidates whenever entity changes
- Extensible: Add new observers without modifying existing code

---

### 5. Builder Pattern (DTO Construction)

**Purpose:** Separate construction of complex object from its representation.

```php
// GOOD - TaskResponseDto acts as builder for complex task representation
final class TaskResponseDto implements \JsonSerializable
{
    public static function fromEntity(
        Task $task,
        bool $includeSubtasks = false,
        bool $includeMeta = true
    ): self {
        $dto = new self();

        // Step 1: Basic properties
        $dto->id = $task->getId();
        $dto->title = $task->getTitle();
        $dto->status = $task->getStatus();

        // Step 2: Conditional metadata
        if ($includeMeta) {
            $dto->createdAt = $task->getCreatedAt();
            $dto->updatedAt = $task->getUpdatedAt();
        }

        // Step 3: Subtask counts
        $subtasks = $task->getSubtasks();
        $dto->subtaskCount = $subtasks->count();
        $dto->completedSubtaskCount = $subtasks->filter(
            fn(Task $subtask) => $subtask->isCompleted()
        )->count();

        // Step 4: Conditional nested subtasks
        if ($includeSubtasks) {
            $dto->subtasks = array_map(
                fn($subtask) => self::fromEntity($subtask, true, $includeMeta),
                $subtasks->toArray()
            );
        }

        // Step 5: Tags transformation
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

// Usage shows flexibility
$lightDto = TaskResponseDto::fromEntity($task, false, false);     // Minimal
$fullDto = TaskResponseDto::fromEntity($task, true, true);        // Complete
$listDto = TaskResponseDto::fromEntity($task, false, true);       // For lists
```

---

## Backend PHP 8.3 Standards

### Naming Conventions

```php
// PascalCase for classes
final class TaskService { }
final class TaskResponseDto { }
final class SimpleRedisCache { }

// camelCase for methods and variables
public function createTask() { }
public function getActiveTasks() { }
private string $userName;
private int $taskCount;

// SCREAMING_SNAKE_CASE for constants
private const TTL_TASK_LIST = 300;
private const TTL_ANALYTICS_DASHBOARD = 900;
public const MAX_SUBTASKS_DEPTH = 5;

// snake_case for database columns (migration files)
$table->addColumn('created_at', 'datetime');
$table->addColumn('due_date', 'datetime_immutable');
$table->addColumn('is_completed', 'boolean');
```

### Type Hints EVERYWHERE

```php
// GOOD - Full type hints
final class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,           // Type hint
        private readonly TaskCacheService $cacheService,           // Type hint
        private readonly EventDispatcherInterface $eventDispatcher, // Type hint
        private readonly LoggerInterface $logger,                  // Type hint
    ) {
    }

    public function createTask(CreateTaskDto $dto, User $user): Task  // Return type
    {
        $task = new Task();
        // ...
        return $task;
    }

    public function getActiveTasks(User $user, TaskFilterDto $filters): array  // Return type
    {
        return $this->cacheService->getTaskList(
            user: $user,
            filters: ['status' => $filters->status],
            callback: fn(): array => $this->taskRepository->findActiveByUser($user, $filters)
        );
    }
}
```

```php
// BAD - No type hints
class TaskService
{
    private $repository;  // WRONG! No type
    private $cache;       // WRONG! No type

    public function create($dto, $user)  // WRONG! No types
    {
        // ...
        return $task;  // WRONG! No return type
    }
}
```

### Readonly Properties (PHP 8.1+)

```php
// GOOD - Readonly properties prevent accidental mutation
final class TaskResponseDto
{
    public readonly int $id;
    public readonly string $title;
    public readonly TaskStatus $status;

    // Or entire class readonly (PHP 8.2+)
}

final readonly class TaskCacheService
{
    // All properties are automatically readonly
    public function __construct(
        private SimpleRedisCache $cacheService,
        private RedisKeyManager $keyManager,
    ) {
    }
}
```

### Constructor Property Promotion (PHP 8.0+)

```php
// GOOD - Modern PHP 8.0+ syntax
final class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TaskCacheService $cacheService,
        private readonly LoggerInterface $logger,
    ) {
        // Properties declared and initialized automatically!
    }
}
```

```php
// BAD - Old PHP 7 syntax (verbose)
class TaskService
{
    private TaskRepository $taskRepository;
    private TaskCacheService $cacheService;
    private LoggerInterface $logger;

    public function __construct(
        TaskRepository $taskRepository,
        TaskCacheService $cacheService,
        LoggerInterface $logger
    ) {
        $this->taskRepository = $taskRepository;
        $this->cacheService = $cacheService;
        $this->logger = $logger;
    }
}
```

### Enums Instead of Constants (PHP 8.1+)

```php
// GOOD - Enum with methods
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
            self::PENDING => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
}

// Usage
$task->setStatus(TaskStatus::IN_PROGRESS);
$color = $task->getStatus()->getColor();
```

```php
// BAD - Constants (old way)
class TaskStatus
{
    public const PENDING = 'pending';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED = 'completed';

    // WRONG! No type safety, no methods
}

// WRONG! Can use invalid values
$task->setStatus('invalid_status');  // No error!
```

### Match Expressions (PHP 8.0+)

```php
// GOOD - Match expression (type-safe, exhaustive)
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

// GOOD - Match with enums
public function getStatusColor(TaskStatus $status): string
{
    return match($status) {
        TaskStatus::PENDING => '#94a3b8',
        TaskStatus::IN_PROGRESS => '#3b82f6',
        TaskStatus::COMPLETED => '#22c55e',
        TaskStatus::CANCELLED => '#ef4444',
        // No default needed - exhaustive!
    };
}
```

```php
// BAD - Switch statement (verbose, not exhaustive)
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

### Named Arguments (PHP 8.0+)

```php
// GOOD - Named arguments for clarity
$tasks = $this->cacheService->getTaskList(
    user: $user,
    filters: ['status' => TaskStatus::PENDING],
    callback: fn() => $this->repository->findPendingTasks($user)
);

$dto = TaskResponseDto::fromEntity(
    task: $task,
    includeSubtasks: true,
    includeMeta: false
);
```

```php
// BAD - Positional arguments (unclear)
$tasks = $this->cacheService->getTaskList(
    $user,
    ['status' => TaskStatus::PENDING],
    fn() => $this->repository->findPendingTasks($user)
);

$dto = TaskResponseDto::fromEntity($task, true, false);  // What do true/false mean?
```

---

## Frontend TypeScript Standards

### Strict Mode (No 'any')

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
// GOOD - Full typing
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
// BAD - Using 'any'
function updateTask(task: any, updates: any): any {  // WRONG!
  return { ...task, ...updates }
}

const tasks = ref([])  // WRONG! No type
const data: any = {}   // WRONG! 'any' disables type checking
```

### Type Everything (Props, Emits, State)

```vue
<script setup lang="ts">
// GOOD - Typed props
interface Props {
  task: Task
  readonly?: boolean
  showSubtasks?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  readonly: false,
  showSubtasks: true
})

// GOOD - Typed emits
interface Emits {
  (e: 'update:task', task: Task): void
  (e: 'delete', taskId: number): void
  (e: 'subtask-added', subtask: Task): void
}

const emit = defineEmits<Emits>()

// GOOD - Typed reactive state
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
// BAD - No types
const props = defineProps({
  task: Object,  // WRONG! Should be Task
  readonly: Boolean
})

const emit = defineEmits(['update', 'delete'])  // WRONG! No parameter types

const tasks = ref([])  // WRONG! No type
</script>
```

### Interfaces for Objects

```typescript
// GOOD - Interfaces define structure
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

### Type Guards

```typescript
// GOOD - Type guards for runtime type checking
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

// Usage
const response = await api.getTasks()
if (isTasks(response)) {
  tasks.value = response
} else {
  console.error('Invalid tasks response')
}
```

### Composition API Only (No Options API)

```vue
<script setup lang="ts">
// GOOD - Composition API
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
// BAD - Options API (don't use)
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

### Smart/Dumb Components

```vue
<!-- SMART COMPONENT (container) -->
<!-- location: /frontend/src/views/TaskListView.vue -->
<script setup lang="ts">
// Smart: Has business logic, store access, API calls
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
<!-- DUMB COMPONENT (presentational) -->
<!-- location: /frontend/src/components/tasks/TaskList.vue -->
<script setup lang="ts">
// Dumb: Only receives props, emits events, no business logic
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

// No store access, no API calls!
// Just presentation and event emission
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

## Code Quality Rules

### DRY (Don't Repeat Yourself)

```php
// GOOD - Extract to reusable method
final class TaskResponseDto
{
    public static function fromEntity(Task $task, bool $includeSubtasks = false): self
    {
        $dto = new self();
        $dto->id = $task->getId();
        $dto->title = $task->getTitle();

        $dto->tags = self::mapTags($task->getTags());  // Reusable

        if ($includeSubtasks) {
            $dto->subtasks = array_map(
                fn($subtask) => self::fromEntity($subtask, true),
                $task->getSubtasks()->toArray()
            );
        }

        return $dto;
    }

    // Reusable tag mapping
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
// BAD - Repeated code
final class TaskResponseDto
{
    public static function fromEntity(Task $task): self
    {
        $dto = new self();

        // WRONG! Tag mapping repeated
        $dto->tags = array_map(
            static fn($tag) => [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
                'color' => $tag->getColor(),
            ],
            $task->getTags()->toArray()
        );

        // WRONG! Same code repeated for subtasks
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

### KISS (Keep It Simple, Stupid)

```php
// GOOD - Simple and clear
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
// BAD - Overly complex
public function isOverdue(): bool
{
    // WRONG! Too complex for simple check
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

### YAGNI (You Aren't Gonna Need It)

```php
// GOOD - Only implement what's needed NOW
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
// BAD - Over-engineering for future needs
final class TaskService
{
    // WRONG! We don't need these features yet
    public function createTaskWithAI(CreateTaskDto $dto, User $user): Task { }
    public function createTaskFromEmail(string $email, User $user): Task { }
    public function createTaskFromVoice(string $audioFile, User $user): Task { }
    public function scheduleTaskCreation(CreateTaskDto $dto, \DateTime $when): void { }
    public function bulkCreateTasksParallel(array $dtos, User $user): array { }
}
```

---

## Common Anti-Patterns to Avoid

### God Objects

```php
// BAD - God object (does everything)
class TaskManager
{
    // Database
    public function save() { }
    public function delete() { }
    public function query() { }

    // Validation
    public function validate() { }

    // Caching
    public function cache() { }
    public function invalidate() { }

    // Email
    public function sendEmail() { }

    // Analytics
    public function trackEvent() { }

    // Export
    public function exportToPDF() { }
    public function exportToExcel() { }

    // Too many responsibilities!
}
```

**Fix:** Split into focused classes (TaskService, TaskRepository, TaskCacheService, TaskExporter)

### Anemic Domain Model

```php
// BAD - Anemic (just getters/setters, no behavior)
class Task
{
    private string $title;
    private bool $isCompleted;

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }
    public function isCompleted(): bool { return $this->isCompleted; }
    public function setIsCompleted(bool $isCompleted): void { $this->isCompleted = $isCompleted; }
}

// WRONG! Business logic outside entity
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
// GOOD - Rich domain model (behavior in entity)
class Task
{
    private string $title;
    private bool $isCompleted;
    private ?\DateTimeImmutable $completedAt = null;

    public function complete(): void
    {
        // Business logic belongs here!
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
        $task->complete();  // Clean!
        $this->repository->save($task);
    }
}
```

### Magic Numbers

```php
// BAD - Magic numbers
public function getTtl(): int
{
    if ($this->type === 'tasks') {
        return 300;  // What is 300?
    } elseif ($this->type === 'analytics') {
        return 900;  // What is 900?
    }
}
```

```php
// GOOD - Named constants
final readonly class TaskCacheService
{
    private const TTL_TASK_LIST = 300;      // 5 minutes
    private const TTL_TASK_STATS = 300;     // 5 minutes
    private const TTL_TODAY_TASKS = 60;     // 1 minute (dynamic)

    public function getTtl(string $type): int
    {
        return match($type) {
            'task_list' => self::TTL_TASK_LIST,
            'task_stats' => self::TTL_TASK_STATS,
            'today_tasks' => self::TTL_TODAY_TASKS,
            default => 300,
        };
    }
}
```

---

## Summary Checklist

Before committing code, verify:

### PHP Backend
- [ ] All classes use type hints (parameters and return types)
- [ ] Constructor property promotion used
- [ ] Readonly properties where possible
- [ ] Enums instead of constants
- [ ] Match expressions instead of switch
- [ ] Named arguments for clarity
- [ ] Each class has single responsibility
- [ ] Dependencies injected via constructor
- [ ] Controllers are thin (only HTTP logic)
- [ ] Business logic in services
- [ ] Database queries in repositories

### TypeScript Frontend
- [ ] Strict mode enabled
- [ ] No 'any' types
- [ ] Props typed with interfaces
- [ ] Emits typed with interfaces
- [ ] Reactive state typed
- [ ] Type guards for unknown data
- [ ] Composition API only
- [ ] Smart/dumb component separation

### General
- [ ] No code duplication (DRY)
- [ ] Simple solutions (KISS)
- [ ] No premature optimization (YAGNI)
- [ ] Meaningful variable names
- [ ] Functions do one thing
- [ ] Comments explain WHY, not WHAT

---

**Last Updated:** November 5, 2025
