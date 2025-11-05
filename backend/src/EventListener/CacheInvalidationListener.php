<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Task;
use App\Entity\Tag;
use App\Service\Cache\TaskCacheService;
use App\Service\Cache\AnalyticsCacheService;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Psr\Log\LoggerInterface;

/**
 * Professional Cache Invalidation Listener
 * Uses intelligent selective invalidation instead of clearing everything
 */
final readonly class CacheInvalidationListener
{
    public function __construct(
        private TaskCacheService $taskCache,
        private AnalyticsCacheService $analyticsCache,
        private LoggerInterface $logger,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->handleCacheInvalidation($args->getObject(), 'persist');
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $this->logger->info('[CacheInvalidation] postUpdate triggered', [
            'entity' => get_class($entity),
            'entity_id' => method_exists($entity, 'getId') ? $entity->getId() : 'N/A'
        ]);
        $this->handleCacheInvalidation($entity, 'update');
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->handleCacheInvalidation($args->getObject(), 'remove');
    }

    private function handleCacheInvalidation(object $entity, string $operation): void
    {
        try {
            if ($entity instanceof Task) {
                $this->invalidateTaskCache($entity, $operation);
            } elseif ($entity instanceof Tag) {
                $this->invalidateTagCache($entity, $operation);
            }
        } catch (\Throwable $e) {
            // Log but don't fail the request
            $this->logger->error('Cache invalidation failed', [
                'entity' => get_class($entity),
                'operation' => $operation,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Intelligently invalidate task-related caches
     */
    private function invalidateTaskCache(Task $task, string $operation): void
    {
        $user = $task->getUser();
        if (!$user) {
            return;
        }

        // Always invalidate specific task cache
        if ($operation !== 'persist') {
            $this->taskCache->invalidateTask($user, $task->getId());
        }

        // Invalidate task lists (they need to reflect the new/updated/removed task)
        $this->taskCache->invalidateTaskLists($user);

        // Invalidate dynamic views (today, overdue, upcoming)
        $this->taskCache->invalidateDynamicViews($user);

        // Invalidate statistics
        $this->taskCache->invalidateStatistics($user);

        // Invalidate analytics - but selectively
        $this->invalidateAnalyticsForTask($user, $task, $operation);

        $this->logger->info('Task cache invalidated', [
            'user_id' => $user->getId(),
            'task_id' => $task->getId(),
            'operation' => $operation
        ]);
    }

    /**
     * Selectively invalidate analytics based on what changed
     */
    private function invalidateAnalyticsForTask($user, Task $task, string $operation): void
    {
        // Always invalidate overview and distributions
        $this->analyticsCache->invalidate($user, 'overview');
        $this->analyticsCache->invalidateDistributions($user);

        // If task is completed/uncompleted, invalidate time-based analytics
        if ($task->isCompleted() || $operation === 'remove') {
            $this->analyticsCache->invalidateTimeBased($user);
        }

        // Invalidate top tags if task has tags
        if (!$task->getTags()->isEmpty()) {
            $this->analyticsCache->invalidate($user, 'top_tags');
        }

        // Always invalidate insights (they depend on many factors)
        $this->analyticsCache->invalidate($user, 'insights');

        // Invalidate dashboard (aggregates everything)
        $pattern = $user->getId() . '_dashboard';
        $this->analyticsCache->invalidate($user, 'dashboard');
    }

    /**
     * Invalidate caches when tag changes
     */
    private function invalidateTagCache(Tag $tag, string $operation): void
    {
        $user = $tag->getUser();
        if (!$user) {
            return;
        }

        // Tags affect task lists (if tasks are filtered by tags)
        // We need to invalidate task lists that might include this tag
        $this->taskCache->invalidateTaskLists($user);

        // Tags affect analytics
        $this->analyticsCache->invalidate($user, 'top_tags');
        $this->analyticsCache->invalidate($user, 'insights');

        // Invalidate dashboard
        $this->analyticsCache->invalidate($user, 'dashboard');

        $this->logger->info('Tag cache invalidated', [
            'user_id' => $user->getId(),
            'tag_id' => $tag->getId(),
            'operation' => $operation
        ]);
    }

    /**
     * Manual cache clear for specific user (useful for admin operations)
     */
    public function clearUserCache($user): array
    {
        return [
            'tasks' => $this->taskCache->invalidateUserCache($user),
            'analytics' => $this->analyticsCache->invalidateAll($user)
        ];
    }

    /**
     * Manual warm-up for specific user (useful after login)
     */
    public function warmUpUserCache($user, callable $taskListCallback, callable $statsCallback, callable $dashboardCallback): void
    {
        // Warm up task caches
        $this->taskCache->warmUp($user, $taskListCallback, $statsCallback);

        // Warm up analytics dashboard
        $this->analyticsCache->warmUpDashboard($user, $dashboardCallback);
    }
}
