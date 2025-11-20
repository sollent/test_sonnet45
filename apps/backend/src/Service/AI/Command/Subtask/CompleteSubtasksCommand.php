<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Subtask;

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
 * Команда завершения всех подзадач родительской задачи
 *
 * Обрабатывает действие complete_subtasks из ParsedCommand.
 * Завершает все подзадачи указанной родительской задачи.
 * Следует принципу Single Responsibility.
 */
class CompleteSubtasksCommand extends AbstractVoiceCommand
{
    private TaskFinder $taskFinder;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        TaskFinder $taskFinder
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger);
        $this->taskFinder = $taskFinder;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_COMPLETE_SUBTASKS;
    }

    protected function validateParameters(array $parameters): void
    {
        $parentSearch = $this->taskFinder->extractParentSearch($parameters);
        if (empty($parentSearch)) {
            throw new RuntimeException('Parent task search query is required for completing subtasks');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $parentSearch = $this->taskFinder->extractParentSearch($parameters);

        // Поиск родительской задачи
        $parentTask = $this->taskFinder->find($parentSearch, $user);

        if (!$parentTask) {
            return CommandResponse::failure(
                'parent_task_not_found',
                sprintf('Родительская задача "%s" не найдена', $parentSearch),
                ['search' => $parentSearch]
            );
        }

        // Получаем подзадачи
        $subtasks = $parentTask->getSubtasks();

        if ($subtasks->isEmpty()) {
            return CommandResponse::failure(
                'no_subtasks',
                sprintf('У задачи "%s" нет подзадач', $parentTask->getTitle()),
                [
                    'parent_task' => [
                        'id' => $parentTask->getId(),
                        'title' => $parentTask->getTitle(),
                    ],
                ]
            );
        }

        $completedSubtasks = [];
        $alreadyCompletedSubtasks = [];

        foreach ($subtasks as $subtask) {
            if ($subtask->getStatus() === TaskStatus::COMPLETED) {
                $alreadyCompletedSubtasks[] = [
                    'id' => $subtask->getId(),
                    'title' => $subtask->getTitle(),
                ];
                continue;
            }

            $subtask->setStatus(TaskStatus::COMPLETED);
            $completedSubtasks[] = [
                'id' => $subtask->getId(),
                'title' => $subtask->getTitle(),
            ];

            $this->logger->info('Complete subtasks - completed subtask', [
                'subtask_id' => $subtask->getId(),
                'subtask_title' => $subtask->getTitle(),
                'parent_id' => $parentTask->getId(),
            ]);
        }

        if (empty($completedSubtasks)) {
            return CommandResponse::failure(
                'all_subtasks_already_completed',
                sprintf('Все подзадачи "%s" уже завершены', $parentTask->getTitle()),
                [
                    'parent_task' => [
                        'id' => $parentTask->getId(),
                        'title' => $parentTask->getTitle(),
                    ],
                    'already_completed_count' => count($alreadyCompletedSubtasks),
                    'already_completed_subtasks' => $alreadyCompletedSubtasks,
                ]
            );
        }

        $this->flush();

        $message = sprintf(
            'Завершено %d подзадач для "%s"',
            count($completedSubtasks),
            $parentTask->getTitle()
        );

        if (!empty($alreadyCompletedSubtasks)) {
            $message .= sprintf(' (уже завершено: %d)', count($alreadyCompletedSubtasks));
        }

        return CommandResponse::success(
            'subtasks_completed',
            $message,
            [
                'parent_task' => [
                    'id' => $parentTask->getId(),
                    'title' => $parentTask->getTitle(),
                ],
                'completed_count' => count($completedSubtasks),
                'completed_subtasks' => $completedSubtasks,
                'already_completed_count' => count($alreadyCompletedSubtasks),
                'already_completed_subtasks' => $alreadyCompletedSubtasks,
            ]
        );
    }
}