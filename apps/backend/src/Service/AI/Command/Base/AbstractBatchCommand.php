<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Base;

use App\Entity\User;
use App\Service\AI\Response\CommandResponse;
use App\Service\AI\Response\ResponseBuilder;
use App\Service\AI\Service\TaskFinder;

/**
 * Базовый класс для пакетных команд
 *
 * Предоставляет общую логику для операций над несколькими задачами.
 * Следует паттерну Template Method.
 */
abstract class AbstractBatchCommand extends AbstractVoiceCommand
{
    protected TaskFinder $taskFinder;
    protected ResponseBuilder $responseBuilder;

    /**
     * Обработать пакетную операцию по фильтрам
     *
     * @param array $filters Фильтры для поиска задач
     * @param User $user Пользователь
     * @return CommandResponse
     */
    protected function processBatchByFilters(array $filters, User $user): CommandResponse
    {
        // Поиск задач по фильтрам
        $tasks = $this->taskFinder->filter($filters, $user);

        if (empty($tasks)) {
            return $this->getNoTasksResponse($filters);
        }

        // Выполнение операции для каждой задачи
        $processed = [];
        $errors = [];

        foreach ($tasks as $task) {
            try {
                if ($this->shouldProcessTask($task)) {
                    $this->processTask($task, $user);
                    $processed[] = [
                        'id' => $task->getId(),
                        'title' => $task->getTitle(),
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = sprintf('Задача "%s": %s', $task->getTitle(), $e->getMessage());
            }
        }

        // Сохранение изменений
        $this->flush();

        return $this->getBatchSuccessResponse(
            count($processed),
            count($tasks),
            $processed,
            $errors
        );
    }

    /**
     * Обработать пакетную операцию по списку названий
     *
     * @param array<string> $taskNames Названия задач
     * @param User $user Пользователь
     * @return CommandResponse
     */
    protected function processBatchByNames(array $taskNames, User $user): CommandResponse
    {
        if (empty($taskNames) || !is_array($taskNames)) {
            throw new \RuntimeException($this->getEmptyTaskNamesMessage());
        }

        $result = $this->taskFinder->findMultiple($taskNames, $user);
        $foundTasks = $result['found'];
        $notFoundTasks = $result['not_found'];

        $processed = [];
        $errors = [];

        foreach ($foundTasks as $task) {
            try {
                if ($this->shouldProcessTask($task)) {
                    $this->processTask($task, $user);
                    $processed[] = [
                        'id' => $task->getId(),
                        'title' => $task->getTitle(),
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = sprintf('Задача "%s": %s', $task->getTitle(), $e->getMessage());
            }
        }

        // Сохранение изменений
        $this->flush();

        $successCount = count($processed);
        $totalCount = count($taskNames);

        if ($successCount === 0) {
            return $this->getNoSuccessResponse($notFoundTasks, $errors);
        }

        return $this->getBatchSuccessResponse(
            $successCount,
            $totalCount,
            $processed,
            $errors,
            $notFoundTasks
        );
    }

    /**
     * Проверить, нужно ли обрабатывать задачу
     *
     * @param $task Задача
     * @return bool True если задача должна быть обработана
     */
    abstract protected function shouldProcessTask($task): bool;

    /**
     * Обработать одну задачу
     *
     * @param $task Задача
     * @param User $user Пользователь
     */
    abstract protected function processTask($task, User $user): void;

    /**
     * Получить ответ когда нет задач для обработки
     *
     * @param array $filters Фильтры
     * @return CommandResponse
     */
    abstract protected function getNoTasksResponse(array $filters): CommandResponse;

    /**
     * Получить ответ при успешной пакетной операции
     *
     * @param int $successCount Количество успешных операций
     * @param int $totalCount Общее количество
     * @param array $processed Обработанные задачи
     * @param array $errors Ошибки
     * @param array $notFound Не найденные задачи
     * @return CommandResponse
     */
    abstract protected function getBatchSuccessResponse(
        int $successCount,
        int $totalCount,
        array $processed,
        array $errors = [],
        array $notFound = []
    ): CommandResponse;

    /**
     * Получить ответ когда ни одна задача не обработана успешно
     *
     * @param array $notFound Не найденные задачи
     * @param array $errors Ошибки
     * @return CommandResponse
     */
    abstract protected function getNoSuccessResponse(array $notFound, array $errors): CommandResponse;

    /**
     * Получить сообщение об ошибке для пустого списка задач
     *
     * @return string
     */
    protected function getEmptyTaskNamesMessage(): string
    {
        return 'Tasks array is required for batch operation';
    }
}