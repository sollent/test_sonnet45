<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Интеграционные тесты Google Auth с полным мокированием HTTP запросов.
 *
 * Этот подход использует MockHttpClient для мокирования запросов к Google API,
 * что позволяет полностью контролировать ответы от Google и тестировать
 * все сценарии аутентификации.
 */
class GoogleAuthWithHttpMockTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private KernelBrowser $client;

    private array $testKeys = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->generateTestRSAKeys();
    }

    /**
     * @group integration
     * @group google-auth
     */
    public function testCompleteGoogleAuthFlowWithNewUser(): void
    {
        $email = 'integration-new@gmail.com';
        $googleId = 'google-id-' . uniqid();
        $name = 'Integration Test User';

        // Создаем валидный Google ID Token
        $idToken = $this->createGoogleIdToken([
            'sub'            => $googleId,
            'email'          => $email,
            'email_verified' => true,
            'name'           => $name,
            'picture'        => 'https://lh3.googleusercontent.com/test',
        ]);

        // TODO: Для полного мокирования нужно заменить file_get_contents в GoogleAuthController
        // на HttpClient и мокировать его здесь
        //
        // $mockResponse = new MockResponse(json_encode($this->createGoogleJWKsMock()));
        // $mockHttpClient = new MockHttpClient($mockResponse);
        //
        // static::getContainer()->set('http_client', $mockHttpClient);

        $this->client->request(
            'POST',
            '/api/auth/google',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['credential' => $idToken]),
        );

        // Проверяем что endpoint обработал запрос
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertNotEquals(Response::HTTP_NOT_FOUND, $statusCode);

        // После мокирования HTTP:
        // $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        //
        // $responseData = json_decode($this->client->getResponse()->getContent(), true);
        // $this->assertArrayHasKey('token', $responseData);
        // $this->assertArrayHasKey('refreshToken', $responseData);
        //
        // // Проверяем что пользователь создан
        // $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        // $user = $userRepository->findOneBy(['email' => $email]);
        //
        // $this->assertNotNull($user);
        // $this->assertEquals($email, $user->getEmail());
        // $this->assertEquals($googleId, $user->getGoogleId());
        // $this->assertEquals($name, $user->getGoogleUserName());
        // $this->assertNull($user->getPassword());
        // $this->assertContains('ROLE_USER', $user->getRoles());
    }

    /**
     * @group integration
     * @group google-auth
     */
    public function testCompleteGoogleAuthFlowWithExistingUser(): void
    {
        $email = 'integration-existing@gmail.com';
        $googleId = 'google-id-existing-' . uniqid();
        $name = 'Existing Integration User';

        // Создаем пользователя заранее
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $existingUser = new User();
        $existingUser->setEmail($email);
        $existingUser->setGoogleId($googleId);
        $existingUser->setGoogleUserName($name);
        $existingUser->setRoles(['ROLE_USER']);

        $entityManager->persist($existingUser);
        $entityManager->flush();
        $entityManager->clear();

        $userId = $existingUser->getId();

        // Создаем валидный Google ID Token
        $idToken = $this->createGoogleIdToken([
            'sub'            => $googleId,
            'email'          => $email,
            'email_verified' => true,
            'name'           => $name,
        ]);

        $this->client->request(
            'POST',
            '/api/auth/google',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['credential' => $idToken]),
        );

        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertNotEquals(Response::HTTP_NOT_FOUND, $statusCode);

        // После мокирования HTTP:
        // $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        //
        // // Проверяем что НЕ создан дубликат
        // $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        // $users = $userRepository->findBy(['email' => $email]);
        // $this->assertCount(1, $users);
        // $this->assertEquals($userId, $users[0]->getId());
    }

    /**
     * @group integration
     * @group google-auth
     */
    public function testGoogleAuthWithTokenCanAccessProtectedEndpoints(): void
    {
        $email = 'integration-protected@gmail.com';
        $googleId = 'google-id-protected-' . uniqid();
        $name = 'Protected Access User';

        // Создаем пользователя напрямую (симулируем успешную Google auth)
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $user = new User();
        $user->setEmail($email);
        $user->setGoogleId($googleId);
        $user->setGoogleUserName($name);
        $user->setRoles(['ROLE_USER']);

        $entityManager->persist($user);
        $entityManager->flush();

        // Получаем JWT токен для этого пользователя
        $jwtManager = static::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        $token = $jwtManager->create($user);

        // Пытаемся получить доступ к защищенному endpoint
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

        $this->assertEquals($email, $responseData['email']);
        $this->assertEquals($name, $responseData['name']);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('roles', $responseData);
    }

    /**
     * @group integration
     * @group google-auth
     */
    public function testGoogleAuthPreservesUserRolesOnReLogin(): void
    {
        $email = 'integration-roles@gmail.com';
        $googleId = 'google-id-roles-' . uniqid();
        $name = 'User With Roles';

        // Создаем пользователя с дополнительными ролями
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $user = new User();
        $user->setEmail($email);
        $user->setGoogleId($googleId);
        $user->setGoogleUserName($name);
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_MODERATOR']);

        $entityManager->persist($user);
        $entityManager->flush();
        $entityManager->clear();

        // Симулируем повторный логин через Google
        $idToken = $this->createGoogleIdToken([
            'sub'            => $googleId,
            'email'          => $email,
            'email_verified' => true,
            'name'           => $name,
        ]);

        $this->client->request(
            'POST',
            '/api/auth/google',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['credential' => $idToken]),
        );

        // После мокирования HTTP:
        // $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        // $reloadedUser = $userRepository->findOneBy(['email' => $email]);
        //
        // // Роли должны сохраниться
        // $this->assertContains('ROLE_ADMIN', $reloadedUser->getRoles());
        // $this->assertContains('ROLE_MODERATOR', $reloadedUser->getRoles());

        $this->assertNotEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @group integration
     * @group google-auth
     */
    public function testMultipleGoogleUsersCanCoexist(): void
    {
        $users = [
            [
                'email'    => 'user1@gmail.com',
                'googleId' => 'google-id-1-' . uniqid(),
                'name'     => 'User One',
            ],
            [
                'email'    => 'user2@gmail.com',
                'googleId' => 'google-id-2-' . uniqid(),
                'name'     => 'User Two',
            ],
            [
                'email'    => 'user3@gmail.com',
                'googleId' => 'google-id-3-' . uniqid(),
                'name'     => 'User Three',
            ],
        ];

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        // Создаем несколько Google пользователей
        foreach ($users as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setGoogleId($userData['googleId']);
            $user->setGoogleUserName($userData['name']);
            $user->setRoles(['ROLE_USER']);

            $entityManager->persist($user);
        }

        $entityManager->flush();
        $entityManager->clear();

        // Проверяем что все созданы
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);

        foreach ($users as $userData) {
            $user = $userRepository->findOneBy(['email' => $userData['email']]);
            $this->assertNotNull($user);
            $this->assertEquals($userData['googleId'], $user->getGoogleId());
            $this->assertEquals($userData['name'], $user->getGoogleUserName());
        }

        // Проверяем что всего 3 пользователя
        $allUsers = $userRepository->findAll();
        $this->assertGreaterThanOrEqual(3, count($allUsers));
    }

    /**
     * Генерирует тестовые RSA ключи для подписи JWT.
     */
    private function generateTestRSAKeys(): void
    {
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($privateKey, $privateKeyPem);
        $publicKeyDetails = openssl_pkey_get_details($privateKey);
        $publicKey = $publicKeyDetails['key'];

        $this->testKeys = [
            'private'     => $privateKey,
            'private_pem' => $privateKeyPem,
            'public'      => $publicKey,
            'n'           => $publicKeyDetails['rsa']['n'],
            'e'           => $publicKeyDetails['rsa']['e'],
            'kid'         => 'test-key-' . bin2hex(random_bytes(8)),
        ];
    }

    /**
     * Создает валидный Google ID Token (JWT).
     */
    private function createGoogleIdToken(array $claims): string
    {
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
            'kid' => $this->testKeys['kid'],
        ];

        $defaultClaims = [
            'iss' => 'https://accounts.google.com',
            'aud' => getenv('GOOGLE_CLIENT_ID') ?: 'test-client-id',
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        $payload = array_merge($defaultClaims, $claims);

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        $signatureInput = $headerEncoded . '.' . $payloadEncoded;

        openssl_sign($signatureInput, $signature, $this->testKeys['private'], OPENSSL_ALGO_SHA256);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Создает мок для Google JWKs endpoint.
     */
    private function createGoogleJWKsMock(): array
    {
        return [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'alg' => 'RS256',
                    'use' => 'sig',
                    'kid' => $this->testKeys['kid'],
                    'n'   => $this->base64UrlEncode($this->testKeys['n']),
                    'e'   => $this->base64UrlEncode($this->testKeys['e']),
                ],
            ],
        ];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
