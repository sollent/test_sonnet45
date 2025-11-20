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
use App\Service\AI\Service\TaskFinder;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Команда дублирования/копирования задачи
 *
 * Обрабатывает действие duplicate_task из ParsedCommand.
 * Создает копию задачи со всеми тегами.
 * Следует принципу Single Responsibility.
 */
class DuplicateTaskCommand extends AbstractVoiceCommand
{
    private TaskFinder $taskFinder;
    private DateTimeResolver $dateTimeResolver;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        TaskFinder $taskFinder,
        DateTimeResolver $dateTimeResolver
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger);
        $this->taskFinder = $taskFinder;
        $this->dateTimeResolver = $dateTimeResolver;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_DUPLICATE_TASK;
    }

    protected function validateParameters(array $parameters): void
    {
        $search = $this->taskFinder->extractSearch($parameters);
        if (empty($search)) {
            throw new RuntimeException('Search query is required for task duplication');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $search = $this->taskFinder->extractSearch($parameters);

        // Поиск задачи для копирования
        $originalTask = $this->taskFinder->find($search, $user);

        if (!$originalTask) {
            return CommandResponse::failure(
                'task_not_found',
                sprintf('Задача "%s" не найдена', $search),
                ['search' => $search]
            );
        }

        // Создание копии
        $dto = new CreateTaskDto();
        $dto->title = $originalTask->getTitle();
        $dto->description = $originalTask->getDescription();
        $dto->status = TaskStatus::PENDING; // Новая задача всегда pending
        $dto->priority = $originalTask->getPriority();

        // Обработка даты
        if (isset($parameters['new_date'])) {
            $dateRange = $this->dateTimeResolver->resolveDateRange([
                'due_date' => $parameters['new_date']
            ]);
            $dto->startDate = $dateRange['start']?->format('Y-m-d H:i:s');
            $dto->dueDate = $dateRange['due']?->format('Y-m-d H:i:s');
        } else {
            // Копируем оригинальные даты
            $dto->startDate = $originalTask->getStartDate()?->format('Y-m-d H:i:s');
            $dto->dueDate = $originalTask->getDueDate()?->format('Y-m-d H:i:s');
        }

        // Создание новой задачи
        $newTask = $this->taskService->createTask($dto, $user);

        // Копирование тегов
        foreach ($originalTask->getTags() as $tag) {
            $newTask->addTag($tag);
        }
        $this->flush();

        return CommandResponse::success(
            'task_duplicated',
            sprintf('Задача "%s" скопирована', $originalTask->getTitle()),
            [
                'original_task' => [
                    'id' => $originalTask->getId(),
                    'title' => $originalTask->getTitle(),
                ],
                'new_task' => [
                    'id' => $newTask->getId(),
                    'title' => $newTask->getTitle(),
                    'status' => $newTask->getStatus()->value,
                    'priority' => $newTask->getPriority()->value,
                    'startDate' => $newTask->getStartDate()?->format('c'),
                    'dueDate' => $newTask->getDueDate()?->format('c'),
                ],
            ]
        );
    }
}