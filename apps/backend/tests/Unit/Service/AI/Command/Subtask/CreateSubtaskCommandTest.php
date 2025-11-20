<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AI\Command\Subtask;

use App\Entity\User;
use App\Service\AI\Command\Subtask\CreateSubtaskCommand;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Service\DateTimeResolver;
use App\Service\AI\Service\PriorityMapper;
use App\Service\AI\Service\TaskFinder;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit тесты для CreateSubtaskCommand
 */
class CreateSubtaskCommandTest extends TestCase
{
    private CreateSubtaskCommand $command;
    private TaskFinder $taskFinder;

    protected function setUp(): void
    {
        $this->taskFinder = $this->createMock(TaskFinder::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->command = new CreateSubtaskCommand(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(TaskService::class),
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

    public function testSupports(): void
    {
        $this->assertTrue($this->command->supports(ParsedCommand::ACTION_CREATE_SUBTASK));
        $this->assertFalse($this->command->supports('other_action'));
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
}
