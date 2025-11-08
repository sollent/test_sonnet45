# 🚀 План оптимизации производительности Backend

> **Цель**: Оптимизировать приложение для работы с 2 миллионами задач без Redis кеширования

---

## 📊 Выявленные критические проблемы

### 🔴 N+1 проблемы (КРИТИЧНО!)

1. **Task Entity - EAGER loading subtasks** (`Task.php:76`)
   - `fetch: 'EAGER'` загружает ВСЕ подзадачи рекурсивно
   - При загрузке 100 tasks → 100+ отдельных запросов для subtasks!

2. **TaskRepository::loadSubtasksRecursively()** (`TaskRepository.php:356-371`)
   - Рекурсивная загрузка БЕЗ JOIN
   - Каждый уровень вложенности = новый запрос

3. **AnalyticsService::calculateStreak()** (`AnalyticsService.php:244-262`)
   - В ЦИКЛЕ до 365 итераций - запрос к БД на каждый день!
   - 365 SELECT запросов для одного streak!

4. **AnalyticsService::getCompletionTimelineData()** (`TaskRepository.php:754-821`)
   - В цикле по дням делает 3 отдельных SELECT для каждого дня
   - 30 дней × 3 запроса = 90 запросов!

5. **AnalyticsService::getTopTags()** (`AnalyticsService.php:138-156`)
   - Для каждого тега отдельный запрос статистики
   - 5 тегов = 5 дополнительных запросов

6. **TaskService::completeSubtasksRecursively()** (`TaskService.php:255-265`)
   - Рекурсия по subtasks БЕЗ предварительной загрузки

### 🟡 Отсутствующие индексы

7. **Task таблица** - нет реальных индексов в миграции!
   - Документация упоминает 13 индексов, но их нет в коде
   - Нужны composite indexes для: user_id, status, priority, due_date, parent_task_id

8. **Tag таблица** - нет индекса на user_id + name

### 🟠 Неоптимальные запросы

9. **TaskController::list()** - загружает tasks без JOIN tags
10. **TaskController::show()** - `findWithSubtasks()` делает рекурсию
11. **AnalyticsService::getDashboardData()** - последовательные запросы вместо батчинга

---

## 🎯 План оптимизации (7 этапов)

---

## **ЭТАП 1: Генерация тестовых данных (2M задач)**

### 📝 Задача
Создать Symfony команды для генерации:
- 50 пользователей
- 2,000,000 задач (40,000 задач на пользователя)
- История за 3 года использования (2022-2025)
- Реалистичные данные: subtasks (3-5 уровней), tags, dates, statuses, priorities

### ✅ Критерии выполнения
- Команда: `php bin/console app:generate-test-data --users=50 --tasks-per-user=40000`
- С прогресс-баром и статистикой
- Батчинг вставок (по 1000 задач) для скорости
- Генерация через Foundry factories
- Опциональный флаг `--clear-existing` для очистки перед генерацией

### 📂 Файлы для создания
- `src/Command/GenerateTestDataCommand.php`
- `src/DataFixtures/PerformanceTestDataFixture.php` (опционально)

### ⏱️ Оценка времени: 3-4 часа

---

## **ЭТАП 2: Исправление N+1 проблем в Task Entity**

### 📝 Задача
Убрать EAGER loading и добавить явные JOIN в репозиториях

### 🔧 Изменения

**1. Task.php:76** - Убрать `fetch: 'EAGER'`
```php
// БЫЛО:
#[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parentTask', cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EAGER')]

// СТАЛО:
#[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parentTask', cascade: ['persist', 'remove'], orphanRemoval: true)]
```

**2. TaskRepository::findWithSubtasks()** - Переписать на PostgreSQL CTE
```php
// Использовать рекурсивный CTE для загрузки всех subtasks одним запросом:
WITH RECURSIVE subtask_tree AS (
    SELECT * FROM task WHERE id = :id
    UNION ALL
    SELECT t.* FROM task t
    INNER JOIN subtask_tree st ON t.parent_task_id = st.id
)
SELECT * FROM subtask_tree;
```

**3. Все методы TaskRepository** - добавить `leftJoin` для tags, user где нужно:
```php
->leftJoin('t.tags', 'tag')
->leftJoin('t.user', 'u')
->addSelect('tag')
->addSelect('u')
```

