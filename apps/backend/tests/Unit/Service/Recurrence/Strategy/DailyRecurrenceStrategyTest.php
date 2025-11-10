<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Recurrence\Strategy;

use App\Entity\RecurrenceRule;
use App\Service\Recurrence\Strategy\DailyRecurrenceStrategy;
use PHPUnit\Framework\TestCase;

class DailyRecurrenceStrategyTest extends TestCase
{
    private DailyRecurrenceStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new DailyRecurrenceStrategy();
    }

    /** @test */
    public function testCalculateNextOccurrenceAddsOneDay(): void
    {
        // Arrange
        $currentDate = new \DateTime('2025-01-10');
        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getTimeOfDay')->willReturn(null);
        $rule->method('getEndDate')->willReturn(null);
        $rule->method('getMaxOccurrences')->willReturn(null);
        $rule->method('getCurrentOccurrences')->willReturn(0);

        // Act
        $nextDate = $this->strategy->calculateNextOccurrence($currentDate, $rule);

        // Assert
        $this->assertNotNull($nextDate);
        $this->assertEquals('2025-01-11', $nextDate->format('Y-m-d'));
    }

    /** @test */
    public function testAppliesTimeOfDayIfSet(): void
    {
        // Arrange
        $currentDate = new \DateTime('2025-01-10 09:00:00');
        $timeOfDay = new \DateTime('14:30:00');

        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getTimeOfDay')->willReturn($timeOfDay);
        $rule->method('getEndDate')->willReturn(null);
        $rule->method('getMaxOccurrences')->willReturn(null);
        $rule->method('getCurrentOccurrences')->willReturn(0);

        // Act
        $nextDate = $this->strategy->calculateNextOccurrence($currentDate, $rule);

        // Assert
        $this->assertNotNull($nextDate);
        $this->assertEquals('2025-01-11 14:30:00', $nextDate->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function testRespectsEndDate(): void
    {
        // Arrange
        $currentDate = new \DateTime('2025-01-15');
        $endDate = new \DateTime('2025-01-15');

        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getTimeOfDay')->willReturn(null);
        $rule->method('getEndDate')->willReturn($endDate);
        $rule->method('getMaxOccurrences')->willReturn(null);
        $rule->method('getCurrentOccurrences')->willReturn(0);

        // Act
        $nextDate = $this->strategy->calculateNextOccurrence($currentDate, $rule);

        // Assert
        $this->assertNull($nextDate, 'Should return null when next date exceeds endDate');
    }

    /** @test */
    public function testRespectsMaxOccurrences(): void
    {
        // Arrange
        $currentDate = new \DateTime('2025-01-10');

        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getTimeOfDay')->willReturn(null);
        $rule->method('getEndDate')->willReturn(null);
        $rule->method('getMaxOccurrences')->willReturn(5);
        $rule->method('getCurrentOccurrences')->willReturn(5); // Already at max

        // Act
        $nextDate = $this->strategy->calculateNextOccurrence($currentDate, $rule);

        // Assert
        $this->assertNull($nextDate, 'Should return null when max occurrences reached');
    }

    /** @test */
    public function testSupportsOnlyDailyType(): void
    {
        // Assert
        $this->assertTrue($this->strategy->supports(RecurrenceRule::TYPE_DAILY));
        $this->assertFalse($this->strategy->supports(RecurrenceRule::TYPE_WEEKLY));
        $this->assertFalse($this->strategy->supports(RecurrenceRule::TYPE_MONTHLY));
        $this->assertFalse($this->strategy->supports(RecurrenceRule::TYPE_YEARLY));
        $this->assertFalse($this->strategy->supports(RecurrenceRule::TYPE_CUSTOM));
    }

    /** @test */
    public function testGetPreviewDatesReturnsCorrectCount(): void
    {
        // Arrange
        $startDate = new \DateTime('2025-01-10');

        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getTimeOfDay')->willReturn(null);
        $rule->method('getEndDate')->willReturn(null);
        $rule->method('getMaxOccurrences')->willReturn(null);
        $rule->method('getCurrentOccurrences')->willReturn(0);

        // Act
        $previewDates = $this->strategy->getPreviewDates($startDate, $rule, 5);

        // Assert
        $this->assertCount(5, $previewDates);
        $this->assertEquals('2025-01-11', $previewDates[0]->format('Y-m-d'));
        $this->assertEquals('2025-01-12', $previewDates[1]->format('Y-m-d'));
        $this->assertEquals('2025-01-15', $previewDates[4]->format('Y-m-d'));
    }

    /** @test */
    public function testGetPreviewDatesStopsAtEndDate(): void
    {
        // Arrange
        $startDate = new \DateTime('2025-01-10');
        $endDate = new \DateTime('2025-01-12'); // Only 2 days forward

        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getTimeOfDay')->willReturn(null);
        $rule->method('getEndDate')->willReturn($endDate);
        $rule->method('getMaxOccurrences')->willReturn(null);
        $rule->method('getCurrentOccurrences')->willReturn(0);

        // Act
        $previewDates = $this->strategy->getPreviewDates($startDate, $rule, 10);

        // Assert
        $this->assertCount(2, $previewDates, 'Should only return dates until endDate');
        $this->assertEquals('2025-01-11', $previewDates[0]->format('Y-m-d'));
        $this->assertEquals('2025-01-12', $previewDates[1]->format('Y-m-d'));
    }
}
