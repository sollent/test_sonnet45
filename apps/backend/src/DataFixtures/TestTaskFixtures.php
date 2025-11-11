<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class TestTaskFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('ru_RU');

        /** @var User|null $user */
        $user = $manager->getRepository(User::class)->findOneBy(['email' => 'sollent98@gmail.com']);

        if (!$user) {
            // If user doesn't exist, you might want to create it or throw an exception
            // For this case, we'll just exit.
            return;
        }

        $tags = ['Работа', 'Личное', 'Покупки', 'Срочно', 'Проект X', 'Здоровье', 'Фитнес', 'Дом'];

        $now = new DateTimeImmutable();

        // --- Create Tasks for the Past (Overdue) ---
        for ($i = 0; $i < 5; $i++) {
            $task = new Task();
            $task->setUser($user)
                ->setTitle($faker->sentence(4))
                ->setDescription($faker->paragraph(3))
                ->setStatus($i % 2 === 0 ? TaskStatus::PENDING : TaskStatus::IN_PROGRESS)
                ->setPriority($faker->randomElement(TaskPriority::cases()))
                ->setDueDate($now->modify('-' . $faker->numberBetween(2, 5) . ' days'));

            $this->addRandomTags($task, $tags, $manager, $user);
            $manager->persist($task);
        }

        // --- Create Tasks for Today ---
        for ($i = 0; $i < 4; $i++) {
            $task = new Task();
            $task->setUser($user)
                ->setTitle($faker->sentence(5))
                ->setDescription($faker->optional(0.7)->paragraph(2))
                ->setStatus($faker->randomElement([TaskStatus::PENDING, TaskStatus::IN_PROGRESS, TaskStatus::COMPLETED]))
                ->setPriority($faker->randomElement(TaskPriority::cases()))
                ->setDueDate($now->setTime(rand(9, 22), rand(0, 59)));

            $this->addRandomTags($task, $tags, $manager, $user);

            // Add subtasks to some of today's tasks
            if ($i % 2 === 0) {
                for ($j = 0; $j < rand(2, 5); $j++) {
                    $subtask = new Task();
                    $subtask->setUser($user)
                        ->setTitle('Подзадача: ' . $faker->sentence(3))
                        ->setStatus($faker->randomElement([TaskStatus::PENDING, TaskStatus::COMPLETED]))
                        ->setPriority(TaskPriority::MEDIUM)
                        ->setParentTask($task);
                    $manager->persist($subtask);
                }
            }

            $manager->persist($task);
        }

        // --- Create Tasks for the Future ---
        for ($i = 0; $i < 10; $i++) {
            $task = new Task();
            $task->setUser($user)
                ->setTitle($faker->sentence(6))
                ->setDescription($faker->paragraph(1))
                ->setStatus(TaskStatus::PENDING)
                ->setPriority($faker->randomElement([TaskPriority::LOW, TaskPriority::MEDIUM, TaskPriority::HIGH]))
                ->setDueDate($now->modify('+' . $faker->numberBetween(1, 14) . ' days'));

            $this->addRandomTags($task, $tags, $manager, $user);
            $manager->persist($task);
        }

        // --- Create a complex task with subtasks ---
        $complexTask = new Task();
        $complexTask->setUser($user)
            ->setTitle('Большой проект: Редизайн главной страницы')
            ->setDescription('Полностью переработать дизайн и UX главной страницы сайта, включая мобильную адаптацию.')
            ->setStatus(TaskStatus::IN_PROGRESS)
            ->setPriority(TaskPriority::URGENT)
            ->setDueDate($now->modify('+7 days'));
        $this->addRandomTags($complexTask, ['Проект X', 'Срочно', 'Работа'], $manager, $user);

        $subtasksData = [
            ['title' => 'Провести анализ конкурентов', 'completed' => true],
            ['title' => 'Создать прототип в Figma', 'completed' => true],
            ['title' => 'Разработать новый UI-кит', 'completed' => false],
            ['title' => 'Написать frontend компоненты', 'completed' => false],
            ['title' => 'Протестировать на всех устройствах', 'completed' => false],
        ];

        foreach ($subtasksData as $data) {
            $subtask = new Task();
            $subtask->setUser($user)
                ->setTitle($data['title'])
                ->setStatus($data['completed'] ? TaskStatus::COMPLETED : TaskStatus::PENDING)
                ->setPriority(TaskPriority::HIGH)
                ->setParentTask($complexTask);
            $manager->persist($subtask);
        }
        $manager->persist($complexTask);

        $manager->flush();
    }

    private function addRandomTags(Task $task, array $tagNames, ObjectManager $manager, User $user): void
    {
        $tagRepository = $manager->getRepository(\App\Entity\Tag::class);
        $tagsToUse = array_slice($tagNames, 0, rand(1, 4));

        $tags = $tagRepository->findOrCreateByNames($tagsToUse, $user);

        foreach ($tags as $tag) {
            $task->addTag($tag);
        }
    }
}
