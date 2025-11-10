<?php

namespace App\Service\Recurrence\Strategy;

use App\Entity\RecurrenceRule;
use App\Service\Recurrence\RecurrenceStrategyInterface;

class YearlyRecurrenceStrategy implements RecurrenceStrategyInterface
{
    public function calculateNextOccurrence(\DateTimeInterface $currentDate, RecurrenceRule $rule): ?\DateTimeInterface
    {
        $dayOfMonth = $rule->getDayOfMonth() ?? 1;
        $monthOfYear = $rule->getMonthOfYear() ?? 1;
        $next = clone $currentDate;

        // Move to next year
        if ($next instanceof \DateTime) {
            $nextYear = (int)$next->format('Y') + 1;
            // Get days in target month using native DateTime
            $daysInMonth = (int)(new \DateTime("{$nextYear}-{$monthOfYear}-01"))->format('t');
            $next->setDate($nextYear, $monthOfYear, min($dayOfMonth, $daysInMonth));
        } else {
            $nextYear = (int)$next->format('Y') + 1;
            // Get days in target month using native DateTime
            $daysInMonth = (int)(new \DateTime("{$nextYear}-{$monthOfYear}-01"))->format('t');
            $next = $next->setDate($nextYear, $monthOfYear, min($dayOfMonth, $daysInMonth));
        }
        
        // Apply time of day if set
        if ($rule->getTimeOfDay()) {
            $time = $rule->getTimeOfDay();
            $next = $next->setTime(
                (int)$time->format('H'),
                (int)$time->format('i'),
                0
            );
        }
        
        // Check end conditions
        if ($rule->getEndDate() && $next > $rule->getEndDate()) {
            return null;
        }
        
        if ($rule->getMaxOccurrences() && $rule->getCurrentOccurrences() >= $rule->getMaxOccurrences()) {
            return null;
        }
        
        return $next;
    }
    
    public function supports(string $recurrenceType): bool
    {
        return $recurrenceType === RecurrenceRule::TYPE_YEARLY;
    }
    
    public function getPreviewDates(\DateTimeInterface $startDate, RecurrenceRule $rule, int $count = 5): array
    {
        $dates = [];
        $current = clone $startDate;
        
        for ($i = 0; $i < $count; $i++) {
            $next = $this->calculateNextOccurrence($current, $rule);
            if (!$next) {
                break;
            }
            $dates[] = $next;
            $current = $next;
        }
        
        return $dates;
    }
}
