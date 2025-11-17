<?php

declare(strict_types=1);

namespace App\Service\AI;

use App\Entity\User;
use App\Entity\Task;
use App\Repository\Database\TagRepository;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use DateTimeImmutable;
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
    private LoggerInterface $logger;

    public function __construct(
        TaskService $taskService,
        TagRepository $tagRepository,
        SmartSearchService $searchService,
        LoggerInterface $logger
    ) {
        $this->taskService = $taskService;
        $this->tagRepository = $tagRepository;
        $this->searchService = $searchService;
        $this->logger = $logger;
    }

    /**
     * Выполнение распарсенной команды
     *
     * @param ParsedCommand $command Распарсенная команда от LLM
     * @param User $user Пользователь
     * @return array Результат выполнения
     * @throws RuntimeException При ошибке выполнения
     */
    public function execute(ParsedCommand $command, User $user): array
    {
        $this->logger->info('Executing voice command', [
            'action' => $command->action,
            'parameters' => $command->parameters,
            'user_id' => $user->getId()
        ]);

        try {
            $result = match ($command->action) {
                ParsedCommand::ACTION_CREATE_TASK => $this->executeCreateTask($command->parameters, $user),
                ParsedCommand::ACTION_COMPLETE_TASK => $this->executeCompleteTask($command->parameters, $user),
                ParsedCommand::ACTION_FILTER_TASKS => $this->executeFilterTasks($command->parameters, $user),
                ParsedCommand::ACTION_CREATE_SUBTASK => $this->executeCreateSubtask($command->parameters, $user),
                ParsedCommand::ACTION_BULK_COMPLETE => $this->executeBulkComplete($command->parameters, $user),
                ParsedCommand::ACTION_CLARIFICATION_NEEDED => $this->executeClarificationNeeded($command->parameters),
                ParsedCommand::ACTION_UNKNOWN => $this->executeUnknown($command->parameters),
                default => throw new RuntimeException('Unsupported action: ' . $command->action)
            };

            $this->logger->info('Voice command executed successfully', [
                'action' => $command->action,
                'result_type' => $result['type'] ?? 'unknown'
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Failed to execute voice command', [
                'action' => $command->action,
                'error' => $e->getMessage()
            ]);

            return [
                'type' => 'error',
                'success' => false,
                'message' => 'Произошла ошибка при выполнении команды: ' . $e->getMessage(),
                'error' => $e->getMessage()
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

        // Подготовка данных для создания задачи
        $taskData = [
            'title' => $title,
            'description' => $parameters['description'] ?? '',
            'status' => 'new',
            'priority' => $this->parsePriority($parameters['priority'] ?? null),
        ];

        // Обработка даты
        if (isset($parameters['due_date'])) {
            $taskData['dueDate'] = $this->parseDueDate($parameters['due_date']);
        }

        // Создание задачи
        $task = $this->taskService->createTask($taskData, $user);

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

        return [
            'type' => 'task_created',
            'success' => true,
            'message' => sprintf('Задача "%s" успешно создана', $title),
            'task' => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'status' => $task->getStatus(),
                'priority' => $task->getPriority(),
                'dueDate' => $task->getDueDate()?->format('c')
            ]
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
                'type' => 'task_not_found',
                'success' => false,
                'message' => sprintf('Задача "%s" не найдена', $search),
                'search' => $search
            ];
        }

        // Отметка как выполненной
        $task->setStatus('done');
        $this->taskService->saveTask($task);

        return [
            'type' => 'task_completed',
            'success' => true,
            'message' => sprintf('Задача "%s" отмечена как выполненная', $task->getTitle()),
            'task' => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'status' => $task->getStatus()
            ]
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
        $taskList = array_map(function(Task $task) {
            return [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'status' => $task->getStatus(),
                'priority' => $task->getPriority(),
                'dueDate' => $task->getDueDate()?->format('c'),
                'tags' => array_map(fn($tag) => $tag->getName(), $task->getTags()->toArray())
            ];
        }, $tasks);

        $count = count($tasks);

        return [
            'type' => 'tasks_filtered',
            'success' => true,
            'message' => $count > 0
                ? sprintf('Найдено задач: %d', $count)
                : 'Задачи не найдены',
            'count' => $count,
            'tasks' => $taskList
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
                'type' => 'parent_not_found',
                'success' => false,
                'message' => sprintf('Родительская задача "%s" не найдена', $parentSearch),
                'search' => $parentSearch
            ];
        }

        // Создание подзадачи
        $subtaskData = [
            'title' => $title,
            'description' => $parameters['description'] ?? '',
            'status' => 'new',
            'priority' => $parentTask->getPriority(), // Наследуем приоритет
            'parentTaskId' => $parentTask->getId()
        ];

        $subtask = $this->taskService->createTask($subtaskData, $user);

        return [
            'type' => 'subtask_created',
            'success' => true,
            'message' => sprintf('Подзадача "%s" создана для "%s"', $title, $parentTask->getTitle()),
            'subtask' => [
                'id' => $subtask->getId(),
                'title' => $subtask->getTitle(),
                'parent_id' => $parentTask->getId(),
                'parent_title' => $parentTask->getTitle()
            ]
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
                'type' => 'no_tasks_to_complete',
                'success' => false,
                'message' => 'Не найдено задач для завершения',
                'filters' => $filters
            ];
        }

        // Завершение всех найденных задач
        $completedCount = 0;
        $completedTitles = [];

        foreach ($tasks as $task) {
            if ($task->getStatus() !== 'done') {
                $task->setStatus('done');
                $this->taskService->saveTask($task);
                $completedCount++;
                $completedTitles[] = $task->getTitle();
            }
        }

        return [
            'type' => 'bulk_completed',
            'success' => true,
            'message' => sprintf('Завершено задач: %d из %d', $completedCount, count($tasks)),
            'completed_count' => $completedCount,
            'total_count' => count($tasks),
            'completed_titles' => $completedTitles
        ];
    }

    /**
     * Обработка команды, требующей уточнения
     */
    private function executeClarificationNeeded(array $parameters): array
    {
        return [
            'type' => 'clarification_needed',
            'success' => false,
            'message' => $parameters['question'] ?? 'Не удалось понять команду. Можете уточнить?',
            'original_text' => $parameters['original_text'] ?? null,
            'suggestions' => [
                'Создай задачу купить молоко',
                'Отметь задачу отчет как выполненную',
                'Покажи все задачи на завтра'
            ]
        ];
    }

    /**
     * Обработка неизвестной команды
     */
    private function executeUnknown(array $parameters): array
    {
        return [
            'type' => 'unknown_command',
            'success' => false,
            'message' => 'Команда не распознана. Попробуйте переформулировать.',
            'original_text' => $parameters['original_text'] ?? null,
            'help' => [
                'Доступные команды:',
                '• Создание задачи: "Создай задачу [название]"',
                '• Завершение задачи: "Отметь [название] как выполненную"',
                '• Фильтрация: "Покажи задачи на [дату]"',
                '• Создание подзадачи: "Добавь подзадачу [название] к [родительская задача]"',
                '• Массовое завершение: "Заверши все задачи на сегодня"'
            ]
        ];
    }

    /**
     * Парсинг приоритета из параметров
     */
    private function parsePriority(?string $priority): string
    {
        if (empty($priority)) {
            return 'medium';
        }

        $priority = mb_strtolower(trim($priority));

        // Карта соответствий русских названий
        $priorityMap = [
            'низкий' => 'low',
            'низкая' => 'low',
            'средний' => 'medium',
            'средняя' => 'medium',
            'обычный' => 'medium',
            'обычная' => 'medium',
            'высокий' => 'high',
            'высокая' => 'high',
            'важный' => 'high',
            'важная' => 'high',
            'срочный' => 'high',
            'срочная' => 'high',
        ];

        if (isset($priorityMap[$priority])) {
            return $priorityMap[$priority];
        }

        // Проверка английских вариантов
        if (in_array($priority, ['low', 'medium', 'high'], true)) {
            return $priority;
        }

        return 'medium';
    }

    /**
     * Парсинг даты из параметров
     */
    private function parseDueDate(string $dateExpression): ?DateTimeImmutable
    {
        $dateExpression = mb_strtolower(trim($dateExpression));

        try {
            return match($dateExpression) {
                'сегодня', 'today' => (new DateTimeImmutable())->setTime(23, 59, 59),
                'завтра', 'tomorrow' => (new DateTimeImmutable('+1 day'))->setTime(23, 59, 59),
                'послезавтра', 'day after tomorrow' => (new DateTimeImmutable('+2 days'))->setTime(23, 59, 59),
                'через неделю', 'next week' => (new DateTimeImmutable('+1 week'))->setTime(23, 59, 59),
                'через месяц', 'next month' => (new DateTimeImmutable('+1 month'))->setTime(23, 59, 59),
                default => new DateTimeImmutable($dateExpression)
            };
        } catch (\Exception $e) {
            $this->logger->warning('Failed to parse due date', [
                'expression' => $dateExpression,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}