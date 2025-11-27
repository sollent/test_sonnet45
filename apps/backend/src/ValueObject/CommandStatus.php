<?php

declare(strict_types=1);

namespace App\ValueObject;

/**
 * Статус обработки голосовой команды
 *
 * Определяет текущее состояние команды в процессе обработки
 * Переходы: PENDING → PROCESSING → EXECUTING → COMPLETED/FAILED
 */
enum CommandStatus: string
{
    /**
     * Команда создана и ожидает обработки
     */
    case PENDING = 'pending';

    /**
     * Команда обрабатывается (транскрипция/парсинг)
     */
    case PROCESSING = 'processing';

    /**
     * Команда выполняется (действие в системе)
     */
    case EXECUTING = 'executing';

    /**
     * Команда успешно выполнена
     */
    case COMPLETED = 'completed';

    /**
     * Команда завершилась с ошибкой
     */
    case FAILED = 'failed';

    /**
     * Получить человекочитаемое название
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING    => 'В ожидании',
            self::PROCESSING => 'Обрабатывается',
            self::EXECUTING  => 'Выполняется',
            self::COMPLETED  => 'Завершена',
            self::FAILED     => 'Ошибка',
        };
    }

    /**
     * Проверить, является ли статус финальным
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED], true);
    }

    /**
     * Проверить, может ли статус перейти в другой
     */
    public function canTransitionTo(self $newStatus): bool
    {
        // Если статус финальный, переход невозможен
        if ($this->isFinal()) {
            return false;
        }

        // Определяем допустимые переходы
        $allowedTransitions = match ($this) {
            self::PENDING    => [self::PROCESSING, self::FAILED],
            self::PROCESSING => [self::EXECUTING, self::FAILED],
            self::EXECUTING  => [self::COMPLETED, self::FAILED],
            self::COMPLETED, self::FAILED => [],
        };

        return in_array($newStatus, $allowedTransitions, true);
    }
}
