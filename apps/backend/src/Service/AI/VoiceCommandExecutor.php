<?php

declare(strict_types=1);

namespace App\Service\AI;

use App\Dto\Request\Task\CreateTaskDto;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Repository\Database\TagRepository;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * ОПТИМИЗИРОВАННЫЙ Исполнитель голосовых команд
 *
 * Версия 2.0 - Полная поддержка всех CRUD операций
 * Выполняет действия на основе распарсенных команд от LLM.
 * Использует паттерн Command для инкапсуляции действий
 */
class VoiceCommandExecutor
{
    private TaskService $taskService;

    private TagRepository $tagRepository;

    private SmartSearchService $searchService;

    private DateTimeParser $dateTimeParser;

    private LoggerInterface $logger;

    private EntityManagerInterface $entityManager;

    public function __construct(
        TaskService $taskService,
        TagRepository $tagRepository,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
    ) {
        $this->taskService = $taskService;
        $this->tagRepository = $tagRepository;
        $this->searchService = $searchService;
        $this->dateTimeParser = $dateTimeParser;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * Выполнение распарсенной команды
     *
     * @param ParsedCommand $command Распарсенная команда от LLM
     * @param User          $user    Пользователь
     *
     * @throws RuntimeException При ошибке выполнения
     *
     * @return array Результат выполнения
     */
    public function execute(ParsedCommand $command, User $user): array
    {
        $this->logger->info('Executing voice command', [
            'action'     => $command->action,
            'parameters' => $command->parameters,
            'user_id'    => $user->getId(),
        ]);

        try {
            $result = match ($command->action) {
                ParsedCommand::ACTION_CREATE_TASK           => $this->executeCreateTask($command->parameters, $user),
                ParsedCommand::ACTION_CREATE_MULTIPLE_TASKS => $this->executeCreateMultipleTasks($command->parameters, $user),
                ParsedCommand::ACTION_COMPLETE_TASK         => $this->executeCompleteTask($command->parameters, $user),
                ParsedCommand::ACTION_UNCOMPLETE_TASK       => $this->executeUncompleteTask($command->parameters, $user), // 🆕 Добавлено!
                ParsedCommand::ACTION_FILTER_TASKS          => $this->executeFilterTasks($command->parameters, $user),
                ParsedCommand::ACTION_CREATE_SUBTASK        => $this->executeCreateSubtask($command->parameters, $user),
                ParsedCommand::ACTION_CREATE_MULTIPLE_SUBTASKS => $this->executeCreateMultipleSubtasks($command->parameters, $user),
                ParsedCommand::ACTION_UPDATE_TASK           => $this->executeUpdateTask($command->parameters, $user),
                ParsedCommand::ACTION_MOVE_TASK             => $this->executeMoveTask($command->parameters, $user),
                ParsedCommand::ACTION_BULK_COMPLETE         => $this->executeBulkComplete($command->parameters, $user),
                ParsedCommand::ACTION_COMPLETE_MULTIPLE_TASKS => $this->executeCompleteMultipleTasks($command->parameters, $user),
                ParsedCommand::ACTION_COMPLETE_SUBTASKS     => $this->executeCompleteSubtasks($command->parameters, $user),
                ParsedCommand::ACTION_DELETE_TASK           => $this->executeDeleteTask($command->parameters, $user),
                ParsedCommand::ACTION_DELETE_MULTIPLE_TASKS => $this->executeDeleteMultipleTasks($command->parameters, $user),
                ParsedCommand::ACTION_BULK_DELETE           => $this->executeBulkDelete($command->parameters, $user),
                ParsedCommand::ACTION_CLARIFICATION_NEEDED  => $this->executeClarificationNeeded($command->parameters),
                ParsedCommand::ACTION_UNKNOWN               => $this->executeUnknown($command->parameters),
                default                                     => throw new RuntimeException('Unsupported action: ' . $command->action)
            };

            $this->logger->info('Voice command executed successfully', [
                'action'      => $command->action,
                'result_type' => $result['type'] ?? 'unknown',
            ]);

            return $result;

        } catch (Exception $e) {
            $this->logger->error('Failed to execute voice command', [
                'action' => $command->action,
                'error'  => $e->getMessage(),
            ]);

            return [
                'type'    => 'error',
                'success' => false,
                'message' => 'Произошла ошибка при выполнении команды: ' . $e->getMessage(),
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Создание новой задачи (CRUD: Create)
     * Оптимизировано для стандартизированных форматов дат
     */
    private function executeCreateTask(array $parameters, User $user): array
    {
        $title = $parameters['title'] ?? null;

        if (empty($title)) {
            throw new RuntimeException('Title is required for task creation');
        }

        // Создание DTO для задачи
        $dto = new CreateTaskDto();
        $dto->title = $title;
        $dto->description = $parameters['description'] ?? '';
        $dto->status = TaskStatus::PENDING;
        $dto->priority = $this->parsePriority($parameters['priority'] ?? null);

        // ОПТИМИЗИРОВАННАЯ обработка даты и времени
        if (isset($parameters['due_date'])) {
            // Проверяем наличие временного диапазона
            if (isset($parameters['start_time']) && isset($parameters['end_time'])) {
                // Задача с временным диапазоном (например: "с 14:00 до 15:00")
                $startDate = $this->dateTimeParser->parseDateWithTime(
                    $parameters['due_date'],
                    $parameters['start_time']
                );
                $endDate = $this->dateTimeParser->parseDateWithTime(
                    $parameters['due_date'],
                    $parameters['end_time']
                );

                $dto->startDate = $startDate?->format('Y-m-d H:i:s');
                $dto->dueDate = $endDate?->format('Y-m-d H:i:s');

            } elseif (isset($parameters['start_time'])) {
                // Задача с начальным временем
                $startDate = $this->dateTimeParser->parseDateWithTime(
                    $parameters['due_date'],
                    $parameters['start_time']
                );
                // Конец через час после начала
                $endDate = $startDate?->modify('+1 hour');

                $dto->startDate = $startDate?->format('Y-m-d H:i:s');
                $dto->dueDate = $endDate?->format('Y-m-d H:i:s');

            } else {
                // Обычная задача на весь день
                $startDate = $this->dateTimeParser->parseStartDate($parameters['due_date']);
                $dueDate = $this->dateTimeParser->parseDueDate($parameters['due_date']);

                $dto->startDate = $startDate?->format('Y-m-d H:i:s');
                $dto->dueDate = $dueDate?->format('Y-m-d H:i:s');
            }
        }

        // Создание задачи
        $task = $this->taskService->createTask($dto, $user);

        // Добавление тегов, если указаны
        if (!empty($parameters['tags'])) {
            $tagNames = is_array($parameters['tags'])
                ? $parameters['tags']
                : [$parameters['tags']];

            $tags = $this->tagRepository->findOrCreateByNames($tagNames, $user);

            foreach ($tags as $tag) {
                $task->addTag($tag);
            }

            $this->entityManager->flush();
        }

        // Создание подзадач, если указаны
        $createdSubtasks = [];
        if (!empty($parameters['subtasks']) && is_array($parameters['subtasks'])) {
            foreach ($parameters['subtasks'] as $subtaskData) {
                // Поддержка как простых строк, так и объектов
                $subtaskTitle = is_array($subtaskData) ? ($subtaskData['title'] ?? null) : $subtaskData;

                if (!empty($subtaskTitle)) {
                    $subtaskDto = new CreateTaskDto();
                    $subtaskDto->title = $subtaskTitle;
                    $subtaskDto->status = TaskStatus::PENDING;
                    $subtaskDto->priority = is_array($subtaskData) && isset($subtaskData['priority'])
                        ? $this->parsePriority($subtaskData['priority'])
                        : $task->getPriority();
                    $subtaskDto->parentTaskId = $task->getId();

                    $subtask = $this->taskService->createTask($subtaskDto, $user);
                    $createdSubtasks[] = [
                        'id'    => $subtask->getId(),
                        'title' => $subtask->getTitle(),
                    ];
                }
            }
        }

        return [
            'type'     => 'task_created',
            'success'  => true,
            'message'  => count($createdSubtasks) > 0
                ? sprintf('Задача "%s" создана с %d подзадачами', $title, count($createdSubtasks))
                : sprintf('Задача "%s" успешно создана', $title),
            'task'     => [
                'id'        => $task->getId(),
                'title'     => $task->getTitle(),
                'status'    => $task->getStatus()->value,
                'priority'  => $task->getPriority()->value,
                'startDate' => $task->getStartDate()?->format('c'),
                'dueDate'   => $task->getDueDate()?->format('c'),
                'subtasks'  => $createdSubtasks,
            ],
        ];
    }

    /**
     * Отметка задачи как выполненной (CRUD: Update)
     */
    private function executeCompleteTask(array $parameters, User $user): array
    {
        $search = $parameters['search'] ?? $parameters['title'] ?? null;

        if (empty($search)) {
            throw new RuntimeException('Search query is required for task completion');
        }

        // Поиск задачи
        $task = $this->searchService->findBestMatch($search, $user);

        if (!$task) {
            return [
                'type'    => 'task_not_found',
                'success' => false,
                'message' => sprintf('Задача "%s" не найдена', $search),
                'search'  => $search,
            ];
        }

        // Отметка как выполненной
        $task = $this->taskService->completeTask($task, $user);

        return [
            'type'    => 'task_completed',
            'success' => true,
            'message' => sprintf('Задача "%s" отмечена как выполненная', $task->getTitle()),
            'task'    => [
                'id'     => $task->getId(),
                'title'  => $task->getTitle(),
                'status' => $task->getStatus()->value,
            ],
        ];
    }

    /**
     * 🆕 НОВЫЙ МЕТОД: Отмена завершения задачи (CRUD: Update)
     * Возвращает задачу в статус "в ожидании"
     */
    private function executeUncompleteTask(array $parameters, User $user): array
    {
        $search = $parameters['search'] ?? $parameters['title'] ?? null;

        if (empty($search)) {
            throw new RuntimeException('Search query is required for task uncomplete');
        }

        // Поиск задачи
        $task = $this->searchService->findBestMatch($search, $user);

        if (!$task) {
            return [
                'type'    => 'task_not_found',
                'success' => false,
                'message' => sprintf('Задача "%s" не найдена', $search),
                'search'  => $search,
            ];
        }

        // Проверка что задача действительно завершена
        if ($task->getStatus() !== TaskStatus::COMPLETED) {
            return [
                'type'    => 'task_already_uncompleted',
                'success' => false,
                'message' => sprintf('Задача "%s" уже не завершена', $task->getTitle()),
                'task'    => [
                    'id'     => $task->getId(),
                    'title'  => $task->getTitle(),
                    'status' => $task->getStatus()->value,
                ],
            ];
        }

        // Возвращаем в статус "в ожидании"
        $task->setStatus(TaskStatus::PENDING);
        $this->entityManager->flush();

        return [
            'type'    => 'task_uncompleted',
            'success' => true,
            'message' => sprintf('Задача "%s" возвращена в работу', $task->getTitle()),
            'task'    => [
                'id'     => $task->getId(),
                'title'  => $task->getTitle(),
                'status' => $task->getStatus()->value,
            ],
        ];
    }

    /**
     * Фильтрация задач (CRUD: Read)
     */
    private function executeFilterTasks(array $parameters, User $user): array
    {
        $filters = $parameters['filters'] ?? $parameters;

        // Применение фильтров через SmartSearchService
        $tasks = $this->searchService->filterTasks($filters, $user);

        // Форматирование результатов
        $taskList = array_map(function (Task $task) {
            return [
                'id'       => $task->getId(),
                'title'    => $task->getTitle(),
                'status'   => $task->getStatus()->value,
                'priority' => $task->getPriority()->value,
                'dueDate'  => $task->getDueDate()?->format('c'),
                'tags'     => array_map(fn ($tag) => $tag->getName(), $task->getTags()->toArray()),
            ];
        }, $tasks);

        $count = count($tasks);

        return [
            'type'    => 'tasks_filtered',
            'success' => true,
            'message' => $count > 0
                ? sprintf('Найдено задач: %d', $count)
                : 'Задачи не найдены',
            'count' => $count,
            'tasks' => $taskList,
        ];
    }

    /**
     * Создание подзадачи (CRUD: Create)
     */
    private function executeCreateSubtask(array $parameters, User $user): array
    {
        $parentSearch = $parameters['parent_search'] ?? $parameters['parent'] ?? $parameters['parent_task'] ?? null;
        $title = $parameters['title'] ?? null;

        if (empty($parentSearch) || empty($title)) {
            throw new RuntimeException('Parent task and title are required for subtask creation');
        }

        // Поиск родительской задачи
        $parentTask = $this->searchService->findBestMatch($parentSearch, $user);

        if (!$parentTask) {
            return [
                'type'    => 'parent_not_found',
                'success' => false,
                'message' => sprintf('Родительская задача "%s" не найдена', $parentSearch),
                'search'  => $parentSearch,
            ];
        }

        // Создание подзадачи
        $dto = new CreateTaskDto();
        $dto->title = $title;
        $dto->description = $parameters['description'] ?? '';
        $dto->status = TaskStatus::PENDING;
        $dto->priority = $parentTask->getPriority(); // Наследуем приоритет
        $dto->parentTaskId = $parentTask->getId();

        $subtask = $this->taskService->createTask($dto, $user);

        return [
            'type'    => 'subtask_created',
            'success' => true,
            'message' => sprintf('Подзадача "%s" создана для "%s"', $title, $parentTask->getTitle()),
            'subtask' => [
                'id'           => $subtask->getId(),
                'title'        => $subtask->getTitle(),
                'parent_id'    => $parentTask->getId(),
                'parent_title' => $parentTask->getTitle(),
            ],
        ];
    }

    /**
     * Создание нескольких подзадач для существующей задачи (CRUD: Create - batch)
     */
    private function executeCreateMultipleSubtasks(array $parameters, User $user): array
    {
        $parentSearch = $parameters['parent_search'] ?? $parameters['parent'] ?? $parameters['parent_task'] ?? null;
        $subtasks = $parameters['subtasks'] ?? [];

        if (empty($parentSearch)) {
            throw new RuntimeException('Parent task search is required for multiple subtask creation');
        }

        if (empty($subtasks) || !is_array($subtasks)) {
            throw new RuntimeException('Subtasks array is required for multiple subtask creation');
        }

        // Поиск родительской задачи
        $parentTask = $this->searchService->findBestMatch($parentSearch, $user);

        if (!$parentTask) {
            return [
                'type'    => 'parent_not_found',
                'success' => false,
                'message' => sprintf('Родительская задача "%s" не найдена', $parentSearch),
                'search'  => $parentSearch,
            ];
        }

        $createdSubtasks = [];
        $errors = [];

        foreach ($subtasks as $index => $subtaskData) {
            try {
                // Поддержка как простых строк, так и объектов
                $title = is_array($subtaskData) ? ($subtaskData['title'] ?? null) : $subtaskData;

                if (empty($title)) {
                    $errors[] = sprintf('Подзадача #%d: название не указано', $index + 1);
                    continue;
                }

                // Создание подзадачи
                $dto = new CreateTaskDto();
                $dto->title = $title;
                $dto->description = is_array($subtaskData) ? ($subtaskData['description'] ?? '') : '';
                $dto->status = TaskStatus::PENDING;
                $dto->priority = is_array($subtaskData) && isset($subtaskData['priority'])
                    ? $this->parsePriority($subtaskData['priority'])
                    : $parentTask->getPriority();
                $dto->parentTaskId = $parentTask->getId();

                // Обработка даты если указана
                if (is_array($subtaskData) && isset($subtaskData['due_date'])) {
                    $startDate = $this->dateTimeParser->parseStartDate($subtaskData['due_date']);
                    $dueDate = $this->dateTimeParser->parseDueDate($subtaskData['due_date']);
                    $dto->startDate = $startDate?->format('Y-m-d H:i:s');
                    $dto->dueDate = $dueDate?->format('Y-m-d H:i:s');
                }

                $subtask = $this->taskService->createTask($dto, $user);
                $createdSubtasks[] = [
                    'id'    => $subtask->getId(),
                    'title' => $subtask->getTitle(),
                ];
            } catch (Exception $e) {
                $errors[] = sprintf('Подзадача #%d: %s', $index + 1, $e->getMessage());
            }
        }

        $successCount = count($createdSubtasks);
        $totalCount = count($subtasks);

        if ($successCount === 0) {
            return [
                'type'    => 'no_subtasks_created',
                'success' => false,
                'message' => sprintf('Не удалось создать подзадачи для "%s"', $parentTask->getTitle()),
                'errors'  => $errors,
            ];
        }

        return [
            'type'          => 'multiple_subtasks_created',
            'success'       => true,
            'message'       => sprintf(
                'Создано подзадач: %d из %d для задачи "%s"',
                $successCount,
                $totalCount,
                $parentTask->getTitle()
            ),
            'parent_task'   => [
                'id'    => $parentTask->getId(),
                'title' => $parentTask->getTitle(),
            ],
            'created_count' => $successCount,
            'total_count'   => $totalCount,
            'subtasks'      => $createdSubtasks,
            'errors'        => $errors,
        ];
    }

    /**
     * Массовое завершение задач (CRUD: Update - batch)
     */
    private function executeBulkComplete(array $parameters, User $user): array
    {
        $filters = $parameters['filters'] ?? $parameters;

        // Поиск задач по фильтрам
        $tasks = $this->searchService->filterTasks($filters, $user);

        if (empty($tasks)) {
            return [
                'type'    => 'no_tasks_to_complete',
                'success' => false,
                'message' => 'Не найдено задач для завершения',
                'filters' => $filters,
            ];
        }

        // Завершение всех найденных задач
        $completedCount = 0;
        $completedTitles = [];

        foreach ($tasks as $task) {
            if ($task->getStatus() !== TaskStatus::COMPLETED) {
                $this->taskService->completeTask($task, $user);
                $completedCount++;
                $completedTitles[] = $task->getTitle();
            }
        }

        return [
            'type'             => 'bulk_completed',
            'success'          => true,
            'message'          => sprintf('Завершено задач: %d из %d', $completedCount, count($tasks)),
            'completed_count'  => $completedCount,
            'total_count'      => count($tasks),
            'completed_titles' => $completedTitles,
        ];
    }

    /**
     * Завершение нескольких конкретных задач по названиям
     */
    private function executeCompleteMultipleTasks(array $parameters, User $user): array
    {
        $taskNames = $parameters['tasks'] ?? [];

        if (empty($taskNames) || !is_array($taskNames)) {
            throw new RuntimeException('Tasks array is required for completing multiple tasks');
        }

        $completedTasks = [];
        $notFoundTasks = [];
        $errors = [];

        foreach ($taskNames as $taskName) {
            // Поиск задачи по названию
            $task = $this->searchService->findBestMatch($taskName, $user);

            if (!$task) {
                $notFoundTasks[] = $taskName;
                continue;
            }

            // Проверяем что задача не завершена
            if ($task->getStatus() === TaskStatus::COMPLETED) {
                $errors[] = sprintf('Задача "%s" уже завершена', $task->getTitle());
                continue;
            }

            // Завершаем задачу
            $this->taskService->completeTask($task, $user);
            $completedTasks[] = [
                'id'    => $task->getId(),
                'title' => $task->getTitle(),
            ];
        }

        $successCount = count($completedTasks);
        $totalCount = count($taskNames);

        if ($successCount === 0) {
            return [
                'type'       => 'no_tasks_completed',
                'success'    => false,
                'message'    => 'Не удалось завершить ни одной задачи',
                'not_found'  => $notFoundTasks,
                'errors'     => $errors,
            ];
        }

        return [
            'type'            => 'multiple_tasks_completed',
            'success'         => true,
            'message'         => sprintf('Завершено задач: %d из %d', $successCount, $totalCount),
            'completed_count' => $successCount,
            'total_count'     => $totalCount,
            'tasks'           => $completedTasks,
            'not_found'       => $notFoundTasks,
            'errors'          => $errors,
        ];
    }

    /**
     * Завершение всех подзадач конкретной задачи (CRUD: Update - batch)
     */
    private function executeCompleteSubtasks(array $parameters, User $user): array
    {
        $parentSearch = $parameters['parent_search'] ?? $parameters['parent'] ?? null;

        if (empty($parentSearch)) {
            throw new RuntimeException('Parent task search is required for completing subtasks');
        }

        // Поиск родительской задачи
        $parentTask = $this->searchService->findBestMatch($parentSearch, $user);

        if (!$parentTask) {
            return [
                'type'    => 'parent_not_found',
                'success' => false,
                'message' => sprintf('Родительская задача "%s" не найдена', $parentSearch),
                'search'  => $parentSearch,
            ];
        }

        // Получаем все подзадачи
        $subtasks = $parentTask->getSubtasks();

        if ($subtasks->isEmpty()) {
            return [
                'type'    => 'no_subtasks',
                'success' => false,
                'message' => sprintf('У задачи "%s" нет подзадач', $parentTask->getTitle()),
                'parent'  => [
                    'id'    => $parentTask->getId(),
                    'title' => $parentTask->getTitle(),
                ],
            ];
        }

        // Завершаем все подзадачи
        $completedCount = 0;
        $completedTitles = [];

        foreach ($subtasks as $subtask) {
            if ($subtask->getStatus() !== TaskStatus::COMPLETED) {
                $this->taskService->completeTask($subtask, $user);
                $completedCount++;
                $completedTitles[] = $subtask->getTitle();
            }
        }

        if ($completedCount === 0) {
            return [
                'type'    => 'all_subtasks_already_completed',
                'success' => false,
                'message' => sprintf('Все подзадачи задачи "%s" уже завершены', $parentTask->getTitle()),
                'parent'  => [
                    'id'    => $parentTask->getId(),
                    'title' => $parentTask->getTitle(),
                ],
            ];
        }

        return [
            'type'             => 'subtasks_completed',
            'success'          => true,
            'message'          => sprintf(
                'Завершено подзадач: %d из %d для задачи "%s"',
                $completedCount,
                $subtasks->count(),
                $parentTask->getTitle()
            ),
            'parent'           => [
                'id'    => $parentTask->getId(),
                'title' => $parentTask->getTitle(),
            ],
            'completed_count'  => $completedCount,
            'total_count'      => $subtasks->count(),
            'completed_titles' => $completedTitles,
        ];
    }

    /**
     * Удаление одной задачи (CRUD: Delete)
     */
    private function executeDeleteTask(array $parameters, User $user): array
    {
        $search = $parameters['search'] ?? $parameters['title'] ?? null;

        if (empty($search)) {
            throw new RuntimeException('Search query is required for task deletion');
        }

        // Поиск задачи
        $task = $this->searchService->findBestMatch($search, $user);

        if (!$task) {
            return [
                'type'    => 'task_not_found',
                'success' => false,
                'message' => sprintf('Задача "%s" не найдена', $search),
                'search'  => $search,
            ];
        }

        $taskTitle = $task->getTitle();
        $taskId = $task->getId();

        // Удаление задачи
        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return [
            'type'    => 'task_deleted',
            'success' => true,
            'message' => sprintf('Задача "%s" удалена', $taskTitle),
            'task'    => [
                'id'    => $taskId,
                'title' => $taskTitle,
            ],
        ];
    }

    /**
     * Удаление нескольких конкретных задач по названиям (CRUD: Delete - batch)
     */
    private function executeDeleteMultipleTasks(array $parameters, User $user): array
    {
        $taskNames = $parameters['tasks'] ?? [];

        if (empty($taskNames) || !is_array($taskNames)) {
            throw new RuntimeException('Tasks array is required for deleting multiple tasks');
        }

        $deletedTasks = [];
        $notFoundTasks = [];
        $errors = [];

        foreach ($taskNames as $taskName) {
            // Поиск задачи по названию
            $task = $this->searchService->findBestMatch($taskName, $user);

            if (!$task) {
                $notFoundTasks[] = $taskName;
                continue;
            }

            try {
                $taskTitle = $task->getTitle();
                $taskId = $task->getId();

                // Удаляем задачу
                $this->entityManager->remove($task);
                $deletedTasks[] = [
                    'id'    => $taskId,
                    'title' => $taskTitle,
                ];
            } catch (Exception $e) {
                $errors[] = sprintf('Задача "%s": %s', $task->getTitle(), $e->getMessage());
            }
        }

        // Сохраняем все удаления
        $this->entityManager->flush();

        $successCount = count($deletedTasks);
        $totalCount = count($taskNames);

        if ($successCount === 0) {
            return [
                'type'       => 'no_tasks_deleted',
                'success'    => false,
                'message'    => 'Не удалось удалить ни одной задачи',
                'not_found'  => $notFoundTasks,
                'errors'     => $errors,
            ];
        }

        return [
            'type'          => 'multiple_tasks_deleted',
            'success'       => true,
            'message'       => sprintf('Удалено задач: %d из %d', $successCount, $totalCount),
            'deleted_count' => $successCount,
            'total_count'   => $totalCount,
            'tasks'         => $deletedTasks,
            'not_found'     => $notFoundTasks,
            'errors'        => $errors,
        ];
    }

    /**
     * Массовое удаление задач по фильтрам (CRUD: Delete - batch)
     */
    private function executeBulkDelete(array $parameters, User $user): array
    {
        $filters = $parameters['filters'] ?? $parameters;

        // Поиск задач по фильтрам
        $tasks = $this->searchService->filterTasks($filters, $user);

        if (empty($tasks)) {
            return [
                'type'    => 'no_tasks_to_delete',
                'success' => false,
                'message' => 'Не найдено задач для удаления',
                'filters' => $filters,
            ];
        }

        // Удаление всех найденных задач
        $deletedCount = 0;
        $deletedTitles = [];

        foreach ($tasks as $task) {
            $deletedTitles[] = $task->getTitle();
            $this->entityManager->remove($task);
            $deletedCount++;
        }

        $this->entityManager->flush();

        return [
            'type'           => 'bulk_deleted',
            'success'        => true,
            'message'        => sprintf('Удалено задач: %d', $deletedCount),
            'deleted_count'  => $deletedCount,
            'deleted_titles' => $deletedTitles,
        ];
    }

    /**
     * Обработка команды, требующей уточнения
     */
    private function executeClarificationNeeded(array $parameters): array
    {
        return [
            'type'          => 'clarification_needed',
            'success'       => false,
            'message'       => $parameters['question'] ?? 'Не удалось понять команду. Можете уточнить?',
            'original_text' => $parameters['original_text'] ?? null,
            'suggestions'   => [
                'Создай задачу купить молоко',
                'Отметь задачу отчет как выполненную',
                'Покажи все задачи на завтра',
                'Переведи задачу в статус в работе',
                'Верни задачу в работу',
            ],
        ];
    }

    /**
     * Обработка неизвестной команды
     */
    private function executeUnknown(array $parameters): array
    {
        return [
            'type'          => 'unknown_command',
            'success'       => false,
            'message'       => 'Команда не распознана. Попробуйте переформулировать.',
            'original_text' => $parameters['original_text'] ?? null,
            'help'          => [
                'Доступные команды:',
                '• Создание задачи: "Создай задачу [название]"',
                '• Завершение задачи: "Отметь [название] как выполненную"',
                '• Отмена завершения: "Верни [название] в работу"',
                '• Изменение статуса: "Переведи [название] в статус в работе"',
                '• Фильтрация: "Покажи задачи на [дату]"',
                '• Создание подзадачи: "Добавь подзадачу [название] к [родительская задача]"',
                '• Массовое завершение: "Заверши все задачи на сегодня"',
            ],
        ];
    }

    /**
     * Создание нескольких задач одновременно (CRUD: Create - batch)
     */
    private function executeCreateMultipleTasks(array $parameters, User $user): array
    {
        $tasks = $parameters['tasks'] ?? [];

        if (empty($tasks) || !is_array($tasks)) {
            throw new RuntimeException('Tasks array is required for multiple task creation');
        }

        $createdTasks = [];
        $errors = [];

        foreach ($tasks as $index => $taskData) {
            try {
                // Используем существующий метод executeCreateTask для каждой задачи
                $result = $this->executeCreateTask($taskData, $user);

                if ($result['success']) {
                    $createdTasks[] = $result['task'];
                } else {
                    $errors[] = sprintf('Задача #%d: %s', $index + 1, $result['message']);
                }
            } catch (Exception $e) {
                $errors[] = sprintf('Задача #%d: %s', $index + 1, $e->getMessage());
            }
        }

        $successCount = count($createdTasks);
        $totalCount = count($tasks);

        return [
            'type'          => 'multiple_tasks_created',
            'success'       => $successCount > 0,
            'message'       => sprintf('Создано задач: %d из %d', $successCount, $totalCount),
            'created_count' => $successCount,
            'total_count'   => $totalCount,
            'tasks'         => $createdTasks,
            'errors'        => $errors,
        ];
    }

    /**
     * ОПТИМИЗИРОВАННОЕ Обновление существующей задачи (CRUD: Update)
     * Поддерживает все типы обновлений: приоритет, статус, дата, время
     */
    private function executeUpdateTask(array $parameters, User $user): array
    {
        $search = $parameters['search'] ?? null;
        $updates = $parameters['updates'] ?? [];

        if (empty($search)) {
            throw new RuntimeException('Search query is required for task update');
        }

        if (empty($updates)) {
            throw new RuntimeException('Updates are required for task update');
        }

        // Поиск задачи
        $task = $this->searchService->findBestMatch($search, $user);

        if (!$task) {
            return [
                'type'    => 'task_not_found',
                'success' => false,
                'message' => sprintf('Задача "%s" не найдена', $search),
                'search'  => $search,
            ];
        }

        $updatedFields = [];

        // Обновление приоритета
        if (isset($updates['priority'])) {
            $newPriority = $this->parsePriority($updates['priority']);
            $task->setPriority($newPriority);
            $updatedFields[] = 'приоритет';
        }

        // ОПТИМИЗИРОВАННОЕ обновление статуса
        if (isset($updates['status'])) {
            $statusMap = [
                // Английские варианты (из LLM)
                'pending'     => TaskStatus::PENDING,
                'in_progress' => TaskStatus::IN_PROGRESS,
                'completed'   => TaskStatus::COMPLETED,

                // Русские варианты
                'ожидание'    => TaskStatus::PENDING,
                'в ожидании'  => TaskStatus::PENDING,
                'запланировано' => TaskStatus::PENDING,

                'в работе'    => TaskStatus::IN_PROGRESS,
                'выполняется' => TaskStatus::IN_PROGRESS,
                'в процессе'  => TaskStatus::IN_PROGRESS,

                'завершено'   => TaskStatus::COMPLETED,
                'выполнено'   => TaskStatus::COMPLETED,
                'готово'      => TaskStatus::COMPLETED,
            ];

            $statusKey = mb_strtolower(trim($updates['status']));
            if (isset($statusMap[$statusKey])) {
                $task->setStatus($statusMap[$statusKey]);
                $updatedFields[] = 'статус';
            } else {
                $this->logger->warning('Unknown status value', [
                    'status' => $updates['status'],
                    'task_id' => $task->getId(),
                ]);
            }
        }

        // ОПТИМИЗИРОВАННОЕ обновление даты и времени
        if (isset($updates['due_date'])) {
            if (isset($updates['start_time']) && isset($updates['end_time'])) {
                // Временной диапазон
                $startDate = $this->dateTimeParser->parseDateWithTime(
                    $updates['due_date'],
                    $updates['start_time']
                );
                $endDate = $this->dateTimeParser->parseDateWithTime(
                    $updates['due_date'],
                    $updates['end_time']
                );

                $task->setStartDate($startDate);
                $task->setDueDate($endDate);
                $updatedFields[] = 'дата и время';
            } elseif (isset($updates['start_time'])) {
                // Только начальное время
                $startDate = $this->dateTimeParser->parseDateWithTime(
                    $updates['due_date'],
                    $updates['start_time']
                );
                $endDate = $startDate?->modify('+1 hour');

                $task->setStartDate($startDate);
                $task->setDueDate($endDate);
                $updatedFields[] = 'дата и время';
            } else {
                // Только дата
                $startDate = $this->dateTimeParser->parseStartDate($updates['due_date']);
                $dueDate = $this->dateTimeParser->parseDueDate($updates['due_date']);

                $task->setStartDate($startDate);
                $task->setDueDate($dueDate);
                $updatedFields[] = 'дата';
            }
        } elseif (isset($updates['start_time']) || isset($updates['end_time'])) {
            // Обновление только времени без изменения даты
            $currentDate = $task->getDueDate() ?? new DateTimeImmutable();
            $dateStr = $currentDate->format('Y-m-d');

            if (isset($updates['start_time'])) {
                $startDate = $this->dateTimeParser->parseDateWithTime($dateStr, $updates['start_time']);
                $task->setStartDate($startDate);
            }

            if (isset($updates['end_time'])) {
                $endDate = $this->dateTimeParser->parseDateWithTime($dateStr, $updates['end_time']);
                $task->setDueDate($endDate);
            }

            $updatedFields[] = 'время';
        }

        // Обновление названия (если нужно)
        if (isset($updates['title'])) {
            $task->setTitle($updates['title']);
            $updatedFields[] = 'название';
        }

        // Сохранение изменений
        $this->entityManager->flush();

        return [
            'type'    => 'task_updated',
            'success' => true,
            'message' => sprintf(
                'Задача "%s" обновлена: %s',
                $task->getTitle(),
                implode(', ', $updatedFields)
            ),
            'task' => [
                'id'        => $task->getId(),
                'title'     => $task->getTitle(),
                'status'    => $task->getStatus()->value,
                'priority'  => $task->getPriority()->value,
                'startDate' => $task->getStartDate()?->format('c'),
                'dueDate'   => $task->getDueDate()?->format('c'),
            ],
            'updated_fields' => $updatedFields,
        ];
    }

    /**
     * Перемещение задачи на другое время/дату (CRUD: Update)
     */
    private function executeMoveTask(array $parameters, User $user): array
    {
        $search = $parameters['search'] ?? null;
        $newDate = $parameters['new_date'] ?? null;

        if (empty($search) || empty($newDate)) {
            throw new RuntimeException('Search query and new date are required for task move');
        }

        // Поиск задачи
        $task = $this->searchService->findBestMatch($search, $user);

        if (!$task) {
            return [
                'type'    => 'task_not_found',
                'success' => false,
                'message' => sprintf('Задача "%s" не найдена', $search),
                'search'  => $search,
            ];
        }

        // Парсинг новой даты/времени
        if (isset($parameters['start_time']) && isset($parameters['end_time'])) {
            $startDate = $this->dateTimeParser->parseDateWithTime($newDate, $parameters['start_time']);
            $endDate = $this->dateTimeParser->parseDateWithTime($newDate, $parameters['end_time']);

            $task->setStartDate($startDate);
            $task->setDueDate($endDate);
        } elseif (isset($parameters['start_time'])) {
            $startDate = $this->dateTimeParser->parseDateWithTime($newDate, $parameters['start_time']);
            $endDate = $startDate?->modify('+1 hour');

            $task->setStartDate($startDate);
            $task->setDueDate($endDate);
        } else {
            $startDate = $this->dateTimeParser->parseStartDate($newDate);
            $dueDate = $this->dateTimeParser->parseDueDate($newDate);

            $task->setStartDate($startDate);
            $task->setDueDate($dueDate);
        }

        $this->entityManager->flush();

        return [
            'type'    => 'task_moved',
            'success' => true,
            'message' => sprintf('Задача "%s" перемещена на %s', $task->getTitle(), $newDate),
            'task'    => [
                'id'        => $task->getId(),
                'title'     => $task->getTitle(),
                'status'    => $task->getStatus()->value,
                'priority'  => $task->getPriority()->value,
                'startDate' => $task->getStartDate()?->format('c'),
                'dueDate'   => $task->getDueDate()?->format('c'),
            ],
        ];
    }

    /**
     * ОПТИМИЗИРОВАННЫЙ Парсинг приоритета из параметров
     * Поддерживает стандартизированные значения из LLM
     */
    private function parsePriority(?string $priority): TaskPriority
    {
        if (empty($priority)) {
            return TaskPriority::MEDIUM;
        }

        $priority = mb_strtolower(trim($priority));

        // Проверка стандартизированных английских вариантов (из LLM)
        $standardMap = [
            'low'    => TaskPriority::LOW,
            'medium' => TaskPriority::MEDIUM,
            'high'   => TaskPriority::HIGH,
            'urgent' => TaskPriority::URGENT,
        ];

        if (isset($standardMap[$priority])) {
            return $standardMap[$priority];
        }

        // Карта русских вариантов (на случай если LLM вернет русские)
        $russianMap = [
            'низкий'  => TaskPriority::LOW,
            'низкая'  => TaskPriority::LOW,
            'средний' => TaskPriority::MEDIUM,
            'средняя' => TaskPriority::MEDIUM,
            'обычный' => TaskPriority::MEDIUM,
            'обычная' => TaskPriority::MEDIUM,
            'высокий' => TaskPriority::HIGH,
            'высокая' => TaskPriority::HIGH,
            'важный'  => TaskPriority::HIGH,
            'важная'  => TaskPriority::HIGH,
            'срочный' => TaskPriority::URGENT,
            'срочная' => TaskPriority::URGENT,
        ];

        if (isset($russianMap[$priority])) {
            return $russianMap[$priority];
        }

        // По умолчанию средний приоритет
        $this->logger->warning('Unknown priority value, defaulting to MEDIUM', [
            'priority' => $priority,
        ]);

        return TaskPriority::MEDIUM;
    }

}