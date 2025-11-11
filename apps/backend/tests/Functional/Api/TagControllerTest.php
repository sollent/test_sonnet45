<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\Tag;
use App\Entity\User;
use App\TestsUtilities\Factory\TagFactory;
use App\TestsUtilities\Factory\TaskFactory;
use App\TestsUtilities\Factory\UserFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TagControllerTest extends WebTestCase
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

    // ==================== GET /api/tags ====================

    /** @test */
    public function testListTags(): void
    {
        // Arrange
        TagFactory::createMany(5, ['user' => $this->user]);

        // Act
        $this->request('GET', '/api/tags');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(5, count($data));
    }

    /** @test */
    public function testListTagsWithSearch(): void
    {
        // Arrange
        TagFactory::createOne(['user' => $this->user, 'name' => 'work-project']);
        TagFactory::createOne(['user' => $this->user, 'name' => 'personal-life']);
        TagFactory::createOne(['user' => $this->user, 'name' => 'urgent']);

        // Act
        $this->request('GET', '/api/tags?search=work');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertGreaterThanOrEqual(1, count($data));
        $this->assertStringContainsString('work', strtolower($data[0]['name']));
    }

    /** @test */
    public function testListTagsWithLimit(): void
    {
        // Arrange
        TagFactory::createMany(10, ['user' => $this->user]);

        // Act
        $this->request('GET', '/api/tags?limit=5');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertLessThanOrEqual(5, count($data));
    }

    /** @test */
    public function testListTagsEmpty(): void
    {
        // Act: No tags created
        $this->request('GET', '/api/tags');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    /** @test */
    public function testListTagsUnauthenticated(): void
    {
        // Act: Request without token
        $this->client->request('GET', '/api/tags');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // ==================== GET /api/tags/most-used ====================

    /** @test */
    public function testGetMostUsedTags(): void
    {
        // Arrange
        TagFactory::createOne([
            'user'       => $this->user,
            'name'       => 'frequently-used',
            'usageCount' => 100,
        ]);
        TagFactory::createOne([
            'user'       => $this->user,
            'name'       => 'rarely-used',
            'usageCount' => 5,
        ]);

        // Act
        $this->request('GET', '/api/tags/most-used');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(1, count($data));

        // First tag should be the most used
        if (count($data) > 0) {
            $this->assertEquals('frequently-used', $data[0]['name']);
        }
    }

    /** @test */
    public function testGetMostUsedWithLimit(): void
    {
        // Arrange
        TagFactory::createMany(10, ['user' => $this->user]);

        // Act
        $this->request('GET', '/api/tags/most-used?limit=3');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertLessThanOrEqual(3, count($data));
    }

    /** @test */
    public function testGetMostUsedEmpty(): void
    {
        // Act: No tags
        $this->request('GET', '/api/tags/most-used');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    // ==================== GET /api/tags/{id} ====================

    /** @test */
    public function testGetTagById(): void
    {
        // Arrange
        $tag = TagFactory::createOne([
            'user'  => $this->user,
            'name'  => 'test-tag',
            'color' => '#FF0000',
        ]);
        $tagId = $tag->_real()->getId();

        // Act
        $this->request('GET', "/api/tags/{$tagId}");

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertEquals($tagId, $data['id']);
        $this->assertEquals('test-tag', $data['name']);
        $this->assertEquals('#FF0000', $data['color']);
    }

    /** @test */
    public function testGetTagNotFound(): void
    {
        // Act
        $this->request('GET', '/api/tags/99999');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /** @test */
    public function testGetTagAccessDenied(): void
    {
        // Arrange: Create tag for another user
        $otherUser = UserFactory::createOne();
        $tag = TagFactory::createOne(['user' => $otherUser->_real()]);
        $tagId = $tag->_real()->getId();

        // Act
        $this->request('GET', "/api/tags/{$tagId}");

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    // ==================== POST /api/tags ====================

    /** @test */
    public function testCreateTag(): void
    {
        // Arrange
        $payload = json_encode([
            'name'  => 'New Tag',
            'color' => '#00FF00',
        ]);

        // Act
        $this->request('POST', '/api/tags', [], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->getResponseData();

        $this->assertEquals('New Tag', $data['name']);
        $this->assertEquals('#00FF00', $data['color']);
    }

    /** @test */
    public function testCreateTagWithMinimalData(): void
    {
        // Arrange
        $payload = json_encode([
            'name' => 'Minimal Tag',
        ]);

        // Act
        $this->request('POST', '/api/tags', [], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->getResponseData();

        $this->assertEquals('Minimal Tag', $data['name']);
        $this->assertNotNull($data['color']); // Should have default color
    }

    /** @test */
    public function testCreateTagWithAllFields(): void
    {
        // Arrange
        $payload = json_encode([
            'name'  => 'Complete Tag',
            'color' => '#0000FF',
        ]);

        // Act
        $this->request('POST', '/api/tags', [], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->getResponseData();

        $this->assertEquals('Complete Tag', $data['name']);
        $this->assertEquals('#0000FF', $data['color']);
    }

    /** @test */
    public function testCreateTagWithInvalidColor(): void
    {
        // Arrange: Invalid hex color
        $payload = json_encode([
            'name'  => 'Invalid Tag',
            'color' => 'not-a-color',
        ]);

        // Act
        $this->request('POST', '/api/tags', [], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /** @test */
    public function testCreateTagMissingName(): void
    {
        // Arrange: No name
        $payload = json_encode([
            'color' => '#FF0000',
        ]);

        // Act
        $this->request('POST', '/api/tags', [], $payload);

        // Assert: Validation should return 400 with error message
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('errors', $data);
        $this->assertContains('Tag name is required', $data['errors']);
    }

    /** @test */
    public function testCreateDuplicateTag(): void
    {
        // Arrange: Create existing tag
        TagFactory::createOne([
            'user' => $this->user,
            'name' => 'existing-tag',
        ]);

        $payload = json_encode([
            'name' => 'existing-tag',
        ]);

        // Act
        $this->request('POST', '/api/tags', [], $payload);

        // Assert: API prevents duplicate tag names
        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $data = $this->getResponseData();
        $this->assertStringContainsString('already exists', $data['message']);
    }

    /** @test */
    public function testCreateTagUnauthenticated(): void
    {
        // Arrange
        $payload = json_encode(['name' => 'Tag']);

        // Act: Request without token
        $this->client->request('POST', '/api/tags', [], [], ['CONTENT_TYPE' => 'application/json'], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // ==================== PUT /api/tags/{id} ====================

    /** @test */
    public function testUpdateTag(): void
    {
        // Arrange
        $tag = TagFactory::createOne([
            'user'  => $this->user,
            'name'  => 'Old Name',
            'color' => '#FF0000',
        ]);
        $tagId = $tag->_real()->getId();

        $payload = json_encode([
            'name'  => 'Updated Name',
            'color' => '#00FF00',
        ]);

        // Act
        $this->request('PUT', "/api/tags/{$tagId}", [], $payload);

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertEquals('Updated Name', $data['name']);
        $this->assertEquals('#00FF00', $data['color']);
    }

    /** @test */
    public function testUpdateTagColor(): void
    {
        // Arrange
        $tag = TagFactory::createOne([
            'user'  => $this->user,
            'name'  => 'Tag',
            'color' => '#FF0000',
        ]);
        $tagId = $tag->_real()->getId();

        $payload = json_encode(['color' => '#0000FF']);

        // Act
        $this->request('PUT', "/api/tags/{$tagId}", [], $payload);

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertEquals('#0000FF', $data['color']);
        $this->assertEquals('Tag', $data['name']); // Name unchanged
    }

    /** @test */
    public function testUpdateTagNotFound(): void
    {
        // Arrange
        $payload = json_encode(['name' => 'Updated']);

        // Act
        $this->request('PUT', '/api/tags/99999', [], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /** @test */
    public function testUpdateTagAccessDenied(): void
    {
        // Arrange
        $otherUser = UserFactory::createOne();
        $tag = TagFactory::createOne(['user' => $otherUser->_real()]);
        $tagId = $tag->_real()->getId();

        $payload = json_encode(['name' => 'Hacked']);

        // Act
        $this->request('PUT', "/api/tags/{$tagId}", [], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    // ==================== DELETE /api/tags/{id} ====================

    /** @test */
    public function testDeleteTag(): void
    {
        // Arrange
        $tag = TagFactory::createOne(['user' => $this->user]);
        $tagId = $tag->_real()->getId();

        // Act
        $this->request('DELETE', "/api/tags/{$tagId}");

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    /** @test */
    public function testDeleteTagWithTasks(): void
    {
        // Arrange: Tag associated with tasks
        $tag = TagFactory::createOne(['user' => $this->user]);
        $task = TaskFactory::createOne(['user' => $this->user]);
        $task->_real()->addTag($tag->_real());
        $task->_save();

        $tagId = $tag->_real()->getId();

        // Act
        $this->request('DELETE', "/api/tags/{$tagId}");

        // Assert: Tags are removed from tasks (no cascade delete issues)
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    /** @test */
    public function testDeleteTagNotFound(): void
    {
        // Act
        $this->request('DELETE', '/api/tags/99999');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /** @test */
    public function testDeleteTagAccessDenied(): void
    {
        // Arrange
        $otherUser = UserFactory::createOne();
        $tag = TagFactory::createOne(['user' => $otherUser->_real()]);
        $tagId = $tag->_real()->getId();

        // Act
        $this->request('DELETE', "/api/tags/{$tagId}");

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    private function request(
        string $method,
        string $uri,
        array $parameters = [],
        ?string $content = null,
    ): void {
        $this->client->request(
            $method,
            $uri,
            $parameters,
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
                'CONTENT_TYPE'       => 'application/json',
            ],
            $content,
        );
    }

    private function getResponseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }
}
