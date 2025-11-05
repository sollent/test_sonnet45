# Cache Strategy Analysis & Proposals

## Текущая проблема

При кешировании **полных Doctrine Entity** в Redis получаем:
- ❌ Огромный размер кеша (~10 KB на задачу)
- ❌ Doctrine метаданные (PersistentCollection, *initialized, etc.)
- ❌ Вложенные объекты (User, Tags, Subtasks) со всеми полями
- ❌ Нечитаемый формат в Redis GUI ("PHP Unserialize Failed")

**Пример текущего кеша:**
```
O:15:"App\Entity\Task":20:{
  s:21:"App\Entity\Taskuser";O:15:"App\Entity\User":17:{
    s:8:"*email";s:24:"sophiekhouryna@gmail.com";
    s:11:"*password";N;
    s:23:"*notificationSettings";a:6:{...}
    // ... ещё 10 полей User!
  }
  s:25:"App\Entity\Tasksubtasks";O:33:"Doctrine\ORM\PersistentCollection":2:{...}
}
```

---

## Предложенное решение: JSON-сериализация через Symfony Serializer

### Концепция

Вместо сериализации Entity через PHP `serialize()`, использовать **Symfony Serializer** с теми же группами сериализации, что используются для API responses.

### Процесс

```php
// 1. Entity → JSON (через Symfony Serializer)
$tasks = $repository->findUserTasks($user);
$json = $serializer->serialize($tasks, 'json', ['groups' => ['task:read']]);

// 2. Кешируем JSON
redis->set($key, $json);

// 3. Из кеша → массив или DTO
$cached = redis->get($key);
$data = json_decode($cached, true); // Вариант A: массив
// ИЛИ
$dtos = $serializer->deserialize($cached, TaskResponseDto::class.'[]', 'json'); // Вариант B: DTO
```

---

## Преимущества

1. ✅ **Единый источник правды** - используются те же группы сериализации, что и для API
2. ✅ **Нет дублирования логики** - не нужно поддерживать отдельный TaskCacheDTO
3. ✅ **Читаемый JSON в Redis** - красиво выглядит в GUI инструментах
4. ✅ **Готовые данные для API** - минимальная обработка перед отдачей на фронт
5. ✅ **Автоматическая синхронизация** - изменения в API response автоматически влияют на кеш
6. ✅ **Без Doctrine метаданных** - чистые данные без PersistentCollection и т.д.

---

## Подводные камни

### 1. Размер данных

**JSON vs PHP serialize:**
- JSON: `{"id":123,"title":"Task"}` - более читаемый
- PHP serialize: `a:2:{s:2:"id";i:123;s:5:"title";s:4:"Task";}` - компактнее

**Вердикт:** JSON немного больше, но **без Doctrine метаданных** всё равно получится значительная экономия.

### 2. CPU Overhead

**Десериализация:**
- PHP `unserialize()` - быстрее
- JSON `json_decode()` - чуть медленнее
- Symfony `$serializer->deserialize()` - ещё медленнее (создаёт объекты)

**Вердикт:** Для массивов (`json_decode($json, true)`) overhead минимален. Для DTO - больше.

### 3. Вложенные связи

**Проблема:**
```json
{
  "id": 123,
  "title": "Task",
  "user": {
    "id": 22,
    "name": "Sofia",
    "email": "sophie@..."
  }
}
```

При десериализации в DTO получим **новый User объект**, не связанный с Doctrine EntityManager.

**Последствия:**
- ✅ Для API response - **отлично** (просто данные)
- ❌ Для дальнейшей работы с Entity - **плохо** (нет связи с БД)

**Вердикт:** Подходит для кеширования **финальных данных для API**, но не для промежуточной обработки.

### 4. Инвалидация при изменении связей

**Проблема:**
Если User поменял имя (`"Sofia"` → `"Sofia K."`), в кеше Task останется старое имя.

**Решение:**
Нужно инвалидировать кеш Task при изменении User.

**Вердикт:** Требует дополнительной логики инвалидации.

### 5. Изменение сигнатур методов

**До:**
```php
// TaskService
public function getActiveTasks(User $user): array // array<Task>

// Controller
$tasks = $taskService->getActiveTasks($user);
foreach ($tasks as $task) {
    $task->getUser()->getName(); // ← работает, т.к. Task Entity
}
```

**После (Вариант A - массивы):**
```php
// TaskService
public function getActiveTasks(User $user): array // array (mixed)

// Controller
$tasksArray = $taskService->getActiveTasks($user);
// $tasksArray[0]['user']['name'] ← массив, нет type safety
```

