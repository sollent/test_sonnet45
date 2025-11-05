# Cached Entity DTO Strategy - Deep Analysis

## Твоё предложение

### Концепция: Вариант B с промежуточными DTO

```
DB (Entity)
  ↓ Symfony Serializer (normalize)
Array (массив из денормализованной Entity)
  ↓ создаём объект
TaskEntityCachedDto (промежуточный DTO)
  ↓ JSON encode
JSON string
  ↓ Redis
CACHE

---

CACHE
  ↓ JSON decode
Array
  ↓ создаём объект
TaskEntityCachedDto (десериализация из массива)
  ↓ TaskResponseDto::fromCachedEntityDto()
TaskResponseDto
  ↓ Controller
JSON Response → Frontend
```

### Ключевые моменты:

1. **TaskEntityCachedDto** - промежуточная структура, отражающая денормализованную Entity
2. **Вложенные DTO** для всех связей:
   - `RecurrenceRuleEntityCachedDto` для recurrence rule
   - `TagEntityCachedDto` для tags
   - `SubtaskEntityCachedDto` для subtasks
   - `MediaObjectEntityCachedDto` для attachments

3. **Два метода создания TaskResponseDto:**
   - `fromEntity(Task $task)` - для работы с Entity (сохраняем!)
   - `fromCachedEntityDto(TaskEntityCachedDto $cached)` - для работы с кешем (новый!)

4. **Фокус только на задачах** - аналитика остаётся как есть (инвалидация)

---

## Глубокий анализ

### ✅ Преимущества

#### 1. Type Safety
```php
// ✅ IDE знает структуру
function processTask(TaskEntityCachedDto $dto) {
    $dto->title; // ← автодополнение работает
    $dto->recurrenceRule->frequency; // ← тоже работает
}

// ❌ Без DTO
function processTask(array $data) {
    $data['title']; // ← нет автодополнения
    $data['recurrenceRule']['frequency']; // ← легко ошибиться
}
```

#### 2. Валидация данных
```php
class TaskEntityCachedDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $title, // ← гарантия что string
        public readonly TaskStatus $status, // ← гарантия что enum
        public readonly ?RecurrenceRuleEntityCachedDto $recurrenceRule,
    ) {
        // Можно добавить валидацию
        if ($id <= 0) {
            throw new \InvalidArgumentException('Invalid task ID');
        }
    }
}
```

При десериализации из кеша **автоматически проверяются типы**.

#### 3. Единая точка десериализации
```php
// В одном месте описываем как создать DTO из массива
class TaskEntityCachedDto
{
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            title: $data['title'],
            status: TaskStatus::from($data['status']),
            recurrenceRule: isset($data['recurrenceRule'])
                ? RecurrenceRuleEntityCachedDto::fromArray($data['recurrenceRule'])
                : null,
            // ...
        );
    }
}
```

#### 4. Защита от изменений структуры кеша
Если структура кеша изменится, **PHP сразу выдаст ошибку** при попытке создать DTO.

С массивами ошибка может **молчаливо проигнорироваться**:
```php
// Массив: нет ошибки, но данные неправильные
$data['titel']; // ← опечатка, вернёт null

// DTO: ошибка сразу
new TaskEntityCachedDto(titel: '...'); // ← PHP Fatal Error
```

#### 5. Читаемость кода
```php
// ✅ С DTO - понятно что это
TaskResponseDto::fromCachedEntityDto($cachedDto);

// ⚠️ С массивом - не очевидно
TaskResponseDto::fromArray($data);
```

#### 6. Переиспользование логики
```php
// RecurrenceRuleDto может использовать один и тот же код
class RecurrenceRuleDto
{
    public static function fromEntity(RecurrenceRule $entity): self {
        // Логика создания из Entity
    }

    public static function fromCachedEntityDto(RecurrenceRuleEntityCachedDto $cached): self {
        // Логика создания из кеша
        // ✅ Может переиспользовать часть логики из fromEntity
    }
}
```

---

### ⚠️ Недостатки

#### 1. Дублирование структуры (Code Duplication)

**Проблема:** Нужно поддерживать **3 версии одной и той же структуры**:

