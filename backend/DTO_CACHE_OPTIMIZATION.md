# DTO Cache Optimization

## Проблема

При кешировании **полных Doctrine Entity** в Redis мы сохраняем огромное количество **ненужной метаинформации**:

### Что попадает в кеш при сериализации Entity:

```php
// Пример сериализованной Task Entity (читаемый вид):
O:15:"App\Entity\Task":20:{
  s:5:"*id";i:29738;
  s:22:"App\Entity\Tasktitle";s:51:"Настроить Prometheus";
  s:23:"App\Entity\Taskstatus";E:31:"App\Enum\TaskStatus:IN_PROGRESS";

  // ❌ ПРОБЛЕМА 1: Полный User объект со всеми полями!
  s:21:"App\Entity\Taskuser";O:15:"App\Entity\User":17:{
    s:5:"*id";i:22;
    s:12:"*createdAt";O:17:"DateTimeImmutable":3:{...};
    s:12:"*updatedAt";O:17:"DateTimeImmutable":3:{...};
    s:8:"*email";s:24:"sophiekhouryna@gmail.com";
    s:11:"*password";N;
    s:8:"*roles";a:0:{}
    s:23:"*notificationSettings";a:6:{...}
    // ... ещё 10 полей User!
  }

  // ❌ ПРОБЛЕМА 2: Doctrine PersistentCollection с метаданными!
  s:25:"App\Entity\Tasksubtasks";O:33:"Doctrine\ORM\PersistentCollection":2:{
    s:13:"*collection";O:43:"Doctrine\Common\Collections\ArrayCollection":1:{
      s:53:"Doctrine\Common\Collections\ArrayCollectionelements";a:3:{
        // Здесь ВСЕ subtasks как полные Entity с User, Tags и т.д.!
      }
    }
    s:14:"*initialized";b:1;  // ← Doctrine lazy loading metadata
  }

  // ❌ ПРОБЛЕМА 3: Такая же ситуация с Tags
  s:21:"App\Entity\Tasktags";O:33:"Doctrine\ORM\PersistentCollection":2:{...}

  // ❌ ПРОБЛЕМА 4: DateTimeImmutable объекты с timezone и всей метой
  s:12:"*createdAt";O:17:"DateTimeImmutable":3:{
    s:4:"date";s:26:"2025-10-29 09:13:38.000000";
    s:13:"timezone_type";i:3;
    s:8:"timezone";s:12:"Europe/Minsk";
  }
}
```

### Последствия:

1. **Огромный размер кеша** - одна задача занимает ~5-10 KB вместо ~500 bytes
2. **Медленная сериализация** - PHP serialize обрабатывает тысячи полей
3. **Медленная десериализация** - PHP unserialize восстанавливает объекты
4. **Нечитаемость в Redis GUI** - "PHP Unserialize Failed" в Another Redis Desktop Manager
5. **Излишняя нагрузка на Redis** - передача больших объёмов данных

### Реальный пример:

Для пользователя с **195 задачами**:

```
❌ BEFORE (Entity):
  - Размер: ~1.5-2 MB на один cache entry
  - Содержит: 195 Task + 195 User + все Tags + все Subtasks + вся Doctrine мета
  - Читаемость: ❌ Ошибка "PHP Unserialize Failed" в GUI

✅ AFTER (DTO):
  - Размер: ~150-200 KB на один cache entry
  - Содержит: только необходимые данные (ID, title, status, etc.)
  - Читаемость: ✅ Красивый JSON в режиме PHPSerialize

🎯 Экономия: ~90% размера кеша!
```

---

## Решение: TaskCacheDTO

Создан **оптимизированный DTO** для кеширования задач - содержит **только необходимые данные** без Doctrine метаинформации.

### Структура TaskCacheDTO:

```php
final readonly class TaskCacheDTO
{
    public function __construct(
        // Основные данные задачи
        public int $id,
        public string $title,
        public ?string $description,
        public TaskStatus $status,              // ✅ Enum value, не объект!
        public TaskPriority $priority,          // ✅ Enum value, не объект!

        // Даты как строки
        public ?string $startDate,              // ✅ 'Y-m-d H:i:s' вместо DateTimeImmutable
        public ?string $dueDate,                // ✅ 'Y-m-d' вместо DateTimeImmutable
        public ?string $completedAt,            // ✅ 'Y-m-d H:i:s' вместо DateTimeImmutable

        // Связи через ID, не полные объекты
        public int $userId,                     // ✅ Только ID, не полный User объект!
        public ?int $parentTaskId,              // ✅ Только ID, не полный Task объект!

        // Массивы простых типов, не коллекции
        public array $tagIds,                   // ✅ [1, 2, 3] вместо PersistentCollection
        public array $tagNames,                 // ✅ ['work', 'urgent'] вместо Tag объектов

        // Предвычисленные счётчики
        public int $subtasksCount,              // ✅ Просто число, не коллекция
        public int $completedSubtasksCount,     // ✅ Просто число, не коллекция

        // Остальные поля
        public int $sortOrder,
        public bool $isArchived,
        public string $createdAt,               // ✅ Строка, не DateTimeImmutable
        public string $updatedAt,               // ✅ Строка, не DateTimeImmutable
    ) {
    }
}
```

