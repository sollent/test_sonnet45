<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Batch;

use App\Entity\User;
use App\Service\AI\Command\Base\AbstractBatchCommand;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Response\CommandResponse;
use App\Service\AI\Service\DateTimeResolver;
use App\Service\AI\Service\PriorityMapper;
use App\Service\AI\Service\StatusMapper;
use App\Service\AI\Service\TaskFinder;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Команда массового обновления задач
 *
 * Обрабатывает действие bulk_update из ParsedCommand.
 * Обновляет статус, приоритет или даты у нескольких задач.
 * Расширяет AbstractBatchCommand для повторного использования логики.
 */
class BulkUpdateCommand extends AbstractBatchCommand
{
    private DateTimeResolver $dateTimeResolver;
    private PriorityMapper $priorityMapper;
    private StatusMapper $statusMapper;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        TaskFinder $taskFinder,
        DateTimeResolver $dateTimeResolver,
        PriorityMapper $priorityMapper,
        StatusMapper $statusMapper
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger, $taskFinder);
        $this->dateTimeResolver = $dateTimeResolver;
        $this->priorityMapper = $priorityMapper;
        $this->statusMapper = $statusMapper;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_BULK_UPDATE;
    }

    protected function validateBatchParameters(array $parameters): void
    {
        // Должно быть хотя бы одно изменение
        $hasUpdate = isset($parameters['new_status'])
            || isset($parameters['new_priority'])
            || isset($parameters['new_due_date']);

        if (!$hasUpdate) {
            throw new RuntimeException('At least one update parameter is required (status, priority or due_date)');
        }
    }

    protected function getOperationName(): string
    {
        return 'обновление';
    }

    protected function processSingleTask($task, array $parameters): void
    {
        $changes = [];

        // Обновление статуса
        if (isset($parameters['new_status'])) {
            $newStatus = $this->statusMapper->map($parameters['new_status']);
            $task->setStatus($newStatus);
            $changes[] = sprintf('статус → %s', $newStatus->value);
        }

        // Обновление приоритета
        if (isset($parameters['new_priority'])) {
            $newPriority = $this->priorityMapper->map($parameters['new_priority']);
            $task->setPriority($newPriority);
            $changes[] = sprintf('приоритет → %s', $newPriority->value);
        }

        // Обновление дат
        if (isset($parameters['new_due_date'])) {
            $dateRange = $this->dateTimeResolver->resolveDateRange([
                'due_date' => $parameters['new_due_date']
            ]);

            if ($dateRange['start'] !== null) {
                $task->setStartDate($dateRange['start']);
            }
            if ($dateRange['due'] !== null) {
                $task->setDueDate($dateRange['due']);
                $changes[] = sprintf('срок → %s', $dateRange['due']->format('d.m.Y'));
            }
        }

        $this->logger->info('Bulk update task', [
            'task_id' => $task->getId(),
            'task_title' => $task->getTitle(),
            'changes' => $changes,
        ]);
    }

    protected function createSuccessResponse(array $processedTasks, array $parameters): CommandResponse
    {
        // Формируем описание изменений
        $changes = [];
        if (isset($parameters['new_status'])) {
            $changes[] = sprintf('статус → %s', $parameters['new_status']);
        }
        if (isset($parameters['new_priority'])) {
            $changes[] = sprintf('приоритет → %s', $parameters['new_priority']);
        }
        if (isset($parameters['new_due_date'])) {
            $changes[] = sprintf('срок → %s', $parameters['new_due_date']);
        }

        $changesText = implode(', ', $changes);

        return CommandResponse::success(
            'bulk_update_completed',
            sprintf('Обновлено %d задач (%s)', count($processedTasks), $changesText),
            [
                'updated_count' => count($processedTasks),
                'changes' => $changes,
                'tasks' => array_map(fn($task) => [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'status' => $task->getStatus()->value,
                    'priority' => $task->getPriority()->value,
                    'dueDate' => $task->getDueDate()?->format('c'),
                ], $processedTasks),
            ]
        );
    }
}