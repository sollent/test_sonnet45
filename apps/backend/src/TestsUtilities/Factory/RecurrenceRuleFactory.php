<?php

declare(strict_types=1);

namespace App\TestsUtilities\Factory;

use App\Entity\RecurrenceRule;
use App\Entity\Task;
use App\Entity\User;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<RecurrenceRule>
 */
final class RecurrenceRuleFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return RecurrenceRule::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'recurrenceType' => RecurrenceRule::TYPE_DAILY,
            'interval' => 1,
            'nextOccurrenceDate' => \DateTimeImmutable::createFromMutable(
                self::faker()->dateTimeBetween('now', '+30 days')
            ),
            'isActive' => true,
            'currentOccurrences' => 0,
            'createdBy' => UserFactory::new(),
            'templateTask' => TaskFactory::new(),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }

    /**
     * Create a daily recurrence rule
     */
    public function daily(int $interval = 1): self
    {
        return $this->with([
            'recurrenceType' => RecurrenceRule::TYPE_DAILY,
            'interval' => $interval,
        ]);
    }

    /**
     * Create a weekly recurrence rule
     */
    public function weekly(array $daysOfWeek = [1, 2, 3, 4, 5]): self
    {
        return $this->with([
            'recurrenceType' => RecurrenceRule::TYPE_WEEKLY,
            'daysOfWeek' => $daysOfWeek,
        ]);
    }

    /**
     * Create a monthly recurrence rule
     */
    public function monthly(int $dayOfMonth = 1): self
    {
        return $this->with([
            'recurrenceType' => RecurrenceRule::TYPE_MONTHLY,
            'dayOfMonth' => $dayOfMonth,
        ]);
    }

    /**
     * Create a yearly recurrence rule
     */
    public function yearly(int $monthOfYear = 1, int $dayOfMonth = 1): self
    {
        return $this->with([
            'recurrenceType' => RecurrenceRule::TYPE_YEARLY,
            'monthOfYear' => $monthOfYear,
            'dayOfMonth' => $dayOfMonth,
        ]);
    }

    /**
     * Create a custom recurrence rule
     */
    public function custom(int $interval): self
    {
        return $this->with([
            'recurrenceType' => RecurrenceRule::TYPE_CUSTOM,
            'interval' => $interval,
        ]);
    }

    /**
     * Set end date for the recurrence
     */
    public function withEndDate(\DateTimeInterface $endDate): self
    {
        return $this->with([
            'endDate' => $endDate,
        ]);
    }

    /**
     * Set max occurrences for the recurrence
     */
    public function withMaxOccurrences(int $maxOccurrences): self
    {
        return $this->with([
            'maxOccurrences' => $maxOccurrences,
        ]);
    }

    /**
     * Set time of day for task creation
     */
    public function withTimeOfDay(string $time = '09:00:00'): self
    {
        return $this->with([
            'timeOfDay' => \DateTimeImmutable::createFromFormat('H:i:s', $time),
        ]);
    }

    /**
     * Create an inactive recurrence rule
     */
    public function inactive(): self
    {
        return $this->with([
            'isActive' => false,
        ]);
    }

    /**
     * Create a recurrence rule for specific user
     */
    public function forUser(User $user): self
    {
        return $this->with([
            'createdBy' => $user,
        ]);
    }

    /**
     * Create a recurrence rule for specific task
     */
    public function forTask(Task $task): self
    {
        return $this->with([
            'templateTask' => $task,
            'createdBy' => $task->getUser(),
        ]);
    }

    /**
     * Create a recurrence rule that should stop (reached max occurrences)
     */
    public function shouldStop(): self
    {
        return $this->with([
            'maxOccurrences' => 5,
            'currentOccurrences' => 5,
        ]);
    }

    /**
     * Create a recurrence rule with past end date
     */
    public function expired(): self
    {
        return $this->with([
            'endDate' => \DateTimeImmutable::createFromMutable(
                self::faker()->dateTimeBetween('-30 days', '-1 day')
            ),
        ]);
    }
}
