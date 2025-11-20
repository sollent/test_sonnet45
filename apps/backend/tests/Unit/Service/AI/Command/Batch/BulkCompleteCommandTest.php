<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AI\Command\Batch;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatus;
use App\Service\AI\Command\Batch\BulkCompleteCommand;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Response\ResponseBuilder;
use App\Service\AI\Service\TaskFinder;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BulkCompleteCommandTest extends TestCase
{
    private BulkCompleteCommand $command;
    private TaskFinder $taskFinder;
    private TaskService $taskService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->taskService = $this->createMock(TaskService::class);
        $this->taskFinder = $this->createMock(TaskFinder::class);

        $this->command = new BulkCompleteCommand(
            $this->entityManager,
            $this->taskService,
            $this->createMock(SmartSearchService::class),
            $this->createMock(DateTimeParser::class),
            $this->createMock(LoggerInterface::class),
            $this->taskFinder,
            $this->createMock(ResponseBuilder::class)
        );
    }

    public function testGetAction(): void
    {
        $this->assertSame(ParsedCommand::ACTION_BULK_COMPLETE, $this->command->getAction());
    }

    public function testExecuteCompletesMultipleTasks(): void
    {
        $user = $this->createMock(User::class);
        
        $task1 = $this->createTaskMock(1, 'Task 1', TaskStatus::PENDING);
        $task2 = $this->createTaskMock(2, 'Task 2', TaskStatus::IN_PROGRESS);
        
        $this->taskFinder
            ->method('filter')
            ->willReturn([$task1, $task2]);

        $this->taskService
            ->expects($this->exactly(2))
            ->method('completeTask');

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->command->execute(['status' => 'pending'], $user);

        $this->assertTrue($result->isSuccess());
    }

    public function testExecuteSkipsAlreadyCompletedTasks(): void
    {
        $user = $this->createMock(User::class);
        
        $pendingTask = $this->createTaskMock(1, 'Pending', TaskStatus::PENDING);
        $completedTask = $this->createTaskMock(2, 'Completed', TaskStatus::COMPLETED);
        
        $this->taskFinder
            ->method('filter')
            ->willReturn([$pendingTask, $completedTask]);

        // Только одна задача должна быть завершена
        $this->taskService
            ->expects($this->once())
            ->method('completeTask');

        $result = $this->command->execute([], $user);

        $this->assertTrue($result->isSuccess());
    }

    public function testExecuteNoTasksFound(): void
    {
        $user = $this->createMock(User::class);
        
        $this->taskFinder
            ->method('filter')
            ->willReturn([]);

        $result = $this->command->execute(['tag' => 'nonexistent'], $user);

        $this->assertFalse($result->isSuccess());
        $this->assertSame('no_tasks_to_complete', $result->getType());
    }

    private function createTaskMock(int $id, string $title, TaskStatus $status): Task
    {
        $task = $this->createMock(Task::class);
        $task->method('getId')->willReturn($id);
        $task->method('getTitle')->willReturn($title);
        $task->method('getStatus')->willReturn($status);
        return $task;
    }
}
