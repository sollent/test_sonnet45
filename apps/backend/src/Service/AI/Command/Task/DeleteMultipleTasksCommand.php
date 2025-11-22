<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Task;

use App\Entity\User;
use App\Service\AI\Command\Base\AbstractVoiceCommand;
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
 * Команда удаления нескольких конкретных задач
 *
 * Обрабатывает действие delete_multiple_tasks из ParsedCommand.
 * Удаляет задачи по списку поисковых запросов.
 * Следует принципу Single Responsibility.
 */
class DeleteMultipleTasksCommand extends AbstractVoiceCommand
{
    private TaskFinder $taskFinder;

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
        return ParsedCommand::ACTION_DELETE_MULTIPLE_TASKS;
    }

    protected function validateParameters(array $parameters): void
    {
        // Поддержка двух форматов: 'searches' и 'tasks'
        $hasSearches = !empty($parameters['searches']) && is_array($parameters['searches']);
        $hasTasks = !empty($parameters['tasks']) && is_array($parameters['tasks']);

        if (!$hasSearches && !$hasTasks) {
            throw new RuntimeException('Array of task searches is required for multiple deletion');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        // Поддержка обоих форматов параметров
        $searches = $parameters['searches'] ?? $parameters['tasks'] ?? [];
        $deletedTaskIds = [];
        $deletedTasks = [];
        $notFoundSearches = [];

        foreach ($searches as $search) {
            $task = $this->taskFinder->find($search, $user);

            if ($task) {
                $deletedTaskIds[] = $task->getId();
                $deletedTasks[] = [
                    'id'    => $task->getId(),
                    'title' => $task->getTitle(),
                ];

                $this->entityManager->remove($task);

                $this->logger->info('Delete multiple tasks - deleted task', [
                    'task_id'    => $task->getId(),
                    'task_title' => $task->getTitle(),
                    'search'     => $search,
                ]);
            } else {
                $notFoundSearches[] = $search;

                $this->logger->warning('Delete multiple tasks - task not found', [
                    'search' => $search,
                ]);
            }
        }

        if (empty($deletedTasks)) {
            return CommandResponse::failure(
                'no_tasks_deleted',
                'Не найдено задач для удаления',
                [
                    'searches'  => $searches,
                    'not_found' => $notFoundSearches,
                ],
            );
        }

        $this->flush();

        $message = sprintf('Удалено %d задач', count($deletedTasks));

        if (!empty($notFoundSearches)) {
            $message .= sprintf(' (не найдено: %d)', count($notFoundSearches));
        }

        return CommandResponse::success(
            'multiple_tasks_deleted',
            $message,
            [
                'deleted_count'      => count($deletedTasks),
                'taskIds'            => $deletedTaskIds,
                'deleted_tasks'      => $deletedTasks,
                'not_found_count'    => count($notFoundSearches),
                'not_found_searches' => $notFoundSearches,
            ],
        );
    }
}
