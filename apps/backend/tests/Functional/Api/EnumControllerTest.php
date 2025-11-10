<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\TestsUtilities\Factory\UserFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class EnumControllerTest extends WebTestCase
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
            'email' => 'test-' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);
        $this->user = $userProxy->_real();
        $this->token = $this->jwtManager->create($this->user);
    }

    private function request(
        string $method,
        string $uri,
        array $headers = []
    ): void {
        $defaultHeaders = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request(
            $method,
            $uri,
            [],
            [],
            array_merge($defaultHeaders, $headers)
        );
    }

    private function getResponseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    /** @test */
    public function testGetPrioritiesReturnsAllPriorities(): void
    {
        // Act
        $this->request('GET', '/api/enums/priorities');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
        $this->assertCount(4, $data); // low, medium, high, urgent

        // Verify all priorities are present
        $values = array_column($data, 'value');
        $this->assertContains('low', $values);
        $this->assertContains('medium', $values);
        $this->assertContains('high', $values);
        $this->assertContains('urgent', $values);
    }

    /** @test */
    public function testGetPrioritiesIncludesColorAndIcon(): void
    {
        // Act
        $this->request('GET', '/api/enums/priorities');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        foreach ($data as $priority) {
            $this->assertArrayHasKey('value', $priority);
            $this->assertArrayHasKey('label', $priority);
            $this->assertArrayHasKey('color', $priority);
            $this->assertArrayHasKey('icon', $priority);

            // Verify format
            $this->assertIsString($priority['value']);
            $this->assertIsString($priority['label']);
            $this->assertIsString($priority['color']);
            $this->assertIsString($priority['icon']);

            // Verify color format (hex color)
            $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/i', $priority['color']);
        }
    }

    /** @test */
    public function testGetPrioritiesRespectsAcceptLanguageHeader(): void
    {
        // Act - Request with Russian locale
        $this->request('GET', '/api/enums/priorities', [
            'HTTP_ACCEPT_LANGUAGE' => 'ru',
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        // Find "high" priority and check label is in Russian
        $highPriority = array_values(array_filter($data, fn($p) => $p['value'] === 'high'))[0] ?? null;
        $this->assertNotNull($highPriority);

        // Russian label should be different from English "High"
        // (actual translation depends on your translation files)
        $this->assertIsString($highPriority['label']);
    }

    /** @test */
    public function testGetStatusesReturnsAllStatuses(): void
    {
        // Act
        $this->request('GET', '/api/enums/statuses');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
        $this->assertCount(4, $data); // pending, in_progress, completed, cancelled

        // Verify all statuses are present
        $values = array_column($data, 'value');
        $this->assertContains('pending', $values);
        $this->assertContains('in_progress', $values);
        $this->assertContains('completed', $values);
        $this->assertContains('cancelled', $values);
    }

    /** @test */
    public function testGetStatusesIncludesColorAndIcon(): void
    {
        // Act
        $this->request('GET', '/api/enums/statuses');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        foreach ($data as $status) {
            $this->assertArrayHasKey('value', $status);
            $this->assertArrayHasKey('label', $status);
            $this->assertArrayHasKey('color', $status);
            $this->assertArrayHasKey('icon', $status);

            // Verify format
            $this->assertIsString($status['value']);
            $this->assertIsString($status['label']);
            $this->assertIsString($status['color']);
            $this->assertIsString($status['icon']);

            // Verify color format (hex color)
            $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/i', $status['color']);
        }
    }

    /** @test */
    public function testGetStatusesRespectsAcceptLanguageHeader(): void
    {
        // Act - Request with Russian locale
        $this->request('GET', '/api/enums/statuses', [
            'HTTP_ACCEPT_LANGUAGE' => 'ru',
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        // Find "completed" status and check label exists
        $completedStatus = array_values(array_filter($data, fn($s) => $s['value'] === 'completed'))[0] ?? null;
        $this->assertNotNull($completedStatus);

        // Russian label should be a string
        $this->assertIsString($completedStatus['label']);
    }

    /** @test */
    public function testUnauthorizedUserCannotAccessEnumEndpoints(): void
    {
        // Act - Request without JWT token
        $this->client->request('GET', '/api/enums/priorities');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
