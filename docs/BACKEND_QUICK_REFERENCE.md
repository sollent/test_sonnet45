# Backend Quick Reference - TaskFlow 🚀

**Быстрая справка для разработчиков**

---

## 📌 Структура Одной Строки

```
Request → Controller → Service → Repository → Doctrine → PostgreSQL → Response
                ↑
         Security/Voters
         (JWT + OAuth)
```

---

## 🎯 5 Ключевых Сущностей

| Сущность | Основные Поля | Ключевое Отношение |
|----------|---------------|--------------------|
| **User** | email, password, googleId, name, theme, language, timezone | OneToMany: tasks, tags |
| **Task** | title, status, priority, dueDate, sortOrder | ManyToOne: user; OneToMany: subtasks; ManyToMany: tags |
| **Tag** | name, color, icon, usageCount | ManyToOne: user; ManyToMany: tasks |
| **RecurrenceRule** | recurrenceType, interval, daysOfWeek, nextOccurrenceDate | OneToOne: templateTask |
| **MediaObject** | filename, hash, uploadedBy | Used by: tasks via ManyToMany |

---

## 🔌 8 API Контроллеров

```
TaskController (24.4 KB)
  ├─ GET  /api/tasks              List with filters
  ├─ POST /api/tasks              Create
  ├─ GET  /api/tasks/{id}         Get details
  ├─ PUT  /api/tasks/{id}         Update
  └─ PATCH/DELETE                 Status change, Delete

TagController (9.5 KB)
  ├─ GET  /api/tags               List
  ├─ POST /api/tags               Create
  ├─ PUT  /api/tags/{id}          Update
  └─ DELETE /api/tags/{id}        Delete

AnalyticsController (8.6 KB)
  └─ GET /api/analytics/*         Stats & metrics

RecurrenceController (11 KB)
  ├─ POST /api/recurrence         Create rule
  └─ GET  /api/recurrence         List active

MediaObjectController (4 KB)
  └─ POST/DELETE /api/media       Upload/delete files

AttachmentController (6.4 KB)
  └─ POST/DELETE /api/attachments Link files to tasks

UserProfileController (2.4 KB)
  └─ GET/PATCH /api/profile       User settings

EnumController (3.8 KB)
  └─ GET /api/enums               Priority, Status lists

TranslationController (2.6 KB)
  └─ GET /api/translations        i18n strings
```

---

## 🔐 3 Способа Аутентификации

```
1. Классическая (Email/Password)
   POST /api/auth
   { "email": "user@example.com", "password": "secret" }
   ← { "token": "JWT...", "refreshToken": "...", "refreshTokenExpiration": 123456 }

2. Google OAuth 2.0
   POST /api/auth/google
   { "credential": "google_id_token" }
   ← { "token": "JWT...", "refreshToken": "...", "refreshTokenExpiration": 123456 }

3. Refresh Token
   POST /api/token/refresh
   { "refreshToken": "abc123..." }
   ← { "token": "new_JWT...", "refreshToken": "new_refresh...", ... }
```

**Все защищены:** `#[IsGranted('ROLE_USER')]` на эндпоинтах

---

## 🛠️ 11 Core Services

```
TaskService (12.9 KB)
  └─ Create, Update, Delete, Complete, Archive, Duplicate tasks

RecurrenceService (11.6 KB)
  └─ Create rules, Generate next occurrences

AnalyticsService (26.3 KB)
  └─ Statistics, completion rates, forecasts

UserRegistrationService (1.4 KB)
  └─ Register new users

UserProfileService (2.1 KB)
  └─ Update profile, theme, language, timezone

FileUploadService (3.7 KB)
  └─ Upload, validate, hash files (deduplication)

MediaObjectService (3.7 KB)
  └─ Manage uploaded files

TranslationService (3.3 KB)
  └─ Multilingual support (EN/RU/UK)

EnumTranslatorService (1.7 KB)
  └─ Translate enums to user language

GoogleAuthenticator (1 KB)
  └─ Decode Google JWT, create/get user

+ Recurrence/DailyStrategy, WeeklyStrategy, etc.
```

---

## 📊 7 Repositories

```
TaskRepository (72 KB!) ⭐ САМЫЙ БОЛЬШОЙ
  ├─ findUserTasks()                     Все задачи пользователя
  ├─ findTodayTasks()                    Задачи на сегодня
  ├─ findUpcomingTasks()                 Предстоящие
  ├─ findOverdueTasks()                  Просроченные
  ├─ findTasksByTag()                    По тегу
  ├─ applyFilters()                      Complex filtering
  └─ Eager load: tags, user, subtasks    ← NO N+1 problems!

UserRepository
  ├─ findByEmail()
  └─ findByGoogleId()

TagRepository
  ├─ findOrCreateByNames()
  └─ findByUser()

RecurrenceRuleRepository
  ├─ findActive()
  └─ findByTemplate()

MediaObjectRepository
  ├─ findByHash()                        Поиск дубликатов
  └─ findByUser()

TaskAttachmentRepository & AuditLogRepository
```

