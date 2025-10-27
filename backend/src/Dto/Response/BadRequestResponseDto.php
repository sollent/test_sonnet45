<?php

declare(strict_types=1);

namespace App\Dto\Response;

final readonly class BadRequestResponseDto
{
    public function __construct(
        public string $message,
        public int $code,
    ) {
    }
}
