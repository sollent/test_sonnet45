<?php

namespace App\Dto\Response\Recurrence;

use App\Entity\RecurrenceRule;

class RecurrenceRuleDto
{
    public int $id;
    public string $recurrenceType;
    public ?int $interval = null;
    public ?array $daysOfWeek = null;
    public ?int $dayOfMonth = null;
    public ?int $monthOfYear = null;
    public ?string $endDate = null;
    public ?int $maxOccurrences = null;
    public int $currentOccurrences;
    public string $nextOccurrenceDate;
    public ?string $timeOfDay = null;
    public bool $isActive;
    public int $templateTaskId;
    public string $createdAt;
    public array $previewDates = [];

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
     * OPTIMIZED: Create DTO from raw database array
     */
    public static function fromRawData(array $data): self
    {
        $dto = new self();
        $dto->id = (int)$data['id'];
        $dto->recurrenceType = $data['recurrence_type'];
        $dto->interval = isset($data['interval']) ? (int)$data['interval'] : null;
        $dto->daysOfWeek = $data['days_of_week'] ? json_decode($data['days_of_week'], true) : null;
        $dto->dayOfMonth = isset($data['day_of_month']) ? (int)$data['day_of_month'] : null;
        $dto->monthOfYear = isset($data['month_of_year']) ? (int)$data['month_of_year'] : null;
        $dto->endDate = $data['end_date'] ?? null;
        $dto->maxOccurrences = isset($data['max_occurrences']) ? (int)$data['max_occurrences'] : null;
        $dto->currentOccurrences = (int)$data['current_occurrences'];
        $dto->nextOccurrenceDate = $data['next_occurrence_date'];
        $dto->timeOfDay = $data['time_of_day'] ?? null;
        $dto->isActive = (bool)$data['is_active'];

        // templateTaskId and createdAt not available in JOIN query (not needed for calendar view)
        $dto->templateTaskId = 0;
        $dto->createdAt = '';
        $dto->previewDates = [];

        return $dto;
    }
}