1. **Task Entity** (Doctrine)
```php
class Task {
    private int $id;
    private string $title;
    private ?RecurrenceRule $recurrenceRule;
    private Collection $tags;
    // ...
}
```

2. **TaskEntityCachedDto** (промежуточный для кеша)
```php
class TaskEntityCachedDto {
    public int $id;
    public string $title;
    public ?RecurrenceRuleEntityCachedDto $recurrenceRule;
    public array $tags; // TagEntityCachedDto[]
    // ...
}
```

3. **TaskResponseDto** (для API)
```php
class TaskResponseDto {
    public int $id;
    public string $title;
    public ?array $recurrenceRule; // массив, не объект!
    public array $tags; // массив массивов
    // ...
}
```

**Последствие:** Если добавляешь поле в Task, нужно обновить **3 места**!

#### 2. Большое количество промежуточных DTO

**Нужно создать:**

```
TaskEntityCachedDto
├── RecurrenceRuleEntityCachedDto
├── TagEntityCachedDto[]
├── SubtaskEntityCachedDto[]
│   └── (рекурсивно те же вложенные DTO)
└── MediaObjectEntityCachedDto[]
```

**Оценка объёма кода:**
- `TaskEntityCachedDto` (~150 строк)
- `RecurrenceRuleEntityCachedDto` (~50 строк)
- `TagEntityCachedDto` (~30 строк)
- `SubtaskEntityCachedDto` = копия `TaskEntityCachedDto` (~150 строк)
- `MediaObjectEntityCachedDto` (~50 строк)

**Итого: ~430 строк кода** только для DTO + методы `fromArray()` + методы `toArray()`

**Реальная оценка с методами: ~800-1000 строк кода**

#### 3. Сложность с рекурсивными структурами

**Subtasks - это те же Task, но вложенные:**

```php
class TaskEntityCachedDto {
    public array $subtasks; // SubtaskEntityCachedDto[]
}

class SubtaskEntityCachedDto {
    public array $subtasks; // SubtaskEntityCachedDto[] - РЕКУРСИЯ!
}
```

**Проблема:** `TaskEntityCachedDto` и `SubtaskEntityCachedDto` - это **одна и та же структура**!

**Решения:**
1. Использовать `TaskEntityCachedDto` для subtasks (логично, но может быть путаница)
2. Создать алиас `class SubtaskEntityCachedDto extends TaskEntityCachedDto` (костыль)

#### 4. Overhead при десериализации

**Процесс создания объектов:**

```php
// 1. Redis → JSON string
$json = $redis->get($key);

// 2. JSON → массив
$array = json_decode($json, true);

// 3. Массив → промежуточный DTO (создание объектов!)
$cachedDto = TaskEntityCachedDto::fromArray($array);
  ├── RecurrenceRuleEntityCachedDto::fromArray($array['recurrenceRule'])
  ├── TagEntityCachedDto::fromArray($tag) x N tags
  ├── SubtaskEntityCachedDto::fromArray($subtask) x M subtasks
  └── MediaObjectEntityCachedDto::fromArray($media) x K attachments

// 4. Промежуточный DTO → Response DTO (ещё одно создание объектов!)
$responseDto = TaskResponseDto::fromCachedEntityDto($cachedDto);
  ├── RecurrenceRuleDto::fromCachedEntityDto($cachedDto->recurrenceRule)
  └── ...
```

**Для задачи с 10 subtasks, 5 tags, 3 attachments:**
- Создаётся ~20 промежуточных DTO объектов
- Потом ~20 response DTO объектов
- **Итого: ~40 объектов на одну задачу!**

**Для списка 100 задач: ~4000 объектов!**

**Альтернатива с массивами:**
```php
$array = json_decode($json, true);
$responseDto = TaskResponseDto::fromArray($array); // ← сразу
```
**Создаётся только 1 объект (TaskResponseDto)**, массивы просто копируются.

#### 5. Поддержка (Maintenance Hell)

**Сценарий:** Добавляем новое поле `estimatedMinutes` в Task.

**Что нужно обновить:**

