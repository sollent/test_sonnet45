<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Batch;

use App\Entity\User;
use App\Service\AI\Command\Base\AbstractBatchCommand;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Response\CommandResponse;
use App\Service\AI\Service\TaskFinder;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Команда массового удаления задач
 *
 * Обрабатывает действие bulk_delete из ParsedCommand.
 * Удаляет несколько задач по заданным критериям.
 * Расширяет AbstractBatchCommand для повторного использования логики.
 */
class BulkDeleteCommand extends AbstractBatchCommand
{
    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        TaskFinder $taskFinder
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger);
        $this->taskFinder = $taskFinder;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_BULK_DELETE;
    }

    protected function validateParameters(array $parameters): void
    {
        // Базовая валидация проверит наличие фильтров
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        return $this->processBatchByFilters($parameters, $user);
    }

    protected function shouldProcessTask($task): bool
    {
        // Всегда удаляем найденные задачи
        return true;
    }

    protected function processTask($task, User $user): void
    {
        $taskTitle = $task->getTitle();
        $taskId = $task->getId();

        // Удаляем задачу через EntityManager
        $this->entityManager->remove($task);

        $this->logger->info('Bulk delete task', [
            'task_id' => $taskId,
            'task_title' => $taskTitle,
        ]);
    }

    protected function getNoTasksResponse(array $filters): CommandResponse
    {
        return CommandResponse::failure(
            'no_tasks_found',
            'Не найдено задач для удаления по указанным критериям',
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
        $message = sprintf('Удалено %d задач', $successCount);

        if (!empty($errors)) {
            $message .= sprintf(' (ошибок: %d)', count($errors));
        }

        return CommandResponse::success(
            'bulk_delete_completed',
            $message,
            [
                'deleted_count' => $successCount,
                'deleted_tasks' => $processed,
                'errors' => $errors,
                'filters' => [],
            ]
        );
    }

    protected function getNoSuccessResponse(array $notFound, array $errors): CommandResponse
    {
        return CommandResponse::failure(
            'bulk_delete_failed',
            'Не удалось удалить ни одной задачи',
            [
                'not_found' => $notFound,
                'errors' => $errors,
            ]
        );
    }
}