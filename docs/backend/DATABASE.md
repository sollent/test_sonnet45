# 📊 Database Schema - PostgreSQL Design

> **TL;DR**: PostgreSQL 15 database with 5 main entities (User, Task, Tag, RefreshToken, Media). Task entity supports unlimited nesting via self-referencing relationship. Optimized with 13 composite indexes for query performance.

---

## Table of Contents

- [Entity Relationship Diagram](#entity-relationship-diagram)
- [Core Entities](#core-entities)
- [Relationships](#relationships)
- [Indexing Strategy](#indexing-strategy)
- [Migrations Workflow](#migrations-workflow)
- [Query Optimization](#query-optimization)

---

## Entity Relationship Diagram

```
┌──────────────────────┐
│       User           │
│──────────────────────│
│ id (PK)              │
│ email (unique)       │
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
│       Task           │◄────────►│        Tag           │
│──────────────────────│   N:M    │──────────────────────│
│ id (PK)              │          │ id (PK)              │
│ user_id (FK)         │          │ name                 │
│ parent_task_id (FK)  │◄─┐       │ color                │
│ title                │  │       │ user_id (FK)         │
│ description          │  │       │ createdAt            │
│ status (enum)        │  │       │ updatedAt            │
│ priority (enum)      │  │       └──────────────────────┘
│ startDate            │  │
│ dueDate              │  │       ┌──────────────────────┐
│ completedAt          │  │       │    MediaObject       │
│ sortOrder            │  │       │──────────────────────│
│ isArchived           │  │       │ id (PK)              │
│ isRecurringTemplate  │  │       │ filePath             │
│ createdAt            │  │       │ fileName             │
│ updatedAt            │  │       │ mimeType             │
└──────────────────────┘  │       │ size                 │
           │              │       │ uploadedBy (FK)      │
           │ Self-ref 1:N │       │ createdAt            │
           └──────────────┘       └──────────────────────┘

┌──────────────────────┐
│   RefreshToken       │
│──────────────────────│
│ id (PK)              │
│ refresh_token (text) │
│ username (string)    │
│ valid (datetime)     │
└──────────────────────┘

┌──────────────────────┐
│  RecurrenceRule      │
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

## Core Entities

### 1. User Entity

**Purpose:** Authentication & user data

**Location:** `/backend/src/Entity/User.php`

```php
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity('email')]
#[ORM\Table(name: '`users`')]
class User extends AbstractEntity implements UserInterface
{
    #[ORM\Column(type: 'string', unique: true)]
    protected string $email;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $password = null;    // Nullable for OAuth users

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

    // User preferences
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

    // Relationships
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $tasks;

    #[ORM\OneToMany(targetEntity: Tag::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $tags;
}
```

**Fields:**
- `id`: Primary key (auto-increment)
- `email`: Unique, required for all users
- `password`: Hashed password (nullable for Google OAuth users)
- `googleId`: Google OAuth identifier
- `roles`: JSON array of roles (ROLE_USER, ROLE_ADMIN)
- `theme`: UI theme preference (light/dark)
- `language`: App language (ru/en)
- `timezone`: User timezone for date display
- `notificationSettings`: JSON of notification preferences

---

### 2. Task Entity

**Purpose:** Core task management with unlimited nesting

**Location:** `/backend/src/Entity/Task.php`

```php
#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: '`task`', indexes: [
    // 13 composite indexes for performance
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

    // Relationships
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // Self-referencing for unlimited nesting
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

**Enums:**

```php
// TaskStatus enum
enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}

// TaskPriority enum
enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';
}
```

**Unlimited Nesting Example:**

```php
// Parent Task
$project = new Task();
$project->setTitle('Launch Website');

// Level 1 Subtask
$design = new Task();
$design->setTitle('Design');
$design->setParentTask($project);

// Level 2 Subtask
$wireframes = new Task();
$wireframes->setTitle('Create Wireframes');
$wireframes->setParentTask($design);

// Level 3 Subtask (and so on...)
$homepage = new Task();
$homepage->setTitle('Homepage Wireframe');
$homepage->setParentTask($wireframes);

// No depth limit!
```

---

### 3. Tag Entity

**Purpose:** Categorize tasks with colored tags

**Location:** `/backend/src/Entity/Tag.php`

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

**Fields:**
- `name`: Tag name (max 50 chars)
- `color`: Hex color code (#3b82f6)
- `user`: Owner of the tag
- `tasks`: Tasks associated with this tag (many-to-many)

**Usage Pattern:**

```php
// Create tag
$tag = new Tag();
$tag->setName('Work');
$tag->setColor('#3b82f6');
$tag->setUser($user);

// Associate with task
$task->addTag($tag);
```

---

### 4. RefreshToken Entity

**Purpose:** Store JWT refresh tokens

**Location:** `/backend/src/Entity/RefreshToken.php`

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

**Fields:**
- `refreshToken`: Hashed token string
- `username`: User email
- `valid`: Expiration datetime (7 days from creation)

---

### 5. MediaObject Entity

**Purpose:** File uploads and attachments

**Location:** `/backend/src/Entity/MediaObject.php`

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

## Relationships

### 1. User ↔ Task (One-to-Many)

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

**Behavior:**
- User can have many tasks
- Task belongs to ONE user (required)
- When user deleted → all their tasks deleted

---

### 2. Task ↔ Task (Self-Referencing, Unlimited Nesting)

```php
// Parent relationship
#[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subtasks')]
private ?self $parentTask = null;

// Children relationship
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

**Behavior:**
- Task can have ONE parent (nullable)
- Task can have MANY children
- When parent deleted → children deleted
- No depth limit!

**Recursive Query (PostgreSQL CTE):**

```sql
WITH RECURSIVE subtasks AS (
    -- Base case: Start with parent task
    SELECT * FROM task WHERE id = 123

    UNION ALL

    -- Recursive case: Get all children
    SELECT t.*
    FROM task t
    INNER JOIN subtasks s ON t.parent_task_id = s.id
)
SELECT * FROM subtasks;
```

---

### 3. Task ↔ Tag (Many-to-Many)

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

**Behavior:**
- Task can have MANY tags
- Tag can be on MANY tasks
- Junction table: `task_tags`

---

## Indexing Strategy

### Why 13 Indexes on Task Table?

Task table is the most queried table. Indexes optimize common query patterns.

```php
// Index 1: User + Parent (most common query)
new ORM\Index(name: 'idx_task_user_parent', columns: ['user_id', 'parent_task_id'])

// Index 2: User + Status (filtering by status)
new ORM\Index(name: 'idx_task_user_status', columns: ['user_id', 'status'])

// Index 3: User + Created At (timeline queries)
new ORM\Index(name: 'idx_task_user_created_at', columns: ['user_id', 'created_at'])

// Index 4: User + Completed At (analytics)
new ORM\Index(name: 'idx_task_user_completed_at', columns: ['user_id', 'completed_at'])

// Index 5: User + Due Date (deadline filtering)
new ORM\Index(name: 'idx_task_user_due_date', columns: ['user_id', 'due_date'])

// Index 6: User + Priority (sorting by priority)
new ORM\Index(name: 'idx_task_user_priority', columns: ['user_id', 'priority'])

// Index 7: User + Archived (exclude archived tasks)
new ORM\Index(name: 'idx_task_user_archived', columns: ['user_id', 'is_archived'])

// Index 8: User + Parent + Archived (task list query)
new ORM\Index(name: 'idx_task_user_parent_archived', columns: ['user_id', 'parent_task_id', 'is_archived'])

// Index 9: User + Parent + Status (filtered task list)
new ORM\Index(name: 'idx_task_user_parent_status', columns: ['user_id', 'parent_task_id', 'status'])

// Index 10: User + Parent + Created (sorted task list)
new ORM\Index(name: 'idx_task_user_parent_created', columns: ['user_id', 'parent_task_id', 'created_at'])

// Index 11: User + Parent + Completed (analytics)
new ORM\Index(name: 'idx_task_user_parent_completed', columns: ['user_id', 'parent_task_id', 'completed_at'])

// Index 12: User + Sort Order (manual ordering)
new ORM\Index(name: 'idx_task_user_sort_order', columns: ['user_id', 'sort_order'])
```

### Index Benefits

**Before Indexes:**
```sql
EXPLAIN ANALYZE
SELECT * FROM task WHERE user_id = 5 AND parent_task_id IS NULL;

→ Seq Scan on task  (cost=0.00..1245.00 rows=500)
→ Execution time: 45ms
```

**After Indexes:**
```sql
EXPLAIN ANALYZE
SELECT * FROM task WHERE user_id = 5 AND parent_task_id IS NULL;

→ Index Scan using idx_task_user_parent  (cost=0.15..8.17 rows=500)
→ Execution time: 0.8ms
```

**56x faster!** (45ms → 0.8ms)

---

## Migrations Workflow

### Create Migration

```bash
# Auto-generate migration from entity changes
docker exec backend-php83 php bin/console make:migration

# Review the generated migration file
# Location: /backend/migrations/VersionYYYYMMDDHHMMSS.php
```

### Example Migration

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

    // Create indexes
    $this->addSql('CREATE INDEX idx_task_user_parent ON task (user_id, parent_task_id)');
    $this->addSql('CREATE INDEX idx_task_user_status ON task (user_id, status)');
    // ... more indexes
}

public function down(Schema $schema): void
{
    $this->addSql('DROP TABLE task');
}
```

### Run Migration

```bash
# Execute migrations
docker exec backend-php83 php bin/console doctrine:migrations:migrate

# Rollback last migration
docker exec backend-php83 php bin/console doctrine:migrations:migrate prev
```

---

## Query Optimization

### Eager Loading

```php
// ❌ BAD: N+1 query problem
$tasks = $taskRepository->findAll();
foreach ($tasks as $task) {
    echo $task->getUser()->getEmail();  // Separate query per task!
}

// ✅ GOOD: Eager load with JOIN
$tasks = $taskRepository->createQueryBuilder('t')
    ->leftJoin('t.user', 'u')
    ->addSelect('u')
    ->leftJoin('t.tags', 'tag')
    ->addSelect('tag')
    ->getQuery()
    ->getResult();

// Now $task->getUser() is already loaded (no additional query)
```

### Partial Selects

```php
// ❌ BAD: Fetch all fields when you only need a few
$tasks = $taskRepository->findAll();  // Fetches description (5000 chars)

// ✅ GOOD: Fetch only needed fields
$tasks = $taskRepository->createQueryBuilder('t')
    ->select('t.id, t.title, t.status')
    ->getQuery()
    ->getArrayResult();
```

---

## Related Documents

### Must Read Next
- **[Architecture](ARCHITECTURE.md)** - How to use these entities
- **[Cache System](CACHE_SYSTEM.md)** - Caching query results

### For Reference
- **[API Reference](API_REFERENCE.md)** - Endpoints using these entities

---

*Last updated: 2025-01-05*
*Database schema version: 1.0*
