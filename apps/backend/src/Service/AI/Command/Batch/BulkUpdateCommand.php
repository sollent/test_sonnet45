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
    private array $updates = [];

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
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger);
        $this->taskFinder = $taskFinder;
        $this->dateTimeResolver = $dateTimeResolver;
        $this->priorityMapper = $priorityMapper;
        $this->statusMapper = $statusMapper;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_BULK_UPDATE;
    }

    protected function validateParameters(array $parameters): void
    {
        // Должно быть хотя бы одно изменение
        $hasUpdate = isset($parameters['new_status'])
            || isset($parameters['new_priority'])
            || isset($parameters['new_due_date']);

        if (!$hasUpdate) {
            throw new RuntimeException('At least one update parameter is required (status, priority or due_date)');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        // Подготавливаем обновления
        $this->updates = [];

        if (isset($parameters['new_status'])) {
            $this->updates['status'] = $this->statusMapper->map($parameters['new_status']);
        }

        if (isset($parameters['new_priority'])) {
            $this->updates['priority'] = $this->priorityMapper->map($parameters['new_priority']);
        }

        if (isset($parameters['new_due_date'])) {
            $dateRange = $this->dateTimeResolver->resolveDateRange([
                'due_date' => $parameters['new_due_date']
            ]);
            $this->updates['start_date'] = $dateRange['start'];
            $this->updates['due_date'] = $dateRange['due'];
        }

        return $this->processBatchByFilters($parameters, $user);
    }

    protected function shouldProcessTask($task): bool
    {
        // Обновляем все найденные задачи
        return true;
    }

    protected function processTask($task, User $user): void
    {
        $changes = [];

        if (isset($this->updates['status'])) {
            $task->setStatus($this->updates['status']);
            $changes[] = sprintf('статус → %s', $this->updates['status']->value);
        }

        if (isset($this->updates['priority'])) {
            $task->setPriority($this->updates['priority']);
            $changes[] = sprintf('приоритет → %s', $this->updates['priority']->value);
        }

        if (isset($this->updates['start_date'])) {
            $task->setStartDate($this->updates['start_date']);
        }

        if (isset($this->updates['due_date'])) {
            $task->setDueDate($this->updates['due_date']);
            $changes[] = sprintf('срок → %s', $this->updates['due_date']?->format('d.m.Y'));
        }

        $this->logger->info('Bulk update task', [
            'task_id' => $task->getId(),
            'task_title' => $task->getTitle(),
            'changes' => $changes,
        ]);
    }

    protected function getNoTasksResponse(array $filters): CommandResponse
    {
        return CommandResponse::failure(
            'no_tasks_found',
            'Не найдено задач для обновления по указанным критериям',
            ['filters' => $filters]
        );
    }

    protected function getBatchSuccessResponse(
        int $successCount,
        int $totalCount,
        array $processed,
        array $errors = [],
        array $notFound = []
    ): CommandResponse {
        // Формируем описание изменений
        $changes = [];
        if (isset($this->updates['status'])) {
            $changes[] = sprintf('статус → %s', $this->updates['status']->value);
        }
        if (isset($this->updates['priority'])) {
            $changes[] = sprintf('приоритет → %s', $this->updates['priority']->value);
        }
        if (isset($this->updates['due_date'])) {
            $changes[] = sprintf('срок → %s', $this->updates['due_date']?->format('d.m.Y'));
        }

        $changesText = implode(', ', $changes);

        return CommandResponse::success(
            'bulk_update_completed',
            sprintf('Обновлено %d задач (%s)', $successCount, $changesText),
            [
                'updated_count' => $successCount,
                'changes' => $changes,
                'tasks' => $processed,
                'errors' => $errors,
            ]
        );
    }

    protected function getNoSuccessResponse(array $notFound, array $errors): CommandResponse
    {
        return CommandResponse::failure(
            'bulk_update_failed',
            'Не удалось обновить ни одной задачи',
            [
                'not_found' => $notFound,
                'errors' => $errors,
            ]
        );
    }
}