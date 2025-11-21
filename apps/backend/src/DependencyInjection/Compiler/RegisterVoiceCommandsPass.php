<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Service\AI\Registry\CommandRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler Pass для автоматической регистрации голосовых команд
 *
 * Находит все сервисы с тегом 'voice.command' и регистрирует их
 * в CommandRegistry автоматически при компиляции контейнера.
 *
 * Следует паттерну Compiler Pass из Symfony.
 */
class RegisterVoiceCommandsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Проверяем, что реестр существует
        if (!$container->has(CommandRegistry::class)) {
            return;
        }

        $registryDefinition = $container->findDefinition(CommandRegistry::class);

        // Находим все сервисы с тегом 'voice.command'
        $taggedServices = $container->findTaggedServiceIds('voice.command');

        foreach ($taggedServices as $id => $tags) {
            // Добавляем вызов метода register для каждой команды
            $registryDefinition->addMethodCall('register', [new Reference($id)]);
        }

        // Логирование для отладки
        $container->log($this, sprintf(
            'Registered %d voice commands in CommandRegistry',
            count($taggedServices),
        ));
    }
}
