<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\Database\RecurrenceRuleRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecurrenceRuleRepository::class)]
#[ORM\Table(name: 'recurrence_rules')]
#[ORM\HasLifecycleCallbacks]
class RecurrenceRule
{
    public const TYPE_DAILY = 'daily';

    public const TYPE_WEEKLY = 'weekly';

    public const TYPE_MONTHLY = 'monthly';

    public const TYPE_YEARLY = 'yearly';

    public const TYPE_CUSTOM = 'custom';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $recurrenceType;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $interval = null; // For custom type - every N days

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $daysOfWeek = null; // For weekly [1,2,3,4,5] = Mon-Fri

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $dayOfMonth = null; // For monthly

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $monthOfYear = null; // For yearly

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $maxOccurrences = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $currentOccurrences = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeInterface $nextOccurrenceDate;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $createdBy;

    #[ORM\OneToOne(targetEntity: Task::class, inversedBy: 'recurrenceRule')]
    #[ORM\JoinColumn(nullable: false)]
    private Task $templateTask;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    private ?DateTimeInterface $timeOfDay = null; // Time when task should be created

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecurrenceType(): string
    {
        return $this->recurrenceType;
    }

    public function setRecurrenceType(string $recurrenceType): self
    {
        $this->recurrenceType = $recurrenceType;

        return $this;
    }

    public function getInterval(): ?int
    {
        return $this->interval;
    }

    public function setInterval(?int $interval): self
    {
        $this->interval = $interval;

        return $this;
    }

    public function getDaysOfWeek(): ?array
    {
        return $this->daysOfWeek;
    }

    public function setDaysOfWeek(?array $daysOfWeek): self
    {
        $this->daysOfWeek = $daysOfWeek;

        return $this;
    }

    public function getDayOfMonth(): ?int
    {
        return $this->dayOfMonth;
    }

    public function setDayOfMonth(?int $dayOfMonth): self
    {
        $this->dayOfMonth = $dayOfMonth;

        return $this;
    }

    public function getMonthOfYear(): ?int
    {
        return $this->monthOfYear;
    }

    public function setMonthOfYear(?int $monthOfYear): self
    {
        $this->monthOfYear = $monthOfYear;

        return $this;
    }

    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getMaxOccurrences(): ?int
    {
        return $this->maxOccurrences;
    }

    public function setMaxOccurrences(?int $maxOccurrences): self
    {
        $this->maxOccurrences = $maxOccurrences;

        return $this;
    }

    public function getCurrentOccurrences(): int
    {
        return $this->currentOccurrences;
    }

    public function setCurrentOccurrences(int $currentOccurrences): self
    {
        $this->currentOccurrences = $currentOccurrences;

        return $this;
    }

    public function incrementOccurrences(): self
    {
        $this->currentOccurrences++;

        return $this;
    }

    public function getNextOccurrenceDate(): DateTimeInterface
    {
        return $this->nextOccurrenceDate;
    }

    public function setNextOccurrenceDate(DateTimeInterface $nextOccurrenceDate): self
    {
        $this->nextOccurrenceDate = $nextOccurrenceDate;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getTemplateTask(): Task
    {
        return $this->templateTask;
    }

    public function setTemplateTask(Task $templateTask): self
    {
        $this->templateTask = $templateTask;

        return $this;
    }

    public function getTimeOfDay(): ?DateTimeInterface
    {
        return $this->timeOfDay;
    }

    public function setTimeOfDay(?DateTimeInterface $timeOfDay): self
    {
        $this->timeOfDay = $timeOfDay;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function shouldStopRecurrence(): bool
    {
        // Check if we've reached the end date
        if ($this->endDate && new DateTime() > $this->endDate) {
            return true;
        }

        // Check if we've reached max occurrences
        return (bool) ($this->maxOccurrences && $this->currentOccurrences >= $this->maxOccurrences);
    }

    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_DAILY,
            self::TYPE_WEEKLY,
            self::TYPE_MONTHLY,
            self::TYPE_YEARLY,
            self::TYPE_CUSTOM,
        ];
    }
}
