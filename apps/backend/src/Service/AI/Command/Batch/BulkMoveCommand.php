<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Batch;

use App\Entity\Task;
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

/**
 * Команда массового перемещения задач к новому родителю
 *
 * Обрабатывает действие bulk_move из ParsedCommand.
 * Перемещает несколько задач к одному новому родителю.
 * Расширяет AbstractBatchCommand для повторного использования логики.
 */
class BulkMoveCommand extends AbstractBatchCommand
{
    private ?Task $newParent = null;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        TaskFinder $taskFinder,
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger);
        $this->taskFinder = $taskFinder;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_BULK_MOVE;
    }

    protected function validateParameters(array $parameters): void
    {
        // new_parent_search может быть null (перемещение в корень)
        // Дополнительная валидация не требуется
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        // Сначала найдем нового родителя если указан
        $this->newParent = null;

        if (!empty($parameters['new_parent_search'])) {
            $this->newParent = $this->taskFinder->find($parameters['new_parent_search'], $user);

            if (!$this->newParent) {
                return CommandResponse::failure(
                    'new_parent_not_found',
                    sprintf('Новая родительская задача "%s" не найдена', $parameters['new_parent_search']),
                    ['search' => $parameters['new_parent_search']],
                );
            }
        }

        // Используем стандартную обработку пакетных операций
        return $this->processBatchByFilters($parameters, $user);
    }

    protected function shouldProcessTask($task): bool
    {
        // Проверяем, не создастся ли циклическая зависимость
        if ($this->newParent && $this->wouldCreateCycle($task, $this->newParent)) {
            $this->logger->warning('Skipping task due to circular dependency', [
                'task_id'       => $task->getId(),
                'task_title'    => $task->getTitle(),
                'new_parent_id' => $this->newParent->getId(),
            ]);

            return false;
        }

        return true;
    }

    protected function processTask($task, User $user): void
    {
        $task->setParent($this->newParent);

        $this->logger->info('Bulk move task', [
            'task_id'          => $task->getId(),
            'task_title'       => $task->getTitle(),
            'new_parent_id'    => $this->newParent?->getId(),
            'new_parent_title' => $this->newParent?->getTitle() ?? '(корень)',
        ]);
    }

    protected function getNoTasksResponse(array $filters): CommandResponse
    {
        return CommandResponse::failure(
            'no_tasks_found',
            'Не найдено задач для перемещения по указанным критериям',
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
        $newParentTitle = $this->newParent?->getTitle() ?? '(корень)';

        $message = sprintf('Перемещено %d задач в "%s"', $successCount, $newParentTitle);

        $skippedCount = $totalCount - $successCount;

        if ($skippedCount > 0) {
            $message .= sprintf(' (%d пропущено из-за циклической зависимости)', $skippedCount);
        }

        return CommandResponse::success(
            'bulk_move_completed',
            $message,
            [
                'moved_count' => $successCount,
                'new_parent'  => $newParentTitle,
                'tasks'       => $processed,
                'errors'      => $errors,
            ],
        );
    }

    protected function getNoSuccessResponse(array $notFound, array $errors): CommandResponse
    {
        return CommandResponse::failure(
            'bulk_move_failed',
            'Не удалось переместить ни одной задачи',
            [
                'not_found' => $notFound,
                'errors'    => $errors,
            ],
        );
    }

    /**
     * Проверяет, создастся ли циклическая зависимость при перемещении
     */
    private function wouldCreateCycle(Task $task, Task $potentialParent): bool
    {
        // Проверяем, не является ли потенциальный родитель потомком задачи
        $current = $potentialParent;

        while ($current !== null) {
            if ($current->getId() === $task->getId()) {
                return true; // Нашли цикл
            }
            $current = $current->getParent();
        }

        return false;
    }
}