### ✅ Критерии выполнения
- Загрузка 100 задач с subtasks: было 500+ запросов → стало 2-3 запроса
- Профилирование через Symfony Profiler Web Debug Toolbar
- Запустить команду `php bin/console doctrine:query:log` и проверить количество запросов

### 📂 Файлы для изменения
- `src/Entity/Task.php`
- `src/Repository/Database/TaskRepository.php`

### ⏱️ Оценка времени: 2-3 часа

---

## **ЭТАП 3: Оптимизация AnalyticsService (убрать циклы с запросами)**

### 📝 Задача
Переписать методы с циклами на батчевые запросы

### 🔧 Изменения

**1. calculateStreak() - ONE запрос вместо 365**
```php
// Использовать GROUP BY DATE(completed_at) и найти максимальную непрерывную последовательность
SELECT DATE(completed_at) as completion_date, COUNT(*) as count
FROM task
WHERE user_id = :userId AND completed_at IS NOT NULL
GROUP BY DATE(completed_at)
ORDER BY completion_date DESC
```

**2. getCompletionTimelineData() - ONE запрос для всех дней**
```php
// Использовать GROUP BY DATE вместо цикла:
SELECT
    DATE(created_at) as date,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    COUNT(*) as created
FROM task
WHERE user_id = :userId AND created_at BETWEEN :start AND :end
GROUP BY DATE(created_at)
```

**3. getTopTags() - JOIN вместо N запросов**
```php
// Загрузить статистику всех тегов одним запросом через JOIN
SELECT
    tag.id, tag.name, tag.color,
    COUNT(t.id) as total,
    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed
FROM tag
LEFT JOIN task_tags tt ON tt.tag_id = tag.id
LEFT JOIN task t ON t.id = tt.task_id
WHERE tag.user_id = :userId
GROUP BY tag.id
ORDER BY total DESC
LIMIT :limit
```

### ✅ Критерии выполнения
- Dashboard analytics: было 150+ запросов → стало 10-15 запросов
- Время отклика `/api/analytics/dashboard`: < 200ms (сейчас 3-5 секунд!)

### 📂 Файлы для изменения
- `src/Service/AnalyticsService.php`
- `src/Repository/Database/TaskRepository.php`

### ⏱️ Оценка времени: 4-5 часов

---

## **ЭТАП 4: Добавление композитных индексов в БД**

### 📝 Задача
Создать миграцию с 15+ композитными индексами для Task таблицы

### 🔧 Индексы для создания

```sql
-- Базовые композитные индексы
CREATE INDEX idx_task_user_parent ON task (user_id, parent_task_id);
CREATE INDEX idx_task_user_status ON task (user_id, status);
CREATE INDEX idx_task_user_priority ON task (user_id, priority);
CREATE INDEX idx_task_user_archived ON task (user_id, is_archived);
CREATE INDEX idx_task_user_due_date ON task (user_id, due_date);
CREATE INDEX idx_task_user_completed_at ON task (user_id, completed_at);
CREATE INDEX idx_task_user_created_at ON task (user_id, created_at);

-- Композитные индексы для фильтрации
CREATE INDEX idx_task_user_parent_archived ON task (user_id, parent_task_id, is_archived);
CREATE INDEX idx_task_user_parent_status ON task (user_id, parent_task_id, status);
CREATE INDEX idx_task_user_status_archived ON task (user_id, status, is_archived);
CREATE INDEX idx_task_user_due_status ON task (user_id, due_date, status);

-- Индексы для аналитики
CREATE INDEX idx_task_completed_date ON task (user_id, completed_at) WHERE completed_at IS NOT NULL;
CREATE INDEX idx_task_overdue ON task (user_id, due_date, status) WHERE status != 'completed';

-- Partial indexes для оптимизации
CREATE INDEX idx_task_active ON task (user_id, due_date) WHERE is_archived = false;
CREATE INDEX idx_task_parent_null ON task (user_id, status) WHERE parent_task_id IS NULL;

-- Tag table index
CREATE INDEX idx_tag_user_name ON tag (user_id, name);

-- Task_tags junction table
CREATE INDEX idx_task_tags_task ON task_tags (task_id);
CREATE INDEX idx_task_tags_tag ON task_tags (tag_id);
```

