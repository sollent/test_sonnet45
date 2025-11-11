<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\TestsUtilities\Factory\TagFactory;
use App\TestsUtilities\Factory\TaskFactory;
use App\TestsUtilities\Factory\UserFactory;
use DateTimeImmutable;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AnalyticsControllerTest extends WebTestCase
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

    // ==================== GET /api/analytics/overview ====================

    /** @test */
    public function testGetOverview(): void
    {
        // Arrange: Create diverse tasks
        TaskFactory::createMany(5, [
            'user'       => $this->user,
            'status'     => TaskStatus::PENDING,
            'isArchived' => false,
        ]);
        TaskFactory::createMany(3, [
            'user'        => $this->user,
            'status'      => TaskStatus::COMPLETED,
            'completedAt' => new DateTimeImmutable(),
            'isArchived'  => false,
        ]);

        // Act
        $this->request('GET', '/api/analytics/overview');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        // Verify response structure (actual data validation is in Service unit tests)
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    /** @test */
    public function testGetOverviewWithNoData(): void
    {
        // Act: No tasks
        $this->request('GET', '/api/analytics/overview');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    /** @test */
    public function testGetOverviewUnauthenticated(): void
    {
        // Act: Request without token
        $this->client->request('GET', '/api/analytics/overview');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // ==================== GET /api/analytics/completion-timeline ====================

    /** @test */
    public function testGetCompletionTimeline(): void
    {
        // Arrange: Create completed tasks over time
        TaskFactory::createMany(3, [
            'user'        => $this->user,
            'status'      => TaskStatus::COMPLETED,
            'completedAt' => new DateTimeImmutable('-5 days'),
        ]);
        TaskFactory::createMany(2, [
            'user'        => $this->user,
            'status'      => TaskStatus::COMPLETED,
            'completedAt' => new DateTimeImmutable('-2 days'),
        ]);

        // Act
        $this->request('GET', '/api/analytics/completion-timeline');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    /** @test */
    public function testGetCompletionTimelineWithPeriod(): void
    {
        // Arrange
        TaskFactory::createMany(5, [
            'user'        => $this->user,
            'status'      => TaskStatus::COMPLETED,
            'completedAt' => new DateTimeImmutable('-10 days'),
        ]);

        // Act: Request specific period
        $this->request('GET', '/api/analytics/completion-timeline?period=30');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    /** @test */
    public function testGetCompletionTimelineWithDateRange(): void
    {
        // Arrange
        TaskFactory::createMany(3, [
            'user'        => $this->user,
            'status'      => TaskStatus::COMPLETED,
            'completedAt' => new DateTimeImmutable('2025-11-01'),
        ]);

        // Act
        $this->request('GET', '/api/analytics/completion-timeline?dateFrom=2025-11-01&dateTo=2025-11-30');

        // Assert
        $this->assertResponseIsSuccessful();
    }

    /** @test */
    public function testGetTimelineEmpty(): void
    {
        // Act: No completed tasks
        $this->request('GET', '/api/analytics/completion-timeline');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    // ==================== GET /api/analytics/status-distribution ====================

    /** @test */
    public function testGetStatusDistribution(): void
    {
        // Arrange: Create tasks with different statuses
        TaskFactory::createMany(5, [
            'user'   => $this->user,
            'status' => TaskStatus::PENDING,
        ]);
        TaskFactory::createMany(3, [
            'user'   => $this->user,
            'status' => TaskStatus::IN_PROGRESS,
        ]);
        TaskFactory::createMany(2, [
            'user'   => $this->user,
            'status' => TaskStatus::COMPLETED,
        ]);

        // Act
        $this->request('GET', '/api/analytics/status-distribution');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    /** @test */
    public function testGetStatusDistributionEmpty(): void
    {
        // Act: No tasks
        $this->request('GET', '/api/analytics/status-distribution');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    // ==================== GET /api/analytics/priority-breakdown ====================

    /** @test */
    public function testGetPriorityBreakdown(): void
    {
        // Arrange: Create tasks with different priorities
        TaskFactory::createMany(4, [
            'user'     => $this->user,
            'priority' => TaskPriority::HIGH,
        ]);
        TaskFactory::createMany(3, [
            'user'     => $this->user,
            'priority' => TaskPriority::MEDIUM,
        ]);
        TaskFactory::createMany(2, [
            'user'     => $this->user,
            'priority' => TaskPriority::LOW,
        ]);

        // Act
        $this->request('GET', '/api/analytics/priority-breakdown');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    /** @test */
    public function testGetPriorityBreakdownEmpty(): void
    {
        // Act: No tasks
        $this->request('GET', '/api/analytics/priority-breakdown');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    // ==================== GET /api/analytics/productivity-heatmap ====================

    /** @test */
    public function testGetProductivityHeatmap(): void
    {
        // Arrange: Create completed tasks throughout the year
        for ($i = 0; $i < 10; $i++) {
            TaskFactory::createOne([
                'user'        => $this->user,
                'status'      => TaskStatus::COMPLETED,
                'completedAt' => new DateTimeImmutable("-{$i} days"),
            ]);
        }

        // Act
        $this->request('GET', '/api/analytics/productivity-heatmap');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    /** @test */
    public function testGetHeatmapForSpecificYear(): void
    {
        // Arrange
        TaskFactory::createMany(5, [
            'user'        => $this->user,
            'status'      => TaskStatus::COMPLETED,
            'completedAt' => new DateTimeImmutable('2025-06-15'),
        ]);

        // Act
        $this->request('GET', '/api/analytics/productivity-heatmap?year=2025');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    /** @test */
    public function testGetHeatmapEmpty(): void
    {
        // Act: No completed tasks
        $this->request('GET', '/api/analytics/productivity-heatmap');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    // ==================== GET /api/analytics/weekday-productivity ====================

    /** @test */
    public function testGetWeekdayProductivity(): void
    {
        // Arrange: Create completed tasks on different weekdays
        for ($i = 0; $i < 7; $i++) {
            TaskFactory::createOne([
                'user'        => $this->user,
                'status'      => TaskStatus::COMPLETED,
                'completedAt' => new DateTimeImmutable("-{$i} days"),
            ]);
        }

        // Act
        $this->request('GET', '/api/analytics/weekday-productivity');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    /** @test */
    public function testGetWeekdayProductivityEmpty(): void
    {
        // Act: No tasks
        $this->request('GET', '/api/analytics/weekday-productivity');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    // ==================== GET /api/analytics/top-tags ====================

    /** @test */
    public function testGetTopTags(): void
    {
        // Arrange: Create tags with usage
        $tag1 = TagFactory::createOne([
            'user'       => $this->user,
            'name'       => 'popular',
            'usageCount' => 100,
        ]);
        $tag2 = TagFactory::createOne([
            'user'       => $this->user,
            'name'       => 'common',
            'usageCount' => 50,
        ]);
        $tag3 = TagFactory::createOne([
            'user'       => $this->user,
            'name'       => 'rare',
            'usageCount' => 5,
        ]);

        // Act
        $this->request('GET', '/api/analytics/top-tags');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(1, count($data));

        // First tag should be most popular
        if (count($data) > 0) {
            $this->assertEquals('popular', $data[0]['name']);
        }
    }

    /** @test */
    public function testGetTopTagsWithLimit(): void
    {
        // Arrange
        TagFactory::createMany(10, ['user' => $this->user]);

        // Act
        $this->request('GET', '/api/analytics/top-tags?limit=5');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertLessThanOrEqual(5, count($data));
    }

    /** @test */
    public function testGetTopTagsEmpty(): void
    {
        // Act: No tags
        $this->request('GET', '/api/analytics/top-tags');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    // ==================== GET /api/analytics/insights ====================

    /** @test */
    public function testGetInsights(): void
    {
        // Arrange: Create data for insights
        TaskFactory::createMany(10, [
            'user'        => $this->user,
            'status'      => TaskStatus::COMPLETED,
            'completedAt' => new DateTimeImmutable('-5 days'),
        ]);
        TaskFactory::createMany(5, [
            'user'    => $this->user,
            'status'  => TaskStatus::PENDING,
            'dueDate' => new DateTimeImmutable('-2 days'), // Overdue
        ]);

        // Act
        $this->request('GET', '/api/analytics/insights');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    /** @test */
    public function testGetInsightsEmpty(): void
    {
        // Act: No data
        $this->request('GET', '/api/analytics/insights');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    // ==================== GET /api/analytics/dashboard ====================

    /** @test */
    public function testGetDashboard(): void
    {
        // Arrange: Create comprehensive data
        TaskFactory::createMany(5, [
            'user'   => $this->user,
            'status' => TaskStatus::PENDING,
        ]);
        TaskFactory::createMany(3, [
            'user'        => $this->user,
            'status'      => TaskStatus::COMPLETED,
            'completedAt' => new DateTimeImmutable(),
        ]);

        $tag = TagFactory::createOne(['user' => $this->user]);

        // Act
        $this->request('GET', '/api/analytics/dashboard');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    /** @test */
    public function testGetDashboardWithPeriod(): void
    {
        // Arrange
        TaskFactory::createMany(5, [
            'user'        => $this->user,
            'status'      => TaskStatus::COMPLETED,
            'completedAt' => new DateTimeImmutable('-10 days'),
        ]);

        // Act
        $this->request('GET', '/api/analytics/dashboard?period=30');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    /** @test */
    public function testGetDashboardWithDateRange(): void
    {
        // Arrange
        TaskFactory::createMany(3, [
            'user'        => $this->user,
            'status'      => TaskStatus::COMPLETED,
            'completedAt' => new DateTimeImmutable('2025-11-01'),
        ]);

        // Act
        $this->request('GET', '/api/analytics/dashboard?dateFrom=2025-11-01&dateTo=2025-11-30');

        // Assert
        $this->assertResponseIsSuccessful();
    }

    /** @test */
    public function testGetDashboardWithYear(): void
    {
        // Arrange
        TaskFactory::createMany(5, [
            'user'        => $this->user,
            'status'      => TaskStatus::COMPLETED,
            'completedAt' => new DateTimeImmutable('2025-06-15'),
        ]);

        // Act
        $this->request('GET', '/api/analytics/dashboard?year=2025');

        // Assert
        $this->assertResponseIsSuccessful();
        $data = $this->getResponseData();

        $this->assertIsArray($data);
    }

    /** @test */
    public function testGetDashboardUnauthenticated(): void
    {
        // Act: Request without token
        $this->client->request('GET', '/api/analytics/dashboard');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
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
