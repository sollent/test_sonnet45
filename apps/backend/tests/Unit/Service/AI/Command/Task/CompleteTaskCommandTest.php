<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AI\Command\Task;

use App\Entity\Task;
use App\Entity\User;
use App\Service\AI\Command\Task\CompleteTaskCommand;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Response\CommandResponse;
use App\Service\AI\Response\ResponseBuilder;
use App\Service\AI\Service\TaskFinder;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit тест для CompleteTaskCommand
 *
 * Демонстрирует преимущества новой архитектуры:
 * - Легко тестировать изолированные команды
 * - Можно замокать только нужные зависимости
 * - Четкое разделение ответственности
 */
class CompleteTaskCommandTest extends TestCase
{
    private CompleteTaskCommand $command;
    private MockObject|TaskFinder $taskFinder;
    private MockObject|TaskService $taskService;
    private MockObject|ResponseBuilder $responseBuilder;
    private MockObject|User $user;

    protected function setUp(): void
    {
        // Создаем моки для зависимостей
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->taskService = $this->createMock(TaskService::class);
        $searchService = $this->createMock(SmartSearchService::class);
        $dateTimeParser = $this->createMock(DateTimeParser::class);
        $logger = $this->createMock(LoggerInterface::class);
        $this->taskFinder = $this->createMock(TaskFinder::class);
        $this->responseBuilder = $this->createMock(ResponseBuilder::class);

        $this->command = new CompleteTaskCommand(
            $entityManager,
            $this->taskService,
            $searchService,
            $dateTimeParser,
            $logger,
            $this->taskFinder,
            $this->responseBuilder
        );

        $this->user = $this->createMock(User::class);
    }

    /**
     * Тест успешного завершения задачи
     */
    public function testExecuteSuccess(): void
    {
        // Arrange
        $parameters = ['search' => 'Написать отчет'];
        $task = $this->createMock(Task::class);
        $completedTask = $this->createMock(Task::class);
        $expectedResponse = CommandResponse::success(
            'task_completed',
            'Задача завершена',
            []
        );

        $this->taskFinder->expects($this->once())
            ->method('extractSearch')
            ->with($parameters)
            ->willReturn('Написать отчет');

        $this->taskFinder->expects($this->once())
            ->method('find')
            ->with('Написать отчет', $this->user)
            ->willReturn($task);

        $this->taskService->expects($this->once())
            ->method('completeTask')
            ->with($task, $this->user)
            ->willReturn($completedTask);

        $this->responseBuilder->expects($this->once())
            ->method('taskCompleted')
            ->with($completedTask)
            ->willReturn($expectedResponse);

        // Act
        $response = $this->command->execute($parameters, $this->user);

        // Assert
        $this->assertInstanceOf(CommandResponse::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertEquals('task_completed', $response->getType());
    }

    /**
     * Тест когда задача не найдена
     */
    public function testExecuteTaskNotFound(): void
    {
        // Arrange
        $parameters = ['search' => 'Несуществующая задача'];
        $expectedResponse = CommandResponse::failure(
            'task_not_found',
            'Задача не найдена',
            []
        );

        $this->taskFinder->expects($this->once())
            ->method('extractSearch')
            ->with($parameters)
            ->willReturn('Несуществующая задача');

        $this->taskFinder->expects($this->once())
            ->method('find')
            ->with('Несуществующая задача', $this->user)
            ->willReturn(null);

        $this->responseBuilder->expects($this->once())
            ->method('taskNotFound')
            ->with('Несуществующая задача')
            ->willReturn($expectedResponse);

        $this->taskService->expects($this->never())
            ->method('completeTask');

        // Act
        $response = $this->command->execute($parameters, $this->user);

        // Assert
        $this->assertInstanceOf(CommandResponse::class, $response);
        $this->assertFalse($response->isSuccess());
        $this->assertEquals('task_not_found', $response->getType());
    }

    /**
     * Тест поддерживаемого действия
     */
    public function testSupports(): void
    {
        $this->assertTrue($this->command->supports(ParsedCommand::ACTION_COMPLETE_TASK));
        $this->assertFalse($this->command->supports(ParsedCommand::ACTION_CREATE_TASK));
    }

    /**
     * Тест получения действия
     */
    public function testGetAction(): void
    {
        $this->assertEquals(ParsedCommand::ACTION_COMPLETE_TASK, $this->command->getAction());
    }

    /**
     * Тест валидации с отсутствующим search параметром
     */
    public function testExecuteWithMissingSearchParameter(): void
    {
        // Arrange
        $parameters = [];

        $this->taskFinder->expects($this->once())
            ->method('extractSearch')
            ->with($parameters)
            ->willReturn(null);

        // Act
        $response = $this->command->execute($parameters, $this->user);

        // Assert
        $this->assertFalse($response->isSuccess());
        $this->assertEquals('error', $response->getType());
        $this->assertStringContainsString('Search query is required', $response->getMessage());
    }
}