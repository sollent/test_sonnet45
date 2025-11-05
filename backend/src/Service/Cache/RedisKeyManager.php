<?php

declare(strict_types=1);

namespace App\Service\Cache;

use App\Entity\User;
use App\Service\Cache\Interface\CacheKeyManagerInterface;

/**
 * Redis Key Manager
 * Implements professional cache key generation strategy
 * Pattern: app:namespace:params:hash
 */
final class RedisKeyManager implements CacheKeyManagerInterface
{
    private const KEY_SEPARATOR = ':';
    private const APP_PREFIX = 'app';

    public function __construct(
        private readonly string $environment = 'prod'
    ) {
    }

    public function buildKey(string $namespace, array $params): string
    {
        $parts = [
            self::APP_PREFIX,
            $this->environment,
            $namespace
        ];

        // Sort params for consistent keys
        ksort($params);

        // Add params to key
        foreach ($params as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                $value = md5(json_encode($value));
            } elseif (is_object($value)) {
                if (method_exists($value, 'getId')) {
                    $value = $value->getId();
                } else {
                    $value = spl_object_hash($value);
                }
            }

            $parts[] = "{$key}_{$value}";
        }

        return implode(self::KEY_SEPARATOR, $parts);
    }

    public function buildPattern(string $namespace, array $params): string
    {
        $parts = [
            self::APP_PREFIX,
            $this->environment,
            $namespace
        ];

        foreach ($params as $key => $value) {
            if ($value === '*') {
                $parts[] = '*';
                break; // Everything after is also wildcard
            }

            if (is_array($value)) {
                $value = md5(json_encode($value));
            }

            $parts[] = "{$key}_{$value}";
        }

        if (end($parts) !== '*') {
            $parts[] = '*';
        }

        return implode(self::KEY_SEPARATOR, $parts);
    }

    public function buildUserKey(User $user, string $type, array $params = []): string
    {
        return $this->buildKey("user_{$type}", array_merge([
            'uid' => $user->getId()
        ], $params));
    }

    public function buildUserPattern(User $user, ?string $type = null): string
    {
        if ($type === null) {
            // Match all user keys: app:prod:user_*:uid_1*
            return implode(self::KEY_SEPARATOR, [
                self::APP_PREFIX,
                $this->environment,
                'user_*',
                "uid_{$user->getId()}*"
            ]);
        }

        // Match specific type: app:prod:user_tasks_list:*:uid_1* or app:prod:user_tasks_list:*uid_1*
        return implode(self::KEY_SEPARATOR, [
            self::APP_PREFIX,
            $this->environment,
            "user_{$type}",
            '*'
        ]) . "*uid_{$user->getId()}*";
    }

    public function generateTags(string $namespace, array $params): array
    {
        $tags = [$namespace];

        // Add user tag if present
        if (isset($params['user_id'])) {
            $tags[] = "user:{$params['user_id']}";
        }

        // Add entity tags
        if (isset($params['task_id'])) {
            $tags[] = "task:{$params['task_id']}";
        }

        if (isset($params['tag_id'])) {
            $tags[] = "tag:{$params['tag_id']}";
        }

        return $tags;
    }

    /**
     * Build key for task list cache
     */
    public function buildTaskListKey(User $user, array $filters): string
    {
        return $this->buildUserKey($user, 'tasks_list', [
            'filters' => $filters
        ]);
    }

    /**
     * Build key for single task cache
     */
    public function buildTaskKey(User $user, int $taskId): string
    {
        return $this->buildUserKey($user, 'task', [
            'tid' => $taskId
        ]);
    }

    /**
     * Build key for task statistics
     */
    public function buildTaskStatsKey(User $user): string
    {
        return $this->buildUserKey($user, 'task_stats');
    }

    /**
     * Build key for analytics
     */
    public function buildAnalyticsKey(User $user, string $type, array $params = []): string
    {
        return $this->buildUserKey($user, "analytics_{$type}", $params);
    }

    /**
     * Build pattern for all user tasks
     */
    public function buildAllUserTasksPattern(User $user): string
    {
        return $this->buildUserPattern($user, 'task*');
    }

    /**
     * Build pattern for all user analytics
     */
    public function buildAllUserAnalyticsPattern(User $user): string
    {
        return $this->buildUserPattern($user, 'analytics*');
    }
}
