<?php

declare(strict_types=1);

namespace App\Dto\Response\User;

use App\Entity\User;

final class UserProfileDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly ?string $name,
        public readonly ?string $avatar,
        public readonly string $theme,
        public readonly string $language,
        public readonly string $timezone,
        public readonly array $notifications,
        public readonly bool $hasPassword,
        public readonly bool $isGoogleAuth,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->getId(),
            email: $user->getEmail(),
            name: $user->getName(),
            avatar: $user->getAvatar(),
            theme: $user->getTheme(),
            language: $user->getLanguage(),
            timezone: $user->getTimezone(),
            notifications: $user->getNotificationSettings(),
            hasPassword: $user->hasPassword(),
            isGoogleAuth: $user->hasGoogleAuth(),
            createdAt: $user->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
            updatedAt: $user->getUpdatedAt()->format('Y-m-d\TH:i:s\Z'),
        );
    }
}
