<?php

declare(strict_types=1);

namespace App\Service\AI\Response;

use App\Entity\Task;

/**
 * Построитель ответов для голосовых команд
 *
 * Предоставляет удобные методы для создания типовых ответов.
 * Следует принципу Single Responsibility и паттерну Builder.
 */
class ResponseBuilder
{
    /**
     * Задача создана
     */
    public function taskCreated(Task $task, array $additionalData = []): CommandResponse
    {
        $taskData = array_merge([
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'status' => $task->getStatus()->value,
            'priority' => $task->getPriority()->value,
            'startDate' => $task->getStartDate()?->format('c'),
            'dueDate' => $task->getDueDate()?->format('c'),
        ], $additionalData);

        return CommandResponse::success(
            'task_created',
            sprintf('Задача "%s" успешно создана', $task->getTitle()),
            ['task' => $taskData]
        );
    }

    /**
     * Задача завершена
     */
    public function taskCompleted(Task $task): CommandResponse
    {
        return CommandResponse::success(
            'task_completed',
            sprintf('Задача "%s" отмечена как выполненная', $task->getTitle()),
            ['task' => $this->serializeTask($task)]
        );
    }

    /**
     * Задача обновлена
     */
    public function taskUpdated(Task $task, array $updatedFields = []): CommandResponse
    {
        $fieldsString = !empty($updatedFields)
            ? ': ' . implode(', ', $updatedFields)
            : '';

        return CommandResponse::success(
            'task_updated',
            sprintf('Задача "%s" обновлена%s', $task->getTitle(), $fieldsString),
            [
                'task' => $this->serializeTask($task),
                'updated_fields' => $updatedFields,
            ]
        );
    }

    /**
     * Задача удалена
     */
    public function taskDeleted(int $taskId, string $taskTitle): CommandResponse
    {
        return CommandResponse::success(
            'task_deleted',
            sprintf('Задача "%s" удалена', $taskTitle),
            ['task' => ['id' => $taskId, 'title' => $taskTitle]]
        );
    }

    /**
     * Задача не найдена
     */
    public function taskNotFound(string $search): CommandResponse
    {
        return CommandResponse::failure(
            'task_not_found',
            sprintf('Задача "%s" не найдена', $search),
            ['search' => $search]
        );
    }

    /**
     * Родительская задача не найдена
     */
    public function parentNotFound(string $search): CommandResponse
    {
        return CommandResponse::failure(
            'parent_not_found',
            sprintf('Родительская задача "%s" не найдена', $search),
            ['search' => $search]
        );
    }

    /**
     * Пакетная операция выполнена успешно
     */
    public function batchSuccess(
        string $type,
        string $operation,
        int $successCount,
        int $totalCount,
        array $items = [],
        array $notFound = [],
        array $errors = []
    ): CommandResponse {
        $data = [
            'success_count' => $successCount,
            'total_count' => $totalCount,
        ];

        if (!empty($items)) {
            $data['items'] = $items;
        }

        if (!empty($notFound)) {
            $data['not_found'] = $notFound;
        }

        return CommandResponse::success(
            $type,
            sprintf('%s: %d из %d', $operation, $successCount, $totalCount),
            $data,
            $errors
        );
    }

    /**
     * Пакетная операция не выполнена
     */
    public function batchFailed(
        string $type,
        string $message,
        array $notFound = [],
        array $errors = []
    ): CommandResponse {
        return CommandResponse::failure(
            $type,
            $message,
            ['not_found' => $notFound],
            $errors
        );
    }

    /**
     * Фильтрация задач
     */
    public function tasksFiltered(array $tasks, array $filters = []): CommandResponse
    {
        $count = count($tasks);
        $taskList = array_map([$this, 'serializeTask'], $tasks);

        return CommandResponse::success(
            'tasks_filtered',
            $count > 0
                ? sprintf('Найдено задач: %d', $count)
                : 'Задачи не найдены',
            [
                'count' => $count,
                'tasks' => $taskList,
                'filters' => $filters,
            ]
        );
    }

    /**
     * Подзадача создана
     */
    public function subtaskCreated(Task $subtask, Task $parentTask): CommandResponse
    {
        return CommandResponse::success(
            'subtask_created',
            sprintf('Подзадача "%s" создана для "%s"', $subtask->getTitle(), $parentTask->getTitle()),
            [
                'subtask' => $this->serializeTask($subtask),
                'parent' => [
                    'id' => $parentTask->getId(),
                    'title' => $parentTask->getTitle(),
                ],
            ]
        );
    }

    /**
     * Несколько задач созданы
     */
    public function multipleTasksCreated(int $successCount, int $totalCount, array $tasks = [], array $errors = []): CommandResponse
    {
        return $this->batchSuccess(
            'multiple_tasks_created',
            'Создано задач',
            $successCount,
            $totalCount,
            $tasks,
            [],
            $errors
        );
    }

    /**
     * Тег добавлен
     */
    public function tagAdded(Task $task, string $tagName): CommandResponse
    {
        return CommandResponse::success(
            'tag_added',
            sprintf('Тег "%s" добавлен к задаче "%s"', $tagName, $task->getTitle()),
            [
                'task' => $this->serializeTask($task),
                'tag' => $tagName,
            ]
        );
    }

    /**
     * Тег удален
     */
    public function tagRemoved(Task $task, string $tagName): CommandResponse
    {
        return CommandResponse::success(
            'tag_removed',
            sprintf('Тег "%s" удалён с задачи "%s"', $tagName, $task->getTitle()),
            [
                'task' => $this->serializeTask($task),
                'tag' => $tagName,
            ]
        );
    }

    /**
     * Ошибка валидации параметров
     */
    public function validationError(string $message): CommandResponse
    {
        return CommandResponse::failure(
            'validation_error',
            $message
        );
    }

    /**
     * Общая ошибка
     */
    public function error(string $message, string $error = ''): CommandResponse
    {
        return CommandResponse::failure(
            'error',
            $message,
            ['error' => $error]
        );
    }

    /**
     * Сериализация задачи в массив
     */
    public function serializeTask(Task $task): array
    {
        return [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'status' => $task->getStatus()->value,
            'priority' => $task->getPriority()->value,
            'startDate' => $task->getStartDate()?->format('c'),
            'dueDate' => $task->getDueDate()?->format('c'),
            'tags' => array_map(fn ($tag) => $tag->getName(), $task->getTags()->toArray()),
        ];
    }
}