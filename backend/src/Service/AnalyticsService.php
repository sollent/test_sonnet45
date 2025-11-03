<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\Database\TaskRepository;
use App\Repository\Database\TagRepository;
use App\Service\Cache\AnalyticsCache;

/**
 * Analytics Service - Business logic for task analytics and insights
 */
final class AnalyticsService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TagRepository $tagRepository,
        private readonly AnalyticsCache $analyticsCache
    ) {
    }

    /**
     * Get overview statistics
     */
    public function getOverview(User $user): array
    {
        $stats = $this->taskRepository->getUserTaskStatistics($user);
        
        // Calculate this week stats
        $thisWeekStart = new \DateTimeImmutable('monday this week');
        $thisWeekTasks = $this->taskRepository->findTasksCreatedBetween($user, $thisWeekStart, new \DateTimeImmutable());
        $lastWeekStart = $thisWeekStart->modify('-7 days');
        $lastWeekTasks = $this->taskRepository->findTasksCreatedBetween($user, $lastWeekStart, $thisWeekStart);
        
        $thisWeekCompleted = $this->taskRepository->findTasksCompletedBetween($user, $thisWeekStart, new \DateTimeImmutable());
        
        // Calculate average completion time
        $avgCompletionTime = $this->taskRepository->getAverageCompletionTime($user);
        
        // Calculate current streak
        $currentStreak = $this->calculateStreak($user);
        
        // Calculate on-time completion rate
        $onTimeRate = $this->taskRepository->getOnTimeCompletionRate($user);
        
        // Find most productive day
        $mostProductiveDay = $this->taskRepository->getMostProductiveDay($user);
        
        return [
            'totalTasks' => $stats['total'],
            'completedThisWeek' => count($thisWeekCompleted),
            'weeklyChange' => count($thisWeekTasks) - count($lastWeekTasks),
            'weeklyChangePercent' => count($lastWeekTasks) > 0 
                ? round((count($thisWeekTasks) - count($lastWeekTasks)) / count($lastWeekTasks) * 100, 1)
                : 0,
            'averageCompletionTime' => $avgCompletionTime,
            'currentStreak' => $currentStreak,
            'onTimeCompletionRate' => $onTimeRate,
            'mostProductiveDay' => $mostProductiveDay,
            'pending' => $stats['pending'] ?? 0,
            'inProgress' => $stats['in_progress'] ?? 0,
            'completed' => $stats['completed'] ?? 0,
            'overdue' => $stats['overdue'] ?? 0
        ];
    }

    /**
     * Get completion timeline data
     */
    public function getCompletionTimeline(User $user, int $days = 30): array
    {
        $endDate = new \DateTimeImmutable();
        
        // Special case: "all_time" (365 days) - show only last 6 months
        if ($days >= 365) {
            $startDate = $endDate->modify('-6 months');
        } else {
            $startDate = $endDate->modify("-{$days} days");
        }
        
        $data = $this->taskRepository->getCompletionTimelineData($user, $startDate, $endDate);
        
        return $data;
    }

    /**
     * Get completion timeline data by custom date range
     */
    public function getCompletionTimelineByDateRange(User $user, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->taskRepository->getCompletionTimelineData($user, $start, $end);
    }

    /**
     * Get status distribution
     */
    public function getStatusDistribution(User $user): array
    {
        $stats = $this->taskRepository->getUserTaskStatistics($user);
        
        return [
            'pending' => $stats['pending'] ?? 0,
            'in_progress' => $stats['in_progress'] ?? 0,
            'completed' => $stats['completed'] ?? 0,
            'cancelled' => $stats['cancelled'] ?? 0,
            'total' => $stats['total'] ?? 0
        ];
    }

    /**
     * Get priority breakdown
     */
    public function getPriorityBreakdown(User $user): array
    {
        return $this->taskRepository->getPriorityBreakdown($user);
    }

    /**
     * Get productivity heatmap data
     */
    public function getProductivityHeatmap(User $user, int $year): array
    {
        return $this->taskRepository->getProductivityHeatmap($user, $year);
    }

    /**
     * Get weekday productivity
     */
    public function getWeekdayProductivity(User $user): array
    {
        return $this->taskRepository->getWeekdayProductivity($user);
    }

    /**
     * Get top tags with statistics
     */
    public function getTopTags(User $user, int $limit = 5): array
    {
        $tags = $this->tagRepository->getMostUsedTags($user, $limit);
        
        $result = [];
        foreach ($tags as $tag) {
            $tagStats = $this->taskRepository->getTagCompletionStats($user, $tag->getId());
            $result[] = [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
                'color' => $tag->getColor(),
                'count' => $tag->getUsageCount(),
                'completionRate' => $tagStats['completionRate'] ?? 0,
                'totalTasks' => $tagStats['total'] ?? 0,
                'completedTasks' => $tagStats['completed'] ?? 0
            ];
        }
        
        return $result;
    }

    /**
     * Generate insights based on user data
     */
    public function generateInsights(User $user): array
    {
        $insights = [];
        
        // Trend insight - weekly productivity
        $thisWeekStart = new \DateTimeImmutable('monday this week');
        $thisWeekTasks = $this->taskRepository->findTasksCompletedBetween($user, $thisWeekStart, new \DateTimeImmutable());
        $lastWeekStart = $thisWeekStart->modify('-7 days');
        $lastWeekTasks = $this->taskRepository->findTasksCompletedBetween($user, $lastWeekStart, $thisWeekStart);
        
        if (count($lastWeekTasks) > 0) {
            $changePercent = round((count($thisWeekTasks) - count($lastWeekTasks)) / count($lastWeekTasks) * 100);
            if ($changePercent > 10) {
                $insights[] = [
                    'type' => 'trend',
                    'icon' => 'pi-chart-line',
                    'message' => "Вы на {$changePercent}% продуктивнее чем на прошлой неделе!",
                    'sentiment' => 'positive'
                ];
            } elseif ($changePercent < -10) {
                $insights[] = [
                    'type' => 'trend',
                    'icon' => 'pi-exclamation-triangle',
                    'message' => "Продуктивность снизилась на " . abs($changePercent) . "%. Всё хорошо?",
                    'sentiment' => 'warning'
                ];
            }
        }
        
        // Best time insight
        $bestHour = $this->taskRepository->getMostProductiveHour($user);
        if ($bestHour !== null) {
            $insights[] = [
                'type' => 'time',
                'icon' => 'pi-clock',
                'message' => "Ваше лучшее время для задач: {$bestHour}:00-" . ($bestHour + 2) . ":00",
                'sentiment' => 'info'
            ];
        }
        
        // Tag completion rate insight
        $tags = $this->tagRepository->getMostUsedTags($user, 3);
        foreach ($tags as $tag) {
            $tagStats = $this->taskRepository->getTagCompletionStats($user, $tag->getId());
            if (isset($tagStats['completionRate']) && $tagStats['completionRate'] >= 85) {
                $insights[] = [
                    'type' => 'achievement',
                    'icon' => 'pi-check-circle',
                    'message' => "{$tagStats['completionRate']}% задач с тегом '{$tag->getName()}' выполняются в срок",
                    'sentiment' => 'positive'
                ];
                break; // Only one tag insight
            }
        }
        
        // Most productive day recommendation
        $mostProductiveDay = $this->taskRepository->getMostProductiveDay($user);
        if ($mostProductiveDay) {
            $insights[] = [
                'type' => 'recommendation',
                'icon' => 'pi-lightbulb',
                'message' => "Попробуйте планировать важные задачи на {$mostProductiveDay}",
                'sentiment' => 'info'
            ];
        }
        
        // Streak motivation
        $streak = $this->calculateStreak($user);
        if ($streak >= 3) {
            $insights[] = [
                'type' => 'streak',
                'icon' => 'pi-bolt',
                'message' => "Серия {$streak} дней! Продолжайте в том же духе! 🔥",
                'sentiment' => 'positive'
            ];
        }
        
        return $insights;
    }

    /**
     * Calculate current streak (consecutive days with completed tasks) - OPTIMIZED VERSION
     * Uses single SQL query instead of 365 individual queries
     */
    private function calculateStreak(User $user): int
    {
        // OPTIMIZATION: Single query to get all completed dates within last year
        $completedDates = $this->taskRepository->getEntityManager()->getConnection()->executeQuery(
            'SELECT DISTINCT DATE(t.completed_at) as completed_date
             FROM task t
             WHERE t.user_id = :user_id
               AND t.parent_task_id IS NULL
               AND t.completed_at IS NOT NULL
               AND t.completed_at >= :one_year_ago
             ORDER BY DATE(t.completed_at) DESC',
            [
                'user_id' => $user->getId(),
                'one_year_ago' => (new \DateTimeImmutable())->modify('-365 days')->format('Y-m-d')
            ]
        )->fetchAllAssociative();

        if (empty($completedDates)) {
            return 0;
        }

        // OPTIMIZATION: Convert to date strings for easy lookup
        $completedDateMap = array_column($completedDates, 'completed_date', 'completed_date');

        $streak = 0;
        $currentDate = new \DateTimeImmutable('today');

        // OPTIMIZATION: Check consecutive days using the pre-loaded data
        for ($i = 0; $i < 365; $i++) {
            $checkDate = $currentDate->modify("-{$i} days")->format('Y-m-d');

            if (isset($completedDateMap[$checkDate])) {
                $streak++;
            } else {
                break; // Streak broken
            }
        }

        return $streak;
    }

    /**
     * Get complete dashboard data in a single optimized request
     * Combines all analytics data to minimize database queries
     * Uses caching for improved performance
     */
    public function getDashboardData(User $user, array $params): array
    {
        $period = $params['period'] ?? 30;
        $dateFrom = $params['dateFrom'];
        $dateTo = $params['dateTo'];
        $year = $params['year'] ?? (int)date('Y');

        // Create cache key based on user and parameters
        $cacheKey = sprintf(
            'analytics_dashboard_%d_%s_%s_%s_%d',
            $user->getId(),
            $period,
            $dateFrom ?: 'null',
            $dateTo ?: 'null',
            $year
        );

        return $this->analyticsCache->get($cacheKey, function () use ($user, $period, $dateFrom, $dateTo, $year) {
            return $this->computeDashboardData($user, $period, $dateFrom, $dateTo, $year);
        }, 900); // Cache for 15 minutes
    }

    /**
     * Compute dashboard data (extracted for caching)
     */
    private function computeDashboardData(User $user, int $period, ?string $dateFrom, ?string $dateTo, int $year): array
    {
        // OPTIMIZATION: Get core statistics once and reuse
        $stats = $this->taskRepository->getUserTaskStatistics($user);

        // OPTIMIZATION: Get this week's data once
        $thisWeekStart = new \DateTimeImmutable('monday this week');
        $thisWeekTasks = $this->taskRepository->findTasksCreatedBetween($user, $thisWeekStart, new \DateTimeImmutable());
        $lastWeekStart = $thisWeekStart->modify('-7 days');
        $lastWeekTasks = $this->taskRepository->findTasksCreatedBetween($user, $lastWeekStart, $thisWeekStart);
        $thisWeekCompleted = $this->taskRepository->findTasksCompletedBetween($user, $thisWeekStart, new \DateTimeImmutable());

        // OPTIMIZATION: Calculate common metrics once
        $avgCompletionTime = $this->taskRepository->getAverageCompletionTime($user);
        $currentStreak = $this->calculateStreak($user);
        $onTimeRate = $this->taskRepository->getOnTimeCompletionRate($user);
        $mostProductiveDay = $this->taskRepository->getMostProductiveDay($user);

        // OPTIMIZATION: Get timeline data (can be expensive)
        if ($dateFrom && $dateTo) {
            $timelineStartDate = new \DateTimeImmutable($dateFrom);
            $timelineEnd = new \DateTimeImmutable($dateTo);
        } else {
            $timelineEnd = new \DateTimeImmutable();
            if ($period >= 365) {
                $timelineStartDate = $timelineEnd->modify('-6 months');
            } else {
                $timelineStartDate = $timelineEnd->modify("-{$period} days");
            }
        }
        $timelineData = $this->taskRepository->getCompletionTimelineData($user, $timelineStartDate, $timelineEnd);

        // OPTIMIZATION: Get all required data in parallel-like execution
        $priorityBreakdown = $this->taskRepository->getPriorityBreakdown($user);
        $productivityHeatmap = $this->taskRepository->getProductivityHeatmap($user, $year);
        $weekdayProductivity = $this->taskRepository->getWeekdayProductivity($user);
        $topTags = $this->getTopTags($user, 5);
        $insights = $this->generateInsights($user);

        return [
            // Overview data
            'overview' => [
                'totalTasks' => $stats['total'],
                'completedThisWeek' => count($thisWeekCompleted),
                'weeklyChange' => count($thisWeekTasks) - count($lastWeekTasks),
                'weeklyChangePercent' => count($lastWeekTasks) > 0
                    ? round((count($thisWeekTasks) - count($lastWeekTasks)) / count($lastWeekTasks) * 100, 1)
                    : 0,
                'averageCompletionTime' => $avgCompletionTime,
                'currentStreak' => $currentStreak,
                'onTimeCompletionRate' => $onTimeRate,
                'mostProductiveDay' => $mostProductiveDay,
                'pending' => $stats['pending'] ?? 0,
                'inProgress' => $stats['in_progress'] ?? 0,
                'completed' => $stats['completed'] ?? 0,
                'overdue' => $stats['overdue'] ?? 0
            ],

            // Timeline data
            'timeline' => $timelineData,

            // Status distribution
            'statusDistribution' => [
                'pending' => $stats['pending'] ?? 0,
                'in_progress' => $stats['in_progress'] ?? 0,
                'completed' => $stats['completed'] ?? 0,
                'cancelled' => $stats['cancelled'] ?? 0,
                'total' => $stats['total'] ?? 0
            ],

            // Priority breakdown
            'priorityBreakdown' => $priorityBreakdown,

            // Productivity heatmap
            'productivityHeatmap' => $productivityHeatmap,

            // Weekday productivity
            'weekdayProductivity' => $weekdayProductivity,

            // Top tags
            'topTags' => $topTags,

            // Insights
            'insights' => $insights,

            // Metadata
            'meta' => [
                'period' => $period,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'year' => $year,
                'generatedAt' => (new \DateTimeImmutable())->format('c'),
                'cached' => true
            ]
        ];
    }
}

