<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Task;

use App\Entity\User;
use App\Enum\TaskStatus;
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
 * Команда завершения нескольких конкретных задач
 *
 * Обрабатывает действие complete_multiple_tasks из ParsedCommand.
 * Завершает задачи по списку поисковых запросов.
 * Следует принципу Single Responsibility.
 */
class CompleteMultipleTasksCommand extends AbstractVoiceCommand
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
        return ParsedCommand::ACTION_COMPLETE_MULTIPLE_TASKS;
    }

    protected function validateParameters(array $parameters): void
    {
        // Поддержка двух форматов: 'searches' и 'tasks'
        $hasSearches = !empty($parameters['searches']) && is_array($parameters['searches']);
        $hasTasks = !empty($parameters['tasks']) && is_array($parameters['tasks']);

        if (!$hasSearches && !$hasTasks) {
            throw new RuntimeException('Array of task searches is required for multiple completion');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        // Поддержка обоих форматов параметров
        $searches = $parameters['searches'] ?? $parameters['tasks'] ?? [];
        $completedTasks = [];
        $alreadyCompletedTasks = [];
        $notFoundSearches = [];

        foreach ($searches as $search) {
            $task = $this->taskFinder->find($search, $user);

            if (!$task) {
                $notFoundSearches[] = $search;
                $this->logger->warning('Complete multiple tasks - task not found', [
                    'search' => $search,
                ]);
                continue;
            }

            if ($task->getStatus() === TaskStatus::COMPLETED) {
                $alreadyCompletedTasks[] = [
                    'id'    => $task->getId(),
                    'title' => $task->getTitle(),
                ];
                $this->logger->info('Complete multiple tasks - already completed', [
                    'task_id'    => $task->getId(),
                    'task_title' => $task->getTitle(),
                ]);
                continue;
            }

            $task->setStatus(TaskStatus::COMPLETED);
            $completedTasks[] = [
                'id'    => $task->getId(),
                'title' => $task->getTitle(),
            ];

            $this->logger->info('Complete multiple tasks - completed task', [
                'task_id'    => $task->getId(),
                'task_title' => $task->getTitle(),
                'search'     => $search,
            ]);
        }

        if (empty($completedTasks)) {
            return CommandResponse::failure(
                'no_tasks_completed',
                'Не найдено задач для завершения',
                [
                    'searches'          => $searches,
                    'not_found'         => $notFoundSearches,
                    'already_completed' => $alreadyCompletedTasks,
                ],
            );
        }

        $this->flush();

        $message = sprintf('Завершено %d задач', count($completedTasks));
        $additionalInfo = [];

        if (!empty($alreadyCompletedTasks)) {
            $additionalInfo[] = sprintf('уже завершено: %d', count($alreadyCompletedTasks));
        }

        if (!empty($notFoundSearches)) {
            $additionalInfo[] = sprintf('не найдено: %d', count($notFoundSearches));
        }

        if (!empty($additionalInfo)) {
            $message .= ' (' . implode(', ', $additionalInfo) . ')';
        }

        return CommandResponse::success(
            'multiple_tasks_completed',
            $message,
            [
                'completed_count'         => count($completedTasks),
                'completed_tasks'         => $completedTasks,
                'already_completed_count' => count($alreadyCompletedTasks),
                'already_completed_tasks' => $alreadyCompletedTasks,
                'not_found_count'         => count($notFoundSearches),
                'not_found_searches'      => $notFoundSearches,
            ],
        );
    }
}
