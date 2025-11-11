<?php

declare(strict_types=1);

namespace App\Service\Recurrence\Strategy;

use App\Entity\RecurrenceRule;
use App\Service\Recurrence\RecurrenceStrategyInterface;
use DateTime;
use DateTimeInterface;

class DailyRecurrenceStrategy implements RecurrenceStrategyInterface
{
    public function calculateNextOccurrence(DateTimeInterface $currentDate, RecurrenceRule $rule): ?DateTimeInterface
    {
        $next = clone $currentDate;

        if ($next instanceof DateTime) {
            $next->modify('+1 day');
        } else {
            $next = $next->modify('+1 day');
        }

        // Apply time of day if set
        if ($rule->getTimeOfDay()) {
            $time = $rule->getTimeOfDay();
            $next = $next->setTime(
                (int) $time->format('H'),
                (int) $time->format('i'),
                0,
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
        return $recurrenceType === RecurrenceRule::TYPE_DAILY;
    }

    public function getPreviewDates(DateTimeInterface $startDate, RecurrenceRule $rule, int $count = 5): array
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
