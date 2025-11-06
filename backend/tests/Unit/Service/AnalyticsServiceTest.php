<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Entity\Tag;
use App\Entity\Task;
use App\Repository\Database\TaskRepository;
use App\Repository\Database\TagRepository;
use App\Service\AnalyticsService;
use PHPUnit\Framework\TestCase;

class AnalyticsServiceTest extends TestCase
{
    private TaskRepository $taskRepository;
    private TagRepository $tagRepository;
    private AnalyticsService $analyticsService;
    private User $user;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(TaskRepository::class);
        $this->tagRepository = $this->createMock(TagRepository::class);

        // AnalyticsService is final - instantiate directly
        $this->analyticsService = new AnalyticsService(
            $this->taskRepository,
            $this->tagRepository
        );

        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setPassword('hashed_password');
    }

    /** @test */
    public function testGetOverview(): void
    {
        // Arrange
        $stats = [
            'total' => 100,
            'pending' => 30,
            'in_progress' => 20,
            'completed' => 45,
            'overdue' => 5
        ];

        $thisWeekTasks = [new Task(), new Task(), new Task()];
        $lastWeekTasks = [new Task(), new Task()];
        $thisWeekCompleted = [new Task(), new Task()];

        $this->taskRepository
            ->expects($this->once())
            ->method('getUserTaskStatistics')
            ->willReturn($stats);

        $this->taskRepository
            ->expects($this->exactly(2))
            ->method('findTasksCreatedBetween')
            ->willReturnOnConsecutiveCalls($thisWeekTasks, $lastWeekTasks);

        // findTasksCompletedBetween is called multiple times:
        // First call: for thisWeekCompleted in getOverview
        // Second+ calls: for streak calculation (return empty to break the loop)
        $this->taskRepository
            ->method('findTasksCompletedBetween')
            ->willReturnOnConsecutiveCalls($thisWeekCompleted, [], [], []);

        $this->taskRepository
            ->expects($this->once())
            ->method('getAverageCompletionTime')
            ->willReturn(2.5);

        $this->taskRepository
            ->expects($this->once())
            ->method('getOnTimeCompletionRate')
            ->willReturn(85);

        $this->taskRepository
            ->expects($this->once())
            ->method('getMostProductiveDay')
            ->willReturn('Monday');

        // Act
        $result = $this->analyticsService->getOverview($this->user);

        // Assert
        $this->assertEquals(100, $result['totalTasks']);
        $this->assertEquals(2, $result['completedThisWeek']);
        $this->assertEquals(1, $result['weeklyChange']);
        $this->assertEquals(50.0, $result['weeklyChangePercent']);
        $this->assertEquals(2.5, $result['averageCompletionTime']);
        $this->assertEquals(85, $result['onTimeCompletionRate']);
        $this->assertEquals('Monday', $result['mostProductiveDay']);
        $this->assertEquals(30, $result['pending']);
        $this->assertEquals(20, $result['inProgress']);
        $this->assertEquals(45, $result['completed']);
        $this->assertEquals(5, $result['overdue']);
    }

    /** @test */
    public function testGetOverviewWithNoLastWeekTasks(): void
    {
        // Arrange
        $stats = ['total' => 10, 'pending' => 5, 'completed' => 5];
        $thisWeekTasks = [new Task()];
        $lastWeekTasks = []; // No tasks last week

        $this->taskRepository
            ->method('getUserTaskStatistics')
            ->willReturn($stats);

        $this->taskRepository
            ->method('findTasksCreatedBetween')
            ->willReturnOnConsecutiveCalls($thisWeekTasks, $lastWeekTasks);

        $this->taskRepository
            ->method('findTasksCompletedBetween')
            ->willReturn([]);

        $this->taskRepository->method('getAverageCompletionTime')->willReturn(0.0);
        $this->taskRepository->method('getOnTimeCompletionRate')->willReturn(0);
        $this->taskRepository->method('getMostProductiveDay')->willReturn(null);

        // Act
        $result = $this->analyticsService->getOverview($this->user);

        // Assert
        $this->assertEquals(0, $result['weeklyChangePercent']); // No division by zero
    }

    /** @test */
    public function testGetCompletionTimeline(): void
    {
        // Arrange
        $timelineData = [
            ['date' => '2024-01-01', 'completed' => 5],
            ['date' => '2024-01-02', 'completed' => 3],
        ];

        $this->taskRepository
            ->expects($this->once())
            ->method('getCompletionTimelineData')
            ->willReturn($timelineData);

        // Act
        $result = $this->analyticsService->getCompletionTimeline($this->user, 30);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals('2024-01-01', $result[0]['date']);
        $this->assertEquals(5, $result[0]['completed']);
    }

    /** @test */
    public function testGetCompletionTimelineForAllTime(): void
    {
        // Arrange
        $timelineData = [
            ['date' => '2023-07-01', 'completed' => 10],
        ];

        $this->taskRepository
            ->expects($this->once())
            ->method('getCompletionTimelineData')
            ->willReturn($timelineData);

        // Act - 365 days means "all time", should show last 6 months
        $result = $this->analyticsService->getCompletionTimeline($this->user, 365);

        // Assert
        $this->assertCount(1, $result);
    }

    /** @test */
    public function testGetCompletionTimelineByDateRange(): void
    {
        // Arrange
        $start = new \DateTimeImmutable('2024-01-01');
        $end = new \DateTimeImmutable('2024-01-31');
        $timelineData = [
            ['date' => '2024-01-15', 'completed' => 8],
        ];

        $this->taskRepository
            ->expects($this->once())
            ->method('getCompletionTimelineData')
            ->with($this->user, $start, $end)
            ->willReturn($timelineData);

        // Act
        $result = $this->analyticsService->getCompletionTimelineByDateRange($this->user, $start, $end);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('2024-01-15', $result[0]['date']);
    }

    /** @test */
    public function testGetStatusDistribution(): void
    {
        // Arrange
        $stats = [
            'total' => 100,
            'pending' => 25,
            'in_progress' => 30,
            'completed' => 40,
            'cancelled' => 5
        ];

        $this->taskRepository
            ->expects($this->once())
            ->method('getUserTaskStatistics')
            ->willReturn($stats);

        // Act
        $result = $this->analyticsService->getStatusDistribution($this->user);

        // Assert
        $this->assertEquals(25, $result['pending']);
        $this->assertEquals(30, $result['in_progress']);
        $this->assertEquals(40, $result['completed']);
        $this->assertEquals(5, $result['cancelled']);
        $this->assertEquals(100, $result['total']);
    }

    /** @test */
    public function testGetPriorityBreakdown(): void
    {
        // Arrange
        $breakdown = [
            'low' => 10,
            'medium' => 30,
            'high' => 15,
            'urgent' => 5
        ];

        $this->taskRepository
            ->expects($this->once())
            ->method('getPriorityBreakdown')
            ->willReturn($breakdown);

        // Act
        $result = $this->analyticsService->getPriorityBreakdown($this->user);

        // Assert
        $this->assertEquals(10, $result['low']);
        $this->assertEquals(30, $result['medium']);
        $this->assertEquals(15, $result['high']);
        $this->assertEquals(5, $result['urgent']);
    }

    /** @test */
    public function testGetProductivityHeatmap(): void
    {
        // Arrange
        $heatmapData = [
            ['date' => '2024-01-01', 'count' => 5],
            ['date' => '2024-01-02', 'count' => 3],
        ];

        $this->taskRepository
            ->expects($this->once())
            ->method('getProductivityHeatmap')
            ->with($this->user, 2024)
            ->willReturn($heatmapData);

        // Act
        $result = $this->analyticsService->getProductivityHeatmap($this->user, 2024);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals('2024-01-01', $result[0]['date']);
        $this->assertEquals(5, $result[0]['count']);
    }

    /** @test */
    public function testGetWeekdayProductivity(): void
    {
        // Arrange
        $weekdayData = [
            ['day' => 'Monday', 'count' => 15],
            ['day' => 'Tuesday', 'count' => 12],
            ['day' => 'Wednesday', 'count' => 18],
        ];

        $this->taskRepository
            ->expects($this->once())
            ->method('getWeekdayProductivity')
            ->willReturn($weekdayData);

        // Act
        $result = $this->analyticsService->getWeekdayProductivity($this->user);

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals('Monday', $result[0]['day']);
        $this->assertEquals(15, $result[0]['count']);
    }

    /** @test */
    public function testGetTopTags(): void
    {
        // Arrange
        $tag1 = new Tag();
        $tag1->setName('urgent');
        $tag1->setColor('#FF0000');

        // Use reflection to set ID
        $reflectionTag1 = new \ReflectionClass($tag1);
        $idProperty1 = $reflectionTag1->getProperty('id');
        $idProperty1->setAccessible(true);
        $idProperty1->setValue($tag1, 1);

        $tag2 = new Tag();
        $tag2->setName('work');
        $tag2->setColor('#0000FF');

        $reflectionTag2 = new \ReflectionClass($tag2);
        $idProperty2 = $reflectionTag2->getProperty('id');
        $idProperty2->setAccessible(true);
        $idProperty2->setValue($tag2, 2);

        $this->tagRepository
            ->expects($this->once())
            ->method('getMostUsedTags')
            ->with($this->user, 5)
            ->willReturn([$tag1, $tag2]);

        $this->taskRepository
            ->expects($this->exactly(2))
            ->method('getTagCompletionStats')
            ->willReturnOnConsecutiveCalls(
                ['total' => 20, 'completed' => 18, 'completionRate' => 90.0],
                ['total' => 15, 'completed' => 12, 'completionRate' => 80.0]
            );

        // Act
        $result = $this->analyticsService->getTopTags($this->user);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals('urgent', $result[0]['name']);
        $this->assertEquals(20, $result[0]['totalTasks']);
        $this->assertEquals(18, $result[0]['completedTasks']);
        $this->assertEquals(90.0, $result[0]['completionRate']);
    }

    /** @test */
    public function testGetTopTagsWithEmptyResult(): void
    {
        // Arrange
        $this->tagRepository
            ->expects($this->once())
            ->method('getMostUsedTags')
            ->willReturn([]);

        // Act
        $result = $this->analyticsService->getTopTags($this->user);

        // Assert
        $this->assertCount(0, $result);
    }

    /** @test */
    public function testGenerateInsightsWithPositiveTrend(): void
    {
        // Arrange
        $thisWeekCompleted = array_fill(0, 10, new Task());
        $lastWeekCompleted = array_fill(0, 5, new Task());

        $this->taskRepository
            ->method('findTasksCompletedBetween')
            ->willReturnOnConsecutiveCalls(
                $thisWeekCompleted, // This week
                $lastWeekCompleted, // Last week
                [] // For streak calculation
            );

        $this->taskRepository
            ->method('getMostProductiveHour')
            ->willReturn(14);

        $this->taskRepository
            ->method('getMostProductiveDay')
            ->willReturn('Wednesday');

        $this->tagRepository
            ->method('getMostUsedTags')
            ->willReturn([]);

        // Act
        $result = $this->analyticsService->generateInsights($this->user);

        // Assert
        $this->assertGreaterThan(0, count($result));

        // Check for positive trend insight
        $trendInsight = array_filter($result, fn($i) => $i['type'] === 'trend');
        $this->assertNotEmpty($trendInsight);
        $firstTrendInsight = array_values($trendInsight)[0];
        $this->assertEquals('positive', $firstTrendInsight['sentiment']);
    }

    /** @test */
    public function testGenerateInsightsWithNegativeTrend(): void
    {
        // Arrange
        $thisWeekCompleted = array_fill(0, 5, new Task());
        $lastWeekCompleted = array_fill(0, 20, new Task());

        $this->taskRepository
            ->method('findTasksCompletedBetween')
            ->willReturnOnConsecutiveCalls(
                $thisWeekCompleted,
                $lastWeekCompleted,
                []
            );

        $this->taskRepository->method('getMostProductiveHour')->willReturn(null);
        $this->taskRepository->method('getMostProductiveDay')->willReturn(null);
        $this->tagRepository->method('getMostUsedTags')->willReturn([]);

        // Act
        $result = $this->analyticsService->generateInsights($this->user);

        // Assert
        $trendInsight = array_filter($result, fn($i) => $i['type'] === 'trend');
        $this->assertNotEmpty($trendInsight);
        $firstTrendInsight = array_values($trendInsight)[0];
        $this->assertEquals('warning', $firstTrendInsight['sentiment']);
    }

    /** @test */
    public function testGenerateInsightsWithStreak(): void
    {
        // Arrange
        // Mock streak: 5 consecutive days with tasks
        $completedTasks = [new Task()];

        $this->taskRepository
            ->method('findTasksCompletedBetween')
            ->willReturnOnConsecutiveCalls(
                $completedTasks, // This week
                $completedTasks, // Last week
                $completedTasks, // Day 0
                $completedTasks, // Day 1
                $completedTasks, // Day 2
                $completedTasks, // Day 3
                $completedTasks, // Day 4
                [] // Day 5 - break streak
            );

        $this->taskRepository->method('getMostProductiveHour')->willReturn(null);
        $this->taskRepository->method('getMostProductiveDay')->willReturn(null);
        $this->tagRepository->method('getMostUsedTags')->willReturn([]);

        // Act
        $result = $this->analyticsService->generateInsights($this->user);

        // Assert
        $streakInsight = array_filter($result, fn($i) => $i['type'] === 'streak');
        $this->assertNotEmpty($streakInsight);
        $firstStreakInsight = array_values($streakInsight)[0];
        $this->assertEquals('positive', $firstStreakInsight['sentiment']);
        $this->assertStringContainsString('Серия', $firstStreakInsight['message']);
    }

    /** @test */
    public function testGetDashboardData(): void
    {
        // Arrange
        $params = [
            'period' => 30,
            'dateFrom' => null,
            'dateTo' => null,
            'year' => 2024
        ];

        $stats = ['total' => 50, 'pending' => 10, 'completed' => 30];

        $this->taskRepository->method('getUserTaskStatistics')->willReturn($stats);
        $this->taskRepository->method('findTasksCreatedBetween')->willReturn([]);
        $this->taskRepository->method('findTasksCompletedBetween')->willReturn([]);
        $this->taskRepository->method('getAverageCompletionTime')->willReturn(2.0);
        $this->taskRepository->method('getOnTimeCompletionRate')->willReturn(75);
        $this->taskRepository->method('getMostProductiveDay')->willReturn('Friday');
        $this->taskRepository->method('getCompletionTimelineData')->willReturn([]);
        $this->taskRepository->method('getPriorityBreakdown')->willReturn([]);
        $this->taskRepository->method('getProductivityHeatmap')->willReturn([]);
        $this->taskRepository->method('getWeekdayProductivity')->willReturn([]);
        $this->taskRepository->method('getMostProductiveHour')->willReturn(null);
        $this->tagRepository->method('getMostUsedTags')->willReturn([]);

        // Act
        $result = $this->analyticsService->getDashboardData($this->user, $params);

        // Assert
        $this->assertArrayHasKey('overview', $result);
        $this->assertArrayHasKey('timeline', $result);
        $this->assertArrayHasKey('statusDistribution', $result);
        $this->assertArrayHasKey('priorityBreakdown', $result);
        $this->assertArrayHasKey('productivityHeatmap', $result);
        $this->assertArrayHasKey('weekdayProductivity', $result);
        $this->assertArrayHasKey('topTags', $result);
        $this->assertArrayHasKey('insights', $result);
        $this->assertArrayHasKey('meta', $result);

        $this->assertEquals(50, $result['overview']['totalTasks']);
        $this->assertEquals(2024, $result['meta']['year']);
    }

    /** @test */
    public function testGetDashboardDataWithCustomDateRange(): void
    {
        // Arrange
        $params = [
            'period' => 30,
            'dateFrom' => '2024-01-01',
            'dateTo' => '2024-01-31',
            'year' => 2024
        ];

        $this->taskRepository->method('getUserTaskStatistics')->willReturn(['total' => 20]);
        $this->taskRepository->method('findTasksCreatedBetween')->willReturn([]);
        $this->taskRepository->method('findTasksCompletedBetween')->willReturn([]);
        $this->taskRepository->method('getAverageCompletionTime')->willReturn(1.5);
        $this->taskRepository->method('getOnTimeCompletionRate')->willReturn(80);
        $this->taskRepository->method('getMostProductiveDay')->willReturn('Monday');
        $this->taskRepository->method('getCompletionTimelineData')->willReturn([]);
        $this->taskRepository->method('getPriorityBreakdown')->willReturn([]);
        $this->taskRepository->method('getProductivityHeatmap')->willReturn([]);
        $this->taskRepository->method('getWeekdayProductivity')->willReturn([]);
        $this->taskRepository->method('getMostProductiveHour')->willReturn(null);
        $this->tagRepository->method('getMostUsedTags')->willReturn([]);

        // Act
        $result = $this->analyticsService->getDashboardData($this->user, $params);

        // Assert
        $this->assertEquals('2024-01-01', $result['meta']['dateFrom']);
        $this->assertEquals('2024-01-31', $result['meta']['dateTo']);
    }
}
