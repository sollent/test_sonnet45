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
    private ?array $tags = null;

    private ?bool $completed = null;

    #[Assert\Date]
    private ?string $dateFrom = null;

    #[Assert\Date]
    private ?string $dateTo = null;

    /**
     * @var array<string>|null
     */
    private ?array $priorities = null;

    /**
     * @var array<string>|null
     */
    private ?array $statuses = null;

    /**
     * Constructor to handle type conversion from query parameters
     */
    public function __construct()
    {
        // Tags will be set via setter
    }

    /**
     * Set tags with automatic type conversion
     */
    public function setTags(?array $tags): void
    {
        if ($tags === null) {
            $this->tags = null;

            return;
        }

        // Convert string values to integers
        $this->tags = array_map('intval', array_filter($tags, fn ($tag) => is_numeric($tag)));
    }

    /**
     * Validate tags after setting
     */
    #[Assert\All([
        new Assert\Type('integer'),
        new Assert\Positive(),
    ])]
    public function getTags(): ?array
    {
        return $this->tags;
    }

    /**
     * Set priorities with validation
     */
    public function setPriorities(?array $priorities): void
    {
        if ($priorities === null) {
            $this->priorities = null;

            return;
        }

        // Filter valid priorities
        $this->priorities = array_filter($priorities, fn ($p) => in_array($p, TaskPriority::values(), true));
    }

    /**
     * Validate priorities
     */
    #[Assert\All([
        new Assert\Choice(callback: [TaskPriority::class, 'values']),
    ])]
    public function getPriorities(): ?array
    {
        return $this->priorities;
    }

    /**
     * Set statuses with validation
     */
    public function setStatuses(?array $statuses): void
    {
        if ($statuses === null) {
            $this->statuses = null;

            return;
        }

        // Filter valid statuses
        $this->statuses = array_filter($statuses, fn ($s) => in_array($s, TaskStatus::values(), true));
    }

    /**
     * Validate statuses
     */
    #[Assert\All([
        new Assert\Choice(callback: [TaskStatus::class, 'values']),
    ])]
    public function getStatuses(): ?array
    {
        return $this->statuses;
    }

    /**
     * Get completed status
     */
    public function getCompleted(): ?bool
    {
        return $this->completed;
    }

    /**
     * Set completed status
     */
    public function setCompleted(?bool $completed): void
    {
        $this->completed = $completed;
    }

    /**
     * Get dateFrom
     */
    public function getDateFrom(): ?string
    {
        return $this->dateFrom;
    }

    /**
     * Set dateFrom
     */
    public function setDateFrom(?string $dateFrom): void
    {
        $this->dateFrom = $dateFrom;
    }

    /**
     * Get dateTo
     */
    public function getDateTo(): ?string
    {
        return $this->dateTo;
    }

    /**
     * Set dateTo
     */
    public function setDateTo(?string $dateTo): void
    {
        $this->dateTo = $dateTo;
    }

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