---

## 🔄 Поток Создания Задачи

```
1. Frontend отправляет JSON:
   {
     "title": "Buy milk",
     "priority": "high",
     "tags": ["shopping"],
     "dueDate": "2025-11-15T17:00:00Z"
   }

2. Controller::create() получает + десериализует в CreateTaskDto

3. Validator проверяет constraints:
   ✓ title: required, 1-255 chars
   ✓ priority: enum(low, medium, high, urgent)
   ✓ dueDate: valid ISO string

4. TaskService::createTask() выполняет:
   ✓ Создает Task entity
   ✓ Ищет или создает Tags через TagRepository
   ✓ Загружает MediaObjects (если есть)
   ✓ Persists в EntityManager
   ✓ Вызывает flush() = INSERT в БД

5. Serializer конвертирует Task → JSON с Groups:
   {
     "id": 42,
     "title": "Buy milk",
     "priority": "high",
     "status": "pending",
     "tags": [{"id": 1, "name": "shopping", "color": "#3B82F6"}],
     "createdAt": "2025-11-14T12:30:00Z",
     "updatedAt": "2025-11-14T12:30:00Z"
   }

6. Response: 201 Created + Location: /api/tasks/42
```

---

## 🔒 Security Architecture

```
┌─────────────────────────────────────────┐
│        Request comes to /api/*          │
└──────────────┬──────────────────────────┘
               │
               ├─→ Is JWT token valid?
               │   - Check signature
               │   - Check expiration
               │   - Extract user from claims
               │
               ├─→ Load User from database
               │   - Email from JWT sub claim
               │   - Ensure user exists
               │
               ├─→ Check @IsGranted('ROLE_USER')
               │   - User must be authenticated
               │
               ├─→ Check SecurityVoters
               │   - TaskVoter::canEdit(task, user)
               │   - Only owner can edit
               │
               └─→ Execute Controller action
```

**JWT Конфиг:**
- Algorithm: RSA256
- TTL: 3600 seconds (1 hour)
- Keys: /config/jwt/ (RSA private/public)
- Refresh: 30 days

---

## 📈 Performance Tips

### DO: ✅

```php
// Eager load relations
->leftJoin('t.tags', 'tag')
->addSelect('tag')  // ← ONE query, not N queries!

// Use compound indexes
// Queries filter by (user_id, status) or (user_id, due_date)
// = FAST with idx_task_user_status

// Cache in production
// APCu for metadata (1 hour TTL)
// Can query = 5-10x faster

// Type hints everywhere
public function createTask(CreateTaskDto $dto, User $user): Task { ... }
```

### DON'T: ❌

```php
// N+1 queries
foreach ($tasks as $task) {
    echo $task->getTags();  // ← SELECT * FROM tags N times!
}

// No indexes
// WHERE user_id = ? AND priority = ?
// = FULL table scan if no idx_task_user_priority

// Using fallback in production
${POSTGRES_PASSWORD:-password}  // ← NEVER!
```

---

## 🎨 DTOs Structure

### Request DTOs → Controllers

```
CreateTaskDto {
  + title: string (required)
  + description: ?string
  + status: TaskStatus (enum)
  + priority: TaskPriority (enum)
  + startDate: ?string (ISO format)
  + dueDate: ?string (ISO format)
  + parentTaskId: ?int
  + tags: array (tag names)
  + mediaIds: array (file IDs)
}

UpdateTaskDto {
  + [same fields, some optional]
}

TaskFilterDto {
  + statuses: ?array (enum values)
  + priorities: ?array (enum values)
  + tags: ?array (tag IDs)
  + dateFrom: ?string (ISO format)
  + dateTo: ?string (ISO format)
  + search: ?string (title search)
  + view: string ('all', 'today', 'upcoming', 'overdue')
}
```

### Response DTOs ← Controllers

```
TaskResponseDto {
  + id: int
  + title: string
  + description: ?string
  + status: TaskStatus
  + priority: TaskPriority
  + startDate: ?string (ISO)
  + dueDate: ?string (ISO)
  + completedAt: ?string (ISO)
  + sortOrder: int
  + isArchived: bool
  + tags: TagDto[] (with id, name, color, icon)
  + subtasks: TaskResponseDto[] (recursive)
  + createdAt: string (ISO)
  + updatedAt: string (ISO)
}
```

---

## 🗂️ Enums (Modern PHP 8.1+)

```php
enum TaskStatus: string {
  case PENDING = 'pending'
  case IN_PROGRESS = 'in_progress'
  case COMPLETED = 'completed'
  case CANCELLED = 'cancelled'
  
  public function getLabel(): string { ... }
  public function getColor(): string { ... }
  public function getIcon(): string { ... }
}

enum TaskPriority: string {
  case LOW = 'low'
  case MEDIUM = 'medium'
  case HIGH = 'high'
  case URGENT = 'urgent'
  
  public function getWeight(): int { ... }
  // Use for sorting!
}
```