**После (Вариант B - DTO):**
```php
// TaskService
public function getActiveTasks(User $user): array // array<TaskResponseDto>

// Controller
$tasksDtos = $taskService->getActiveTasks($user);
foreach ($tasksDtos as $dto) {
    $dto->user->name; // ← работает, type safe
}
```

**Вердикт:** Вариант B (DTO) сохраняет type safety, но требует изменения всех мест использования.

---

## Два варианта реализации

### Вариант A: JSON в кеше + массивы в коде

**Архитектура:**
```
DB (Entity) → Symfony Serializer → JSON → Redis
Redis → JSON → json_decode(true) → Array → Controller → JSON Response
```

**Код:**
```php
// TaskCacheService::getTaskList()
public function getTaskList(User $user, array $filters, callable $callback): array
{
    $key = $this->keyManager->buildTaskListKey($user, $filters);

    $cached = $this->redis->get($key);
    if ($cached) {
        return json_decode($cached, true); // ← массив!
    }

    // Fetch from DB
    $tasks = $callback(); // Task[] entities

    // Serialize to JSON
    $json = $this->serializer->serialize($tasks, 'json', [
        'groups' => ['task:read']
    ]);

    // Cache JSON
    $this->redis->setex($key, $ttl, $json);

    // Return array
    return json_decode($json, true);
}
```

**Controller:**
```php
public function list(Request $request): JsonResponse
{
    $tasksArray = $this->taskService->getActiveTasks($user);
    return $this->json($tasksArray); // ← просто массив!
}
```

**Плюсы:**
- ✅ Минимум кода
- ✅ Высокая производительность (нет создания объектов)
- ✅ Простота
- ✅ Меньше памяти

**Минусы:**
- ❌ Нет type safety
- ❌ IDE не подсказывает структуру
- ❌ Легко сделать ошибку (`$task['usr']` вместо `$task['user']`)

---

### Вариант B: JSON в кеше + DTO объекты

**Архитектура:**
```
DB (Entity) → TaskResponseDto → Symfony Serializer → JSON → Redis
Redis → JSON → Symfony Deserializer → TaskResponseDto[] → Controller → JSON Response
```

**Код:**
```php
// TaskCacheService::getTaskList()
public function getTaskList(User $user, array $filters, callable $callback): array
{
    $key = $this->keyManager->buildTaskListKey($user, $filters);

    $cached = $this->redis->get($key);
    if ($cached) {
        // Deserialize JSON → TaskResponseDto[]
        return $this->serializer->deserialize(
            $cached,
            TaskResponseDto::class.'[]',
            'json'
        );
    }

    // Fetch from DB
    $tasks = $callback(); // Task[] entities

    // Convert Entity → DTO
    $dtos = array_map(
        fn(Task $t) => TaskResponseDto::fromEntity($t),
        $tasks
    );

    // Serialize DTO → JSON
    $json = $this->serializer->serialize($dtos, 'json', [
        'groups' => ['task:read']
    ]);

    // Cache JSON
    $this->redis->setex($key, $ttl, $json);

    // Return DTO[]
    return $dtos;
}
```

**Controller:**
```php
public function list(Request $request): JsonResponse
{
    $tasksDtos = $this->taskService->getActiveTasks($user); // TaskResponseDto[]
    return $this->json($tasksDtos);
}
```

**Плюсы:**
- ✅ Type safety (IDE подсказывает)
- ✅ Меньше ошибок
- ✅ Понятная структура данных

**Минусы:**
- ❌ Больше CPU (создание объектов)
- ❌ Больше памяти
- ❌ Нужно менять все места использования TaskService

---

## Сравнение вариантов

| Критерий | PHP serialize (сейчас) | Вариант A (JSON + Array) | Вариант B (JSON + DTO) |
|----------|----------------------|--------------------------|------------------------|
| **Размер кеша** | ❌ Огромный (~10 KB/task) | ✅ Средний (~1-2 KB/task) | ✅ Средний (~1-2 KB/task) |
| **Читаемость в Redis** | ❌ Нечитаемо | ✅ Красивый JSON | ✅ Красивый JSON |
| **CPU overhead** | ✅ Минимум | ✅ Низкий | ⚠️ Средний |
| **Память** | ❌ Много | ✅ Меньше | ⚠️ Средне |
| **Type safety** | ✅ Да (Task Entity) | ❌ Нет (массив) | ✅ Да (DTO) |
| **Простота кода** | ✅ Простой | ✅ Простой | ⚠️ Сложнее |
| **Breaking changes** | - | ⚠️ Средние | ❌ Большие |

