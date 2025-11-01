<?php

namespace App\Command;

use App\Entity\Tag;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-tasks',
    description: 'Seeds the database with a large number of tasks for a specific user.',
)]
class SeedTasksCommand extends Command
{
    private const USER_EMAIL = 'vladislikedev@gmail.com';
    private const DAYS_AGO = 90; // 3 months
    private const TASKS_PER_DAY = 20;
    private const MAX_SUBTASK_DEPTH = 3;

    private \Faker\Generator $faker;

    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
        $this->faker = Factory::create();
    }

    protected function configure(): void
    {
        // configuration
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => self::USER_EMAIL]);
        if (!$user) {
            $io->error(sprintf('User with email "%s" not found.', self::USER_EMAIL));
            return Command::FAILURE;
        }

        $io->progressStart(self::DAYS_AGO * self::TASKS_PER_DAY);

        $tags = $this->getOrCreateTags(['Work', 'Personal', 'Study', 'Health', 'Finance', 'Urgent'], $user);

        for ($i = self::DAYS_AGO; $i >= 0; $i--) {
            $date = new \DateTimeImmutable("-{$i} days");
            for ($j = 0; $j < self::TASKS_PER_DAY; $j++) {
                $task = $this->createTask($user, $date, $tags);
                $this->em->persist($task);

                // Create subtasks
                if ($this->faker->boolean(40)) { // 40% chance of having subtasks
                    $this->createSubtasks($task, $user, $date, $tags, 1);
                }
                $io->progressAdvance();
            }
        }

        $this->em->flush();
        $io->progressFinish();
        $io->success('Successfully seeded tasks!');

        return Command::SUCCESS;
    }

    private function createTask(User $user, \DateTimeImmutable $date, array $tags): Task
    {
        $task = new Task();
        $task->setUser($user);
        $task->setTitle($this->faker->sentence(4));
        $task->setDescription($this->faker->boolean(70) ? $this->faker->paragraph : null);

        $status = $this->faker->randomElement(TaskStatus::cases());
        $task->setStatus($status);

        $task->setPriority($this->faker->randomElement(TaskPriority::cases()));

        $createdAt = $date->setTime($this->faker->numberBetween(0, 23), $this->faker->numberBetween(0, 59));

        if ($this->faker->boolean(60)) {
            $task->setDueDate($createdAt->modify('+' . $this->faker->numberBetween(1, 14) . ' days'));
        }
        if ($this->faker->boolean(30)) {
            $task->setStartDate($createdAt->modify('-' . $this->faker->numberBetween(1, 5) . ' days'));
        }

        // Add tags
        if ($this->faker->boolean(50)) {
            $taskTags = $this->faker->randomElements($tags, $this->faker->numberBetween(1, 3));
            foreach ($taskTags as $tag) {
                $task->addTag($tag);
            }
        }

        return $task;
    }

    private function createSubtasks(Task $parentTask, User $user, \DateTimeImmutable $date, array $tags, int $depth): void
    {
        if ($depth > self::MAX_SUBTASK_DEPTH) {
            return;
        }

        $subtaskCount = $this->faker->numberBetween(1, 5);
        for ($i = 0; $i < $subtaskCount; $i++) {
            $subtask = $this->createTask($user, $date, $tags);
            $subtask->setParentTask($parentTask);
            $this->em->persist($subtask);

            if ($this->faker->boolean(30)) { // 30% chance of nested subtasks
                $this->createSubtasks($subtask, $user, $date, $tags, $depth + 1);
            }
        }
    }

    private function getOrCreateTags(array $tagNames, User $user): array
    {
        $tags = [];
        $tagRepository = $this->em->getRepository(Tag::class);

        foreach ($tagNames as $tagName) {
            $tag = $tagRepository->findOneBy(['name' => $tagName, 'user' => $user]);
            if (!$tag) {
                $tag = new Tag();
                $tag->setName($tagName);
                $tag->setColor('#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT));
                $tag->setUser($user);
                $this->em->persist($tag);
            }
            $tags[] = $tag;
        }
        $this->em->flush();

        return $tags;
    }
}
