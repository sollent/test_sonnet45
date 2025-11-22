<?php

declare(strict_types=1);

namespace App\Service\WebSocket;

use App\ValueObject\ParsedCommand;

/**
 * Маппер для определения какие команды требуют WebSocket событий
 * Использует константы из ParsedCommand для type safety
 */
class CommandEventMapper
{
    /**
     * Конфигурация WebSocket событий для каждой команды
     * Ключи - константы ACTION_* из ParsedCommand
     */
    private const COMMAND_EVENTS = [
        // ===== КОМАНДЫ СОЗДАНИЯ =====
        ParsedCommand::ACTION_CREATE_TASK => [
            'publish' => true,
            'event' => 'task.created',
            'includeStats' => true,
            'includeEntity' => true,
        ],
        ParsedCommand::ACTION_CREATE_MULTIPLE_TASKS => [
            'publish' => true,
            'event' => 'tasks.created',
            'includeStats' => true,
            'includeEntity' => true,
        ],
        ParsedCommand::ACTION_CREATE_SUBTASK => [
            'publish' => true,
            'event' => 'subtask.created',
            'includeStats' => true,
            'includeEntity' => true,
        ],
        ParsedCommand::ACTION_CREATE_MULTIPLE_SUBTASKS => [
            'publish' => true,
            'event' => 'subtasks.created',
            'includeStats' => true,
            'includeEntity' => true,
        ],

        // ===== КОМАНДЫ ВЫПОЛНЕНИЯ =====
        ParsedCommand::ACTION_COMPLETE_TASK => [
            'publish' => true,
            'event' => 'task.completed',
            'includeStats' => true,
            'includeEntity' => true,
        ],
        ParsedCommand::ACTION_COMPLETE_MULTIPLE_TASKS => [
            'publish' => true,
            'event' => 'tasks.completed',
            'includeStats' => true,
            'includeEntity' => true,
        ],
        ParsedCommand::ACTION_COMPLETE_SUBTASKS => [
            'publish' => true,
            'event' => 'subtasks.completed',
            'includeStats' => true,
            'includeEntity' => true,
        ],
        ParsedCommand::ACTION_BULK_COMPLETE => [
            'publish' => true,
            'event' => 'tasks.bulk_completed',
            'includeStats' => true,
            'includeEntity' => false,
        ],
        ParsedCommand::ACTION_UNCOMPLETE_TASK => [
            'publish' => true,
            'event' => 'task.uncompleted',
            'includeStats' => true,
            'includeEntity' => true,
        ],
        ParsedCommand::ACTION_UNCOMPLETE_MULTIPLE_TASKS => [
            'publish' => true,
            'event' => 'tasks.uncompleted',
            'includeStats' => true,
            'includeEntity' => true,
        ],

        // ===== КОМАНДЫ ОБНОВЛЕНИЯ =====
        ParsedCommand::ACTION_UPDATE_TASK => [
            'publish' => true,
            'event' => 'task.updated',
            'includeStats' => false,
            'includeEntity' => true,
        ],
        ParsedCommand::ACTION_BULK_UPDATE => [
            'publish' => true,
            'event' => 'tasks.bulk_updated',
            'includeStats' => true,
            'includeEntity' => false,
        ],
        ParsedCommand::ACTION_MOVE_TASK => [
            'publish' => true,
            'event' => 'task.moved',
            'includeStats' => false,
            'includeEntity' => true,
        ],
        ParsedCommand::ACTION_BULK_MOVE => [
            'publish' => true,
            'event' => 'tasks.bulk_moved',
            'includeStats' => false,
            'includeEntity' => false,
        ],

        // ===== КОМАНДЫ УДАЛЕНИЯ =====
        ParsedCommand::ACTION_DELETE_TASK => [
            'publish' => true,
            'event' => 'task.deleted',
            'includeStats' => true,
            'includeEntity' => true,
        ],
        ParsedCommand::ACTION_DELETE_MULTIPLE_TASKS => [
            'publish' => true,
            'event' => 'tasks.deleted',
            'includeStats' => true,
            'includeEntity' => true,
        ],
        ParsedCommand::ACTION_BULK_DELETE => [
            'publish' => true,
            'event' => 'tasks.bulk_deleted',
            'includeStats' => true,
            'includeEntity' => false,
        ],
        ParsedCommand::ACTION_CLEANUP_COMPLETED => [
            'publish' => true,
            'event' => 'tasks.cleanup_completed',
            'includeStats' => true,
            'includeEntity' => false,
        ],

        // ===== КОМАНДЫ ДУБЛИРОВАНИЯ И КОНВЕРТАЦИИ =====
        ParsedCommand::ACTION_DUPLICATE_TASK => [
            'publish' => true,
            'event' => 'task.duplicated',
            'includeStats' => true,
            'includeEntity' => true,
        ],
        ParsedCommand::ACTION_CONVERT_SUBTASK_TO_TASK => [
            'publish' => true,
            'event' => 'subtask.converted',
            'includeStats' => true,
            'includeEntity' => true,
        ],

        // ===== КОМАНДЫ ТЕГОВ =====
        ParsedCommand::ACTION_ADD_TAG => [
            'publish' => true,
            'event' => 'task.tag_added',
            'includeStats' => false,
            'includeEntity' => true,
        ],
        ParsedCommand::ACTION_REMOVE_TAG => [
            'publish' => true,
            'event' => 'task.tag_removed',
            'includeStats' => false,
            'includeEntity' => true,
        ],

        // ===== КОМАНДЫ ОПИСАНИЯ =====
        ParsedCommand::ACTION_SET_DESCRIPTION => [
            'publish' => true,
            'event' => 'task.description_updated',
            'includeStats' => false,
            'includeEntity' => true,
        ],

        // ===== КОМАНДЫ БЕЗ ПУБЛИКАЦИИ =====
        ParsedCommand::ACTION_FILTER_TASKS => [
            'publish' => false,
            'event' => null,
            'includeStats' => false,
            'includeEntity' => false,
        ],
        ParsedCommand::ACTION_CLARIFICATION_NEEDED => [
            'publish' => false,
            'event' => null,
            'includeStats' => false,
            'includeEntity' => false,
        ],
        ParsedCommand::ACTION_UNKNOWN => [
            'publish' => false,
            'event' => null,
            'includeStats' => false,
            'includeEntity' => false,
        ],
    ];

