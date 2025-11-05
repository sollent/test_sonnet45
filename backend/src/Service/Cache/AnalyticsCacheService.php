<?php

declare(strict_types=1);

namespace App\Service\Cache;

use App\Entity\User;

/**
 * Professional Analytics Cache Service
 * Handles all analytics-related caching with Redis using SimpleRedisCache
 * Caches ALL analytics methods, not just dashboard
 */
final readonly class AnalyticsCacheService
{
    // TTL constants (in seconds)
    private const TTL_OVERVIEW = 600;                // 10 minutes
    private const TTL_TIMELINE = 900;                // 15 minutes
    private const TTL_DISTRIBUTION = 600;            // 10 minutes
    private const TTL_PRIORITY_BREAKDOWN = 600;      // 10 minutes
    private const TTL_HEATMAP = 1800;                // 30 minutes (expensive query)
    private const TTL_WEEKDAY = 900;                 // 15 minutes
    private const TTL_TOP_TAGS = 600;                // 10 minutes
    private const TTL_INSIGHTS = 300;                // 5 minutes (more dynamic)
    private const TTL_DASHBOARD = 900;               // 15 minutes
    private const TTL_STREAK = 300;                  // 5 minutes

    public function __construct(
        private SimpleRedisCache $cacheService,
        private RedisKeyManager $keyManager,
    ) {
    }

    /**
     * Get or compute analytics overview
     */
    public function getOverview(User $user, callable $callback): mixed
    {
        $key = $this->keyManager->buildAnalyticsKey($user, 'overview');

        return $this->cacheService->get($key, $callback, self::TTL_OVERVIEW);
    }

    /**
     * Get or compute completion timeline
     */
    public function getCompletionTimeline(User $user, int $days, callable $callback): mixed
    {
        $key = $this->keyManager->buildAnalyticsKey($user, 'timeline', ['days' => $days]);

        return $this->cacheService->get($key, $callback, self::TTL_TIMELINE);
    }

    /**
     * Get or compute completion timeline by date range
     */
    public function getCompletionTimelineByDateRange(
        User $user,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        callable $callback
    ): mixed {
        $key = $this->keyManager->buildAnalyticsKey($user, 'timeline_range', [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d')
        ]);

        return $this->cacheService->get($key, $callback, self::TTL_TIMELINE);
    }

    /**
     * Get or compute status distribution
     */
    public function getStatusDistribution(User $user, callable $callback): mixed
    {
        $key = $this->keyManager->buildAnalyticsKey($user, 'status_distribution');

        return $this->cacheService->get($key, $callback, self::TTL_DISTRIBUTION);
    }

    /**
     * Get or compute priority breakdown
     */
    public function getPriorityBreakdown(User $user, callable $callback): mixed
    {
        $key = $this->keyManager->buildAnalyticsKey($user, 'priority_breakdown');

        return $this->cacheService->get($key, $callback, self::TTL_PRIORITY_BREAKDOWN);
    }

    /**
     * Get or compute productivity heatmap
     */
    public function getProductivityHeatmap(User $user, int $year, callable $callback): mixed
    {
        $key = $this->keyManager->buildAnalyticsKey($user, 'heatmap', ['year' => $year]);

        return $this->cacheService->get($key, $callback, self::TTL_HEATMAP);
    }

    /**
     * Get or compute weekday productivity
     */
    public function getWeekdayProductivity(User $user, callable $callback): mixed
    {
        $key = $this->keyManager->buildAnalyticsKey($user, 'weekday_productivity');

        return $this->cacheService->get($key, $callback, self::TTL_WEEKDAY);
    }

    /**
     * Get or compute top tags
     */
    public function getTopTags(User $user, int $limit, callable $callback): mixed
    {
        $key = $this->keyManager->buildAnalyticsKey($user, 'top_tags', ['limit' => $limit]);

        return $this->cacheService->get($key, $callback, self::TTL_TOP_TAGS);
    }

    /**
     * Get or compute insights
     */
    public function getInsights(User $user, callable $callback): mixed
    {
        $key = $this->keyManager->buildAnalyticsKey($user, 'insights');

        return $this->cacheService->get($key, $callback, self::TTL_INSIGHTS);
    }

    /**
     * Get or compute complete dashboard data
     */
    public function getDashboardData(User $user, array $params, callable $callback): mixed
    {
        $key = $this->keyManager->buildAnalyticsKey($user, 'dashboard', [
            'period' => $params['period'] ?? 30,
            'dateFrom' => $params['dateFrom'] ?? 'null',
            'dateTo' => $params['dateTo'] ?? 'null',
            'year' => $params['year'] ?? date('Y')
        ]);

        return $this->cacheService->get($key, $callback, self::TTL_DASHBOARD);
    }

    /**
     * Get or compute streak
     */
    public function getStreak(User $user, callable $callback): mixed
    {
        $key = $this->keyManager->buildAnalyticsKey($user, 'streak');

        return $this->cacheService->get($key, $callback, self::TTL_STREAK);
    }

    /**
     * Invalidate ALL analytics cache for user
     */
    public function invalidateAll(User $user): int
    {
        $pattern = $this->keyManager->buildAllUserAnalyticsPattern($user);

        return $this->cacheService->deleteByPattern($pattern);
    }

    /**
     * Invalidate specific analytics cache
     * For types with parameters (like dashboard), uses pattern matching
     */
    public function invalidate(User $user, string $type): bool|int
    {
        // Dashboard and other parametrized types should use pattern matching
        $typesWithParams = ['dashboard', 'heatmap', 'timeline', 'timeline_range', 'top_tags'];

        if (in_array($type, $typesWithParams, true)) {
            // Use pattern to delete all variations
            $pattern = $this->keyManager->buildUserPattern($user, "analytics_{$type}");
            return $this->cacheService->deleteByPattern($pattern);
        }

        // Simple types use exact key
        $key = $this->keyManager->buildAnalyticsKey($user, $type);
        return $this->cacheService->delete($key);
    }

    /**
     * Invalidate time-based caches (timeline, heatmap, etc.)
     */
    public function invalidateTimeBased(User $user): int
    {
        $types = ['timeline', 'timeline_range', 'heatmap', 'weekday_productivity', 'insights', 'dashboard'];
        $deleted = 0;

        foreach ($types as $type) {
            $pattern = $this->keyManager->buildUserPattern($user, "analytics_{$type}");
            $deleted += $this->cacheService->deleteByPattern($pattern);
        }

        return $deleted;
    }

    /**
     * Invalidate distribution caches (status, priority)
     */
    public function invalidateDistributions(User $user): int
    {
        $types = ['status_distribution', 'priority_breakdown'];
        $deleted = 0;

        foreach ($types as $type) {
            $deleted += $this->invalidate($user, $type) ? 1 : 0;
        }

        return $deleted;
    }

    /**
     * Warm up cache for dashboard
     */
    public function warmUpDashboard(User $user, callable $callback, array $params = []): void
    {
        // Warm up dashboard with default params
        $this->getDashboardData($user, $params, $callback);

        // Warm up overview
        $this->getOverview($user, fn() => []);

        // Warm up current year heatmap
        $this->getProductivityHeatmap($user, (int)date('Y'), fn() => []);
    }

    /**
     * Get cache stats for user
     */
    public function getUserCacheStats(User $user): array
    {
        $pattern = $this->keyManager->buildAllUserAnalyticsPattern($user);
        // This would require custom implementation to count keys by pattern
        // For now, return basic stats
        return [
            'pattern' => $pattern,
            'types' => [
                'overview', 'timeline', 'status_distribution',
                'priority_breakdown', 'heatmap', 'weekday_productivity',
                'top_tags', 'insights', 'dashboard'
            ]
        ];
    }

    /**
     * Get cache key for debugging
     */
    public function getCacheKey(User $user, string $type, array $params = []): string
    {
        return $this->keyManager->buildAnalyticsKey($user, $type, $params);
    }
}
