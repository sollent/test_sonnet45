<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Сообщение для асинхронной обработки голосовой команды
 *
 * Отправляется в RabbitMQ для обработки в фоновом режиме
 */
final class ProcessVoiceCommand
{
    /**
     * @param int $commandId ID команды для обработки
     * @param int $userId ID пользователя
     * @param string $type Тип команды (audio/text)
     * @param string|null $audioUrl URL аудио файла (для voice_audio)
     * @param string|null $text Текст команды (для voice_text)
     * @param array $context Дополнительный контекст
     */
    public function __construct(
        private int $commandId,
        private int $userId,
        private string $type,
        private ?string $audioUrl = null,
        private ?string $text = null,
        private array $context = []
    ) {
    }

    public function getCommandId(): int
    {
        return $this->commandId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAudioUrl(): ?string
    {
        return $this->audioUrl;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}