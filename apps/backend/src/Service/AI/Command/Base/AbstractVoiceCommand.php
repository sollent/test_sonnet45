<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Base;

use App\Entity\Task;
use App\Entity\User;
use App\Service\AI\Command\Contract\VoiceCommandInterface;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Response\CommandResponse;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Базовый класс для всех голосовых команд
 *
 * Следует паттерну Template Method, предоставляя общую логику для:
 * - Поиска задач
 * - Парсинга дат
 * - Логирования
 * - Обработки ошибок
 */
abstract class AbstractVoiceCommand implements VoiceCommandInterface
{
    protected EntityManagerInterface $entityManager;

    protected TaskService $taskService;

    protected SmartSearchService $searchService;

    protected DateTimeParser $dateTimeParser;

    protected LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
    ) {
        $this->entityManager = $entityManager;
        $this->taskService = $taskService;
        $this->searchService = $searchService;
        $this->dateTimeParser = $dateTimeParser;
        $this->logger = $logger;
    }

    /**
     * Template Method: выполнение команды с логированием и обработкой ошибок
     */
    public function execute(array $parameters, User $user): CommandResponse
    {
        $this->logger->info('Executing voice command', [
            'action'     => $this->getAction(),
            'parameters' => $parameters,
            'user_id'    => $user->getId(),
        ]);

        try {
            $this->validateParameters($parameters);
            $response = $this->doExecute($parameters, $user);

            $this->logger->info('Voice command executed successfully', [
                'action'        => $this->getAction(),
                'response_type' => $response->getType(),
            ]);

            return $response;
        } catch (Exception $e) {
            $this->logger->error('Failed to execute voice command', [
                'action' => $this->getAction(),
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);

            return CommandResponse::failure(
                'error',
                'Произошла ошибка при выполнении команды: ' . $e->getMessage(),
                ['error' => $e->getMessage()],
            );
        }
    }

    public function supports(string $action): bool
    {
        return $action === $this->getAction();
    }

    /**
     * Валидация параметров команды
     *
     * @throws RuntimeException При невалидных параметрах
     */
    abstract protected function validateParameters(array $parameters): void;

    /**
     * Выполнение основной логики команды
     */
    abstract protected function doExecute(array $parameters, User $user): CommandResponse;

    /**
     * Найти задачу или выбросить исключение
     *
     * @param string $search Поисковый запрос
     * @param User   $user   Пользователь
     *
     * @throws RuntimeException Если задача не найдена
     *
     * @return Task Найденная задача
     */
    protected function findTaskOrFail(string $search, User $user): Task
    {
        $task = $this->searchService->findBestMatch($search, $user);

        if (!$task) {
            throw new RuntimeException(sprintf('Задача "%s" не найдена', $search));
        }

        return $task;
    }

    /**
     * Найти задачу или вернуть null
     */
    protected function findTask(string $search, User $user): ?Task
    {
        return $this->searchService->findBestMatch($search, $user);
    }

    /**
     * Найти родительскую задачу из параметров
     *
     * @param array $parameters Параметры с parent_search/parent/parent_task
     * @param User  $user       Пользователь
     *
     * @throws RuntimeException Если задача не найдена или не указана
     *
     * @return Task Найденная родительская задача
     */
    protected function findParentTaskOrFail(array $parameters, User $user): Task
    {
        $parentSearch = $parameters['parent_search']
            ?? $parameters['parent']
            ?? $parameters['parent_task']
            ?? null;

        if (empty($parentSearch)) {
            throw new RuntimeException('Родительская задача не указана');
        }

        $parentTask = $this->searchService->findBestMatch($parentSearch, $user);

        if (!$parentTask) {
            throw new RuntimeException(sprintf('Родительская задача "%s" не найдена', $parentSearch));
        }

        return $parentTask;
    }

    /**
     * Парсинг диапазона дат из параметров
     *
     * @return array{start: ?DateTimeImmutable, due: ?DateTimeImmutable}
     */
    protected function parseDateRange(array $parameters): array
    {
        if (!isset($parameters['due_date'])) {
            return ['start' => null, 'due' => null];
        }

        // Временной диапазон (с 14:00 до 15:00)
        if (isset($parameters['start_time'], $parameters['end_time'])) {
            $startDate = $this->dateTimeParser->parseDateWithTime(
                $parameters['due_date'],
                $parameters['start_time'],
            );
            $endDate = $this->dateTimeParser->parseDateWithTime(
                $parameters['due_date'],
                $parameters['end_time'],
            );

            return ['start' => $startDate, 'due' => $endDate];
        }

        // Только начальное время
        if (isset($parameters['start_time'])) {
            $startDate = $this->dateTimeParser->parseDateWithTime(
                $parameters['due_date'],
                $parameters['start_time'],
            );
            $endDate = $startDate?->modify('+1 hour');

            return ['start' => $startDate, 'due' => $endDate];
        }

        // Только дата
        $startDate = $this->dateTimeParser->parseStartDate($parameters['due_date']);
        $dueDate = $this->dateTimeParser->parseDueDate($parameters['due_date']);

        return ['start' => $startDate, 'due' => $dueDate];
    }

    /**
     * Получить поисковый параметр из различных вариантов
     */
    protected function getSearchParameter(array $parameters): ?string
    {
        return $parameters['search']
            ?? $parameters['title']
            ?? $parameters['name']
            ?? null;
    }

    /**
     * Сохранить изменения в базу данных
     */
    protected function flush(): void
    {
        $this->entityManager->flush();
    }
}
