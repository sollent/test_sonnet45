<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class Calendar30OctoberFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Find testuser@example.com
        $user = $manager->getRepository(User::class)->findOneBy(['email' => 'testuser@example.com']);
        
        if (!$user) {
            echo "User testuser@example.com not found!\n";
            return;
        }

        // Create 5 tasks for October 30, 2025
        $tasksData = [
            [
                'title' => 'Задача 1 на 30 октября',
                'description' => 'Первая тестовая задача для проверки календаря',
                'priority' => TaskPriority::HIGH,
                'status' => TaskStatus::PENDING,
                'startHour' => 9,
                'durationHours' => 2,
            ],
            [
                'title' => 'Задача 2 на 30 октября',
                'description' => 'Вторая тестовая задача',
                'priority' => TaskPriority::MEDIUM,
                'status' => TaskStatus::PENDING,
                'startHour' => 12,
                'durationHours' => 1,
            ],
            [
                'title' => 'Задача 3 на 30 октября',
                'description' => 'Третья тестовая задача',
                'priority' => TaskPriority::LOW,
                'status' => TaskStatus::IN_PROGRESS,
                'startHour' => 14,
                'durationHours' => 3,
            ],
            [
                'title' => 'Задача 4 на 30 октября',
                'description' => 'Четвертая тестовая задача',
                'priority' => TaskPriority::URGENT,
                'status' => TaskStatus::PENDING,
                'startHour' => 18,
                'durationHours' => 1,
            ],
            [
                'title' => 'Задача 5 на 30 октября',
                'description' => 'Пятая тестовая задача',
                'priority' => TaskPriority::MEDIUM,
                'status' => TaskStatus::PENDING,
                'startHour' => 20,
                'durationHours' => 2,
            ],
        ];

        foreach ($tasksData as $taskData) {
            $task = new Task();
            $task->setTitle($taskData['title']);
            $task->setDescription($taskData['description']);
            $task->setPriority($taskData['priority']);
            $task->setStatus($taskData['status']);
            $task->setUser($user);

            // Set dates for October 30, 2025
            $startDate = new \DateTimeImmutable('2025-10-30');
            $startDate = $startDate->setTime($taskData['startHour'], 0, 0);
            $task->setStartDate($startDate);

            $dueDate = $startDate->modify("+{$taskData['durationHours']} hours");
            $task->setDueDate($dueDate);

            $manager->persist($task);
        }

        $manager->flush();
        
        echo "Created 5 test tasks for October 30, 2025\n";
    }
}

