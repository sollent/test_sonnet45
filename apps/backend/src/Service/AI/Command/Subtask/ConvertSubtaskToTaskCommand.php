<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Subtask;

use App\Entity\User;
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
 * Команда преобразования подзадачи в самостоятельную задачу
 *
 * Обрабатывает действие convert_subtask_to_task из ParsedCommand.
 * Отсоединяет подзадачу от родителя.
 * Следует принципу Single Responsibility.
 */
class ConvertSubtaskToTaskCommand extends AbstractVoiceCommand
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
        DateTimeResolver $dateTimeResolver,
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger);
        $this->taskFinder = $taskFinder;
        $this->dateTimeResolver = $dateTimeResolver;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_CONVERT_SUBTASK_TO_TASK;
    }

    protected function validateParameters(array $parameters): void
    {
        $search = $this->taskFinder->extractSearch($parameters);

        if (empty($search)) {
            throw new RuntimeException('Search query is required for converting subtask');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $search = $this->taskFinder->extractSearch($parameters);

        // Поиск подзадачи
        $subtask = $this->taskFinder->find($search, $user);

        if (!$subtask) {
            return CommandResponse::failure(
                'task_not_found',
                sprintf('Подзадача "%s" не найдена', $search),
                ['search' => $search],
            );
        }

        // Проверяем что это действительно подзадача
        $parentTask = $subtask->getParentTask();

        if (!$parentTask) {
            return CommandResponse::failure(
                'not_a_subtask',
                sprintf('"%s" не является подзадачей', $subtask->getTitle()),
                [
                    'task' => [
                        'id'    => $subtask->getId(),
                        'title' => $subtask->getTitle(),
                    ],
                ],
            );
        }

        $parentTitle = $parentTask->getTitle();

        // Отсоединяем от родителя
        $subtask->setParentTask(null);

        // Обновляем дату если указана
        if (isset($parameters['new_date'])) {
            $dateRange = $this->dateTimeResolver->resolveDateRange([
                'due_date' => $parameters['new_date'],
            ]);

            if ($dateRange['start'] !== null) {
                $subtask->setStartDate($dateRange['start']);
            }

            if ($dateRange['due'] !== null) {
                $subtask->setDueDate($dateRange['due']);
            }
        }

        $this->flush();

        return CommandResponse::success(
            'subtask_converted',
            sprintf(
                'Подзадача "%s" преобразована в самостоятельную задачу (была у "%s")',
                $subtask->getTitle(),
                $parentTitle,
            ),
            [
                'task' => [
                    'id'        => $subtask->getId(),
                    'title'     => $subtask->getTitle(),
                    'status'    => $subtask->getStatus()->value,
                    'priority'  => $subtask->getPriority()->value,
                    'startDate' => $subtask->getStartDate()?->format('c'),
                    'dueDate'   => $subtask->getDueDate()?->format('c'),
                ],
                'former_parent' => $parentTitle,
            ],
        );
    }
}
