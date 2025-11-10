и и# 📊 Phase 2.1: Domain Model & Architecture

> **Document Version**: 1.0.0
> **Last Updated**: 2025-11-08
> **Estimated Time**: 1 day
> **Complexity**: HIGH
> **Prerequisites**: Understanding of DDD, Symfony, SOLID principles

## 📋 Table of Contents

1. [Domain-Driven Design Overview](#domain-driven-design-overview)
2. [Entities & Value Objects](#entities--value-objects)
3. [Aggregates & Repositories](#aggregates--repositories)
4. [Domain Events](#domain-events)
5. [Database Schema](#database-schema)
6. [Doctrine Configuration](#doctrine-configuration)
7. [Business Rules](#business-rules)
8. [Code Examples](#code-examples)

---

## 🏗️ Domain-Driven Design Overview

### Bounded Contexts

```yaml
Voice AI Assistant Domain:
  Core Domain:
    - Voice Command Processing
    - Command Execution
    - Natural Language Understanding

  Supporting Subdomains:
    - User Management (existing)
    - Task Management (existing)
    - Analytics (existing)

  Generic Subdomains:
    - Authentication
    - Notification
    - File Storage

Ubiquitous Language:
  - Voice Command: Audio or text input from user
  - Transcription: Converted audio to text
  - Parsed Command: Structured command from LLM
  - Command Result: Outcome of command execution
  - Command Handler: Service that executes commands
  - Voice Session: Continuous interaction context
```

### Architecture Layers

```
┌─────────────────────────────────────┐
│         Presentation Layer          │
│    (Controllers, API Endpoints)     │
├─────────────────────────────────────┤
│        Application Layer            │
│  (Command Handlers, DTOs, Events)   │
├─────────────────────────────────────┤
│          Domain Layer               │
│  (Entities, Value Objects, Rules)   │
├─────────────────────────────────────┤
│       Infrastructure Layer          │
│  (Repositories, External Services)  │
└─────────────────────────────────────┘
```

---

## 🎯 Entities & Value Objects

### VoiceCommand Entity

```php
<?php
// File: apps/backend/src/Entity/VoiceCommand.php

namespace App\Entity;

use App\Repository\VoiceCommandRepository;
use App\ValueObject\CommandStatus;
use App\ValueObject\CommandType;
use App\ValueObject\TranscriptionResult;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: VoiceCommandRepository::class)]
#[ORM\Table(name: 'voice_commands')]
#[ORM\Index(name: 'idx_voice_command_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_voice_command_status', columns: ['status'])]
#[ORM\Index(name: 'idx_voice_command_created', columns: ['created_at'])]
#[ORM\HasLifecycleCallbacks]
class VoiceCommand
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'voiceCommands')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 50, enumType: CommandType::class)]
    private CommandType $type;

    #[ORM\Column(type: 'string', length: 50, enumType: CommandStatus::class)]
    private CommandStatus $status;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $audioFilePath = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $audioDuration = null; // in milliseconds

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rawText = null;

    #[ORM\Embedded(class: TranscriptionResult::class, columnPrefix: 'transcription_')]
    private ?TranscriptionResult $transcription = null;

    #[ORM\Embedded(class: ParsedCommand::class, columnPrefix: 'parsed_')]
    private ?ParsedCommand $parsedCommand = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $commandResult = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $source; // 'web', 'telegram', 'api', etc.

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $sessionId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $processingTimeMs = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $confidence = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct(
        User $user,
        CommandType $type,
        string $source
    ) {
        $this->id = Uuid::v4();
        $this->user = $user;
        $this->type = $type;
        $this->source = $source;
        $this->status = CommandStatus::PENDING;
        $this->createdAt = new \DateTimeImmutable();
        $this->metadata = [];
    }

    // Domain logic methods

    public function startProcessing(): void
    {
        if (!$this->status->isPending()) {
            throw new \DomainException('Can only start processing pending commands');
        }

        $this->status = CommandStatus::PROCESSING;
        $this->processedAt = new \DateTimeImmutable();
    }

    public function setTranscription(TranscriptionResult $transcription): void
    {
        if (!$this->status->isProcessing()) {
            throw new \DomainException('Can only set transcription for processing commands');
        }

        $this->transcription = $transcription;
        $this->rawText = $transcription->getText();
        $this->status = CommandStatus::TRANSCRIBED;
    }

    public function setParsedCommand(ParsedCommand $parsedCommand): void
    {
        if (!$this->status->isTranscribed() && !$this->status->isProcessing()) {
            throw new \DomainException('Can only parse transcribed or processing commands');
        }

        $this->parsedCommand = $parsedCommand;
        $this->confidence = $parsedCommand->getConfidence();
        $this->status = CommandStatus::PARSED;
    }

    public function markAsExecuting(): void
    {
        if (!$this->status->isParsed()) {
            throw new \DomainException('Can only execute parsed commands');
        }

        $this->status = CommandStatus::EXECUTING;
    }

    public function complete(array $result): void
    {
        if (!$this->status->isExecuting()) {
            throw new \DomainException('Can only complete executing commands');
        }

        $this->commandResult = $result;
        $this->status = CommandStatus::COMPLETED;
        $this->completedAt = new \DateTimeImmutable();

        if ($this->processedAt) {
            $this->processingTimeMs = (int)(
                ($this->completedAt->getTimestamp() - $this->processedAt->getTimestamp()) * 1000
            );
        }
    }

    public function fail(string $errorMessage): void
    {
        $this->status = CommandStatus::FAILED;
        $this->errorMessage = $errorMessage;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function isRetryable(): bool
    {
        return $this->status->isFailed()
            && $this->getRetryCount() < 3
            && !$this->isCriticalError();
    }

    private function isCriticalError(): bool
    {
        if (!$this->errorMessage) {
            return false;
        }

        $criticalErrors = [
            'authentication',
            'authorization',
            'invalid_command',
            'malformed_input'
        ];

        foreach ($criticalErrors as $error) {
            if (stripos($this->errorMessage, $error) !== false) {
                return true;
            }
        }

        return false;
    }

    public function getRetryCount(): int
    {
        return $this->metadata['retry_count'] ?? 0;
    }

    public function incrementRetryCount(): void
    {
        $this->metadata['retry_count'] = $this->getRetryCount() + 1;
        $this->metadata['last_retry_at'] = (new \DateTimeImmutable())->format('c');
    }

    // Getters

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getType(): CommandType
    {
        return $this->type;
    }

    public function getStatus(): CommandStatus
    {
        return $this->status;
    }

    public function getTranscription(): ?TranscriptionResult
    {
        return $this->transcription;
    }

    public function getParsedCommand(): ?ParsedCommand
    {
        return $this->parsedCommand;
    }

    public function getCommandResult(): ?array
    {
        return $this->commandResult;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getProcessingTimeMs(): ?int
    {
        return $this->processingTimeMs;
    }

    public function getConfidence(): ?float
    {
        return $this->confidence;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function validateInvariants(): void
    {
        // Business rule: Audio commands must have audio file
        if ($this->type === CommandType::AUDIO && !$this->audioFilePath) {
            throw new \DomainException('Audio commands must have an audio file');
        }

        // Business rule: Completed commands must have result
        if ($this->status === CommandStatus::COMPLETED && !$this->commandResult) {
            throw new \DomainException('Completed commands must have a result');
        }

        // Business rule: Failed commands must have error message
        if ($this->status === CommandStatus::FAILED && !$this->errorMessage) {
            throw new \DomainException('Failed commands must have an error message');
        }
    }
}
```

### Value Objects

```php
<?php
// File: apps/backend/src/ValueObject/CommandType.php

namespace App\ValueObject;

enum CommandType: string
{
    case AUDIO = 'audio';
    case TEXT = 'text';
    case GESTURE = 'gesture';  // Future: gesture-based commands
    case SCHEDULED = 'scheduled';  // Scheduled voice commands

    public function requiresTranscription(): bool
    {
        return $this === self::AUDIO;
    }

    public function supportsOfflineProcessing(): bool
    {
        return in_array($this, [self::TEXT, self::SCHEDULED], true);
    }
}
```

```php
<?php
// File: apps/backend/src/ValueObject/CommandStatus.php

namespace App\ValueObject;

enum CommandStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case TRANSCRIBED = 'transcribed';
    case PARSED = 'parsed';
    case EXECUTING = 'executing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isProcessing(): bool
    {
        return $this === self::PROCESSING;
    }

    public function isTranscribed(): bool
    {
        return $this === self::TRANSCRIBED;
    }

    public function isParsed(): bool
    {
        return $this === self::PARSED;
    }

    public function isExecuting(): bool
    {
        return $this === self::EXECUTING;
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::CANCELLED], true);
    }

    public function canTransitionTo(self $newStatus): bool
    {
        $transitions = [
            self::PENDING->value => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING->value => [self::TRANSCRIBED, self::PARSED, self::FAILED],
            self::TRANSCRIBED->value => [self::PARSED, self::FAILED],
            self::PARSED->value => [self::EXECUTING, self::FAILED],
            self::EXECUTING->value => [self::COMPLETED, self::FAILED],
            self::COMPLETED->value => [],
            self::FAILED->value => [self::PENDING], // Allow retry
            self::CANCELLED->value => [],
        ];

        return in_array($newStatus, $transitions[$this->value] ?? [], true);
    }
}
```

```php
<?php
// File: apps/backend/src/ValueObject/TranscriptionResult.php

namespace App\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class TranscriptionResult
{
    #[ORM\Column(type: 'text')]
    private string $text;

    #[ORM\Column(type: 'string', length: 10)]
    private string $language;

    #[ORM\Column(type: 'float')]
    private float $confidence;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $segments;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $model;

    public function __construct(
        string $text,
        string $language,
        float $confidence,
        ?array $segments = null,
        ?string $model = null
    ) {
        if (empty($text)) {
            throw new \InvalidArgumentException('Transcription text cannot be empty');
        }

        if ($confidence < 0 || $confidence > 1) {
            throw new \InvalidArgumentException('Confidence must be between 0 and 1');
        }

        $this->text = $text;
        $this->language = $language;
        $this->confidence = $confidence;
        $this->segments = $segments;
        $this->model = $model;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getConfidence(): float
    {
        return $this->confidence;
    }

    public function getSegments(): ?array
    {
        return $this->segments;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function isHighConfidence(): bool
    {
        return $this->confidence >= 0.8;
    }

    public function requiresManualReview(): bool
    {
        return $this->confidence < 0.5;
    }
}
```

```php
<?php
// File: apps/backend/src/ValueObject/ParsedCommand.php

namespace App\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class ParsedCommand
{
    #[ORM\Column(type: 'string', length: 100)]
    private string $action;

    #[ORM\Column(type: 'json')]
    private array $parameters;

    #[ORM\Column(type: 'float')]
    private float $confidence;

    #[ORM\Column(type: 'boolean')]
    private bool $requiresConfirmation;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $entities;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $intent;

    public function __construct(
        string $action,
        array $parameters,
        float $confidence,
        bool $requiresConfirmation = false,
        ?array $entities = null,
        ?string $intent = null
    ) {
        if (empty($action)) {
            throw new \InvalidArgumentException('Action cannot be empty');
        }

        if ($confidence < 0 || $confidence > 1) {
            throw new \InvalidArgumentException('Confidence must be between 0 and 1');
        }

        $this->action = $action;
        $this->parameters = $parameters;
        $this->confidence = $confidence;
        $this->requiresConfirmation = $requiresConfirmation;
        $this->entities = $entities;
        $this->intent = $intent;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    public function getConfidence(): float
    {
        return $this->confidence;
    }

    public function requiresConfirmation(): bool
    {
        return $this->requiresConfirmation;
    }

    public function getEntities(): ?array
    {
        return $this->entities;
    }

    public function getIntent(): ?string
    {
        return $this->intent;
    }

    public function isValid(): bool
    {
        // Validate based on action type
        $requiredParams = $this->getRequiredParametersForAction();

        foreach ($requiredParams as $param) {
            if (!isset($this->parameters[$param])) {
                return false;
            }
        }

        return true;
    }

    private function getRequiredParametersForAction(): array
    {
        return match($this->action) {
            'create_task' => ['title'],
            'update_task' => ['task_id', 'updates'],
            'complete_task' => ['task_id'],
            'filter_tasks' => ['filters'],
            'create_subtask' => ['parent_task_id', 'title'],
            default => []
        };
    }
}
```

### UserTelegramLink Entity

```php
<?php
// File: apps/backend/src/Entity/UserTelegramLink.php

namespace App\Entity;

use App\Repository\UserTelegramLinkRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserTelegramLinkRepository::class)]
#[ORM\Table(name: 'user_telegram_links')]
#[ORM\UniqueConstraint(name: 'uniq_telegram_id', columns: ['telegram_id'])]
class UserTelegramLink
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'telegramLink')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'bigint')]
    private int $telegramId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $telegramUsername = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $languageCode = null;

    #[ORM\Column(type: 'string', length: 64)]
    private string $linkToken;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'boolean')]
    private bool $notificationsEnabled = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $preferences = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $linkedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $unlinkedAt = null;

    public function __construct(User $user, int $telegramId)
    {
        $this->id = Uuid::v4();
        $this->user = $user;
        $this->telegramId = $telegramId;
        $this->linkToken = bin2hex(random_bytes(32));
        $this->linkedAt = new \DateTimeImmutable();
        $this->preferences = [];
    }

    public function updateTelegramInfo(
        ?string $username,
        ?string $firstName,
        ?string $lastName,
        ?string $languageCode
    ): void {
        $this->telegramUsername = $username;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->languageCode = $languageCode;
    }

    public function recordUsage(): void
    {
        $this->lastUsedAt = new \DateTimeImmutable();
    }

    public function unlink(): void
    {
        if (!$this->isActive) {
            throw new \DomainException('Link is already inactive');
        }

        $this->isActive = false;
        $this->unlinkedAt = new \DateTimeImmutable();
    }

    public function reactivate(): void
    {
        if ($this->isActive) {
            throw new \DomainException('Link is already active');
        }

        $this->isActive = true;
        $this->unlinkedAt = null;
        $this->linkToken = bin2hex(random_bytes(32));
    }

    // Getters...

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTelegramId(): int
    {
        return $this->telegramId;
    }

    public function getTelegramUsername(): ?string
    {
        return $this->telegramUsername;
    }

    public function getFullName(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function areNotificationsEnabled(): bool
    {
        return $this->notificationsEnabled;
    }

    public function setNotificationsEnabled(bool $enabled): void
    {
        $this->notificationsEnabled = $enabled;
    }

    public function getPreference(string $key, mixed $default = null): mixed
    {
        return $this->preferences[$key] ?? $default;
    }

    public function setPreference(string $key, mixed $value): void
    {
        $this->preferences[$key] = $value;
    }
}
```

### VoiceSession Entity

```php
<?php
// File: apps/backend/src/Entity/VoiceSession.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: VoiceSessionRepository::class)]
#[ORM\Table(name: 'voice_sessions')]
class VoiceSession
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $sessionId;

    #[ORM\OneToMany(targetEntity: VoiceCommand::class, mappedBy: 'session')]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $commands;

    #[ORM\Column(type: 'json')]
    private array $context;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status; // active, paused, completed

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $endedAt = null;

    #[ORM\Column(type: 'integer')]
    private int $commandCount = 0;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $summary = null;

    public function __construct(User $user)
    {
        $this->id = Uuid::v4();
        $this->user = $user;
        $this->sessionId = $this->generateSessionId();
        $this->commands = new ArrayCollection();
        $this->context = [];
        $this->status = 'active';
        $this->startedAt = new \DateTimeImmutable();
    }

    private function generateSessionId(): string
    {
        return sprintf(
            'voice_%s_%s',
            $this->user->getId()->toRfc4122(),
            bin2hex(random_bytes(8))
        );
    }

    public function addCommand(VoiceCommand $command): void
    {
        if ($this->status !== 'active') {
            throw new \DomainException('Cannot add commands to inactive session');
        }

        $this->commands->add($command);
        $command->setSessionId($this->sessionId);
        $this->commandCount++;

        // Update context based on command
        $this->updateContext($command);
    }

    private function updateContext(VoiceCommand $command): void
    {
        // Store relevant context from the command
        if ($command->getParsedCommand()) {
            $this->context['last_action'] = $command->getParsedCommand()->getAction();
            $this->context['last_entities'] = $command->getParsedCommand()->getEntities();
        }

        $this->context['command_count'] = $this->commandCount;
        $this->context['last_command_at'] = (new \DateTimeImmutable())->format('c');
    }

    public function pause(): void
    {
        if ($this->status !== 'active') {
            throw new \DomainException('Can only pause active sessions');
        }

        $this->status = 'paused';
    }

    public function resume(): void
    {
        if ($this->status !== 'paused') {
            throw new \DomainException('Can only resume paused sessions');
        }

        $this->status = 'active';
    }

    public function end(): void
    {
        if ($this->status === 'completed') {
            throw new \DomainException('Session already completed');
        }

        $this->status = 'completed';
        $this->endedAt = new \DateTimeImmutable();
        $this->generateSummary();
    }

    private function generateSummary(): void
    {
        $successCount = 0;
        $failedCount = 0;
        $actions = [];

        foreach ($this->commands as $command) {
            if ($command->getStatus()->isCompleted()) {
                $successCount++;
            } elseif ($command->getStatus()->isFailed()) {
                $failedCount++;
            }

            if ($command->getParsedCommand()) {
                $actions[] = $command->getParsedCommand()->getAction();
            }
        }

        $this->summary = [
            'duration_seconds' => $this->endedAt->getTimestamp() - $this->startedAt->getTimestamp(),
            'total_commands' => $this->commandCount,
            'successful_commands' => $successCount,
            'failed_commands' => $failedCount,
            'unique_actions' => array_unique($actions),
            'average_confidence' => $this->calculateAverageConfidence(),
        ];
    }

    private function calculateAverageConfidence(): float
    {
        $totalConfidence = 0;
        $count = 0;

        foreach ($this->commands as $command) {
            if ($command->getConfidence() !== null) {
                $totalConfidence += $command->getConfidence();
                $count++;
            }
        }

        return $count > 0 ? $totalConfidence / $count : 0;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCommands(): Collection
    {
        return $this->commands;
    }

    public function getSummary(): ?array
    {
        return $this->summary;
    }
}
```

---

## 🏭 Aggregates & Repositories

### VoiceCommandRepository

```php
<?php
// File: apps/backend/src/Repository/VoiceCommandRepository.php

namespace App\Repository;

use App\Entity\VoiceCommand;
use App\Entity\User;
use App\ValueObject\CommandStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<VoiceCommand>
 * @method VoiceCommand|null find($id, $lockMode = null, $lockVersion = null)
 * @method VoiceCommand|null findOneBy(array $criteria, array $orderBy = null)
 * @method VoiceCommand[]    findAll()
 * @method VoiceCommand[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VoiceCommandRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VoiceCommand::class);
    }

    public function save(VoiceCommand $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(VoiceCommand $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find pending commands for processing
     * @return VoiceCommand[]
     */
    public function findPendingCommands(int $limit = 10): array
    {
        return $this->createQueryBuilder('vc')
            ->where('vc.status = :status')
            ->setParameter('status', CommandStatus::PENDING->value)
            ->orderBy('vc.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find failed commands for retry
     * @return VoiceCommand[]
     */
    public function findRetryableCommands(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('vc');

        return $qb
            ->where('vc.status = :status')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('vc.metadata'),
                $qb->expr()->lt("JSON_EXTRACT(vc.metadata, '$.retry_count')", ':maxRetries')
            ))
            ->setParameter('status', CommandStatus::FAILED->value)
            ->setParameter('maxRetries', 3)
            ->orderBy('vc.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find user's recent commands
     * @return VoiceCommand[]
     */
    public function findUserRecentCommands(User $user, int $days = 7, int $limit = 100): array
    {
        $since = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('vc')
            ->where('vc.user = :user')
            ->andWhere('vc.createdAt >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->orderBy('vc.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get user command statistics
     */
    public function getUserStatistics(User $user): array
    {
        $qb = $this->createQueryBuilder('vc');

        $stats = $qb
            ->select([
                'COUNT(vc.id) as total_commands',
                'COUNT(CASE WHEN vc.status = :completed THEN 1 END) as completed_commands',
                'COUNT(CASE WHEN vc.status = :failed THEN 1 END) as failed_commands',
                'AVG(vc.processingTimeMs) as avg_processing_time',
                'AVG(vc.confidence) as avg_confidence'
            ])
            ->where('vc.user = :user')
            ->setParameter('user', $user)
            ->setParameter('completed', CommandStatus::COMPLETED->value)
            ->setParameter('failed', CommandStatus::FAILED->value)
            ->getQuery()
            ->getSingleResult();

        // Get command type distribution
        $typeDistribution = $this->createQueryBuilder('vc')
            ->select('vc.type, COUNT(vc.id) as count')
            ->where('vc.user = :user')
            ->setParameter('user', $user)
            ->groupBy('vc.type')
            ->getQuery()
            ->getResult();

        $stats['type_distribution'] = $typeDistribution;

        // Get action distribution
        $actionDistribution = $this->createQueryBuilder('vc')
            ->select("JSON_EXTRACT(vc.parsed_action, '$.action') as action, COUNT(vc.id) as count")
            ->where('vc.user = :user')
            ->andWhere('vc.parsed_action IS NOT NULL')
            ->setParameter('user', $user)
            ->groupBy('action')
            ->getQuery()
            ->getResult();

        $stats['action_distribution'] = $actionDistribution;

        return $stats;
    }

    /**
     * Find commands by session
     * @return VoiceCommand[]
     */
    public function findBySessionId(string $sessionId): array
    {
        return $this->createQueryBuilder('vc')
            ->where('vc.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('vc.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find similar commands for context
     * @return VoiceCommand[]
     */
    public function findSimilarCommands(User $user, string $text, int $limit = 5): array
    {
        // Use PostgreSQL full-text search
        $sql = "
            SELECT vc.*
            FROM voice_commands vc
            WHERE vc.user_id = :userId
            AND vc.status = :status
            AND vc.raw_text IS NOT NULL
            AND similarity(vc.raw_text, :text) > 0.3
            ORDER BY similarity(vc.raw_text, :text) DESC
            LIMIT :limit
        ";

        $rsm = new \Doctrine\ORM\Query\ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(VoiceCommand::class, 'vc');

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('userId', $user->getId(), 'uuid');
        $query->setParameter('status', CommandStatus::COMPLETED->value);
        $query->setParameter('text', $text);
        $query->setParameter('limit', $limit);

        return $query->getResult();
    }

    /**
     * Clean up old commands
     */
    public function cleanupOldCommands(int $daysToKeep = 30): int
    {
        $cutoffDate = new \DateTimeImmutable("-{$daysToKeep} days");

        $qb = $this->createQueryBuilder('vc');

        return $qb->delete()
            ->where('vc.createdAt < :cutoff')
            ->andWhere('vc.status IN (:statuses)')
            ->setParameter('cutoff', $cutoffDate)
            ->setParameter('statuses', [
                CommandStatus::COMPLETED->value,
                CommandStatus::FAILED->value,
                CommandStatus::CANCELLED->value
            ])
            ->getQuery()
            ->execute();
    }

    /**
     * Create query builder for complex searches
     */
    public function createSearchQueryBuilder(array $criteria): QueryBuilder
    {
        $qb = $this->createQueryBuilder('vc')
            ->leftJoin('vc.user', 'u');

        if (isset($criteria['user'])) {
            $qb->andWhere('vc.user = :user')
                ->setParameter('user', $criteria['user']);
        }

        if (isset($criteria['status'])) {
            $qb->andWhere('vc.status = :status')
                ->setParameter('status', $criteria['status']);
        }

        if (isset($criteria['type'])) {
            $qb->andWhere('vc.type = :type')
                ->setParameter('type', $criteria['type']);
        }

        if (isset($criteria['source'])) {
            $qb->andWhere('vc.source = :source')
                ->setParameter('source', $criteria['source']);
        }

        if (isset($criteria['from_date'])) {
            $qb->andWhere('vc.createdAt >= :from')
                ->setParameter('from', $criteria['from_date']);
        }

        if (isset($criteria['to_date'])) {
            $qb->andWhere('vc.createdAt <= :to')
                ->setParameter('to', $criteria['to_date']);
        }

        if (isset($criteria['search'])) {
            $qb->andWhere('vc.rawText LIKE :search')
                ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        return $qb;
    }
}
```

---

## 📢 Domain Events

### VoiceCommandEvent

```php
<?php
// File: apps/backend/src/Domain/Event/VoiceCommandEvent.php

namespace App\Domain\Event;

use App\Entity\VoiceCommand;
use Symfony\Contracts\EventDispatcher\Event;

abstract class VoiceCommandEvent extends Event
{
    public function __construct(
        protected VoiceCommand $command
    ) {}

    public function getCommand(): VoiceCommand
    {
        return $this->command;
    }
}

class VoiceCommandReceivedEvent extends VoiceCommandEvent
{
    public const NAME = 'voice.command.received';
}

class VoiceCommandTranscribedEvent extends VoiceCommandEvent
{
    public const NAME = 'voice.command.transcribed';
}

class VoiceCommandParsedEvent extends VoiceCommandEvent
{
    public const NAME = 'voice.command.parsed';
}

class VoiceCommandExecutingEvent extends VoiceCommandEvent
{
    public const NAME = 'voice.command.executing';
}

class VoiceCommandCompletedEvent extends VoiceCommandEvent
{
    public const NAME = 'voice.command.completed';

    public function __construct(
        VoiceCommand $command,
        private array $result
    ) {
        parent::__construct($command);
    }

    public function getResult(): array
    {
        return $this->result;
    }
}

class VoiceCommandFailedEvent extends VoiceCommandEvent
{
    public const NAME = 'voice.command.failed';

    public function __construct(
        VoiceCommand $command,
        private string $error
    ) {
        parent::__construct($command);
    }

    public function getError(): string
    {
        return $this->error;
    }
}
```

---

## 💾 Database Schema

### Migration Files

```php
<?php
// File: apps/backend/migrations/Version20250108_VoiceCommandTables.php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250108VoiceCommandTables extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create voice command related tables';
    }

    public function up(Schema $schema): void
    {
        // Voice commands table
        $this->addSql('
            CREATE TABLE voice_commands (
                id UUID NOT NULL DEFAULT gen_random_uuid(),
                user_id UUID NOT NULL,
                type VARCHAR(50) NOT NULL,
                status VARCHAR(50) NOT NULL,
                audio_file_path VARCHAR(255),
                audio_duration INTEGER,
                raw_text TEXT,
                transcription_text TEXT,
                transcription_language VARCHAR(10),
                transcription_confidence DOUBLE PRECISION,
                transcription_segments JSON,
                transcription_model VARCHAR(50),
                parsed_action VARCHAR(100),
                parsed_parameters JSON,
                parsed_confidence DOUBLE PRECISION,
                parsed_requires_confirmation BOOLEAN DEFAULT FALSE,
                parsed_entities JSON,
                parsed_intent VARCHAR(255),
                command_result JSON,
                source VARCHAR(50) NOT NULL,
                session_id VARCHAR(255),
                processing_time_ms INTEGER,
                confidence DOUBLE PRECISION,
                error_message TEXT,
                metadata JSON,
                created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                processed_at TIMESTAMP,
                completed_at TIMESTAMP,
                PRIMARY KEY(id)
            )
        ');

        // Indexes
        $this->addSql('CREATE INDEX idx_voice_command_user ON voice_commands(user_id)');
        $this->addSql('CREATE INDEX idx_voice_command_status ON voice_commands(status)');
        $this->addSql('CREATE INDEX idx_voice_command_created ON voice_commands(created_at DESC)');
        $this->addSql('CREATE INDEX idx_voice_command_session ON voice_commands(session_id)');
        $this->addSql('CREATE INDEX idx_voice_command_source ON voice_commands(source)');

        // Foreign key
        $this->addSql('
            ALTER TABLE voice_commands
            ADD CONSTRAINT fk_voice_command_user
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ');

        // User Telegram links table
        $this->addSql('
            CREATE TABLE user_telegram_links (
                id UUID NOT NULL DEFAULT gen_random_uuid(),
                user_id UUID NOT NULL,
                telegram_id BIGINT NOT NULL,
                telegram_username VARCHAR(255),
                first_name VARCHAR(255),
                last_name VARCHAR(255),
                language_code VARCHAR(10),
                link_token VARCHAR(64) NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                notifications_enabled BOOLEAN DEFAULT TRUE,
                preferences JSON,
                linked_at TIMESTAMP NOT NULL DEFAULT NOW(),
                last_used_at TIMESTAMP,
                unlinked_at TIMESTAMP,
                PRIMARY KEY(id)
            )
        ');

        $this->addSql('CREATE UNIQUE INDEX uniq_telegram_id ON user_telegram_links(telegram_id)');
        $this->addSql('CREATE INDEX idx_telegram_link_user ON user_telegram_links(user_id)');
        $this->addSql('CREATE INDEX idx_telegram_link_token ON user_telegram_links(link_token)');

        // Foreign key
        $this->addSql('
            ALTER TABLE user_telegram_links
            ADD CONSTRAINT fk_telegram_link_user
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ');

        // Voice sessions table
        $this->addSql('
            CREATE TABLE voice_sessions (
                id UUID NOT NULL DEFAULT gen_random_uuid(),
                user_id UUID NOT NULL,
                session_id VARCHAR(255) NOT NULL,
                context JSON DEFAULT \'{}\',
                status VARCHAR(50) NOT NULL,
                started_at TIMESTAMP NOT NULL DEFAULT NOW(),
                ended_at TIMESTAMP,
                command_count INTEGER DEFAULT 0,
                summary JSON,
                PRIMARY KEY(id)
            )
        ');

        $this->addSql('CREATE UNIQUE INDEX uniq_session_id ON voice_sessions(session_id)');
        $this->addSql('CREATE INDEX idx_voice_session_user ON voice_sessions(user_id)');
        $this->addSql('CREATE INDEX idx_voice_session_status ON voice_sessions(status)');

        // Foreign key
        $this->addSql('
            ALTER TABLE voice_sessions
            ADD CONSTRAINT fk_voice_session_user
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ');

        // Add trigram extension for fuzzy text search
        $this->addSql('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        $this->addSql('CREATE INDEX idx_voice_command_text_trgm ON voice_commands USING gin (raw_text gin_trgm_ops)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS voice_commands CASCADE');
        $this->addSql('DROP TABLE IF EXISTS user_telegram_links CASCADE');
        $this->addSql('DROP TABLE IF EXISTS voice_sessions CASCADE');
        $this->addSql('DROP EXTENSION IF EXISTS pg_trgm');
    }
}
```

---

## ⚙️ Doctrine Configuration

### Doctrine Configuration

```yaml
# File: apps/backend/config/packages/doctrine.yaml

doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        types:
            uuid: Symfony\Bridge\Doctrine\Types\UuidType
            command_type: App\DBAL\Types\CommandTypeType
            command_status: App\DBAL\Types\CommandStatusType

    orm:
        auto_generate_proxy_classes: true
        enable_lazy_ghost_objects: true
        report_fields_where_declared: true
        validate_xml_mapping: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true
        mappings:
            App:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/src/Entity'
                prefix: 'App\Entity'
                alias: App

        dql:
            string_functions:
                JSON_EXTRACT: App\DQL\JsonExtract
                SIMILARITY: App\DQL\Similarity
```

### Custom Doctrine Types

```php
<?php
// File: apps/backend/src/DBAL/Types/CommandTypeType.php

namespace App\DBAL\Types;

use App\ValueObject\CommandType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class CommandTypeType extends Type
{
    const NAME = 'command_type';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'VARCHAR(50)';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?CommandType
    {
        return $value ? CommandType::from($value) : null;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value instanceof CommandType ? $value->value : null;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
```

---

## 📏 Business Rules

### Command Validation Rules

```php
<?php
// File: apps/backend/src/Domain/Rules/VoiceCommandRules.php

namespace App\Domain\Rules;

use App\Entity\VoiceCommand;
use App\Entity\User;

class VoiceCommandRules
{
    /**
     * Maximum commands per user per hour
     */
    public const MAX_COMMANDS_PER_HOUR = 100;

    /**
     * Maximum audio file size in MB
     */
    public const MAX_AUDIO_FILE_SIZE_MB = 10;

    /**
     * Maximum audio duration in seconds
     */
    public const MAX_AUDIO_DURATION_SECONDS = 30;

    /**
     * Maximum text command length
     */
    public const MAX_TEXT_COMMAND_LENGTH = 1000;

    /**
     * Command expiry time in seconds
     */
    public const COMMAND_EXPIRY_SECONDS = 300;

    /**
     * Minimum confidence threshold
     */
    public const MIN_CONFIDENCE_THRESHOLD = 0.5;

    /**
     * Commands requiring confirmation
     */
    private const DANGEROUS_ACTIONS = [
        'delete_all_tasks',
        'reset_account',
        'share_all_data',
        'bulk_delete',
    ];

    public static function canUserSubmitCommand(User $user, int $recentCommandCount): bool
    {
        // Check rate limit
        if ($recentCommandCount >= self::MAX_COMMANDS_PER_HOUR) {
            return false;
        }

        // Check user status
        if (!$user->isActive()) {
            return false;
        }

        // Check user subscription limits (if applicable)
        // ...

        return true;
    }

    public static function requiresConfirmation(string $action): bool
    {
        return in_array($action, self::DANGEROUS_ACTIONS, true);
    }

    public static function isConfidenceAcceptable(float $confidence): bool
    {
        return $confidence >= self::MIN_CONFIDENCE_THRESHOLD;
    }

    public static function isCommandExpired(VoiceCommand $command): bool
    {
        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $command->getCreatedAt()->getTimestamp();

        return $diff > self::COMMAND_EXPIRY_SECONDS;
    }

    public static function validateAudioDuration(int $durationMs): bool
    {
        return $durationMs <= (self::MAX_AUDIO_DURATION_SECONDS * 1000);
    }

    public static function validateTextLength(string $text): bool
    {
        return mb_strlen($text) <= self::MAX_TEXT_COMMAND_LENGTH;
    }
}
```

---

## 💻 Code Examples

### Using the Domain Model

```php
<?php
// Example: Creating and processing a voice command

use App\Entity\VoiceCommand;
use App\Entity\User;
use App\ValueObject\CommandType;
use App\ValueObject\TranscriptionResult;
use App\ValueObject\ParsedCommand;

// Create a new voice command
$user = $userRepository->find($userId);
$command = new VoiceCommand(
    $user,
    CommandType::AUDIO,
    'web'
);

// Set audio file
$command->setAudioFile('/uploads/voice/command_123.wav', 5000);

// Start processing
$command->startProcessing();

// Add transcription result
$transcription = new TranscriptionResult(
    'Создай задачу купить молоко завтра в 15:00',
    'ru',
    0.95,
    [/* segments */],
    'whisper-base'
);
$command->setTranscription($transcription);

// Parse command
$parsed = new ParsedCommand(
    'create_task',
    [
        'title' => 'Купить молоко',
        'due_date' => '2025-01-09 15:00:00',
    ],
    0.92,
    false,
    ['task', 'time'],
    'task.create'
);
$command->setParsedCommand($parsed);

// Execute
$command->markAsExecuting();

// Complete with result
$result = [
    'task_id' => 'task_456',
    'created' => true,
    'message' => 'Task created successfully'
];
$command->complete($result);

// Save
$voiceCommandRepository->save($command, true);
```

---

## ✅ Next Steps

1. ✅ Domain Model complete
2. → Implement [Service Layer](02_SERVICES.md)
3. → Create [Command Handlers](03_COMMAND_HANDLERS.md)
4. → Define [API Endpoints](04_API_ENDPOINTS.md)

---

**Document Status**: Complete
**Domain Model Version**: 1.0.0
**Last Updated**: 2025-11-08
**Author**: Backend Architecture Team