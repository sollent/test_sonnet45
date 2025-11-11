<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\RecurrenceRule;
use App\Entity\Task;
use App\Entity\User;
use DateTime;
use PHPUnit\Framework\TestCase;

class RecurrenceRuleTest extends TestCase
{
    private User $user;

    private Task $templateTask;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->user->setEmail('test@example.com');

        $this->templateTask = new Task();
        $this->templateTask->setTitle('Template Task');
        $this->templateTask->setUser($this->user);
    }

    /** @test */
    public function testIncrementOccurrencesIncreasesCounter(): void
    {
        // Arrange
        $rule = new RecurrenceRule();
        $rule->setRecurrenceType(RecurrenceRule::TYPE_DAILY);
        $rule->setTemplateTask($this->templateTask);
        $rule->setCreatedBy($this->user);
        $rule->setNextOccurrenceDate(new DateTime('+1 day'));
        $rule->setCurrentOccurrences(5);

        // Act
        $rule->incrementOccurrences();

        // Assert
        $this->assertEquals(6, $rule->getCurrentOccurrences());
    }

    /** @test */
    public function testIncrementOccurrencesMultipleTimes(): void
    {
        // Arrange
        $rule = new RecurrenceRule();
        $rule->setRecurrenceType(RecurrenceRule::TYPE_WEEKLY);
        $rule->setTemplateTask($this->templateTask);
        $rule->setCreatedBy($this->user);
        $rule->setNextOccurrenceDate(new DateTime('+1 week'));
        $rule->setCurrentOccurrences(0);

        // Act
        $rule->incrementOccurrences();
        $rule->incrementOccurrences();
        $rule->incrementOccurrences();

        // Assert
        $this->assertEquals(3, $rule->getCurrentOccurrences());
    }

    /** @test */
    public function testShouldStopRecurrenceReturnsTrueWhenEndDatePassed(): void
    {
        // Arrange
        $rule = new RecurrenceRule();
        $rule->setRecurrenceType(RecurrenceRule::TYPE_DAILY);
        $rule->setTemplateTask($this->templateTask);
        $rule->setCreatedBy($this->user);
        $rule->setNextOccurrenceDate(new DateTime('+1 day'));
        $rule->setEndDate(new DateTime('-1 day')); // Past date
        $rule->setMaxOccurrences(null);
        $rule->setCurrentOccurrences(0);

        // Act & Assert
        $this->assertTrue($rule->shouldStopRecurrence());
    }

    /** @test */
    public function testShouldStopRecurrenceReturnsTrueWhenMaxOccurrencesReached(): void
    {
        // Arrange
        $rule = new RecurrenceRule();
        $rule->setRecurrenceType(RecurrenceRule::TYPE_DAILY);
        $rule->setTemplateTask($this->templateTask);
        $rule->setCreatedBy($this->user);
        $rule->setNextOccurrenceDate(new DateTime('+1 day'));
        $rule->setEndDate(null);
        $rule->setMaxOccurrences(10);
        $rule->setCurrentOccurrences(10); // Reached max

        // Act & Assert
        $this->assertTrue($rule->shouldStopRecurrence());
    }

    /** @test */
    public function testShouldStopRecurrenceReturnsTrueWhenMaxOccurrencesExceeded(): void
    {
        // Arrange
        $rule = new RecurrenceRule();
        $rule->setRecurrenceType(RecurrenceRule::TYPE_DAILY);
        $rule->setTemplateTask($this->templateTask);
        $rule->setCreatedBy($this->user);
        $rule->setNextOccurrenceDate(new DateTime('+1 day'));
        $rule->setEndDate(null);
        $rule->setMaxOccurrences(10);
        $rule->setCurrentOccurrences(15); // Exceeded max

        // Act & Assert
        $this->assertTrue($rule->shouldStopRecurrence());
    }

    /** @test */
    public function testShouldStopRecurrenceReturnsFalseWhenConditionsNotMet(): void
    {
        // Arrange
        $rule = new RecurrenceRule();
        $rule->setRecurrenceType(RecurrenceRule::TYPE_DAILY);
        $rule->setTemplateTask($this->templateTask);
        $rule->setCreatedBy($this->user);
        $rule->setNextOccurrenceDate(new DateTime('+1 day'));
        $rule->setEndDate(new DateTime('+1 year')); // Future date
        $rule->setMaxOccurrences(100);
        $rule->setCurrentOccurrences(5); // Not reached max

        // Act & Assert
        $this->assertFalse($rule->shouldStopRecurrence());
    }

    /** @test */
    public function testShouldStopRecurrenceReturnsFalseWhenNoEndDateOrMaxOccurrences(): void
    {
        // Arrange
        $rule = new RecurrenceRule();
        $rule->setRecurrenceType(RecurrenceRule::TYPE_DAILY);
        $rule->setTemplateTask($this->templateTask);
        $rule->setCreatedBy($this->user);
        $rule->setNextOccurrenceDate(new DateTime('+1 day'));
        $rule->setEndDate(null);
        $rule->setMaxOccurrences(null);
        $rule->setCurrentOccurrences(100);

        // Act & Assert
        $this->assertFalse($rule->shouldStopRecurrence());
    }

    /** @test */
    public function testIsActiveReturnsCorrectValue(): void
    {
        // Arrange
        $rule = new RecurrenceRule();
        $rule->setRecurrenceType(RecurrenceRule::TYPE_WEEKLY);
        $rule->setTemplateTask($this->templateTask);
        $rule->setCreatedBy($this->user);
        $rule->setNextOccurrenceDate(new DateTime('+1 week'));
        $rule->setIsActive(true);

        // Act & Assert
        $this->assertTrue($rule->isActive());

        // Change to inactive
        $rule->setIsActive(false);
        $this->assertFalse($rule->isActive());
    }
}
