<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->user->setEmail('test@example.com');
    }

    /** @test */
    public function testIsCompletedReturnsTrueWhenStatusCompleted(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Completed Task');
        $task->setUser($this->user);
        $task->setStatus(TaskStatus::COMPLETED);

        // Act & Assert
        $this->assertTrue($task->isCompleted());
    }

    /** @test */
    public function testIsCompletedReturnsFalseForOtherStatuses(): void
    {
        // Arrange
        $statuses = [TaskStatus::PENDING, TaskStatus::IN_PROGRESS, TaskStatus::CANCELLED];

        foreach ($statuses as $status) {
            $task = new Task();
            $task->setTitle('Test Task');
            $task->setUser($this->user);
            $task->setStatus($status);

            // Act & Assert
            $this->assertFalse($task->isCompleted(), "Status {$status->value} should not be completed");
        }
    }

    /** @test */
    public function testIsOverdueReturnsTrueForPastDueDate(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Overdue Task');
        $task->setUser($this->user);
        $task->setStatus(TaskStatus::IN_PROGRESS);
        $task->setDueDate(new DateTimeImmutable('-1 day'));

        // Act & Assert
        $this->assertTrue($task->isOverdue());
    }

    /** @test */
    public function testIsOverdueReturnsFalseForFutureDueDate(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Future Task');
        $task->setUser($this->user);
        $task->setStatus(TaskStatus::PENDING);
        $task->setDueDate(new DateTimeImmutable('+1 day'));

        // Act & Assert
        $this->assertFalse($task->isOverdue());
    }

    /** @test */
    public function testIsOverdueReturnsFalseWhenNoDueDate(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('No Due Date Task');
        $task->setUser($this->user);
        $task->setStatus(TaskStatus::PENDING);
        $task->setDueDate(null);

        // Act & Assert
        $this->assertFalse($task->isOverdue());
    }

    /** @test */
    public function testIsOverdueReturnsFalseWhenTaskIsCompleted(): void
    {
        // Arrange - Completed task with past due date
        $task = new Task();
        $task->setTitle('Completed Task');
        $task->setUser($this->user);
        $task->setStatus(TaskStatus::COMPLETED);
        $task->setDueDate(new DateTimeImmutable('-1 day'));

        // Act & Assert
        $this->assertFalse($task->isOverdue(), 'Completed tasks should not be overdue');
    }

    /** @test */
    public function testGetCompletionProgressReturnsZeroForTaskWithoutSubtasks(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Parent Task');
        $task->setUser($this->user);
        $task->setStatus(TaskStatus::IN_PROGRESS);

        // Act
        $progress = $task->getCompletionProgress();

        // Assert
        $this->assertEquals(0.0, $progress);
    }

    /** @test */
    public function testGetCompletionProgressReturns100ForCompletedTaskWithoutSubtasks(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Completed Task');
        $task->setUser($this->user);
        $task->setStatus(TaskStatus::COMPLETED);

        // Act
        $progress = $task->getCompletionProgress();

        // Assert
        $this->assertEquals(100.0, $progress);
    }

    /** @test */
    public function testGetCompletionProgressCalculatesBasedOnSubtasks(): void
    {
        // Arrange
        $parent = new Task();
        $parent->setTitle('Parent Task');
        $parent->setUser($this->user);
        $parent->setStatus(TaskStatus::IN_PROGRESS);

        // Add 4 subtasks, 2 completed
        $subtask1 = new Task();
        $subtask1->setTitle('Subtask 1');
        $subtask1->setUser($this->user);
        $subtask1->setStatus(TaskStatus::COMPLETED);
        $parent->addSubtask($subtask1);

        $subtask2 = new Task();
        $subtask2->setTitle('Subtask 2');
        $subtask2->setUser($this->user);
        $subtask2->setStatus(TaskStatus::COMPLETED);
        $parent->addSubtask($subtask2);

        $subtask3 = new Task();
        $subtask3->setTitle('Subtask 3');
        $subtask3->setUser($this->user);
        $subtask3->setStatus(TaskStatus::IN_PROGRESS);
        $parent->addSubtask($subtask3);

        $subtask4 = new Task();
        $subtask4->setTitle('Subtask 4');
        $subtask4->setUser($this->user);
        $subtask4->setStatus(TaskStatus::PENDING);
        $parent->addSubtask($subtask4);

        // Act
        $progress = $parent->getCompletionProgress();

        // Assert - 2 out of 4 completed = 50%
        $this->assertEquals(50.0, $progress);
    }

    /** @test */
    public function testAddSubtaskRelationship(): void
    {
        // Arrange
        $parent = new Task();
        $parent->setTitle('Parent');
        $parent->setUser($this->user);

        $subtask = new Task();
        $subtask->setTitle('Subtask');
        $subtask->setUser($this->user);

        // Act
        $parent->addSubtask($subtask);

        // Assert
        $this->assertCount(1, $parent->getSubtasks());
        $this->assertTrue($parent->getSubtasks()->contains($subtask));
        $this->assertSame($parent, $subtask->getParentTask());
    }

    /** @test */
    public function testRemoveSubtaskRelationship(): void
    {
        // Arrange
        $parent = new Task();
        $parent->setTitle('Parent');
        $parent->setUser($this->user);

        $subtask = new Task();
        $subtask->setTitle('Subtask');
        $subtask->setUser($this->user);

        $parent->addSubtask($subtask);

        // Act
        $parent->removeSubtask($subtask);

        // Assert
        $this->assertCount(0, $parent->getSubtasks());
        $this->assertFalse($parent->getSubtasks()->contains($subtask));
        $this->assertNull($subtask->getParentTask());
    }

    /** @test */
    public function testAddTagRelationship(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Task');
        $task->setUser($this->user);

        $tag = $this->createMock(\App\Entity\Tag::class);

        // Act
        $task->addTag($tag);

        // Assert
        $this->assertCount(1, $task->getTags());
        $this->assertTrue($task->getTags()->contains($tag));
    }

    /** @test */
    public function testClearMediaObjects(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Task with media');
        $task->setUser($this->user);

        $media1 = $this->createMock(\App\Entity\MediaObject::class);
        $media2 = $this->createMock(\App\Entity\MediaObject::class);

        $task->addMediaObject($media1);
        $task->addMediaObject($media2);

        $this->assertCount(2, $task->getMediaObjects());

        // Act
        $task->clearMediaObjects();

        // Assert
        $this->assertCount(0, $task->getMediaObjects());
    }
}
