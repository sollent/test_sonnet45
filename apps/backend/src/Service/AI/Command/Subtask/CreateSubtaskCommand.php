<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Subtask;

use App\Dto\Request\Task\CreateTaskDto;
use App\Entity\User;
use App\Enum\TaskStatus;
use App\Service\AI\Command\Base\AbstractVoiceCommand;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Response\CommandResponse;
use App\Service\AI\Service\DateTimeResolver;
use App\Service\AI\Service\PriorityMapper;
use App\Service\AI\Service\TaskFinder;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Команда создания подзадачи для существующей задачи
 *
 * Обрабатывает действие create_subtask из ParsedCommand.
 * Создает новую задачу и связывает её как подзадачу.
 * Следует принципу Single Responsibility.
 */
class CreateSubtaskCommand extends AbstractVoiceCommand
{
    private TaskFinder $taskFinder;

    private DateTimeResolver $dateTimeResolver;

    private PriorityMapper $priorityMapper;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        TaskFinder $taskFinder,
        DateTimeResolver $dateTimeResolver,
        PriorityMapper $priorityMapper,
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger);
        $this->taskFinder = $taskFinder;
        $this->dateTimeResolver = $dateTimeResolver;
        $this->priorityMapper = $priorityMapper;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_CREATE_SUBTASK;
    }

    protected function validateParameters(array $parameters): void
    {
        $parentSearch = $this->taskFinder->extractParentSearch($parameters);

        if (empty($parentSearch)) {
            throw new RuntimeException('Parent task search query is required for subtask creation');
        }

        // Поддержка обоих форматов: 'title' (строка) и 'subtasks' (массив)
        $hasTitle = !empty($parameters['title']);
        $hasSubtasks = !empty($parameters['subtasks']) && is_array($parameters['subtasks']);

        if (!$hasTitle && !$hasSubtasks) {
            throw new RuntimeException('Subtask title is required');
        }
    }

    /**
     * Извлечь названия подзадач из параметров
     *
     * @param array<string, mixed> $parameters
     * @return array<string>
     */
    private function extractSubtaskTitles(array $parameters): array
    {
        // Формат 'title' (одна подзадача)
        if (!empty($parameters['title'])) {
            return [$parameters['title']];
        }

        // Формат 'subtasks' (массив объектов с title)
        if (!empty($parameters['subtasks']) && is_array($parameters['subtasks'])) {
            $titles = [];
            foreach ($parameters['subtasks'] as $subtask) {
                if (is_array($subtask) && isset($subtask['title'])) {
                    $titles[] = $subtask['title'];
                } elseif (is_string($subtask)) {
                    $titles[] = $subtask;
                }
            }
            return $titles;
        }

        return [];
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $parentSearch = $this->taskFinder->extractParentSearch($parameters);
        $subtaskTitles = $this->extractSubtaskTitles($parameters);

        // Поиск родительской задачи
        $parentTask = $this->taskFinder->find($parentSearch, $user);
        $parentCreated = false;

        // Если родительская задача не найдена - создаём её автоматически
        if (!$parentTask) {
            $this->logger->info('Parent task not found, creating automatically', [
                'parent_search' => $parentSearch,
            ]);

            $parentDto = new CreateTaskDto();
            $parentDto->title = ucfirst($parentSearch);
            $parentDto->status = TaskStatus::PENDING;

            // Устанавливаем дату на сегодня по умолчанию
            $todayRange = $this->dateTimeResolver->resolveDateRange(['due_date' => 'today']);
            $parentDto->startDate = $todayRange['start']?->format('Y-m-d H:i:s');
            $parentDto->dueDate = $todayRange['due']?->format('Y-m-d H:i:s');

            $parentTask = $this->taskService->createTask($parentDto, $user);
            $parentCreated = true;
        }

        // Подготовка общих параметров
        $priority = isset($parameters['priority'])
            ? $this->priorityMapper->map($parameters['priority'])
            : $parentTask->getPriority();

        $dateRange = null;
        if (isset($parameters['due_date'])) {
            $dateRange = $this->dateTimeResolver->resolveDateRange($parameters);
        }

        $createdSubtasks = [];

        // Создание подзадач
        foreach ($subtaskTitles as $title) {
            $dto = new CreateTaskDto();
            $dto->title = $title;
            $dto->description = $parameters['description'] ?? null;
            $dto->status = TaskStatus::PENDING;
            $dto->priority = $priority;

            // Даты
            if ($dateRange) {
                $dto->startDate = $dateRange['start']?->format('Y-m-d H:i:s');
                $dto->dueDate = $dateRange['due']?->format('Y-m-d H:i:s');
            } else {
                // Наследуем даты родителя
                $dto->startDate = $parentTask->getStartDate()?->format('Y-m-d H:i:s');
                $dto->dueDate = $parentTask->getDueDate()?->format('Y-m-d H:i:s');
            }

            // Создаем подзадачу
            $subtask = $this->taskService->createTask($dto, $user);

            // Связываем с родителем
            $subtask->setParentTask($parentTask);

            // Наследуем теги родителя если не указаны свои
            if (empty($parameters['tags'])) {
                foreach ($parentTask->getTags() as $tag) {
                    $subtask->addTag($tag);
                }
            }

            $createdSubtasks[] = [
                'id'        => $subtask->getId(),
                'title'     => $subtask->getTitle(),
                'status'    => $subtask->getStatus()->value,
                'priority'  => $subtask->getPriority()->value,
                'startDate' => $subtask->getStartDate()?->format('c'),
                'dueDate'   => $subtask->getDueDate()?->format('c'),
            ];
        }

        $this->flush();

        // Формируем сообщение
        $subtasksCount = count($createdSubtasks);
        if ($parentCreated) {
            $message = $subtasksCount === 1
                ? sprintf('Создана задача "%s" с подзадачей "%s"', $parentTask->getTitle(), $createdSubtasks[0]['title'])
                : sprintf('Создана задача "%s" с %d подзадачами', $parentTask->getTitle(), $subtasksCount);
        } else {
            $message = $subtasksCount === 1
                ? sprintf('Подзадача "%s" создана для "%s"', $createdSubtasks[0]['title'], $parentTask->getTitle())
                : sprintf('Создано %d подзадач для "%s"', $subtasksCount, $parentTask->getTitle());
        }

        return CommandResponse::success(
            'subtask_created',
            $message,
            [
                'parent_task' => [
                    'id'      => $parentTask->getId(),
                    'title'   => $parentTask->getTitle(),
                    'created' => $parentCreated,
                ],
                'subtasks' => $createdSubtasks,
            ],
        );
    }
}
