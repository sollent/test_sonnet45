<?php

declare(strict_types=1);

namespace App\ValueObject;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Распарсенная команда от LLM
 *
 * Value Object для хранения структурированной команды после парсинга Qwen 2.5
 * Содержит action, parameters и confidence согласно PROMPTS_LIBRARY
 */
final readonly class ParsedCommand implements JsonSerializable
{
    /**
     * Доступные действия согласно документации
     */
    public const ACTION_CREATE_TASK = 'create_task';

    public const ACTION_COMPLETE_TASK = 'complete_task';

    public const ACTION_UNCOMPLETE_TASK = 'uncomplete_task';

    public const ACTION_FILTER_TASKS = 'filter_tasks';

    public const ACTION_CREATE_SUBTASK = 'create_subtask';

    public const ACTION_BULK_COMPLETE = 'bulk_complete';

    public const ACTION_COMPLETE_MULTIPLE_TASKS = 'complete_multiple_tasks';

    // 🆕 Новые действия для супер сложных команд
    public const ACTION_CREATE_MULTIPLE_TASKS = 'create_multiple_tasks';

    public const ACTION_UPDATE_TASK = 'update_task';

    public const ACTION_MOVE_TASK = 'move_task';

    public const ACTION_CLARIFICATION_NEEDED = 'clarification_needed';

    public const ACTION_UNKNOWN = 'unknown';

    /**
     * @param string      $action       Действие для выполнения
     * @param array       $parameters   Параметры команды
     * @param float       $confidence   Уверенность LLM в парсинге (0.0-1.0)
     * @param string|null $originalText Оригинальный текст команды
     */
    public function __construct(
        public string $action,
        public array $parameters,
        public float $confidence,
        public ?string $originalText = null,
    ) {
        // Валидация уверенности
        if ($this->confidence < 0.0 || $this->confidence > 1.0) {
            throw new InvalidArgumentException('Confidence must be between 0.0 and 1.0');
        }

        // Валидация действия
        if (!$this->isValidAction($this->action)) {
            throw new InvalidArgumentException('Invalid action: ' . $this->action);
        }
    }

    /**
     * Преобразование в строку для логирования
     */
    public function __toString(): string
    {
        return sprintf(
            'ParsedCommand[action=%s, confidence=%.2f, executable=%s]',
            $this->action,
            $this->confidence,
            $this->isExecutable() ? 'yes' : 'no',
        );
    }

    /**
     * Создать из массива (результат парсинга JSON от LLM)
     */
    public static function fromArray(array $data, ?string $originalText = null): self
    {
        return new self(
            action: $data['action'] ?? self::ACTION_UNKNOWN,
            parameters: $data['parameters'] ?? [],
            confidence: (float) ($data['confidence'] ?? 0.0),
            originalText: $originalText,
        );
    }

    /**
     * Требуется ли уточнение от пользователя
     */
    public function needsClarification(): bool
    {
        return $this->action === self::ACTION_CLARIFICATION_NEEDED
               || $this->confidence < 0.5;
    }

    /**
     * Является ли команда выполнимой
     */
    public function isExecutable(): bool
    {
        return !$this->needsClarification()
               && $this->action !== self::ACTION_UNKNOWN
               && $this->confidence >= 0.5;
    }

    /**
     * Получить параметр по ключу с дефолтным значением
     */
    public function getParameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Получить вопрос для уточнения (если требуется)
     */
    public function getClarificationQuestion(): ?string
    {
        if (!$this->needsClarification()) {
            return null;
        }

        return $this->parameters['question'] ??
               'Не удалось точно распознать команду. Можете уточнить, что вы хотите сделать?';
    }

    /**
     * Сериализация в JSON
     */
    public function jsonSerialize(): array
    {
        return [
            'action'              => $this->action,
            'parameters'          => $this->parameters,
            'confidence'          => $this->confidence,
            'original_text'       => $this->originalText,
            'needs_clarification' => $this->needsClarification(),
            'is_executable'       => $this->isExecutable(),
        ];
    }

    /**
     * Проверить, является ли действие валидным
     */
    private function isValidAction(string $action): bool
    {
        return in_array($action, [
            self::ACTION_CREATE_TASK,
            self::ACTION_COMPLETE_TASK,
            self::ACTION_FILTER_TASKS,
            self::ACTION_CREATE_SUBTASK,
            self::ACTION_BULK_COMPLETE,
            self::ACTION_CREATE_MULTIPLE_TASKS,
            self::ACTION_UPDATE_TASK,
            self::ACTION_MOVE_TASK,
            self::ACTION_CLARIFICATION_NEEDED,
            self::ACTION_UNKNOWN,
        ], true);
    }
}
