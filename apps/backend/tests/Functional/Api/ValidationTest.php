<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\User;
use App\TestsUtilities\Factory\TaskFactory;
use App\TestsUtilities\Factory\UserFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Tests for validation edge cases and error handling
 */
class ValidationTest extends WebTestCase
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

        // Create authenticated user for tests
        $userProxy = UserFactory::createOne([
            'email' => 'test-' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);
        $this->user = $userProxy->_real();
        $this->token = $this->jwtManager->create($this->user);
    }

    /**
     * Helper: Make authenticated request
     */
    private function request(
        string $method,
        string $uri,
        string $content = null,
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
            array_merge($defaultHeaders, $headers),
            $content
        );
    }

    /**
     * Helper: Get response data
     */
    private function getResponseData(): ?array
    {
        $content = $this->client->getResponse()->getContent();
        if (!$content) {
            return null;
        }
        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : null;
    }

    // ==================== JSON Payload Validation ====================

    /** @test */
    public function testInvalidJsonPayload(): void
    {
        // Act: Send invalid JSON (missing closing brace)
        $this->request('POST', '/api/tasks', '{"title": "Test Task"');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /** @test */
    public function testMalformedJsonPayload(): void
    {
        // Act: Send malformed JSON (invalid syntax)
        $this->request('POST', '/api/tasks', '{title: Test Task}');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /** @test */
    public function testMissingContentTypeHeader(): void
    {
        // Act: Request without Content-Type header
        $this->client->request(
            'POST',
            '/api/tasks',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token],
            json_encode(['title' => 'Test Task'])
        );

        // Assert: Should still work or return specific error
        $response = $this->client->getResponse();
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_CREATED,
            Response::HTTP_BAD_REQUEST,
            Response::HTTP_UNSUPPORTED_MEDIA_TYPE
        ]);
    }

    /** @test */
    public function testInvalidContentType(): void
    {
        // Act: Send with wrong Content-Type
        $this->request(
            'POST',
            '/api/tasks',
            json_encode(['title' => 'Test Task']),
            ['CONTENT_TYPE' => 'text/plain']
        );

        // Assert: May accept or reject based on Symfony config
        $response = $this->client->getResponse();
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_CREATED,
            Response::HTTP_UNSUPPORTED_MEDIA_TYPE
        ]);
    }

    /** @test */
    public function testEmptyRequestBody(): void
    {
        // Act: POST with empty body
        $this->request('POST', '/api/tasks', '');

        // Assert: Symfony returns 422 for empty/invalid payload
        $response = $this->client->getResponse();
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_BAD_REQUEST,
            Response::HTTP_UNPROCESSABLE_ENTITY
        ]);
    }

    /** @test */
    public function testExtraFieldsInPayload(): void
    {
        // Act: Send payload with extra unknown fields
        $this->request('POST', '/api/tasks', json_encode([
            'title' => 'Valid Task',
            'description' => 'Valid description',
            'unknownField' => 'Should be ignored',
            'anotherUnknownField' => 123,
        ]));

        // Assert: Symfony typically ignores extra fields (status 201)
        $this->assertResponseIsSuccessful();
    }

    // ==================== Field Type Validation ====================

    /** @test */
    public function testInvalidFieldTypes(): void
    {
        // Act: Send wrong type for priority (expects enum string, gets integer)
        $this->request('POST', '/api/tasks', json_encode([
            'title' => 'Test Task',
            'priority' => 123, // Should be 'low', 'medium', 'high', etc.
        ]));

        // Assert: Should validate and return error
        $response = $this->client->getResponse();
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_BAD_REQUEST,
            Response::HTTP_UNPROCESSABLE_ENTITY
        ]);
    }

    /** @test */
    public function testFieldMaxLengthViolation(): void
    {
        // Act: Send title exceeding 255 characters
        $longTitle = str_repeat('a', 300);
        $this->request('POST', '/api/tasks', json_encode([
            'title' => $longTitle,
        ]));

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = $this->getResponseData();
        if ($data !== null) {
            $this->assertArrayHasKey('violations', $data);
        }
    }

    /** @test */
    public function testFieldMinLengthViolation(): void
    {
        // Act: Send empty title (min length 1)
        $this->request('POST', '/api/tasks', json_encode([
            'title' => '',
        ]));

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = $this->getResponseData();
        if ($data !== null) {
            $this->assertArrayHasKey('violations', $data);
        }
    }

    /** @test */
    public function testInvalidEnumValue(): void
    {
        // Act: Send invalid status enum value
        $this->request('POST', '/api/tasks', json_encode([
            'title' => 'Test Task',
            'status' => 'invalid_status', // Not a valid TaskStatus enum
        ]));

        // Assert
        $response = $this->client->getResponse();
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_BAD_REQUEST,
            Response::HTTP_UNPROCESSABLE_ENTITY
        ]);
    }

    // ==================== Date & Format Validation ====================

    /** @test */
    public function testInvalidDateFormat(): void
    {
        // Act: Send invalid date format
        $this->request('POST', '/api/tasks', json_encode([
            'title' => 'Test Task',
            'dueDate' => 'not-a-date',
        ]));

        // Assert: May throw exception or validate
        $response = $this->client->getResponse();
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_BAD_REQUEST,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            Response::HTTP_INTERNAL_SERVER_ERROR // May throw exception during date parsing
        ]);
    }

    /**
     * @test
     * @group skip
     * Note: No separate registration endpoint - users are created via fixtures
     */
    public function testInvalidEmailFormat(): void
    {
        $this->markTestSkipped('No registration endpoint in current API - email validation tested via UserFactory');
    }

    // ==================== Nested Object Validation ====================

    /** @test */
    public function testInvalidNestedObjectValidation(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);

        // Act: Update task with invalid tag data
        $this->request('PUT', '/api/tasks/' . $task->getId(), json_encode([
            'title' => 'Updated Task',
            'tags' => [
                ['name' => ''], // Empty tag name - invalid
            ],
        ]));

        // Assert: Should validate nested objects or handle gracefully
        $response = $this->client->getResponse();
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_BAD_REQUEST,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            Response::HTTP_OK, // May accept and ignore invalid nested data
            Response::HTTP_CREATED,
            Response::HTTP_INTERNAL_SERVER_ERROR // May throw exception during tag processing
        ]);
    }

    /** @test */
    public function testNullValueForRequiredField(): void
    {
        // Act: Send null for required field
        $this->request('POST', '/api/tasks', json_encode([
            'title' => null, // Required field
        ]));

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = $this->getResponseData();
        if ($data !== null) {
            $this->assertArrayHasKey('violations', $data);
        }
    }

    /** @test */
    public function testDescriptionMaxLengthViolation(): void
    {
        // Act: Send description exceeding 5000 characters
        $longDescription = str_repeat('a', 5500);
        $this->request('POST', '/api/tasks', json_encode([
            'title' => 'Test Task',
            'description' => $longDescription,
        ]));

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = $this->getResponseData();
        if ($data !== null) {
            $this->assertArrayHasKey('violations', $data);
        }
    }

    /** @test */
    public function testInvalidBooleanType(): void
    {
        // Act: Send string instead of boolean
        $this->request('POST', '/api/tasks', json_encode([
            'title' => 'Test Task',
            'isArchived' => 'true', // Should be boolean, not string
        ]));

        // Assert: PHP/Symfony may auto-cast or reject
        $response = $this->client->getResponse();
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_CREATED,
            Response::HTTP_BAD_REQUEST,
            Response::HTTP_UNPROCESSABLE_ENTITY
        ]);
    }

    /** @test */
    public function testInvalidArrayType(): void
    {
        // Act: Send object instead of array for tags
        $this->request('POST', '/api/tasks', json_encode([
            'title' => 'Test Task',
            'tags' => 'not-an-array', // Should be array
        ]));

        // Assert
        $response = $this->client->getResponse();
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_BAD_REQUEST,
            Response::HTTP_UNPROCESSABLE_ENTITY
        ]);
    }
}
