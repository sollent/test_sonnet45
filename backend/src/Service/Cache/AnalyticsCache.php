<?php

declare(strict_types=1);

namespace App\Service\Cache;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class AnalyticsCache
{
    public function __construct(
        private CacheInterface $cache
    ) {
    }

    /**
     * Get cached analytics data or compute and cache it
     */
    public function get(string $key, callable $callback, int $ttl = 900): mixed
    {
        return $this->cache->get($key, function (ItemInterface $item) use ($callback, $ttl) {
            $item->expiresAfter($ttl);
            return $callback();
        });
    }

    /**
     * Delete specific cache key
     */
    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }

    /**
     * Clear all analytics cache
     */
    public function clear(): bool
    {
        return $this->cache->clear();
    }

    /**
     * Check if cache key exists
     */
    public function has(string $key): bool
    {
        return $this->cache->hasItem($key);
    }
}
