<?php

declare(strict_types=1);

namespace App\Dto\Response\Task;

use App\Dto\Response\Recurrence\RecurrenceRuleDto;
use App\Entity\Task;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Symfony\Component\Serializer\Annotation\Groups;

final class TaskResponseDto implements \JsonSerializable
{
    #[Groups(['task:read'])]
    public int $id;

    #[Groups(['task:read'])]
    public string $title;

    #[Groups(['task:read'])]
    public ?string $description;

    #[Groups(['task:read'])]
    public TaskStatus $status;

    #[Groups(['task:read'])]
    public TaskPriority $priority;

    #[Groups(['task:read'])]
    public ?\DateTimeImmutable $startDate;

    #[Groups(['task:read'])]
    public ?\DateTimeImmutable $dueDate;

    #[Groups(['task:read'])]
    public ?\DateTimeImmutable $completedAt;

    #[Groups(['task:read'])]
    public ?int $parentTaskId;

    #[Groups(['task:read'])]
    public array $subtasks = [];

    #[Groups(['task:read'])]
    public array $tags = [];

    #[Groups(['task:read'])]
    public int $sortOrder = 0;

    #[Groups(['task:read'])]
    public bool $isArchived = false;

    #[Groups(['task:read'])]
    public bool $isCompleted = false;

    #[Groups(['task:read'])]
    public bool $isOverdue = false;

    #[Groups(['task:read'])]
    public float $completionProgress = 0.0;

    #[Groups(['task:read'])]
    public ?\DateTimeImmutable $createdAt = null;

    #[Groups(['task:read'])]
    public ?\DateTimeImmutable $updatedAt = null;

    #[Groups(['task:read'])]
    public int $subtaskCount = 0;

    #[Groups(['task:read'])]
    public int $completedSubtaskCount = 0;

    #[Groups(['task:read'])]
    public bool $hasNestedSubtasks = false;

    #[Groups(['task:read'])]
    public array $attachments = [];

    #[Groups(['task:read'])]
    public bool $isRecurringTemplate = false;

    #[Groups(['task:read'])]
    public ?RecurrenceRuleDto $recurrenceRule = null;

    // Translated labels (populated by TaskService)
    #[Groups(['task:read'])]
    public ?string $priorityLabel = null;

    #[Groups(['task:read'])]
    public ?string $statusLabel = null;

    /**
     * Create DTO from Task Entity (Database → DTO)
     *
     * This method is used when fetching fresh data from database.
     *
     * @param Task $task The task entity from database
     * @param bool $includeSubtasks Whether to recursively include subtasks
     * @param bool $includeMeta Whether to include metadata (createdAt, updatedAt)
     * @return self
     */
    public static function fromEntity(Task $task, bool $includeSubtasks = false, bool $includeMeta = true): self
    {
        $dto = new self();
        $dto->id = $task->getId();
        $dto->title = $task->getTitle();
        $dto->description = $task->getDescription();
        $dto->status = $task->getStatus();
        $dto->priority = $task->getPriority();
        $dto->startDate = $task->getStartDate();
        $dto->dueDate = $task->getDueDate();
        $dto->completedAt = $task->getCompletedAt();
        $dto->parentTaskId = $task->getParentTask()?->getId();
        $dto->sortOrder = $task->getSortOrder();
        $dto->isArchived = $task->isArchived();
        $dto->isCompleted = $task->isCompleted();
        $dto->isOverdue = $task->isOverdue();
        $dto->completionProgress = $task->getCompletionProgress();
        $dto->isRecurringTemplate = $task->isRecurringTemplate();

        if ($includeMeta) {
            $dto->createdAt = $task->getCreatedAt();
            $dto->updatedAt = $task->getUpdatedAt();
        }

        $subtasks = $task->getSubtasks();
        $dto->subtaskCount = $subtasks->count();
        $dto->completedSubtaskCount = $subtasks->filter(fn(Task $subtask) => $subtask->isCompleted())->count();
        $dto->hasNestedSubtasks = $subtasks->exists(fn($key, Task $subtask) => $subtask->getSubtasks()->count() > 0);

        // Map tags with lightweight payload
        $dto->tags = array_map(
            static fn($tag) => [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
                'color' => $tag->getColor(),
            ],
            $task->getTags()->toArray()
        );

        // Map subtasks if requested
        if ($includeSubtasks) {
            $dto->subtasks = array_map(
                fn($subtask) => self::fromEntity($subtask, true, $includeMeta),
                $subtasks->toArray()
            );
        }

        // Map media objects
        $dto->attachments = array_map(
            static fn($media) => [
                'id' => $media->getId(),
                'fileName' => $media->getFileName(),
                'originalName' => $media->getOriginalName(),
                'mimeType' => $media->getMimeType(),
                'fileSize' => $media->getFileSize(),
                'fileSizeHuman' => $media->getHumanReadableSize(),
                'fileType' => $media->getFileType(),
                'filePath' => $media->getFilePath(),
                'thumbnailPath' => $media->getThumbnailPath(),
                'createdAt' => $media->getCreatedAt()->format('Y-m-d H:i:s'),
            ],
            $task->getMediaObjects()->toArray()
        );

        // Map recurrence rule if exists
        if ($task->getRecurrenceRule()) {
            $dto->recurrenceRule = RecurrenceRuleDto::fromEntity($task->getRecurrenceRule());
        }

        return $dto;
    }

