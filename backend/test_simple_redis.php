<?php

use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/.env');

// Загружаем Symfony kernel
$kernel = new \App\Kernel($_ENV['APP_ENV'], false);
$kernel->boot();
$container = $kernel->getContainer();

echo "=== ТЕСТ SIMPLE REDIS CACHE ===\n\n";

// 1. Очистим Redis
$redis = new Redis();
$redis->connect('redis', 6379);
$redis->flushAll();
echo "1. ✓ Redis очищен\n\n";

// 2. Получим SimpleRedisCache из контейнера
try {
    $cache = $container->get('App\Service\Cache\SimpleRedisCache');
    echo "2. ✓ SimpleRedisCache получен из контейнера\n\n";
} catch (Exception $e) {
    echo "2. ✗ ОШИБКА: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Проверим что Redis пустой
$keysBefore = $redis->keys('*');
echo "3. Ключей в Redis ПЕРЕД тестом: " . count($keysBefore) . "\n\n";

// 4. Сохраним через SimpleRedisCache
$testKey = 'test_' . time();
$testValue = ['data' => 'test_value', 'timestamp' => time()];

echo "4. Сохранение в кеш:\n";
echo "   Ключ: $testKey\n";
echo "   Значение: " . json_encode($testValue) . "\n";

$setResult = $cache->set($testKey, $testValue, 300);
echo "   Результат set(): " . ($setResult ? '✓ TRUE' : '✗ FALSE') . "\n\n";

// 5. Проверим Redis СРАЗУ после записи
$keysAfter = $redis->keys('*');
echo "5. Ключей в Redis ПОСЛЕ записи: " . count($keysAfter) . "\n";
foreach ($keysAfter as $key) {
    echo "   - $key\n";
}
echo "\n";

// 6. Получим значение через cache->get()
echo "6. Чтение из кеша:\n";
$getValue = $cache->get($testKey, function() {
    echo "   ⚠ FALLBACK был вызван (кеш промах!)\n";
    return ['fallback' => true];
});
echo "   Полученное значение: " . json_encode($getValue) . "\n";
echo "   Совпадает с оригиналом: " . ($getValue === $testValue ? '✓ ДА' : '✗ НЕТ') . "\n\n";

// 7. Проверим Redis напрямую
echo "7. Чтение напрямую из Redis:\n";
$directValue = $redis->get('app:' . $testKey);
if ($directValue === false) {
    echo "   ✗ Ключ НЕ найден в Redis!\n";
} else {
    $unserialized = unserialize($directValue);
    echo "   ✓ Значение найдено: " . json_encode($unserialized) . "\n";
    echo "   Совпадает с оригиналом: " . ($unserialized === $testValue ? '✓ ДА' : '✗ НЕТ') . "\n";
}

echo "\n=== ИТОГ ===\n";
$success = $setResult && count($keysAfter) > 0 && ($getValue === $testValue);
echo $success ? "✓ ВСЕ ТЕСТЫ ПРОЙДЕНЫ\n" : "✗ ЕСТЬ ОШИБКИ\n";

$kernel->shutdown();
