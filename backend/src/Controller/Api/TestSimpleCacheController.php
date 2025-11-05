<?php

namespace App\Controller\Api;

use App\Service\Cache\SimpleRedisCache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/test-simple-cache')]
class TestSimpleCacheController extends AbstractController
{
    public function __construct(
        private SimpleRedisCache $cache
    ) {}

    #[Route('/test', methods: ['GET'])]
    public function test(): JsonResponse
    {
        // Очистим Redis
        $redis = $this->cache->getRedis();
        $redis->flushAll();

        $testKey = 'test_' . time();
        $testValue = ['data' => 'test_value_' . time(), 'timestamp' => time()];

        // 1. Сохраним
        $setResult = $this->cache->set($testKey, $testValue, 300);

        // 2. Получим
        $getValue = $this->cache->get($testKey, fn() => ['fallback' => true]);

        // 3. Проверим Redis напрямую
        $keysInRedis = $redis->keys('app:*');

        return $this->json([
            'test_key' => $testKey,
            'set_result' => $setResult,
            'get_value' => $getValue,
            'values_match' => $getValue === $testValue,
            'keys_in_redis' => count($keysInRedis),
            'redis_keys' => $keysInRedis,
            'success' => $setResult && ($getValue === $testValue) && count($keysInRedis) > 0
        ]);
    }

    #[Route('/performance', methods: ['GET'])]
    public function performance(): JsonResponse
    {
        $key = 'perf_test_' . uniqid();

        // Первый вызов - вычисление
        $start1 = microtime(true);
        $value1 = $this->cache->get($key, function() {
            usleep(50000); // 50ms задержка
            return ['computed' => true, 'time' => time()];
        }, 300);
        $time1 = (microtime(true) - $start1) * 1000;

        // Второй вызов - из кеша
        $start2 = microtime(true);
        $value2 = $this->cache->get($key, function() {
            usleep(50000);
            return ['computed' => true, 'time' => time() + 1000];
        }, 300);
        $time2 = (microtime(true) - $start2) * 1000;

        // Проверим Redis
        $redis = $this->cache->getRedis();
        $keys = $redis->keys('app:perf_test_*');

        return $this->json([
            'first_call_ms' => round($time1, 2),
            'second_call_ms' => round($time2, 2),
            'speedup' => round($time1 / $time2, 2) . 'x',
            'values_match' => $value1 === $value2,
            'cached_correctly' => $value1['time'] === $value2['time'],
            'keys_in_redis' => count($keys),
            'success' => $time2 < 5 && $value1 === $value2 && count($keys) > 0
        ]);
    }
}