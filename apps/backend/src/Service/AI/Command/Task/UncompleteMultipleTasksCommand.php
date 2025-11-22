<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Task;

use App\Entity\User;
use App\Enum\TaskStatus;
use App\Service\AI\Command\Base\AbstractVoiceCommand;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Response\CommandResponse;
use App\Service\AI\Response\ResponseBuilder;
use App\Service\AI\Service\TaskFinder;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Команда отмены завершения нескольких задач
 *
 * Обрабатывает действие uncomplete_multiple_tasks из ParsedCommand.
 * Возвращает задачи в статус PENDING по списку поисковых запросов.
 * Следует принципу Single Responsibility.
 */
class UncompleteMultipleTasksCommand extends AbstractVoiceCommand
{
    private TaskFinder $taskFinder;

    private ResponseBuilder $responseBuilder;

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
        return ParsedCommand::ACTION_UNCOMPLETE_MULTIPLE_TASKS;
    }

    protected function validateParameters(array $parameters): void
    {
        // Поддержка двух форматов: 'searches' и 'tasks'
        $hasSearches = !empty($parameters['searches']) && is_array($parameters['searches']);
        $hasTasks = !empty($parameters['tasks']) && is_array($parameters['tasks']);

        if (!$hasSearches && !$hasTasks) {
            throw new RuntimeException('Array of task searches is required for multiple uncompletion');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        // Поддержка обоих форматов параметров
        $searches = $parameters['searches'] ?? $parameters['tasks'] ?? [];
        $uncompletedTaskEntities = [];
        $alreadyUncompletedTasks = [];
        $notFoundSearches = [];

        foreach ($searches as $search) {
            $task = $this->taskFinder->find($search, $user);

            if (!$task) {
                $notFoundSearches[] = $search;
                $this->logger->warning('Uncomplete multiple tasks - task not found', [
                    'search' => $search,
                ]);
                continue;
            }

            if ($task->getStatus() !== TaskStatus::COMPLETED) {
                $alreadyUncompletedTasks[] = [
                    'id'     => $task->getId(),
                    'title'  => $task->getTitle(),
                    'status' => $task->getStatus()->value,
                ];
                $this->logger->info('Uncomplete multiple tasks - not completed', [
                    'task_id'    => $task->getId(),
                    'task_title' => $task->getTitle(),
                    'status'     => $task->getStatus()->value,
                ]);
                continue;
            }

            $task->setStatus(TaskStatus::PENDING);
            $uncompletedTaskEntities[] = $task;

            $this->logger->info('Uncomplete multiple tasks - uncompleted task', [
                'task_id'    => $task->getId(),
                'task_title' => $task->getTitle(),
                'search'     => $search,
            ]);
        }

        if (empty($uncompletedTaskEntities)) {
            return CommandResponse::failure(
                'no_tasks_uncompleted',
                'Не найдено завершенных задач для возврата в работу',
                [
                    'searches'            => $searches,
                    'not_found'           => $notFoundSearches,
                    'already_uncompleted' => $alreadyUncompletedTasks,
                ],
            );
        }

        $this->flush();

        // Сериализуем задачи для WebSocket
        $tasks = array_map(
            fn ($task) => $this->responseBuilder->serializeTask($task),
            $uncompletedTaskEntities
        );

        $message = sprintf('Возвращено в работу %d задач', count($tasks));
        $additionalInfo = [];

        if (!empty($alreadyUncompletedTasks)) {
            $additionalInfo[] = sprintf('уже в работе: %d', count($alreadyUncompletedTasks));
        }

        if (!empty($notFoundSearches)) {
            $additionalInfo[] = sprintf('не найдено: %d', count($notFoundSearches));
        }

        if (!empty($additionalInfo)) {
            $message .= ' (' . implode(', ', $additionalInfo) . ')';
        }

        return CommandResponse::success(
            'multiple_tasks_uncompleted',
            $message,
            [
                'uncompleted_count'         => count($tasks),
                'tasks'                     => $tasks,
                'already_uncompleted_count' => count($alreadyUncompletedTasks),
                'already_uncompleted_tasks' => $alreadyUncompletedTasks,
                'not_found_count'           => count($notFoundSearches),
                'not_found_searches'        => $notFoundSearches,
            ],
        );
    }
}
