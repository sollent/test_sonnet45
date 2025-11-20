<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\AI;

use App\Service\AI\Registry\CommandRegistry;
use App\Service\AI\VoiceCommandExecutor;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration тесты для VoiceCommandExecutor
 *
 * Тестирует что сервис правильно зарегистрирован в контейнере.
 */
class VoiceCommandExecutorTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testServiceIsRegistered(): void
    {
        $container = static::getContainer();

        $executor = $container->get(VoiceCommandExecutor::class);

        $this->assertInstanceOf(VoiceCommandExecutor::class, $executor);
    }

    public function testCommandRegistryIsRegistered(): void
    {
        $container = static::getContainer();

        $registry = $container->get(CommandRegistry::class);

        $this->assertInstanceOf(CommandRegistry::class, $registry);
    }
}
