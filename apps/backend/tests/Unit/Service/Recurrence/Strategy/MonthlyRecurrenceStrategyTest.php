<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Recurrence\Strategy;

use App\Entity\RecurrenceRule;
use App\Service\Recurrence\Strategy\MonthlyRecurrenceStrategy;
use PHPUnit\Framework\TestCase;

class MonthlyRecurrenceStrategyTest extends TestCase
{
    private MonthlyRecurrenceStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new MonthlyRecurrenceStrategy();
    }

    /** @test */
    public function testCalculateNextOccurrenceMovesToNextMonth(): void
    {
        $currentDate = new \DateTime('2025-01-15');
        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getDayOfMonth')->willReturn(15);
        $rule->method('getTimeOfDay')->willReturn(null);
        $rule->method('getEndDate')->willReturn(null);
        $rule->method('getMaxOccurrences')->willReturn(null);
        $rule->method('getCurrentOccurrences')->willReturn(0);

        $nextDate = $this->strategy->calculateNextOccurrence($currentDate, $rule);

        $this->assertNotNull($nextDate);
        $this->assertEquals('2025-02-15', $nextDate->format('Y-m-d'));
    }

    /** @test */
    public function testAppliesTimeOfDayIfSet(): void
    {
        $currentDate = new \DateTime('2025-01-15');
        $timeOfDay = new \DateTime('14:30:00');

        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getDayOfMonth')->willReturn(15);
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
        $currentDate = new \DateTime('2025-01-15');
        $endDate = new \DateTime('2025-02-01');

        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getDayOfMonth')->willReturn(15);
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
        $currentDate = new \DateTime('2025-01-15');

        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getDayOfMonth')->willReturn(15);
        $rule->method('getTimeOfDay')->willReturn(null);
        $rule->method('getEndDate')->willReturn(null);
        $rule->method('getMaxOccurrences')->willReturn(5);
        $rule->method('getCurrentOccurrences')->willReturn(5);

        $nextDate = $this->strategy->calculateNextOccurrence($currentDate, $rule);

        $this->assertNull($nextDate);
    }

    /** @test */
    public function testSupportsOnlyMonthlyType(): void
    {
        $this->assertTrue($this->strategy->supports(RecurrenceRule::TYPE_MONTHLY));
        $this->assertFalse($this->strategy->supports(RecurrenceRule::TYPE_DAILY));
    }

    /** @test */
    public function testGetPreviewDatesReturnsCorrectCount(): void
    {
        $startDate = new \DateTime('2025-01-15');

        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getDayOfMonth')->willReturn(15);
        $rule->method('getTimeOfDay')->willReturn(null);
        $rule->method('getEndDate')->willReturn(null);
        $rule->method('getMaxOccurrences')->willReturn(null);
        $rule->method('getCurrentOccurrences')->willReturn(0);

        $previewDates = $this->strategy->getPreviewDates($startDate, $rule, 3);

        $this->assertCount(3, $previewDates);
        $this->assertEquals('2025-02-15', $previewDates[0]->format('Y-m-d'));
        $this->assertEquals('2025-03-15', $previewDates[1]->format('Y-m-d'));
    }

    /** @test */
    public function testGetPreviewDatesStopsAtEndDate(): void
    {
        $startDate = new \DateTime('2025-01-15');
        $endDate = new \DateTime('2025-03-01');

        $rule = $this->createMock(RecurrenceRule::class);
        $rule->method('getDayOfMonth')->willReturn(15);
        $rule->method('getTimeOfDay')->willReturn(null);
        $rule->method('getEndDate')->willReturn($endDate);
        $rule->method('getMaxOccurrences')->willReturn(null);
        $rule->method('getCurrentOccurrences')->willReturn(0);

        $previewDates = $this->strategy->getPreviewDates($startDate, $rule, 10);

        $this->assertLessThanOrEqual(2, count($previewDates));
    }
}
