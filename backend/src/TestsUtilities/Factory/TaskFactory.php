<?php

declare(strict_types=1);

namespace App\TestsUtilities\Factory;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Task>
 */
final class TaskFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Task::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'title' => self::faker()->sentence(3),
            'description' => self::faker()->optional()->paragraph(),
            'status' => self::faker()->randomElement(TaskStatus::cases()),
            'priority' => self::faker()->randomElement(TaskPriority::cases()),
            'user' => UserFactory::new(),
            'startDate' => self::faker()->optional()->dateTimeBetween('-30 days', '+30 days')
                ? \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-30 days', '+30 days'))
                : null,
            'dueDate' => self::faker()->optional()->dateTimeBetween('now', '+60 days')
                ? \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('now', '+60 days'))
                : null,
            'sortOrder' => self::faker()->numberBetween(0, 1000),
            'isArchived' => false,
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }

    /**
     * Create a pending task
     */
    public function pending(): self
    {
        return $this->with([
            'status' => TaskStatus::PENDING,
            'completedAt' => null,
        ]);
    }

    /**
     * Create a completed task
     */
    public function completed(): self
    {
        return $this->with([
            'status' => TaskStatus::COMPLETED,
            'completedAt' => \DateTimeImmutable::createFromMutable(
                self::faker()->dateTimeBetween('-30 days', 'now')
            ),
        ]);
    }

    /**
     * Create an archived task
     */
    public function archived(): self
    {
        return $this->with([
            'isArchived' => true,
        ]);
    }

    /**
     * Create a task with high priority
     */
    public function highPriority(): self
    {
        return $this->with([
            'priority' => TaskPriority::HIGH,
        ]);
    }

    /**
     * Create a task with urgent priority
     */
    public function urgent(): self
    {
        return $this->with([
            'priority' => TaskPriority::URGENT,
        ]);
    }

    /**
     * Create an overdue task
     */
    public function overdue(): self
    {
        return $this->with([
            'dueDate' => \DateTimeImmutable::createFromMutable(
                self::faker()->dateTimeBetween('-30 days', '-1 day')
            ),
            'status' => TaskStatus::PENDING,
        ]);
    }

    /**
     * Create a task without due date (unscheduled)
     */
    public function unscheduled(): self
    {
        return $this->with([
            'dueDate' => null,
            'startDate' => null,
        ]);
    }

    /**
     * Create a task for specific user
     */
    public function forUser(User $user): self
    {
        return $this->with([
            'user' => $user,
        ]);
    }

    /**
     * Create a subtask
     */
    public function asSubtaskOf(Task $parentTask): self
    {
        return $this->with([
            'parentTask' => $parentTask,
            'user' => $parentTask->getUser(),
        ]);
    }
}
