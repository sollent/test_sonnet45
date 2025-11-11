<?php

declare(strict_types=1);

namespace App\Dto\Request\Task;

use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateTaskDto
{
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'task.title.min_length',
        maxMessage: 'task.title.max_length',
    )]
    public ?string $title = null;

    #[Assert\Length(
        max: 5000,
        maxMessage: 'task.description.max_length',
    )]
    public ?string $description = null;

    public ?TaskStatus $status = null;

    public ?TaskPriority $priority = null;

    public ?string $startDate = null;

    public ?string $dueDate = null;

    /**
     * @var string[]|null
     */
    public ?array $tags = null;

    /**
     * @var int[]|null
     */
    public ?array $mediaIds = null;

    public ?int $sortOrder = null;

    public ?bool $isArchived = null;
}
