<?php

declare(strict_types=1);

namespace App\Service\Cache;

use App\Entity\User;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class TaskCache
{
    public function __construct(
        private CacheInterface $cache
    ) {
    }

    /**
     * Get cached task list for user
     */
    public function getTaskList(User $user, array $filters, callable $callback): mixed
    {
        $cacheKey = $this->buildTaskListKey($user, $filters);
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(300); // 5 minutes for task lists
            return $callback();
        });
    }

    /**
     * Get cached single task
     */
    public function getTask(User $user, int $taskId, callable $callback): mixed
    {
        $cacheKey = $this->buildTaskKey($user, $taskId);
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(300); // 5 minutes for single tasks
            return $callback();
        });
    }

    /**
     * Get cached task statistics
     */
    public function getTaskStatistics(User $user, callable $callback): mixed
    {
        $cacheKey = $this->buildTaskStatisticsKey($user);
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(300); // 5 minutes for statistics
            return $callback();
        });
    }

    /**
     * Invalidate all cache for user
     */
    public function invalidateUserCache(User $user): void
    {
        $pattern = $this->buildUserCachePattern($user);
        // Note: Symfony Cache doesn't support pattern deletion by default
        // We'll delete specific known keys instead
        $this->deleteUserTaskListCache($user);
        $this->deleteUserTaskStatisticsCache($user);
        $this->deleteUserSingleTasksCache($user);
    }

    /**
     * Delete task list cache for user
     */
    public function deleteUserTaskListCache(User $user): void
    {
        // Since we can't pattern match, we'll delete common filter combinations
        $commonFilters = [
            [],
            ['status' => 'pending'],
            ['status' => 'in_progress'],
            ['status' => 'completed'],
            ['status' => 'cancelled'],
            ['completed' => 'false'],
            ['completed' => 'true'],
        ];

        foreach ($commonFilters as $filters) {
            $cacheKey = $this->buildTaskListKey($user, $filters);
            try {
                $this->cache->delete($cacheKey);
            } catch (\Throwable $e) {
                // Ignore cache deletion errors
            }
        }
    }

    /**
     * Delete task statistics cache for user
     */
    public function deleteUserTaskStatisticsCache(User $user): void
    {
        $cacheKey = $this->buildTaskStatisticsKey($user);
        try {
            $this->cache->delete($cacheKey);
        } catch (\Throwable $e) {
            // Ignore cache deletion errors
        }
    }

    /**
     * Delete single task cache for user
     */
    public function deleteUserSingleTasksCache(User $user): void
    {
        // We can't easily delete all single task caches, so we'll rely on TTL
        // In a production system, you might want to use Redis SCAN or maintain a separate index
    }

    /**
     * Delete specific task cache
     */
    public function deleteTaskCache(User $user, int $taskId): void
    {
        $cacheKey = $this->buildTaskKey($user, $taskId);
        try {
            $this->cache->delete($cacheKey);
        } catch (\Throwable $e) {
            // Ignore cache deletion errors
        }
    }

    /**
     * Build cache key for task list
     */
    private function buildTaskListKey(User $user, array $filters): string
    {
        ksort($filters);
        $filtersHash = md5(serialize($filters));
        return sprintf('tasks_list_%d_%s', $user->getId(), $filtersHash);
    }

    /**
     * Build cache key for single task
     */
    private function buildTaskKey(User $user, int $taskId): string
    {
        return sprintf('task_%d_%d', $user->getId(), $taskId);
    }

    /**
     * Build cache key for task statistics
     */
    private function buildTaskStatisticsKey(User $user): string
    {
        return sprintf('task_stats_%d', $user->getId());
    }

    /**
     * Build pattern for user cache keys
     */
    private function buildUserCachePattern(User $user): string
    {
        return sprintf('tasks_*_%d*', $user->getId());
    }
}
