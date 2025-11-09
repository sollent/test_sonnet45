<?php

declare(strict_types=1);

namespace App\Exception\Task;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TaskNotFoundException extends NotFoundHttpException
{
    public function __construct(string $message = 'Task not found', \Throwable $previous = null, int $code = 0)
    {
        parent::__construct($message, $previous, $code);
    }
}
