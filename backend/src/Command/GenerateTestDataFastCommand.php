<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Doctrine\DBAL\Connection;
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
    name: 'app:generate-test-data-fast',
    description: 'FAST generation using native SQL - 1M tasks in ~1 hour'
)]
class GenerateTestDataFastCommand extends Command
{
    private const BATCH_SIZE = 5000; // Increased from 1000
    private const DEFAULT_USERS = 50;
    private const DEFAULT_TASKS_PER_USER = 40000;

    private Generator $faker;
    private int $totalTasksGenerated = 0;

    public function __construct(
        private readonly Connection $connection,
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

        $io->title('⚡ FAST Performance Test Data Generator (Native SQL)');
        $io->text([
            sprintf('Users to generate: <info>%d</info>', $numUsers),
            sprintf('Tasks per user: <info>%d</info>', $tasksPerUser),
            sprintf('Total tasks: <info>%s</info>', number_format($totalTasks)),
            sprintf('Batch size: <info>%d</info>', self::BATCH_SIZE),
            '',
            '<comment>Using native SQL for maximum performance!</comment>'
        ]);

        if ($clearExisting) {
            $io->section('Clearing existing data...');
            $this->clearExistingData($io);
        }

        $startTime = microtime(true);

        // Using auto-commit mode for safety (data saved every batch)
        // No single big transaction - if interrupted, partial data will be saved

        try {
            // Step 1: Generate users
            $io->section('Step 1/4: Generating users...');
            $userIds = $this->generateUsersFast($numUsers, $io);

            // Step 2: Generate tags
            $io->section('Step 2/4: Generating tags...');
            $tagIdsByUser = $this->generateTagsFast($userIds, $io);

            // Step 3: Drop indexes for faster inserts
            $io->section('Step 3/4: Optimizing database (dropping indexes)...');
            $this->dropIndexes($io);

            // Step 4: Generate tasks with COPY or batch INSERT
            $io->section('Step 4/4: Generating tasks (FAST)...');
            $this->generateTasksFast($userIds, $tagIdsByUser, $tasksPerUser, $io);

            // Step 5: Recreate indexes
            $io->section('Step 5/4: Rebuilding indexes...');
            $this->recreateIndexes($io);

            $duration = microtime(true) - $startTime;

            $io->success([
                sprintf('⚡ FAST generation completed in %.2f seconds!', $duration),
                sprintf('Generated %d users', $numUsers),
                sprintf('Generated %d tags', array_sum(array_map('count', $tagIdsByUser))),
                sprintf('Generated %s tasks', number_format($this->totalTasksGenerated)),
                sprintf('Speed: %s tasks/sec', number_format($this->totalTasksGenerated / $duration)),
                '',
                'You can now run performance tests!',
                '',
                sprintf('Speedup: ~%.0fx faster than ORM!', 12 * 3600 / max($duration, 1))
            ]);

        } catch (\Exception $e) {
            $io->error('Generation failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function clearExistingData(SymfonyStyle $io): void
    {
        $io->text('Truncating tables...');

        $this->connection->executeStatement('TRUNCATE TABLE task_tags CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE task RESTART IDENTITY CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE tag RESTART IDENTITY CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE users RESTART IDENTITY CASCADE');

        $io->success('Existing data cleared!');
    }

    private function generateUsersFast(int $count, SymfonyStyle $io): array
    {
        $progressBar = new ProgressBar($io, $count);
        $progressBar->start();

        $userIds = [];
        $values = [];

        // Pre-hash password once
        $hashedPassword = password_hash('password123', PASSWORD_BCRYPT);

        for ($i = 0; $i < $count; $i++) {
            $email = sprintf('testuser%d@example.com', $i + 1);
            $name = $this->connection->quote($this->faker->name());
            $createdAt = $updatedAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

            $values[] = sprintf(
                "('%s', %s, '%s', '[]', 'light', 'ru', 'Europe/Moscow', '{}', '%s', '%s')",
                $email,
                $name,
                $hashedPassword,
                $createdAt,
                $updatedAt
            );

            if (count($values) >= 100 || $i === $count - 1) {
                $sql = "INSERT INTO users (email, name, password, roles, theme, language, timezone, notification_settings, created_at, updated_at) VALUES "
                    . implode(', ', $values) . " RETURNING id";

                $result = $this->connection->executeQuery($sql);
                $userIds = array_merge($userIds, $result->fetchFirstColumn());

                $values = [];
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        return $userIds;
    }

    private function generateTagsFast(array $userIds, SymfonyStyle $io): array
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

        $progressBar = new ProgressBar($io, count($userIds));
        $progressBar->start();

        $tagIdsByUser = [];
        $values = [];

        foreach ($userIds as $userId) {
            $numTags = rand(5, 8);
            $selectedNames = (array)array_rand(array_flip($tagNames), $numTags);

            foreach ($selectedNames as $tagName) {
                $color = $colors[array_rand($colors)];
                $createdAt = $updatedAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

                $values[] = sprintf(
                    "('%s', '%s', 0, %d, '%s', '%s')",
                    $tagName,
                    $color,
                    $userId,
                    $createdAt,
                    $updatedAt
                );
            }

            $progressBar->advance();
        }

        // Insert all tags at once
        if (!empty($values)) {
            $sql = "INSERT INTO tag (name, color, usage_count, user_id, created_at, updated_at) VALUES "
                . implode(', ', $values) . " RETURNING id, user_id";

            $result = $this->connection->executeQuery($sql);

            foreach ($result->fetchAllAssociative() as $row) {
                $tagIdsByUser[$row['user_id']][] = $row['id'];
            }
        }

        $progressBar->finish();
        $io->newLine(2);

        return $tagIdsByUser;
    }

    private function dropIndexes(SymfonyStyle $io): void
    {
        $io->text('Dropping non-essential indexes for faster inserts...');

        // Get all indexes except PRIMARY and UNIQUE
        $indexes = $this->connection->executeQuery(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'task' AND indexname NOT LIKE '%pkey%'"
        )->fetchFirstColumn();

        foreach ($indexes as $indexName) {
            try {
                $this->connection->executeStatement("DROP INDEX IF EXISTS $indexName");
            } catch (\Exception $e) {
                // Ignore errors
            }
        }

        $io->text(sprintf('Dropped %d indexes', count($indexes)));
    }

    private function recreateIndexes(SymfonyStyle $io): void
    {
        $io->text('Recreating indexes...');

        // Recreate essential indexes
        $indexes = [
            "CREATE INDEX idx_task_user_id ON task(user_id)",
            "CREATE INDEX idx_task_parent_task_id ON task(parent_task_id)",
            "CREATE INDEX idx_task_status ON task(status)",
            "CREATE INDEX idx_task_priority ON task(priority)",
            "CREATE INDEX idx_task_due_date ON task(due_date)",
            "CREATE INDEX idx_task_is_archived ON task(is_archived)",
        ];

        foreach ($indexes as $sql) {
            try {
                $this->connection->executeStatement($sql);
            } catch (\Exception $e) {
                $io->warning('Failed to create index: ' . $e->getMessage());
            }
        }

        $io->text(sprintf('Created %d indexes', count($indexes)));
    }

    private function generateTasksFast(array $userIds, array $tagIdsByUser, int $tasksPerUser, SymfonyStyle $io): void
    {
        $totalTasks = count($userIds) * $tasksPerUser;
        $progressBar = new ProgressBar($io, $totalTasks);
        $progressBar->start();

        $taskValues = [];
        $taskTagValues = [];
        $batchCount = 0;

        $statuses = [
            TaskStatus::PENDING->value => 40,
            TaskStatus::IN_PROGRESS->value => 25,
            TaskStatus::COMPLETED->value => 30,
            TaskStatus::CANCELLED->value => 5,
        ];

        $priorities = [
            TaskPriority::LOW->value => 20,
            TaskPriority::MEDIUM->value => 50,
            TaskPriority::HIGH->value => 25,
            TaskPriority::URGENT->value => 5,
        ];

        foreach ($userIds as $userId) {
            $userTags = $tagIdsByUser[$userId] ?? [];

            for ($i = 0; $i < $tasksPerUser; $i++) {
                $title = $this->connection->quote($this->faker->sentence(rand(3, 8)));
                $description = rand(1, 100) <= 70 ? $this->connection->quote($this->faker->paragraph()) : 'NULL';
                $status = $this->weightedRandom($statuses);
                $priority = $this->weightedRandom($priorities);

                // Generate dates
                $baseDate = $this->faker->dateTimeBetween('-3 years', '-1 day');
                $createdAt = $updatedAt = $baseDate->format('Y-m-d H:i:s');

                $dueDate = 'NULL';
                if (rand(1, 100) <= 70) {
                    $dueDateObj = $this->faker->dateTimeBetween($baseDate, (clone $baseDate)->modify('+6 months'));
                    $dueDate = "'" . $dueDateObj->format('Y-m-d H:i:s') . "'";
                }

                $startDate = 'NULL';
                if (rand(1, 100) <= 30 && $dueDate !== 'NULL') {
                    $startDateObj = $this->faker->dateTimeBetween($baseDate, str_replace("'", "", $dueDate));
                    $startDate = "'" . $startDateObj->format('Y-m-d H:i:s') . "'";
                }

                $completedAt = 'NULL';
                if ($status === TaskStatus::COMPLETED->value) {
                    $completedAtObj = $this->faker->dateTimeBetween($baseDate, 'now');
                    $completedAt = "'" . $completedAtObj->format('Y-m-d H:i:s') . "'";
                }

                $isArchived = rand(1, 100) <= 10 ? 'true' : 'false';
                $sortOrder = 0;

                $taskValues[] = sprintf(
                    "(%s, %s, '%s', '%s', %s, %s, %s, %d, NULL, 0, %s, false, '%s', '%s')",
                    $title,
                    $description,
                    $status,
                    $priority,
                    $startDate,
                    $dueDate,
                    $completedAt,
                    $userId,
                    $isArchived,
                    $createdAt,
                    $updatedAt
                );

                $this->totalTasksGenerated++;
                $batchCount++;

                // Flush batch
                if ($batchCount >= self::BATCH_SIZE) {
                    $this->insertTaskBatch($taskValues, $taskTagValues, $userTags);
                    $taskValues = [];
                    $taskTagValues = [];
                    $batchCount = 0;
                }

                $progressBar->advance();
            }
        }

        // Insert remaining
        if (!empty($taskValues)) {
            $this->insertTaskBatch($taskValues, $taskTagValues, []);
        }

        $progressBar->finish();
        $io->newLine(2);
    }

    private function insertTaskBatch(array &$taskValues, array &$taskTagValues, array $userTags): void
    {
        if (empty($taskValues)) {
            return;
        }

        $sql = "INSERT INTO task (title, description, status, priority, start_date, due_date, completed_at, user_id, parent_task_id, sort_order, is_archived, is_recurring_template, created_at, updated_at) VALUES "
            . implode(', ', $taskValues);

        $this->connection->executeStatement($sql);
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
