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
 * Команда создания множественных подзадач для существующей задачи
 *
 * Обрабатывает действие create_multiple_subtasks из ParsedCommand.
 * Создает несколько подзадач одновременно.
 * Следует принципу Single Responsibility.
 */
class CreateMultipleSubtasksCommand extends AbstractVoiceCommand
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
        return ParsedCommand::ACTION_CREATE_MULTIPLE_SUBTASKS;
    }

    protected function validateParameters(array $parameters): void
    {
        $parentSearch = $this->taskFinder->extractParentSearch($parameters);

        if (empty($parentSearch)) {
            throw new RuntimeException('Parent task search query is required for subtasks creation');
        }

        // Поддержка двух форматов: 'titles' (массив строк) и 'subtasks' (массив объектов)
        $hasTitles = !empty($parameters['titles']) && is_array($parameters['titles']);
        $hasSubtasks = !empty($parameters['subtasks']) && is_array($parameters['subtasks']);

        if (!$hasTitles && !$hasSubtasks) {
            throw new RuntimeException('Array of subtask titles is required');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $parentSearch = $this->taskFinder->extractParentSearch($parameters);
        $subtaskTitles = $this->extractSubtaskTitles($parameters);

        // Поиск родительской задачи
        $parentTask = $this->taskFinder->find($parentSearch, $user);
        $parentWasCreated = false;

        // Если родительская задача не найдена - создаём её
        if (!$parentTask) {
            $parentDto = new CreateTaskDto();
            $parentDto->title = ucfirst($parentSearch);
            $parentDto->status = TaskStatus::PENDING;

            // Устанавливаем даты и приоритет из параметров если указаны
            if (isset($parameters['due_date'])) {
                $dateRange = $this->dateTimeResolver->resolveDateRange($parameters);
                $parentDto->startDate = $dateRange['start']?->format('Y-m-d H:i:s');
                $parentDto->dueDate = $dateRange['due']?->format('Y-m-d H:i:s');
            }

            if (isset($parameters['priority'])) {
                $parentDto->priority = $this->priorityMapper->map($parameters['priority']);
            }

            $parentTask = $this->taskService->createTask($parentDto, $user);
            $parentWasCreated = true;
        }

        // Подготовка общих параметров для всех подзадач
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
                'id'       => $subtask->getId(),
                'title'    => $subtask->getTitle(),
                'status'   => $subtask->getStatus()->value,
                'priority' => $subtask->getPriority()->value,
            ];
        }

        $this->flush();

        $message = $parentWasCreated
            ? sprintf(
                'Создана задача "%s" с %d подзадачами',
                $parentTask->getTitle(),
                count($createdSubtasks),
            )
            : sprintf(
                'Создано %d подзадач для "%s"',
                count($createdSubtasks),
                $parentTask->getTitle(),
            );

        return CommandResponse::success(
            'multiple_subtasks_created',
            $message,
            [
                'parent_task' => [
                    'id'           => $parentTask->getId(),
                    'title'        => $parentTask->getTitle(),
                    'was_created'  => $parentWasCreated,
                ],
                'subtasks_count' => count($createdSubtasks),
                'subtasks'       => $createdSubtasks,
            ],
        );
    }

    /**
     * Извлечь названия подзадач из параметров
     *
     * @param array<string, mixed> $parameters
     * @return array<string>
     */
    private function extractSubtaskTitles(array $parameters): array
    {
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

        // Формат 'titles' (массив строк)
        if (!empty($parameters['titles']) && is_array($parameters['titles'])) {
            return $parameters['titles'];
        }

        return [];
    }
}
