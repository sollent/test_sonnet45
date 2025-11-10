<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Recurrence\Strategy;

use App\Entity\RecurrenceRule;
use App\Service\Recurrence\Strategy\WeeklyRecurrenceStrategy;
use PHPUnit\Framework\TestCase;

class WeeklyRecurrenceStrategyTest extends TestCase
{
    private WeeklyRecurrenceStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new WeeklyRecurrenceStrategy();
    }

    /** @test */
    public function testCalculateNextOccurrenceFindsNextDayOfWeek(): void
    {
        // Arrange - Monday 2025-01-13, next occurrence on Wednesday (day 3)
        $currentDate = new \DateTime('2025-01-13'); // Monday
        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getDaysOfWeek')->willReturn([3]); // Wednesday
        $rule->method('getTimeOfDay')->willReturn(null);
        $rule->method('getEndDate')->willReturn(null);
        $rule->method('getMaxOccurrences')->willReturn(null);
        $rule->method('getCurrentOccurrences')->willReturn(0);

        // Act
        $nextDate = $this->strategy->calculateNextOccurrence($currentDate, $rule);

        // Assert
        $this->assertNotNull($nextDate);
        $this->assertEquals('2025-01-15', $nextDate->format('Y-m-d')); // Wednesday
    }

    /** @test */
    public function testAppliesTimeOfDayIfSet(): void
    {
        $currentDate = new \DateTime('2025-01-13 09:00:00');
        $timeOfDay = new \DateTime('14:30:00');

        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getDaysOfWeek')->willReturn([1]); // Monday
        $rule->method('getTimeOfDay')->willReturn($timeOfDay);
        $rule->method('getEndDate')->willReturn(null);
        $rule->method('getMaxOccurrences')->willReturn(null);
        $rule->method('getCurrentOccurrences')->willReturn(0);

        $nextDate = $this->strategy->calculateNextOccurrence($currentDate, $rule);

        $this->assertEquals('14:30:00', $nextDate->format('H:i:s'));
    }

    /** @test */
    public function testRespectsEndDate(): void
    {
        $currentDate = new \DateTime('2025-01-13');
        $endDate = new \DateTime('2025-01-14');

        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getDaysOfWeek')->willReturn([3]); // Wednesday (2025-01-15)
        $rule->method('getTimeOfDay')->willReturn(null);
        $rule->method('getEndDate')->willReturn($endDate);
        $rule->method('getMaxOccurrences')->willReturn(null);
        $rule->method('getCurrentOccurrences')->willReturn(0);

        $nextDate = $this->strategy->calculateNextOccurrence($currentDate, $rule);

        $this->assertNull($nextDate);
    }

    /** @test */
    public function testRespectsMaxOccurrences(): void
    {
        $currentDate = new \DateTime('2025-01-13');

        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getDaysOfWeek')->willReturn([1]);
        $rule->method('getTimeOfDay')->willReturn(null);
        $rule->method('getEndDate')->willReturn(null);
        $rule->method('getMaxOccurrences')->willReturn(5);
        $rule->method('getCurrentOccurrences')->willReturn(5);

        $nextDate = $this->strategy->calculateNextOccurrence($currentDate, $rule);

        $this->assertNull($nextDate);
    }

    /** @test */
    public function testSupportsOnlyWeeklyType(): void
    {
        $this->assertTrue($this->strategy->supports(RecurrenceRule::TYPE_WEEKLY));
        $this->assertFalse($this->strategy->supports(RecurrenceRule::TYPE_DAILY));
    }

    /** @test */
    public function testGetPreviewDatesReturnsCorrectCount(): void
    {
        $startDate = new \DateTime('2025-01-13'); // Monday

        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getDaysOfWeek')->willReturn([1, 3, 5]); // Mon, Wed, Fri
        $rule->method('getTimeOfDay')->willReturn(null);
        $rule->method('getEndDate')->willReturn(null);
        $rule->method('getMaxOccurrences')->willReturn(null);
        $rule->method('getCurrentOccurrences')->willReturn(0);

        $previewDates = $this->strategy->getPreviewDates($startDate, $rule, 3);

        $this->assertCount(3, $previewDates);
    }

    /** @test */
    public function testGetPreviewDatesStopsAtEndDate(): void
    {
        $startDate = new \DateTime('2025-01-13');
        $endDate = new \DateTime('2025-01-16');

        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getDaysOfWeek')->willReturn([1, 3, 5]);
        $rule->method('getTimeOfDay')->willReturn(null);
        $rule->method('getEndDate')->willReturn($endDate);
        $rule->method('getMaxOccurrences')->willReturn(null);
        $rule->method('getCurrentOccurrences')->willReturn(0);

        $previewDates = $this->strategy->getPreviewDates($startDate, $rule, 10);

        $this->assertLessThanOrEqual(2, count($previewDates));
    }
}