    /**
     * Create DTO from cached array (Redis Cache → DTO)
     *
     * This method deserializes task data from Redis cache.
     * The array structure matches the JSON representation stored in Redis.
     *
     * Expected array structure:
     * [
     *     'id' => int,
     *     'title' => string,
     *     'description' => ?string,
     *     'status' => string (enum value: 'PENDING'|'IN_PROGRESS'|'COMPLETED'|'CANCELLED'),
     *     'priority' => string (enum value: 'LOW'|'MEDIUM'|'HIGH'|'URGENT'),
     *     'startDate' => ?string (ISO 8601: '2025-01-15T10:30:00+00:00'),
     *     'dueDate' => ?string (ISO 8601: '2025-01-20T18:00:00+00:00'),
     *     'completedAt' => ?string (ISO 8601),
     *     'parentTaskId' => ?int,
     *     'sortOrder' => int,
     *     'isArchived' => bool,
     *     'isCompleted' => bool,
     *     'isOverdue' => bool,
     *     'completionProgress' => float (0.0 - 100.0),
     *     'createdAt' => ?string (ISO 8601),
     *     'updatedAt' => ?string (ISO 8601),
     *     'subtaskCount' => int,
     *     'completedSubtaskCount' => int,
     *     'hasNestedSubtasks' => bool,
     *     'isRecurringTemplate' => bool,
     *
     *     'tags' => [
     *         ['id' => int, 'name' => string, 'color' => string],
     *         ...
     *     ],
     *
     *     'subtasks' => [
     *         [... recursive task structure ...],
     *         ...
     *     ],
     *
     *     'attachments' => [
     *         [
     *             'id' => int,
     *             'fileName' => string,
     *             'originalName' => string,
     *             'mimeType' => string,
     *             'fileSize' => int,
     *             'fileSizeHuman' => string,
     *             'fileType' => string,
     *             'filePath' => string,
     *             'thumbnailPath' => ?string,
     *             'createdAt' => string,
     *         ],
     *         ...
     *     ],
     *
     *     'recurrenceRule' => ?[
     *         'id' => int,
     *         'recurrenceType' => string,
     *         'interval' => ?int,
     *         'daysOfWeek' => ?array,
     *         'dayOfMonth' => ?int,
     *         'monthOfYear' => ?int,
     *         'endDate' => ?string,
     *         'maxOccurrences' => ?int,
     *         'currentOccurrences' => int,
     *         'nextOccurrenceDate' => string,
     *         'timeOfDay' => ?string,
     *         'isActive' => bool,
     *         'templateTaskId' => int,
     *         'createdAt' => string,
     *         'previewDates' => array,
     *     ]
     * ]
     *
     * @param array $data The deserialized array from cache
     * @return self
     * @throws \InvalidArgumentException If required fields are missing
     * @throws \ValueError If enum values are invalid
     */
    public static function fromArray(array $data): self
    {
        // Validate required fields
        if (!isset($data['id'], $data['title'], $data['status'], $data['priority'])) {
            throw new \InvalidArgumentException(
                'Missing required fields in cached task data. Required: id, title, status, priority'
            );
        }

        $dto = new self();

        // Basic fields
        $dto->id = (int) $data['id'];
        $dto->title = (string) $data['title'];
        $dto->description = $data['description'] ?? null;

        // Enums - convert string values back to enum instances
        // IMPORTANT: Symfony Serializer serializes enums with extra methods (getLabel, getColor, etc.)
        // as objects/arrays instead of simple strings:
        //   Simple enum: "in_progress" (string)
        //   With methods: {"value": "in_progress", "label": "In Progress", "color": "#3B82F6", ...} (object/array)
        // We handle both formats for maximum compatibility
        $dto->status = is_array($data['status'])
            ? TaskStatus::from($data['status']['value'] ?? $data['status'][0] ?? 'PENDING')
            : TaskStatus::from($data['status']);

        $dto->priority = is_array($data['priority'])
            ? TaskPriority::from($data['priority']['value'] ?? $data['priority'][0] ?? 'MEDIUM')
            : TaskPriority::from($data['priority']);

        // Dates - convert ISO 8601 strings to DateTimeImmutable
        // Handle both string format (from json_encode with JsonSerializable) and array format (fallback)
        $dto->startDate = isset($data['startDate']) && $data['startDate']
            ? (is_string($data['startDate']) ? new \DateTimeImmutable($data['startDate']) : null)
            : null;

        $dto->dueDate = isset($data['dueDate']) && $data['dueDate']
            ? (is_string($data['dueDate']) ? new \DateTimeImmutable($data['dueDate']) : null)
            : null;

        $dto->completedAt = isset($data['completedAt']) && $data['completedAt']
            ? (is_string($data['completedAt']) ? new \DateTimeImmutable($data['completedAt']) : null)
            : null;

        $dto->createdAt = isset($data['createdAt']) && $data['createdAt']
            ? (is_string($data['createdAt']) ? new \DateTimeImmutable($data['createdAt']) : null)
            : null;

        $dto->updatedAt = isset($data['updatedAt']) && $data['updatedAt']
            ? (is_string($data['updatedAt']) ? new \DateTimeImmutable($data['updatedAt']) : null)
            : null;

        // Relationships
        $dto->parentTaskId = $data['parentTaskId'] ?? null;

        // Boolean flags
        $dto->sortOrder = (int) ($data['sortOrder'] ?? 0);
        $dto->isArchived = (bool) ($data['isArchived'] ?? false);
        $dto->isCompleted = (bool) ($data['isCompleted'] ?? false);
        $dto->isOverdue = (bool) ($data['isOverdue'] ?? false);
        $dto->isRecurringTemplate = (bool) ($data['isRecurringTemplate'] ?? false);

        // Metrics
        $dto->completionProgress = (float) ($data['completionProgress'] ?? 0.0);
        $dto->subtaskCount = (int) ($data['subtaskCount'] ?? 0);
        $dto->completedSubtaskCount = (int) ($data['completedSubtaskCount'] ?? 0);
        $dto->hasNestedSubtasks = (bool) ($data['hasNestedSubtasks'] ?? false);

        // Tags - already in the correct format (array of arrays)
        $dto->tags = $data['tags'] ?? [];

        // Subtasks - recursively deserialize (SOLID: Single Responsibility)
        $dto->subtasks = isset($data['subtasks']) && is_array($data['subtasks'])
            ? array_map(fn(array $subtaskData) => self::fromArray($subtaskData), $data['subtasks'])
            : [];

        // Attachments - already in the correct format
        $dto->attachments = $data['attachments'] ?? [];

        // Recurrence rule - delegate to RecurrenceRuleDto (SOLID: Dependency Inversion)
        $dto->recurrenceRule = isset($data['recurrenceRule']) && is_array($data['recurrenceRule'])
            ? RecurrenceRuleDto::fromArray($data['recurrenceRule'])
            : null;

        return $dto;
    }