### ✅ Критерии выполнения
- Создать миграцию: `php bin/console make:migration`
- Применить: `php bin/console doctrine:migrations:migrate`
- Проверить EXPLAIN ANALYZE для тяжелых запросов:
  ```sql
  EXPLAIN ANALYZE
  SELECT * FROM task
  WHERE user_id = 1 AND parent_task_id IS NULL AND is_archived = false
  ORDER BY due_date ASC;
  ```
- Убедиться что используется Index Scan вместо Seq Scan

### 📂 Файлы для создания
- `migrations/VersionYYYYMMDDHHMMSS_AddPerformanceIndexes.php`

### ⏱️ Оценка времени: 1-2 часа

---

## **ЭТАП 5: EXPLAIN ANALYZE тяжелых запросов + доработка индексов**

### 📝 Задача
Проанализировать самые медленные запросы через EXPLAIN ANALYZE и добавить недостающие индексы

### 🔧 Запросы для анализа

1. **GET /api/tasks?view=all** (список задач)
2. **GET /api/tasks/{id}** (одна задача с subtasks)
3. **GET /api/analytics/dashboard** (полная аналитика)
4. **GET /api/analytics/completion-timeline?period=365**
5. **GET /api/analytics/productivity-heatmap?year=2024**
6. **GET /api/tasks/overdue** (просроченные)
7. **POST /api/tasks/{id}/toggle** (toggle completion with recursive subtasks)

### 📋 Для каждого запроса:

```bash
# 1. Включить Doctrine SQL логирование
docker exec backend-php83 php bin/console doctrine:query:log

# 2. Сделать запрос через API
curl http://localhost:8089/api/tasks?view=all

# 3. Скопировать SQL из логов в PostgreSQL
docker exec -it backend-psql16 psql -U user -d backend-app

# 4. Выполнить EXPLAIN ANALYZE
EXPLAIN (ANALYZE, BUFFERS, VERBOSE)
<скопированный SQL запрос>;

# 5. Проанализировать план выполнения:
# - Seq Scan → добавить индекс
# - Execution time > 100ms → оптимизировать
# - Rows estimation != actual → ANALYZE table
```

### ✅ Критерии выполнения
- Все запросы используют Index Scan (не Seq Scan)
- Execution time < 50ms для простых запросов
- Execution time < 200ms для аналитики
- Создать отчет `PERFORMANCE_REPORT.md` с результатами EXPLAIN

### 📂 Файлы для создания
- `docs/guides/PERFORMANCE_REPORT.md`
- Дополнительные миграции с индексами по необходимости

### ⏱️ Оценка времени: 3-4 часа

---

## **ЭТАП 6: Batch loading для связей (DataLoader pattern)**

### 📝 Задача
Реализовать батчевую загрузку tags и mediaObjects для массивов Task

### 🔧 Что сделать

**1. Создать TagBatchLoader**
```php
class TagBatchLoader
{
    public function loadForTasks(array $tasks): void
    {
        $taskIds = array_map(fn($t) => $t->getId(), $tasks);

        // ONE query для всех tags всех tasks
        $tags = $this->em->createQuery('
            SELECT t, tag
            FROM App\Entity\Task t
            JOIN t.tags tag
            WHERE t.id IN (:ids)
        ')->setParameter('ids', $taskIds)->getResult();

        // Уже загружены в persistence context
    }
}
```

**2. Использовать в TaskController::list()**
```php
public function list(...): JsonResponse
{
    $tasks = $this->taskRepository->findActiveTasks(...);

    // Batch load tags для всех tasks одним запросом
    $this->tagBatchLoader->loadForTasks($tasks);

    // Теперь $task->getTags() не делает доп запросы!
    $dtos = array_map(fn($t) => TaskResponseDto::fromEntity($t), $tasks);

    return $this->json($dtos);
}
```

**3. То же для User, MediaObjects, Attachments**

### ✅ Критерии выполнения
- GET /api/tasks: было 1 + N запросов → стало 2-3 запроса ВСЕГО
- Batch loader переиспользуется во всех контроллерах

### 📂 Файлы для создания
- `src/Service/Loader/TagBatchLoader.php`
- `src/Service/Loader/MediaBatchLoader.php`

