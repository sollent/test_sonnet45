<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Task;

use App\Entity\User;
use App\Service\AI\Command\Base\AbstractVoiceCommand;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Response\CommandResponse;
use App\Service\AI\Response\ResponseBuilder;
use App\Service\AI\Service\TaskFinder;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Команда удаления задачи
 *
 * Обрабатывает действие delete_task из ParsedCommand.
 * Следует принципу Single Responsibility.
 */
class DeleteTaskCommand extends AbstractVoiceCommand
{
    private TaskFinder $taskFinder;

    private ResponseBuilder $responseBuilder;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        TaskFinder $taskFinder,
        ResponseBuilder $responseBuilder,
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger);
        $this->taskFinder = $taskFinder;
        $this->responseBuilder = $responseBuilder;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_DELETE_TASK;
    }

    protected function validateParameters(array $parameters): void
    {
        $search = $this->taskFinder->extractSearch($parameters);

        if (empty($search)) {
            throw new RuntimeException('Search query is required for task deletion');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $search = $this->taskFinder->extractSearch($parameters);

        // Поиск задачи
        $task = $this->taskFinder->find($search, $user);

        if (!$task) {
            return $this->responseBuilder->taskNotFound($search);
        }

        $taskTitle = $task->getTitle();
        $taskId = $task->getId();

        // Удаление задачи
        $this->entityManager->remove($task);
        $this->flush();

        return $this->responseBuilder->taskDeleted($taskId, $taskTitle);
    }
}
