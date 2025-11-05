<?php

declare(strict_types=1);

namespace App\Service\Cache;

use Psr\Log\LoggerInterface;

/**
 * Simple Redis Cache Service - использует НАТИВНЫЙ Redis без Symfony обёрток
 * Гарантирует прямую запись в Redis
 */
final class SimpleRedisCache
{
    private \Redis $redis;
    private string $prefix;
    private int $defaultTtl;

    public function __construct(
        private readonly LoggerInterface $logger,
        string $redisUrl = 'redis://redis:6379',
        string $prefix = 'app:',
        int $defaultTtl = 900
    ) {
        $this->prefix = $prefix;
        $this->defaultTtl = $defaultTtl;
        $this->redis = $this->connect($redisUrl);
    }

    private function connect(string $url): \Redis
    {
        // Parse Redis URL
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? 'redis';
        $port = $parsed['port'] ?? 6379;

        $redis = new \Redis();

        try {
            $redis->connect($host, $port);
            $this->logger->info('Connected to Redis', ['host' => $host, 'port' => $port]);
        } catch (\RedisException $e) {
            $this->logger->error('Failed to connect to Redis', [
                'error' => $e->getMessage(),
                'host' => $host,
                'port' => $port
            ]);
            throw $e;
        }

        return $redis;
    }

    /**
     * Get value from cache or compute it
     */
    public function get(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $fullKey = $this->prefix . $key;

        try {
            // Try to get from Redis
            $value = $this->redis->get($fullKey);

            if ($value !== false) {
                $this->logger->debug('Cache HIT', ['key' => $key]);
                return unserialize($value);
            }

            // Cache MISS - compute value
            $this->logger->debug('Cache MISS', ['key' => $key]);
            $computedValue = $callback();

            // Save to Redis
            $serialized = serialize($computedValue);
            $ttlToUse = $ttl ?? $this->defaultTtl;

            $result = $this->redis->setex($fullKey, $ttlToUse, $serialized);

            $this->logger->info('Saved to Redis', [
                'key' => $key,
                'ttl' => $ttlToUse,
                'success' => $result
            ]);

            return $computedValue;
        } catch (\Throwable $e) {
            $this->logger->error('Cache error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            // Fallback to direct computation
            return $callback();
        }
    }

    /**
     * Set value in cache
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $fullKey = $this->prefix . $key;

        try {
            $serialized = serialize($value);
            $ttlToUse = $ttl ?? $this->defaultTtl;

            $result = $this->redis->setex($fullKey, $ttlToUse, $serialized);

            $this->logger->info('Set cache value', [
                'key' => $key,
                'ttl' => $ttlToUse,
                'success' => $result
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to set cache', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Delete value from cache
     */
    public function delete(string $key): bool
    {
        $fullKey = $this->prefix . $key;

        try {
            $result = $this->redis->del($fullKey) > 0;

            $this->logger->info('Deleted from cache', [
                'key' => $key,
                'success' => $result
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to delete from cache', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Delete by pattern (e.g., "user:123:*")
     */
    public function deleteByPattern(string $pattern): int
    {
        $fullPattern = $this->prefix . $pattern;

        try {
            $keys = $this->redis->keys($fullPattern);

            if (empty($keys)) {
                return 0;
            }

            $deleted = $this->redis->del($keys);

            $this->logger->info('Deleted by pattern', [
                'pattern' => $pattern,
                'deleted' => $deleted
            ]);

            return $deleted;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to delete by pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        $fullKey = $this->prefix . $key;

        try {
            return $this->redis->exists($fullKey) > 0;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to check key existence', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Clear all keys with our prefix
     */
    public function clear(): int
    {
        return $this->deleteByPattern('*');
    }

    /**
     * Get Redis instance (for advanced operations)
     */
    public function getRedis(): \Redis
    {
        return $this->redis;
    }

    /**
     * Get cache prefix (for pattern matching)
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
}