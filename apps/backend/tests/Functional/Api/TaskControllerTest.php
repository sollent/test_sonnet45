<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\TestsUtilities\Factory\TaskFactory;
use App\TestsUtilities\Factory\TagFactory;
use App\TestsUtilities\Factory\UserFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TaskControllerTest extends WebTestCase
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

        // Create authenticated user for tests with unique email
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
        array $parameters = [],
        string $content = null
    ): void {
        $this->client->request(
            $method,
            $uri,
            $parameters,
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
                'CONTENT_TYPE' => 'application/json',
            ],
            $content
        );
    }

    /**
     * Helper: Get response data
     */
    private function getResponseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    // ==================== GET /api/tasks (List Tasks) ====================

    /** @test */
    public function testListTasksAuthenticated(): void
    {
        // Arrange: Create active (pending) tasks for authenticated user
        // Active tasks must have dueDate or startDate >= today
        TaskFactory::createMany(5, [
            'user' => $this->user,
            'status' => TaskStatus::PENDING,
            'isArchived' => false,
            'dueDate' => new \DateTimeImmutable('+1 day'), // Ensure tasks are "active"
        ]);

        // Act
        $this->request('GET', '/api/tasks');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
        $this->assertCount(5, $data);
    }

    /** @test */
    public function testListTasksUnauthenticated(): void
    {
        // Act: Request without token
        $this->client->request('GET', '/api/tasks');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /** @test */
    public function testListTasksWithFilters(): void
    {
        // Arrange: Create different tasks
        TaskFactory::createMany(3, [
            'user' => $this->user,
            'status' => TaskStatus::PENDING,
            'dueDate' => new \DateTimeImmutable('+1 day'), // Ensure tasks are "active"
        ]);
        TaskFactory::createMany(2, [
            'user' => $this->user,
            'status' => TaskStatus::COMPLETED,
            'dueDate' => new \DateTimeImmutable('+1 day'),
        ]);

        // Act: Filter by completed
        $this->request('GET', '/api/tasks?completed=false');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();
        $this->assertCount(3, $data);
    }

    /** @test */
    public function testFilterByView(): void
    {
        // Arrange: Create overdue task
        TaskFactory::createOne([
            'user' => $this->user,
            'dueDate' => new \DateTimeImmutable('-2 days'),
            'status' => TaskStatus::PENDING,
        ]);

        // Act
        $this->request('GET', '/api/tasks?view=overdue');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();
        $this->assertGreaterThanOrEqual(1, count($data));
    }

    /** @test */
    public function testFilterBySearch(): void
    {
        // Arrange
        TaskFactory::createOne([
            'user' => $this->user,
            'title' => 'Important Meeting',
            'dueDate' => new \DateTimeImmutable('+1 day'),
        ]);
        TaskFactory::createOne([
            'user' => $this->user,
            'title' => 'Buy groceries',
            'dueDate' => new \DateTimeImmutable('+1 day'),
        ]);

        // Act
        $this->request('GET', '/api/tasks?search=Meeting');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Meeting', $data[0]['title']);
    }

    /** @test */
    public function testFilterByTags(): void
    {
        // Arrange
        $tag1 = TagFactory::createOne(['user' => $this->user, 'name' => 'work']);
        $tag2 = TagFactory::createOne(['user' => $this->user, 'name' => 'personal']);

        $task1 = TaskFactory::createOne([
            'user' => $this->user,
            'status' => TaskStatus::PENDING, // Prevent random CANCELLED status
            'dueDate' => new \DateTimeImmutable('+1 day'),
        ]);
        $task1->_real()->addTag($tag1->_real());
        $task1->_save();

        $task2 = TaskFactory::createOne([
            'user' => $this->user,
            'status' => TaskStatus::PENDING, // Prevent random CANCELLED status
            'dueDate' => new \DateTimeImmutable('+1 day'),
        ]);
        $task2->_real()->addTag($tag2->_real());
        $task2->_save();

        // Act: Filter by work tag
        $tagId = $tag1->_real()->getId();
        $this->request('GET', "/api/tasks?tags[]={$tagId}");

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();
        $this->assertGreaterThanOrEqual(1, count($data));
    }

    /** @test */
    public function testFilterByPriorities(): void
    {
        // Arrange
        TaskFactory::createMany(2, [
            'user' => $this->user,
            'priority' => TaskPriority::HIGH,
            'status' => TaskStatus::PENDING, // Prevent random CANCELLED status
            'dueDate' => new \DateTimeImmutable('+1 day'),
        ]);
        TaskFactory::createOne([
            'user' => $this->user,
            'priority' => TaskPriority::LOW,
            'status' => TaskStatus::PENDING,
            'dueDate' => new \DateTimeImmutable('+1 day'),
        ]);

        // Act
        $this->request('GET', '/api/tasks?priorities[]=high');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();
        $this->assertGreaterThanOrEqual(2, count($data));
    }

    /** @test */
    public function testFilterByStatuses(): void
    {
        // Arrange
        TaskFactory::createMany(2, [
            'user' => $this->user,
            'status' => TaskStatus::PENDING,
            'dueDate' => new \DateTimeImmutable('+1 day'),
        ]);
        TaskFactory::createOne([
            'user' => $this->user,
            'status' => TaskStatus::IN_PROGRESS,
            'dueDate' => new \DateTimeImmutable('+1 day'),
        ]);

        // Act
        $this->request('GET', '/api/tasks?statuses[]=pending');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();
        $this->assertGreaterThanOrEqual(2, count($data));
    }

    /** @test */
    public function testPaginationWithLimitAndOffset(): void
    {
        // Arrange
        TaskFactory::createMany(10, [
            'user' => $this->user,
            'dueDate' => new \DateTimeImmutable('+1 day'),
        ]);

        // Act
        $this->request('GET', '/api/tasks?limit=5&offset=0');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();
        $this->assertCount(5, $data);
    }

    /** @test */
    public function testEmptyResults(): void
    {
        // Act: No tasks created
        $this->request('GET', '/api/tasks');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();
        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    // ==================== GET /api/tasks/{id} ====================

    /** @test */
    public function testGetTaskById(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user, 'title' => 'Test Task']);
        $taskId = $task->_real()->getId();

        // Act
        $this->request('GET', "/api/tasks/{$taskId}");

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertEquals($taskId, $data['id']);
        $this->assertEquals('Test Task', $data['title']);
    }

    /** @test */
    public function testGetTaskNotFound(): void
    {
        // Act
        $this->request('GET', '/api/tasks/99999');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /** @test */
    public function testGetTaskAccessDenied(): void
    {
        // Arrange: Create task for another user
        $otherUser = UserFactory::createOne();
        $task = TaskFactory::createOne(['user' => $otherUser->_real()]);
        $taskId = $task->_real()->getId();

        // Act
        $this->request('GET', "/api/tasks/{$taskId}");

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function testGetTaskWithSubtasks(): void
    {
        // Arrange
        $parentTask = TaskFactory::createOne(['user' => $this->user]);
        TaskFactory::createMany(2, [
            'user' => $this->user,
            'parentTask' => $parentTask->_real(),
        ]);

        $taskId = $parentTask->_real()->getId();

        // Act
        $this->request('GET', "/api/tasks/{$taskId}");

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertArrayHasKey('subtasks', $data);
        $this->assertCount(2, $data['subtasks']);
    }

    /** @test */
    public function testGetTaskWithTags(): void
    {
        // Arrange
        $tag = TagFactory::createOne(['user' => $this->user, 'name' => 'urgent']);
        $task = TaskFactory::createOne(['user' => $this->user]);
        $task->_real()->addTag($tag->_real());
        $task->_save();

        $taskId = $task->_real()->getId();

        // Act
        $this->request('GET', "/api/tasks/{$taskId}");

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertArrayHasKey('tags', $data);
        $this->assertGreaterThanOrEqual(1, count($data['tags']));
    }

    // ==================== POST /api/tasks (Create Task) ====================

    /** @test */
    public function testCreateTaskSuccessfully(): void
    {
        // Arrange
        $payload = json_encode([
            'title' => 'New Task',
            'description' => 'Task description',
            'priority' => 'medium',
            'status' => 'pending',
        ]);

        // Act
        $this->request('POST', '/api/tasks', [], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->getResponseData();

        $this->assertEquals('New Task', $data['title']);
        $this->assertEquals('Task description', $data['description']);
    }

    /** @test */
    public function testCreateTaskWithMinimalData(): void
    {
        // Arrange
        $payload = json_encode([
            'title' => 'Minimal Task',
        ]);

        // Act
        $this->request('POST', '/api/tasks', [], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->getResponseData();

        $this->assertEquals('Minimal Task', $data['title']);
        $this->assertEquals('pending', $data['status']);
        $this->assertEquals('medium', $data['priority']);
    }

    /** @test */
    public function testCreateTaskWithAllFields(): void
    {
        // Arrange
        $tag = TagFactory::createOne(['user' => $this->user]);

        $payload = json_encode([
            'title' => 'Complete Task',
            'description' => 'Full description',
            'priority' => 'high',
            'status' => 'pending',
            'dueDate' => '2025-12-31T23:59:59+00:00',
            'tagIds' => [$tag->_real()->getId()],
        ]);

        // Act
        $this->request('POST', '/api/tasks', [], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->getResponseData();

        $this->assertEquals('Complete Task', $data['title']);
        $this->assertEquals('high', $data['priority']);
        $this->assertNotNull($data['dueDate']);
    }

    /** @test */
    public function testCreateTaskWithTags(): void
    {
        // Arrange
        $tag1 = TagFactory::createOne(['user' => $this->user, 'name' => 'work']);
        $tag2 = TagFactory::createOne(['user' => $this->user, 'name' => 'urgent']);

        $payload = json_encode([
            'title' => 'Tagged Task',
            'tags' => ['work', 'urgent'], // Use tag names, not IDs
        ]);

        // Act
        $this->request('POST', '/api/tasks', [], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->getResponseData();

        $this->assertArrayHasKey('tags', $data);
        $this->assertCount(2, $data['tags']);
    }

    /** @test */
    public function testCreateSubtask(): void
    {
        // Arrange
        $parentTask = TaskFactory::createOne(['user' => $this->user]);

        $payload = json_encode([
            'title' => 'Subtask',
            'parentTaskId' => $parentTask->_real()->getId(),
        ]);

        // Act
        $this->request('POST', '/api/tasks', [], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->getResponseData();

        $this->assertEquals('Subtask', $data['title']);
        $this->assertEquals($parentTask->_real()->getId(), $data['parentTaskId']);
    }

    /** @test */
    public function testCreateTaskWithInvalidData(): void
    {
        // Arrange: Invalid priority
        $payload = json_encode([
            'title' => 'Invalid Task',
            'priority' => 'invalid_priority',
        ]);

        // Act
        $this->request('POST', '/api/tasks', [], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function testCreateTaskMissingRequiredFields(): void
    {
        // Arrange: No title
        $payload = json_encode([
            'description' => 'No title provided',
        ]);

        // Act
        $this->request('POST', '/api/tasks', [], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function testCreateTaskUnauthenticated(): void
    {
        // Arrange
        $payload = json_encode(['title' => 'Task']);

        // Act: Request without token
        $this->client->request('POST', '/api/tasks', [], [], ['CONTENT_TYPE' => 'application/json'], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // ==================== PUT /api/tasks/{id} (Update Task) ====================

    /** @test */
    public function testUpdateTaskSuccessfully(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user, 'title' => 'Old Title']);
        $taskId = $task->_real()->getId();

        $payload = json_encode(['title' => 'Updated Title']);

        // Act
        $this->request('PUT', "/api/tasks/{$taskId}", [], $payload);

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertEquals('Updated Title', $data['title']);
    }

    /** @test */
    public function testUpdateTaskTitle(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $taskId = $task->_real()->getId();

        $payload = json_encode(['title' => 'New Title']);

        // Act
        $this->request('PATCH', "/api/tasks/{$taskId}", [], $payload);

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertEquals('New Title', $data['title']);
    }

    /** @test */
    public function testUpdateTaskStatus(): void
    {
        // Arrange
        $task = TaskFactory::createOne([
            'user' => $this->user,
            'status' => TaskStatus::PENDING,
        ]);
        $taskId = $task->_real()->getId();

        $payload = json_encode(['status' => 'in_progress']);

        // Act
        $this->request('PUT', "/api/tasks/{$taskId}", [], $payload);

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertEquals('in_progress', $data['status']);
    }

    /** @test */
    public function testUpdateTaskPriority(): void
    {
        // Arrange
        $task = TaskFactory::createOne([
            'user' => $this->user,
            'priority' => TaskPriority::MEDIUM,
        ]);
        $taskId = $task->_real()->getId();

        $payload = json_encode(['priority' => 'urgent']);

        // Act
        $this->request('PUT', "/api/tasks/{$taskId}", [], $payload);

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertEquals('urgent', $data['priority']);
    }

    /** @test */
    public function testUpdateTaskDates(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $taskId = $task->_real()->getId();

        $payload = json_encode([
            'dueDate' => '2025-12-31T23:59:59+00:00',
            'startDate' => '2025-12-01T00:00:00+00:00',
        ]);

        // Act
        $this->request('PUT', "/api/tasks/{$taskId}", [], $payload);

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertNotNull($data['dueDate']);
        $this->assertNotNull($data['startDate']);
    }

    /** @test */
    public function testUpdateTaskTags(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $tag = TagFactory::createOne(['user' => $this->user, 'name' => 'important']);
        $taskId = $task->_real()->getId();

        $payload = json_encode(['tags' => ['important']]); // Use tag names, not IDs

        // Act
        $this->request('PUT', "/api/tasks/{$taskId}", [], $payload);

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertArrayHasKey('tags', $data);
        $this->assertCount(1, $data['tags']);
    }

    /** @test */
    public function testUpdateTaskNotFound(): void
    {
        // Arrange
        $payload = json_encode(['title' => 'Updated']);

        // Act
        $this->request('PUT', '/api/tasks/99999', [], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /** @test */
    public function testUpdateTaskAccessDenied(): void
    {
        // Arrange
        $otherUser = UserFactory::createOne();
        $task = TaskFactory::createOne(['user' => $otherUser->_real()]);
        $taskId = $task->_real()->getId();

        $payload = json_encode(['title' => 'Hacked']);

        // Act
        $this->request('PUT', "/api/tasks/{$taskId}", [], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function testUpdateWithInvalidData(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $taskId = $task->_real()->getId();

        $payload = json_encode(['status' => 'invalid_status']);

        // Act
        $this->request('PUT', "/api/tasks/{$taskId}", [], $payload);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // ==================== DELETE /api/tasks/{id} ====================

    /** @test */
    public function testDeleteTask(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $taskId = $task->_real()->getId();

        // Act
        $this->request('DELETE', "/api/tasks/{$taskId}");

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    /** @test */
    public function testDeleteTaskWithSubtasks(): void
    {
        // Arrange
        $parentTask = TaskFactory::createOne(['user' => $this->user]);
        TaskFactory::createMany(2, [
            'user' => $this->user,
            'parentTask' => $parentTask->_real(),
        ]);

        $taskId = $parentTask->_real()->getId();

        // Act
        $this->request('DELETE', "/api/tasks/{$taskId}");

        // Assert: Should delete parent and cascade to subtasks
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    /** @test */
    public function testDeleteTaskNotFound(): void
    {
        // Act
        $this->request('DELETE', '/api/tasks/99999');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /** @test */
    public function testDeleteTaskAccessDenied(): void
    {
        // Arrange
        $otherUser = UserFactory::createOne();
        $task = TaskFactory::createOne(['user' => $otherUser->_real()]);
        $taskId = $task->_real()->getId();

        // Act
        $this->request('DELETE', "/api/tasks/{$taskId}");

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    // ==================== POST /api/tasks/{id}/complete ====================

    /** @test */
    public function testCompleteTask(): void
    {
        // Arrange
        $task = TaskFactory::createOne([
            'user' => $this->user,
            'status' => TaskStatus::PENDING,
        ]);
        $taskId = $task->_real()->getId();

        // Act
        $this->request('POST', "/api/tasks/{$taskId}/complete");

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertEquals('completed', $data['status']);
        $this->assertNotNull($data['completedAt']);
    }

    /** @test */
    public function testCompleteAlreadyCompletedTask(): void
    {
        // Arrange
        $task = TaskFactory::createOne([
            'user' => $this->user,
            'status' => TaskStatus::COMPLETED,
            'completedAt' => new \DateTimeImmutable(),
        ]);
        $taskId = $task->_real()->getId();

        // Act
        $this->request('POST', "/api/tasks/{$taskId}/complete");

        // Assert: Should still return success (idempotent)
        $this->assertResponseIsSuccessful();
    }

    /** @test */
    public function testCompleteTaskNotFound(): void
    {
        // Act
        $this->request('POST', '/api/tasks/99999/complete');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /** @test */
    public function testCompleteTaskAccessDenied(): void
    {
        // Arrange
        $otherUser = UserFactory::createOne();
        $task = TaskFactory::createOne(['user' => $otherUser->_real()]);
        $taskId = $task->_real()->getId();

        // Act
        $this->request('POST', "/api/tasks/{$taskId}/complete");

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    // ==================== POST /api/tasks/{id}/toggle ====================

    /** @test */
    public function testToggleTaskCompletion(): void
    {
        // Arrange
        $task = TaskFactory::createOne([
            'user' => $this->user,
            'status' => TaskStatus::PENDING,
        ]);
        $taskId = $task->_real()->getId();

        // Act: Toggle to completed
        $this->request('POST', "/api/tasks/{$taskId}/toggle");

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();
        $this->assertEquals('completed', $data['status']);
    }

    /** @test */
    public function testToggleFromPendingToCompleted(): void
    {
        // Arrange
        $task = TaskFactory::createOne([
            'user' => $this->user,
            'status' => TaskStatus::PENDING,
        ]);
        $taskId = $task->_real()->getId();

        // Act
        $this->request('POST', "/api/tasks/{$taskId}/toggle");

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertEquals('completed', $data['status']);
        $this->assertNotNull($data['completedAt']);
    }

    /** @test */
    public function testToggleFromCompletedToPending(): void
    {
        // Arrange
        $task = TaskFactory::createOne([
            'user' => $this->user,
            'status' => TaskStatus::COMPLETED,
            'completedAt' => new \DateTimeImmutable(),
        ]);
        $taskId = $task->_real()->getId();

        // Act
        $this->request('POST', "/api/tasks/{$taskId}/toggle");

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertEquals('pending', $data['status']);
        $this->assertNull($data['completedAt']);
    }

    // ==================== POST /api/tasks/{id}/archive ====================

    /** @test */
    public function testArchiveTask(): void
    {
        // Arrange
        $task = TaskFactory::createOne([
            'user' => $this->user,
            'isArchived' => false,
        ]);
        $taskId = $task->_real()->getId();

        // Act
        $this->request('POST', "/api/tasks/{$taskId}/archive");

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertTrue($data['isArchived']);
    }

    /** @test */
    public function testArchiveAlreadyArchivedTask(): void
    {
        // Arrange
        $task = TaskFactory::createOne([
            'user' => $this->user,
            'isArchived' => true,
        ]);
        $taskId = $task->_real()->getId();

        // Act
        $this->request('POST', "/api/tasks/{$taskId}/archive");

        // Assert: Should still succeed (idempotent)
        $this->assertResponseIsSuccessful();
    }

    // ==================== POST /api/tasks/{id}/unarchive ====================

    /** @test */
    public function testUnarchiveTask(): void
    {
        // Arrange
        $task = TaskFactory::createOne([
            'user' => $this->user,
            'isArchived' => true,
        ]);
        $taskId = $task->_real()->getId();

        // Act
        $this->request('POST', "/api/tasks/{$taskId}/unarchive");

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertFalse($data['isArchived']);
    }

    /** @test */
    public function testUnarchiveNonArchivedTask(): void
    {
        // Arrange
        $task = TaskFactory::createOne([
            'user' => $this->user,
            'isArchived' => false,
        ]);
        $taskId = $task->_real()->getId();

        // Act
        $this->request('POST', "/api/tasks/{$taskId}/unarchive");

        // Assert: Should still succeed (idempotent)
        $this->assertResponseIsSuccessful();
    }

    // ==================== GET /api/tasks/overdue ====================

    /** @test */
    public function testGetOverdueTasks(): void
    {
        // Arrange
        TaskFactory::createMany(2, [
            'user' => $this->user,
            'dueDate' => new \DateTimeImmutable('-3 days'),
            'status' => TaskStatus::PENDING,
        ]);
        TaskFactory::createOne([
            'user' => $this->user,
            'dueDate' => new \DateTimeImmutable('+3 days'),
            'status' => TaskStatus::PENDING,
        ]);

        // Act
        $this->request('GET', '/api/tasks/overdue');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertGreaterThanOrEqual(2, count($data));
    }

    /** @test */
    public function testGetOverdueTasksEmpty(): void
    {
        // Act: No overdue tasks
        $this->request('GET', '/api/tasks/overdue');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    /** @test */
    public function testGetOverdueTasksPagination(): void
    {
        // Arrange
        TaskFactory::createMany(10, [
            'user' => $this->user,
            'dueDate' => new \DateTimeImmutable('-1 day'),
            'status' => TaskStatus::PENDING,
        ]);

        // Act
        $this->request('GET', '/api/tasks/overdue?limit=5');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertLessThanOrEqual(5, count($data));
    }

    // ==================== GET /api/tasks/unscheduled ====================

    /** @test */
    public function testGetUnscheduledTasks(): void
    {
        // Arrange
        TaskFactory::createMany(3, [
            'user' => $this->user,
            'dueDate' => null,
            'startDate' => null,
            'status' => TaskStatus::PENDING, // Prevent random CANCELLED status
            'isArchived' => false, // Explicitly set to prevent any issues
            'parentTask' => null, // Ensure these are top-level tasks
        ]);
        TaskFactory::createOne([
            'user' => $this->user,
            'dueDate' => new \DateTimeImmutable('+1 day'),
            'status' => TaskStatus::PENDING,
            'isArchived' => false,
        ]);

        // Act
        $this->request('GET', '/api/tasks/unscheduled');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertArrayHasKey('tasks', $data);
        $this->assertGreaterThanOrEqual(3, count($data['tasks'])); // Count tasks, not response keys
    }

    /** @test */
    public function testGetUnscheduledTasksEmpty(): void
    {
        // Act: No unscheduled tasks
        $this->request('GET', '/api/tasks/unscheduled');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    // ==================== GET /api/tasks/statistics ====================

    /** @test */
    public function testGetTaskStatistics(): void
    {
        // Arrange
        TaskFactory::createMany(5, ['user' => $this->user, 'status' => TaskStatus::PENDING]);
        TaskFactory::createMany(3, ['user' => $this->user, 'status' => TaskStatus::COMPLETED]);

        // Act
        $this->request('GET', '/api/tasks/statistics');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('completed', $data);
        $this->assertArrayHasKey('pending', $data);
    }

    /** @test */
    public function testGetStatisticsWithNoTasks(): void
    {
        // Act
        $this->request('GET', '/api/tasks/statistics');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertEquals(0, $data['total']);
    }

    // ==================== POST /api/tasks/reorder ====================

    /** @test */
    public function testReorderTasks(): void
    {
        // Arrange
        $task1 = TaskFactory::createOne(['user' => $this->user, 'sortOrder' => 0]);
        $task2 = TaskFactory::createOne(['user' => $this->user, 'sortOrder' => 1]);
        $task3 = TaskFactory::createOne(['user' => $this->user, 'sortOrder' => 2]);

        $payload = json_encode([
            'taskIds' => [
                $task3->_real()->getId(),
                $task1->_real()->getId(),
                $task2->_real()->getId(),
            ],
        ]);

        // Act
        $this->request('POST', '/api/tasks/reorder', [], $payload);

        // Assert
        $this->assertResponseIsSuccessful();
    }

    /** @test */
    public function testReorderWithInvalidIds(): void
    {
        // Arrange
        $payload = json_encode(['taskIds' => [99999, 99998]]);

        // Act
        $this->request('POST', '/api/tasks/reorder', [], $payload);

        // Assert: Should handle gracefully
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // ==================== Calendar Endpoints ====================

    /** @test */
    public function testGetCalendarMonth(): void
    {
        // Arrange
        $date = new \DateTimeImmutable('2025-12-15');
        TaskFactory::createMany(5, [
            'user' => $this->user,
            'dueDate' => $date,
        ]);

        // Act
        $this->request('GET', '/api/tasks/calendar/month?year=2025&month=12');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    /** @test */
    public function testGetCalendarWeek(): void
    {
        // Act
        $this->request('GET', '/api/tasks/calendar/week?year=2025&week=50');

        // Assert
        $this->assertResponseIsSuccessful();
    }

    /** @test */
    public function testGetCalendarDay(): void
    {
        // Arrange
        $date = new \DateTimeImmutable('2025-12-15');
        TaskFactory::createMany(3, [
            'user' => $this->user,
            'dueDate' => $date,
        ]);

        // Act
        $this->request('GET', '/api/tasks/calendar/day?date=2025-12-15');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    /** @test */
    public function testCalendarWithIncludeCompleted(): void
    {
        // Arrange
        $date = new \DateTimeImmutable('2025-12-15');
        TaskFactory::createOne([
            'user' => $this->user,
            'dueDate' => $date,
            'status' => TaskStatus::COMPLETED,
        ]);

        // Act
        $this->request('GET', '/api/tasks/calendar/day?date=2025-12-15&includeCompleted=true');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertGreaterThanOrEqual(1, count($data));
    }

    /** @test */
    public function testCalendarWithInvalidDate(): void
    {
        // Act
        $this->request('GET', '/api/tasks/calendar/day?date=invalid-date');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
