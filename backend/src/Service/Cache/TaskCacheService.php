<?php

declare(strict_types=1);

namespace App\Service\Cache;

use App\Entity\User;
use App\Service\Cache\Interface\CacheServiceInterface;

/**
 * Professional Task Cache Service
 * Handles all task-related caching with Redis
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
        private CacheServiceInterface $cacheService,
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
     * Invalidate task list caches (when task list changes)
     */
    public function invalidateTaskLists(User $user): int
    {
        $pattern = $this->keyManager->buildUserPattern($user, 'tasks_list');

        return $this->cacheService->deleteByPattern($pattern);
    }

    /**
     * Invalidate task statistics
     */
    public function invalidateStatistics(User $user): bool
    {
        $key = $this->keyManager->buildTaskStatsKey($user);

        return $this->cacheService->delete($key);
    }

    /**
     * Invalidate dynamic task views (today, overdue, upcoming)
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
