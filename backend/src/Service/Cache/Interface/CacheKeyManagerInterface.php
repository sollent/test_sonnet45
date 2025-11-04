<?php

declare(strict_types=1);

namespace App\Service\Cache\Interface;

use App\Entity\User;

/**
 * Cache Key Manager Interface
 * Centralizes cache key generation strategy
 */
interface CacheKeyManagerInterface
{
    /**
     * Build cache key with namespace and parameters
     *
     * @param string $namespace Cache namespace (e.g., 'tasks', 'analytics')
     * @param array<string, mixed> $params Key parameters
     * @return string Formatted cache key
     */
    public function buildKey(string $namespace, array $params): string;

    /**
     * Build pattern for key matching
     *
     * @param string $namespace Cache namespace
     * @param array<string, mixed> $params Pattern parameters (use '*' for wildcards)
     * @return string Key pattern
     */
    public function buildPattern(string $namespace, array $params): string;

    /**
     * Build user-specific cache key
     *
     * @param User $user User entity
     * @param string $type Key type (e.g., 'tasks', 'stats')
     * @param array<string, mixed> $params Additional parameters
     * @return string User cache key
     */
    public function buildUserKey(User $user, string $type, array $params = []): string;

    /**
     * Build user pattern for invalidation
     *
     * @param User $user User entity
     * @param string|null $type Optional key type filter
     * @return string User key pattern
     */
    public function buildUserPattern(User $user, ?string $type = null): string;

    /**
     * Generate cache tags for grouped invalidation
     *
     * @param string $namespace Namespace
     * @param array<string, mixed> $params Tag parameters
     * @return array<string> Cache tags
     */
    public function generateTags(string $namespace, array $params): array;
}
