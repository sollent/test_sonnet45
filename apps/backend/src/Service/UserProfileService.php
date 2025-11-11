<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\User\UpdatePasswordDto;
use App\Dto\Request\User\UpdateProfileDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserProfileService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function updateProfile(User $user, UpdateProfileDto $dto): User
    {
        if ($dto->getName() !== null) {
            $user->setName($dto->getName());
        }

        if ($dto->getLanguage() !== null) {
            $user->setLanguage($dto->getLanguage());
        }

        if ($dto->getTimezone() !== null) {
            $user->setTimezone($dto->getTimezone());
        }

        $this->entityManager->flush();

        return $user;
    }

    public function updatePassword(User $user, UpdatePasswordDto $dto): void
    {
        // If user has password, verify current password
        if ($user->hasPassword()) {
            if ($dto->getCurrentPassword() === null) {
                throw new BadRequestHttpException('Текущий пароль обязателен');
            }

            if (!$this->passwordHasher->isPasswordValid($user, $dto->getCurrentPassword())) {
                throw new UnauthorizedHttpException('', 'Неверный текущий пароль');
            }
        }

        // Hash and set new password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->getNewPassword());
        $user->setPassword($hashedPassword);

        $this->entityManager->flush();
    }

    public function updateNotifications(User $user, array $notifications): User
    {
        $user->setNotificationSettings($notifications);
        $this->entityManager->flush();

        return $user;
    }
}