1. ✅ `Task` Entity - добавить поле
2. ✅ `TaskEntityCachedDto` - добавить поле
3. ✅ `TaskEntityCachedDto::fromArray()` - добавить mapping
4. ✅ `TaskResponseDto` - добавить поле
5. ✅ `TaskResponseDto::fromEntity()` - добавить mapping
6. ✅ `TaskResponseDto::fromCachedEntityDto()` - добавить mapping
7. ✅ Symfony Serializer groups - добавить в группу сериализации

**7 мест для одного поля!**

С массивами:
1. ✅ `Task` Entity
2. ✅ `TaskResponseDto`
3. ✅ `TaskResponseDto::fromEntity()`
4. ✅ `TaskResponseDto::fromArray()`
5. ✅ Symfony Serializer groups

**5 мест** (меньше на 2)

#### 6. Сложность отладки

**При ошибке в кеше:**

Массив:
```php
// Ошибка: поле 'titel' вместо 'title'
var_dump($array); // ← сразу видно структуру
```

DTO:
```php
// Ошибка при создании DTO
TaskEntityCachedDto::fromArray($array);
// Fatal error: Unknown named parameter $titel
// ← нужно дебажить fromArray(), смотреть $array
```

---

### 🎯 Альтернатива: Вариант A+ (улучшенный)

**Предлагаю гибрид:** JSON в кеше + **прямая десериализация в TaskResponseDto**

```php
// БЕЗ промежуточных DTO!

DB (Entity)
  ↓ TaskResponseDto::fromEntity()
TaskResponseDto
  ↓ Symfony Serializer normalize
Array
  ↓ JSON encode
JSON
  ↓ Redis
CACHE

---

CACHE
  ↓ JSON decode
Array
  ↓ TaskResponseDto::fromArray() - ПРЯМО!
TaskResponseDto
  ↓ Controller
JSON Response
```

**Код:**

```php
class TaskResponseDto
{
    // Существующий метод (сохраняем!)
    public static function fromEntity(Task $task, bool $includeSubtasks = false): self
    {
        // Текущий код
    }

    // НОВЫЙ метод - десериализация из кеша
    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->id = $data['id'];
        $dto->title = $data['title'];
        $dto->description = $data['description'] ?? null;
        $dto->status = TaskStatus::from($data['status']);
        $dto->priority = TaskPriority::from($data['priority']);

        // Даты - десериализуем из строк
        $dto->startDate = isset($data['startDate'])
            ? new \DateTimeImmutable($data['startDate'])
            : null;
        $dto->dueDate = isset($data['dueDate'])
            ? new \DateTimeImmutable($data['dueDate'])
            : null;

        // Tags - уже массив массивов (как нужно для API)
        $dto->tags = $data['tags'] ?? [];

        // Subtasks - рекурсивно
        $dto->subtasks = isset($data['subtasks'])
            ? array_map(fn($s) => self::fromArray($s), $data['subtasks'])
            : [];

        // RecurrenceRule - создаём DTO
        $dto->recurrenceRule = isset($data['recurrenceRule'])
            ? RecurrenceRuleDto::fromArray($data['recurrenceRule'])
            : null;

        // Остальные поля...

        return $dto;
    }
}
```

**TaskCacheService:**

```php
public function getTaskList(User $user, array $filters, callable $callback): array
{
    $key = $this->keyManager->buildTaskListKey($user, $filters);

    $cached = $this->redis->get($key);
    if ($cached) {
        $array = json_decode($cached, true);
        // ✅ Прямая десериализация в TaskResponseDto!
        return array_map(
            fn($taskData) => TaskResponseDto::fromArray($taskData),
            $array
        );
    }

    // Cache miss - fetch from DB
    $tasks = $callback(); // Task[] entities

    // Convert Entity → TaskResponseDto
    $dtos = array_map(
        fn(Task $task) => TaskResponseDto::fromEntity($task),
        $tasks
    );

    // Serialize DTO → JSON (через Symfony Serializer)
    $json = $this->serializer->serialize($dtos, 'json', [
        'groups' => ['task:read']
    ]);

    // Cache JSON
    $this->redis->setex($key, $ttl, $json);

    return $dtos;
}
```

**Преимущества над промежуточными DTO:**

