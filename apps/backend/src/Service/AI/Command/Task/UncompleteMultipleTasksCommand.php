<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Task;

use App\Entity\User;
use App\Enum\TaskStatus;
use App\Repository\Database\TaskRepository;
use App\Service\AI\Command\Base\AbstractVoiceCommand;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Response\CommandResponse;
use App\Service\AI\Response\ResponseBuilder;
use App\Service\AI\Service\TaskFinder;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use DateTime;
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

    private TaskRepository $taskRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        TaskFinder $taskFinder,
        ResponseBuilder $responseBuilder,
        TaskRepository $taskRepository,
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger);
        $this->taskFinder = $taskFinder;
        $this->responseBuilder = $responseBuilder;
        $this->taskRepository = $taskRepository;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_UNCOMPLETE_MULTIPLE_TASKS;
    }

    protected function validateParameters(array $parameters): void
    {
        // Поддержка форматов: 'searches' (array), 'tasks' (array или string), 'filters' (object)
        $hasSearches = !empty($parameters['searches']) && is_array($parameters['searches']);
        $hasTasks = !empty($parameters['tasks']);
        $hasFilters = !empty($parameters['filters']);

        if (!$hasSearches && !$hasTasks && !$hasFilters) {
            throw new RuntimeException('Array of task searches or filters is required for multiple uncompletion');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        // Если есть фильтры - работаем в режиме bulk
        if (!empty($parameters['filters'])) {
            return $this->executeBulkByFilters($parameters['filters'], $user);
        }

        // Поддержка обоих форматов параметров
        $tasksParam = $parameters['searches'] ?? $parameters['tasks'] ?? [];

        // Конвертируем строку в массив (LLM иногда возвращает строку вместо массива)
        $searches = is_array($tasksParam) ? $tasksParam : [$tasksParam];
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

    /**
     * Выполнение bulk операции по фильтрам (дата, статус)
     */
    private function executeBulkByFilters(array $filters, User $user): CommandResponse
    {
        // Парсим дату из фильтра
        $date = null;
        if (!empty($filters['date'])) {
            $date = $this->dateTimeParser->parse($filters['date']);
        }

        if (!$date) {
            return CommandResponse::failure(
                'invalid_date',
                'Не удалось распознать дату для фильтрации задач',
                ['filters' => $filters],
            );
        }

        // Получаем все задачи пользователя за указанную дату
        $startOfDay = DateTime::createFromInterface($date)->setTime(0, 0, 0);
        $endOfDay = DateTime::createFromInterface($date)->setTime(23, 59, 59);
        $tasks = $this->taskRepository->findTasksByDateRange($user, $startOfDay, $endOfDay, true);

        // Фильтруем только завершенные
        $completedTasks = array_filter(
            $tasks,
            fn ($task) => $task->getStatus() === TaskStatus::COMPLETED
        );

        if (empty($completedTasks)) {
            return CommandResponse::failure(
                'no_completed_tasks',
                sprintf('Не найдено завершенных задач за %s', $date->format('d.m.Y')),
                ['filters' => $filters, 'date' => $date->format('Y-m-d')],
            );
        }

        // Возвращаем в работу
        $uncompletedTaskEntities = [];
        foreach ($completedTasks as $task) {
            $task->setStatus(TaskStatus::PENDING);
            $uncompletedTaskEntities[] = $task;

            $this->logger->info('Bulk uncomplete - uncompleted task', [
                'task_id'    => $task->getId(),
                'task_title' => $task->getTitle(),
            ]);
        }

        $this->flush();

        // Сериализуем для WebSocket
        $serializedTasks = array_map(
            fn ($task) => $this->responseBuilder->serializeTask($task),
            $uncompletedTaskEntities
        );

        $message = sprintf(
            'Возвращено в работу %d задач за %s',
            count($serializedTasks),
            $date->format('d.m.Y')
        );

        return CommandResponse::success(
            'multiple_tasks_uncompleted',
            $message,
            [
                'uncompleted_count' => count($serializedTasks),
                'tasks'             => $serializedTasks,
                'date'              => $date->format('Y-m-d'),
            ],
        );
    }
}
