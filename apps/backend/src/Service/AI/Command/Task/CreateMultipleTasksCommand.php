<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Task;

use App\Dto\Request\Task\CreateTaskDto;
use App\Entity\User;
use App\Enum\TaskStatus;
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
 * Следует принципу Single Responsibility.
 */
class CreateMultipleTasksCommand extends AbstractVoiceCommand
{
    private DateTimeResolver $dateTimeResolver;
    private PriorityMapper $priorityMapper;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        DateTimeResolver $dateTimeResolver,
        PriorityMapper $priorityMapper
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger);
        $this->dateTimeResolver = $dateTimeResolver;
        $this->priorityMapper = $priorityMapper;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_CREATE_MULTIPLE_TASKS;
    }

    protected function validateParameters(array $parameters): void
    {
        if (empty($parameters['titles']) || !is_array($parameters['titles'])) {
            throw new RuntimeException('Array of task titles is required for multiple task creation');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $titles = $parameters['titles'];

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
            foreach ($tagNames as $tagName) {
                $tag = $this->searchService->findOrCreateTag($tagName, $user);
                if ($tag) {
                    $tags[] = $tag;
                }
            }
        }

        $createdTasks = [];

        // Создание задач
        foreach ($titles as $title) {
            $dto = new CreateTaskDto();
            $dto->title = $title;
            $dto->description = $parameters['description'] ?? null;
            $dto->status = TaskStatus::PENDING;

            if ($priority !== null) {
                $dto->priority = $priority;
            }

            if ($dateRange) {
                $dto->startDate = $dateRange['start']?->format('Y-m-d H:i:s');
                $dto->dueDate = $dateRange['due']?->format('Y-m-d H:i:s');
            }

            // Создаем задачу
            $task = $this->taskService->createTask($dto, $user);

            // Добавляем теги
            foreach ($tags as $tag) {
                $task->addTag($tag);
            }

            $createdTasks[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'status' => $task->getStatus()->value,
                'priority' => $task->getPriority()->value,
                'startDate' => $task->getStartDate()?->format('c'),
                'dueDate' => $task->getDueDate()?->format('c'),
                'tags' => array_map(fn($tag) => $tag->getName(), $task->getTags()->toArray()),
            ];

            $this->logger->info('Create multiple tasks - created task', [
                'task_id' => $task->getId(),
                'task_title' => $task->getTitle(),
            ]);
        }

        $this->flush();

        return CommandResponse::success(
            'multiple_tasks_created',
            sprintf('Создано %d задач', count($createdTasks)),
            [
                'created_count' => count($createdTasks),
                'tasks' => $createdTasks,
            ]
        );
    }
}