---

## Рекомендация

### 🎯 Рекомендую: **Вариант A (JSON + массивы)**

**Почему:**
1. ✅ **Минимум кода** - простая реализация
2. ✅ **Производительность** - меньше CPU и памяти
3. ✅ **Читаемость в Redis** - красивый JSON
4. ✅ **Меньше breaking changes** - контроллеры работают с массивами
5. ✅ **Единый источник правды** - используем группы сериализации API

**Trade-off:**
- ⚠️ Теряем type safety (массив вместо объекта)
- ⚠️ IDE не подсказывает структуру

**Когда выбрать Вариант B:**
- Если type safety критична
- Если много логики работает с Task объектами после получения из кеша
- Если команда большая и нужна защита от ошибок

---

## План реализации (Вариант A)

### Этап 1: Подготовка

1. ✅ Удалить `TaskCacheDTO.php` (не нужен)
2. ✅ Добавить `SerializerInterface` в `TaskCacheService`
3. ✅ Убедиться, что группы сериализации настроены в Entity

### Этап 2: TaskCacheService

Обновить методы:
```php
public function getTaskList(User $user, array $filters, callable $callback): array
{
    return $this->cacheService->get($key, function() use ($callback) {
        $tasks = $callback();
        return $this->serializeToArray($tasks); // ← новый метод
    }, $ttl);
}

private function serializeToArray(array $entities): array
{
    $json = $this->serializer->serialize($entities, 'json', [
        'groups' => ['task:read']
    ]);
    return json_decode($json, true);
}
```

### Этап 3: SimpleRedisCache

Обновить для работы с JSON:
```php
public function set(string $key, mixed $value, int $ttl): bool
{
    if (is_array($value)) {
        // Сохраняем как JSON, а не PHP serialize
        $data = json_encode($value);
    } else {
        $data = serialize($value);
    }

    return $this->redis->setex($this->prefix . $key, $ttl, $data);
}

public function get(string $key, ?callable $callback = null, ?int $ttl = null): mixed
{
    $data = $this->redis->get($this->prefix . $key);

    if ($data === false) {
        // Cache miss
        if ($callback) {
            $value = $callback();
            $this->set($key, $value, $ttl);
            return $value;
        }
        return null;
    }

    // Try JSON first
    $decoded = json_decode($data, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    }

    // Fallback to PHP unserialize
    return unserialize($data);
}
```

### Этап 4: Controller

Упростить контроллеры:
```php
// ДО:
$tasks = $this->taskService->getActiveTasks($user);
$response = array_map(
    fn(Task $task) => TaskResponseDto::fromEntity($task),
    $tasks
);
return $this->json($response);

// ПОСЛЕ:
$tasksArray = $this->taskService->getActiveTasks($user);
return $this->json($tasksArray); // ← уже массив!
```

### Этап 5: Очистка

1. Очистить Redis кеш: `redis-cli FLUSHDB`
2. Удалить неиспользуемые тестовые файлы
3. Обновить документацию

### Этап 6: Тестирование

1. Создать задачу через API
2. Проверить кеш в Redis (должен быть JSON)
3. Получить список задач (должен вернуться корректный response)
4. Проверить размер кеша в Redis

---

## Альтернативное решение: ID-based caching

**Идея:** Кешировать только массив ID задач, а не полные данные.

```php
// В кеше: массив ID
cache['tasks_list'] = [123, 456, 789, ...]  // ~1 KB

// При чтении
$ids = cache->get('tasks_list');
$tasks = $repository->findByIds($ids); // WITH JOIN
```

**Плюсы:**
- ✅ Минимальный размер кеша
- ✅ Всегда свежие данные из БД
- ✅ Нет проблем с устаревшими связями

**Минусы:**
- ❌ Дополнительный запрос к БД
- ❌ Не подходит для высоконагруженных систем

**Когда использовать:**
- Когда связанные данные часто меняются
- Когда важна консистентность
- Когда нагрузка на БД не критична

---

## Итоговые рекомендации

### Для твоего проекта:

1. **Используй Вариант A (JSON + массивы)** для кеширования списков задач
2. Группы сериализации уже настроены в API
3. Контроллеры работают с массивами - минимум изменений
4. Получишь **читаемый JSON в Redis** без Doctrine метаданных
5. **Экономия памяти ~80-90%**

### Следующий шаг:

Подтверди выбор варианта, и я начну реализацию! 🚀

---

**Дата:** 2025-11-05
**Автор:** Claude (Sonnet 4.5)
**Статус:** Ожидание решения
