<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Task;

use App\Dto\Request\Task\CreateTaskDto;
use App\Entity\User;
use App\Enum\TaskStatus;
use App\Repository\Database\TagRepository;
use App\Service\AI\Command\Base\AbstractVoiceCommand;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Response\CommandResponse;
use App\Service\AI\Service\DateTimeResolver;
use App\Service\AI\Service\PriorityMapper;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Команда создания нескольких задач одновременно
 *
 * Обрабатывает действие create_multiple_tasks из ParsedCommand.
 * Создает несколько независимых задач с общими параметрами.
 * Поддерживает два формата параметров:
 * - 'titles': массив строк ['Задача 1', 'Задача 2']
 * - 'tasks': массив объектов [['title' => '...', 'description' => '...'], ...]
 */
class CreateMultipleTasksCommand extends AbstractVoiceCommand
{
    private DateTimeResolver $dateTimeResolver;

    private PriorityMapper $priorityMapper;

    private TagRepository $tagRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        DateTimeResolver $dateTimeResolver,
        PriorityMapper $priorityMapper,
        TagRepository $tagRepository,
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger);
        $this->dateTimeResolver = $dateTimeResolver;
        $this->priorityMapper = $priorityMapper;
        $this->tagRepository = $tagRepository;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_CREATE_MULTIPLE_TASKS;
    }

    protected function validateParameters(array $parameters): void
    {
        // Поддержка двух форматов: 'titles' (массив строк) и 'tasks' (массив объектов)
        $hasTitles = !empty($parameters['titles']) && is_array($parameters['titles']);
        $hasTasks = !empty($parameters['tasks']) && is_array($parameters['tasks']);

        if (!$hasTitles && !$hasTasks) {
            throw new RuntimeException('Array of task titles is required for multiple task creation');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        // Извлечение задач из разных форматов параметров
        $taskItems = $this->extractTaskItems($parameters);

        // Подготовка общих параметров
        $priority = isset($parameters['priority'])
            ? $this->priorityMapper->map($parameters['priority'])
            : null;

        $dateRange = null;

        if (isset($parameters['due_date'])) {
            $dateRange = $this->dateTimeResolver->resolveDateRange($parameters);
        }

        $tags = [];

        if (!empty($parameters['tags'])) {
            $tagNames = is_array($parameters['tags']) ? $parameters['tags'] : [$parameters['tags']];
            $tags = $this->tagRepository->findOrCreateByNames($tagNames, $user);
        }

        $createdTasks = [];

        // Создание задач
        foreach ($taskItems as $taskItem) {
            $dto = new CreateTaskDto();
            $dto->title = $taskItem['title'];
            // Используем описание задачи или общее описание
            $dto->description = $taskItem['description'] ?? $parameters['description'] ?? null;
            $dto->status = TaskStatus::PENDING;

            if ($priority !== null) {
                $dto->priority = $priority;
            }

            // Проверяем due_date для конкретной задачи или общий, по умолчанию - сегодня
            $taskDueDate = $taskItem['due_date'] ?? $parameters['due_date'] ?? 'today';
            $taskDateRange = $this->dateTimeResolver->resolveDateRange(['due_date' => $taskDueDate]);

            if ($taskDateRange) {
                $dto->startDate = $taskDateRange['start']?->format('Y-m-d H:i:s');
                $dto->dueDate = $taskDateRange['due']?->format('Y-m-d H:i:s');
            }

            // Создаем задачу
            $task = $this->taskService->createTask($dto, $user);

            // Добавляем теги
            foreach ($tags as $tag) {
                $task->addTag($tag);
            }

            $createdTasks[] = [
                'id'        => $task->getId(),
                'title'     => $task->getTitle(),
                'status'    => $task->getStatus()->value,
                'priority'  => $task->getPriority()->value,
                'startDate' => $task->getStartDate()?->format('c'),
                'dueDate'   => $task->getDueDate()?->format('c'),
                'tags'      => array_map(fn ($tag) => $tag->getName(), $task->getTags()->toArray()),
            ];

            $this->logger->info('Create multiple tasks - created task', [
                'task_id'    => $task->getId(),
                'task_title' => $task->getTitle(),
            ]);
        }

        $this->flush();

        return CommandResponse::success(
            'multiple_tasks_created',
            sprintf('Создано %d задач', count($createdTasks)),
            [
                'created_count' => count($createdTasks),
                'tasks'         => $createdTasks,
            ],
        );
    }

    /**
     * Извлекает элементы задач из параметров
     *
     * Поддерживает два формата:
     * - 'titles': ['Задача 1', 'Задача 2'] -> [['title' => 'Задача 1'], ...]
     * - 'tasks': [['title' => '...', 'description' => '...', 'due_date' => '...'], ...]
     *
     * @param array<string, mixed> $parameters
     *
     * @return array<int, array{title: string, description?: string, due_date?: string}>
     */
    private function extractTaskItems(array $parameters): array
    {
        // Формат 'tasks' (массив объектов с title, description, due_date)
        if (!empty($parameters['tasks']) && is_array($parameters['tasks'])) {
            $items = [];

            foreach ($parameters['tasks'] as $task) {
                if (is_array($task) && isset($task['title'])) {
                    $items[] = [
                        'title'       => $task['title'],
                        'description' => $task['description'] ?? null,
                        'due_date'    => $task['due_date'] ?? null,
                    ];
                } elseif (is_string($task)) {
                    // Если элемент - строка, используем как title
                    $items[] = ['title' => $task];
                }
            }

            return $items;
        }

        // Формат 'titles' (массив строк)
        if (!empty($parameters['titles']) && is_array($parameters['titles'])) {
            return array_map(
                fn ($title) => ['title' => $title],
                $parameters['titles'],
            );
        }

        return [];
    }
}
