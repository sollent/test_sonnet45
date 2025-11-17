<?php

declare(strict_types=1);

namespace App\Dto\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO для запроса обработки голосовой команды
 *
 * Принимает либо URL аудио файла, либо текст команды
 */
#[OA\Schema(
    title: 'Voice Command Request',
    description: 'Request payload for processing voice or text command',
    required: [],
    type: 'object'
)]
class VoiceCommandRequest
{
    #[OA\Property(
        description: 'URL of the audio file for voice command',
        example: 'https://example.com/audio/command.mp3'
    )]
    #[Assert\Url(message: 'Invalid audio URL format')]
    public ?string $audioUrl = null;

    #[OA\Property(
        description: 'Text command (alternative to audio)',
        example: 'Создай задачу купить молоко завтра'
    )]
    #[Assert\Length(
        min: 3,
        max: 500,
        minMessage: 'Command text must be at least {{ limit }} characters',
        maxMessage: 'Command text cannot exceed {{ limit }} characters'
    )]
    public ?string $text = null;

    #[OA\Property(
        description: 'Language code for better recognition (optional)',
        example: 'ru',
        enum: ['ru', 'en', 'uk']
    )]
    #[Assert\Choice(choices: ['ru', 'en', 'uk'], message: 'Invalid language code')]
    public string $language = 'ru';

    #[OA\Property(
        description: 'Additional context for command processing (optional)',
        example: ['project_id' => 123, 'context' => 'work']
    )]
    public ?array $context = null;

    #[Assert\Callback]
    public function validate(\Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        // Проверяем, что указан либо audioUrl, либо text
        if ($this->audioUrl === null && $this->text === null) {
            $context->buildViolation('Either audioUrl or text must be provided')
                ->atPath('audioUrl')
                ->addViolation();
        }

        // Проверяем, что не указаны оба параметра одновременно
        if ($this->audioUrl !== null && $this->text !== null) {
            $context->buildViolation('Cannot provide both audioUrl and text')
                ->atPath('audioUrl')
                ->addViolation();
        }
    }
}