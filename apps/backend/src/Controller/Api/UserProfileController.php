<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Request\User\UpdateNotificationsDto;
use App\Dto\Request\User\UpdatePasswordDto;
use App\Dto\Request\User\UpdateProfileDto;
use App\Dto\Response\User\UserProfileDto;
use App\Service\UserProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users/profile', name: 'api_user_profile_')]
#[IsGranted('ROLE_USER')]
class UserProfileController extends AbstractController
{
    public function __construct(
        private readonly UserProfileService $profileService,
    ) {
    }

    #[Route('', name: 'get', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        $user = $this->getUser();
        $profileDto = UserProfileDto::fromEntity($user);

        return $this->json($profileDto);
    }

    #[Route('', name: 'update', methods: ['PATCH'])]
    public function updateProfile(
        #[MapRequestPayload]
        UpdateProfileDto $dto,
    ): JsonResponse {
        $user = $this->getUser();
        $updatedUser = $this->profileService->updateProfile($user, $dto);
        $profileDto = UserProfileDto::fromEntity($updatedUser);

        return $this->json($profileDto);
    }

    #[Route('/password', name: 'update_password', methods: ['POST'])]
    public function updatePassword(
        #[MapRequestPayload]
        UpdatePasswordDto $dto,
    ): JsonResponse {
        $user = $this->getUser();
        $this->profileService->updatePassword($user, $dto);

        return $this->json([
            'message' => $user->hasPassword()
                ? 'Пароль успешно изменен'
                : 'Пароль успешно создан',
        ]);
    }

    #[Route('/notifications', name: 'update_notifications', methods: ['PATCH'])]
    public function updateNotifications(
        #[MapRequestPayload]
        UpdateNotificationsDto $dto,
    ): JsonResponse {
        $user = $this->getUser();
        $updatedUser = $this->profileService->updateNotifications($user, $dto->getNotifications());
        $profileDto = UserProfileDto::fromEntity($updatedUser);

        return $this->json($profileDto);
    }
}
