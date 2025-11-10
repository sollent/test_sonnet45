# 🚀 Настройка Doctrine кеширования (Dev + Prod)

> **Цель**: Настроить многоуровневое кеширование Doctrine для разных окружений с оптимальной производительностью

**Версия**: 1.0
**Дата**: 2025-01-10
**Автор**: Claude Code AI (Sonnet 4.5)

---

## 📖 Содержание

1. [Что такое Doctrine кеширование](#что-такое-doctrine-кеширование)
2. [Типы кешей в Doctrine](#типы-кешей-в-doctrine)
3. [Структура конфигурации](#структура-конфигурации)
4. [Реализация по шагам](#реализация-по-шагам)
5. [Тестирование кеша](#тестирование-кеша)
6. [Troubleshooting](#troubleshooting)
7. [Оптимизация для Production](#оптимизация-для-production)

---

## 🎯 Что такое Doctrine кеширование

Doctrine ORM использует **3 типа кешей** для ускорения работы с БД:

```
┌─────────────────────────────────────────────────────┐
│              Doctrine ORM Caching                    │
├─────────────────────────────────────────────────────┤
│                                                      │
│  1. Query Cache    → Кеш парсинга DQL в SQL         │
│     (всегда нужен)    Парсит: "SELECT t FROM Task" │
│                       Кеш: SQL "SELECT * FROM task" │
│                                                      │
│  2. Metadata Cache → Кеш метаданных Entity          │
│     (критично!)       Читает аннотации PHP классов  │
│                       Кеш: структура таблиц         │
│                                                      │
│  3. Result Cache   → Кеш результатов запросов       │
│     (опционально)     Выполняет: SQL запрос         │
│                       Кеш: массив данных из БД      │
│                                                      │
└─────────────────────────────────────────────────────┘
```

### Зачем нужно кеширование?

**Без кеша:**
```php
// Каждый раз:
// 1. Парсим DQL → SQL (5-10ms)
// 2. Читаем аннотации Entity (10-20ms)
// 3. Выполняем SQL в БД (50-200ms)
// ИТОГО: 65-230ms
```

**С кешем:**
```php
// Первый раз: 65-230ms (заполняем кеш)
// Последующие: 1-5ms (из кеша)
// ПРИРОСТ: 10-100x быстрее! 🚀
```

---

## 📦 Типы кешей в Doctrine

### 1. Query Cache (парсинг DQL)

**Что кеширует**: Парсинг DQL запросов в SQL

```php
// DQL запрос
$query = $em->createQuery('SELECT t FROM App\Entity\Task t WHERE t.user = :user');

// Doctrine делает:
// 1. Парсит DQL → AST → SQL (БЕЗ кеша: 5-10ms)
// 2. Сохраняет SQL в кеш
// 3. При повторном запросе берет SQL из кеша (0.1ms)
```

**Рекомендация**:
- ✅ **Всегда включать** в dev и prod
- 🎯 Адаптер: `cache.adapter.filesystem` (dev), `cache.adapter.apcu` (prod)
- ⏱️ TTL: Бесконечный (не меняется пока код не изменится)

---

### 2. Metadata Cache (метаданные Entity)

**Что кеширует**: Структура Entity классов (аннотации, mapping)

```php
// При загрузке Task Entity, Doctrine:
// 1. Читает аннотации #[ORM\Entity], #[ORM\Column] (БЕЗ кеша: 10-20ms)
// 2. Парсит relationships (OneToMany, ManyToOne)
// 3. Сохраняет в кеш
// 4. При повторной загрузке берет из кеша (0.1ms)
```

**Рекомендация**:
- ✅ **КРИТИЧНО для prod** (без него performance деградирует в 5-10 раз!)
- ⚠️ В dev можно отключить (чтобы видеть изменения в Entity сразу)
- 🎯 Адаптер: `cache.adapter.filesystem` (dev), `cache.adapter.apcu` (prod)
- ⏱️ TTL: 1 час (prod), infinite (dev с auto-refresh)

---

### 3. Result Cache (результаты запросов)

**Что кеширует**: Сами данные из БД (массивы/объекты)

```php
// Запрос с Result Cache
$tags = $this->createQueryBuilder('t')
    ->where('t.user = :user')
    ->setParameter('user', $user)
    ->getQuery()
    ->enableResultCache(300, 'user_tags_' . $user->getId()) // 5 минут
    ->getResult();

// Doctrine делает:
// 1. Проверяет кеш по ключу 'user_tags_123'
// 2. Если есть - возвращает из кеша (1ms)
// 3. Если нет - выполняет SQL, сохраняет в кеш, возвращает (50-200ms)
```

**Рекомендация**:
- ⚠️ **Опционально** - включать только для медленных запросов
- ❌ **НЕ использовать в dev** (усложняет отладку - видишь старые данные)
- ✅ **Использовать в prod** для аналитики, списков тегов, статики
- 🎯 Адаптер: `cache.adapter.array` (dev), `cache.adapter.apcu` (prod)
- ⏱️ TTL: 5-10 минут (prod)

---

## 📁 Структура конфигурации

Symfony поддерживает **разделение конфигов по окружениям**:

```
apps/backend/config/packages/
├── doctrine.yaml                    # Базовая конфигурация (все окружения)
│
├── dev/
│   └── doctrine.yaml                # Переопределения для dev
│
├── prod/
│   └── doctrine.yaml                # Переопределения для prod
│
└── test/
    └── doctrine.yaml                # Переопределения для test
```

**Как это работает:**

1. Symfony загружает `config/packages/doctrine.yaml` (базовые настройки)
2. Затем загружает `config/packages/{env}/doctrine.yaml` (переопределяет base)
3. Итоговый конфиг = base + env-specific

**Пример:**
```yaml
# Base (doctrine.yaml)
doctrine:
    orm:
        auto_mapping: true

# Dev override (dev/doctrine.yaml)
doctrine:
    orm:
        auto_mapping: true              # Наследуется из base
        auto_generate_proxy_classes: true  # Добавляется только для dev
```

---

## 🔧 Реализация по шагам

### Шаг 1: Базовая конфигурация (все окружения)

**Файл**: `apps/backend/config/packages/doctrine.yaml`

```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'

        # Профилирование запросов (автоматически включается в debug режиме)
        profiling_collect_backtrace: '%kernel.debug%'

    orm:
        # Авто-генерация proxy классов (будет переопределено в prod)
        auto_generate_proxy_classes: true

        # Стратегия именования таблиц (snake_case)
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware

        # Автоматическое обнаружение Entity
        auto_mapping: true

        # Mapping для Entity классов
        mappings:
            App:
                type: attribute          # Используем PHP 8 атрибуты
                is_bundle: false
                dir: '%kernel.project_dir%/src/Entity'
                prefix: 'App\Entity'
                alias: App
```

**Что здесь:**
- ✅ Основные настройки подключения к БД
- ✅ Профилирование для debug режима
- ✅ Mapping для Entity классов
- ⚠️ **НЕТ настроек кеша** (будут в dev/prod конфигах)

---

### Шаг 2: Development конфигурация

**Файл**: `apps/backend/config/packages/dev/doctrine.yaml`

```yaml
doctrine:
    orm:
        # Query Cache - кеш парсинга DQL
        # Включаем для ускорения, используем filesystem (не требует расширений)
        query_cache_driver:
            type: pool
            pool: doctrine.system_cache_pool

        # Metadata Cache - НЕ включаем в dev
        # Чтобы изменения в Entity сразу применялись без cache:clear
        # metadata_cache_driver:
        #     type: pool
        #     pool: doctrine.system_cache_pool

        # Result Cache - используем array (очищается каждый request)
        # Можно временно закомментировать если мешает отладке
        result_cache_driver:
            type: pool
            pool: doctrine.result_cache_pool

framework:
    cache:
        pools:
            # Query Cache + Metadata Cache - filesystem (быстро, не требует расширений)
            doctrine.system_cache_pool:
                adapter: cache.adapter.filesystem

            # Result Cache - array (автоматически очищается при каждом запросе)
            doctrine.result_cache_pool:
                adapter: cache.adapter.array
                # default_lifetime не нужен для array adapter
```

**Особенности Dev конфигурации:**

✅ **Query Cache включен** - парсинг DQL кешируется
❌ **Metadata Cache выключен** - изменения Entity видны сразу
⚠️ **Result Cache = array** - очищается при каждом запросе (не мешает отладке)

**Когда временно отключить Result Cache в dev:**
```yaml
# Закомментируй эти строки если Result Cache мешает отладке:
# result_cache_driver:
#     type: pool
#     pool: doctrine.result_cache_pool
```

---

### Шаг 3: Production конфигурация

**Файл**: `apps/backend/config/packages/prod/doctrine.yaml`

```yaml
doctrine:
    orm:
        # ВАЖНО! Отключаем авто-генерацию proxy в prod
        auto_generate_proxy_classes: false

        # Query Cache - APCu (самый быстрый)
        query_cache_driver:
            type: pool
            pool: doctrine.system_cache_pool

        # Metadata Cache - APCu (КРИТИЧНО для prod!)
        metadata_cache_driver:
            type: pool
            pool: doctrine.system_cache_pool

        # Result Cache - APCu с TTL 5 минут
        result_cache_driver:
            type: pool
            pool: doctrine.result_cache_pool

framework:
    cache:
        pools:
            # Query + Metadata Cache - APCu, долгое хранение
            doctrine.system_cache_pool:
                adapter: cache.adapter.apcu
                default_lifetime: 3600  # 1 час

            # Result Cache - APCu, короткое хранение
            doctrine.result_cache_pool:
                adapter: cache.adapter.apcu
                default_lifetime: 300  # 5 минут
```

**Особенности Prod конфигурации:**

✅ **auto_generate_proxy_classes: false** - proxy генерируются при deploy
✅ **Все 3 типа кеша включены** - максимальная производительность
✅ **APCu adapter** - самый быстрый (in-memory кеш)
✅ **TTL оптимизирован** - system cache долго (1 час), result cache коротко (5 минут)

---

### Шаг 4: Test конфигурация (опционально)

**Файл**: `apps/backend/config/packages/test/doctrine.yaml`

```yaml
doctrine:
    orm:
        # В тестах отключаем ВСЕ кеши для предсказуемости
        query_cache_driver:
            type: pool
            pool: doctrine.test_cache_pool

        result_cache_driver:
            type: pool
            pool: doctrine.test_cache_pool

framework:
    cache:
        pools:
            # Array cache - очищается после каждого теста
            doctrine.test_cache_pool:
                adapter: cache.adapter.array
```

**Зачем:**
- ✅ Тесты изолированы друг от друга
- ✅ Нет проблем с кешированными данными между тестами
- ✅ Array adapter = автоматическая очистка

---

## 🧪 Тестирование кеша

### Проверка 1: Убедиться что конфиги применились

```bash
# В dev окружении
docker exec backend-php83 php bin/console debug:config doctrine

# Проверь что в выводе:
# - query_cache_driver: pool (doctrine.system_cache_pool)
# - result_cache_driver: pool (doctrine.result_cache_pool)
# - metadata_cache_driver: null (отсутствует в dev)
```

---

### Проверка 2: Тест Query Cache (парсинг DQL)

```bash
# Включи Symfony Profiler и сделай запрос
curl -H "Authorization: Bearer YOUR_TOKEN" http://localhost:8089/api/tasks

# Открой Profiler в браузере: http://localhost:8089/_profiler
# Перейди в раздел "Doctrine"
# Проверь:
# - Query Cache hits > 0 (после 2-го запроса)
# - Parse time уменьшилось
```

---

### Проверка 3: Тест Result Cache (данные из БД)

**Шаг 1:** Включи Result Cache в одном запросе

```php
// В TaskRepository добавь временно:
public function findUserTags(User $user): array
{
    return $this->createQueryBuilder('t')
        ->where('t.user = :user')
        ->setParameter('user', $user)
        ->getQuery()
        ->enableResultCache(60, 'test_result_cache') // 60 секунд
        ->getResult();
}
```

**Шаг 2:** Проверь в Profiler

```bash
# Первый запрос (cache miss)
curl http://localhost:8089/api/tasks

# Открой Profiler → Doctrine → Cache
# Должно быть: Result Cache Misses: 1

# Второй запрос (cache hit)
curl http://localhost:8089/api/tasks

# Открой Profiler → Doctrine → Cache
# Должно быть: Result Cache Hits: 1
```

---

### Проверка 4: Замер скорости

```bash
# БЕЗ кеша (отключи в dev/doctrine.yaml)
time curl -H "Authorization: Bearer TOKEN" http://localhost:8089/api/analytics/dashboard
# Запомни время (например: 0.250s)

# С КЕШЕМ (включи обратно)
docker exec backend-php83 php bin/console cache:clear
time curl -H "Authorization: Bearer TOKEN" http://localhost:8089/api/analytics/dashboard
# Первый запрос: ~0.250s (заполняет кеш)

time curl -H "Authorization: Bearer TOKEN" http://localhost:8089/api/analytics/dashboard
# Второй запрос: ~0.050s (из кеша) ← 5x быстрее!
```

---

## 🐛 Troubleshooting

### Проблема 1: "APCu not enabled"

**Ошибка:**
```
Cache adapter "cache.adapter.apcu" is not available on your system.
```

**Решение:**

```dockerfile
# В docker/backend/Dockerfile добавь:
RUN pecl install apcu \
    && docker-php-ext-enable apcu

# Пересобери контейнер:
docker-compose build backend-php83
docker-compose up -d
```

**Проверка:**
```bash
docker exec backend-php83 php -m | grep apcu
# Должно вывести: apcu
```

---

### Проблема 2: Кеш не очищается

**Симптом:** Вижу старые данные после изменений в БД

**Решение:**

```bash
# Очисти все кеши
docker exec backend-php83 php bin/console cache:clear

# Очисти только Result Cache
docker exec backend-php83 php bin/console cache:pool:clear doctrine.result_cache_pool

# Или в коде - инвалидируй конкретный ключ:
$cacheProvider->delete('user_tasks_123');
```

---

### Проблема 3: Медленные запросы в prod

**Симптом:** После deploy запросы медленные (первые 10-20 минут)

**Причина:** Кеш пустой после deploy

**Решение:** Прогрей кеш после deploy

```bash
# Добавь в deploy скрипт:
docker exec backend-php83 php bin/console cache:warmup

# Или создай команду для прогрева важных кешей:
docker exec backend-php83 php bin/console app:cache:warmup
```

---

### Проблема 4: Изменения Entity не видны

**Симптом:** Добавил новое поле в Entity, но получаю ошибку

**Причина:** Metadata Cache не обновился

**Решение:**

```bash
# В dev - просто перезагрузи страницу (Metadata Cache выключен)

# В prod - очисти Metadata Cache:
docker exec backend-php83 php bin/console cache:pool:clear doctrine.system_cache_pool

# Или полная очистка:
docker exec backend-php83 php bin/console cache:clear
```

---

## 🚀 Оптимизация для Production

### 1. Проверка что APCu работает

```bash
# Проверь размер APCu кеша
docker exec backend-php83 php -r "var_dump(apcu_cache_info());"

# Должно показать:
# - num_slots: 4099
# - ttl: 0
# - num_entries: > 0 (после нескольких запросов)
```

---

### 2. Увеличение размера APCu (если нужно)

**Файл:** `docker/backend/php.ini` (создать если нет)

```ini
[apcu]
apc.enabled = 1
apc.shm_size = 128M        # Увеличь если видишь "cache full"
apc.ttl = 3600             # 1 час
apc.gc_ttl = 600           # Garbage collection
apc.enable_cli = 1         # Для Symfony console
```

**Добавь в Dockerfile:**
```dockerfile
COPY php.ini /usr/local/etc/php/conf.d/custom.ini
```

---

### 3. Мониторинг кеша в prod

Создай endpoint для проверки статистики кеша:

```php
// src/Controller/Admin/CacheStatsController.php
#[Route('/admin/cache-stats', name: 'cache_stats')]
public function stats(): JsonResponse
{
    $info = apcu_cache_info();

    return $this->json([
        'cache_type' => 'APCu',
        'memory_usage' => round($info['mem_size'] / 1024 / 1024, 2) . ' MB',
        'entries' => $info['num_entries'],
        'hits' => $info['num_hits'],
        'misses' => $info['num_misses'],
        'hit_rate' => round(($info['num_hits'] / ($info['num_hits'] + $info['num_misses'])) * 100, 2) . '%',
    ]);
}
```

**Проверка:**
```bash
curl http://localhost:8089/admin/cache-stats
# {
#   "cache_type": "APCu",
#   "memory_usage": "45.23 MB",
#   "entries": 1234,
#   "hits": 98765,
#   "misses": 1234,
#   "hit_rate": "98.77%"
# }
```

**Хороший hit_rate**: > 95%

---

### 4. Очистка кеша по расписанию

Добавь cron job для очистки старого кеша:

```bash
# Cron каждую ночь в 3:00
0 3 * * * docker exec backend-php83 php bin/console cache:pool:prune
```

---

## 📊 Ожидаемые результаты

### До настройки кеша:

- GET /api/tasks: **80-100ms**
- GET /api/analytics/dashboard: **200-300ms**
- Query парсинг: **5-10ms** на каждый запрос
- Metadata loading: **10-20ms** на каждую Entity

### После настройки (dev с array cache):

- GET /api/tasks: **60-80ms** (Query Cache ускоряет)
- GET /api/analytics/dashboard: **150-250ms**
- Query парсинг: **0.1ms** (из кеша)
- Metadata loading: **10-20ms** (не кешируется в dev)

### После настройки (prod с APCu):

- GET /api/tasks (первый): **80-100ms** (заполняет кеш)
- GET /api/tasks (повторный): **10-20ms** ✅ **4-8x быстрее!**
- GET /api/analytics/dashboard (повторный): **30-50ms** ✅ **6-10x быстрее!**
- Query парсинг: **0.1ms** (из APCu)
- Metadata loading: **0.1ms** (из APCu)

---

## 📋 Чеклист реализации

- [ ] Создан базовый `config/packages/doctrine.yaml`
- [ ] Создан dev конфиг `config/packages/dev/doctrine.yaml`
- [ ] Создан prod конфиг `config/packages/prod/doctrine.yaml`
- [ ] (Опционально) Создан test конфиг `config/packages/test/doctrine.yaml`
- [ ] Очищен кеш: `php bin/console cache:clear`
- [ ] Проверена конфигурация: `php bin/console debug:config doctrine`
- [ ] Протестирован Query Cache через Profiler
- [ ] Протестирован Result Cache (если включен)
- [ ] Замерено время отклика до/после
- [ ] Проверено что APCu установлен (для prod): `php -m | grep apcu`
- [ ] Добавлен мониторинг cache stats (опционально)
- [ ] Обновлена документация в README (если нужно)

---

## 🔗 Связанные документы

- [Performance Optimization Plan](PERFORMANCE_OPTIMIZATION_PLAN.md) - Общий план оптимизации
- [Backend Architecture](../../backend/ARCHITECTURE.md) - Архитектура backend
- [Development Workflow](../DEVELOPMENT_WORKFLOW.md) - Workflow разработки

---

## 📚 Полезные ссылки

- [Doctrine Caching Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/caching.html)
- [Symfony Cache Component](https://symfony.com/doc/current/components/cache.html)
- [APCu Extension](https://www.php.net/manual/en/book.apcu.php)

---

## 🏆 Заключение

Настройка Doctrine кеширования дает **4-10x прирост производительности** для повторных запросов!

**Ключевые моменты:**
1. ✅ **Dev**: Query Cache (filesystem) + Result Cache отключен или array
2. ✅ **Prod**: Все 3 типа кеша (APCu) для максимальной скорости
3. ✅ **APCu** - самый быстрый adapter для production
4. ✅ **Раздельные конфиги** позволяют гибко настраивать каждое окружение

**Следующие шаги:**
1. Реализовать настройки из этого документа
2. Протестировать производительность
3. При необходимости добавить Redis для Application Cache (следующий уровень)

---

*Создано: 2025-01-10*
*Автор: Claude Code AI (Sonnet 4.5)*
*Оценка времени реализации: 30-45 минут*