✅ Меньше кода (нет промежуточных DTO)
✅ Меньше объектов (создаём только Response DTO)
✅ Проще поддержка (2-3 места вместо 7)
✅ Быстрее (меньше overhead)
✅ Всё ещё есть type safety (в TaskResponseDto)

**Недостатки:**

⚠️ Type safety чуть хуже (массив → DTO, а не CachedDTO → DTO)
⚠️ Нужен метод `fromArray()` в каждом Response DTO

---

## Сравнение подходов

| Критерий | Вариант B (Cached DTO) | Вариант A+ (fromArray) |
|----------|----------------------|----------------------|
| **Type safety** | ✅✅ Максимальная | ✅ Хорошая |
| **Объём кода** | ❌ ~1000 строк | ✅ ~200 строк |
| **Поддержка** | ❌ 7 мест при изменении | ✅ 4-5 мест |
| **Производительность** | ⚠️ Средняя (2 этапа) | ✅ Высокая (1 этап) |
| **Память** | ⚠️ ~40 объектов/задачу | ✅ ~10 объектов/задачу |
| **Читаемость** | ✅ Отличная | ✅ Хорошая |
| **Отладка** | ⚠️ Сложнее | ✅ Проще |

---

## Рекомендация

### 🎯 **Рекомендую Вариант A+ (fromArray)**

**Почему:**

1. **Золотая середина** между type safety и простотой
2. **В 5 раз меньше кода** чем Cached DTO
3. **В 2 раза быстрее** (меньше создания объектов)
4. **Проще поддерживать** (меньше мест изменения)
5. **TaskResponseDto уже существует** - просто добавляем метод `fromArray()`

### Если всё-таки Вариант B (Cached DTO):

**Используй его только если:**

1. ✅ **Команда большая** (5+ разработчиков) - type safety критична
2. ✅ **Структура часто меняется** - нужна защита от ошибок
3. ✅ **Много бизнес-логики** в DTO - нужна чёткая структура

**Но учти:**
- ⚠️ Потребуется **~1000 строк** дополнительного кода
- ⚠️ Увеличится **время поддержки** при изменениях
- ⚠️ Снизится **производительность** (~20-30%)

---

## Детальный план для Вариант B (если выбираешь его)

### Структура файлов

```
src/
├── Dto/
│   ├── Cache/                           # Промежуточные DTO для кеша
│   │   ├── Task/
│   │   │   ├── TaskEntityCachedDto.php
│   │   │   ├── SubtaskEntityCachedDto.php (alias для Task)
│   │   │   └── MediaObjectEntityCachedDto.php
│   │   ├── Tag/
│   │   │   └── TagEntityCachedDto.php
│   │   └── Recurrence/
│   │       └── RecurrenceRuleEntityCachedDto.php
│   │
│   └── Response/                        # Response DTO (уже существуют)
│       ├── Task/
│       │   └── TaskResponseDto.php      # + метод fromCachedEntityDto()
│       ├── Tag/
│       │   └── TagDto.php
│       └── Recurrence/
│           └── RecurrenceRuleDto.php    # + метод fromCachedEntityDto()
```

### Этап 1: Создать промежуточные DTO

**1.1 TaskEntityCachedDto**

