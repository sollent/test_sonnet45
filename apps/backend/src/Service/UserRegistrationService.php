<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\User\UserRegistrationRequestDto;
use App\Entity\User;
use App\Exception\User\UserRegistrationException;
use App\Repository\Database\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserRegistrationService
{
    public function __construct(
        private UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws UserRegistrationException
     */
    public function register(UserRegistrationRequestDto $userRegistrationDto): UserInterface
    {
        $userExists = $this->userRepository->findOneBy(['email' => $userRegistrationDto->email]);

        if ($userExists) {
            throw new UserRegistrationException($this->translator->trans('user_registration.messages.exists_with_such_email'));
        }

        $user = new User();
        $user->setEmail($userRegistrationDto->email);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $userRegistrationDto->password,
        );

        $user->setPassword($hashedPassword);
        $user->eraseCredentials();

        $this->userRepository->save($user, true);

        return $user;
    }
}
