<?php

declare(strict_types=1);

namespace App\Dto\Request\Recurrence;

use DateTimeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CreateRecurrenceDto
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['daily', 'weekly', 'monthly', 'yearly', 'custom'])]
    public string $recurrenceType;

    #[Assert\Positive]
    public ?int $interval = null; // For custom type - every N days

    #[Assert\All([
        new Assert\Range(min: 1, max: 7), // 1 = Monday, 7 = Sunday
    ])]
    public ?array $daysOfWeek = null; // For weekly

    #[Assert\Range(min: 1, max: 31)]
    public ?int $dayOfMonth = null; // For monthly

    #[Assert\Range(min: 1, max: 12)]
    public ?int $monthOfYear = null; // For yearly

    #[Assert\Type(DateTimeInterface::class)]
    public ?DateTimeInterface $endDate = null;

    #[Assert\Positive]
    public ?int $maxOccurrences = null;

    #[Assert\Type(DateTimeInterface::class)]
    public ?DateTimeInterface $timeOfDay = null; // Time when task should be created

    public function toArray(): array
    {
        return [
            'recurrenceType' => $this->recurrenceType,
            'interval'       => $this->interval,
            'daysOfWeek'     => $this->daysOfWeek,
            'dayOfMonth'     => $this->dayOfMonth,
            'monthOfYear'    => $this->monthOfYear,
            'endDate'        => $this->endDate,
            'maxOccurrences' => $this->maxOccurrences,
            'timeOfDay'      => $this->timeOfDay,
        ];
    }
}
