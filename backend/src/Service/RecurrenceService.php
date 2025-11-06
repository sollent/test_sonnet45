<?php

namespace App\Service;

use App\Entity\RecurrenceRule;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatus;
use App\Repository\Database\RecurrenceRuleRepository;
use App\Repository\Database\TaskRepository;
use App\Service\Recurrence\RecurrenceStrategyInterface;
use App\Service\Recurrence\Strategy\CustomRecurrenceStrategy;
use App\Service\Recurrence\Strategy\DailyRecurrenceStrategy;
use App\Service\Recurrence\Strategy\WeeklyRecurrenceStrategy;
use App\Service\Recurrence\Strategy\MonthlyRecurrenceStrategy;
use App\Service\Recurrence\Strategy\YearlyRecurrenceStrategy;
use Psr\Log\LoggerInterface;

class RecurrenceService
{
    /** @var RecurrenceStrategyInterface[] */
    private array $strategies;

    public function __construct(
        private readonly RecurrenceRuleRepository $recurrenceRepository,
        private readonly TaskRepository $taskRepository,
        private readonly LoggerInterface $logger
    ) {
        // Initialize strategies
        $this->strategies = [
            new DailyRecurrenceStrategy(),
            new WeeklyRecurrenceStrategy(),
            new MonthlyRecurrenceStrategy(),
            new YearlyRecurrenceStrategy(),
            new CustomRecurrenceStrategy(),
        ];
    }

    /**
     * Create a recurrence rule for a task
     */
    public function createRecurrenceRule(
        Task $templateTask,
        string $recurrenceType,
        array $options = []
    ): RecurrenceRule {
        $rule = new RecurrenceRule();
        $rule->setTemplateTask($templateTask);
        $rule->setCreatedBy($templateTask->getUser());
        $rule->setRecurrenceType($recurrenceType);
        
        // Set template task as recurring template
        $templateTask->setIsRecurringTemplate(true);
        
        // Set options based on recurrence type
        switch ($recurrenceType) {
            case RecurrenceRule::TYPE_DAILY:
                // No additional options needed
                break;
                
            case RecurrenceRule::TYPE_WEEKLY:
                $rule->setDaysOfWeek($options['daysOfWeek'] ?? [1]); // Default to Monday
                break;
                
            case RecurrenceRule::TYPE_MONTHLY:
                $rule->setDayOfMonth($options['dayOfMonth'] ?? 1);
                break;
                
            case RecurrenceRule::TYPE_YEARLY:
                $rule->setDayOfMonth($options['dayOfMonth'] ?? 1);
                $rule->setMonthOfYear($options['monthOfYear'] ?? 1);
                break;
                
            case RecurrenceRule::TYPE_CUSTOM:
                $rule->setInterval($options['interval'] ?? 1);
                break;
        }
        
        // Set optional parameters
        if (isset($options['endDate'])) {
            if (is_string($options['endDate'])) {
                $endDate = new \DateTimeImmutable($options['endDate']);
                $rule->setEndDate($endDate);
            } elseif ($options['endDate'] instanceof \DateTimeInterface) {
                $rule->setEndDate($options['endDate']);
            }
        }
        
        if (isset($options['maxOccurrences'])) {
            $rule->setMaxOccurrences($options['maxOccurrences']);
        }
        
        if (isset($options['timeOfDay'])) {
            // Convert string time to DateTimeImmutable
            if (is_string($options['timeOfDay'])) {
                $time = \DateTimeImmutable::createFromFormat('H:i', $options['timeOfDay']);
                if ($time) {
                    $rule->setTimeOfDay($time);
                }
            } elseif ($options['timeOfDay'] instanceof \DateTimeInterface) {
                $rule->setTimeOfDay($options['timeOfDay']);
            }
        }
        
        // Calculate first occurrence
        $startDate = $templateTask->getStartDate() ?? new \DateTimeImmutable();
        $nextOccurrence = $this->calculateNextOccurrence($startDate, $rule);
        
        if (!$nextOccurrence) {
            throw new \InvalidArgumentException('Unable to calculate next occurrence for the given rule');
        }
        
        $rule->setNextOccurrenceDate($nextOccurrence);
        
        $this->recurrenceRepository->save($rule);
        
        return $rule;
    }

