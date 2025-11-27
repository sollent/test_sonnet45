<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AI;

use App\Entity\User;
use App\Service\AI\Command\Contract\VoiceCommandInterface;
use App\Service\AI\Registry\CommandRegistry;
use App\Service\AI\Response\CommandResponse;
use App\Service\AI\VoiceCommandExecutor;
use App\ValueObject\ParsedCommand;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Unit тесты для VoiceCommandExecutor
 *
 * Тестирует координацию выполнения команд.
 */
class VoiceCommandExecutorTest extends TestCase
{
    private VoiceCommandExecutor $executor;

    private CommandRegistry $registry;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(CommandRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->executor = new VoiceCommandExecutor(
            $this->registry,
            $this->logger,
        );
    }

    public function testExecuteDelegatestoCommand(): void
    {
        $user = $this->createMock(User::class);
        $command = $this->createMock(VoiceCommandInterface::class);

        $expectedResponse = CommandResponse::success('test', 'Success');

        $parsedCommand = new ParsedCommand(
            action: ParsedCommand::ACTION_CREATE_TASK,
            confidence: 0.9,
            parameters: ['key' => 'value'],
        );

        $this->registry
            ->method('getOrFail')
            ->with(ParsedCommand::ACTION_CREATE_TASK)
            ->willReturn($command);

        $command
            ->expects($this->once())
            ->method('execute')
            ->with(['key' => 'value'], $user)
            ->willReturn($expectedResponse);

        $result = $this->executor->execute($parsedCommand, $user);

        $this->assertSame($expectedResponse, $result);
    }

    public function testExecuteCommandNotFound(): void
    {
        $user = $this->createMock(User::class);

        $parsedCommand = new ParsedCommand(
            action: ParsedCommand::ACTION_DELETE_TASK,
            confidence: 0.9,
            parameters: [],
        );

        $this->registry
            ->method('getOrFail')
            ->willThrowException(new RuntimeException('No command registered for action "delete_task"'));

        $result = $this->executor->execute($parsedCommand, $user);

        $this->assertFalse($result->isSuccess());
    }

    public function testExecuteLogsCommandExecution(): void
    {
        $user = $this->createMock(User::class);
        $command = $this->createMock(VoiceCommandInterface::class);

        $parsedCommand = new ParsedCommand(
            action: ParsedCommand::ACTION_COMPLETE_TASK,
            confidence: 0.95,
            parameters: [],
        );

        $this->registry->method('getOrFail')->willReturn($command);
        $command->method('execute')->willReturn(CommandResponse::success('test', 'OK'));

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('info');

        $this->executor->execute($parsedCommand, $user);
    }

    public function testExecuteHandlesCommandException(): void
    {
        $user = $this->createMock(User::class);
        $command = $this->createMock(VoiceCommandInterface::class);

        $parsedCommand = new ParsedCommand(
            action: ParsedCommand::ACTION_UPDATE_TASK,
            confidence: 0.9,
            parameters: [],
        );

        $this->registry->method('getOrFail')->willReturn($command);
        $command->method('execute')->willThrowException(new Exception('Command failed'));

        $this->logger
            ->expects($this->once())
            ->method('error');

        $result = $this->executor->execute($parsedCommand, $user);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Command failed', $result->getMessage());
    }
}
