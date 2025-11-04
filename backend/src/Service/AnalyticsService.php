<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\Database\TaskRepository;
use App\Repository\Database\TagRepository;
use App\Service\Cache\AnalyticsCacheService;

/**
 * Analytics Service - Business logic for task analytics and insights
 * NOW WITH COMPLETE CACHING FOR ALL METHODS
 */
final class AnalyticsService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TagRepository $tagRepository,
        private readonly AnalyticsCacheService $analyticsCache
    ) {
    }

    /**
     * Get overview statistics - NOW CACHED
     */
    public function getOverview(User $user): array
    {
        return $this->analyticsCache->getOverview($user, function () use ($user) {
            return $this->computeOverview($user);
        });
    }

    /**
     * Get completion timeline data - NOW CACHED
     */
    public function getCompletionTimeline(User $user, int $days = 30): array
    {
        return $this->analyticsCache->getCompletionTimeline($user, $days, function () use ($user, $days) {
            $endDate = new \DateTimeImmutable();

            if ($days >= 365) {
                $startDate = $endDate->modify('-6 months');
            } else {
                $startDate = $endDate->modify("-{$days} days");
            }

            return $this->taskRepository->getCompletionTimelineData($user, $startDate, $endDate);
        });
    }

    /**
     * Get completion timeline data by custom date range - NOW CACHED
     */
    public function getCompletionTimelineByDateRange(User $user, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->analyticsCache->getCompletionTimelineByDateRange($user, $start, $end, function () use ($user, $start, $end) {
            return $this->taskRepository->getCompletionTimelineData($user, $start, $end);
        });
    }

    /**
     * Get status distribution - NOW CACHED
     */
    public function getStatusDistribution(User $user): array
    {
        return $this->analyticsCache->getStatusDistribution($user, function () use ($user) {
            $stats = $this->taskRepository->getUserTaskStatistics($user);

            return [
                'pending' => $stats['pending'] ?? 0,
                'in_progress' => $stats['in_progress'] ?? 0,
                'completed' => $stats['completed'] ?? 0,
                'cancelled' => $stats['cancelled'] ?? 0,
                'total' => $stats['total'] ?? 0
            ];
        });
    }

    /**
     * Get priority breakdown - NOW CACHED
     */
    public function getPriorityBreakdown(User $user): array
    {
        return $this->analyticsCache->getPriorityBreakdown($user, function () use ($user) {
            return $this->taskRepository->getPriorityBreakdown($user);
        });
    }

    /**
     * Get productivity heatmap data - NOW CACHED
     */
    public function getProductivityHeatmap(User $user, int $year): array
    {
        return $this->analyticsCache->getProductivityHeatmap($user, $year, function () use ($user, $year) {
            return $this->taskRepository->getProductivityHeatmap($user, $year);
        });
    }

    /**
     * Get weekday productivity - NOW CACHED
     */
    public function getWeekdayProductivity(User $user): array
    {
        return $this->analyticsCache->getWeekdayProductivity($user, function () use ($user) {
            return $this->taskRepository->getWeekdayProductivity($user);
        });
    }

    /**
     * Get top tags with statistics - NOW CACHED
     */
    public function getTopTags(User $user, int $limit = 5): array
    {
        return $this->analyticsCache->getTopTags($user, $limit, function () use ($user, $limit) {
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
        });
    }

    /**
     * Generate insights based on user data - NOW CACHED
     */
    public function generateInsights(User $user): array
    {
        return $this->analyticsCache->getInsights($user, function () use ($user) {
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
                    break;
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
        });
    }

    /**
     * Calculate current streak - OPTIMIZED & CACHED
     */
    private function calculateStreak(User $user): int
    {
        return $this->analyticsCache->getStreak($user, function () use ($user) {
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

            $completedDateMap = array_column($completedDates, 'completed_date', 'completed_date');
            $streak = 0;
            $currentDate = new \DateTimeImmutable('today');

            for ($i = 0; $i < 365; $i++) {
                $checkDate = $currentDate->modify("-{$i} days")->format('Y-m-d');

                if (isset($completedDateMap[$checkDate])) {
                    $streak++;
                } else {
                    break;
                }
            }

            return $streak;
        });
    }

    /**
     * Get complete dashboard data - FULLY CACHED
     */
    public function getDashboardData(User $user, array $params): array
    {
        return $this->analyticsCache->getDashboardData($user, $params, function () use ($user, $params) {
            return $this->computeDashboardData($user, $params);
        });
    }

    /**
     * Compute overview (extracted for clarity)
     */
    private function computeOverview(User $user): array
    {
        $stats = $this->taskRepository->getUserTaskStatistics($user);

        $thisWeekStart = new \DateTimeImmutable('monday this week');
        $thisWeekTasks = $this->taskRepository->findTasksCreatedBetween($user, $thisWeekStart, new \DateTimeImmutable());
        $lastWeekStart = $thisWeekStart->modify('-7 days');
        $lastWeekTasks = $this->taskRepository->findTasksCreatedBetween($user, $lastWeekStart, $thisWeekStart);
        $thisWeekCompleted = $this->taskRepository->findTasksCompletedBetween($user, $thisWeekStart, new \DateTimeImmutable());

        $avgCompletionTime = $this->taskRepository->getAverageCompletionTime($user);
        $currentStreak = $this->calculateStreak($user);
        $onTimeRate = $this->taskRepository->getOnTimeCompletionRate($user);
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
     * Compute dashboard data (extracted for caching)
     */
    private function computeDashboardData(User $user, array $params): array
    {
        $period = $params['period'] ?? 30;
        $dateFrom = $params['dateFrom'] ?? null;
        $dateTo = $params['dateTo'] ?? null;
        $year = $params['year'] ?? (int)date('Y');

        // Get timeline data
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

        // Get overview (will use its own cache)
        $overview = $this->getOverview($user);

        return [
            'overview' => $overview,
            'timeline' => $timelineData,
            'statusDistribution' => $this->getStatusDistribution($user),
            'priorityBreakdown' => $this->getPriorityBreakdown($user),
            'productivityHeatmap' => $this->getProductivityHeatmap($user, $year),
            'weekdayProductivity' => $this->getWeekdayProductivity($user),
            'topTags' => $this->getTopTags($user, 5),
            'insights' => $this->generateInsights($user),
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
