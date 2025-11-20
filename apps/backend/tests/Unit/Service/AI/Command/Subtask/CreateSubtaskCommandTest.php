<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AI\Command\Subtask;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Service\AI\Command\Subtask\CreateSubtaskCommand;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Service\DateTimeResolver;
use App\Service\AI\Service\PriorityMapper;
use App\Service\AI\Service\TaskFinder;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CreateSubtaskCommandTest extends TestCase
{
    private CreateSubtaskCommand $command;
    private TaskFinder $taskFinder;
    private TaskService $taskService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->taskService = $this->createMock(TaskService::class);
        $this->taskFinder = $this->createMock(TaskFinder::class);

        $logger = $this->createMock(LoggerInterface::class);

        $this->command = new CreateSubtaskCommand(
            $this->entityManager,
            $this->taskService,
            $this->createMock(SmartSearchService::class),
            $this->createMock(DateTimeParser::class),
            $logger,
            $this->taskFinder,
            $this->createMock(DateTimeResolver::class),
            new PriorityMapper($logger)
        );
    }

    public function testGetAction(): void
    {
        $this->assertSame(ParsedCommand::ACTION_CREATE_SUBTASK, $this->command->getAction());
    }

    public function testExecuteCreatesSubtask(): void
    {
        $user = $this->createMock(User::class);
        $parentTask = $this->createTaskMock(1, 'Parent Task');
        $subtask = $this->createTaskMock(2, 'New Subtask');

        $this->taskFinder
            ->method('find')
            ->willReturn($parentTask);

        $this->taskFinder
            ->method('extractParentSearch')
            ->willReturn('Parent Task');

        $this->taskService
            ->method('createTask')
            ->willReturn($subtask);

        $subtask->expects($this->once())
            ->method('setParent')
            ->with($parentTask);

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->command->execute([
            'parent_task' => 'Parent Task',
            'title' => 'New Subtask'
        ], $user);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('subtask_created', $result->getType());
    }

    public function testExecuteParentNotFound(): void
    {
        $user = $this->createMock(User::class);

        $this->taskFinder
            ->method('extractParentSearch')
            ->willReturn('Nonexistent Parent');

        $this->taskFinder
            ->method('find')
            ->willReturn(null);

        $result = $this->command->execute([
            'parent_task' => 'Nonexistent Parent',
            'title' => 'Subtask'
        ], $user);

        $this->assertFalse($result->isSuccess());
        $this->assertSame('parent_task_not_found', $result->getType());
    }

    public function testExecuteWithoutTitleThrowsException(): void
    {
        $user = $this->createMock(User::class);

        $this->taskFinder
            ->method('extractParentSearch')
            ->willReturn('Parent');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('title is required');

        $this->command->execute([
            'parent_task' => 'Parent',
            // title отсутствует
        ], $user);
    }

    private function createTaskMock(int $id, string $title): Task
    {
        $task = $this->createMock(Task::class);
        $task->method('getId')->willReturn($id);
        $task->method('getTitle')->willReturn($title);
        $task->method('getStatus')->willReturn(TaskStatus::PENDING);
        $task->method('getPriority')->willReturn(TaskPriority::MEDIUM);
        $task->method('getStartDate')->willReturn(null);
        $task->method('getDueDate')->willReturn(null);
        $task->method('getTags')->willReturn(new ArrayCollection());
        return $task;
    }
}
