<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AI\Command\Task;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Service\AI\Command\Task\CreateTaskCommand;
use App\Repository\Database\TagRepository;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Response\ResponseBuilder;
use App\Service\AI\Service\DateTimeResolver;
use App\Service\AI\Service\PriorityMapper;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CreateTaskCommandTest extends TestCase
{
    private CreateTaskCommand $command;
    private TaskService $taskService;
    private SmartSearchService $searchService;
    private DateTimeResolver $dateTimeResolver;
    private PriorityMapper $priorityMapper;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->taskService = $this->createMock(TaskService::class);
        $this->searchService = $this->createMock(SmartSearchService::class);
        $this->dateTimeResolver = $this->createMock(DateTimeResolver::class);
        $this->priorityMapper = $this->createMock(PriorityMapper::class);

        $this->command = new CreateTaskCommand(
            $this->entityManager,
            $this->taskService,
            $this->searchService,
            $this->createMock(DateTimeParser::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(TagRepository::class),
            $this->priorityMapper,
            $this->dateTimeResolver,
            $this->createMock(ResponseBuilder::class)
        );
    }

    public function testGetAction(): void
    {
        $this->assertSame(ParsedCommand::ACTION_CREATE_TASK, $this->command->getAction());
    }

    public function testExecuteCreatesSimpleTask(): void
    {
        $user = $this->createMock(User::class);
        $task = $this->createMock(Task::class);

        $task->method('getId')->willReturn(1);
        $task->method('getTitle')->willReturn('New Task');
        $task->method('getStatus')->willReturn(TaskStatus::PENDING);
        $task->method('getPriority')->willReturn(TaskPriority::MEDIUM);
        $task->method('getStartDate')->willReturn(null);
        $task->method('getDueDate')->willReturn(null);
        $task->method('getTags')->willReturn(new \Doctrine\Common\Collections\ArrayCollection());

        $this->taskService
            ->expects($this->once())
            ->method('createTask')
            ->willReturn($task);

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->command->execute(['title' => 'New Task'], $user);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('task_created', $result->getType());
    }

    public function testExecuteWithPriority(): void
    {
        $user = $this->createMock(User::class);
        $task = $this->createMock(Task::class);

        $task->method('getId')->willReturn(1);
        $task->method('getTitle')->willReturn('High Priority Task');
        $task->method('getStatus')->willReturn(TaskStatus::PENDING);
        $task->method('getPriority')->willReturn(TaskPriority::HIGH);
        $task->method('getStartDate')->willReturn(null);
        $task->method('getDueDate')->willReturn(null);
        $task->method('getTags')->willReturn(new \Doctrine\Common\Collections\ArrayCollection());

        $this->priorityMapper
            ->method('map')
            ->with('high')
            ->willReturn(TaskPriority::HIGH);

        $this->taskService->method('createTask')->willReturn($task);

        $result = $this->command->execute([
            'title' => 'High Priority Task',
            'priority' => 'high'
        ], $user);

        $this->assertTrue($result->isSuccess());
    }

    public function testExecuteWithoutTitleThrowsException(): void
    {
        $user = $this->createMock(User::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Task title is required');

        $this->command->execute([], $user);
    }

    public function testExecuteWithDueDate(): void
    {
        $user = $this->createMock(User::class);
        $task = $this->createMock(Task::class);
        $dueDate = new \DateTimeImmutable('2025-12-31');

        $task->method('getId')->willReturn(1);
        $task->method('getTitle')->willReturn('Task with Due');
        $task->method('getStatus')->willReturn(TaskStatus::PENDING);
        $task->method('getPriority')->willReturn(TaskPriority::MEDIUM);
        $task->method('getStartDate')->willReturn(null);
        $task->method('getDueDate')->willReturn($dueDate);
        $task->method('getTags')->willReturn(new \Doctrine\Common\Collections\ArrayCollection());

        $this->dateTimeResolver
            ->method('resolveDateRange')
            ->willReturn(['start' => null, 'due' => $dueDate]);

        $this->taskService->method('createTask')->willReturn($task);

        $result = $this->command->execute([
            'title' => 'Task with Due',
            'due_date' => 'завтра'
        ], $user);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('2025-12-31', $result->getData()['task']['dueDate']->format('Y-m-d'));
    }
}
