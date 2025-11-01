<?php

declare(strict_types=1);

namespace App\Dto\Request\Task;

use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Symfony\Component\Validator\Constraints as Assert;

final class TaskFilterDto
{
    /**
     * @var array<int>|null
     */
    #[Assert\Type('array')]
    #[Assert\All([
        new Assert\Type('integer'),
        new Assert\Positive()
    ])]
    public ?array $tags = null;

    #[Assert\Type('bool')]
    public ?bool $completed = null;

    #[Assert\Date]
    public ?string $dateFrom = null;

    #[Assert\Date]
    public ?string $dateTo = null;

    /**
     * @var array<string>|null
     */
    #[Assert\Type('array')]
    #[Assert\All([
        new Assert\Choice(callback: [TaskPriority::class, 'values'])
    ])]
    public ?array $priorities = null;

    /**
     * @var array<string>|null
     */
    #[Assert\Type('array')]
    #[Assert\All([
        new Assert\Choice(callback: [TaskStatus::class, 'values'])
    ])]
    public ?array $statuses = null;

    public function hasFilters(): bool
    {
        return $this->tags !== null
            || $this->completed !== null
            || $this->dateFrom !== null
            || $this->dateTo !== null
            || $this->priorities !== null
            || $this->statuses !== null;
    }
}

