<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\AI;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Service\AI\VoiceCommandExecutor;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Integration тесты для VoiceCommandExecutor
 *
 * Тестирует полный цикл выполнения команд с реальными сервисами.
 */
class VoiceCommandExecutorTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    private VoiceCommandExecutor $executor;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->executor = $container->get(VoiceCommandExecutor::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
    }

    public function testExecuteCreateTask(): void
    {
        $user = $this->createUser();

        $command = new ParsedCommand(
            action: ParsedCommand::ACTION_CREATE_TASK,
            confidence: 0.95,
            parameters: [
                'title' => 'Integration Test Task',
                'priority' => 'high',
            ]
        );

        $result = $this->executor->execute($command, $user);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('task_created', $result->getType());

        // Проверяем что задача создана в БД
        $task = $this->entityManager->getRepository(Task::class)->findOneBy([
            'title' => 'Integration Test Task'
        ]);

        $this->assertNotNull($task);
        $this->assertSame(TaskPriority::HIGH, $task->getPriority());
        $this->assertSame($user->getId(), $task->getUser()->getId());
    }

    public function testExecuteCompleteTask(): void
    {
        $user = $this->createUser();
        $task = $this->createTask($user, 'Task to Complete');

        $command = new ParsedCommand(
            action: ParsedCommand::ACTION_COMPLETE_TASK,
            confidence: 0.9,
            parameters: ['search' => 'Task to Complete']
        );

        $result = $this->executor->execute($command, $user);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('task_completed', $result->getType());

        // Перезагружаем задачу из БД
        $this->entityManager->refresh($task);
        $this->assertSame(TaskStatus::COMPLETED, $task->getStatus());
    }

    public function testExecuteDeleteTask(): void
    {
        $user = $this->createUser();
        $task = $this->createTask($user, 'Task to Delete');
        $taskId = $task->getId();

        $command = new ParsedCommand(
            action: ParsedCommand::ACTION_DELETE_TASK,
            confidence: 0.9,
            parameters: ['search' => 'Task to Delete']
        );

        $result = $this->executor->execute($command, $user);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('task_deleted', $result->getType());

        // Проверяем что задача удалена
        $deletedTask = $this->entityManager->getRepository(Task::class)->find($taskId);
        $this->assertNull($deletedTask);
    }

    public function testExecuteUpdateTask(): void
    {
        $user = $this->createUser();
        $task = $this->createTask($user, 'Task to Update');

        $command = new ParsedCommand(
            action: ParsedCommand::ACTION_UPDATE_TASK,
            confidence: 0.9,
            parameters: [
                'search' => 'Task to Update',
                'priority' => 'urgent',
                'status' => 'in_progress',
            ]
        );

        $result = $this->executor->execute($command, $user);

        $this->assertTrue($result->isSuccess());

        $this->entityManager->refresh($task);
        $this->assertSame(TaskPriority::URGENT, $task->getPriority());
        $this->assertSame(TaskStatus::IN_PROGRESS, $task->getStatus());
    }

    public function testExecuteUnknownAction(): void
    {
        $user = $this->createUser();

        $command = new ParsedCommand(
            action: 'unknown_action',
            confidence: 0.9,
            parameters: []
        );

        $result = $this->executor->execute($command, $user);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('not found', $result->getMessage());
    }

    public function testExecuteTaskNotFound(): void
    {
        $user = $this->createUser();

        $command = new ParsedCommand(
            action: ParsedCommand::ACTION_COMPLETE_TASK,
            confidence: 0.9,
            parameters: ['search' => 'Non Existent Task']
        );

        $result = $this->executor->execute($command, $user);

        $this->assertFalse($result->isSuccess());
        $this->assertSame('task_not_found', $result->getType());
    }

    public function testExecuteWithDifferentUsers(): void
    {
        $user1 = $this->createUser('user1@test.com');
        $user2 = $this->createUser('user2@test.com');

        // Создаем задачу для user1
        $task = $this->createTask($user1, 'User1 Task');

        // user2 пытается найти задачу user1
        $command = new ParsedCommand(
            action: ParsedCommand::ACTION_COMPLETE_TASK,
            confidence: 0.9,
            parameters: ['search' => 'User1 Task']
        );

        $result = $this->executor->execute($command, $user2);

        // Задача не должна быть найдена
        $this->assertFalse($result->isSuccess());
    }

    private function createUser(string $email = 'test@example.com'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('password123');
        $user->setName('Test User');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createTask(User $user, string $title): Task
    {
        $task = new Task();
        $task->setTitle($title);
        $task->setUser($user);
        $task->setStatus(TaskStatus::PENDING);
        $task->setPriority(TaskPriority::MEDIUM);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }
}
