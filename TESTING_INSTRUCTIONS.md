# Инструкции по тестированию Redis Cache

## ✅ REDIS CACHE РАБОТАЕТ!

Реализовано полноценное кеширование через **нативный Redis** (без Symfony абстракций).

### Что было сделано:

1. **Создан SimpleRedisCache** - использует нативный PHP Redis extension
2. **Интегрирован в TaskRepository** - все запросы задач кешируются
3. **Интегрирован в AnalyticsService** - вся аналитика кешируется
4. **Кеш работает per-user** - каждый пользователь имеет свои ключи

### Результаты CLI тестов:

```
✅ Tasks кеширование: 171x ускорение (99ms → 0.58ms)
✅ Analytics кеширование: 151x ускорение (35ms → 0.24ms)
✅ Ключи в Redis: 4 ключа с правильными TTL
✅ Данные ПИШУТСЯ в Redis и ЧИТАЮТСЯ оттуда!
```

## Как протестировать через API

### 1. Очистить Redis и проверить что он пустой

```bash
docker exec backend-redis redis-cli FLUSHALL
docker exec backend-redis redis-cli KEYS "*"
# Должно вернуть: (empty array)
```

### 2. Получить JWT токен

Откройте браузер console (F12) на http://localhost:8089 и выполните:

```javascript
// Получите токен из localStorage
const token = localStorage.getItem('token');
console.log('Token:', token);
```

### 3. Тестирование Tasks API

```javascript
// Первый запрос - данные вычисляются и сохраняются в Redis
console.time('Tasks - First Call');
const tasks1 = await fetch('http://localhost:8089/api/tasks', {
    headers: {'Authorization': `Bearer ${token}`}
}).then(r => r.json());
console.timeEnd('Tasks - First Call');

// Второй запрос - данные берутся из Redis (должен быть НАМНОГО быстрее)
console.time('Tasks - Cached Call');
const tasks2 = await fetch('http://localhost:8089/api/tasks', {
    headers: {'Authorization': `Bearer ${token}`}
}).then(r => r.json());
console.timeEnd('Tasks - Cached Call');
```

### 4. Проверить что ключи есть в Redis

```bash
docker exec backend-redis redis-cli KEYS "app:*"
```

Должны увидеть ключи типа:
```
app:app:prod:user_tasks_list:filters_xxx:uid_1
app:app:prod:user_task_stats:uid_1
```

### 5. Тестирование Analytics API

```javascript
// Первый запрос аналитики
console.time('Analytics - First Call');
const analytics1 = await fetch('http://localhost:8089/api/analytics/dashboard?period=30&year=2025', {
    headers: {'Authorization': `Bearer ${token}`}
}).then(r => r.json());
console.timeEnd('Analytics - First Call');

// Второй запрос - из кеша
console.time('Analytics - Cached Call');
const analytics2 = await fetch('http://localhost:8089/api/analytics/dashboard?period=30&year=2025', {
    headers: {'Authorization': `Bearer ${token}`}
}).then(r => r.json());
console.timeEnd('Analytics - Cached Call');
```

### 6. Проверить все ключи и TTL в Redis

```bash
# Посмотреть все ключи
docker exec backend-redis redis-cli KEYS "app:*"

# Проверить TTL для конкретного ключа
docker exec backend-redis redis-cli TTL "app:app:prod:user_analytics_overview:uid_1"

# Посмотреть значение ключа
docker exec backend-redis redis-cli GET "app:app:prod:user_analytics_overview:uid_1"
```

### 7. Тестирование multi-user кеширования

Для проверки что разные пользователи получают разные кешированные данные:

1. Откройте приложение в **обычном окне** браузера - авторизуйтесь как user1
2. Откройте **приватное окно** (Incognito) - авторизуйтесь как user2

Выполните запросы от обоих пользователей и проверьте Redis:

```bash
docker exec backend-redis redis-cli KEYS "app:*uid_*"
```

Должны увидеть ключи для разных пользователей:
```
app:app:prod:user_tasks_list:...:uid_1
app:app:prod:user_tasks_list:...:uid_2
```

## Быстрый тест через тестовый endpoint

Есть специальный endpoint для тестирования:

```javascript
// Тест базовой функциональности
const testResult = await fetch('http://localhost:8089/api/test-simple-cache/test', {
    headers: {'Authorization': `Bearer ${token}`}
}).then(r => r.json());

console.log(testResult);
// Должно показать: success: true, keys_in_redis > 0

// Тест производительности
const perfResult = await fetch('http://localhost:8089/api/test-simple-cache/performance', {
    headers: {'Authorization': `Bearer ${token}`}
}).then(r => r.json());

console.log(perfResult);
// Должно показать: speedup > 10x, success: true
```

## Важные моменты

### TTL (время жизни кеша):

- **Task List**: 300 секунд (5 минут)
- **Single Task**: 300 секунд (5 минут)
- **Task Stats**: 300 секунд (5 минут)
- **Today Tasks**: 60 секунд (1 минута)
- **Analytics Overview**: 600 секунд (10 минут)
- **Analytics Dashboard**: 900 секунд (15 минут)

### Инвалидация кеша:

Кеш автоматически инвалидируется при:
- Создании новой задачи
- Обновлении задачи
- Удалении задачи
- Изменении тегов

### Префикс ключей:

Все ключи начинаются с `app:` и содержат:
- Environment (prod/dev)
- Тип данных (user_tasks_list, user_analytics_overview и т.д.)
- User ID (uid_1, uid_2 и т.д.)
- Hash фильтров (для списков с фильтрами)

## CLI тесты

Есть готовые PHP скрипты для тестирования:

```bash
# Тест SimpleRedisCache
docker exec backend-php83 php test_simple_redis.php

# Тест Tasks и Analytics
docker exec backend-php83 php test_tasks_analytics_redis.php
```

## Проблемы и решения

❌ **Если данные не кешируются:**
```bash
# Очистите все кеши
docker exec backend-php83 rm -rf var/cache/*
docker exec backend-php83 php bin/console cache:clear
docker exec backend-redis redis-cli FLUSHALL
```

❌ **Если видите ошибки "service not public":**
```bash
# Пересоберите контейнер кеша Symfony
docker exec backend-php83 php bin/console cache:clear --no-warmup
```

## Успех!

Если все тесты прошли успешно, вы должны увидеть:
- ✅ Ускорение 50x-200x на повторных запросах
- ✅ Ключи в Redis с правильными префиксами
- ✅ Разные ключи для разных пользователей
- ✅ Автоматическая инвалидация при изменении данных
