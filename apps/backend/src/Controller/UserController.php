<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\User\UserRegistrationRequestDto;
use App\Dto\Response\BadRequestResponseDto;
use App\Dto\Response\User\UserProfileResponseDto;
use App\Dto\Response\User\UserRegistrationResponseDto;
use App\Entity\User;
use App\Exception\User\UserRegistrationException;
use App\Service\UserRegistrationService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    public function __construct(private UserRegistrationService $userRegistrationService)
    {
    }

    #[Route('/api/users', methods: ['POST'])]
    #[OA\RequestBody]
    #[OA\Response(
        response: 201,
        description: 'User has been successfully registered',
        content: new Model(type: UserRegistrationResponseDto::class),
    )]
    #[OA\Response(
        response: 400,
        description: 'Provided user data has error or unprocessable',
        content: new Model(type: BadRequestResponseDto::class),
    )]
    #[OA\Tag(name: 'User')]
    public function register(#[MapRequestPayload] UserRegistrationRequestDto $registrationRequestDto): JsonResponse
    {

        try {
            /** @var User $user */
            $user = $this->userRegistrationService->register($registrationRequestDto);
        } catch (UserRegistrationException $e) {
            return $this->json(
                new BadRequestResponseDto($e->getMessage(), $e->getCode()),
                Response::HTTP_BAD_REQUEST,
            );
        }

        return $this->json(
            new UserRegistrationResponseDto($user->getId(), $user->getEmail()),
            Response::HTTP_CREATED,
        );
    }

    #[Route('/api/users/me', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Current user profile information',
        content: new Model(type: UserProfileResponseDto::class),
    )]
    #[OA\Response(
        response: 401,
        description: 'User not authenticated',
    )]
    #[OA\Tag(name: 'User')]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(
            new UserProfileResponseDto(
                $user->getId(),
                $user->getEmail(),
                $user->getGoogleUserName(),
                $user->getRoles(),
                $user->getCreatedAt(),
                $user->getUpdatedAt(),
            ),
            Response::HTTP_OK,
        );
    }
}
