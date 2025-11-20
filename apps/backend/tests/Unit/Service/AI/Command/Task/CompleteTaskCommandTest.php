<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AI\Command\Task;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatus;
use App\Service\AI\Command\Task\CompleteTaskCommand;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Service\TaskFinder;
use App\Service\AI\SmartSearchService;
use App\Service\AI\Response\ResponseBuilder;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CompleteTaskCommandTest extends TestCase
{
    private CompleteTaskCommand $command;
    private EntityManagerInterface $entityManager;
    private TaskFinder $taskFinder;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->taskFinder = $this->createMock(TaskFinder::class);

        $this->command = new CompleteTaskCommand(
            $this->entityManager,
            $this->createMock(TaskService::class),
            $this->createMock(SmartSearchService::class),
            $this->createMock(DateTimeParser::class),
            $this->createMock(LoggerInterface::class),
            $this->taskFinder,
            $this->createMock(ResponseBuilder::class)
        );
    }

    public function testGetAction(): void
    {
        $this->assertSame(ParsedCommand::ACTION_COMPLETE_TASK, $this->command->getAction());
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->command->supports(ParsedCommand::ACTION_COMPLETE_TASK));
        $this->assertFalse($this->command->supports('other_action'));
    }

    public function testExecuteCompletesTask(): void
    {
        $user = $this->createMock(User::class);
        $task = $this->createMock(Task::class);

        $task->method('getId')->willReturn(1);
        $task->method('getTitle')->willReturn('Test Task');
        $task->method('getStatus')->willReturn(TaskStatus::PENDING);

        $this->taskFinder->method('find')->willReturn($task);
        $task->expects($this->once())->method('setStatus')->with(TaskStatus::COMPLETED);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->command->execute(['search' => 'Test Task'], $user);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('task_completed', $result->getType());
    }

    public function testExecuteTaskNotFound(): void
    {
        $user = $this->createMock(User::class);
        $this->taskFinder->method('find')->willReturn(null);

        $result = $this->command->execute(['search' => 'Non Existent'], $user);

        $this->assertFalse($result->isSuccess());
        $this->assertSame('task_not_found', $result->getType());
    }
}
