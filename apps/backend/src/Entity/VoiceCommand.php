<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\Database\VoiceCommandRepository;
use App\ValueObject\CommandStatus;
use App\ValueObject\CommandType;
use App\ValueObject\ParsedCommand;
use App\ValueObject\TranscriptionResult;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/**
 * Сущность голосовой команды
 *
 * Отслеживает полный жизненный цикл голосовой команды от получения до выполнения
 * Переходы состояний: pending → processing → executing → completed|failed
 * Соответствует паттерну State и принципу SRP
 */
#[ORM\Entity(repositoryClass: VoiceCommandRepository::class)]
#[ORM\Table(name: 'voice_commands')]
#[ORM\Index(columns: ['user_id', 'status'], name: 'idx_voice_commands_user_status')]
#[ORM\Index(columns: ['status', 'created_at'], name: 'idx_voice_commands_status_created')]
#[ORM\HasLifecycleCallbacks]
class VoiceCommand extends AbstractEntity
{
    /**
     * Пользователь, создавший команду
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /**
     * Текущий статус команды
     */
    #[ORM\Column(type: 'string', enumType: CommandStatus::class)]
    private CommandStatus $status;

    /**
     * Тип команды (аудио или текст)
     */
    #[ORM\Column(type: 'string', enumType: CommandType::class)]
    private CommandType $commandType;

    /**
     * URL аудио файла (для голосовых команд)
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $rawAudioUrl = null;

    /**
     * Транскрибированный текст (результат STT)
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $transcribedText = null;

    /**
     * Результат транскрипции (сериализованный TranscriptionResult)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $transcriptionResult = null;

    /**
     * Распарсенная команда (сериализованный ParsedCommand)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $parsedCommand = null;

    /**
     * Результат выполнения команды
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $executionResult = null;

    /**
     * Сообщение об ошибке (если есть)
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    /**
     * Время начала обработки
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $processingStartedAt = null;

    /**
     * Время завершения
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    /**
     * Длительность обработки в миллисекундах
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $processingDurationMs = null;

    /**
     * Метаданные (дополнительная информация)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    /**
     * Конструктор
     */
    public function __construct(
        User $user,
        CommandType $commandType,
        ?string $rawAudioUrl = null,
        ?string $transcribedText = null
    ) {
        parent::__construct();

        $this->user = $user;
        $this->commandType = $commandType;
        $this->status = CommandStatus::PENDING;

        // Валидация в зависимости от типа команды
        if ($commandType === CommandType::VOICE_AUDIO) {
            if (empty($rawAudioUrl)) {
                throw new InvalidArgumentException('Audio URL is required for voice commands');
            }
            $this->rawAudioUrl = $rawAudioUrl;
        } elseif ($commandType === CommandType::VOICE_TEXT) {
            if (empty($transcribedText)) {
                throw new InvalidArgumentException('Text is required for text commands');
            }
            $this->transcribedText = $transcribedText;
        }
    }

