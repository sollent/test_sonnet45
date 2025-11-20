<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AI\Command\Tag;

use App\Entity\User;
use App\Repository\Database\TagRepository;
use App\Service\AI\Command\Tag\AddTagCommand;
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
 * Unit тесты для AddTagCommand
 */
class AddTagCommandTest extends TestCase
{
    private AddTagCommand $command;
    private TaskFinder $taskFinder;

    protected function setUp(): void
    {
        $this->taskFinder = $this->createMock(TaskFinder::class);

        $this->command = new AddTagCommand(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(TaskService::class),
            $this->createMock(SmartSearchService::class),
            $this->createMock(DateTimeParser::class),
            $this->createMock(LoggerInterface::class),
            $this->taskFinder,
            $this->createMock(TagRepository::class),
            $this->createMock(ResponseBuilder::class)
        );
    }

    public function testGetAction(): void
    {
        $this->assertSame(ParsedCommand::ACTION_ADD_TAG, $this->command->getAction());
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->command->supports(ParsedCommand::ACTION_ADD_TAG));
        $this->assertFalse($this->command->supports('other_action'));
    }

    public function testExecuteTaskNotFound(): void
    {
        $user = $this->createMock(User::class);
        $this->taskFinder->method('find')->willReturn(null);

        $result = $this->command->execute([
            'search' => 'Nonexistent',
            'tag_name' => 'work'
        ], $user);

        $this->assertFalse($result->isSuccess());
    }
}
