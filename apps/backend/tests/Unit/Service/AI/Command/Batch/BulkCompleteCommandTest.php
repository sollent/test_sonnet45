<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AI\Command\Batch;

use App\Entity\User;
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

/**
 * Unit тесты для BulkCompleteCommand
 */
class BulkCompleteCommandTest extends TestCase
{
    private BulkCompleteCommand $command;

    private TaskFinder $taskFinder;

    protected function setUp(): void
    {
        $this->taskFinder = $this->createMock(TaskFinder::class);

        $this->command = new BulkCompleteCommand(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(TaskService::class),
            $this->createMock(SmartSearchService::class),
            $this->createMock(DateTimeParser::class),
            $this->createMock(LoggerInterface::class),
            $this->taskFinder,
            $this->createMock(ResponseBuilder::class),
        );
    }

    public function testGetAction(): void
    {
        $this->assertSame(ParsedCommand::ACTION_BULK_COMPLETE, $this->command->getAction());
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->command->supports(ParsedCommand::ACTION_BULK_COMPLETE));
        $this->assertFalse($this->command->supports('other_action'));
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
}
