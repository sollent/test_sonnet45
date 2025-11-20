<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AI\Command\Tag;

use App\Entity\Tag;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatus;
use App\Service\AI\Command\Tag\AddTagCommand;
use App\Repository\Database\TagRepository;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Response\ResponseBuilder;
use App\Service\AI\Service\TaskFinder;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AddTagCommandTest extends TestCase
{
    private AddTagCommand $command;
    private TaskFinder $taskFinder;
    private SmartSearchService $searchService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->taskFinder = $this->createMock(TaskFinder::class);
        $this->searchService = $this->createMock(SmartSearchService::class);

        $this->command = new AddTagCommand(
            $this->entityManager,
            $this->createMock(TaskService::class),
            $this->searchService,
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

    public function testExecuteAddsNewTag(): void
    {
        $user = $this->createMock(User::class);
        $task = $this->createMock(Task::class);
        $tag = $this->createMock(Tag::class);

        $task->method('getId')->willReturn(1);
        $task->method('getTitle')->willReturn('Test Task');
        $task->method('getTags')->willReturn(new ArrayCollection());

        $tag->method('getName')->willReturn('work');

        $this->taskFinder->method('find')->willReturn($task);
        $this->searchService->method('findOrCreateTag')->willReturn($tag);

        $task->expects($this->once())->method('addTag')->with($tag);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->command->execute([
            'search' => 'Test Task',
            'tag_name' => 'work'
        ], $user);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('tag_added', $result->getType());
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
        $this->assertSame('task_not_found', $result->getType());
    }

    public function testExecuteTagAlreadyExists(): void
    {
        $user = $this->createMock(User::class);
        $task = $this->createMock(Task::class);
        $tag = $this->createMock(Tag::class);

        $task->method('getId')->willReturn(1);
        $task->method('getTitle')->willReturn('Test Task');
        
        // Задача уже имеет этот тег
        $existingTags = new ArrayCollection([$tag]);
        $task->method('getTags')->willReturn($existingTags);
        
        $tag->method('getName')->willReturn('work');

        $this->taskFinder->method('find')->willReturn($task);
        $this->searchService->method('findOrCreateTag')->willReturn($tag);

        $result = $this->command->execute([
            'search' => 'Test Task',
            'tag_name' => 'work'
        ], $user);

        // Должен вернуть успех, но с информацией что тег уже есть
        $this->assertTrue($result->isSuccess());
    }
}
