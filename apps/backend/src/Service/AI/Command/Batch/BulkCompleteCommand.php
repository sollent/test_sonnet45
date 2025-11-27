<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Batch;

use App\Entity\User;
use App\Enum\TaskStatus;
use App\Service\AI\Command\Base\AbstractBatchCommand;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Response\CommandResponse;
use App\Service\AI\Response\ResponseBuilder;
use App\Service\AI\Service\TaskFinder;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Команда массового завершения задач
 *
 * Обрабатывает действие bulk_complete из ParsedCommand.
 * Завершает все задачи, соответствующие фильтрам.
 * Следует принципу Single Responsibility.
 */
class BulkCompleteCommand extends AbstractBatchCommand
{
    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        TaskFinder $taskFinder,
        ResponseBuilder $responseBuilder,
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger);
        $this->taskFinder = $taskFinder;
        $this->responseBuilder = $responseBuilder;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_BULK_COMPLETE;
    }

    protected function validateParameters(array $parameters): void
    {
        // Фильтры не обязательны - можно завершить все задачи
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $filters = $parameters['filters'] ?? $parameters;

        return $this->processBatchByFilters($filters, $user);
    }

    protected function shouldProcessTask($task): bool
    {
        // Обрабатываем только незавершенные задачи
        return $task->getStatus() !== TaskStatus::COMPLETED;
    }

    protected function processTask($task, User $user): void
    {
        $this->taskService->completeTask($task, $user);
    }

    protected function getNoTasksResponse(array $filters): CommandResponse
    {
        return CommandResponse::failure(
            'no_tasks_to_complete',
            'Не найдено задач для завершения',
            ['filters' => $filters],
        );
    }

    protected function getBatchSuccessResponse(
        int $successCount,
        int $totalCount,
        array $processed,
        array $errors = [],
        array $notFound = [],
    ): CommandResponse {
        return $this->responseBuilder->batchSuccess(
            'bulk_completed',
            'Завершено задач',
            $successCount,
            $totalCount,
            $processed,
            $notFound,
            $errors,
        );
    }

    protected function getNoSuccessResponse(array $notFound, array $errors): CommandResponse
    {
        return $this->responseBuilder->batchFailed(
            'no_tasks_completed',
            'Не удалось завершить ни одной задачи',
            $notFound,
            $errors,
        );
    }
}
