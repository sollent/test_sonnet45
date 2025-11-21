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
        PriorityMapper $priorityMapper
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

        if (empty($parameters['titles']) || !is_array($parameters['titles'])) {
            throw new RuntimeException('Array of subtask titles is required');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $parentSearch = $this->taskFinder->extractParentSearch($parameters);
        $subtaskTitles = $parameters['titles'];

        // Поиск родительской задачи
        $parentTask = $this->taskFinder->find($parentSearch, $user);

        if (!$parentTask) {
            return CommandResponse::failure(
                'parent_task_not_found',
                sprintf('Родительская задача "%s" не найдена', $parentSearch),
                ['search' => $parentSearch]
            );
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
                'id' => $subtask->getId(),
                'title' => $subtask->getTitle(),
                'status' => $subtask->getStatus()->value,
                'priority' => $subtask->getPriority()->value,
            ];
        }

        $this->flush();

        return CommandResponse::success(
            'multiple_subtasks_created',
            sprintf(
                'Создано %d подзадач для "%s"',
                count($createdSubtasks),
                $parentTask->getTitle()
            ),
            [
                'parent_task' => [
                    'id' => $parentTask->getId(),
                    'title' => $parentTask->getTitle(),
                ],
                'subtasks_count' => count($createdSubtasks),
                'subtasks' => $createdSubtasks,
            ]
        );
    }
}