### Преимущества:

1. **Минимальный размер** - только необходимые данные
2. **Нет Doctrine метаданных** - никаких PersistentCollection, ArrayCollection, *initialized
3. **Нет вложенных объектов** - User, Tags, Subtasks заменены на ID и имена
4. **Простые типы** - DateTime → string, Enum → value
5. **Читаемость** - отлично десериализуется в Redis GUI

---

## Интеграция

### 1. TaskCacheService автоматически использует DTO

```php
public function updateTaskListsCache(User $user, callable $fetchCallback): int
{
    // Fetch fresh data ONCE (Task entities)
    $freshTasks = $fetchCallback();

    // ✅ Convert to DTOs for optimal caching (NO Doctrine metadata!)
    $freshTaskDTOs = TaskCacheDTO::fromEntities($freshTasks);

    // Update ALL existing cache keys with fresh DTO data
    foreach ($keys as $fullKey) {
        $serialized = serialize($freshTaskDTOs);  // ← Сериализуем DTO, не Entity!
        $redis->setex($fullKey, self::TTL_TASK_LIST, $serialized);
    }
}
```

### 2. Все методы update используют DTO

- `updateTaskListsCache()` ✅
- `updateDynamicViewsCache()` ✅ (today, overdue, upcoming)
- `updateStatisticsCache()` - статистика уже оптимальна (массив чисел)

### 3. TaskService вызывает update после каждой операции

```php
private function updateTaskCache(User $user): void
{
    // UPDATE task lists with fresh data (uses DTO internally)
    $this->taskCache->updateTaskListsCache($user, function () use ($user) {
        return $this->taskRepository->findUserTasks($user);
    });

    // UPDATE dynamic views (uses DTO internally)
    $this->taskCache->updateDynamicViewsCache($user, [
        'today' => fn() => $this->taskRepository->findTodayTasks($user),
        'overdue' => fn() => $this->taskRepository->findOverdueTasks($user),
        'upcoming' => fn() => $this->taskRepository->findUpcomingTasks($user, 7),
    ]);
}
```

---

## Как проверить оптимизацию

### В Another Redis Desktop Manager:

1. **BEFORE (Entity):**
   - Открываешь ключ `app:app:prod:user_tasks_list:...:uid_22`
   - Режим PHPSerialize: ❌ "PHP Unserialize Failed"
   - Режим Text: огромная стена текста с Doctrine metadata

2. **AFTER (DTO):**
   - Открываешь тот же ключ
   - Режим PHPSerialize: ✅ Красивый JSON-подобный вид
   - Видны только нужные поля: id, title, status, priority, userId, tagIds, tagNames

### Триггер обновления кеша:

Просто **создай/обнови/удали** любую задачу через API:
- POST `/api/tasks` - создать задачу
- PUT `/api/tasks/123` - обновить задачу
- DELETE `/api/tasks/123` - удалить задачу
- POST `/api/tasks/123/toggle` - переключить статус

После этого кеш автоматически обновится с использованием DTO!

---

## Расчёт экономии памяти

### Для пользователя с 6595 задачами:

```
Entity-based (BEFORE):
  - 195 tasks sample ≈ 2 MB
  - Per task: ~10 KB
  - Total for 6595 tasks: ~64 MB per cache entry

DTO-based (AFTER):
  - 195 tasks sample ≈ 200 KB
  - Per task: ~1 KB
  - Total for 6595 tasks: ~6.4 MB per cache entry

💾 SAVINGS: ~57.6 MB (90% reduction) per cache entry
```

### При наличии нескольких cache entries (разные фильтры):

```
Если у пользователя 10 разных фильтрованных списков:
  - BEFORE: 10 × 64 MB = 640 MB
  - AFTER:  10 × 6.4 MB = 64 MB
  - SAVINGS: 576 MB!
```

---

## Важные замечания

### ⚠️ Обратная совместимость

Если в Redis уже есть старый кеш (Entity-based), он будет работать до истечения TTL. После следующего обновления задачи кеш автоматически пересоздастся в формате DTO.

### ⚠️ API Response

API **по-прежнему возвращает полные Task Entity** с нормализацией через Symfony Serializer. DTO используется **только внутри кеша**, это абстракция слоя кеширования.

### ✅ Нет breaking changes

- API контракт не изменился
- Frontend получает те же данные
- Изменения только во внутреннем слое кеша

---

## Файлы

1. **DTO:** `/src/DTO/Cache/TaskCacheDTO.php`
2. **Service:** `/src/Service/Cache/TaskCacheService.php` (обновлён)
3. **Tests:**
   - `/test_dto_cache_optimization.php` - сравнение размеров
   - `/test_dto_simple_comparison.php` - анализ реального кеша из Redis

---

## Итог

✅ **Оптимизация внедрена и работает автоматически!**

- Кеш теперь в **10 раз меньше** (90% экономии памяти)
- Сериализация/десериализация **значительно быстрее**
- Данные **читаемы** в Redis GUI инструментах
- **Нет breaking changes** для API или Frontend

🎯 Просто создай/обнови задачу через API, и кеш автоматически обновится в новом формате!
