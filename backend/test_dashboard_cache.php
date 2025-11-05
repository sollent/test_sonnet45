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

echo "=== ТЕСТ DASHBOARD ANALYTICS CACHE ===\n\n";

// 1. Очистим Redis
$redis = new Redis();
$redis->connect('redis', 6379);
$redis->flushAll();
echo "1. ✓ Redis очищен\n\n";

// 2. Получим первого пользователя
$em = $container->get('doctrine')->getManager();
$user = $em->getRepository(User::class)->findOneBy([]);

if (!$user) {
    echo "✗ ОШИБКА: Не найден пользователь\n";
    exit(1);
}

echo "2. ✓ Пользователь: " . $user->getEmail() . " (ID: " . $user->getId() . ")\n\n";

// 3. Получим AnalyticsService
$analyticsService = $container->get('App\Service\AnalyticsService');

// 4. Первый запрос dashboard
echo "3. Тестируем /api/analytics/dashboard?period=30&year=2025:\n";
$params = [
    'period' => 30,
    'dateFrom' => null,
    'dateTo' => null,
    'year' => 2025,
];

$start1 = microtime(true);
$dashboard1 = $analyticsService->getDashboardData($user, $params);
$time1 = (microtime(true) - $start1) * 1000;

echo "   Первый запрос (вычисление): " . round($time1, 2) . " мс\n";

// Проверим Redis
$keysAfter1 = $redis->keys('app:*dashboard*');
echo "   Dashboard ключей в Redis: " . count($keysAfter1) . "\n";
foreach ($keysAfter1 as $key) {
    $ttl = $redis->ttl($key);
    echo "   - $key (TTL: {$ttl}s)\n";
}

// 5. Второй запрос - должен быть из кеша
$start2 = microtime(true);
$dashboard2 = $analyticsService->getDashboardData($user, $params);
$time2 = (microtime(true) - $start2) * 1000;

echo "\n   Второй запрос (из кеша): " . round($time2, 2) . " мс\n";
echo "   Ускорение: " . round($time1 / max($time2, 0.01), 2) . "x\n";

// Проверим что данные идентичны
$identical = json_encode($dashboard1) === json_encode($dashboard2);
echo "   Данные идентичны: " . ($identical ? '✓ ДА' : '✗ НЕТ') . "\n\n";

// 6. Проверим с другими параметрами (должен создать новый ключ)
echo "4. Тестируем dashboard с period=7:\n";
$params2 = [
    'period' => 7,
    'dateFrom' => null,
    'dateTo' => null,
    'year' => 2025,
];

$start3 = microtime(true);
$dashboard3 = $analyticsService->getDashboardData($user, $params2);
$time3 = (microtime(true) - $start3) * 1000;

echo "   Запрос с period=7: " . round($time3, 2) . " мс\n";

$keysAfter2 = $redis->keys('app:*dashboard*');
echo "   Dashboard ключей в Redis: " . count($keysAfter2) . "\n";
echo "   (должно быть 2 - для period=30 и period=7)\n\n";

// 7. Все ключи в Redis
echo "5. Все ключи аналитики в Redis:\n";
$allKeys = $redis->keys('app:*analytics*');
echo "   Всего analytics ключей: " . count($allKeys) . "\n";
foreach ($allKeys as $key) {
    $ttl = $redis->ttl($key);
    echo "   - $key (TTL: {$ttl}s)\n";
}

echo "\n=== ИТОГ ===\n";
$success = count($keysAfter1) > 0 && $time2 < $time1 && $identical && count($keysAfter2) === 2;
echo $success ? "✓ DASHBOARD КЕШИРОВАНИЕ РАБОТАЕТ ИДЕАЛЬНО!\n" : "✗ ЕСТЬ ПРОБЛЕМЫ\n";

echo "\nСтатистика:\n";
echo "  - Dashboard (period=30) кеш: " . round($time1 / max($time2, 0.01), 2) . "x ускорение\n";
echo "  - Ключей в Redis: " . count($allKeys) . "\n";
echo "  - TTL: 900 секунд (15 минут)\n";

$kernel->shutdown();
