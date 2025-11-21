<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Task;

use App\Entity\User;
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
 * Команда перемещения задачи к другому родителю или в корень
 *
 * Обрабатывает действие move_task из ParsedCommand.
 * Позволяет изменить родителя задачи или сделать её корневой.
 * Следует принципу Single Responsibility.
 */
class MoveTaskCommand extends AbstractVoiceCommand
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
        return ParsedCommand::ACTION_MOVE_TASK;
    }

    protected function validateParameters(array $parameters): void
    {
        $search = $this->taskFinder->extractSearch($parameters);
        if (empty($search)) {
            throw new RuntimeException('Task search query is required for moving');
        }

        // new_parent_search может быть null (перемещение в корень)
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $taskSearch = $this->taskFinder->extractSearch($parameters);
        $newParentSearch = $parameters['new_parent_search'] ?? null;

        // Поиск задачи для перемещения
        $task = $this->taskFinder->find($taskSearch, $user);

        if (!$task) {
            return CommandResponse::failure(
                'task_not_found',
                sprintf('Задача "%s" не найдена', $taskSearch),
                ['search' => $taskSearch]
            );
        }

        $oldParentTitle = $task->getParent()?->getTitle() ?? '(корень)';

        // Если указан новый родитель
        if ($newParentSearch) {
            $newParent = $this->taskFinder->find($newParentSearch, $user);

            if (!$newParent) {
                return CommandResponse::failure(
                    'new_parent_not_found',
                    sprintf('Новая родительская задача "%s" не найдена', $newParentSearch),
                    ['search' => $newParentSearch]
                );
            }

            // Проверка на циклическую зависимость
            if ($this->wouldCreateCycle($task, $newParent)) {
                return CommandResponse::failure(
                    'circular_dependency',
                    'Невозможно переместить задачу: создастся циклическая зависимость',
                    [
                        'task' => $task->getTitle(),
                        'new_parent' => $newParent->getTitle(),
                    ]
                );
            }

            $task->setParentTask($newParent);
            $newParentTitle = $newParent->getTitle();
        } else {
            // Перемещаем в корень
            $task->setParentTask(null);
            $newParentTitle = '(корень)';
        }

        $this->flush();

        return CommandResponse::success(
            'task_moved',
            sprintf(
                'Задача "%s" перемещена из "%s" в "%s"',
                $task->getTitle(),
                $oldParentTitle,
                $newParentTitle
            ),
            [
                'task' => [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                ],
                'old_parent' => $oldParentTitle,
                'new_parent' => $newParentTitle,
            ]
        );
    }

    /**
     * Проверяет, создастся ли циклическая зависимость при перемещении
     */
    private function wouldCreateCycle($task, $potentialParent): bool
    {
        // Проверяем, не является ли потенциальный родитель потомком задачи
        $current = $potentialParent;
        while ($current !== null) {
            if ($current->getId() === $task->getId()) {
                return true; // Нашли цикл
            }
            $current = $current->getParent();
        }
        return false;
    }
}