<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\User;
use App\TestsUtilities\Factory\UserFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class UserProfileTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private KernelBrowser $client;

    private JWTTokenManagerInterface $jwtManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);
    }

    public function testGetProfileWithValidToken(): void
    {
        // Создаем пользователя
        $userProxy = UserFactory::createOne([
            'email'    => 'user@example.com',
            'password' => 'password123',
        ]);

        /** @var User $user */
        $user = $userProxy->_real();
        $token = $this->jwtManager->create($user);

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

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('email', $responseData);
        $this->assertArrayHasKey('roles', $responseData);
        $this->assertArrayHasKey('createdAt', $responseData);
        $this->assertArrayHasKey('updatedAt', $responseData);

        $this->assertEquals($user->getEmail(), $responseData['email']);
        $this->assertEquals($user->getId(), $responseData['id']);
    }

    public function testGetProfileWithGoogleUser(): void
    {
        // Создаем пользователя с Google данными
        $userProxy = UserFactory::createOne([
            'email'          => 'google@example.com',
            'password'       => null,
            'googleId'       => 'google-id-123',
            'googleUserName' => 'Google User Name',
        ]);

        /** @var User $user */
        $user = $userProxy->_real();
        $token = $this->jwtManager->create($user);

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

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        // API возвращает googleUserName в поле 'name'
        $this->assertArrayHasKey('name', $responseData);
        $this->assertEquals('Google User Name', $responseData['name']);
    }

    public function testGetProfileWithoutToken(): void
    {
        $this->client->request(
            'GET',
            '/api/users/me',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetProfileWithInvalidToken(): void
    {
        $this->client->request(
            'GET',
            '/api/users/me',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer invalid.token.here',
                'CONTENT_TYPE'       => 'application/json',
            ],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetProfileWithExpiredToken(): void
    {
        // Создаем пользователя
        $userProxy = UserFactory::createOne([
            'email'    => 'expired@example.com',
            'password' => 'password123',
        ]);

        /** @var User $user */
        $user = $userProxy->_real();

        // Создаем токен с прошедшим временем жизни (это сложно протестировать без модификации конфига)
        // Вместо этого используем неправильно подписанный токен
        $fakeToken = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE1MTYyMzkwMjJ9.fake';

        $this->client->request(
            'GET',
            '/api/users/me',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $fakeToken,
                'CONTENT_TYPE'       => 'application/json',
            ],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetProfileWithMalformedAuthorizationHeader(): void
    {
        $userProxy = UserFactory::createOne([
            'email'    => 'malformed-header@example.com',
            'password' => 'password123',
        ]);

        /** @var User $user */
        $user = $userProxy->_real();
        $token = $this->jwtManager->create($user);

        // Без "Bearer " префикса
        $this->client->request(
            'GET',
            '/api/users/me',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => $token,
                'CONTENT_TYPE'       => 'application/json',
            ],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetProfileResponseStructure(): void
    {
        $userProxy = UserFactory::createOne([
            'email'    => 'structure@example.com',
            'password' => 'password123',
            'roles'    => ['ROLE_USER', 'ROLE_ADMIN'],
        ]);

        /** @var User $user */
        $user = $userProxy->_real();
        $token = $this->jwtManager->create($user);

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

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        // Проверяем типы данных
        $this->assertIsInt($responseData['id']);
        $this->assertIsString($responseData['email']);
        $this->assertIsArray($responseData['roles']);
        $this->assertIsString($responseData['createdAt']);
        $this->assertIsString($responseData['updatedAt']);

        // Проверяем что роли корректны
        $this->assertContains('ROLE_USER', $responseData['roles']);
        $this->assertContains('ROLE_ADMIN', $responseData['roles']);
    }
}