### ⏱️ Оценка времени: 2-3 часа

---

## **ЭТАП 7: Pagination + DTO optimization**

### 📝 Задача
Добавить пагинацию везде + оптимизировать DTO сериализацию

### 🔧 Изменения

**1. Лимит по умолчанию для всех списков**
```php
// TaskRepository::findActiveTasks() - всегда с лимитом
public function findActiveTasks(User $user, ?TaskFilterDto $filters = null, int $limit = 20, int $offset = 0): array
```

**2. Не загружать subtasks в списках (только в show)**
```php
// В TaskController::list()
TaskResponseDto::fromEntity($task, includeSubtasks: false)

// В TaskController::show()
TaskResponseDto::fromEntity($task, includeSubtasks: true)
```

**3. Partial select для больших полей**
```php
// Не загружать description в списках (экономит память)
$qb->select('PARTIAL t.{id, title, status, priority, dueDate}')
```

**4. Streaming для больших ответов**
```php
// Для analytics/dashboard - использовать iterate() вместо getResult()
foreach ($query->toIterable() as $row) {
    $entityManager->detach($row); // Освобождаем память
}
```

### ✅ Критерии выполнения
- Все списки возвращают max 100 items
- Память на запрос: < 50MB (было 200MB+)
- Response size уменьшен в 2-3 раза

### 📂 Файлы для изменения
- Все методы в `TaskRepository`
- Все list методы в `TaskController`
- `TaskResponseDto::fromEntity()` - добавить параметры для контроля

### ⏱️ Оценка времени: 2-3 часа

---

## **ДОПОЛНИТЕЛЬНЫЕ ОПТИМИЗАЦИИ (Опционально)**

### 8. HTTP/2 Server Push для критических ресурсов
### 9. Database Connection Pooling (PgBouncer)
### 10. Partial indexes для часто используемых фильтров
### 11. Materialized Views для сложной аналитики
### 12. Query result caching (Doctrine Result Cache) - без Redis, in-memory
### 13. VACUUM ANALYZE задачи для PostgreSQL (cron job)
### 14. Compression для API responses (gzip)

---

## 📈 Ожидаемые результаты

### До оптимизации (с 2M задач):
- GET /api/tasks: **3-5 секунд**, 500+ SQL запросов
- GET /api/analytics/dashboard: **15-30 секунд**, 300+ SQL запросов
- Memory usage: **200-500 MB** per request

### После оптимизации:
- GET /api/tasks: **< 200ms**, 3-5 SQL запросов ✅
- GET /api/analytics/dashboard: **< 500ms**, 10-15 SQL запросов ✅
- Memory usage: **< 50 MB** per request ✅

### Производительность:
- **100x ускорение** для списков задач
- **30-50x ускорение** для аналитики
- **80-90% снижение** количества SQL запросов
- **75% снижение** потребления памяти

---

## 🎯 Порядок выполнения

1. **ЭТАП 1** → Генерируем тестовые данные (база для замеров)
2. **ЭТАП 2** → Исправляем N+1 в Task Entity (быстрый win)
3. **ЭТАП 4** → Добавляем индексы (критично!)
4. **ЭТАП 3** → Оптимизируем Analytics (убираем циклы)
5. **ЭТАП 5** → EXPLAIN ANALYZE + доработка индексов
6. **ЭТАП 6** → Batch loaders (полировка)
7. **ЭТАП 7** → Pagination + DTO optimization (финальная оптимизация)

---

## 📝 Чеклист после каждого этапа

- [ ] Запустить тесты: `php bin/console phpunit`
- [ ] Проверить количество SQL запросов через Profiler
- [ ] Замерить время отклика через `curl -w "@curl-format.txt"`
- [ ] Обновить `PERFORMANCE_REPORT.md` с метриками
- [ ] Сделать git commit с описанием оптимизации

---

## 🔗 Связанные документы

- [Database Schema](../backend/DATABASE.md)
- [Architecture](../backend/ARCHITECTURE.md)
- [Development Workflow](DEVELOPMENT_WORKFLOW.md)
- [Troubleshooting](TROUBLESHOOTING.md)

---

*Создано: 2025-11-08 by Claude Code AI (Sonnet 4.5)*
*План рассчитан на 20-25 часов работы*
