<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AI\Registry;

use App\Service\AI\Command\Contract\VoiceCommandInterface;
use App\Service\AI\Registry\CommandRegistry;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit тесты для CommandRegistry
 *
 * Тестирует регистрацию и поиск команд в реестре.
 */
class CommandRegistryTest extends TestCase
{
    private CommandRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new CommandRegistry();
    }

    public function testRegisterAndGetCommand(): void
    {
        $command = $this->createMockCommand('test_action');

        $this->registry->register($command);

        $result = $this->registry->get('test_action');

        $this->assertSame($command, $result);
    }

    public function testGetNonExistentCommandReturnsNull(): void
    {
        $result = $this->registry->get('non_existent');

        $this->assertNull($result);
    }

    public function testGetOrFailReturnsCommand(): void
    {
        $command = $this->createMockCommand('existing_action');
        $this->registry->register($command);

        $result = $this->registry->getOrFail('existing_action');

        $this->assertSame($command, $result);
    }

    public function testGetOrFailThrowsExceptionForNonExistent(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No command registered for action "missing_action"');

        $this->registry->getOrFail('missing_action');
    }

    public function testRegisterMultipleCommands(): void
    {
        $command1 = $this->createMockCommand('action_1');
        $command2 = $this->createMockCommand('action_2');
        $command3 = $this->createMockCommand('action_3');

        $this->registry->register($command1);
        $this->registry->register($command2);
        $this->registry->register($command3);

        $this->assertSame($command1, $this->registry->get('action_1'));
        $this->assertSame($command2, $this->registry->get('action_2'));
        $this->assertSame($command3, $this->registry->get('action_3'));
    }

    public function testHasCommand(): void
    {
        $command = $this->createMockCommand('existing');
        $this->registry->register($command);

        $this->assertTrue($this->registry->has('existing'));
        $this->assertFalse($this->registry->has('non_existing'));
    }

    public function testGetRegisteredActions(): void
    {
        $command1 = $this->createMockCommand('action_1');
        $command2 = $this->createMockCommand('action_2');

        $this->registry->register($command1);
        $this->registry->register($command2);

        $actions = $this->registry->getRegisteredActions();

        $this->assertCount(2, $actions);
        $this->assertContains('action_1', $actions);
        $this->assertContains('action_2', $actions);
    }

    public function testRegisterDuplicateThrowsException(): void
    {
        $command1 = $this->createMockCommand('same_action');
        $command2 = $this->createMockCommand('same_action');

        $this->registry->register($command1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Command for action "same_action" is already registered');

        $this->registry->register($command2);
    }

    public function testEmptyRegistry(): void
    {
        $this->assertNull($this->registry->get('any_action'));
        $this->assertFalse($this->registry->has('any_action'));
        $this->assertEmpty($this->registry->getRegisteredActions());
    }

    public function testGetStats(): void
    {
        $command1 = $this->createMockCommand('action_1');
        $command2 = $this->createMockCommand('action_2');

        $this->registry->register($command1);
        $this->registry->register($command2);

        $stats = $this->registry->getStats();

        $this->assertSame(2, $stats['count']);
        $this->assertCount(2, $stats['actions']);
        $this->assertCount(2, $stats['classes']);
    }

    public function testIsSpecialAction(): void
    {
        $this->assertTrue($this->registry->isSpecialAction('clarification_needed'));
        $this->assertTrue($this->registry->isSpecialAction('unknown'));
        $this->assertFalse($this->registry->isSpecialAction('create_task'));
    }

    private function createMockCommand(string $action): VoiceCommandInterface
    {
        $mock = $this->createMock(VoiceCommandInterface::class);
        $mock->method('getAction')->willReturn($action);
        $mock->method('supports')->willReturnCallback(fn ($a) => $a === $action);

        return $mock;
    }
}
