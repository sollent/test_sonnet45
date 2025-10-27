<?php

declare(strict_types=1);

namespace App\Dto\Response\User;

class UserProfileResponseDto
{
    public function __construct(
        public ?int $id = null,
        public ?string $email = null,
        public ?string $name = null,
        public ?array $roles = [],
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
        public ?bool $isEmailVerified = true // По умолчанию true, можно добавить логику проверки
    ) {
    }
}