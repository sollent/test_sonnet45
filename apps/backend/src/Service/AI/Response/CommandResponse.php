<?php

declare(strict_types=1);

namespace App\Service\AI\Response;

use JsonSerializable;

/**
 * DTO для ответа голосовой команды
 *
 * Заменяет array ответы на типизированный объект,
 * следуя принципу Type Safety и улучшая поддержку IDE.
 */
final class CommandResponse implements JsonSerializable
{
    /**
     * @param string $type    Тип ответа (например, 'task_created', 'task_completed')
     * @param bool   $success Успешность выполнения команды
     * @param string $message Сообщение для пользователя
     * @param array  $data    Дополнительные данные (task, tasks, count и т.д.)
     * @param array  $errors  Список ошибок при batch операциях
     */
    public function __construct(
        private readonly string $type,
        private readonly bool $success,
        private readonly string $message,
        private readonly array $data = [],
        private readonly array $errors = [],
    ) {
    }

    public static function success(string $type, string $message, array $data = [], array $errors = []): self
    {
        return new self($type, true, $message, $data, $errors);
    }

    public static function failure(string $type, string $message, array $data = [], array $errors = []): self
    {
        return new self($type, false, $message, $data, $errors);
    }

    public static function taskCreated(int $taskId, string $taskTitle, array $additionalData = []): self
    {
        return self::success(
            'task_created',
            sprintf('Задача "%s" успешно создана', $taskTitle),
            array_merge(['task' => ['id' => $taskId, 'title' => $taskTitle]], $additionalData),
        );
    }

    public static function taskCompleted(int $taskId, string $taskTitle): self
    {
        return self::success(
            'task_completed',
            sprintf('Задача "%s" отмечена как выполненная', $taskTitle),
            ['task' => ['id' => $taskId, 'title' => $taskTitle]],
        );
    }

    public static function taskNotFound(string $search): self
    {
        return self::failure(
            'task_not_found',
            sprintf('Задача "%s" не найдена', $search),
            ['search' => $search],
        );
    }

    public static function batchSuccess(string $type, string $operation, int $successCount, int $totalCount, array $items = []): self
    {
        return self::success(
            $type,
            sprintf('%s: %d из %d', $operation, $successCount, $totalCount),
            [
                'success_count' => $successCount,
                'total_count'   => $totalCount,
                'items'         => $items,
            ],
        );
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function jsonSerialize(): array
    {
        $result = [
            'type'    => $this->type,
            'success' => $this->success,
            'message' => $this->message,
        ];

        // Добавляем данные если они есть
        foreach ($this->data as $key => $value) {
            $result[$key] = $value;
        }

        // Добавляем ошибки если они есть
        if (!empty($this->errors)) {
            $result['errors'] = $this->errors;
        }

        return $result;
    }

    /**
     * Преобразовать в массив для обратной совместимости
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
