<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\User;
use App\TestsUtilities\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class UserRegistrationTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * @dataProvider validRegistrationDataProvider
     */
    public function testSuccessfulRegistration(string $email, string $password): void
    {
        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => $password,
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('email', $responseData);
        $this->assertEquals($email, $responseData['email']);
        
        // Проверяем что пользователь создан в БД
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        
        $this->assertNotNull($user);
        $this->assertEquals($email, $user->getEmail());
        $this->assertNotNull($user->getPassword());
    }

    public function validRegistrationDataProvider(): array
    {
        return [
            'standard email' => ['test@example.com', 'SecurePassword123!'],
            'email with subdomain' => ['user@mail.example.com', 'AnotherPass456!'],
            'email with plus' => ['user+test@example.com', 'Password789!'],
            'long password' => ['long@example.com', 'VeryLongSecurePassword123456789!@#$%'],
        ];
    }

    public function testRegistrationWithExistingEmail(): void
    {
        $email = 'existing@example.com';
        
        // Создаем пользователя через фабрику
        UserFactory::createOne([
            'email' => $email,
            'password' => 'password123',
        ]);

        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => 'NewPassword123!',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('email', strtolower($responseData['message']));
    }

    /**
     * @dataProvider invalidRegistrationDataProvider
     */
    public function testRegistrationWithInvalidData(array $payload, string $expectedErrorField): void
    {
        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function invalidRegistrationDataProvider(): array
    {
        return [
            'missing email' => [
                ['password' => 'Password123!'],
                'email',
            ],
            'missing password' => [
                ['email' => 'test@example.com'],
                'password',
            ],
            'invalid email format' => [
                ['email' => 'not-an-email', 'password' => 'Password123!'],
                'email',
            ],
            'empty email' => [
                ['email' => '', 'password' => 'Password123!'],
                'email',
            ],
            'empty password' => [
                ['email' => 'test@example.com', 'password' => ''],
                'password',
            ],
            'password too short' => [
                ['email' => 'test@example.com', 'password' => '12345'],
                'password',
            ],
        ];
    }

    public function testRegistrationWithEmptyBody(): void
    {
        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ''
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRegistrationWithInvalidJson(): void
    {
        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{invalid json}'
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRegistrationWithMissingContentType(): void
    {
        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            [],
            json_encode([
                'email' => 'test@example.com',
                'password' => 'Password123!',
            ])
        );

        // Без Content-Type Symfony может вернуть ошибку или обработать как form data
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_BAD_REQUEST, Response::HTTP_UNPROCESSABLE_ENTITY, Response::HTTP_CREATED]),
            sprintf('Expected 400, 422 or 201, got %d', $statusCode)
        );
    }
}

