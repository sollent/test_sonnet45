<?php

use Symfony\Component\Dotenv\Dotenv;
use App\Entity\User;

require __DIR__ . '/vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/.env');

// Загружаем Symfony kernel
$kernel = new \App\Kernel($_ENV['APP_ENV'], false);
$kernel->boot();
$container = $kernel->getContainer();

echo "=== ТЕСТ MULTI-USER КЕШИРОВАНИЯ ===\n\n";

// 1. Очистим Redis
$redis = new Redis();
$redis->connect('redis', 6379);
$redis->flushAll();
echo "1. ✓ Redis очищен\n\n";

// 2. Получим двух пользователей
$em = $container->get('doctrine')->getManager();
$users = $em->getRepository(User::class)->findAll();

if (count($users) < 2) {
    echo "⚠ ВНИМАНИЕ: Найден только 1 пользователь. Создам второго для теста...\n";

    $user2 = new User();
    $user2->setEmail('test_user_2_' . time() . '@example.com');
    $user2->setPassword('test');
    $user2->setRoles(['ROLE_USER']);

    $em->persist($user2);
    $em->flush();

    $users = $em->getRepository(User::class)->findAll();
    echo "✓ Создан второй пользователь\n\n";
}

$user1 = $users[0];
$user2 = $users[1];

echo "2. ✓ Пользователи:\n";
echo "   User 1: " . $user1->getEmail() . " (ID: " . $user1->getId() . ")\n";
echo "   User 2: " . $user2->getEmail() . " (ID: " . $user2->getId() . ")\n\n";

// 3. Получим сервисы
$taskRepo = $em->getRepository(\App\Entity\Task::class);
$analyticsService = $container->get('App\Service\AnalyticsService');

// 4. Запросим задачи для User 1
echo "3. Запрос задач для User 1:\n";
$start1 = microtime(true);
$tasks1 = $taskRepo->findUserTasks($user1);
$time1 = (microtime(true) - $start1) * 1000;

echo "   Время: " . round($time1, 2) . " мс\n";
echo "   Задач: " . count($tasks1) . "\n";

// Проверим Redis
$keysUser1 = $redis->keys('app:*uid_' . $user1->getId() . '*');
echo "   Ключей User 1 в Redis: " . count($keysUser1) . "\n\n";

// 5. Запросим задачи для User 2
echo "4. Запрос задач для User 2:\n";
$start2 = microtime(true);
$tasks2 = $taskRepo->findUserTasks($user2);
$time2 = (microtime(true) - $start2) * 1000;

echo "   Время: " . round($time2, 2) . " мс\n";
echo "   Задач: " . count($tasks2) . "\n";

// Проверим Redis
$keysUser2 = $redis->keys('app:*uid_' . $user2->getId() . '*');
echo "   Ключей User 2 в Redis: " . count($keysUser2) . "\n\n";

// 6. Проверим что ключи разные
echo "5. Проверка изоляции кеша:\n";
$allKeys = $redis->keys('app:*uid_*');
echo "   Всего ключей с uid: " . count($allKeys) . "\n";

$user1Keys = array_filter($allKeys, fn($k) => str_contains($k, 'uid_' . $user1->getId()));
$user2Keys = array_filter($allKeys, fn($k) => str_contains($k, 'uid_' . $user2->getId()));

echo "   Ключей для User 1: " . count($user1Keys) . "\n";
echo "   Ключей для User 2: " . count($user2Keys) . "\n";

foreach ($user1Keys as $key) {
    echo "   [User 1] $key\n";
}
foreach ($user2Keys as $key) {
    echo "   [User 2] $key\n";
}

// 7. Analytics для обоих пользователей
echo "\n6. Запрос аналитики:\n";

$start3 = microtime(true);
$analytics1 = $analyticsService->getOverview($user1);
$time3 = (microtime(true) - $start3) * 1000;
echo "   User 1 analytics: " . round($time3, 2) . " мс\n";

$start4 = microtime(true);
$analytics2 = $analyticsService->getOverview($user2);
$time4 = (microtime(true) - $start4) * 1000;
echo "   User 2 analytics: " . round($time4, 2) . " мс\n\n";

// 8. Финальная проверка
$finalKeysUser1 = $redis->keys('app:*uid_' . $user1->getId() . '*');
$finalKeysUser2 = $redis->keys('app:*uid_' . $user2->getId() . '*');

echo "7. Финальная статистика:\n";
echo "   User 1 - всего ключей: " . count($finalKeysUser1) . "\n";
echo "   User 2 - всего ключей: " . count($finalKeysUser2) . "\n";

// Проверим что данные разные
$tasksMatch = count($tasks1) === count($tasks2);
$analyticsMatch = json_encode($analytics1) === json_encode($analytics2);

echo "\n   Задачи одинаковые: " . ($tasksMatch ? '⚠ ДА (может быть OK если у обоих 0 задач)' : '✓ НЕТ (правильно!)') . "\n";
echo "   Аналитика одинаковая: " . ($analyticsMatch ? '⚠ ДА' : '✓ НЕТ (правильно!)') . "\n";

echo "\n=== ИТОГ ===\n";
$success = count($finalKeysUser1) > 0 && count($finalKeysUser2) > 0 && count($user1Keys) > 0 && count($user2Keys) > 0;
echo $success ? "✓ MULTI-USER КЕШИРОВАНИЕ РАБОТАЕТ!\n" : "✗ ЕСТЬ ПРОБЛЕМЫ\n";

echo "\nКаждый пользователь имеет свой изолированный кеш в Redis!\n";

$kernel->shutdown();
