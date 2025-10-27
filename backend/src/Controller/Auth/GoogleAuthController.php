<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Security\GoogleAuthenticator;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

class GoogleAuthController extends AbstractController
{
    #[Route('/api/auth/google', name: 'api_google_auth', methods: ['POST'])]
    public function google(
        Request $request,
        GoogleAuthenticator $googleAuthenticator,
        JWTTokenManagerInterface $jwtManager,
        RefreshTokenManagerInterface $refreshTokenManager,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $credential = $data['credential'] ?? null;

        if (!$credential) {
            return $this->json(['error' => 'Missing credential'], 400);
        }

        // Получение и декодирование публичных ключей Google (JWKs)
        $googleJwks = json_decode(file_get_contents('https://www.googleapis.com/oauth2/v3/certs'), true);
        $decoded = JWT::decode($credential, JWK::parseKeySet($googleJwks));

        $email = $decoded->email ?? null;

        if (!$email) {
            return $this->json(['error' => 'Invalid token'], 400);
        }

        // Создание или получение пользователя
        $user = $googleAuthenticator->loadUserFromDecodedJwt($decoded);

        $token = $jwtManager->create($user);

        $refreshToken = $refreshTokenManager->create();
        $refreshToken->setRefreshToken(Uuid::v4()->toRfc4122());
        $refreshToken->setUsername($user->getUserIdentifier());
        $refreshToken->setValid((new \DateTime())->modify('+30 days'));

        $refreshTokenManager->save($refreshToken);

        return $this->json([
            'token' => $token,
            'refreshToken' => $refreshToken->getRefreshToken(),
            'refreshTokenExpiration' => $refreshToken->getValid()?->getTimestamp(),
        ]);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheck(): Response
    {
        // Пустой метод — просто чтобы маршрут существовал
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
