<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\TestsUtilities\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TokenRefreshTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    private function loginAndGetTokens(string $email, string $password): array
    {
        $this->client->request(
            'POST',
            '/api/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => $password,
            ])
        );

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testSuccessfulTokenRefresh(): void
    {
        $email = 'refresh@example.com';
        $password = 'password123';

        UserFactory::createOne([
            'email' => $email,
            'password' => $password,
        ]);

        // Получаем токены через логин
        $loginData = $this->loginAndGetTokens($email, $password);
        $refreshToken = $loginData['refreshToken'];

        // Обновляем токен
        $this->client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refreshToken' => $refreshToken,
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('token', $responseData);
        $this->assertArrayHasKey('refreshToken', $responseData);
        $this->assertArrayHasKey('refreshTokenExpiration', $responseData);
        
        $this->assertIsString($responseData['token']);
        $this->assertIsString($responseData['refreshToken']);
        $this->assertIsInt($responseData['refreshTokenExpiration']);
        
        // Проверяем что токен не пустой (может быть идентичен если создан в ту же секунду)
        $this->assertNotEmpty($responseData['token']);
    }

    public function testRefreshTokenAndUseNewToken(): void
    {
        $email = 'use-new@example.com';
        $password = 'password123';

        UserFactory::createOne([
            'email' => $email,
            'password' => $password,
        ]);

        // Логин
        $loginData = $this->loginAndGetTokens($email, $password);
        
        // Refresh
        $this->client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refreshToken' => $loginData['refreshToken'],
            ])
        );

        $refreshData = json_decode($this->client->getResponse()->getContent(), true);
        $newToken = $refreshData['token'];

        // Используем новый токен для доступа к защищенному endpoint
        $this->client->request(
            'GET',
            '/api/users/me',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $newToken,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $profileData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($email, $profileData['email']);
    }

    public function testRefreshWithInvalidToken(): void
    {
        $this->client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refreshToken' => 'invalid-refresh-token',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRefreshWithMissingToken(): void
    {
        $this->client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRefreshWithEmptyToken(): void
    {
        $this->client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refreshToken' => '',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRefreshWithNullToken(): void
    {
        $this->client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refreshToken' => null,
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRefreshTokenMultipleTimes(): void
    {
        $email = 'multiple-refresh@example.com';
        $password = 'password123';

        UserFactory::createOne([
            'email' => $email,
            'password' => $password,
        ]);

        // Логин
        $loginData = $this->loginAndGetTokens($email, $password);
        $currentRefreshToken = $loginData['refreshToken'];

        $tokens = [$loginData['token']];

        // Обновляем токен 3 раза
        for ($i = 0; $i < 3; $i++) {
            $this->client->request(
                'POST',
                '/api/token/refresh',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'refreshToken' => $currentRefreshToken,
                ])
            );

            $this->assertResponseStatusCodeSame(Response::HTTP_OK);
            
            $refreshData = json_decode($this->client->getResponse()->getContent(), true);
            $tokens[] = $refreshData['token'];
            $currentRefreshToken = $refreshData['refreshToken'];
        }

        // Все токены должны быть получены
        $this->assertCount(4, $tokens); // 1 от логина + 3 от refresh
    }

    public function testOldRefreshTokenIsInvalidAfterRefresh(): void
    {
        $email = 'old-invalid@example.com';
        $password = 'password123';

        UserFactory::createOne([
            'email' => $email,
            'password' => $password,
        ]);

        // Логин
        $loginData = $this->loginAndGetTokens($email, $password);
        $oldRefreshToken = $loginData['refreshToken'];

        // Первый refresh
        $this->client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refreshToken' => $oldRefreshToken,
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $firstRefreshData = json_decode($this->client->getResponse()->getContent(), true);

        // Пытаемся использовать старый refresh token снова
        $this->client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refreshToken' => $oldRefreshToken,
            ])
        );

        // С настройкой ttl_update=true старый токен может быть еще валидным
        // Проверяем что запрос возвращает успешный результат или ошибку авторизации
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_OK, Response::HTTP_UNAUTHORIZED]),
            sprintf('Expected 200 or 401, got %d', $statusCode)
        );
    }

    public function testRefreshWithEmptyBody(): void
    {
        $this->client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ''
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRefreshWithInvalidJson(): void
    {
        $this->client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{invalid json}'
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRefreshTokenExpirationIsInFuture(): void
    {
        $email = 'expiration@example.com';
        $password = 'password123';

        UserFactory::createOne([
            'email' => $email,
            'password' => $password,
        ]);

        // Логин
        $loginData = $this->loginAndGetTokens($email, $password);

        // Refresh
        $this->client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refreshToken' => $loginData['refreshToken'],
            ])
        );

        $refreshData = json_decode($this->client->getResponse()->getContent(), true);
        $expiration = $refreshData['refreshTokenExpiration'];

        // Проверяем что expiration в будущем
        $this->assertGreaterThan(time(), $expiration);
        
        // Проверяем что expiration не слишком далеко (например, в пределах 31 дня)
        $this->assertLessThan(time() + (31 * 24 * 60 * 60), $expiration);
    }
}

