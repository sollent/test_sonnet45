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
        $dto->isRecurringTemplate = $task->isRecurringTemplate();

        if ($includeMeta) {
            $dto->createdAt = $task->getCreatedAt();
            $dto->updatedAt = $task->getUpdatedAt();
        }

        // Always calculate subtask counts (subtasks are pre-loaded via JOIN in repository)
        $subtasks = $task->getSubtasks();
        $dto->subtaskCount = $subtasks->count();
        $dto->completedSubtaskCount = $subtasks->filter(fn(Task $subtask) => $subtask->isCompleted())->count();

        // Calculate completion progress based on subtasks
        $dto->completionProgress = $task->getCompletionProgress();

        // Only include full subtask details if explicitly requested
        if ($includeSubtasks) {
            $dto->hasNestedSubtasks = $subtasks->exists(fn($key, Task $subtask) => $subtask->getSubtasks()->count() > 0);
        } else {
            // For list views: don't check nested subtasks to avoid additional queries
            $dto->hasNestedSubtasks = false;
        }

        // Map tags with lightweight payload - only if initialized to avoid N+1
        $dto->tags = $task->getTags()->isInitialized()
            ? array_map(
                static fn($tag) => [
                    'id' => $tag->getId(),
                    'name' => $tag->getName(),
                    'color' => $tag->getColor(),
                ],
                $task->getTags()->toArray()
            )
            : [];

        // Map subtasks if requested
        if ($includeSubtasks) {
            $dto->subtasks = array_map(
                fn($subtask) => self::fromEntity($subtask, true, $includeMeta),
                $subtasks->toArray()
            );
        }

        // Map media objects - only if initialized to avoid N+1
        $dto->attachments = $task->getMediaObjects()->isInitialized()
            ? array_map(
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
            )
            : [];

        // Map recurrence rule if exists
        if ($task->getRecurrenceRule()) {
            $dto->recurrenceRule = RecurrenceRuleDto::fromEntity($task->getRecurrenceRule());
        }

        return $dto;
    }

    /**
     * OPTIMIZED: Create DTO from raw database array (no Doctrine entities, no lazy loading)
     * Used by findTasksForMonthRaw() for maximum performance
     */
    public static function fromRawData(array $data): self
    {
        $dto = new self();
        $dto->id = (int)$data['id'];
        $dto->title = $data['title'];
        $dto->description = $data['description'];
        $dto->status = $data['status'];
        $dto->priority = $data['priority'];

        // Parse dates
        $dto->startDate = $data['start_date'] ? new \DateTimeImmutable($data['start_date']) : null;
        $dto->dueDate = $data['due_date'] ? new \DateTimeImmutable($data['due_date']) : null;
        $dto->completedAt = $data['completed_at'] ? new \DateTimeImmutable($data['completed_at']) : null;
        $dto->createdAt = $data['created_at'] ? new \DateTimeImmutable($data['created_at']) : null;
        $dto->updatedAt = $data['updated_at'] ? new \DateTimeImmutable($data['updated_at']) : null;

        $dto->parentTaskId = isset($data['parent_task_id']) ? (int)$data['parent_task_id'] : null;
        $dto->sortOrder = (int)$data['sort_order'];
        $dto->isArchived = (bool)$data['is_archived'];
        $dto->isRecurringTemplate = (bool)$data['is_recurring_template'];

        // Calculate computed fields
        $dto->isCompleted = $data['status'] === 'completed';
        $dto->isOverdue = !$dto->isCompleted && $dto->dueDate && $dto->dueDate < new \DateTimeImmutable();

        // Subtasks data (already populated in raw data)
        $subtasks = $data['subtasks'] ?? [];
        $dto->subtaskCount = count($subtasks);
        $dto->completedSubtaskCount = count(array_filter($subtasks, fn($s) => $s['status'] === 'completed'));

        // Calculate completion progress
        if ($dto->subtaskCount > 0) {
            $dto->completionProgress = round(($dto->completedSubtaskCount / $dto->subtaskCount) * 100, 2);
        } else {
            $dto->completionProgress = $dto->isCompleted ? 100.0 : 0.0;
        }

        // Map tags (already in raw format)
        $dto->tags = $data['tags'] ?? [];

        // Map subtasks recursively
        $dto->subtasks = array_map(fn($subtask) => self::fromRawData($subtask), $subtasks);

        // Map recurrence rule if exists
        if (isset($data['rr_id']) && $data['rr_id']) {
            $dto->recurrenceRule = RecurrenceRuleDto::fromRawData([
                'id' => $data['rr_id'],
                'recurrence_type' => $data['recurrence_type'],
                'interval' => $data['interval'],
                'days_of_week' => $data['days_of_week'],
                'day_of_month' => $data['day_of_month'],
                'month_of_year' => $data['month_of_year'],
                'end_date' => $data['end_date'],
                'max_occurrences' => $data['max_occurrences'],
                'current_occurrences' => $data['current_occurrences'],
                'next_occurrence_date' => $data['next_occurrence_date'],
                'time_of_day' => $data['time_of_day'],
                'is_active' => $data['is_active'],
            ]);
        }

        // hasNestedSubtasks not calculated for performance (not needed in calendar view)
        $dto->hasNestedSubtasks = false;

        // Attachments empty for calendar view (not needed)
        $dto->attachments = [];

        return $dto;
    }
}
