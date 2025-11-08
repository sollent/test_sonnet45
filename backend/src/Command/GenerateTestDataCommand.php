<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Tag;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:generate-test-data',
    description: 'Generate test data for performance testing (2M tasks, 50 users)'
)]
class GenerateTestDataCommand extends Command
{
    private const BATCH_SIZE = 1000;
    private const DEFAULT_USERS = 50;
    private const DEFAULT_TASKS_PER_USER = 40000;

    private Generator $faker;
    private array $userCache = [];
    private array $tagCache = [];
    private int $totalTasksGenerated = 0;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
        $this->faker = FakerFactory::create();
    }

    protected function configure(): void
    {
        $this
            ->addOption('users', 'u', InputOption::VALUE_OPTIONAL, 'Number of users to generate', self::DEFAULT_USERS)
            ->addOption('tasks-per-user', 't', InputOption::VALUE_OPTIONAL, 'Number of tasks per user', self::DEFAULT_TASKS_PER_USER)
            ->addOption('clear-existing', 'c', InputOption::VALUE_NONE, 'Clear existing data before generating')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $numUsers = (int)$input->getOption('users');
        $tasksPerUser = (int)$input->getOption('tasks-per-user');
        $clearExisting = (bool)$input->getOption('clear-existing');

        $totalTasks = $numUsers * $tasksPerUser;

        $io->title('Performance Test Data Generator');
        $io->text([
            sprintf('Users to generate: <info>%d</info>', $numUsers),
            sprintf('Tasks per user: <info>%d</info>', $tasksPerUser),
            sprintf('Total tasks: <info>%s</info>', number_format($totalTasks)),
            sprintf('Batch size: <info>%d</info>', self::BATCH_SIZE),
        ]);

        if ($clearExisting) {
            $io->section('Clearing existing data...');
            $this->clearExistingData($io);
        }

        $startTime = microtime(true);

        // Step 1: Generate users
        $io->section('Step 1/3: Generating users...');
        $this->generateUsers($numUsers, $io);

        // Step 2: Generate tags for each user
        $io->section('Step 2/3: Generating tags...');
        $this->generateTags($io);

        // Step 3: Generate tasks
        $io->section('Step 3/3: Generating tasks...');
        $this->generateTasks($tasksPerUser, $io);

        $duration = microtime(true) - $startTime;

        $io->success([
            sprintf('Test data generation completed in %.2f seconds!', $duration),
            sprintf('Generated %d users', $numUsers),
            sprintf('Generated %d tags', count($this->tagCache)),
            sprintf('Generated %s tasks', number_format($this->totalTasksGenerated)),
            '',
            'You can now run performance tests!'
        ]);

        return Command::SUCCESS;
    }

    private function clearExistingData(SymfonyStyle $io): void
    {
        $connection = $this->em->getConnection();

        $io->text('Truncating tables...');

        // PostgreSQL: Use TRUNCATE CASCADE to handle foreign keys
        $connection->executeStatement('TRUNCATE TABLE task RESTART IDENTITY CASCADE');
        $connection->executeStatement('TRUNCATE TABLE tag RESTART IDENTITY CASCADE');
        $connection->executeStatement('TRUNCATE TABLE users RESTART IDENTITY CASCADE');

        $io->success('Existing data cleared!');
    }

    private function generateUsers(int $count, SymfonyStyle $io): void
    {
        $progressBar = new ProgressBar($io, $count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $user = new User();
            $user->setEmail(sprintf('testuser%d@example.com', $i + 1));
            $user->setName($this->faker->name());

            // Set password hash (all test users have password "password123")
            $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
            $user->setPassword($hashedPassword);

            $this->em->persist($user);
            $this->userCache[] = $user;

            if (($i + 1) % self::BATCH_SIZE === 0) {
                $this->em->flush();
                $this->em->clear();

                // Re-fetch users to keep references
                $this->refetchUsers();
            }

            $progressBar->advance();
        }

        $this->em->flush();
        $this->em->clear();
        $this->refetchUsers();

        $progressBar->finish();
        $io->newLine(2);
    }

    private function refetchUsers(): void
    {
        $this->userCache = $this->em->getRepository(User::class)->findAll();
    }

    private function generateTags(SymfonyStyle $io): void
    {
        $tagNames = [
            'Work', 'Personal', 'Urgent', 'Important', 'Meeting',
            'Project', 'Research', 'Development', 'Design', 'Bug',
            'Feature', 'Documentation', 'Testing', 'Review', 'Planning'
        ];

        $colors = [
            '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
            '#EC4899', '#14B8A6', '#F97316', '#6366F1', '#06B6D4'
        ];

        $progressBar = new ProgressBar($io, count($this->userCache));
        $progressBar->start();

        foreach ($this->userCache as $user) {
            // Each user gets 5-8 random tags
            $numTags = rand(5, 8);
            $selectedNames = (array)array_rand(array_flip($tagNames), $numTags);

            foreach ($selectedNames as $tagName) {
                $tag = new Tag();
                $tag->setName($tagName);
                $tag->setUser($user);
                $tag->setColor($colors[array_rand($colors)]);

                $this->em->persist($tag);
                $this->tagCache[$user->getId()][] = $tag;
            }

            $progressBar->advance();
        }

        $this->em->flush();
        $this->em->clear();

        // Re-fetch tags and users
        $this->refetchUsers();
        $this->refetchTags();

        $progressBar->finish();
        $io->newLine(2);
    }

    private function refetchTags(): void
    {
        $this->tagCache = [];
        $allTags = $this->em->getRepository(Tag::class)->findAll();

        foreach ($allTags as $tag) {
            $this->tagCache[$tag->getUser()->getId()][] = $tag;
        }
    }

    private function generateTasks(int $tasksPerUser, SymfonyStyle $io): void
    {
        $totalTasks = count($this->userCache) * $tasksPerUser;
        $progressBar = new ProgressBar($io, $totalTasks);
        $progressBar->start();

        $batchCount = 0;

        // Store user and tag IDs instead of entities
        $userIds = [];
        $tagIdsByUser = [];

        foreach ($this->userCache as $user) {
            $userIds[] = $user->getId();
            $tagIdsByUser[$user->getId()] = array_map(fn($tag) => $tag->getId(), $this->tagCache[$user->getId()] ?? []);
        }

        foreach ($userIds as $userId) {
            // Get managed reference to user (no DB query)
            $user = $this->em->getReference(User::class, $userId);

            // Get managed references for tags
            $userTags = [];
            foreach ($tagIdsByUser[$userId] as $tagId) {
                $userTags[] = $this->em->getReference(Tag::class, $tagId);
            }

            for ($i = 0; $i < $tasksPerUser; $i++) {
                $task = $this->createTask($user, $userTags);
                $this->em->persist($task);

                $this->totalTasksGenerated++;
                $batchCount++;

                // 20% of tasks have subtasks
                if (rand(1, 100) <= 20) {
                    $this->createSubtasks($task, $user, $userTags, rand(1, 3));
                }

                if ($batchCount >= self::BATCH_SIZE) {
                    $this->em->flush();
                    $this->em->clear();

                    // Re-create managed references after clear
                    $user = $this->em->getReference(User::class, $userId);
                    $userTags = [];
                    foreach ($tagIdsByUser[$userId] as $tagId) {
                        $userTags[] = $this->em->getReference(Tag::class, $tagId);
                    }

                    $batchCount = 0;
                }

                $progressBar->advance();
            }
        }

        // Flush remaining tasks
        if ($batchCount > 0) {
            $this->em->flush();
            $this->em->clear();
        }

        $progressBar->finish();
        $io->newLine(2);
    }

    private function createTask(User $user, array $userTags, ?Task $parentTask = null): Task
    {
        $task = new Task();
        $task->setUser($user);
        $task->setTitle($this->faker->sentence(rand(3, 8)));
        $task->setDescription(rand(1, 100) <= 70 ? $this->faker->paragraph() : null);

        // Set random status with realistic distribution
        $statusWeights = [
            TaskStatus::PENDING->value => 40,
            TaskStatus::IN_PROGRESS->value => 25,
            TaskStatus::COMPLETED->value => 30,
            TaskStatus::CANCELLED->value => 5,
        ];
        $task->setStatus(TaskStatus::from($this->weightedRandom($statusWeights)));

        // Set random priority
        $priorityWeights = [
            TaskPriority::LOW->value => 20,
            TaskPriority::MEDIUM->value => 50,
            TaskPriority::HIGH->value => 25,
            TaskPriority::URGENT->value => 5,
        ];
        $task->setPriority(TaskPriority::from($this->weightedRandom($priorityWeights)));

        // Generate dates spanning 2022-2025 (3 years of history)
        // Base date for this task (when it was "created")
        $baseDate = $this->faker->dateTimeBetween('-3 years', '-1 day');

        // 70% of tasks have due date (from base date to +6 months)
        if (rand(1, 100) <= 70) {
            $dueDate = $this->faker->dateTimeBetween($baseDate, (clone $baseDate)->modify('+6 months'));
            $task->setDueDate(\DateTimeImmutable::createFromMutable($dueDate));
        }

        // 30% have start date (from base date to due date, or base date + 1 month if no due date)
        if (rand(1, 100) <= 30) {
            $endForStart = $task->getDueDate()
                ? $task->getDueDate()->format('Y-m-d H:i:s')
                : (clone $baseDate)->modify('+1 month')->format('Y-m-d H:i:s');

            $startDate = $this->faker->dateTimeBetween($baseDate, $endForStart);
            $task->setStartDate(\DateTimeImmutable::createFromMutable($startDate));
        }

        // Set completed_at for completed tasks (from base date to now)
        if ($task->getStatus() === TaskStatus::COMPLETED) {
            $completedAt = $this->faker->dateTimeBetween($baseDate, 'now');
            $task->setCompletedAt(\DateTimeImmutable::createFromMutable($completedAt));
        }

        // Add 1-3 tags randomly
        if (!empty($userTags) && rand(1, 100) <= 80) {
            $numTags = rand(1, min(3, count($userTags)));
            $selectedTags = (array)array_rand($userTags, $numTags);

            foreach ($selectedTags as $tagIndex) {
                $task->addTag($userTags[$tagIndex]);
            }
        }

        if ($parentTask) {
            $task->setParentTask($parentTask);
        }

        // 10% archived
        $task->setIsArchived(rand(1, 100) <= 10);

        return $task;
    }

    private function createSubtasks(Task $parentTask, User $user, array $userTags, int $depth, int $currentLevel = 1): void
    {
        if ($currentLevel > $depth) {
            return;
        }

        $numSubtasks = rand(1, 3);

        for ($i = 0; $i < $numSubtasks; $i++) {
            $subtask = $this->createTask($user, $userTags, $parentTask);
            $this->em->persist($subtask);
            $this->totalTasksGenerated++;

            // Recursively create deeper subtasks with decreasing probability
            if ($currentLevel < $depth && rand(1, 100) <= 40) {
                $this->createSubtasks($subtask, $user, $userTags, $depth, $currentLevel + 1);
            }
        }
    }

    private function weightedRandom(array $weights): string
    {
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);

        $currentWeight = 0;
        foreach ($weights as $value => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return (string)$value;
            }
        }

        return (string)array_key_first($weights);
    }
}
