<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Entity\VoiceCommand;
use OpenApi\Attributes as OA;

/**
 * DTO для ответа с информацией о голосовой команде
 */
#[OA\Schema(
    title: 'Voice Command Response',
    description: 'Response with voice command details and processing status',
    type: 'object',
)]
class VoiceCommandResponse
{
    #[OA\Property(description: 'Command ID', example: 123)]
    public int $id;

    #[OA\Property(
        description: 'Current status',
        example: 'completed',
        enum: ['pending', 'processing', 'executing', 'completed', 'failed'],
    )]
    public string $status;

    #[OA\Property(description: 'Human-readable status label', example: 'Завершена')]
    public string $statusLabel;

    #[OA\Property(
        description: 'Command type',
        example: 'voice_text',
        enum: ['voice_audio', 'voice_text'],
    )]
    public string $commandType;

    #[OA\Property(description: 'Transcribed text from audio', example: 'Создай задачу купить молоко')]
    public ?string $transcribedText = null;

    #[OA\Property(
        description: 'Parsed command details',
        example: [
            'action'     => 'create_task',
            'parameters' => ['title' => 'Купить молоко', 'due_date' => 'tomorrow'],
            'confidence' => 0.95,
        ],
    )]
    public ?array $parsedCommand = null;

    #[OA\Property(
        description: 'Execution result',
        example: [
            'type'    => 'task_created',
            'success' => true,
            'message' => 'Задача "Купить молоко" успешно создана',
            'task'    => ['id' => 456, 'title' => 'Купить молоко'],
        ],
    )]
    public ?array $executionResult = null;

    #[OA\Property(description: 'Error message if failed', example: null)]
    public ?string $errorMessage = null;

    #[OA\Property(description: 'Processing duration in milliseconds', example: 1250)]
    public ?int $processingDurationMs = null;

    #[OA\Property(description: 'Creation timestamp', example: '2024-01-15T10:30:00Z')]
    public string $createdAt;

    #[OA\Property(description: 'Completion timestamp', example: '2024-01-15T10:30:02Z')]
    public ?string $completedAt = null;

    #[OA\Property(description: 'Success flag', example: true)]
    public bool $success;

    /**
     * Создание DTO из сущности
     */
    public static function fromEntity(VoiceCommand $command): self
    {
        $dto = new self();
        $dto->id = $command->getId();
        $dto->status = $command->getStatus()->value;
        $dto->statusLabel = $command->getStatus()->getLabel();
        $dto->commandType = $command->getCommandType()->value;
        $dto->transcribedText = $command->getTranscribedText();
        $dto->parsedCommand = $command->getParsedCommand();
        $dto->executionResult = $command->getExecutionResult();
        $dto->errorMessage = $command->getErrorMessage();
        $dto->processingDurationMs = $command->getProcessingDurationMs();
        $dto->createdAt = $command->getCreatedAt()->format('c');
        $dto->completedAt = $command->getCompletedAt()?->format('c');
        $dto->success = $command->isSuccessful();

        return $dto;
    }
}
