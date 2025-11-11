<?php

declare(strict_types=1);

namespace App\Dto\Request\Task;

use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateTaskDto
{
    #[Assert\NotBlank(message: 'task.title.not_blank')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'task.title.min_length',
        maxMessage: 'task.title.max_length',
    )]
    public string $title;

    #[Assert\Length(
        max: 5000,
        maxMessage: 'task.description.max_length',
    )]
    public ?string $description = null;

    public TaskStatus $status = TaskStatus::PENDING;

    public TaskPriority $priority = TaskPriority::MEDIUM;

    public ?string $startDate = null;

    public ?string $dueDate = null;

    public ?int $parentTaskId = null;

    /**
     * @var string[]
     */
    public array $tags = [];

    /**
     * @var int[]
     */
    public array $mediaIds = [];

    public int $sortOrder = 0;

    public bool $isArchived = false;

    /**
     * Recurrence settings (optional)
     *
     * @var array{
     *   recurrenceType: string,
     *   interval?: int,
     *   daysOfWeek?: int[],
     *   dayOfMonth?: int,
     *   monthOfYear?: int,
     *   endDate?: string,
     *   maxOccurrences?: int,
     *   timeOfDay?: string
     * }|null
     */
    public ?array $recurrence = null;
}