    /**
     * Process all active recurrence rules and generate tasks
     */
    public function processRecurrenceRules(\DateTimeInterface $now = null): int
    {
        $now = $now ?? new \DateTimeImmutable();
        $processedCount = 0;
        
        // First, deactivate expired rules
        $this->recurrenceRepository->deactivateExpiredRules($now);
        
        // Get active rules that need processing
        $rules = $this->recurrenceRepository->findActiveRulesToProcess($now);
        
        foreach ($rules as $rule) {
            try {
                $this->generateTaskFromRule($rule, $now);
                $processedCount++;
            } catch (\Exception $e) {
                $this->logger->error('Failed to process recurrence rule', [
                    'rule_id' => $rule->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $processedCount;
    }

    /**
     * Generate a task from a recurrence rule
     */
    public function generateTaskFromRule(RecurrenceRule $rule, \DateTimeInterface $now = null): Task
    {
        $now = $now ?? new \DateTimeImmutable();
        $templateTask = $rule->getTemplateTask();
        
        // Create new task from template
        $newTask = new Task();
        $newTask->setTitle($templateTask->getTitle());
        $newTask->setDescription($templateTask->getDescription());
        $newTask->setStatus(TaskStatus::PENDING);
        $newTask->setPriority($templateTask->getPriority());
        $newTask->setUser($templateTask->getUser());
        $newTask->setGeneratedFromRule($rule);
        
        // Set dates based on next occurrence
        $nextOccurrence = $rule->getNextOccurrenceDate();
        $newTask->setStartDate(new \DateTimeImmutable($nextOccurrence->format('Y-m-d H:i:s')));
        
        // If template has a duration (difference between start and due), apply it
        if ($templateTask->getStartDate() && $templateTask->getDueDate()) {
            $duration = $templateTask->getStartDate()->diff($templateTask->getDueDate());
            $dueDate = clone $nextOccurrence;
            $dueDate->add($duration);
            $newTask->setDueDate(new \DateTimeImmutable($dueDate->format('Y-m-d H:i:s')));
        } elseif ($templateTask->getDueDate()) {
            // If only due date is set, use the same time offset
            $newTask->setDueDate(new \DateTimeImmutable($nextOccurrence->format('Y-m-d H:i:s')));
        }
        
        // Copy tags
        foreach ($templateTask->getTags() as $tag) {
            $newTask->addTag($tag);
        }
        
        // Save the new task
        $this->taskRepository->save($newTask);
        
        // Update the rule
        $rule->incrementOccurrences();
        
        // Calculate next occurrence
        $nextNextOccurrence = $this->calculateNextOccurrence($nextOccurrence, $rule);
        
        if ($nextNextOccurrence) {
            $rule->setNextOccurrenceDate($nextNextOccurrence);
        } else {
            // No more occurrences, deactivate the rule
            $rule->setIsActive(false);
        }
        
        $this->recurrenceRepository->save($rule);
        
        $this->logger->info('Generated task from recurrence rule', [
            'rule_id' => $rule->getId(),
            'task_id' => $newTask->getId(),
            'next_occurrence' => $nextNextOccurrence ? $nextNextOccurrence->format('Y-m-d H:i:s') : 'none',
        ]);
        
        return $newTask;
    }

    /**
     * Calculate the next occurrence date for a rule
     */
    public function calculateNextOccurrence(\DateTimeInterface $currentDate, RecurrenceRule $rule): ?\DateTimeInterface
    {
        $strategy = $this->getStrategy($rule->getRecurrenceType());
        
        if (!$strategy) {
            throw new \InvalidArgumentException('No strategy found for recurrence type: ' . $rule->getRecurrenceType());
        }
        
        return $strategy->calculateNextOccurrence($currentDate, $rule);
    }

    /**
     * Get preview of upcoming occurrences
     */
    public function getPreviewDates(\DateTimeInterface $startDate, RecurrenceRule $rule, int $count = 5): array
    {
        $strategy = $this->getStrategy($rule->getRecurrenceType());
        
        if (!$strategy) {
            return [];
        }
        
        return $strategy->getPreviewDates($startDate, $rule, $count);
    }

    /**
     * Update a recurrence rule
     */
    public function updateRecurrenceRule(RecurrenceRule $rule, array $options): RecurrenceRule
    {
        // Update recurrence type if changed
        if (isset($options['recurrenceType'])) {
            $rule->setRecurrenceType($options['recurrenceType']);
        }
        
        // Update type-specific options
        if (isset($options['interval'])) {
            $rule->setInterval($options['interval']);
        }
        
        if (isset($options['daysOfWeek'])) {
            $rule->setDaysOfWeek($options['daysOfWeek']);
        }
        
        if (isset($options['dayOfMonth'])) {
            $rule->setDayOfMonth($options['dayOfMonth']);
        }
        
        if (isset($options['monthOfYear'])) {
            $rule->setMonthOfYear($options['monthOfYear']);
        }
        
        // Update end conditions
        if (array_key_exists('endDate', $options)) {
            if (is_string($options['endDate'])) {
                $endDate = new \DateTimeImmutable($options['endDate']);
                $rule->setEndDate($endDate);
            } elseif ($options['endDate'] instanceof \DateTimeInterface) {
                $rule->setEndDate($options['endDate']);
            } else {
                $rule->setEndDate(null);
            }
        }
        
        if (array_key_exists('maxOccurrences', $options)) {
            $rule->setMaxOccurrences($options['maxOccurrences']);
        }
        
        if (isset($options['timeOfDay'])) {
            // Convert string time to DateTimeImmutable
            if (is_string($options['timeOfDay'])) {
                $time = \DateTimeImmutable::createFromFormat('H:i', $options['timeOfDay']);
                if ($time) {
                    $rule->setTimeOfDay($time);
                }
            } elseif ($options['timeOfDay'] instanceof \DateTimeInterface) {
                $rule->setTimeOfDay($options['timeOfDay']);
            }
        }
        
        // Recalculate next occurrence
        $currentDate = $rule->getNextOccurrenceDate() ?? new \DateTimeImmutable();
        $nextOccurrence = $this->calculateNextOccurrence($currentDate, $rule);
        
        if ($nextOccurrence) {
            $rule->setNextOccurrenceDate($nextOccurrence);
        } else {
            $rule->setIsActive(false);
        }
        
        $this->recurrenceRepository->save($rule);
        
        return $rule;
    }

    /**
     * Delete a recurrence rule
     */
    public function deleteRecurrenceRule(RecurrenceRule $rule): void
    {
        // Mark template task as non-recurring
        $templateTask = $rule->getTemplateTask();
        $templateTask->setIsRecurringTemplate(false);
        $this->taskRepository->save($templateTask);
        
        // Delete the rule
        $this->recurrenceRepository->remove($rule);
    }

    /**
     * Get the appropriate strategy for a recurrence type
     */
    private function getStrategy(string $recurrenceType): ?RecurrenceStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($recurrenceType)) {
                return $strategy;
            }
        }
        
        return null;
    }
}
