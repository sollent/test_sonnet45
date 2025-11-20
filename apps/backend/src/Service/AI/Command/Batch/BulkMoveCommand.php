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
use RuntimeException;

/**
 * Команда массового перемещения задач к новому родителю
 *
 * Обрабатывает действие bulk_move из ParsedCommand.
 * Перемещает несколько задач к одному новому родителю.
 * Расширяет AbstractBatchCommand для повторного использования логики.
 */
class BulkMoveCommand extends AbstractBatchCommand
{
    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        TaskFinder $taskFinder
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger, $taskFinder);
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_BULK_MOVE;
    }

    protected function validateBatchParameters(array $parameters): void
    {
        // new_parent_search может быть null (перемещение в корень)
        // Дополнительная валидация не требуется
    }

    protected function getOperationName(): string
    {
        return 'перемещение';
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        // Сначала найдем нового родителя если указан
        $newParent = null;
        $newParentTitle = '(корень)';

        if (!empty($parameters['new_parent_search'])) {
            $newParent = $this->taskFinder->find($parameters['new_parent_search'], $user);

            if (!$newParent) {
                return CommandResponse::failure(
                    'new_parent_not_found',
                    sprintf('Новая родительская задача "%s" не найдена', $parameters['new_parent_search']),
                    ['search' => $parameters['new_parent_search']]
                );
            }

            $newParentTitle = $newParent->getTitle();
        }

        // Теперь найдем задачи для перемещения
        $tasks = $this->findTasks($parameters, $user);

        if (empty($tasks)) {
            return $this->createNoTasksFoundResponse($parameters);
        }

        // Фильтруем задачи которые могут создать циклическую зависимость
        $validTasks = [];
        $skippedTasks = [];

        foreach ($tasks as $task) {
            if ($newParent && $this->wouldCreateCycle($task, $newParent)) {
                $skippedTasks[] = $task;
                $this->logger->warning('Skipping task due to circular dependency', [
                    'task_id' => $task->getId(),
                    'task_title' => $task->getTitle(),
                    'new_parent_id' => $newParent->getId(),
                ]);
            } else {
                $validTasks[] = $task;
            }
        }

        if (empty($validTasks)) {
            return CommandResponse::failure(
                'circular_dependency',
                'Невозможно переместить задачи: все создадут циклическую зависимость',
                [
                    'tasks_count' => count($tasks),
                    'new_parent' => $newParentTitle,
                ]
            );
        }

        // Перемещаем валидные задачи
        foreach ($validTasks as $task) {
            $this->processSingleTask($task, ['new_parent' => $newParent]);
        }

        $this->flush();

        // Формируем ответ
        $response = $this->createSuccessResponse($validTasks, $parameters);

        // Добавляем информацию о пропущенных задачах
        if (!empty($skippedTasks)) {
            $data = $response->getData();
            $data['skipped_count'] = count($skippedTasks);
            $data['skipped_tasks'] = array_map(fn($task) => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'reason' => 'circular_dependency',
            ], $skippedTasks);

            return CommandResponse::success(
                $response->getType(),
                $response->getMessage() . sprintf(' (%d пропущено из-за циклической зависимости)', count($skippedTasks)),
                $data,
                $response->getErrors()
            );
        }

        return $response;
    }

    protected function processSingleTask($task, array $parameters): void
    {
        $newParent = $parameters['new_parent'] ?? null;
        $task->setParent($newParent);

        $this->logger->info('Bulk move task', [
            'task_id' => $task->getId(),
            'task_title' => $task->getTitle(),
            'new_parent_id' => $newParent?->getId(),
            'new_parent_title' => $newParent?->getTitle() ?? '(корень)',
        ]);
    }

    protected function createSuccessResponse(array $processedTasks, array $parameters): CommandResponse
    {
        $newParentTitle = isset($parameters['new_parent_search'])
            ? ($parameters['new_parent_search'] ?: '(корень)')
            : '(корень)';

        return CommandResponse::success(
            'bulk_move_completed',
            sprintf('Перемещено %d задач в "%s"', count($processedTasks), $newParentTitle),
            [
                'moved_count' => count($processedTasks),
                'new_parent' => $newParentTitle,
                'tasks' => array_map(fn($task) => [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                ], $processedTasks),
            ]
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