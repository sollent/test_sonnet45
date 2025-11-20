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
        PriorityMapper $priorityMapper
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

        if (empty($parameters['title'])) {
            throw new RuntimeException('Subtask title is required');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $parentSearch = $this->taskFinder->extractParentSearch($parameters);
        $subtaskTitle = $parameters['title'];

        // Поиск родительской задачи
        $parentTask = $this->taskFinder->find($parentSearch, $user);

        if (!$parentTask) {
            return CommandResponse::failure(
                'parent_task_not_found',
                sprintf('Родительская задача "%s" не найдена', $parentSearch),
                ['search' => $parentSearch]
            );
        }

        // Создание подзадачи
        $dto = new CreateTaskDto();
        $dto->title = $subtaskTitle;
        $dto->description = $parameters['description'] ?? null;
        $dto->status = TaskStatus::PENDING;

        // Приоритет
        if (isset($parameters['priority'])) {
            $dto->priority = $this->priorityMapper->map($parameters['priority']);
        } else {
            // Наследуем приоритет родителя
            $dto->priority = $parentTask->getPriority();
        }

        // Даты
        if (isset($parameters['due_date'])) {
            $dateRange = $this->dateTimeResolver->resolveDateRange($parameters);
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
        $subtask->setParent($parentTask);

        // Наследуем теги родителя если не указаны свои
        if (empty($parameters['tags'])) {
            foreach ($parentTask->getTags() as $tag) {
                $subtask->addTag($tag);
            }
        }

        $this->flush();

        return CommandResponse::success(
            'subtask_created',
            sprintf('Подзадача "%s" создана для "%s"', $subtask->getTitle(), $parentTask->getTitle()),
            [
                'parent_task' => [
                    'id' => $parentTask->getId(),
                    'title' => $parentTask->getTitle(),
                ],
                'subtask' => [
                    'id' => $subtask->getId(),
                    'title' => $subtask->getTitle(),
                    'status' => $subtask->getStatus()->value,
                    'priority' => $subtask->getPriority()->value,
                    'startDate' => $subtask->getStartDate()?->format('c'),
                    'dueDate' => $subtask->getDueDate()?->format('c'),
                ],
            ]
        );
    }
}