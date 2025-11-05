<?php

namespace App\Dto\Response\Recurrence;

use App\Entity\RecurrenceRule;
use Symfony\Component\Serializer\Annotation\Groups;

class RecurrenceRuleDto implements \JsonSerializable
{
    #[Groups(['task:read'])]
    public int $id;

    #[Groups(['task:read'])]
    public string $recurrenceType;

    #[Groups(['task:read'])]
    public ?int $interval = null;

    #[Groups(['task:read'])]
    public ?array $daysOfWeek = null;

    #[Groups(['task:read'])]
    public ?int $dayOfMonth = null;

    #[Groups(['task:read'])]
    public ?int $monthOfYear = null;

    #[Groups(['task:read'])]
    public ?string $endDate = null;

    #[Groups(['task:read'])]
    public ?int $maxOccurrences = null;

    #[Groups(['task:read'])]
    public int $currentOccurrences;

    #[Groups(['task:read'])]
    public string $nextOccurrenceDate;

    #[Groups(['task:read'])]
    public ?string $timeOfDay = null;

    #[Groups(['task:read'])]
    public bool $isActive;

    #[Groups(['task:read'])]
    public int $templateTaskId;

    #[Groups(['task:read'])]
    public string $createdAt;

    #[Groups(['task:read'])]
    public array $previewDates = [];

    /**
     * Create DTO from RecurrenceRule Entity (Database → DTO)
     *
     * @param RecurrenceRule $rule The recurrence rule entity
     * @param array $previewDates Array of \DateTimeImmutable objects
     * @return self
     */
    public static function fromEntity(RecurrenceRule $rule, array $previewDates = []): self
    {
        $dto = new self();
        $dto->id = $rule->getId();
        $dto->recurrenceType = $rule->getRecurrenceType();
        $dto->interval = $rule->getInterval();
        $dto->daysOfWeek = $rule->getDaysOfWeek();
        $dto->dayOfMonth = $rule->getDayOfMonth();
        $dto->monthOfYear = $rule->getMonthOfYear();
        $dto->endDate = $rule->getEndDate()?->format('Y-m-d');
        $dto->maxOccurrences = $rule->getMaxOccurrences();
        $dto->currentOccurrences = $rule->getCurrentOccurrences();
        $dto->nextOccurrenceDate = $rule->getNextOccurrenceDate()->format('Y-m-d H:i:s');
        $dto->timeOfDay = $rule->getTimeOfDay()?->format('H:i');
        $dto->isActive = $rule->isActive();
        $dto->templateTaskId = $rule->getTemplateTask()->getId();
        $dto->createdAt = $rule->getCreatedAt()->format('Y-m-d H:i:s');

        // Format preview dates
        $dto->previewDates = array_map(
            fn($date) => $date->format('Y-m-d H:i:s'),
            $previewDates
        );

        return $dto;
    }

    /**
     * Create DTO from cached array (Redis Cache → DTO)
     *
     * Expected array structure:
     * [
     *     'id' => int,
     *     'recurrenceType' => string ('daily'|'weekly'|'monthly'|'yearly'),
     *     'interval' => ?int,
     *     'daysOfWeek' => ?array (e.g., [1, 3, 5] for Mon, Wed, Fri),
     *     'dayOfMonth' => ?int (1-31),
     *     'monthOfYear' => ?int (1-12),
     *     'endDate' => ?string (ISO date: '2025-12-31'),
     *     'maxOccurrences' => ?int,
     *     'currentOccurrences' => int,
     *     'nextOccurrenceDate' => string (ISO 8601: '2025-01-15 10:30:00'),
     *     'timeOfDay' => ?string (HH:MM format: '14:30'),
     *     'isActive' => bool,
     *     'templateTaskId' => int,
     *     'createdAt' => string (ISO 8601),
     *     'previewDates' => array (array of ISO 8601 strings),
     * ]
     *
     * @param array $data The deserialized array from cache
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->id = (int) $data['id'];
        $dto->recurrenceType = (string) $data['recurrenceType'];
        $dto->interval = $data['interval'] ?? null;
        $dto->daysOfWeek = $data['daysOfWeek'] ?? null;
        $dto->dayOfMonth = $data['dayOfMonth'] ?? null;
        $dto->monthOfYear = $data['monthOfYear'] ?? null;
        $dto->endDate = $data['endDate'] ?? null;
        $dto->maxOccurrences = $data['maxOccurrences'] ?? null;
        $dto->currentOccurrences = (int) ($data['currentOccurrences'] ?? 0);
        $dto->nextOccurrenceDate = (string) $data['nextOccurrenceDate'];
        $dto->timeOfDay = $data['timeOfDay'] ?? null;
        $dto->isActive = (bool) ($data['isActive'] ?? true);
        $dto->templateTaskId = (int) $data['templateTaskId'];
        $dto->createdAt = (string) $data['createdAt'];
        $dto->previewDates = $data['previewDates'] ?? [];

        return $dto;
    }

    /**
     * Serialize DTO to JSON-compatible array
     *
     * This method is called by json_encode() to properly serialize the DTO.
     * All dates are already stored as strings, so we just return all properties.
     *
     * @return array JSON-serializable array representation
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'recurrenceType' => $this->recurrenceType,
            'interval' => $this->interval,
            'daysOfWeek' => $this->daysOfWeek,
            'dayOfMonth' => $this->dayOfMonth,
            'monthOfYear' => $this->monthOfYear,
            'endDate' => $this->endDate,
            'maxOccurrences' => $this->maxOccurrences,
            'currentOccurrences' => $this->currentOccurrences,
            'nextOccurrenceDate' => $this->nextOccurrenceDate,
            'timeOfDay' => $this->timeOfDay,
            'isActive' => $this->isActive,
            'templateTaskId' => $this->templateTaskId,
            'createdAt' => $this->createdAt,
            'previewDates' => $this->previewDates,
        ];
    }
}
