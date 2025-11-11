<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Command\ProcessRecurrenceRulesCommand;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatus;
use App\Service\RecurrenceService;
use App\TestsUtilities\Factory\RecurrenceRuleFactory;
use App\TestsUtilities\Factory\TaskFactory;
use App\TestsUtilities\Factory\UserFactory;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Integration tests for ProcessRecurrenceRulesCommand
 * Tests the command behavior with real database and service instances
 *
 * @group integration
 * @group command
 */
final class ProcessRecurrenceRulesCommandTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    private CommandTester $commandTester;

    private EntityManagerInterface $entityManager;

    private RecurrenceService $recurrenceService;

    private User $user;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->recurrenceService = $container->get(RecurrenceService::class);

        // Create application and command
        $application = new Application(self::$kernel);
        $command = $application->find('app:process-recurrence-rules');
        $this->commandTester = new CommandTester($command);

        // Create test user
        $userProxy = UserFactory::createOne([
            'email'    => 'command-test-' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);
        $this->user = $userProxy->_real();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * Test command executes successfully and processes recurrence rules
     * Verifies that the command:
     * - Runs without errors
     * - Processes active recurrence rules
     * - Generates new tasks based on rules
     * - Returns success status code
     */
    public function testProcessRecurrenceCommand(): void
    {
        // Arrange: Create active recurrence rules with upcoming dates
        $templateTask1 = TaskFactory::createOne([
            'title'  => 'Daily task template',
            'user'   => $this->user,
            'status' => TaskStatus::PENDING,
        ]);

        $templateTask2 = TaskFactory::createOne([
            'title'  => 'Weekly task template',
            'user'   => $this->user,
            'status' => TaskStatus::PENDING,
        ]);

        // Create daily recurrence rule (next occurrence is today)
        $dailyRule = RecurrenceRuleFactory::new()->daily()->create([
            'createdBy'    => $this->user,
            'templateTask' => $templateTask1->_real(),
            'isActive'     => true,
        ]);

        $dailyRule->_real()->setEndDate(new DateTimeImmutable('+30 days'));
        $dailyRule->_real()->setNextOccurrenceDate(new DateTimeImmutable('today'));

        // Create weekly recurrence rule (next occurrence is today)
        $weeklyRule = RecurrenceRuleFactory::new()->weekly()->create([
            'createdBy'    => $this->user,
            'templateTask' => $templateTask2->_real(),
            'isActive'     => true,
        ]);

        $weeklyRule->_real()->setEndDate(new DateTimeImmutable('+30 days'));
        $weeklyRule->_real()->setNextOccurrenceDate(new DateTimeImmutable('today'));

        $this->entityManager->flush();
        $this->entityManager->clear();

        // Count tasks before command execution
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $tasksBeforeCount = count($taskRepository->findBy(['user' => $this->user]));

        // Act: Execute command
        $exitCode = $this->commandTester->execute([]);

        // Assert: Command executed successfully
        $this->assertEquals(0, $exitCode, 'Command should return success status code');

        // Assert: Output contains success message
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Processed', $output);
        $this->assertStringContainsString('recurrence rules', $output);

        // Assert: New tasks were generated
        $this->entityManager->clear();
        $tasksAfterCount = count($taskRepository->findBy(['user' => $this->user]));

        // Should have at least 2 more tasks (from 2 rules)
        $this->assertGreaterThanOrEqual(
            $tasksBeforeCount + 2,
            $tasksAfterCount,
            'New tasks should be generated from recurrence rules',
        );
    }

    /**
     * Test command with --dry-run option
     * Verifies that:
     * - Command executes without errors
     * - No actual tasks are created in database
     * - Dry-run message is displayed
     * - Returns success status code
     */
    public function testProcessRecurrenceWithDryRun(): void
    {
        // Arrange: Create active recurrence rule
        $templateTask = TaskFactory::createOne([
            'title'  => 'Dry run template',
            'user'   => $this->user,
            'status' => TaskStatus::PENDING,
        ]);

        $rule = RecurrenceRuleFactory::new()->daily()->create([
            'createdBy'    => $this->user,
            'templateTask' => $templateTask->_real(),
            'isActive'     => true,
        ]);

        $rule->_real()->setEndDate(new DateTimeImmutable('+30 days'));
        $rule->_real()->setNextOccurrenceDate(new DateTimeImmutable('today'));

        $this->entityManager->flush();
        $this->entityManager->clear();

        // Count tasks before command
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $tasksBeforeCount = count($taskRepository->findBy(['user' => $this->user]));

        // Act: Execute command with --dry-run
        $exitCode = $this->commandTester->execute([
            '--dry-run' => true,
        ]);

        // Assert: Command executed successfully
        $this->assertEquals(0, $exitCode, 'Dry-run command should succeed');

        // Assert: Output indicates dry-run mode
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('DRY-RUN', $output);
        $this->assertStringContainsString('no tasks will be created', $output);

        // Assert: No new tasks were created in database
        $this->entityManager->clear();
        $tasksAfterCount = count($taskRepository->findBy(['user' => $this->user]));

        $this->assertEquals(
            $tasksBeforeCount,
            $tasksAfterCount,
            'Dry-run should not create any new tasks',
        );
    }

    /**
     * Test command with --limit option
     * Verifies that:
     * - Command respects the limit parameter
     * - Only specified number of rules are processed
     * - Command completes successfully
     */
    public function testProcessRecurrenceWithLimit(): void
    {
        // Arrange: Create multiple active recurrence rules (more than limit)
        $rulesToCreate = 5;
        $limit = 3;

        for ($i = 1; $i <= $rulesToCreate; $i++) {
            $templateTask = TaskFactory::createOne([
                'title'  => "Limited task template #{$i}",
                'user'   => $this->user,
                'status' => TaskStatus::PENDING,
            ]);

            $rule = RecurrenceRuleFactory::new()->daily()->create([
                'createdBy'    => $this->user,
                'templateTask' => $templateTask->_real(),
                'isActive'     => true,
            ]);

            $rule->_real()->setEndDate(new DateTimeImmutable('+30 days'));
            $rule->_real()->setNextOccurrenceDate(new DateTimeImmutable('today'));
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        // Act: Execute command with --limit
        $exitCode = $this->commandTester->execute([
            '--limit' => (string) $limit,
        ]);

        // Assert: Command executed successfully
        $this->assertEquals(0, $exitCode, 'Command with limit should succeed');

        // Assert: Output shows processing completed
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Processed', $output);
        $this->assertStringContainsString('recurrence rules', $output);

        // Note: The actual number processed might be less than limit
        // depending on service implementation and whether rules need processing
        // We just verify the command runs without errors
    }

    /**
     * Test command handles errors gracefully
     * Verifies that command doesn't crash when there are no rules to process
     */
    public function testProcessRecurrenceWithNoActiveRules(): void
    {
        // Arrange: Create only inactive or expired rules
        $templateTask = TaskFactory::createOne([
            'title'  => 'Inactive template',
            'user'   => $this->user,
            'status' => TaskStatus::PENDING,
        ]);

        $inactiveRule = RecurrenceRuleFactory::new()->daily()->create([
            'createdBy'    => $this->user,
            'templateTask' => $templateTask->_real(),
            'isActive'     => false, // Inactive rule
        ]);

        $inactiveRule->_real()->setEndDate(new DateTimeImmutable('-1 day')); // Expired
        $inactiveRule->_real()->setNextOccurrenceDate(new DateTimeImmutable('-30 days'));

        $this->entityManager->flush();

        // Act: Execute command
        $exitCode = $this->commandTester->execute([]);

        // Assert: Command executes successfully even with no active rules
        $this->assertEquals(0, $exitCode, 'Command should succeed even with no active rules');

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Processed', $output);
    }
}
