<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\RecurrenceRule;
use App\Entity\Tag;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:e2e:seed',
    description: 'Seeds the database with test data for E2E tests',
)]
final class E2ESeedCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🌱 Seeding E2E Test Data');

        // 1. Create test user
        $user = $this->createTestUser();
        $io->success('✅ Test user created: ' . $user->getEmail());

        // 2. Create tags
        $tags = $this->createTags($user);
        $io->success('✅ Created ' . count($tags) . ' tags');

        // 3. Create tasks (including subtasks)
        $tasks = $this->createTasks($user, $tags);
        $io->success('✅ Created ' . count($tasks) . ' tasks');

        // 4. Create recurrence rules
        $recurrences = $this->createRecurrenceRules($user);
        $io->success('✅ Created ' . count($recurrences) . ' recurrence rules');

        $this->entityManager->flush();

        $io->success('🎉 E2E test data seeded successfully!');
        $io->newLine();
        $io->text('📊 Summary:');
        $io->listing([
            '👤 1 test user (e2e-test@example.com)',
            '📝 ' . count($tasks) . ' tasks (various statuses, priorities, dates)',
            '🔁 ' . count($recurrences) . ' recurrence rules (daily, weekly, monthly, yearly)',
            '🏷️  ' . count($tags) . ' tags',
            '🌲 1 parent task + 1 subtask',
        ]);

        return Command::SUCCESS;
    }

    private function createTestUser(): User
    {
        // Check if user already exists
        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'e2e-test@example.com']);

        if ($existingUser) {
            // Update password to ensure it's correct
            $existingUser->setPassword(
                $this->passwordHasher->hashPassword($existingUser, 'TestPassword123!'),
            );

            return $existingUser;
        }

        $user = new User();
        $user->setEmail('e2e-test@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'TestPassword123!'));

        $this->entityManager->persist($user);

        return $user;
    }

    private function createTags(User $user): array
    {
        $tagData = [
            ['name' => 'Work', 'color' => '#FF5733'],
            ['name' => 'Personal', 'color' => '#33FF57'],
            ['name' => 'Urgent', 'color' => '#FF3333'],
            ['name' => 'Project', 'color' => '#3357FF'],
            ['name' => 'Home', 'color' => '#F3FF33'],
        ];

        $tags = [];

        foreach ($tagData as $data) {
            // Check if tag already exists
            $existingTag = $this->entityManager
                ->getRepository(Tag::class)
                ->findOneBy(['name' => $data['name'], 'user' => $user]);

            if ($existingTag) {
                $tags[$data['name']] = $existingTag;
                continue;
            }

            $tag = new Tag();
            $tag->setName($data['name']);
            $tag->setColor($data['color']);
            $tag->setUser($user);

            $this->entityManager->persist($tag);
            $tags[$data['name']] = $tag;
        }

        return $tags;
    }

    private function createTasks(User $user, array $tags): array
    {
        $now = new DateTimeImmutable();

        $tasksData = [
            ['title' => 'Task Today 1', 'status' => TaskStatus::PENDING, 'priority' => TaskPriority::MEDIUM, 'dueDate' => $now, 'tags' => ['Work', 'Urgent']],
            ['title' => 'Task Today 2', 'status' => TaskStatus::PENDING, 'priority' => TaskPriority::HIGH, 'dueDate' => $now, 'tags' => ['Work', 'Project']],
            ['title' => 'Task Tomorrow', 'status' => TaskStatus::PENDING, 'priority' => TaskPriority::LOW, 'dueDate' => $now->modify('+1 day'), 'tags' => ['Personal']],
            ['title' => 'Task Overdue', 'status' => TaskStatus::PENDING, 'priority' => TaskPriority::URGENT, 'dueDate' => $now->modify('-1 day'), 'tags' => ['Urgent']],
            ['title' => 'Task Next Week', 'status' => TaskStatus::PENDING, 'priority' => TaskPriority::MEDIUM, 'dueDate' => $now->modify('+7 days'), 'tags' => ['Project']],
            ['title' => 'Task Completed', 'status' => TaskStatus::COMPLETED, 'priority' => TaskPriority::MEDIUM, 'dueDate' => $now, 'tags' => ['Personal']],
            ['title' => 'Task In Progress', 'status' => TaskStatus::IN_PROGRESS, 'priority' => TaskPriority::HIGH, 'dueDate' => $now, 'tags' => ['Work']],
            ['title' => 'Task No Date', 'status' => TaskStatus::PENDING, 'priority' => TaskPriority::LOW, 'dueDate' => null, 'tags' => ['Home']],
        ];

        $tasks = [];

        foreach ($tasksData as $data) {
            // Check if task already exists
            $existingTask = $this->entityManager
                ->getRepository(Task::class)
                ->findOneBy(['title' => $data['title'], 'user' => $user]);

            if ($existingTask) {
                $tasks[] = $existingTask;
                continue;
            }

            $task = new Task();
            $task->setTitle($data['title']);
            $task->setStatus($data['status']);
            $task->setPriority($data['priority']);
            $task->setDueDate($data['dueDate']);
            $task->setUser($user);

            // Add tags
            foreach ($data['tags'] as $tagName) {
                if (isset($tags[$tagName])) {
                    $task->addTag($tags[$tagName]);
                }
            }

            $this->entityManager->persist($task);
            $tasks[] = $task;
        }

        // Create parent task with subtask
        $existingParent = $this->entityManager
            ->getRepository(Task::class)
            ->findOneBy(['title' => 'Parent Task', 'user' => $user]);

        if (!$existingParent) {
            $parentTask = new Task();
            $parentTask->setTitle('Parent Task');
            $parentTask->setStatus(TaskStatus::PENDING);
            $parentTask->setPriority(TaskPriority::MEDIUM);
            $parentTask->setDueDate($now);
            $parentTask->setUser($user);
            $this->entityManager->persist($parentTask);
            $tasks[] = $parentTask;

            $subtask = new Task();
            $subtask->setTitle('Subtask 1');
            $subtask->setStatus(TaskStatus::PENDING);
            $subtask->setPriority(TaskPriority::LOW);
            $subtask->setDueDate($now);
            $subtask->setUser($user);
            $subtask->setParentTask($parentTask);
            $this->entityManager->persist($subtask);
            $tasks[] = $subtask;
        }

        return $tasks;
    }

    private function createRecurrenceRules(User $user): array
    {
        $now = new DateTimeImmutable();

        $recurrenceData = [
            ['title' => 'Daily Recurring Task', 'type' => 'daily', 'interval' => 1],
            ['title' => 'Weekly Recurring Task', 'type' => 'weekly', 'interval' => 1, 'daysOfWeek' => [1]], // Monday
            ['title' => 'Monthly Recurring Task', 'type' => 'monthly', 'interval' => 1, 'dayOfMonth' => 15],
            ['title' => 'Yearly Recurring Task', 'type' => 'yearly', 'interval' => 1, 'monthOfYear' => 1, 'dayOfMonth' => 1],
        ];

        $rules = [];

        foreach ($recurrenceData as $data) {
            // Check if task already exists
            $existingTask = $this->entityManager
                ->getRepository(Task::class)
                ->findOneBy(['title' => $data['title'], 'user' => $user]);

            if ($existingTask) {
                $rules[] = $existingTask->getRecurrenceRule();
                continue;
            }

            $task = new Task();
            $task->setTitle($data['title']);
            $task->setStatus(TaskStatus::PENDING);
            $task->setPriority(TaskPriority::MEDIUM);
            $task->setDueDate($now);
            $task->setUser($user);
            $this->entityManager->persist($task);

            $rule = new RecurrenceRule();
            $rule->setTemplateTask($task);
            $rule->setCreatedBy($user);
            $rule->setRecurrenceType($data['type']);
            $rule->setInterval($data['interval']);
            $rule->setNextOccurrenceDate($now);

            if (isset($data['daysOfWeek'])) {
                $rule->setDaysOfWeek($data['daysOfWeek']);
            }

            if (isset($data['dayOfMonth'])) {
                $rule->setDayOfMonth($data['dayOfMonth']);
            }

            if (isset($data['monthOfYear'])) {
                $rule->setMonthOfYear($data['monthOfYear']);
            }

            $this->entityManager->persist($rule);
            $rules[] = $rule;
        }

        return $rules;
    }
}
