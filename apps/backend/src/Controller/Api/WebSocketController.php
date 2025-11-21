<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\WebSocket\CentrifugoTokenProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Контроллер для WebSocket операций
 *
 * Предоставляет JWT токены для аутентификации клиентов в Centrifugo.
 */
#[Route('/api/websocket')]
class WebSocketController extends AbstractController
{
    public function __construct(
        private readonly CentrifugoTokenProvider $tokenProvider
    ) {}

    /**
     * Получить токен для подключения к WebSocket
     *
     * Возвращает JWT токен для аутентификации в Centrifugo
     * и токены для подписки на личные каналы пользователя.
     */
    #[Route('/token', name: 'api_websocket_token', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getToken(): JsonResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'error' => 'User not authenticated',
            ], 401);
        }

        // Генерируем токен подключения
        $connectionToken = $this->tokenProvider->generateConnectionToken($user);

        // Генерируем токены для всех каналов пользователя
        $channelTokens = $this->tokenProvider->generateAllChannelTokens($user);

        return $this->json([
            'token' => $connectionToken,
            'channels' => $channelTokens,
            'user_id' => $user->getId(),
            'websocket_url' => 'ws://localhost:8001/connection/websocket',
        ]);
    }

    /**
     * Получить токен для подписки на конкретный канал
     */
    #[Route('/subscribe/{channel}', name: 'api_websocket_subscribe', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getSubscriptionToken(string $channel): JsonResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'error' => 'User not authenticated',
            ], 401);
        }

        // Проверяем, что пользователь может подписаться на этот канал
        $userId = $user->getId();
        $allowedChannels = [
            sprintf('personal:%d', $userId),
            sprintf('personal:%d:voice', $userId),
            sprintf('personal:%d:tasks', $userId),
        ];

        if (!in_array($channel, $allowedChannels, true)) {
            return $this->json([
                'error' => 'Access denied to channel',
            ], 403);
        }

        $token = $this->tokenProvider->generateSubscriptionToken($user, $channel);

        return $this->json([
            'token' => $token,
            'channel' => $channel,
        ]);
    }
}
