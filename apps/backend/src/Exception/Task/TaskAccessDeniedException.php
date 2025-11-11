<?php

declare(strict_types=1);

namespace App\Exception\Task;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

final class TaskAccessDeniedException extends AccessDeniedHttpException
{
    public function __construct(string $message = 'Access to this task is denied', ?Throwable $previous = null, int $code = 0)
    {
        parent::__construct($message, $previous, $code);
    }
}
