<?php

namespace App\Service\Recurrence\Strategy;

use App\Entity\RecurrenceRule;
use App\Service\Recurrence\RecurrenceStrategyInterface;

class MonthlyRecurrenceStrategy implements RecurrenceStrategyInterface
{
    public function calculateNextOccurrence(\DateTimeInterface $currentDate, RecurrenceRule $rule): ?\DateTimeInterface
    {
        $dayOfMonth = $rule->getDayOfMonth() ?? 1;
        $next = clone $currentDate;
        
        // Move to next month
        if ($next instanceof \DateTime) {
            $next->modify('first day of next month');
            $next->setDate($next->format('Y'), $next->format('n'), min($dayOfMonth, $next->format('t')));
        } else {
            $next = $next->modify('first day of next month');
            $next = $next->setDate($next->format('Y'), $next->format('n'), min($dayOfMonth, $next->format('t')));
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
        return $recurrenceType === RecurrenceRule::TYPE_MONTHLY;
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
