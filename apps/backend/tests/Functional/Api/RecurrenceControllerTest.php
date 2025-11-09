<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\RecurrenceRule;
use App\Entity\Task;
use App\Entity\User;
use App\TestsUtilities\Factory\RecurrenceRuleFactory;
use App\TestsUtilities\Factory\TaskFactory;
use App\TestsUtilities\Factory\UserFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class RecurrenceControllerTest extends WebTestCase
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

    // ==================== GET /api/recurrence (List Rules) ====================

    /** @test */
    public function testListRecurrenceRules(): void
    {
        // Arrange: Create recurrence rules for authenticated user
        // Each rule needs its own task (OneToOne relationship)
        for ($i = 0; $i < 3; $i++) {
            $task = TaskFactory::createOne(['user' => $this->user]);
            RecurrenceRuleFactory::createOne([
                'createdBy' => $this->user,
                'templateTask' => $task->_real(),
                'isActive' => true,
            ]);
        }

        // Act
        $this->request('GET', '/api/recurrence');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
        $this->assertCount(3, $data);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('recurrenceType', $data[0]);
        $this->assertArrayHasKey('previewDates', $data[0]);
    }

    /** @test */
    public function testListRecurrenceRulesEmpty(): void
    {
        // Act
        $this->request('GET', '/api/recurrence');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
        $this->assertCount(0, $data);
    }

    /** @test */
    public function testListRecurrenceRulesUnauthenticated(): void
    {
        // Act: Request without token
        $this->client->request('GET', '/api/recurrence');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /** @test */
    public function testListRecurrenceRulesOnlyUserOwned(): void
    {
        // Arrange: Create rules for different users
        $otherUser = UserFactory::createOne()->_real();

        // Create 2 rules for current user (each with own task)
        for ($i = 0; $i < 2; $i++) {
            $task = TaskFactory::createOne(['user' => $this->user]);
            RecurrenceRuleFactory::createOne([
                'createdBy' => $this->user,
                'templateTask' => $task->_real(),
            ]);
        }

        // Create 3 rules for other user (each with own task)
        for ($i = 0; $i < 3; $i++) {
            $task = TaskFactory::createOne(['user' => $otherUser]);
            RecurrenceRuleFactory::createOne([
                'createdBy' => $otherUser,
                'templateTask' => $task->_real(),
            ]);
        }

        // Act
        $this->request('GET', '/api/recurrence');

        // Assert
        $data = $this->getResponseData();
        $this->assertCount(2, $data); // Only user's rules
    }

    // ==================== GET /api/recurrence/{id} (Show Rule) ====================

    /** @test */
    public function testGetRecurrenceRule(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $rule = RecurrenceRuleFactory::createOne([
            'createdBy' => $this->user,
            'templateTask' => $task->_real(),
            'recurrenceType' => RecurrenceRule::TYPE_DAILY,
        ]);

        // Act
        $this->request('GET', '/api/recurrence/' . $rule->getId());

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertEquals($rule->getId(), $data['id']);
        $this->assertEquals('daily', $data['recurrenceType']);
        $this->assertArrayHasKey('previewDates', $data);
        $this->assertIsArray($data['previewDates']);
    }

    /** @test */
    public function testGetRecurrenceRuleWithPreviewDates(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $rule = RecurrenceRuleFactory::new()->daily()->create([
            'createdBy' => $this->user,
            'templateTask' => $task->_real(),
        ]);

        // Act
        $this->request('GET', '/api/recurrence/' . $rule->getId());

        // Assert
        $data = $this->getResponseData();
        $this->assertArrayHasKey('previewDates', $data);
        $this->assertCount(10, $data['previewDates']); // Default preview count
    }

    /** @test */
    public function testGetRecurrenceRuleNotFound(): void
    {
        // Act
        $this->request('GET', '/api/recurrence/99999');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /** @test */
    public function testGetRecurrenceRuleAccessDenied(): void
    {
        // Arrange: Create rule for other user
        $otherUser = UserFactory::createOne()->_real();
        $task = TaskFactory::createOne(['user' => $otherUser]);
        $rule = RecurrenceRuleFactory::createOne([
            'createdBy' => $otherUser,
            'templateTask' => $task->_real(),
        ]);

        // Act
        $this->request('GET', '/api/recurrence/' . $rule->getId());

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // ==================== POST /api/recurrence/task/{taskId} (Create Rule) ====================

    /** @test */
    public function testCreateDailyRecurrence(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);

        $payload = [
            'recurrenceType' => 'daily',
            'interval' => 1,
        ];

        // Act
        $this->request(
            'POST',
            '/api/recurrence/task/' . $task->getId(),
            [],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->getResponseData();

        $this->assertEquals('daily', $data['recurrenceType']);
        $this->assertArrayHasKey('previewDates', $data);
    }

    /** @test */
    public function testCreateWeeklyRecurrence(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);

        $payload = [
            'recurrenceType' => 'weekly',
            'daysOfWeek' => [1, 3, 5], // Mon, Wed, Fri
        ];

        // Act
        $this->request(
            'POST',
            '/api/recurrence/task/' . $task->getId(),
            [],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->getResponseData();

        $this->assertEquals('weekly', $data['recurrenceType']);
        $this->assertEquals([1, 3, 5], $data['daysOfWeek']);
    }

    /** @test */
    public function testCreateMonthlyRecurrence(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);

        $payload = [
            'recurrenceType' => 'monthly',
            'dayOfMonth' => 15,
        ];

        // Act
        $this->request(
            'POST',
            '/api/recurrence/task/' . $task->getId(),
            [],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->getResponseData();

        $this->assertEquals('monthly', $data['recurrenceType']);
        $this->assertEquals(15, $data['dayOfMonth']);
    }

    /** @test */
    public function testCreateYearlyRecurrence(): void
    {
        if (!function_exists('cal_days_in_month')) {
            $this->markTestSkipped('Calendar extension not available - required for yearly recurrence');
        }

        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);

        $payload = [
            'recurrenceType' => 'yearly',
            'monthOfYear' => 12,
            'dayOfMonth' => 25,
        ];

        // Act
        $this->request(
            'POST',
            '/api/recurrence/task/' . $task->getId(),
            [],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->getResponseData();

        $this->assertEquals('yearly', $data['recurrenceType']);
        $this->assertEquals(12, $data['monthOfYear']);
        $this->assertEquals(25, $data['dayOfMonth']);
    }

    /** @test */
    public function testCreateCustomRecurrence(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);

        $payload = [
            'recurrenceType' => 'custom',
            'interval' => 10,
        ];

        // Act
        $this->request(
            'POST',
            '/api/recurrence/task/' . $task->getId(),
            [],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->getResponseData();

        $this->assertEquals('custom', $data['recurrenceType']);
        $this->assertEquals(10, $data['interval']);
    }

    /** @test */
    public function testCreateRecurrenceWithEndDate(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $endDate = (new \DateTimeImmutable('+60 days'))->format('Y-m-d');

        $payload = [
            'recurrenceType' => 'daily',
            'interval' => 1,
            'endDate' => $endDate,
        ];

        // Act
        $this->request(
            'POST',
            '/api/recurrence/task/' . $task->getId(),
            [],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->getResponseData();

        $this->assertArrayHasKey('endDate', $data);
    }

    /** @test */
    public function testCreateRecurrenceWithMaxOccurrences(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);

        $payload = [
            'recurrenceType' => 'daily',
            'interval' => 1,
            'maxOccurrences' => 10,
        ];

        // Act
        $this->request(
            'POST',
            '/api/recurrence/task/' . $task->getId(),
            [],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->getResponseData();

        $this->assertEquals(10, $data['maxOccurrences']);
    }

    /** @test */
    public function testCreateRecurrenceWithTimeOfDay(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);

        $payload = [
            'recurrenceType' => 'daily',
            'interval' => 1,
            'timeOfDay' => '14:30:00',
        ];

        // Act
        $this->request(
            'POST',
            '/api/recurrence/task/' . $task->getId(),
            [],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->getResponseData();

        $this->assertArrayHasKey('timeOfDay', $data);
    }

    /** @test */
    public function testCreateRecurrenceTaskNotFound(): void
    {
        // Arrange
        $payload = [
            'recurrenceType' => 'daily',
            'interval' => 1,
        ];

        // Act
        $this->request(
            'POST',
            '/api/recurrence/task/99999',
            [],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /** @test */
    public function testCreateRecurrenceInvalidType(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);

        $payload = [
            'recurrenceType' => 'invalid_type',
            'interval' => 1,
        ];

        // Act
        $this->request(
            'POST',
            '/api/recurrence/task/' . $task->getId(),
            [],
            json_encode($payload)
        );

        // Assert: DTO validation returns 422 Unprocessable Entity
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function testCreateRecurrenceAccessDenied(): void
    {
        // Arrange: Create task for other user
        $otherUser = UserFactory::createOne()->_real();
        $task = TaskFactory::createOne(['user' => $otherUser]);

        $payload = [
            'recurrenceType' => 'daily',
            'interval' => 1,
        ];

        // Act
        $this->request(
            'POST',
            '/api/recurrence/task/' . $task->getId(),
            [],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /** @test */
    public function testCreateRecurrenceTaskAlreadyHasRule(): void
    {
        // Arrange: Task with existing recurrence rule
        $task = TaskFactory::createOne(['user' => $this->user]);
        RecurrenceRuleFactory::createOne([
            'templateTask' => $task->_real(),
            'createdBy' => $this->user,
        ]);

        $payload = [
            'recurrenceType' => 'daily',
            'interval' => 1,
        ];

        // Act
        $this->request(
            'POST',
            '/api/recurrence/task/' . $task->getId(),
            [],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = $this->getResponseData();
        $this->assertStringContainsString('already has a recurrence rule', $data['error']);
    }

    // ==================== PUT /api/recurrence/{id} (Update Rule) ====================

    /** @test */
    public function testUpdateRecurrenceRule(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $rule = RecurrenceRuleFactory::new()->daily()->create([
            'createdBy' => $this->user,
            'templateTask' => $task->_real(),
        ]);

        $payload = [
            'recurrenceType' => 'weekly',
            'daysOfWeek' => [1, 2, 3],
        ];

        // Act
        $this->request(
            'PUT',
            '/api/recurrence/' . $rule->getId(),
            [],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertEquals('weekly', $data['recurrenceType']);
        $this->assertEquals([1, 2, 3], $data['daysOfWeek']);
    }

    /** @test */
    public function testUpdateRecurrenceType(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $rule = RecurrenceRuleFactory::new()->daily()->create([
            'createdBy' => $this->user,
            'templateTask' => $task->_real(),
        ]);

        $payload = [
            'recurrenceType' => 'monthly',
            'dayOfMonth' => 1,
        ];

        // Act
        $this->request(
            'PUT',
            '/api/recurrence/' . $rule->getId(),
            [],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();
        $this->assertEquals('monthly', $data['recurrenceType']);
    }

    /** @test */
    public function testUpdateRecurrenceOptions(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $rule = RecurrenceRuleFactory::new()->daily()->create([
            'createdBy' => $this->user,
            'templateTask' => $task->_real(),
        ]);

        $payload = [
            'recurrenceType' => 'daily',
            'interval' => 3,
            'maxOccurrences' => 20,
        ];

        // Act
        $this->request(
            'PUT',
            '/api/recurrence/' . $rule->getId(),
            [],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();
        $this->assertEquals(20, $data['maxOccurrences']);
    }

    /** @test */
    public function testUpdateRecurrenceNotFound(): void
    {
        // Arrange
        $payload = [
            'recurrenceType' => 'daily',
            'interval' => 1,
        ];

        // Act
        $this->request(
            'PUT',
            '/api/recurrence/99999',
            [],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // ==================== DELETE /api/recurrence/{id} (Delete Rule) ====================

    /** @test */
    public function testDeleteRecurrenceRule(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $rule = RecurrenceRuleFactory::createOne([
            'createdBy' => $this->user,
            'templateTask' => $task->_real(),
        ]);

        // Act
        $this->request('DELETE', '/api/recurrence/' . $rule->getId());

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    /** @test */
    public function testDeleteRecurrenceRuleNotFound(): void
    {
        // Act
        $this->request('DELETE', '/api/recurrence/99999');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // ==================== GET /api/recurrence/{id}/preview (Preview Occurrences) ====================

    /** @test */
    public function testPreviewOccurrences(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $rule = RecurrenceRuleFactory::new()->daily()->create([
            'createdBy' => $this->user,
            'templateTask' => $task->_real(),
        ]);

        // Act
        $this->request('GET', '/api/recurrence/' . $rule->getId() . '/preview');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertArrayHasKey('dates', $data);
        $this->assertIsArray($data['dates']);
        $this->assertCount(5, $data['dates']); // Default count
    }

    /** @test */
    public function testPreviewWithCustomCount(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $rule = RecurrenceRuleFactory::new()->daily()->create([
            'createdBy' => $this->user,
            'templateTask' => $task->_real(),
        ]);

        // Act
        $this->request('GET', '/api/recurrence/' . $rule->getId() . '/preview?count=10');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();
        $this->assertCount(10, $data['dates']);
    }

    /** @test */
    public function testPreviewWithMaxCount(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $rule = RecurrenceRuleFactory::new()->daily()->create([
            'createdBy' => $this->user,
            'templateTask' => $task->_real(),
        ]);

        // Act: Try to get 100 previews (max is 20)
        $this->request('GET', '/api/recurrence/' . $rule->getId() . '/preview?count=100');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();
        $this->assertCount(20, $data['dates']); // Max limit
    }

    /** @test */
    public function testPreviewNotFound(): void
    {
        // Act
        $this->request('GET', '/api/recurrence/99999/preview');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // ==================== POST /api/recurrence/{id}/pause (Pause Rule) ====================

    /** @test */
    public function testPauseRecurrenceRule(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $rule = RecurrenceRuleFactory::createOne([
            'createdBy' => $this->user,
            'templateTask' => $task->_real(),
            'isActive' => true,
        ]);

        // Act
        $this->request('POST', '/api/recurrence/' . $rule->getId() . '/pause');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();
        $this->assertStringContainsString('paused', $data['message']);
    }

    /** @test */
    public function testPauseAlreadyPausedRule(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $rule = RecurrenceRuleFactory::new()->inactive()->create([
            'createdBy' => $this->user,
            'templateTask' => $task->_real(),
        ]);

        // Act
        $this->request('POST', '/api/recurrence/' . $rule->getId() . '/pause');

        // Assert: Should still return success
        $this->assertResponseIsSuccessful();
    }

    // ==================== POST /api/recurrence/{id}/resume (Resume Rule) ====================

    /** @test */
    public function testResumeRecurrenceRule(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $rule = RecurrenceRuleFactory::new()->inactive()->create([
            'createdBy' => $this->user,
            'templateTask' => $task->_real(),
        ]);

        // Act
        $this->request('POST', '/api/recurrence/' . $rule->getId() . '/resume');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();
        $this->assertStringContainsString('resumed', $data['message']);
    }

    /** @test */
    public function testResumeActiveRule(): void
    {
        // Arrange
        $task = TaskFactory::createOne(['user' => $this->user]);
        $rule = RecurrenceRuleFactory::createOne([
            'createdBy' => $this->user,
            'templateTask' => $task->_real(),
            'isActive' => true,
        ]);

        // Act
        $this->request('POST', '/api/recurrence/' . $rule->getId() . '/resume');

        // Assert: Should still return success
        $this->assertResponseIsSuccessful();
    }
}
