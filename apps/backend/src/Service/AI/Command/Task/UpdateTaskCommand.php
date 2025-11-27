<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Task;

use App\Entity\User;
use App\Repository\Database\TagRepository;
use App\Service\AI\Command\Base\AbstractVoiceCommand;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Response\CommandResponse;
use App\Service\AI\Response\ResponseBuilder;
use App\Service\AI\Service\DateTimeResolver;
use App\Service\AI\Service\PriorityMapper;
use App\Service\AI\Service\StatusMapper;
use App\Service\AI\Service\TaskFinder;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Команда обновления задачи
 *
 * Обрабатывает действие update_task из ParsedCommand.
 * Поддерживает обновление приоритета, статуса, даты и времени.
 * Следует принципу Single Responsibility.
 */
class UpdateTaskCommand extends AbstractVoiceCommand
{
    private TaskFinder $taskFinder;

    private PriorityMapper $priorityMapper;

    private StatusMapper $statusMapper;

    private DateTimeResolver $dateTimeResolver;

    private ResponseBuilder $responseBuilder;

    private TagRepository $tagRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        TaskFinder $taskFinder,
        PriorityMapper $priorityMapper,
        StatusMapper $statusMapper,
        DateTimeResolver $dateTimeResolver,
        ResponseBuilder $responseBuilder,
        TagRepository $tagRepository,
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger);
        $this->taskFinder = $taskFinder;
        $this->priorityMapper = $priorityMapper;
        $this->statusMapper = $statusMapper;
        $this->dateTimeResolver = $dateTimeResolver;
        $this->responseBuilder = $responseBuilder;
        $this->tagRepository = $tagRepository;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_UPDATE_TASK;
    }

    protected function validateParameters(array $parameters): void
    {
        $search = $this->taskFinder->extractSearch($parameters);

        if (empty($search)) {
            throw new RuntimeException('Search query is required for task update');
        }

        if (empty($parameters['updates'])) {
            throw new RuntimeException('Updates are required for task update');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $search = $this->taskFinder->extractSearch($parameters);
        $updates = $parameters['updates'];

        // Поиск задачи
        $task = $this->taskFinder->find($search, $user);

        if (!$task) {
            return $this->responseBuilder->taskNotFound($search);
        }

        $updatedFields = [];

        // Обновление приоритета
        if (isset($updates['priority'])) {
            $newPriority = $this->priorityMapper->map($updates['priority']);
            $task->setPriority($newPriority);
            $updatedFields[] = 'приоритет';
        }

        // Обновление статуса
        if (isset($updates['status'])) {
            $newStatus = $this->statusMapper->map($updates['status']);

            if ($newStatus !== null) {
                $task->setStatus($newStatus);
                $updatedFields[] = 'статус';
            }
        }

        // Обновление даты и времени
        if (isset($updates['due_date'])) {
            $this->updateTaskDates($task, $updates);
            $updatedFields[] = isset($updates['start_time']) || isset($updates['end_time'])
                ? 'дата и время'
                : 'дата';
        } elseif (isset($updates['start_time']) || isset($updates['end_time'])) {
            $this->updateTaskTime($task, $updates);
            $updatedFields[] = 'время';
        }

        // Обновление названия
        if (isset($updates['title'])) {
            $task->setTitle($updates['title']);
            $updatedFields[] = 'название';
        }

        // Обновление описания
        if (isset($updates['description'])) {
            $task->setDescription($updates['description']);
            $updatedFields[] = 'описание';
        }

        // Обновление тегов
        if (isset($updates['tags']) && is_array($updates['tags'])) {
            $tagNames = $updates['tags'];
            $tags = $this->tagRepository->findOrCreateByNames($tagNames, $user);

            // Добавляем новые теги (не заменяем существующие)
            foreach ($tags as $tag) {
                if (!$task->getTags()->contains($tag)) {
                    $task->addTag($tag);
                }
            }

            $updatedFields[] = 'теги';
        }

        // Сохранение изменений
        $this->flush();

        return $this->responseBuilder->taskUpdated($task, $updatedFields);
    }

    /**
     * Обновить даты задачи
     *
     * @param mixed $task
     */
    private function updateTaskDates($task, array $updates): void
    {
        $dateRange = $this->dateTimeResolver->resolveDateRange($updates);

        if ($dateRange['start'] !== null) {
            $task->setStartDate($dateRange['start']);
        }

        if ($dateRange['due'] !== null) {
            $task->setDueDate($dateRange['due']);
        }
    }

    /**
     * Обновить только время задачи без изменения даты
     *
     * @param mixed $task
     */
    private function updateTaskTime($task, array $updates): void
    {
        $currentDate = $task->getDueDate() ?? new DateTimeImmutable();
        $dateStr = $currentDate->format('Y-m-d');

        if (isset($updates['start_time'])) {
            $startDate = $this->dateTimeResolver->resolveDateWithTime($dateStr, $updates['start_time']);
            $task->setStartDate($startDate);
        }

        if (isset($updates['end_time'])) {
            $endDate = $this->dateTimeResolver->resolveDateWithTime($dateStr, $updates['end_time']);
            $task->setDueDate($endDate);
        }
    }
}
