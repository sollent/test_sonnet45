<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Task;
use App\Entity\Tag;
use App\Entity\User;
use App\Service\Cache\AnalyticsCache;
use App\Service\Cache\TaskCache;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Cache\CacheItemPoolInterface;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
final readonly class CacheInvalidationListener
{
    public function __construct(
        private CacheItemPoolInterface $resultCache,
        private AnalyticsCache $analyticsCache,
        private TaskCache $taskCache,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->invalidateAnalyticsCache($args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->invalidateAnalyticsCache($args->getObject());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->invalidateAnalyticsCache($args->getObject());
    }

    private function invalidateAnalyticsCache(object $entity): void
    {
        // Invalidate cache only for Task and Tag entities that affect analytics
        if ($entity instanceof Task) {
            $this->invalidateTaskCaches($entity);
        } elseif ($entity instanceof Tag) {
            $this->invalidateTagCaches($entity);
        }
    }

    private function invalidateTaskCaches(Task $task): void
    {
        // Clear Doctrine result cache
        try {
            $this->resultCache->clear();
        } catch (\Throwable $e) {
            // Ignore cache clearing errors
        }

        // Clear analytics cache
        $cacheKeys = [
            'analytics_dashboard',
            'analytics_overview',
            'analytics_timeline',
            'analytics_status_distribution',
            'analytics_priority_breakdown',
            'analytics_productivity_heatmap',
            'analytics_weekday_productivity',
            'analytics_top_tags',
            'analytics_insights'
        ];

        foreach ($cacheKeys as $key) {
            try {
                $this->analyticsCache->delete($key);
            } catch (\Throwable $e) {
                // Ignore cache deletion errors
            }
        }

        // Clear task caches for the user
        if ($task->getUser()) {
            // Delete specific task cache first
            $this->taskCache->deleteTaskCache($task->getUser(), $task->getId());

            // Then invalidate all user caches
            $this->taskCache->invalidateUserCache($task->getUser());
        }
    }

    private function invalidateTagCaches(Tag $tag): void
    {
        // Clear Doctrine result cache
        try {
            $this->resultCache->clear();
        } catch (\Throwable $e) {
            // Ignore cache clearing errors
        }

        // Clear analytics cache (tags affect analytics)
        $cacheKeys = [
            'analytics_dashboard',
            'analytics_top_tags'
        ];

        foreach ($cacheKeys as $key) {
            try {
                $this->analyticsCache->delete($key);
            } catch (\Throwable $e) {
                // Ignore cache deletion errors
            }
        }

        // Clear task caches for users who have this tag
        // Note: This is complex to implement efficiently, so we'll clear caches on tag changes
        // In production, you might want to track which users use which tags
        if ($tag->getUser()) {
            $this->taskCache->invalidateUserCache($tag->getUser());
        }
    }
}