```php
namespace App\Dto\Cache\Task;

final readonly class TaskEntityCachedDto
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public string $status, // enum value
        public string $priority, // enum value
        public ?string $startDate, // ISO 8601
        public ?string $dueDate,
        public ?string $completedAt,
        public ?int $parentTaskId,
        public int $sortOrder,
        public bool $isArchived,
        public bool $isCompleted,
        public bool $isOverdue,
        public float $completionProgress,
        public ?string $createdAt,
        public ?string $updatedAt,
        public int $subtaskCount,
        public int $completedSubtaskCount,
        public bool $hasNestedSubtasks,
        public bool $isRecurringTemplate,

        // Вложенные структуры
        /** @var TagEntityCachedDto[] */
        public array $tags,

        /** @var TaskEntityCachedDto[] - рекурсивно! */
        public array $subtasks,

        /** @var MediaObjectEntityCachedDto[] */
        public array $attachments,

        public ?RecurrenceRuleEntityCachedDto $recurrenceRule,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            title: $data['title'],
            description: $data['description'] ?? null,
            status: $data['status'],
            priority: $data['priority'],
            startDate: $data['startDate'] ?? null,
            dueDate: $data['dueDate'] ?? null,
            completedAt: $data['completedAt'] ?? null,
            parentTaskId: $data['parentTaskId'] ?? null,
            sortOrder: $data['sortOrder'] ?? 0,
            isArchived: $data['isArchived'] ?? false,
            isCompleted: $data['isCompleted'] ?? false,
            isOverdue: $data['isOverdue'] ?? false,
            completionProgress: $data['completionProgress'] ?? 0.0,
            createdAt: $data['createdAt'] ?? null,
            updatedAt: $data['updatedAt'] ?? null,
            subtaskCount: $data['subtaskCount'] ?? 0,
            completedSubtaskCount: $data['completedSubtaskCount'] ?? 0,
            hasNestedSubtasks: $data['hasNestedSubtasks'] ?? false,
            isRecurringTemplate: $data['isRecurringTemplate'] ?? false,

            // Вложенные DTO
            tags: array_map(
                fn(array $tag) => TagEntityCachedDto::fromArray($tag),
                $data['tags'] ?? []
            ),
            subtasks: array_map(
                fn(array $subtask) => self::fromArray($subtask), // рекурсия
                $data['subtasks'] ?? []
            ),
            attachments: array_map(
                fn(array $media) => MediaObjectEntityCachedDto::fromArray($media),
                $data['attachments'] ?? []
            ),
            recurrenceRule: isset($data['recurrenceRule'])
                ? RecurrenceRuleEntityCachedDto::fromArray($data['recurrenceRule'])
                : null,
        );
    }
}
```

**1.2 TagEntityCachedDto**

```php
namespace App\Dto\Cache\Tag;

final readonly class TagEntityCachedDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $color,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            color: $data['color'],
        );
    }
}
```

**1.3 RecurrenceRuleEntityCachedDto**

```php
namespace App\Dto\Cache\Recurrence;

final readonly class RecurrenceRuleEntityCachedDto
{
    public function __construct(
        public int $id,
        public string $frequency,
        public int $interval,
        public ?string $endDate,
        // ... остальные поля
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            frequency: $data['frequency'],
            interval: $data['interval'],
            endDate: $data['endDate'] ?? null,
            // ...
        );
    }
}
```

**1.4 MediaObjectEntityCachedDto**

```php
namespace App\Dto\Cache\MediaObject;

final readonly class MediaObjectEntityCachedDto
{
    public function __construct(
        public int $id,
        public string $fileName,
        public string $originalName,
        public string $mimeType,
        public int $fileSize,
        public string $fileSizeHuman,
        public string $fileType,
        public string $filePath,
        public ?string $thumbnailPath,
        public string $createdAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            fileName: $data['fileName'],
            originalName: $data['originalName'],
            mimeType: $data['mimeType'],
            fileSize: $data['fileSize'],
            fileSizeHuman: $data['fileSizeHuman'],
            fileType: $data['fileType'],
            filePath: $data['filePath'],
            thumbnailPath: $data['thumbnailPath'] ?? null,
            createdAt: $data['createdAt'],
        );
    }
}
```

### Этап 2: Обновить Response DTO

**TaskResponseDto - добавить метод `fromCachedEntityDto()`:**

