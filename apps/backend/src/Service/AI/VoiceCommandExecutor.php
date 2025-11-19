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
 * Исполнитель голосовых команд
 *
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
                ParsedCommand::ACTION_FILTER_TASKS          => $this->executeFilterTasks($command->parameters, $user),
                ParsedCommand::ACTION_CREATE_SUBTASK        => $this->executeCreateSubtask($command->parameters, $user),
                ParsedCommand::ACTION_UPDATE_TASK           => $this->executeUpdateTask($command->parameters, $user),
                ParsedCommand::ACTION_MOVE_TASK             => $this->executeMoveTask($command->parameters, $user),
                ParsedCommand::ACTION_BULK_COMPLETE         => $this->executeBulkComplete($command->parameters, $user),
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
     * Создание новой задачи
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

        // Обработка даты и времени
        if (isset($parameters['due_date'])) {
            // Проверяем наличие временного диапазона (start_time и end_time)
            if (isset($parameters['start_time']) && isset($parameters['end_time'])) {
                // Задача с временным диапазоном (например: "с 19:30 до 21:00")
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
            } else {
                // Обычная задача без временного диапазона
                // Устанавливаем start_date на начало дня, due_date на конец дня
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

            $this->taskService->saveTask($task);
        }

        // 🆕 Создание подзадач, если указаны
        $createdSubtasks = [];
        if (!empty($parameters['subtasks']) && is_array($parameters['subtasks'])) {
            foreach ($parameters['subtasks'] as $subtaskTitle) {
                if (!empty($subtaskTitle)) {
                    $subtaskDto = new CreateTaskDto();
                    $subtaskDto->title = $subtaskTitle;
                    $subtaskDto->status = TaskStatus::PENDING;
                    $subtaskDto->priority = $task->getPriority(); // Наследуем приоритет
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
                'status'    => $task->getStatus(),
                'priority'  => $task->getPriority(),
                'startDate' => $task->getStartDate()?->format('c'),
                'dueDate'   => $task->getDueDate()?->format('c'),
                'subtasks'  => $createdSubtasks,
            ],
        ];
    }

    /**
     * Отметка задачи как выполненной
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
                'status' => $task->getStatus(),
            ],
        ];
    }

    /**
     * Фильтрация задач
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
                'status'   => $task->getStatus(),
                'priority' => $task->getPriority(),
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
     * Создание подзадачи
     */
    private function executeCreateSubtask(array $parameters, User $user): array
    {
        $parentSearch = $parameters['parent'] ?? $parameters['parent_task'] ?? null;
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
     * Массовое завершение задач
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
                '• Фильтрация: "Покажи задачи на [дату]"',
                '• Создание подзадачи: "Добавь подзадачу [название] к [родительская задача]"',
                '• Массовое завершение: "Заверши все задачи на сегодня"',
            ],
        ];
    }

    /**
     * 🆕 Создание нескольких задач одновременно
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
     * 🆕 Обновление существующей задачи
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

        // Обновление статуса
        if (isset($updates['status'])) {
            $statusMap = [
                'pending'    => TaskStatus::PENDING,
                'ожидание'   => TaskStatus::PENDING,
                'в работе'   => TaskStatus::IN_PROGRESS,
                'in_progress'=> TaskStatus::IN_PROGRESS,
                'completed'  => TaskStatus::COMPLETED,
                'выполнено'  => TaskStatus::COMPLETED,
                'завершено'  => TaskStatus::COMPLETED,
            ];

            $statusKey = mb_strtolower($updates['status']);
            if (isset($statusMap[$statusKey])) {
                $task->setStatus($statusMap[$statusKey]);
                $updatedFields[] = 'статус';
            }
        }

        // Обновление даты и времени
        if (isset($updates['due_date'])) {
            if (isset($updates['start_time']) && isset($updates['end_time'])) {
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
            } else {
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
                'status'    => $task->getStatus(),
                'priority'  => $task->getPriority(),
                'startDate' => $task->getStartDate()?->format('c'),
                'dueDate'   => $task->getDueDate()?->format('c'),
            ],
            'updated_fields' => $updatedFields,
        ];
    }

    /**
     * 🆕 Перемещение задачи на другое время/дату
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
        } else {
            $startDate = $this->dateTimeParser->parseStartDate($newDate);
            $dueDate = $this->dateTimeParser->parseDueDate($newDate);

            $task->setStartDate($startDate);
            $task->setDueDate($dueDate);
        }

        $this->taskService->saveTask($task);

        return [
            'type'    => 'task_moved',
            'success' => true,
            'message' => sprintf('Задача "%s" перемещена на %s', $task->getTitle(), $newDate),
            'task'    => [
                'id'        => $task->getId(),
                'title'     => $task->getTitle(),
                'status'    => $task->getStatus(),
                'priority'  => $task->getPriority(),
                'startDate' => $task->getStartDate()?->format('c'),
                'dueDate'   => $task->getDueDate()?->format('c'),
            ],
        ];
    }

    /**
     * Парсинг приоритета из параметров
     */
    private function parsePriority(?string $priority): TaskPriority
    {
        if (empty($priority)) {
            return TaskPriority::MEDIUM;
        }

        $priority = mb_strtolower(trim($priority));

        // Карта соответствий русских названий
        $priorityMap = [
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

        if (isset($priorityMap[$priority])) {
            return $priorityMap[$priority];
        }

        // Проверка английских вариантов
        return match ($priority) {
            'low' => TaskPriority::LOW,
            'medium' => TaskPriority::MEDIUM,
            'high' => TaskPriority::HIGH,
            'urgent' => TaskPriority::URGENT,
            default => TaskPriority::MEDIUM,
        };
    }

}
