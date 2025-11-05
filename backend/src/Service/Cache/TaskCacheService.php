<?php

declare(strict_types=1);

namespace App\Service\Cache;

use App\Dto\Response\Task\TaskResponseDto;
use App\Entity\Task;
use App\Entity\User;

/**
 * Professional Task Cache Service
 * Handles all task-related caching with Redis using SimpleRedisCache
 */
final readonly class TaskCacheService
{
    // TTL constants (in seconds)
    private const TTL_TASK_LIST = 300;      // 5 minutes
    private const TTL_SINGLE_TASK = 300;    // 5 minutes
    private const TTL_TASK_STATS = 300;     // 5 minutes
    private const TTL_TODAY_TASKS = 60;     // 1 minute (more dynamic)
    private const TTL_OVERDUE_TASKS = 120;  // 2 minutes

    public function __construct(
        private SimpleRedisCache $cacheService,
        private RedisKeyManager $keyManager,
    ) {
    }

    /**
     * Get or compute task list for user
     *
     * Strategy: JSON serialization for optimal storage and readability
     *
     * Flow:
     * 1. Cache HIT:  Redis JSON → Array → TaskResponseDto[]
     * 2. Cache MISS: DB Task[] → TaskResponseDto[] → JSON → Redis
     *
     * @param User $user The user to fetch tasks for
     * @param array $filters Filter parameters for the task list
     * @param callable $callback Callback that returns Task[] entities from database
     * @return TaskResponseDto[] Array of task response DTOs
     */
    public function getTaskList(User $user, array $filters, callable $callback): array
    {
        $key = $this->keyManager->buildTaskListKey($user, $filters);
        $redis = $this->cacheService->getRedis();
        $fullKey = $this->cacheService->getPrefix() . $key;

        // Try to get from cache
        $cached = $redis->get($fullKey);

        if ($cached !== false) {
            // Cache HIT: Deserialize JSON → Array → TaskResponseDto[]
            $tasksArray = json_decode($cached, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($tasksArray)) {
                // Convert array to TaskResponseDto objects (SOLID: Single Responsibility)
                return array_map(
                    fn(array $taskData) => TaskResponseDto::fromArray($taskData),
                    $tasksArray
                );
            }
        }

        // Cache MISS: Fetch from database
        /** @var Task[] $tasks */
        $tasks = $callback();

        // Convert Entity → DTO (SOLID: Dependency Inversion - callback provides entities)
        // IMPORTANT: includeSubtasks: FALSE for list views to prevent memory exhaustion
        // Subtasks are loaded separately when user opens a specific task (GET /api/tasks/{id}?includeSubtasks=true)
        $taskDtos = array_map(
            fn(Task $task) => TaskResponseDto::fromEntity($task, includeSubtasks: false, includeMeta: true),
            $tasks
        );

        // Serialize DTO → JSON (human-readable format for Redis)
        // Using json_encode() directly since DTOs are simple data containers with public properties
        $json = json_encode($taskDtos, JSON_THROW_ON_ERROR);

        // Store in cache
        $redis->setex($fullKey, self::TTL_TASK_LIST, $json);

        return $taskDtos;
    }

    /**
     * Get or compute single task
     */
    public function getTask(User $user, int $taskId, callable $callback): mixed
    {
        $key = $this->keyManager->buildTaskKey($user, $taskId);

        return $this->cacheService->get($key, $callback, self::TTL_SINGLE_TASK);
    }

    /**
     * Get or compute task statistics
     */
    public function getTaskStatistics(User $user, callable $callback): mixed
    {
        $key = $this->keyManager->buildTaskStatsKey($user);

        return $this->cacheService->get($key, $callback, self::TTL_TASK_STATS);
    }

    /**
     * Get or compute today's tasks
     */
    public function getTodayTasks(User $user, array $filters, callable $callback): mixed
    {
        $key = $this->keyManager->buildUserKey($user, 'tasks_today', ['filters' => $filters]);

        return $this->cacheService->get($key, $callback, self::TTL_TODAY_TASKS);
    }

    /**
     * Get or compute overdue tasks
     */
    public function getOverdueTasks(User $user, callable $callback): mixed
    {
        $key = $this->keyManager->buildUserKey($user, 'tasks_overdue');

        return $this->cacheService->get($key, $callback, self::TTL_OVERDUE_TASKS);
    }

    /**
     * Get or compute upcoming tasks
     */
    public function getUpcomingTasks(User $user, int $days, array $filters, callable $callback): mixed
    {
        $key = $this->keyManager->buildUserKey($user, 'tasks_upcoming', [
            'days' => $days,
            'filters' => $filters
        ]);

        return $this->cacheService->get($key, $callback, self::TTL_TASK_LIST);
    }

    /**
     * Invalidate all task caches for user
     */
    public function invalidateUserCache(User $user): int
    {
        $pattern = $this->keyManager->buildAllUserTasksPattern($user);

        return $this->cacheService->deleteByPattern($pattern);
    }

    /**
     * Invalidate specific task cache
     */
    public function invalidateTask(User $user, int $taskId): bool
    {
        $key = $this->keyManager->buildTaskKey($user, $taskId);

        return $this->cacheService->delete($key);
    }

    /**
     * UPDATE: Update all task list caches with fresh data
     *
     * Strategy: Fetch once, serialize once, update all cache entries
     * This is MORE PERFORMANT than invalidate - user gets instant fresh data!
     *
     * @param User $user The user whose cache to update
     * @param callable $fetchCallback Callback that returns fresh Task[] entities
     * @return int Number of cache entries updated
     */
    public function updateTaskListsCache(User $user, callable $fetchCallback): int
    {
        $pattern = $this->keyManager->buildUserPattern($user, 'tasks_list');

        // Find all existing cache keys for task lists
        $redis = $this->cacheService->getRedis();
        $keys = $redis->keys($this->cacheService->getPrefix() . $pattern);

        if (empty($keys)) {
            return 0; // No caches to update
        }

        // Fetch fresh data ONCE (GRASP: Information Expert - callback knows how to fetch)
        /** @var Task[] $freshTasks */
        $freshTasks = $fetchCallback();

        // Convert Entity → DTO ONCE (avoid duplicate work)
        // IMPORTANT: includeSubtasks: FALSE for bulk cache updates to prevent memory exhaustion
        // Subtasks are loaded separately when user opens a specific task
        // Check if we have entities or DTOs
        if (!empty($freshTasks) && $freshTasks[0] instanceof TaskResponseDto) {
            // Already DTOs, use them directly
            $taskDtos = $freshTasks;
        } else {
            // Convert entities to DTOs WITHOUT subtasks (memory optimization)
            $taskDtos = array_map(
                fn(Task $task) => TaskResponseDto::fromEntity($task, includeSubtasks: false, includeMeta: true),
                $freshTasks
            );
        }

        // Serialize to JSON ONCE
        // Using json_encode() directly since DTOs are simple data containers with public properties
        $json = json_encode($taskDtos, JSON_THROW_ON_ERROR);

        // Update ALL existing cache keys with the same fresh JSON
        $updated = 0;
        foreach ($keys as $fullKey) {
            if ($redis->setex($fullKey, self::TTL_TASK_LIST, $json)) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * LEGACY: Invalidate task list caches (delete approach - slower)
     * Use updateTaskListsCache() for better performance!
     */
    public function invalidateTaskLists(User $user): int
    {
        $pattern = $this->keyManager->buildUserPattern($user, 'tasks_list');

        return $this->cacheService->deleteByPattern($pattern);
    }

    /**
     * UPDATE: Update statistics cache with fresh data
     */
    public function updateStatisticsCache(User $user, callable $fetchCallback): bool
    {
        $key = $this->keyManager->buildTaskStatsKey($user);

        // Fetch fresh statistics
        $freshStats = $fetchCallback();

        // Update cache
        return $this->cacheService->set($key, $freshStats, self::TTL_TASK_STATS);
    }

    /**
     * LEGACY: Invalidate task statistics
     */
    public function invalidateStatistics(User $user): bool
    {
        $key = $this->keyManager->buildTaskStatsKey($user);

        return $this->cacheService->delete($key);
    }

    /**
     * UPDATE: Update dynamic views caches with fresh data
     *
     * Updates special task views (today, overdue, upcoming) with fresh JSON data
     *
     * @param User $user The user whose cache to update
     * @param array $callbacks Associative array of callbacks ['today' => callable, 'overdue' => callable, 'upcoming' => callable]
     * @return int Number of cache entries updated
     */
    public function updateDynamicViewsCache(User $user, array $callbacks): int
    {
        $redis = $this->cacheService->getRedis();
        $updated = 0;

        // Define views configuration (GRASP: Low Coupling - centralized configuration)
        $views = [
            'today' => ['pattern' => 'tasks_today', 'ttl' => self::TTL_TODAY_TASKS],
            'overdue' => ['pattern' => 'tasks_overdue', 'ttl' => self::TTL_OVERDUE_TASKS],
            'upcoming' => ['pattern' => 'tasks_upcoming', 'ttl' => self::TTL_TASK_LIST],
        ];

        foreach ($views as $viewName => $config) {
            if (!isset($callbacks[$viewName])) {
                continue; // Skip if no callback provided
            }

            $pattern = $this->keyManager->buildUserPattern($user, $config['pattern']);
            $keys = $redis->keys($this->cacheService->getPrefix() . $pattern);

            if (empty($keys)) {
                continue; // No caches to update for this view
            }

            // Fetch fresh data ONCE per view
            /** @var Task[] $freshTasks */
            $freshTasks = $callbacks[$viewName]();

            // Convert Entity → DTO ONCE
            // IMPORTANT: includeSubtasks: FALSE for bulk cache updates to prevent memory exhaustion
            // Subtasks are loaded separately when user opens a specific task
            // Check if we have entities or DTOs
            if (!empty($freshTasks) && $freshTasks[0] instanceof TaskResponseDto) {
                // Already DTOs, use them directly
                $taskDtos = $freshTasks;
            } else {
                // Convert entities to DTOs WITHOUT subtasks (memory optimization)
                $taskDtos = array_map(
                    fn(Task $task) => TaskResponseDto::fromEntity($task, includeSubtasks: false, includeMeta: true),
                    $freshTasks
                );
            }

            // Serialize to JSON ONCE
            // Using json_encode() directly since DTOs are simple data containers with public properties
            $json = json_encode($taskDtos, JSON_THROW_ON_ERROR);

            // Update all cache keys for this view
            foreach ($keys as $fullKey) {
                if ($redis->setex($fullKey, $config['ttl'], $json)) {
                    $updated++;
                }
            }
        }

        return $updated;
    }

    /**
     * LEGACY: Invalidate dynamic task views (today, overdue, upcoming)
     */
    public function invalidateDynamicViews(User $user): int
    {
        $patterns = [
            $this->keyManager->buildUserPattern($user, 'tasks_today'),
            $this->keyManager->buildUserPattern($user, 'tasks_overdue'),
            $this->keyManager->buildUserPattern($user, 'tasks_upcoming'),
        ];

        $deleted = 0;
        foreach ($patterns as $pattern) {
            $deleted += $this->cacheService->deleteByPattern($pattern);
        }

        return $deleted;
    }

    /**
     * Warm up cache for common queries
     */
    public function warmUp(User $user, callable $listCallback, callable $statsCallback): void
    {
        // Warm up default task list
        $this->getTaskList($user, [], $listCallback);

        // Warm up statistics
        $this->getTaskStatistics($user, $statsCallback);

        // Warm up today's tasks
        $this->getTodayTasks($user, [], function () use ($user) {
            return []; // Will be filled by actual callback in real usage
        });
    }

    /**
     * Get cache key for debugging
     */
    public function getCacheKey(User $user, string $type, array $params = []): string
    {
        return match ($type) {
            'list' => $this->keyManager->buildTaskListKey($user, $params),
            'task' => $this->keyManager->buildTaskKey($user, $params['id'] ?? 0),
            'stats' => $this->keyManager->buildTaskStatsKey($user),
            'today' => $this->keyManager->buildUserKey($user, 'tasks_today', $params),
            'overdue' => $this->keyManager->buildUserKey($user, 'tasks_overdue'),
            'upcoming' => $this->keyManager->buildUserKey($user, 'tasks_upcoming', $params),
            default => ''
        };
    }
}
