<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\Request\Task\CreateTaskDto;
use App\Dto\Request\Task\UpdateTaskDto;
use App\Entity\Tag;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Exception\Task\TaskAccessDeniedException;
use App\Repository\Database\MediaObjectRepository;
use App\Repository\Database\TagRepository;
use App\Repository\Database\TaskRepository;
use App\Service\TaskService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class TaskServiceTest extends TestCase
{
    private TaskRepository $taskRepository;

    private TagRepository $tagRepository;

    private MediaObjectRepository $mediaObjectRepository;

    private EntityManagerInterface $entityManager;

    private TaskService $taskService;

    private User $user;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(TaskRepository::class);
        $this->tagRepository = $this->createMock(TagRepository::class);
        $this->mediaObjectRepository = $this->createMock(MediaObjectRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // TaskService with null optional dependencies
        $this->taskService = new TaskService(
            $this->taskRepository,
            $this->tagRepository,
            $this->entityManager,
            $this->mediaObjectRepository,
            null, // RecurrenceService
            null, // TranslationService
            null,  // RequestStack
        );

        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setPassword('hashed_password');
    }

    /** @test */
    public function testCreateTaskSuccessfully(): void
    {
        // Arrange
        $dto = new CreateTaskDto();
        $dto->title = 'Test Task';
        $dto->description = 'Test Description';
        $dto->status = TaskStatus::PENDING;
        $dto->priority = TaskPriority::MEDIUM;

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $task = $this->taskService->createTask($dto, $this->user);

        // Assert
        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Test Task', $task->getTitle());
        $this->assertEquals('Test Description', $task->getDescription());
        $this->assertEquals(TaskStatus::PENDING, $task->getStatus());
    }

    /** @test */
    public function testCreateTaskWithTags(): void
    {
        // Arrange
        $dto = new CreateTaskDto();
        $dto->title = 'Task with Tags';
        $dto->tags = ['urgent', 'work'];

        $tag1 = new Tag();
        $tag1->setName('urgent');
        $tag2 = new Tag();
        $tag2->setName('work');

        $this->tagRepository
            ->expects($this->once())
            ->method('findOrCreateByNames')
            ->willReturn([$tag1, $tag2]);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $task = $this->taskService->createTask($dto, $this->user);

        // Assert
        $this->assertCount(2, $task->getTags());
    }

    /** @test */
    public function testUpdateTaskSuccessfully(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Original');
        $task->setUser($this->user);

        $dto = new UpdateTaskDto();
        $dto->title = 'Updated Title';
        $dto->description = 'Updated Description';

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $updatedTask = $this->taskService->updateTask($task, $dto, $this->user);

        // Assert
        $this->assertEquals('Updated Title', $updatedTask->getTitle());
        $this->assertEquals('Updated Description', $updatedTask->getDescription());
    }

    /** @test */
    public function testUpdateTaskAccessDenied(): void
    {
        // Arrange
        $otherUser = new User();
        $otherUser->setEmail('other@example.com');

        $task = new Task();
        $task->setTitle('Task');
        $task->setUser($otherUser);

        $dto = new UpdateTaskDto();
        $dto->title = 'Trying to update';

        // Assert
        $this->expectException(TaskAccessDeniedException::class);

        // Act
        $this->taskService->updateTask($task, $dto, $this->user);
    }

    /** @test */
    public function testDeleteTask(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Task to Delete');
        $task->setUser($this->user);

        $this->entityManager->expects($this->once())->method('remove');
        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $this->taskService->deleteTask($task, $this->user);

        // Assert - no exception
        $this->assertTrue(true);
    }

    /** @test */
    public function testDeleteTaskAccessDenied(): void
    {
        // Arrange
        $otherUser = new User();
        $otherUser->setEmail('other@example.com');

        $task = new Task();
        $task->setUser($otherUser);

        // Assert
        $this->expectException(TaskAccessDeniedException::class);

        // Act
        $this->taskService->deleteTask($task, $this->user);
    }

    /** @test */
    public function testCompleteTask(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Task');
        $task->setStatus(TaskStatus::PENDING);
        $task->setUser($this->user);

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $completedTask = $this->taskService->completeTask($task, $this->user);

        // Assert
        $this->assertEquals(TaskStatus::COMPLETED, $completedTask->getStatus());
        $this->assertNotNull($completedTask->getCompletedAt());
    }

    /** @test */
    public function testToggleTaskCompletionFromPendingToCompleted(): void
    {
        // Arrange
        $task = new Task();
        $task->setStatus(TaskStatus::PENDING);
        $task->setUser($this->user);

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $toggledTask = $this->taskService->toggleTaskCompletion($task, $this->user);

        // Assert
        $this->assertEquals(TaskStatus::COMPLETED, $toggledTask->getStatus());
        $this->assertNotNull($toggledTask->getCompletedAt());
    }

    /** @test */
    public function testToggleTaskCompletionFromCompletedToPending(): void
    {
        // Arrange
        $task = new Task();
        $task->setStatus(TaskStatus::COMPLETED);
        $task->setCompletedAt(new DateTimeImmutable());
        $task->setUser($this->user);

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $toggledTask = $this->taskService->toggleTaskCompletion($task, $this->user);

        // Assert
        $this->assertEquals(TaskStatus::PENDING, $toggledTask->getStatus());
        $this->assertNull($toggledTask->getCompletedAt());
    }

    /** @test */
    public function testArchiveTask(): void
    {
        // Arrange
        $task = new Task();
        $task->setIsArchived(false);
        $task->setUser($this->user);

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $archivedTask = $this->taskService->archiveTask($task, $this->user);

        // Assert
        $this->assertTrue($archivedTask->isArchived());
    }

    /** @test */
    public function testUnarchiveTask(): void
    {
        // Arrange
        $task = new Task();
        $task->setIsArchived(true);
        $task->setUser($this->user);

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $unarchivedTask = $this->taskService->unarchiveTask($task, $this->user);

        // Assert
        $this->assertFalse($unarchivedTask->isArchived());
    }

    /** @test */
    public function testGetOverdueTasks(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Overdue Task');

        $this->taskRepository
            ->expects($this->once())
            ->method('findOverdueTasks')
            ->willReturn([$task]);

        // Act
        $tasks = $this->taskService->getOverdueTasks($this->user);

        // Assert
        $this->assertCount(1, $tasks);
        $this->assertEquals('Overdue Task', $tasks[0]->getTitle());
    }

    /** @test */
    public function testGetTaskStatistics(): void
    {
        // Arrange
        $expectedStats = [
            'total'     => 10,
            'pending'   => 5,
            'completed' => 3,
        ];

        $this->taskRepository
            ->expects($this->once())
            ->method('getUserTaskStatistics')
            ->willReturn($expectedStats);

        // Act
        $stats = $this->taskService->getTaskStatistics($this->user);

        // Assert
        $this->assertEquals(10, $stats['total']);
        $this->assertEquals(5, $stats['pending']);
    }

    /** @test */
    public function testCreateTaskAsSubtask(): void
    {
        // Arrange
        $parentTask = new Task();
        $parentTask->setTitle('Parent Task');
        $parentTask->setUser($this->user);

        $dto = new CreateTaskDto();
        $dto->title = 'Subtask';
        $dto->parentTaskId = 123;

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($parentTask);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $task = $this->taskService->createTask($dto, $this->user);

        // Assert
        $this->assertEquals('Subtask', $task->getTitle());
        $this->assertEquals($parentTask, $task->getParentTask());
    }

    /** @test */
    public function testGetUserTasks(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Test Task');
        $task->setUser($this->user);

        $this->taskRepository
            ->expects($this->once())
            ->method('findUserTasks')
            ->with($this->user, null, false, true)
            ->willReturn([$task]);

        // Act
        $tasks = $this->taskService->getUserTasks($this->user);

        // Assert
        $this->assertCount(1, $tasks);
        $this->assertEquals('Test Task', $tasks[0]->getTitle());
    }

    /** @test */
    public function testGetTodayTasks(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Today Task');

        $this->taskRepository
            ->expects($this->once())
            ->method('findTodayTasks')
            ->willReturn([$task]);

        // Act
        $tasks = $this->taskService->getTodayTasks($this->user);

        // Assert
        $this->assertCount(1, $tasks);
        $this->assertEquals('Today Task', $tasks[0]->getTitle());
    }

    /** @test */
    public function testGetUpcomingTasks(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Upcoming Task');

        $this->taskRepository
            ->expects($this->once())
            ->method('findUpcomingTasks')
            ->with($this->user, 7, null)
            ->willReturn([$task]);

        // Act
        $tasks = $this->taskService->getUpcomingTasks($this->user);

        // Assert
        $this->assertCount(1, $tasks);
        $this->assertEquals('Upcoming Task', $tasks[0]->getTitle());
    }

    /** @test */
    public function testGetActiveTasks(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Active Task');

        $this->taskRepository
            ->expects($this->once())
            ->method('findActiveTasks')
            ->with($this->user, null, null, null)
            ->willReturn([$task]);

        // Act
        $tasks = $this->taskService->getActiveTasks($this->user);

        // Assert
        $this->assertCount(1, $tasks);
        $this->assertEquals('Active Task', $tasks[0]->getTitle());
    }

    /** @test */
    public function testCountActiveTasks(): void
    {
        // Arrange
        $this->taskRepository
            ->expects($this->once())
            ->method('countActiveTasks')
            ->with($this->user, null)
            ->willReturn(42);

        // Act
        $count = $this->taskService->countActiveTasks($this->user);

        // Assert
        $this->assertEquals(42, $count);
    }

    /** @test */
    public function testSearchTasks(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Searchable Task');

        $this->taskRepository
            ->expects($this->once())
            ->method('searchTasks')
            ->with($this->user, 'search query', null)
            ->willReturn([$task]);

        // Act
        $tasks = $this->taskService->searchTasks($this->user, 'search query');

        // Assert
        $this->assertCount(1, $tasks);
        $this->assertEquals('Searchable Task', $tasks[0]->getTitle());
    }

    /** @test */
    public function testGetTasksByTag(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Tagged Task');

        $this->taskRepository
            ->expects($this->once())
            ->method('findTasksByTag')
            ->with($this->user, 99)
            ->willReturn([$task]);

        // Act
        $tasks = $this->taskService->getTasksByTag($this->user, 99);

        // Assert
        $this->assertCount(1, $tasks);
        $this->assertEquals('Tagged Task', $tasks[0]->getTitle());
    }

    /** @test */
    public function testUpdateTaskSortOrders(): void
    {
        // Arrange
        $task1 = new Task();
        $task1->setUser($this->user);

        $task2 = new Task();
        $task2->setUser($this->user);

        $this->taskRepository
            ->expects($this->exactly(2))
            ->method('find')
            ->willReturnOnConsecutiveCalls($task1, $task2);

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $this->taskService->updateTaskSortOrders($this->user, [1, 2]);

        // Assert
        $this->assertEquals(0, $task1->getSortOrder());
        $this->assertEquals(1, $task2->getSortOrder());
    }

    /** @test */
    public function testUpdateTaskClearsTagsWhenEmptyArray(): void
    {
        // Arrange
        $tag = new Tag();
        $tag->setName('old-tag');

        $task = new Task();
        $task->setTitle('Task with Tag');
        $task->setUser($this->user);
        $task->addTag($tag);

        $dto = new UpdateTaskDto();
        $dto->tags = []; // Empty array should clear all tags

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $updatedTask = $this->taskService->updateTask($task, $dto, $this->user);

        // Assert
        $this->assertCount(0, $updatedTask->getTags());
    }

    /** @test */
    public function testCompleteTaskSetsCompletedAt(): void
    {
        // Arrange
        $task = new Task();
        $task->setTitle('Task');
        $task->setStatus(TaskStatus::PENDING);
        $task->setUser($this->user);

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $completedTask = $this->taskService->completeTask($task, $this->user);

        // Assert
        $this->assertEquals(TaskStatus::COMPLETED, $completedTask->getStatus());
        $this->assertNotNull($completedTask->getCompletedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $completedTask->getCompletedAt());
    }
}
