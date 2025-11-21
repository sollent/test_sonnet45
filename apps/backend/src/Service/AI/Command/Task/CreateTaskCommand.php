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
use App\Service\AI\Response\ResponseBuilder;
use App\Service\AI\Service\DateTimeResolver;
use App\Service\AI\Service\PriorityMapper;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Команда создания задачи
 *
 * Обрабатывает действие create_task из ParsedCommand.
 * Следует принципу Single Responsibility.
 */
class CreateTaskCommand extends AbstractVoiceCommand
{
    private TagRepository $tagRepository;
    private PriorityMapper $priorityMapper;
    private DateTimeResolver $dateTimeResolver;
    private ResponseBuilder $responseBuilder;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        TagRepository $tagRepository,
        PriorityMapper $priorityMapper,
        DateTimeResolver $dateTimeResolver,
        ResponseBuilder $responseBuilder
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger);
        $this->tagRepository = $tagRepository;
        $this->priorityMapper = $priorityMapper;
        $this->dateTimeResolver = $dateTimeResolver;
        $this->responseBuilder = $responseBuilder;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_CREATE_TASK;
    }

    protected function validateParameters(array $parameters): void
    {
        if (empty($parameters['title'])) {
            throw new RuntimeException('Title is required for task creation');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $title = $parameters['title'];

        // Создание DTO для задачи
        $dto = new CreateTaskDto();
        $dto->title = $title;
        $dto->description = $parameters['description'] ?? '';
        $dto->status = TaskStatus::PENDING;
        $dto->priority = $this->priorityMapper->map($parameters['priority'] ?? null);

        // Обработка даты и времени (по умолчанию - сегодня)
        $dateParams = $parameters;
        if (!isset($dateParams['due_date'])) {
            $dateParams['due_date'] = 'today';
        }
        $dateRange = $this->dateTimeResolver->resolveDateRange($dateParams);
        $dto->startDate = $dateRange['start']?->format('Y-m-d H:i:s');
        $dto->dueDate = $dateRange['due']?->format('Y-m-d H:i:s');

        // Создание задачи
        $task = $this->taskService->createTask($dto, $user);

        // Добавление тегов
        if (!empty($parameters['tags'])) {
            $this->addTagsToTask($task, $parameters['tags'], $user);
        }

        // Создание подзадач
        $createdSubtasks = [];
        if (!empty($parameters['subtasks']) && is_array($parameters['subtasks'])) {
            $createdSubtasks = $this->createSubtasks($parameters['subtasks'], $task, $user);
        }

        // Формирование ответа
        $additionalData = [];
        if (!empty($createdSubtasks)) {
            $additionalData['subtasks'] = $createdSubtasks;
        }

        $response = $this->responseBuilder->taskCreated($task, $additionalData);

        // Кастомизация сообщения при наличии подзадач
        if (count($createdSubtasks) > 0) {
            return CommandResponse::success(
                $response->getType(),
                sprintf('Задача "%s" создана с %d подзадачами', $title, count($createdSubtasks)),
                $response->getData()
            );
        }

        return $response;
    }

    /**
     * Добавить теги к задаче
     */
    private function addTagsToTask($task, $tags, User $user): void
    {
        $tagNames = is_array($tags) ? $tags : [$tags];
        $tagEntities = $this->tagRepository->findOrCreateByNames($tagNames, $user);

        foreach ($tagEntities as $tag) {
            $task->addTag($tag);
        }

        $this->flush();
    }

    /**
     * Создать подзадачи
     */
    private function createSubtasks(array $subtasksData, $parentTask, User $user): array
    {
        $createdSubtasks = [];

        foreach ($subtasksData as $subtaskData) {
            // Поддержка как простых строк, так и объектов
            $subtaskTitle = is_array($subtaskData)
                ? ($subtaskData['title'] ?? null)
                : $subtaskData;

            if (!empty($subtaskTitle)) {
                $subtaskDto = new CreateTaskDto();
                $subtaskDto->title = $subtaskTitle;
                $subtaskDto->status = TaskStatus::PENDING;
                $subtaskDto->priority = is_array($subtaskData) && isset($subtaskData['priority'])
                    ? $this->priorityMapper->map($subtaskData['priority'])
                    : $parentTask->getPriority();
                $subtaskDto->parentTaskId = $parentTask->getId();

                $subtask = $this->taskService->createTask($subtaskDto, $user);
                $createdSubtasks[] = [
                    'id' => $subtask->getId(),
                    'title' => $subtask->getTitle(),
                ];
            }
        }

        return $createdSubtasks;
    }
}