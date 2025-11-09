<?php

declare(strict_types=1);

namespace App\Dto\Response\User;

final readonly class UserRegistrationResponseDto
{
    public function __construct(
        public int $id,
        public string $email,
    ) {
    }
}
