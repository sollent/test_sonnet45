<?php

declare(strict_types=1);

namespace App\Dto\Response\Task;

use App\Dto\Response\Tag\TagResponseDto;
use App\Entity\Task;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;

final class TaskResponseDto
{
    public int $id;
    public string $title;
    public ?string $description;
    public TaskStatus $status;
    public TaskPriority $priority;
    public ?\DateTimeImmutable $startDate;
    public ?\DateTimeImmutable $dueDate;
    public ?\DateTimeImmutable $completedAt;
    public ?int $parentTaskId;
    public array $subtasks = [];
    public array $tags = [];
    public int $sortOrder;
    public bool $isArchived;
    public bool $isCompleted;
    public bool $isOverdue;
    public float $completionProgress;
    public \DateTimeImmutable $createdAt;
    public \DateTimeImmutable $updatedAt;

    public static function fromEntity(Task $task, bool $includeSubtasks = false): self
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
        $dto->createdAt = $task->getCreatedAt();
        $dto->updatedAt = $task->getUpdatedAt();

        // Map tags
        $dto->tags = array_map(
            fn($tag) => TagResponseDto::fromEntity($tag),
            $task->getTags()->toArray()
        );

        // Map subtasks if requested
        if ($includeSubtasks) {
            $dto->subtasks = array_map(
                fn($subtask) => self::fromEntity($subtask, false),
                $task->getSubtasks()->toArray()
            );
        }

        return $dto;
    }

    public function getStatusLabel(): string
    {
        return $this->status->getLabel();
    }

    public function getStatusColor(): string
    {
        return $this->status->getColor();
    }

    public function getStatusIcon(): string
    {
        return $this->status->getIcon();
    }

    public function getPriorityLabel(): string
    {
        return $this->priority->getLabel();
    }

    public function getPriorityColor(): string
    {
        return $this->priority->getColor();
    }

    public function getPriorityIcon(): string
    {
        return $this->priority->getIcon();
    }
}
