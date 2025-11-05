<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Task;
use App\Entity\Tag;
use App\Service\Cache\AnalyticsCache;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Cache\CacheItemPoolInterface;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
final class CacheInvalidationListener
{
    public function __construct(
        private readonly CacheItemPoolInterface $resultCache,
        private readonly AnalyticsCache $analyticsCache,
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
        if ($entity instanceof Task || $entity instanceof Tag) {
            // Clear Doctrine result cache
            try {
                $this->resultCache->clear();
            } catch (\Throwable $e) {
                // Ignore cache clearing errors
            }

            // Clear specific analytics cache keys
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
        }
    }
}
