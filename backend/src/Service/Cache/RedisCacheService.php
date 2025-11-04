<?php

declare(strict_types=1);

namespace App\Service\Cache;

use App\Service\Cache\Interface\CacheServiceInterface;
use App\Service\Cache\Interface\CacheKeyManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Professional Redis Cache Service
 * Implements native Redis operations with Symfony Cache fallback
 */
final class RedisCacheService implements CacheServiceInterface
{
    private \Redis|\RedisCluster|null $redis = null;
    private int $hits = 0;
    private int $misses = 0;

    public function __construct(
        private readonly RedisAdapter $redisAdapter,
        private readonly CacheKeyManagerInterface $keyManager,
        private readonly LoggerInterface $logger,
        private readonly int $defaultTtl = 900,
    ) {
        $this->initializeRedisConnection();
    }

    /**
     * Initialize native Redis connection for pattern operations
     */
    private function initializeRedisConnection(): void
    {
        try {
            // Get native Redis connection from adapter
            $this->redis = $this->redisAdapter->getConnection();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to initialize Redis connection', [
                'error' => $e->getMessage()
            ]);
            $this->redis = null;
        }
    }

    public function get(string $key, callable $callback, ?int $ttl = null): mixed
    {
        try {
            return $this->redisAdapter->get($key, function (ItemInterface $item) use ($callback, $ttl) {
                $item->expiresAfter($ttl ?? $this->defaultTtl);
                $this->misses++;
                return $callback();
            });
        } catch (\Throwable $e) {
            $this->logger->error('Cache get failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            // Fallback to direct computation
            return $callback();
        }
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        try {
            $item = $this->redisAdapter->getItem($key);
            $item->set($value);
            $item->expiresAfter($ttl ?? $this->defaultTtl);

            return $this->redisAdapter->save($item);
        } catch (\Throwable $e) {
            $this->logger->error('Cache set failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function delete(string $key): bool
    {
        try {
            return $this->redisAdapter->delete($key);
        } catch (\Throwable $e) {
            $this->logger->error('Cache delete failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function deleteByPattern(string $pattern): int
    {
        if ($this->redis === null) {
            $this->logger->warning('Native Redis not available for pattern deletion');
            return 0;
        }

        try {
            $deletedCount = 0;
            $iterator = null;

            // Use SCAN for efficient pattern matching
            while (false !== ($keys = $this->redis->scan($iterator, $pattern, 100))) {
                if (!empty($keys)) {
                    $deleted = $this->redis->del($keys);
                    $deletedCount += is_int($deleted) ? $deleted : count($keys);
                }
            }

            $this->logger->info('Cache pattern deletion completed', [
                'pattern' => $pattern,
                'deleted' => $deletedCount
            ]);

            return $deletedCount;
        } catch (\Throwable $e) {
            $this->logger->error('Pattern deletion failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    public function deleteByTags(array $tags): int
    {
        try {
            return $this->redisAdapter->invalidateTags($tags);
        } catch (\Throwable $e) {
            $this->logger->error('Tag invalidation failed', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    public function has(string $key): bool
    {
        try {
            return $this->redisAdapter->hasItem($key);
        } catch (\Throwable $e) {
            $this->logger->error('Cache has check failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function clear(): bool
    {
        try {
            $result = $this->redisAdapter->clear();

            if ($result) {
                $this->logger->info('Cache cleared successfully');
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Cache clear failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getStats(): array
    {
        $stats = [
            'hits' => $this->hits,
            'misses' => $this->misses,
            'size' => 0,
            'keys' => 0
        ];

        if ($this->redis === null) {
            return $stats;
        }

        try {
            $info = $this->redis->info('stats');
            $stats['keys'] = $info['db0'] ?? 0;

            $memory = $this->redis->info('memory');
            $stats['size'] = $memory['used_memory'] ?? 0;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to get cache stats', [
                'error' => $e->getMessage()
            ]);
        }

        return $stats;
    }

    /**
     * Get multiple keys at once (batch operation)
     *
     * @param array<string> $keys
     * @return array<string, mixed>
     */
    public function getMultiple(array $keys): array
    {
        try {
            $items = $this->redisAdapter->getItems($keys);
            $results = [];

            foreach ($items as $key => $item) {
                if ($item->isHit()) {
                    $results[$key] = $item->get();
                    $this->hits++;
                } else {
                    $this->misses++;
                }
            }

            return $results;
        } catch (\Throwable $e) {
            $this->logger->error('Batch get failed', [
                'keys' => $keys,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Set multiple keys at once (batch operation)
     *
     * @param array<string, mixed> $values
     * @param int|null $ttl
     * @return bool
     */
    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        try {
            foreach ($values as $key => $value) {
                $item = $this->redisAdapter->getItem($key);
                $item->set($value);
                $item->expiresAfter($ttl ?? $this->defaultTtl);
                $this->redisAdapter->saveDeferred($item);
            }

            return $this->redisAdapter->commit();
        } catch (\Throwable $e) {
            $this->logger->error('Batch set failed', [
                'count' => count($values),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Increment counter in cache
     */
    public function increment(string $key, int $value = 1): int|false
    {
        if ($this->redis === null) {
            return false;
        }

        try {
            return $this->redis->incrBy($key, $value);
        } catch (\Throwable $e) {
            $this->logger->error('Increment failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get TTL for key
     */
    public function getTtl(string $key): int
    {
        if ($this->redis === null) {
            return -1;
        }

        try {
            return $this->redis->ttl($key);
        } catch (\Throwable $e) {
            $this->logger->error('Get TTL failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return -1;
        }
    }

    /**
     * Touch key to refresh TTL
     */
    public function touch(string $key, ?int $ttl = null): bool
    {
        if ($this->redis === null) {
            return false;
        }

        try {
            return $this->redis->expire($key, $ttl ?? $this->defaultTtl);
        } catch (\Throwable $e) {
            $this->logger->error('Touch failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
