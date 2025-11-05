<?php

declare(strict_types=1);

namespace App\Service\Cache;

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
     */
    public function getTaskList(User $user, array $filters, callable $callback): mixed
    {
        $key = $this->keyManager->buildTaskListKey($user, $filters);

        return $this->cacheService->get($key, $callback, self::TTL_TASK_LIST);
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
     * This is MORE PERFORMANT than invalidate - user gets instant fresh data!
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

        // Fetch fresh data ONCE
        $freshTasks = $fetchCallback();

        // Update ALL existing cache keys with fresh data
        $updated = 0;
        foreach ($keys as $fullKey) {
            $serialized = serialize($freshTasks);
            if ($redis->setex($fullKey, self::TTL_TASK_LIST, $serialized)) {
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
     */
    public function updateDynamicViewsCache(User $user, array $callbacks): int
    {
        $updated = 0;

        // Update today's tasks if callback provided
        if (isset($callbacks['today'])) {
            $pattern = $this->keyManager->buildUserPattern($user, 'tasks_today');
            $keys = $this->cacheService->getRedis()->keys($this->cacheService->getPrefix() . $pattern);

            if (!empty($keys)) {
                $freshData = $callbacks['today']();
                foreach ($keys as $fullKey) {
                    if ($this->cacheService->getRedis()->setex($fullKey, self::TTL_TODAY_TASKS, serialize($freshData))) {
                        $updated++;
                    }
                }
            }
        }

        // Update overdue tasks if callback provided
        if (isset($callbacks['overdue'])) {
            $pattern = $this->keyManager->buildUserPattern($user, 'tasks_overdue');
            $keys = $this->cacheService->getRedis()->keys($this->cacheService->getPrefix() . $pattern);

            if (!empty($keys)) {
                $freshData = $callbacks['overdue']();
                foreach ($keys as $fullKey) {
                    if ($this->cacheService->getRedis()->setex($fullKey, self::TTL_OVERDUE_TASKS, serialize($freshData))) {
                        $updated++;
                    }
                }
            }
        }

        // Update upcoming tasks if callback provided
        if (isset($callbacks['upcoming'])) {
            $pattern = $this->keyManager->buildUserPattern($user, 'tasks_upcoming');
            $keys = $this->cacheService->getRedis()->keys($this->cacheService->getPrefix() . $pattern);

            if (!empty($keys)) {
                $freshData = $callbacks['upcoming']();
                foreach ($keys as $fullKey) {
                    if ($this->cacheService->getRedis()->setex($fullKey, self::TTL_TASK_LIST, serialize($freshData))) {
                        $updated++;
                    }
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
