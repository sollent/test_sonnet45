<?php

declare(strict_types=1);

namespace App\Dto\Response\Task;

use App\Dto\Response\Recurrence\RecurrenceRuleDto;
use App\Entity\Task;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;

final class TaskResponseDto
{
    public int $id;
    public string $title;
    public ?string $description;
    public string $status;
    public string $priority;
    public ?\DateTimeImmutable $startDate;
    public ?\DateTimeImmutable $dueDate;
    public ?\DateTimeImmutable $completedAt;
    public ?int $parentTaskId;
    public array $subtasks = [];
    public array $tags = [];
    public int $sortOrder = 0;
    public bool $isArchived = false;
    public bool $isCompleted = false;
    public bool $isOverdue = false;
    public float $completionProgress = 0.0;
    public ?\DateTimeImmutable $createdAt = null;
    public ?\DateTimeImmutable $updatedAt = null;
    public int $subtaskCount = 0;
    public int $completedSubtaskCount = 0;
    public bool $hasNestedSubtasks = false;
    public array $attachments = [];
    public bool $isRecurringTemplate = false;
    public ?RecurrenceRuleDto $recurrenceRule = null;
    
    // Translated labels (populated by TaskService)
    public ?string $priorityLabel = null;
    public ?string $statusLabel = null;

    public static function fromEntity(Task $task, bool $includeSubtasks = false, bool $includeMeta = true): self
    {
        $dto = new self();
        $dto->id = $task->getId();
        $dto->title = $task->getTitle();
        $dto->description = $task->getDescription();
        $dto->status = $task->getStatus()->value;
        $dto->priority = $task->getPriority()->value;
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
}
