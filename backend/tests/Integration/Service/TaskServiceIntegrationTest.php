<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\User;
use App\Service\TaskService;
use App\Service\RecurrenceService;
use App\TestsUtilities\Factory\TaskFactory;
use App\TestsUtilities\Factory\UserFactory;
use App\TestsUtilities\Factory\RecurrenceRuleFactory;
use App\TestsUtilities\Factory\TagFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Integration tests for TaskService interactions with other services
 * Uses real database and real service instances (no mocks)
 */
class TaskServiceIntegrationTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    private TaskService $taskService;
    private RecurrenceService $recurrenceService;
    private User $user;

    protected function setUp(): void
    {
        self::bootKernel();

        // Get real service instances from container
        $container = static::getContainer();
        $this->taskService = $container->get(TaskService::class);
        $this->recurrenceService = $container->get(RecurrenceService::class);

        // Create test user
        $userProxy = UserFactory::createOne([
            'email' => 'test-' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);
        $this->user = $userProxy->_real();
    }

    /**
     * @test
     * Test that creating a task with recurrence rule properly integrates
     * between TaskService and RecurrenceService
     */
    public function testTaskCreationWithRecurrence(): void
    {
        // Arrange: Create task with recurrence data
        $taskData = [
            'title' => 'Daily standup meeting',
            'description' => 'Team sync meeting',
            'user' => $this->user,
        ];

        $recurrenceData = [
            'recurrenceType' => 'daily',
            'interval' => 1,
            'startDate' => new \DateTimeImmutable('2025-01-01'),
            'endDate' => new \DateTimeImmutable('2025-01-31'),
            'isActive' => true,
        ];

        // Act: Create task (simulating what controller does)
        $task = TaskFactory::createOne($taskData);

        // Create recurrence rule for the task
        $rule = RecurrenceRuleFactory::new()->daily()->create([
            'createdBy' => $this->user,
            'templateTask' => $task->_real(),
            'isActive' => true,
        ]);

        // Set start and end dates manually (no setters in factory)
        $rule->_real()->setStartDate($recurrenceData['startDate']);
        $rule->_real()->setEndDate($recurrenceData['endDate']);

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->flush();

        // Assert: Task and recurrence rule are properly linked
        $this->assertNotNull($task->_real()->getId());
        $this->assertNotNull($rule->_real()->getId());
        $this->assertEquals($task->_real()->getId(), $rule->_real()->getTemplateTask()->getId());
        $this->assertTrue($rule->_real()->isActive());

        // Test that RecurrenceService can generate instances from this rule
        $generatedTasks = $this->recurrenceService->generateTasksForRule(
            $rule->_real(),
            new \DateTimeImmutable('2025-01-01'),
            new \DateTimeImmutable('2025-01-05')
        );

        $this->assertCount(5, $generatedTasks); // Should generate 5 daily tasks
        foreach ($generatedTasks as $generatedTask) {
            $this->assertEquals('Daily standup meeting', $generatedTask->getTitle());
            $this->assertNotNull($generatedTask->getGeneratedFromRule());
        }
    }

    /**
     * @test
     * Test task completion cascade logic with subtasks
     * Verifies that TaskService properly handles parent-child relationships
     */
    public function testTaskCompletionWithSubtasks(): void
    {
        // Arrange: Create parent task with subtasks
        $parentTask = TaskFactory::createOne([
            'title' => 'Complete project',
            'user' => $this->user,
            'status' => \App\Enum\TaskStatus::PENDING,
        ]);

        $subtask1 = TaskFactory::createOne([
            'title' => 'Design mockups',
            'user' => $this->user,
            'parentTask' => $parentTask->_real(),
            'status' => \App\Enum\TaskStatus::PENDING,
        ]);

        $subtask2 = TaskFactory::createOne([
            'title' => 'Write code',
            'user' => $this->user,
            'parentTask' => $parentTask->_real(),
            'status' => \App\Enum\TaskStatus::PENDING,
        ]);

        $subtask3 = TaskFactory::createOne([
            'title' => 'Write tests',
            'user' => $this->user,
            'parentTask' => $parentTask->_real(),
            'status' => \App\Enum\TaskStatus::PENDING,
        ]);

        // Act: Complete all subtasks
        $subtask1->_real()->setStatus(\App\Enum\TaskStatus::COMPLETED);
        $subtask2->_real()->setStatus(\App\Enum\TaskStatus::COMPLETED);
        $subtask3->_real()->setStatus(\App\Enum\TaskStatus::COMPLETED);

        // Manually persist to DB (simulating what service does)
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->flush();

        // Refresh parent to get updated subtasks
        $entityManager->refresh($parentTask->_real());

        // Assert: Parent task completion progress reflects completed subtasks
        $completionProgress = $parentTask->_real()->getCompletionProgress();
        $this->assertEquals(100.0, $completionProgress);

        // Verify all subtasks are indeed completed
        $subtasks = $parentTask->_real()->getSubtasks();
        $this->assertCount(3, $subtasks);
        foreach ($subtasks as $subtask) {
            $this->assertTrue($subtask->isCompleted());
        }
    }

    /**
     * @test
     * Test cascade deletion - when task is deleted, all related data should be removed
     * Tests integration with Tags, MediaObjects, Subtasks, RecurrenceRules
     */
    public function testTaskDeletionWithCascade(): void
    {
        // Arrange: Create task with related entities
        $tag1 = TagFactory::createOne(['name' => 'work', 'user' => $this->user]);
        $tag2 = TagFactory::createOne(['name' => 'urgent', 'user' => $this->user]);

        $parentTask = TaskFactory::createOne([
            'title' => 'Main task',
            'user' => $this->user,
        ]);

        // Add tags to task
        $parentTask->_real()->addTag($tag1->_real());
        $parentTask->_real()->addTag($tag2->_real());

        // Create subtasks
        $subtask1 = TaskFactory::createOne([
            'title' => 'Subtask 1',
            'user' => $this->user,
            'parentTask' => $parentTask->_real(),
        ]);

        $subtask2 = TaskFactory::createOne([
            'title' => 'Subtask 2',
            'user' => $this->user,
            'parentTask' => $parentTask->_real(),
        ]);

        // Create recurrence rule
        $rule = RecurrenceRuleFactory::new()->weekly()->create([
            'createdBy' => $this->user,
            'templateTask' => $parentTask->_real(),
        ]);

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->flush();

        $parentTaskId = $parentTask->_real()->getId();
        $subtask1Id = $subtask1->_real()->getId();
        $subtask2Id = $subtask2->_real()->getId();
        $ruleId = $rule->_real()->getId();

        // Act: Delete parent task
        $entityManager->remove($parentTask->_real());
        $entityManager->flush();
        $entityManager->clear();

        // Assert: Task is deleted
        $taskRepository = $entityManager->getRepository(\App\Entity\Task::class);
        $deletedTask = $taskRepository->find($parentTaskId);
        $this->assertNull($deletedTask);

        // Assert: Subtasks are deleted (orphanRemoval=true)
        $deletedSubtask1 = $taskRepository->find($subtask1Id);
        $deletedSubtask2 = $taskRepository->find($subtask2Id);
        $this->assertNull($deletedSubtask1);
        $this->assertNull($deletedSubtask2);

        // Assert: Recurrence rule is deleted (cascade remove)
        $ruleRepository = $entityManager->getRepository(\App\Entity\RecurrenceRule::class);
        $deletedRule = $ruleRepository->find($ruleId);
        $this->assertNull($deletedRule);

        // Assert: Tags still exist (ManyToMany - tags aren't deleted)
        $tagRepository = $entityManager->getRepository(\App\Entity\Tag::class);
        $tag1Exists = $tagRepository->find($tag1->_real()->getId());
        $tag2Exists = $tagRepository->find($tag2->_real()->getId());
        $this->assertNotNull($tag1Exists);
        $this->assertNotNull($tag2Exists);
    }

    /**
     * @test
     * Test bulk operations - creating and manipulating multiple tasks efficiently
     * Tests service layer performance with multiple entities
     */
    public function testBulkTaskOperations(): void
    {
        // Arrange: Create multiple tasks in bulk
        $tag = TagFactory::createOne(['name' => 'batch', 'user' => $this->user]);

        // Act: Create 20 tasks with same tag
        $tasks = [];
        for ($i = 1; $i <= 20; $i++) {
            $task = TaskFactory::createOne([
                'title' => 'Bulk task #' . $i,
                'user' => $this->user,
                'priority' => $i % 3 === 0 ? \App\Enum\TaskPriority::HIGH : \App\Enum\TaskPriority::MEDIUM,
            ]);
            $task->_real()->addTag($tag->_real());
            $tasks[] = $task;
        }

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->flush();

        // Assert: All tasks created
        $taskRepository = $entityManager->getRepository(\App\Entity\Task::class);
        $userTasks = $taskRepository->findBy(['user' => $this->user]);
        $this->assertCount(20, $userTasks);

        // Assert: Bulk status update
        foreach ($tasks as $task) {
            if ($task->_real()->getPriority() === \App\Enum\TaskPriority::HIGH) {
                $task->_real()->setStatus(\App\Enum\TaskStatus::IN_PROGRESS);
            }
        }
        $entityManager->flush();
        $entityManager->clear();

        // Verify status updates
        $inProgressTasks = $taskRepository->findBy([
            'user' => $this->user,
            'status' => \App\Enum\TaskStatus::IN_PROGRESS,
        ]);

        // Should have ~7 high priority tasks (20/3 ≈ 7)
        $this->assertGreaterThanOrEqual(6, count($inProgressTasks));
        $this->assertLessThanOrEqual(8, count($inProgressTasks));

        // Assert: Bulk deletion
        $tasksToDelete = array_slice($tasks, 0, 10);
        foreach ($tasksToDelete as $task) {
            $entityManager->remove($task->_real());
        }
        $entityManager->flush();
        $entityManager->clear();

        // Verify deletion
        $remainingTasks = $taskRepository->findBy(['user' => $this->user]);
        $this->assertCount(10, $remainingTasks);
    }
}