    /**
     * Получить конфигурацию события для команды
     *
     * @return array{publish: bool, event: string|null, includeStats: bool, includeEntity: bool}|null
     */
    public function getEventConfig(string $action): ?array
    {
        return self::COMMAND_EVENTS[$action] ?? null;
    }

    /**
     * Проверить, требует ли команда публикации события
     */
    public function shouldPublish(string $action): bool
    {
        $config = $this->getEventConfig($action);

        return $config !== null && $config['publish'] === true;
    }

    /**
     * Получить название события для команды
     */
    public function getEventName(string $action): ?string
    {
        $config = $this->getEventConfig($action);

        return $config['event'] ?? null;
    }

    /**
     * Проверить, нужно ли включать статистику в событие
     */
    public function shouldIncludeStats(string $action): bool
    {
        $config = $this->getEventConfig($action);

        return $config !== null && $config['includeStats'] === true;
    }

    /**
     * Проверить, нужно ли включать сущность в событие
     */
    public function shouldIncludeEntity(string $action): bool
    {
        $config = $this->getEventConfig($action);

        return $config !== null && $config['includeEntity'] === true;
    }

    /**
     * Получить все поддерживаемые команды
     *
     * @return array<string>
     */
    public function getSupportedActions(): array
    {
        return array_keys(self::COMMAND_EVENTS);
    }

    /**
     * Получить все команды, требующие публикации
     *
     * @return array<string>
     */
    public function getPublishableActions(): array
    {
        return array_keys(
            array_filter(
                self::COMMAND_EVENTS,
                fn (array $config) => $config['publish'] === true
            )
        );
    }
}
