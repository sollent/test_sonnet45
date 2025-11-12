# 📊 Схема базы данных - Дизайн PostgreSQL

> **TL;DR**: База данных PostgreSQL 15 с 5 основными сущностями (User, Task, Tag, RefreshToken, Media). Сущность Task поддерживает неограниченную вложенность через самореферентную связь. Оптимизирована 13 составными индексами для производительности запросов.

---

## Содержание

- [Диаграмма связей сущностей](#диаграмма-связей-сущностей)
- [Основные сущности](#основные-сущности)
- [Связи](#связи)
- [Стратегия индексирования](#стратегия-индексирования)
- [Рабочий процесс с миграциями](#рабочий-процесс-с-миграциями)
- [Оптимизация запросов](#оптимизация-запросов)

---

## Диаграмма связей сущностей

```
┌──────────────────────┐
│    Пользователь      │
│       (User)         │
│──────────────────────│
│ id (PK)              │
│ email (уникальный)   │
│ password (nullable)  │
│ googleId (nullable)  │
│ name                 │
│ avatar               │
│ theme                │
│ language             │
│ timezone             │
│ notificationSettings │
│ roles (JSON)         │
│ createdAt            │
│ updatedAt            │
└──────────┬───────────┘
           │
           │ 1:N
           ▼
┌──────────────────────┐          ┌──────────────────────┐
│       Задача         │◄────────►│         Тег          │
│       (Task)         │   N:M    │        (Tag)         │
│──────────────────────│          │──────────────────────│
│ id (PK)              │          │ id (PK)              │
│ user_id (FK)         │          │ name                 │
│ parent_task_id (FK)  │◄─┐       │ color                │
│ title                │  │       │ user_id (FK)         │
│ description          │  │       │ createdAt            │
│ status (enum)        │  │       │ updatedAt            │
│ priority (enum)      │  │       └──────────────────────┘
│ startDate            │  │
│ dueDate              │  │       ┌──────────────────────┐
│ completedAt          │  │       │   Медиафайл          │
│ sortOrder            │  │       │   (MediaObject)      │
│ isArchived           │  │       │──────────────────────│
│ isRecurringTemplate  │  │       │ id (PK)              │
│ createdAt            │  │       │ filePath             │
│ updatedAt            │  │       │ fileName             │
└──────────────────────┘  │       │ mimeType             │
           │              │       │ size                 │
           │ Самоссылка 1:N       │ uploadedBy (FK)      │
           └──────────────┘       │ createdAt            │
                                  └──────────────────────┘

┌──────────────────────┐
│  Токен обновления    │
│   (RefreshToken)     │
│──────────────────────│
│ id (PK)              │
│ refresh_token (text) │
│ username (string)    │
│ valid (datetime)     │
└──────────────────────┘

┌──────────────────────┐
│  Правило повторения  │
│  (RecurrenceRule)    │
│──────────────────────│
│ id (PK)              │
│ template_task_id(FK) │
│ frequency (enum)     │
│ interval             │
│ endDate              │
│ occurrences          │
│ daysOfWeek (JSON)    │
│ createdAt            │
└──────────────────────┘
```

---

## Основные сущности

### 1. Сущность User (Пользователь)

**Назначение:** Аутентификация и данные пользователя

**Расположение:** `/backend/src/Entity/User.php`

```php
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity('email')]
#[ORM\Table(name: '`users`')]
class User extends AbstractEntity implements UserInterface
{
    #[ORM\Column(type: 'string', unique: true)]
    protected string $email;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $password = null;    // Nullable для OAuth пользователей

    #[ORM\Column(type: 'json')]
    protected array $roles = [];           // ['ROLE_USER', 'ROLE_ADMIN']

    #[ORM\Column(type: 'string', nullable: true)]
    protected mixed $googleId = null;      // Google OAuth ID

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $googleUserName = null;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $name = null;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $avatar = null;

    // Настройки пользователя
    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'light'])]
    protected string $theme = 'light';     // light/dark

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'ru'])]
    protected string $language = 'ru';     // ru/en

    #[ORM\Column(type: 'string', length: 50, options: ['default' => 'Europe/Moscow'])]
    protected string $timezone = 'Europe/Moscow';

    #[ORM\Column(type: 'json', name: 'notification_settings')]
    protected array $notificationSettings = [
        'email' => true,
        'push' => true,
        'taskReminders' => true,
        'taskAssignments' => true,
        'taskCompletion' => true,
        'weeklyDigest' => false,
    ];

    // Связи
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $tasks;

    #[ORM\OneToMany(targetEntity: Tag::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $tags;
}
```

**Поля:**
- `id`: Первичный ключ (автоинкремент)
- `email`: Уникальный, обязательный для всех пользователей
- `password`: Хешированный пароль (nullable для пользователей Google OAuth)
- `googleId`: Идентификатор Google OAuth
- `roles`: JSON массив ролей (ROLE_USER, ROLE_ADMIN)
- `theme`: Предпочтение темы UI (light/dark)
- `language`: Язык приложения (ru/en)
- `timezone`: Часовой пояс пользователя для отображения дат
- `notificationSettings`: JSON с настройками уведомлений

---

### 2. Сущность Task (Задача)

**Назначение:** Основное управление задачами с неограниченной вложенностью

**Расположение:** `/backend/src/Entity/Task.php`

```php
#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: '`task`', indexes: [
    // 13 составных индексов для производительности
    new ORM\Index(name: 'idx_task_user_parent', columns: ['user_id', 'parent_task_id']),
    new ORM\Index(name: 'idx_task_user_status', columns: ['user_id', 'status']),
    new ORM\Index(name: 'idx_task_user_created_at', columns: ['user_id', 'created_at']),
    new ORM\Index(name: 'idx_task_user_completed_at', columns: ['user_id', 'completed_at']),
    new ORM\Index(name: 'idx_task_user_due_date', columns: ['user_id', 'due_date']),
    new ORM\Index(name: 'idx_task_user_priority', columns: ['user_id', 'priority']),
    new ORM\Index(name: 'idx_task_user_archived', columns: ['user_id', 'is_archived']),
    new ORM\Index(name: 'idx_task_user_parent_archived', columns: ['user_id', 'parent_task_id', 'is_archived']),
    new ORM\Index(name: 'idx_task_user_parent_status', columns: ['user_id', 'parent_task_id', 'status']),
    new ORM\Index(name: 'idx_task_user_parent_created', columns: ['user_id', 'parent_task_id', 'created_at']),
    new ORM\Index(name: 'idx_task_user_parent_completed', columns: ['user_id', 'parent_task_id', 'completed_at']),
    new ORM\Index(name: 'idx_task_user_sort_order', columns: ['user_id', 'sort_order']),
])]
class Task extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'task.title.not_blank')]
    #[Assert\Length(min: 1, max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 5000)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 20, enumType: TaskStatus::class)]
    private TaskStatus $status = TaskStatus::PENDING;

    #[ORM\Column(type: 'string', length: 20, enumType: TaskPriority::class)]
    private TaskPriority $priority = TaskPriority::MEDIUM;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    // Связи
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // Самореферентная связь для неограниченной вложенности
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subtasks')]
    private ?self $parentTask = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parentTask', cascade: ['persist', 'remove'])]
    private Collection $subtasks;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'tasks', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'task_tags')]
    private Collection $tags;

    #[ORM\ManyToMany(targetEntity: MediaObject::class)]
    #[ORM\JoinTable(name: 'task_media')]
    private Collection $mediaObjects;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column]
    private bool $isArchived = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isRecurringTemplate = false;

    #[ORM\OneToOne(targetEntity: RecurrenceRule::class, mappedBy: 'templateTask')]
    private ?RecurrenceRule $recurrenceRule = null;
}
```

**Перечисления (Enums):**

```php
// Перечисление TaskStatus
enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}

// Перечисление TaskPriority
enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';
}
```

**Пример неограниченной вложенности:**

```php
// Родительская задача
$project = new Task();
$project->setTitle('Запуск веб-сайта');

// Подзадача уровня 1
$design = new Task();
$design->setTitle('Дизайн');
$design->setParentTask($project);

// Подзадача уровня 2
$wireframes = new Task();
$wireframes->setTitle('Создать вайрфреймы');
$wireframes->setParentTask($design);

// Подзадача уровня 3 (и так далее...)
$homepage = new Task();
$homepage->setTitle('Вайрфрейм главной страницы');
$homepage->setParentTask($wireframes);

// Нет ограничения по глубине!
```

---

### 3. Сущность Tag (Тег)

**Назначение:** Категоризация задач с цветными тегами

**Расположение:** `/backend/src/Entity/Tag.php`

```php
#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 50)]
    private ?string $name = null;

    #[ORM\Column(length: 7, options: ['default' => '#3b82f6'])]
    #[Assert\Regex(pattern: '/^#[0-9a-fA-F]{6}$/', message: 'Invalid hex color')]
    private string $color = '#3b82f6';

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tags')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToMany(targetEntity: Task::class, mappedBy: 'tags')]
    private Collection $tasks;
}
```

**Поля:**
- `name`: Название тега (макс. 50 символов)
- `color`: Hex код цвета (#3b82f6)
- `user`: Владелец тега
- `tasks`: Задачи, связанные с этим тегом (многие-ко-многим)

**Паттерн использования:**

```php
// Создать тег
$tag = new Tag();
$tag->setName('Работа');
$tag->setColor('#3b82f6');
$tag->setUser($user);

// Связать с задачей
$task->addTag($tag);
```

---

### 4. Сущность RefreshToken (Токен обновления)

**Назначение:** Хранение JWT токенов обновления

**Расположение:** `/backend/src/Entity/RefreshToken.php`

```php
#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
class RefreshToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private ?string $refreshToken = null;

    #[ORM\Column(type: 'string')]
    private ?string $username = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $valid = null;
}
```

**Поля:**
- `refreshToken`: Хешированная строка токена
- `username`: Email пользователя
- `valid`: Дата истечения срока действия (7 дней с момента создания)

---

### 5. Сущность MediaObject (Медиафайл)

**Назначение:** Загрузка файлов и вложений

**Расположение:** `/backend/src/Entity/MediaObject.php`

```php
#[ORM\Entity(repositoryClass: MediaObjectRepository::class)]
class MediaObject extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $filePath = null;

    #[ORM\Column(length: 255)]
    private ?string $fileName = null;

    #[ORM\Column(length: 100)]
    private ?string $mimeType = null;

    #[ORM\Column]
    private ?int $size = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $uploadedBy = null;
}
```

---

## Связи

### 1. User ↔ Task (Один-ко-многим)

```php
// User.php
#[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'user', cascade: ['remove'])]
private Collection $tasks;

// Task.php
#[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tasks')]
#[ORM\JoinColumn(nullable: false)]
private ?User $user = null;
```

**SQL:**
```sql
ALTER TABLE task
ADD CONSTRAINT fk_task_user
FOREIGN KEY (user_id) REFERENCES users(id)
ON DELETE CASCADE;
```

**Поведение:**
- Пользователь может иметь много задач
- Задача принадлежит ОДНОМУ пользователю (обязательно)
- При удалении пользователя → все его задачи удаляются

---

### 2. Task ↔ Task (Самореферентная связь, неограниченная вложенность)

```php
// Связь с родителем
#[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subtasks')]
private ?self $parentTask = null;

// Связь с потомками
#[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parentTask', cascade: ['persist', 'remove'])]
private Collection $subtasks;
```

**SQL:**
```sql
ALTER TABLE task
ADD CONSTRAINT fk_task_parent
FOREIGN KEY (parent_task_id) REFERENCES task(id)
ON DELETE CASCADE;
```

**Поведение:**
- Задача может иметь ОДНОГО родителя (nullable)
- Задача может иметь МНОГО потомков
- При удалении родителя → потомки удаляются
- Нет ограничения по глубине!

**Рекурсивный запрос (PostgreSQL CTE):**

```sql
WITH RECURSIVE subtasks AS (
    -- Базовый случай: Начать с родительской задачи
    SELECT * FROM task WHERE id = 123

    UNION ALL

    -- Рекурсивный случай: Получить всех потомков
    SELECT t.*
    FROM task t
    INNER JOIN subtasks s ON t.parent_task_id = s.id
)
SELECT * FROM subtasks;
```

---

### 3. Task ↔ Tag (Многие-ко-многим)

```php
// Task.php
#[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'tasks', cascade: ['persist'])]
#[ORM\JoinTable(name: 'task_tags')]
private Collection $tags;

// Tag.php
#[ORM\ManyToMany(targetEntity: Task::class, mappedBy: 'tags')]
private Collection $tasks;
```

**SQL:**
```sql
CREATE TABLE task_tags (
    task_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (task_id, tag_id),
    FOREIGN KEY (task_id) REFERENCES task(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tag(id) ON DELETE CASCADE
);
```

**Поведение:**
- Задача может иметь МНОГО тегов
- Тег может быть на МНОГИХ задачах
- Связующая таблица: `task_tags`

---

## Стратегия индексирования

### Зачем 13 индексов на таблице Task?

Таблица Task - самая часто запрашиваемая таблица. Индексы оптимизируют типичные паттерны запросов.

```php
// Индекс 1: Пользователь + Родитель (самый частый запрос)
new ORM\Index(name: 'idx_task_user_parent', columns: ['user_id', 'parent_task_id'])

// Индекс 2: Пользователь + Статус (фильтрация по статусу)
new ORM\Index(name: 'idx_task_user_status', columns: ['user_id', 'status'])

// Индекс 3: Пользователь + Дата создания (запросы временной линии)
new ORM\Index(name: 'idx_task_user_created_at', columns: ['user_id', 'created_at'])

// Индекс 4: Пользователь + Дата завершения (аналитика)
new ORM\Index(name: 'idx_task_user_completed_at', columns: ['user_id', 'completed_at'])

// Индекс 5: Пользователь + Срок выполнения (фильтрация по дедлайну)
new ORM\Index(name: 'idx_task_user_due_date', columns: ['user_id', 'due_date'])

// Индекс 6: Пользователь + Приоритет (сортировка по приоритету)
new ORM\Index(name: 'idx_task_user_priority', columns: ['user_id', 'priority'])

// Индекс 7: Пользователь + Архивирован (исключить архивные задачи)
new ORM\Index(name: 'idx_task_user_archived', columns: ['user_id', 'is_archived'])

// Индекс 8: Пользователь + Родитель + Архивирован (запрос списка задач)
new ORM\Index(name: 'idx_task_user_parent_archived', columns: ['user_id', 'parent_task_id', 'is_archived'])

// Индекс 9: Пользователь + Родитель + Статус (отфильтрованный список задач)
new ORM\Index(name: 'idx_task_user_parent_status', columns: ['user_id', 'parent_task_id', 'status'])

// Индекс 10: Пользователь + Родитель + Создан (отсортированный список задач)
new ORM\Index(name: 'idx_task_user_parent_created', columns: ['user_id', 'parent_task_id', 'created_at'])

// Индекс 11: Пользователь + Родитель + Завершен (аналитика)
new ORM\Index(name: 'idx_task_user_parent_completed', columns: ['user_id', 'parent_task_id', 'completed_at'])

// Индекс 12: Пользователь + Порядок сортировки (ручное упорядочивание)
new ORM\Index(name: 'idx_task_user_sort_order', columns: ['user_id', 'sort_order'])
```

### Преимущества индексов

**До индексов:**
```sql
EXPLAIN ANALYZE
SELECT * FROM task WHERE user_id = 5 AND parent_task_id IS NULL;

→ Seq Scan on task  (cost=0.00..1245.00 rows=500)
→ Execution time: 45ms
```

**После индексов:**
```sql
EXPLAIN ANALYZE
SELECT * FROM task WHERE user_id = 5 AND parent_task_id IS NULL;

→ Index Scan using idx_task_user_parent  (cost=0.15..8.17 rows=500)
→ Execution time: 0.8ms
```

**В 56 раз быстрее!** (45ms → 0.8ms)

---

## Рабочий процесс с миграциями

### Создать миграцию

```bash
# Автоматически сгенерировать миграцию из изменений сущностей
docker exec backend-php83 php bin/console make:migration

# Просмотреть сгенерированный файл миграции
# Расположение: /backend/migrations/VersionYYYYMMDDHHMMSS.php
```

### Пример миграции

```php
<?php
// migrations/Version20250105000000.php

public function up(Schema $schema): void
{
    $this->addSql('CREATE TABLE task (
        id SERIAL PRIMARY KEY,
        user_id INT NOT NULL,
        parent_task_id INT DEFAULT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        status VARCHAR(20) NOT NULL,
        priority VARCHAR(20) NOT NULL,
        start_date TIMESTAMP DEFAULT NULL,
        due_date TIMESTAMP DEFAULT NULL,
        completed_at TIMESTAMP DEFAULT NULL,
        sort_order INT DEFAULT 0,
        is_archived BOOLEAN DEFAULT false,
        created_at TIMESTAMP NOT NULL,
        updated_at TIMESTAMP NOT NULL,
        CONSTRAINT fk_task_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_task_parent FOREIGN KEY (parent_task_id) REFERENCES task(id) ON DELETE CASCADE
    )');

    // Создать индексы
    $this->addSql('CREATE INDEX idx_task_user_parent ON task (user_id, parent_task_id)');
    $this->addSql('CREATE INDEX idx_task_user_status ON task (user_id, status)');
    // ... больше индексов
}

public function down(Schema $schema): void
{
    $this->addSql('DROP TABLE task');
}
```

### Выполнить миграцию

```bash
# Выполнить миграции
docker exec backend-php83 php bin/console doctrine:migrations:migrate

# Откатить последнюю миграцию
docker exec backend-php83 php bin/console doctrine:migrations:migrate prev
```

---

## Оптимизация запросов

### Жадная загрузка (Eager Loading)

```php
// ❌ ПЛОХО: Проблема N+1 запросов
$tasks = $taskRepository->findAll();
foreach ($tasks as $task) {
    echo $task->getUser()->getEmail();  // Отдельный запрос на каждую задачу!
}

// ✅ ХОРОШО: Жадная загрузка с JOIN
$tasks = $taskRepository->createQueryBuilder('t')
    ->leftJoin('t.user', 'u')
    ->addSelect('u')
    ->leftJoin('t.tags', 'tag')
    ->addSelect('tag')
    ->getQuery()
    ->getResult();

// Теперь $task->getUser() уже загружен (нет дополнительного запроса)
```

### Частичные выборки

```php
// ❌ ПЛОХО: Получить все поля, когда нужны только некоторые
$tasks = $taskRepository->findAll();  // Получает description (5000 символов)

// ✅ ХОРОШО: Получить только нужные поля
$tasks = $taskRepository->createQueryBuilder('t')
    ->select('t.id, t.title, t.status')
    ->getQuery()
    ->getArrayResult();
```

---

## Связанные документы

### Обязательно прочитать далее
- **[Архитектура](ARCHITECTURE.md)** - Как использовать эти сущности

### Для справки
- **[API Reference](API_REFERENCE.md)** - Эндпоинты, использующие эти сущности

---

*Последнее обновление: 2025-01-05*
*Версия схемы базы данных: 1.0*
