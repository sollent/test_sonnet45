<?php

declare(strict_types=1);

namespace App\Service\Cache\Interface;

/**
 * Cache Service Interface
 * Defines contract for all cache implementations
 */
interface CacheServiceInterface
{
    /**
     * Get item from cache or compute it using callback
     *
     * @param string $key Cache key
     * @param callable $callback Function to compute value if not cached
     * @param int|null $ttl Time to live in seconds (null = default)
     * @return mixed Cached or computed value
     */
    public function get(string $key, callable $callback, ?int $ttl = null): mixed;

    /**
     * Set cache item
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl Time to live in seconds
     * @return bool Success status
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Delete cache item by key
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete(string $key): bool;

    /**
     * Delete multiple cache items by pattern
     *
     * @param string $pattern Key pattern (e.g., 'user:*', 'tasks:123:*')
     * @return int Number of deleted keys
     */
    public function deleteByPattern(string $pattern): int;

    /**
     * Delete cache items by tags
     *
     * @param array<string> $tags Cache tags
     * @return int Number of deleted keys
     */
    public function deleteByTags(array $tags): int;

    /**
     * Check if cache key exists
     *
     * @param string $key Cache key
     * @return bool Existence status
     */
    public function has(string $key): bool;

    /**
     * Clear all cache
     *
     * @return bool Success status
     */
    public function clear(): bool;

    /**
     * Get cache statistics
     *
     * @return array{hits: int, misses: int, size: int, keys: int} Cache stats
     */
    public function getStats(): array;
}
