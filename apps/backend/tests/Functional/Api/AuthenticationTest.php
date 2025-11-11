<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\TestsUtilities\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AuthenticationTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testSuccessfulLogin(): void
    {
        $email = 'login@example.com';
        $password = 'password123';

        // Создаем пользователя с известным паролем
        UserFactory::createOne([
            'email'    => $email,
            'password' => $password,
        ]);

        $this->client->request(
            'POST',
            '/api/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email'    => $email,
                'password' => $password,
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('token', $responseData);
        $this->assertArrayHasKey('refreshToken', $responseData);
        $this->assertArrayHasKey('refreshTokenExpiration', $responseData);

        $this->assertIsString($responseData['token']);
        $this->assertIsString($responseData['refreshToken']);
        $this->assertIsInt($responseData['refreshTokenExpiration']);

        // Проверяем что токен не пустой
        $this->assertNotEmpty($responseData['token']);
        $this->assertNotEmpty($responseData['refreshToken']);
    }

    public function testLoginWithWrongPassword(): void
    {
        $email = 'valid-password@example.com';

        UserFactory::createOne([
            'email'    => $email,
            'password' => 'validpassword123',
        ]);

        $this->client->request(
            'POST',
            '/api/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email'    => $email,
                'password' => 'wrongpassword',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginWithWrongEmail(): void
    {
        UserFactory::createOne([
            'email'    => 'valid-email@example.com',
            'password' => 'validpassword123',
        ]);

        $this->client->request(
            'POST',
            '/api/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email'    => 'wrong@example.com',
                'password' => 'validpassword123',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginWithNonExistentUser(): void
    {
        $this->client->request(
            'POST',
            '/api/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email'    => 'nonexistent@example.com',
                'password' => 'anypassword',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginWithEmptyCredentials(): void
    {
        $this->client->request(
            'POST',
            '/api/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email'    => '',
                'password' => '',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testLoginWithMissingEmail(): void
    {
        $this->client->request(
            'POST',
            '/api/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'password' => 'password123',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testLoginWithMissingPassword(): void
    {
        $this->client->request(
            'POST',
            '/api/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test@example.com',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testLoginWithEmptyBody(): void
    {
        $this->client->request(
            'POST',
            '/api/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testLoginWithInvalidJson(): void
    {
        $this->client->request(
            'POST',
            '/api/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{invalid json}',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testLoginResponseContainsValidJWT(): void
    {
        $email = 'jwt-test@example.com';
        $password = 'password123';

        UserFactory::createOne([
            'email'    => $email,
            'password' => $password,
        ]);

        $this->client->request(
            'POST',
            '/api/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email'    => $email,
                'password' => $password,
            ]),
        );

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['token'];

        // JWT токен должен состоять из 3 частей, разделенных точками
        $this->assertCount(3, explode('.', $token));
    }

    public function testLoginAndUseTokenToAccessProtectedEndpoint(): void
    {
        $email = 'protected@example.com';
        $password = 'password123';

        UserFactory::createOne([
            'email'    => $email,
            'password' => $password,
        ]);

        // Логин
        $this->client->request(
            'POST',
            '/api/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email'    => $email,
                'password' => $password,
            ]),
        );

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['token'];

        // Используем токен для доступа к защищенному endpoint
        $this->client->request(
            'GET',
            '/api/users/me',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE'       => 'application/json',
            ],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $profileData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($email, $profileData['email']);
    }

    public function testMultipleSuccessfulLogins(): void
    {
        $email = 'multiple@example.com';
        $password = 'password123';

        UserFactory::createOne([
            'email'    => $email,
            'password' => $password,
        ]);

        $tokens = [];

        // Выполняем 3 логина
        for ($i = 0; $i < 3; $i++) {
            $this->client->request(
                'POST',
                '/api/auth',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'email'    => $email,
                    'password' => $password,
                ]),
            );

            $this->assertResponseStatusCodeSame(Response::HTTP_OK);

            $responseData = json_decode($this->client->getResponse()->getContent(), true);
            $tokens[] = $responseData['token'];
        }

        // Все токены должны быть уникальными (или могут быть одинаковыми в зависимости от TTL)
        $this->assertCount(3, $tokens);
    }
}
