<?php

declare(strict_types=1);

namespace App\Service\WebSocket;

use App\Entity\User;
use Firebase\JWT\JWT;

/**
 * Провайдер JWT токенов для аутентификации клиентов в Centrifugo
 */
class CentrifugoTokenProvider
{
    private string $hmacSecretKey;

    private int $tokenTtl;

    public function __construct(
        string $hmacSecretKey,
        int $tokenTtl = 3600
    ) {
        $this->hmacSecretKey = $hmacSecretKey;
        $this->tokenTtl = $tokenTtl;
    }

    /**
     * Генерирует JWT токен для подключения пользователя к Centrifugo
     */
    public function generateConnectionToken(User $user): string
    {
        $payload = [
            'sub' => (string) $user->getId(),
            'exp' => time() + $this->tokenTtl,
            'info' => [
                'name' => $user->getName(),
                'email' => $user->getEmail(),
            ],
        ];

        return JWT::encode($payload, $this->hmacSecretKey, 'HS256');
    }

    /**
     * Генерирует JWT токен для подписки на приватный канал
     */
    public function generateSubscriptionToken(User $user, string $channel): string
    {
        $payload = [
            'sub' => (string) $user->getId(),
            'channel' => $channel,
            'exp' => time() + $this->tokenTtl,
        ];

        return JWT::encode($payload, $this->hmacSecretKey, 'HS256');
    }

    /**
     * Генерирует токены для всех каналов пользователя
     *
     * @return array<string, string> Массив [channel => token]
     */
    public function generateAllChannelTokens(User $user): array
    {
        $userId = $user->getId();

        $channels = [
            sprintf('user:%d', $userId),
        ];

        $tokens = [];
        foreach ($channels as $channel) {
            $tokens[$channel] = $this->generateSubscriptionToken($user, $channel);
        }

        return $tokens;
    }
}
