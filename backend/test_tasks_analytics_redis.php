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

echo "=== ТЕСТ REDIS CACHE ДЛЯ TASKS И ANALYTICS ===\n\n";

// 1. Очистим Redis
$redis = new Redis();
$redis->connect('redis', 6379);
$redis->flushAll();
echo "1. ✓ Redis очищен\n\n";

// 2. Получим первого пользователя для теста
$em = $container->get('doctrine')->getManager();
$user = $em->getRepository(User::class)->findOneBy([]);

if (!$user) {
    echo "✗ ОШИБКА: Не найден пользователь для теста\n";
    exit(1);
}

echo "2. ✓ Используем пользователя: " . $user->getEmail() . " (ID: " . $user->getId() . ")\n\n";

// 3. Проверим что Redis пустой
$keysBefore = $redis->keys('*');
echo "3. Ключей в Redis ПЕРЕД тестом: " . count($keysBefore) . "\n\n";

// 4. Получим TaskRepository и сделаем запрос
echo "4. Тестируем кеширование задач:\n";
$taskRepo = $em->getRepository(\App\Entity\Task::class);

$start1 = microtime(true);
$tasks1 = $taskRepo->findUserTasks($user);
$time1 = (microtime(true) - $start1) * 1000;

echo "   Первый запрос (должен вычислить): " . round($time1, 2) . " мс\n";
echo "   Найдено задач: " . count($tasks1) . "\n";

// Проверим Redis после первого запроса
$keysAfterTask1 = $redis->keys('app:*');
echo "   Ключей в Redis после 1-го запроса: " . count($keysAfterTask1) . "\n";

// Второй запрос (должен быть из кеша)
$start2 = microtime(true);
$tasks2 = $taskRepo->findUserTasks($user);
$time2 = (microtime(true) - $start2) * 1000;

echo "   Второй запрос (из кеша): " . round($time2, 2) . " мс\n";
echo "   Ускорение: " . round($time1 / max($time2, 0.01), 2) . "x\n";
echo "   Данные совпадают: " . (count($tasks1) === count($tasks2) ? '✓ ДА' : '✗ НЕТ') . "\n\n";

// 5. Тестируем Analytics
echo "5. Тестируем кеширование аналитики:\n";
$analyticsService = $container->get('App\Service\AnalyticsService');

$start3 = microtime(true);
$analytics1 = $analyticsService->getOverview($user);
$time3 = (microtime(true) - $start3) * 1000;

echo "   Первый запрос аналитики (должен вычислить): " . round($time3, 2) . " мс\n";

// Проверим Redis после analytics
$keysAfterAnalytics1 = $redis->keys('app:*');
echo "   Ключей в Redis после analytics: " . count($keysAfterAnalytics1) . "\n";

// Второй запрос analytics (должен быть из кеша)
$start4 = microtime(true);
$analytics2 = $analyticsService->getOverview($user);
$time4 = (microtime(true) - $start4) * 1000;

echo "   Второй запрос аналитики (из кеша): " . round($time4, 2) . " мс\n";
echo "   Ускорение: " . round($time3 / max($time4, 0.01), 2) . "x\n\n";

// 6. Проверим все ключи в Redis
echo "6. Все ключи в Redis:\n";
$allKeys = $redis->keys('app:*');
echo "   Всего ключей: " . count($allKeys) . "\n";
foreach ($allKeys as $key) {
    $ttl = $redis->ttl($key);
    echo "   - $key (TTL: {$ttl}s)\n";
}

echo "\n=== ИТОГ ===\n";
$success = count($allKeys) > 0 && $time2 < $time1 && $time4 < $time3;
echo $success ? "✓ ВСЕ ТЕСТЫ ПРОЙДЕНЫ - ДАННЫЕ КЕШИРУЮТСЯ В REDIS!\n" : "✗ ЕСТЬ ПРОБЛЕМЫ С КЕШИРОВАНИЕМ\n";

$kernel->shutdown();
