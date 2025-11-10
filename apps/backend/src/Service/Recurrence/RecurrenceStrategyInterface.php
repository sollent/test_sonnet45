<?php

namespace App\Service\Recurrence;

use App\Entity\RecurrenceRule;

interface RecurrenceStrategyInterface
{
    /**
     * Calculate the next occurrence date based on the current date and recurrence rule
     */
    public function calculateNextOccurrence(\DateTimeInterface $currentDate, RecurrenceRule $rule): ?\DateTimeInterface;
    
    /**
     * Check if the strategy supports the given recurrence type
     */
    public function supports(string $recurrenceType): bool;
    
    /**
     * Get preview of next N occurrences for UI display
     * 
     * @return \DateTimeInterface[]
     */
    public function getPreviewDates(\DateTimeInterface $startDate, RecurrenceRule $rule, int $count = 5): array;
}
