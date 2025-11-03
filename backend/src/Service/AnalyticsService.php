<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\Database\TaskRepository;
use App\Repository\Database\TagRepository;

/**
 * Analytics Service - Business logic for task analytics and insights
 */
final class AnalyticsService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TagRepository $tagRepository
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
     * Calculate current streak (consecutive days with completed tasks)
     */
    private function calculateStreak(User $user): int
    {
        $streak = 0;
        $currentDate = new \DateTimeImmutable('today');
        
        for ($i = 0; $i < 365; $i++) {
            $dayStart = $currentDate->modify("-{$i} days")->setTime(0, 0);
            $dayEnd = $dayStart->setTime(23, 59, 59);
            
            $completedTasks = $this->taskRepository->findTasksCompletedBetween($user, $dayStart, $dayEnd);
            
            if (count($completedTasks) > 0) {
                $streak++;
            } else {
                break;
            }
        }
        
        return $streak;
    }
}

