<?php

declare(strict_types=1);

namespace App\ValueObject;

/**
 * Тип голосовой команды
 *
 * Определяет источник команды - аудио файл или текстовый ввод
 * Следует принципу SRP - только определение типа команды
 */
enum CommandType: string
{
    /**
     * Команда пришла как аудио файл (требует транскрипции)
     */
    case VOICE_AUDIO = 'voice_audio';

    /**
     * Команда пришла как текст (транскрипция не требуется)
     */
    case VOICE_TEXT = 'voice_text';

    /**
     * Получить человекочитаемое название
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::VOICE_AUDIO => 'Голосовое аудио',
            self::VOICE_TEXT  => 'Текстовая команда',
        };
    }

    /**
     * Требуется ли транскрипция для этого типа
     */
    public function requiresTranscription(): bool
    {
        return $this === self::VOICE_AUDIO;
    }
}