    /**
     * Начать обработку команды
     */
    public function startProcessing(): void
    {
        if (!$this->status->canTransitionTo(CommandStatus::PROCESSING)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot transition from %s to %s',
                $this->status->value,
                CommandStatus::PROCESSING->value
            ));
        }

        $this->status = CommandStatus::PROCESSING;
        $this->processingStartedAt = new DateTimeImmutable();
        $this->setUpdatedAtValue();
    }

    /**
     * Установить результат транскрипции
     */
    public function setTranscriptionResult(TranscriptionResult $result): void
    {
        if ($this->status !== CommandStatus::PROCESSING) {
            throw new InvalidArgumentException('Can only set transcription during processing');
        }

        $this->transcribedText = $result->text;
        $this->transcriptionResult = $result->jsonSerialize();
        $this->setUpdatedAtValue();
    }

    /**
     * Установить распарсенную команду
     */
    public function setParsedCommand(ParsedCommand $command): void
    {
        if ($this->status !== CommandStatus::PROCESSING) {
            throw new InvalidArgumentException('Can only set parsed command during processing');
        }

        $this->parsedCommand = $command->jsonSerialize();

        // Если команда требует уточнения, не переходим в executing
        if (!$command->isExecutable()) {
            $this->markAsFailed($command->getClarificationQuestion() ?? 'Command needs clarification');
            return;
        }

        // Переход к выполнению
        $this->status = CommandStatus::EXECUTING;
        $this->setUpdatedAtValue();
    }

    /**
     * Отметить команду как выполненную
     */
    public function markAsCompleted(array $result): void
    {
        if (!$this->status->canTransitionTo(CommandStatus::COMPLETED)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot transition from %s to %s',
                $this->status->value,
                CommandStatus::COMPLETED->value
            ));
        }

        $this->status = CommandStatus::COMPLETED;
        $this->executionResult = $result;
        $this->completedAt = new DateTimeImmutable();

        // Рассчитать длительность обработки
        if ($this->processingStartedAt !== null) {
            $duration = $this->completedAt->getTimestamp() - $this->processingStartedAt->getTimestamp();
            $this->processingDurationMs = $duration * 1000;
        }

        $this->setUpdatedAtValue();
    }

    /**
     * Отметить команду как проваленную
     */
    public function markAsFailed(string $errorMessage): void
    {
        if (!$this->status->canTransitionTo(CommandStatus::FAILED)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot transition from %s to %s',
                $this->status->value,
                CommandStatus::FAILED->value
            ));
        }

        $this->status = CommandStatus::FAILED;
        $this->errorMessage = $errorMessage;
        $this->completedAt = new DateTimeImmutable();

        // Рассчитать длительность обработки
        if ($this->processingStartedAt !== null) {
            $duration = $this->completedAt->getTimestamp() - $this->processingStartedAt->getTimestamp();
            $this->processingDurationMs = $duration * 1000;
        }

        $this->setUpdatedAtValue();
    }

    /**
     * Добавить метаданные
     */
    public function addMetadata(string $key, mixed $value): void
    {
        if ($this->metadata === null) {
            $this->metadata = [];
        }

        $this->metadata[$key] = $value;
        $this->setUpdatedAtValue();
    }

    /**
     * Получить TranscriptionResult объект
     */
    public function getTranscriptionResultObject(): ?TranscriptionResult
    {
        if ($this->transcriptionResult === null) {
            return null;
        }

        return TranscriptionResult::fromArray($this->transcriptionResult);
    }

    /**
     * Получить ParsedCommand объект
     */
    public function getParsedCommandObject(): ?ParsedCommand
    {
        if ($this->parsedCommand === null) {
            return null;
        }

        return ParsedCommand::fromArray($this->parsedCommand, $this->transcribedText);
    }

    /**
     * Проверить, завершена ли команда
     */
    public function isFinished(): bool
    {
        return $this->status->isFinal();
    }

    /**
     * Проверить, успешно ли выполнена команда
     */
    public function isSuccessful(): bool
    {
        return $this->status === CommandStatus::COMPLETED;
    }

    // Геттеры

    public function getUser(): User
    {
        return $this->user;
    }

    public function getStatus(): CommandStatus
    {
        return $this->status;
    }

    public function getCommandType(): CommandType
    {
        return $this->commandType;
    }

    public function getRawAudioUrl(): ?string
    {
        return $this->rawAudioUrl;
    }

    public function getTranscribedText(): ?string
    {
        return $this->transcribedText;
    }

    public function getTranscriptionResult(): ?array
    {
        return $this->transcriptionResult;
    }

    public function getParsedCommand(): ?array
    {
        return $this->parsedCommand;
    }

    public function getExecutionResult(): ?array
    {
        return $this->executionResult;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getProcessingStartedAt(): ?DateTimeImmutable
    {
        return $this->processingStartedAt;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getProcessingDurationMs(): ?int
    {
        return $this->processingDurationMs;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * Получить данные для сериализации
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'user_id' => $this->user->getId(),
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'command_type' => $this->commandType->value,
            'raw_audio_url' => $this->rawAudioUrl,
            'transcribed_text' => $this->transcribedText,
            'transcription_result' => $this->transcriptionResult,
            'parsed_command' => $this->parsedCommand,
            'execution_result' => $this->executionResult,
            'error_message' => $this->errorMessage,
            'processing_started_at' => $this->processingStartedAt?->format('c'),
            'completed_at' => $this->completedAt?->format('c'),
            'processing_duration_ms' => $this->processingDurationMs,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt?->format('c'),
            'updated_at' => $this->updatedAt?->format('c'),
        ];
    }
}