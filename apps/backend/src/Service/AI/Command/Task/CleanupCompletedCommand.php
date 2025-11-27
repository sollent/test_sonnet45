<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Task;

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
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Команда очистки завершённых задач за определённый период
 *
 * Обрабатывает действие cleanup_completed из ParsedCommand.
 * ВАЖНО: period обязателен для безопасности!
 * Следует принципу Single Responsibility.
 */
class CleanupCompletedCommand extends AbstractVoiceCommand
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
        return ParsedCommand::ACTION_CLEANUP_COMPLETED;
    }

    protected function validateParameters(array $parameters): void
    {
        if (empty($parameters['period'])) {
            throw new RuntimeException(
                'Для очистки завершённых задач необходимо указать период ' .
                '(yesterday, last_week, last_month, before_date)',
            );
        }

        if ($parameters['period'] === 'before_date' && empty($parameters['before_date'])) {
            throw new RuntimeException('Для period=before_date необходимо указать before_date');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $period = $parameters['period'];
        $beforeDate = $parameters['before_date'] ?? null;

        try {
            // Определяем диапазон дат для периода
            $dateRange = $this->dateTimeResolver->resolvePeriod($period, $beforeDate);
        } catch (InvalidArgumentException $e) {
            return CommandResponse::failure(
                'invalid_period',
                $e->getMessage(),
            );
        }

        // Фильтруем завершённые задачи
        $tasks = $this->taskFinder->filter(['status' => 'completed'], $user);

        // Фильтруем по периоду
        $tasksToDelete = [];

        foreach ($tasks as $task) {
            $taskDate = $task->getDueDate() ?? $task->getCreatedAt();

            if (!$taskDate) {
                continue;
            }

            // Проверяем попадание в период
            if ($dateRange['start'] !== null && $taskDate < $dateRange['start']) {
                continue;
            }

            if ($taskDate > $dateRange['end']) {
                continue;
            }

            $tasksToDelete[] = $task;
        }

        if (empty($tasksToDelete)) {
            return CommandResponse::failure(
                'no_tasks_to_cleanup',
                sprintf('Не найдено завершённых задач за период: %s', $period),
                ['period' => $period],
            );
        }

        // Удаляем задачи
        $deletedTitles = [];

        foreach ($tasksToDelete as $task) {
            $deletedTitles[] = $task->getTitle();
            $this->entityManager->remove($task);
        }

        $this->flush();

        return CommandResponse::success(
            'cleanup_completed',
            sprintf('Очищено завершённых задач: %d за период %s', count($deletedTitles), $period),
            [
                'deleted_count'  => count($deletedTitles),
                'period'         => $period,
                'deleted_titles' => $deletedTitles,
            ],
        );
    }
}
