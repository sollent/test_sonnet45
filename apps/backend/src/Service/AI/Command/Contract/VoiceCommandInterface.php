<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Contract;

use App\Entity\User;
use App\Service\AI\Response\CommandResponse;

/**
 * Интерфейс для всех голосовых команд
 *
 * Следует принципу Single Responsibility - каждая команда отвечает
 * только за одно действие в системе управления задачами.
 */
interface VoiceCommandInterface
{
    /**
     * Выполнить команду с заданными параметрами
     *
     * @param array $parameters Параметры команды от LLM
     * @param User $user Пользователь, выполняющий команду
     *
     * @return CommandResponse Результат выполнения команды
     * @throws \RuntimeException При ошибке выполнения команды
     */
    public function execute(array $parameters, User $user): CommandResponse;

    /**
     * Проверить, поддерживает ли команда данное действие
     *
     * @param string $action Название действия из ParsedCommand
     *
     * @return bool True если команда поддерживает действие
     */
    public function supports(string $action): bool;

    /**
     * Получить название действия, которое обрабатывает команда
     *
     * @return string Название действия (например, 'create_task')
     */
    public function getAction(): string;
}