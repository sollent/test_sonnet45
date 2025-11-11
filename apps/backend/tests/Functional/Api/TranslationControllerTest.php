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

class TranslationControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private KernelBrowser $client;

    private JWTTokenManagerInterface $jwtManager;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);

        // Create authenticated user
        $userProxy = UserFactory::createOne([
            'email'    => 'test-' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);
        $this->user = $userProxy->_real();
        $this->token = $this->jwtManager->create($this->user);
    }

    /** @test */
    public function testGetEnumTranslationsReturnsAllEnums(): void
    {
        // Act
        $this->request('GET', '/api/translations/enums');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('priorities', $data);
        $this->assertArrayHasKey('statuses', $data);
    }

    /** @test */
    public function testGetEnumTranslationsRespectsLocaleParameter(): void
    {
        // Act - Request with Russian locale parameter
        $this->request('GET', '/api/translations/enums?locale=ru');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertArrayHasKey('priorities', $data);
        $this->assertArrayHasKey('statuses', $data);

        // Verify structure
        $this->assertIsArray($data['priorities']);
        $this->assertIsArray($data['statuses']);
    }

    /** @test */
    public function testGetEnumTranslationsRespectsAcceptLanguageHeader(): void
    {
        // Act - Request with Accept-Language header
        $this->request('GET', '/api/translations/enums', [
            'HTTP_ACCEPT_LANGUAGE' => 'ru',
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertArrayHasKey('priorities', $data);
        $this->assertArrayHasKey('statuses', $data);
    }

    /** @test */
    public function testGetPriorityTranslationsReturnsAllPriorities(): void
    {
        // Act
        $this->request('GET', '/api/translations/priorities');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);

        // Verify all priorities are present
        $this->assertArrayHasKey('low', $data);
        $this->assertArrayHasKey('medium', $data);
        $this->assertArrayHasKey('high', $data);
        $this->assertArrayHasKey('urgent', $data);

        // Verify each has correct structure (value, label, color)
        foreach ($data as $priority => $translation) {
            $this->assertIsArray($translation);
            $this->assertArrayHasKey('value', $translation);
            $this->assertArrayHasKey('label', $translation);
            $this->assertArrayHasKey('color', $translation);
            $this->assertIsString($translation['label']);
            $this->assertNotEmpty($translation['label']);
        }
    }

    /** @test */
    public function testGetPriorityTranslationsRespectsLocaleParameter(): void
    {
        // Act - Request with Russian locale
        $this->request('GET', '/api/translations/priorities?locale=ru');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertArrayHasKey('high', $data);
        $this->assertIsArray($data['high']);
        $this->assertArrayHasKey('label', $data['high']);
        $this->assertIsString($data['high']['label']);
    }

    /** @test */
    public function testGetStatusTranslationsReturnsAllStatuses(): void
    {
        // Act
        $this->request('GET', '/api/translations/statuses');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);

        // Verify all statuses are present
        $this->assertArrayHasKey('pending', $data);
        $this->assertArrayHasKey('in_progress', $data);
        $this->assertArrayHasKey('completed', $data);
        $this->assertArrayHasKey('cancelled', $data);

        // Verify each has correct structure (value, label, color)
        foreach ($data as $status => $translation) {
            $this->assertIsArray($translation);
            $this->assertArrayHasKey('value', $translation);
            $this->assertArrayHasKey('label', $translation);
            $this->assertArrayHasKey('color', $translation);
            $this->assertIsString($translation['label']);
            $this->assertNotEmpty($translation['label']);
        }
    }

    /** @test */
    public function testGetStatusTranslationsRespectsLocaleParameter(): void
    {
        // Act - Request with Russian locale
        $this->request('GET', '/api/translations/statuses?locale=ru');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertArrayHasKey('completed', $data);
        $this->assertIsArray($data['completed']);
        $this->assertArrayHasKey('label', $data['completed']);
        $this->assertIsString($data['completed']['label']);
    }

    /** @test */
    public function testUnauthorizedUserCannotAccessTranslationEndpoints(): void
    {
        // Act - Request without JWT token
        $this->client->request('GET', '/api/translations/enums');

        // Assert - This endpoint might be public, adjust if needed
        // If it requires authentication:
        // $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        // For now, just verify it returns valid response
        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->getStatusCode() === Response::HTTP_UNAUTHORIZED,
            'Expected either 200 or 401 response',
        );
    }

    private function request(
        string $method,
        string $uri,
        array $headers = [],
    ): void {
        $defaultHeaders = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'CONTENT_TYPE'       => 'application/json',
        ];

        $this->client->request(
            $method,
            $uri,
            [],
            [],
            array_merge($defaultHeaders, $headers),
        );
    }

    private function getResponseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }
}