---

## 🔧 Environment Variables (Production)

```bash
# .env.docker.prod (FAIL-FAST = no defaults!)
POSTGRES_DB=${POSTGRES_DB}              # БЕЗ fallback
POSTGRES_USER=${POSTGRES_USER}
POSTGRES_PASSWORD=${POSTGRES_PASSWORD}
RABBITMQ_USER=${RABBITMQ_USER}
RABBITMQ_PASSWORD=${RABBITMQ_PASSWORD}

# apps/backend/.env.prod (also no defaults!)
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=${APP_SECRET}                # БЕЗ fallback!
DATABASE_URL="postgresql://${POSTGRES_USER}:${POSTGRES_PASSWORD}@psql16:5432/${POSTGRES_DB}?..."
JWT_PASSPHRASE=${JWT_PASSPHRASE}        # БЕЗ fallback!
```

**Why?** If not set → Docker/Symfony crashes immediately ✅ (better than running with wrong password!)

---

## 🧪 Testing

```bash
# Run all tests
make test

# Specific test file
php bin/phpunit tests/Unit/Service/TaskServiceTest.php

# With coverage
php bin/phpunit --coverage-html coverage/

# Watch mode
php bin/phpunit --watch
```

**Structure:**
```
tests/
├── Unit/                      (Fast, isolated)
│   ├── Service/
│   ├── Dto/
│   ├── Entity/
│   ├── Repository/
│   └── Security/
├── Integration/               (With database)
│   ├── Controller/Api/
│   └── Service/
└── Fixtures/                  (Test data)
```

---

## 🚨 Common Issues

| Проблема | Решение |
|----------|---------|
| N+1 queries | Eager load: `leftJoin()->addSelect()` |
| Circular refs in serialization | Use Groups: `#[Groups(['task:list'])]` |
| Date parsing errors | Use DateTimeImmutable + ISO 8601 format |
| JWT expired | Refresh with `/api/token/refresh` |
| CORS blocked | Check `nelmio_cors.yaml` domain regex |
| 403 Forbidden | Check SecurityVoter or `@IsGranted()` |
| Slow queries | Add compound index: `idx_task_user_status` |

---

## 📚 Key Files to Know

```
Must Read:
  ✓ src/Entity/Task.php                 (Schema understanding)
  ✓ src/Service/TaskService.php         (Business logic)
  ✓ src/Repository/Database/TaskRepository.php  (Queries)
  ✓ config/packages/security.yaml       (JWT + OAuth)
  ✓ config/packages/nelmio_cors.yaml    (CORS)

Should Know:
  ✓ src/Dto/Request/Task/              (Input validation)
  ✓ src/Controller/Api/TaskController.php  (Endpoints)
  ✓ migrations/                         (Schema versions)

Deep Dive:
  ✓ src/Security/GoogleAuthenticator.php    (OAuth flow)
  ✓ src/Service/RecurrenceService.php       (Complex logic)
  ✓ src/Service/AnalyticsService.php        (Stats)
```

---

## 🎯 One-Minute Answers

**Q: Как добавить новый эндпоинт?**
A: Create method in Controller + route attribute `#[Route('/api/...')]`

**Q: Как добавить новое поле в Task?**
A: Edit Entity property + Run `php bin/console make:migration` + Migrate

**Q: Как изменить CORS?**
A: Edit `config/packages/nelmio_cors.yaml` + Restart container

**Q: Как юзер логинится?**
A: `POST /api/auth` (email/password) или `POST /api/auth/google` (Google token)

**Q: Почему мой запрос медленный?**
A: Check: N+1 queries (add eager load), missing index (add compound index), cache (APCu)

**Q: Как развернуть в production?**
A: `docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose-prod.yml up -d`

---

## ✨ Best Practices Checklist

```
Before writing code:
  ☐ Read CODING_STANDARDS.md (type hints, SOLID, enums)
  ☐ Check if similar feature exists
  ☐ Plan DTO structure (input/output)

Writing code:
  ☐ Type hints everywhere (PHP 8.3)
  ☐ Use Enums not constants
  ☐ Put logic in Services, not Controllers
  ☐ Validate in DTO + Entity constraints
  ☐ Eager load relations (no N+1)
  ☐ Add groups for serialization

Testing:
  ☐ Unit test Services (fast)
  ☐ Integration test Repositories
  ☐ Functional test Controllers
  ☐ Test error cases, not just happy path

Before commit:
  ☐ make quality-check passes
  ☐ Tests pass
  ☐ No console errors/warnings
  ☐ CORS tested (if API change)
```

---

**Last Updated**: 2025-11-14  
**Version**: 1.0
