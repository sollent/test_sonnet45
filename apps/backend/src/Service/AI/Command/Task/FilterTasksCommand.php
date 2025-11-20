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

/**
 * Команда фильтрации задач по различным критериям
 *
 * Обрабатывает действие filter_tasks из ParsedCommand.
 * Поддерживает фильтрацию по статусу, приоритету, тегам, датам.
 * Следует принципу Single Responsibility.
 */
class FilterTasksCommand extends AbstractVoiceCommand
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
        return ParsedCommand::ACTION_FILTER_TASKS;
    }

    protected function validateParameters(array $parameters): void
    {
        // Фильтрация не требует обязательных параметров
        // Можно показать все задачи или применить любые фильтры
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        // Применяем фильтры
        $tasks = $this->taskFinder->filter($parameters, $user);

        if (empty($tasks)) {
            return CommandResponse::failure(
                'no_tasks_found',
                'Не найдено задач по указанным критериям',
                ['filters' => $parameters]
            );
        }

        // Форматируем задачи для ответа
        $formattedTasks = [];
        foreach ($tasks as $task) {
            $formattedTasks[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'status' => $task->getStatus()->value,
                'priority' => $task->getPriority()->value,
                'startDate' => $task->getStartDate()?->format('c'),
                'dueDate' => $task->getDueDate()?->format('c'),
                'tags' => array_map(fn($tag) => $tag->getName(), $task->getTags()->toArray()),
            ];
        }

        // Формируем описание фильтров
        $filterDescription = $this->buildFilterDescription($parameters);

        return CommandResponse::success(
            'tasks_filtered',
            sprintf('Найдено %d задач%s', count($tasks), $filterDescription),
            [
                'count' => count($tasks),
                'filters' => $parameters,
                'tasks' => $formattedTasks,
            ]
        );
    }

    private function buildFilterDescription(array $parameters): string
    {
        $parts = [];

        if (isset($parameters['status'])) {
            $parts[] = sprintf('со статусом "%s"', $parameters['status']);
        }

        if (isset($parameters['priority'])) {
            $parts[] = sprintf('с приоритетом "%s"', $parameters['priority']);
        }

        if (!empty($parameters['tags'])) {
            $tags = is_array($parameters['tags']) ? $parameters['tags'] : [$parameters['tags']];
            $parts[] = sprintf('с тегами "%s"', implode(', ', $tags));
        }

        if (isset($parameters['due_date']) || isset($parameters['period'])) {
            $parts[] = 'за указанный период';
        }

        return empty($parts) ? '' : ' (' . implode(', ', $parts) . ')';
    }
}