    /**
     * Serialize DTO to JSON-compatible array
     *
     * This method is called by json_encode() to properly serialize the DTO.
     * It converts DateTimeImmutable objects to ISO 8601 strings and Enums to their values.
     *
     * @return array JSON-serializable array representation
     */
    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value, // Enum to string
            'priority' => $this->priority->value, // Enum to string
            'startDate' => $this->startDate?->format(\DateTimeInterface::ATOM), // DateTimeImmutable to ISO 8601 string
            'dueDate' => $this->dueDate?->format(\DateTimeInterface::ATOM),
            'completedAt' => $this->completedAt?->format(\DateTimeInterface::ATOM),
            'parentTaskId' => $this->parentTaskId,
            'tags' => $this->tags,
            'sortOrder' => $this->sortOrder,
            'isArchived' => $this->isArchived,
            'isCompleted' => $this->isCompleted,
            'isOverdue' => $this->isOverdue,
            'completionProgress' => $this->completionProgress,
            'createdAt' => $this->createdAt?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
            'subtaskCount' => $this->subtaskCount,
            'completedSubtaskCount' => $this->completedSubtaskCount,
            'hasNestedSubtasks' => $this->hasNestedSubtasks,
            'attachments' => $this->attachments,
            'isRecurringTemplate' => $this->isRecurringTemplate,
            'priorityLabel' => $this->priorityLabel,
            'statusLabel' => $this->statusLabel,
        ];

        // Handle subtasks safely - they are already DTOs and will recursively call jsonSerialize()
        if (!empty($this->subtasks)) {
            $data['subtasks'] = $this->subtasks;
        } else {
            $data['subtasks'] = [];
        }

        // Handle recurrence rule safely
        if ($this->recurrenceRule !== null) {
            $data['recurrenceRule'] = $this->recurrenceRule;
        } else {
            $data['recurrenceRule'] = null;
        }

        return $data;
    }
}