```php
public static function fromCachedEntityDto(TaskEntityCachedDto $cached): self
{
    $dto = new self();
    $dto->id = $cached->id;
    $dto->title = $cached->title;
    $dto->description = $cached->description;
    $dto->status = TaskStatus::from($cached->status);
    $dto->priority = TaskPriority::from($cached->priority);

    // Даты - конвертируем из строк
    $dto->startDate = $cached->startDate
        ? new \DateTimeImmutable($cached->startDate)
        : null;
    $dto->dueDate = $cached->dueDate
        ? new \DateTimeImmutable($cached->dueDate)
        : null;
    $dto->completedAt = $cached->completedAt
        ? new \DateTimeImmutable($cached->completedAt)
        : null;
    $dto->createdAt = $cached->createdAt
        ? new \DateTimeImmutable($cached->createdAt)
        : null;
    $dto->updatedAt = $cached->updatedAt
        ? new \DateTimeImmutable($cached->updatedAt)
        : null;

    $dto->parentTaskId = $cached->parentTaskId;
    $dto->sortOrder = $cached->sortOrder;
    $dto->isArchived = $cached->isArchived;
    $dto->isCompleted = $cached->isCompleted;
    $dto->isOverdue = $cached->isOverdue;
    $dto->completionProgress = $cached->completionProgress;
    $dto->isRecurringTemplate = $cached->isRecurringTemplate;
    $dto->subtaskCount = $cached->subtaskCount;
    $dto->completedSubtaskCount = $cached->completedSubtaskCount;
    $dto->hasNestedSubtasks = $cached->hasNestedSubtasks;

    // Tags - конвертируем CachedDto → массив
    $dto->tags = array_map(
        fn(TagEntityCachedDto $tag) => [
            'id' => $tag->id,
            'name' => $tag->name,
            'color' => $tag->color,
        ],
        $cached->tags
    );

    // Subtasks - рекурсивно
    $dto->subtasks = array_map(
        fn(TaskEntityCachedDto $subtask) => self::fromCachedEntityDto($subtask),
        $cached->subtasks
    );

    // Attachments
    $dto->attachments = array_map(
        fn(MediaObjectEntityCachedDto $media) => [
            'id' => $media->id,
            'fileName' => $media->fileName,
            'originalName' => $media->originalName,
            'mimeType' => $media->mimeType,
            'fileSize' => $media->fileSize,
            'fileSizeHuman' => $media->fileSizeHuman,
            'fileType' => $media->fileType,
            'filePath' => $media->filePath,
            'thumbnailPath' => $media->thumbnailPath,
            'createdAt' => $media->createdAt,
        ],
        $cached->attachments
    );

    // RecurrenceRule
    if ($cached->recurrenceRule) {
        $dto->recurrenceRule = RecurrenceRuleDto::fromCachedEntityDto(
            $cached->recurrenceRule
        );
    }

    return $dto;
}
```

### Этап 3: Обновить TaskCacheService

```php
public function getTaskList(User $user, array $filters, callable $callback): array
{
    $key = $this->keyManager->buildTaskListKey($user, $filters);

    $cached = $this->redis->get($key);
    if ($cached) {
        $array = json_decode($cached, true);

        // Десериализация: Array → CachedDto → ResponseDto
        return array_map(function($taskData) {
            $cachedDto = TaskEntityCachedDto::fromArray($taskData);
            return TaskResponseDto::fromCachedEntityDto($cachedDto);
        }, $array);
    }

    // Cache miss
    $tasks = $callback(); // Task[] entities

    // Entity → ResponseDto
    $dtos = array_map(
        fn(Task $task) => TaskResponseDto::fromEntity($task),
        $tasks
    );

    // ResponseDto → JSON
    $json = $this->serializer->serialize($dtos, 'json', [
        'groups' => ['task:read']
    ]);

    // Cache
    $this->redis->setex($key, $ttl, $json);

    return $dtos;
}
```

---

## Окончательная рекомендация

### Для твоего проекта:

**Я бы рекомендовал **Вариант A+ (fromArray)** по следующим причинам:**

1. ✅ Твой проект один разработчик (судя по контексту) - избыточная type safety не нужна
2. ✅ **В 5 раз меньше кода** (~200 строк вместо ~1000)
3. ✅ **Проще поддерживать** - меньше мест изменения
4. ✅ **Быстрее работает** - меньше создания объектов
5. ✅ **Достаточная type safety** - TaskResponseDto всё ещё typed

### НО если команда растёт или планируется сложная логика:

**Тогда Вариант B (Cached DTO)** будет оправдан:
- Строгая type safety защитит от ошибок
- Явная структура поможет новым разработчикам
- IDE подскажет все поля

**Выбор за тобой! Оба варианта технически корректны.**

Дай знать какой выбираешь, и я реализую! 🚀

---

**Дата:** 2025-11-05
**Статус:** Ожидание решения
