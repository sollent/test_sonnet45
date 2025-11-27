<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Task;

use App\Entity\User;
use App\Enum\TaskStatus;
use App\Service\AI\Command\Base\AbstractVoiceCommand;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Response\CommandResponse;
use App\Service\AI\Service\TaskFinder;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Команда отмены завершения задачи (возврат в работу)
 *
 * Обрабатывает действие uncomplete_task из ParsedCommand.
 * Возвращает задачу в статус PENDING.
 * Следует принципу Single Responsibility.
 */
class UncompleteTaskCommand extends AbstractVoiceCommand
{
    private TaskFinder $taskFinder;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        TaskFinder $taskFinder,
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger);
        $this->taskFinder = $taskFinder;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_UNCOMPLETE_TASK;
    }

    protected function validateParameters(array $parameters): void
    {
        $search = $this->taskFinder->extractSearch($parameters);

        if (empty($search)) {
            throw new RuntimeException('Search query is required for task uncomplete');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $search = $this->taskFinder->extractSearch($parameters);

        // Поиск задачи
        $task = $this->taskFinder->find($search, $user);

        if (!$task) {
            return CommandResponse::failure(
                'task_not_found',
                sprintf('Задача "%s" не найдена', $search),
                ['search' => $search],
            );
        }

        // Проверка что задача действительно завершена
        if ($task->getStatus() !== TaskStatus::COMPLETED) {
            return CommandResponse::failure(
                'task_already_uncompleted',
                sprintf('Задача "%s" уже не завершена', $task->getTitle()),
                [
                    'task' => [
                        'id'     => $task->getId(),
                        'title'  => $task->getTitle(),
                        'status' => $task->getStatus()->value,
                    ],
                ],
            );
        }

        // Возвращаем в статус "в ожидании"
        $task->setStatus(TaskStatus::PENDING);
        $this->flush();

        return CommandResponse::success(
            'task_uncompleted',
            sprintf('Задача "%s" возвращена в работу', $task->getTitle()),
            [
                'task' => [
                    'id'     => $task->getId(),
                    'title'  => $task->getTitle(),
                    'status' => $task->getStatus()->value,
                ],
            ],
        );
    }
}
