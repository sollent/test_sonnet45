<?php

declare(strict_types=1);

namespace App\Service\AI\Registry;

use App\Service\AI\Command\Contract\VoiceCommandInterface;
use App\ValueObject\ParsedCommand;
use RuntimeException;

/**
 * Реестр голосовых команд
 *
 * Следует паттерну Registry для управления командами.
 * Позволяет автоматически регистрировать команды через DI
 * и находить нужную команду по действию.
 */
class CommandRegistry
{
    /**
     * @var array<string, VoiceCommandInterface> Карта действие -> команда
     */
    private array $commands = [];

    /**
     * @var array<string, string> Карта действие -> класс команды (для отладки)
     */
    private array $commandClasses = [];

    /**
     * Регистрация команды в реестре
     *
     * @param VoiceCommandInterface $command Команда для регистрации
     */
    public function register(VoiceCommandInterface $command): void
    {
        $action = $command->getAction();

        if (isset($this->commands[$action])) {
            throw new RuntimeException(sprintf(
                'Command for action "%s" is already registered by %s',
                $action,
                $this->commandClasses[$action] ?? 'unknown',
            ));
        }

        $this->commands[$action] = $command;
        $this->commandClasses[$action] = get_class($command);
    }

    /**
     * Получить команду по действию
     *
     * @param string $action Действие из ParsedCommand
     *
     * @return VoiceCommandInterface|null Команда или null если не найдена
     */
    public function get(string $action): ?VoiceCommandInterface
    {
        return $this->commands[$action] ?? null;
    }

    /**
     * Получить команду или выбросить исключение
     *
     * @param string $action Действие из ParsedCommand
     *
     * @throws RuntimeException Если команда не найдена
     *
     * @return VoiceCommandInterface Команда
     */
    public function getOrFail(string $action): VoiceCommandInterface
    {
        $command = $this->get($action);

        if (!$command) {
            throw new RuntimeException(sprintf(
                'No command registered for action "%s". Available actions: %s',
                $action,
                implode(', ', array_keys($this->commands)),
            ));
        }

        return $command;
    }

    /**
     * Проверить, зарегистрирована ли команда для действия
     *
     * @param string $action Действие
     *
     * @return bool True если команда зарегистрирована
     */
    public function has(string $action): bool
    {
        return isset($this->commands[$action]);
    }

    /**
     * Получить все зарегистрированные действия
     *
     * @return array<string> Список действий
     */
    public function getRegisteredActions(): array
    {
        return array_keys($this->commands);
    }

    /**
     * Получить статистику реестра
     *
     * @return array{count: int, actions: array<string>, classes: array<string, string>}
     */
    public function getStats(): array
    {
        return [
            'count'   => count($this->commands),
            'actions' => $this->getRegisteredActions(),
            'classes' => $this->commandClasses,
        ];
    }

    /**
     * Обработка специальных действий (clarification_needed, unknown)
     */
    public function isSpecialAction(string $action): bool
    {
        return in_array($action, [
            ParsedCommand::ACTION_CLARIFICATION_NEEDED,
            ParsedCommand::ACTION_UNKNOWN,
        ], true);
    }
}
