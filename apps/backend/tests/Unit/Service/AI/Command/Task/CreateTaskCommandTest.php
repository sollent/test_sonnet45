<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AI\Command\Task;

use App\Repository\Database\TagRepository;
use App\Service\AI\Command\Task\CreateTaskCommand;
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

/**
 * Unit тесты для CreateTaskCommand
 */
class CreateTaskCommandTest extends TestCase
{
    private CreateTaskCommand $command;

    protected function setUp(): void
    {
        $this->command = new CreateTaskCommand(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(TaskService::class),
            $this->createMock(SmartSearchService::class),
            $this->createMock(DateTimeParser::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(TagRepository::class),
            $this->createMock(PriorityMapper::class),
            $this->createMock(DateTimeResolver::class),
            $this->createMock(ResponseBuilder::class),
        );
    }

    public function testGetAction(): void
    {
        $this->assertSame(ParsedCommand::ACTION_CREATE_TASK, $this->command->getAction());
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->command->supports(ParsedCommand::ACTION_CREATE_TASK));
        $this->assertFalse($this->command->supports('other_action'));
    }
}
