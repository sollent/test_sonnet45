# Backend Архитектура - TaskFlow (Symfony 7.1) 🏗️

**Дата анализа**: 2025-11-14  
**Версия Symfony**: 7.1  
**Версия PHP**: 8.3+  
**СУБД**: PostgreSQL 16  
**Строк кода**: ~17,149 PHP  

---

## 📊 Структура Backend

```
apps/backend/
├── src/                           # Исходный код приложения (~17K LOC)
│   ├── Command/                   # CLI команды (Symfony Console)
│   ├── Controller/                # HTTP Контроллеры
│   │   ├── Api/                   # REST API контроллеры (9 файлов)
│   │   ├── Auth/                  # Аутентификация (Google OAuth)
│   │   └── Admin/                 # EasyAdmin панель
│   ├── Entity/                    # Doctrine ORM сущности (9 файлов)
│   ├── Repository/Database/       # Data Access Layer (8 репозиториев)
│   ├── Service/                   # Бизнес-логика (11 сервисов)
│   ├── Dto/Request/              # DTO для входящих данных
│   ├── Dto/Response/             # DTO для исходящих данных
│   ├── Enum/                      # Перечисления (Enums)
│   ├── Security/                  # Security (Voters, Authenticators)
│   ├── Serializer/                # Custom Serializer Normalizers
│   ├── EventListener/             # Event Listeners (Doctrine)
│   ├── EventSubscriber/           # Event Subscribers
│   ├── Exception/                 # Custom Exceptions
│   ├── Doctrine/                  # Custom DQL функции
│   └── Kernel.php                 # Symfony Kernel
│
├── config/                        # Конфигурация
│   ├── packages/                  # Bundle конфиги
│   │   ├── security.yaml          # JWT + OAuth2 + Form Auth
│   │   ├── nelmio_cors.yaml       # CORS конфигурация
│   │   ├── nelmio_api_doc.yaml    # Swagger/OpenAPI документация
│   │   ├── doctrine.yaml          # ORM конфигурация + Кеширование
│   │   ├── lexik_jwt_authentication.yaml
│   │   ├── gesdinet_jwt_refresh_token.yaml
│   │   └── ...
│   ├── packages/prod/             # Production overrides
│   │   └── doctrine.yaml          # APCu кеширование для production
│   ├── routes/                    # Route конфигурация
│   ├── routes.yaml                # Main route mapping
│   ├── services.yaml              # Service container конфигурация
│   └── jwt/                       # JWT ключи (в .gitignore)
│
├── migrations/                    # Doctrine миграции (13 файлов)
├── tests/                         # Тесты (PHPUnit)
├── public/                        # Публичная папка
├── var/                           # Временные файлы (logs, cache)
└── composer.json                  # Dependencies

```

---

## 🎯 Ключевые Сущности (Entities)

### 1. User
```
Entity: App\Entity\User implements UserInterface, PasswordAuthenticatedUserInterface

Свойства:
- email                    (уникальный, email юзера)
- password                 (хешированный пароль для классической аутентификации)
- plainPassword            (временное хранилище плейнтекста для валидации)
- roles                    (JSON массив ролей)
- googleId                 (ID для Google OAuth)
- googleUserName           (имя от Google)
- name                     (имя юзера)
- avatar                   (URL аватара)
- theme                    (light/dark, default: light)
- language                 (en/ru/uk, default: ru)
- timezone                 (default: Europe/Moscow)
- notificationSettings     (JSON с настройками уведомлений)

Отношения:
- OneToMany tasks          (каскадное удаление)
- OneToMany tags           (каскадное удаление)

Методы:
- getRoles()               (добавляет ROLE_USER автоматически)
- hasGoogleAuth()          (проверка Google аутентификации)
- hasPassword()            (проверка наличия пароля)
- eraseCredentials()       (очистка plainPassword)
```

### 2. Task
```
Entity: App\Entity\Task extends AbstractEntity

Свойства:
- title                    (VARCHAR 255, required)
- description              (TEXT, nullable, max 5000 chars)
- status                   (Enum: PENDING, IN_PROGRESS, COMPLETED, CANCELLED)
- priority                 (Enum: LOW, MEDIUM, HIGH, URGENT)
- startDate                (DateTimeImmutable, nullable)
- dueDate                  (DateTimeImmutable, nullable)
- completedAt              (DateTimeImmutable, auto-set when completed)
- sortOrder                (INT для сортировки)
- isArchived               (BOOLEAN)
- isRecurringTemplate      (BOOLEAN, для шаблонов повторяющихся задач)
- generatedFromRule        (ForeignKey к RecurrenceRule если это сгенерированная копия)

Отношения:
- ManyToOne user           (владелец задачи)
- ManyToOne parentTask     (для подзадач - unlimited nesting)
- OneToMany subtasks       (cascade persist, orphanRemoval)
- ManyToMany tags          (JOIN table: task_tags)
- ManyToMany mediaObjects  (JOIN table: task_media)
- OneToMany attachments    (TaskAttachment)
- OneToOne recurrenceRule  (двусторонняя связь с шаблоном повторения)

Методы:
- isCompleted()            (проверка status === COMPLETED)
- isOverdue()              (проверка dueDate < now && !completed)
- getCompletionProgress()  (процент выполненных подзадач: 0-100%)

Индексы (оптимизация запросов):
- idx_task_user_parent             (user_id, parent_task_id)
- idx_task_user_status             (user_id, status)
- idx_task_user_priority           (user_id, priority)
- idx_task_user_archived           (user_id, is_archived)
- idx_task_user_due_date           (user_id, due_date)
- idx_task_user_completed_at       (user_id, completed_at)
- idx_task_user_created_at         (user_id, created_at)
- idx_task_user_parent_archived    (user_id, parent_task_id, is_archived)
- idx_task_user_parent_status      (user_id, parent_task_id, status)
- idx_task_user_status_archived    (user_id, status, is_archived)
- idx_task_user_due_status         (user_id, due_date, status)
```

### 3. Tag
```
Entity: App\Entity\Tag extends AbstractEntity

Свойства:
- name                     (VARCHAR 50, required, pattern: /^[\w\s\-]+$/u)
- color                    (VARCHAR 7, HEX color #RRGGBB, default: #3B82F6)
- icon                     (VARCHAR 255, nullable)
- usageCount               (INT, отслеживание использования)

Отношения:
- ManyToOne user           (владелец тега)
- ManyToMany tasks         (обратная ссылка на задачи)

Методы:
- updateUsageCount()       (автоматический подсчет на основе $tasks->count())

Уникальность:
- UNIQUE(name, user_id)    (один тег с таким именем на пользователя)
```

### 4. RecurrenceRule (для повторяющихся задач)
```
Entity: App\Entity\RecurrenceRule

Свойства:
- recurrenceType           (Enum: daily, weekly, monthly, yearly, custom)
- interval                 (INT, для custom: каждые N дней)
- daysOfWeek               (JSON [1,2,3,4,5] для weekly)
- dayOfMonth               (INT для monthly: 1-31)
- monthOfYear              (INT для yearly: 1-12)
- endDate                  (DateTimeInterface, когда прекратить генерацию)
- maxOccurrences           (INT, максимум количество копий)
- currentOccurrences       (INT, сколько уже сгенерировано)
- nextOccurrenceDate       (DateTimeInterface для планирования)

Отношения:
- OneToOne templateTask    (Task которая является шаблоном)

Стратегия Strategy Pattern:
- daily:  Генерируется 1 копия каждый день
- weekly: На выбранные дни недели
- monthly: На определенный день месяца
- yearly: На определенный день года
- custom: На основе cron выражения (если интегрировано)
```

### 5. Другие сущности
- **MediaObject**: Загруженные файлы/изображения (с хешированием для поиска дубликатов)
- **TaskAttachment**: Связь Task с загруженными файлами
- **RefreshToken**: Для JWT refresh token механизма
- **AuditLog**: Для аудита действий (опционально)

---

## 🎨 Контроллеры (Слой API)

### API Контроллеры (`src/Controller/Api/`)

| Контроллер | Маршрут | Методы | Ответственность |
|-----------|---------|--------|-----------------|
| **TaskController** | `/api/tasks` | GET, POST, PUT, PATCH, DELETE | CRUD задач, фильтрация, поиск |
| **TagController** | `/api/tags` | GET, POST, PUT, DELETE | Управление тегами |
| **AnalyticsController** | `/api/analytics` | GET | Статистика, метрики, отчеты |
| **RecurrenceController** | `/api/recurrence` | GET, POST, PUT, DELETE | Управление повторяющимися задачами |
| **MediaObjectController** | `/api/media` | POST, DELETE | Загрузка/удаление файлов |
| **AttachmentController** | `/api/attachments` | POST, DELETE | Управление вложениями |
| **UserProfileController** | `/api/profile` | GET, PATCH | Профиль юзера, настройки |
| **EnumController** | `/api/enums` | GET | Получение справочников (Priority, Status) |
| **TranslationController** | `/api/translations` | GET | Multilingual strings |

### Auth Контроллеры (`src/Controller/Auth/`)

| Контроллер | Маршрут | Метод | Описание |
|-----------|---------|-------|---------|
| **GoogleAuthController** | `/api/auth/google` | POST | Google OAuth2 аутентификация |
| **(JWT автоматически)** | `/api/auth` | POST | Классическая аутентификация (email/password) |
| **(JWT автоматически)** | `/api/token/refresh` | POST | Обновление JWT токена |

### Admin Контроллеры (`src/Controller/Admin/`)
- **DashboardController**: EasyAdmin панель администратора
- **UserCrudController**: Управление юзерами
- **TaskCrudController**: Управление задачами
- **TagCrudController**: Управление тегами

---

## 🔧 Сервисы (Бизнес-логика)

### Core Services

#### 1. TaskService
```php
Функции:
- createTask(CreateTaskDto $dto, User $user): Task
- updateTask(Task $task, UpdateTaskDto $dto): Task
- deleteTask(Task $task): void
- completeTask(Task $task): Task
- reopenTask(Task $task): Task
- archiveTask(Task $task): Task
- unarchiveTask(Task $task): Task
- duplicateTask(Task $task, User $user): Task
- updateSortOrder(array $taskIds): void

Зависимости:
- TaskRepository
- TagRepository
- MediaObjectRepository
- RecurrenceService
- EntityManager (Doctrine)
```

#### 2. RecurrenceService
```php
Функции:
- createRecurrenceRule(CreateRecurrenceDto $dto, Task $template): RecurrenceRule
- generateNextOccurrence(RecurrenceRule $rule): ?Task
- processAllRecurrenceRules(): void
- validateRecurrenceRule(RecurrenceRule $rule): void

Strategy Pattern:
- DailyStrategy      (Генерирует ежедневно)
- WeeklyStrategy     (Генерирует на выбранные дни недели)
- MonthlyStrategy    (Генерирует на определенный день месяца)
- YearlyStrategy     (Генерирует на определенный день года)
- CustomStrategy     (На основе custom интервала)
```

#### 3. AnalyticsService
```php
Функции:
- getUserStatistics(User $user): array
- getTasksCompletionRate(User $user): float
- getTasksByPriority(User $user): array
- getTasksByStatus(User $user): array
- getUpcomingTasks(User $user, DateTimeInterface $from, $to): array
- getTasksOverdueCount(User $user): int
- getAverageCompletionTime(User $user): ?int
- getTasksPerDay(User $user, DateTimeInterface $from, $to): array
```

#### 4. UserRegistrationService
```php
Функции:
- register(UserRegistrationRequestDto $dto): User

Делает:
- Валидирует email
- Хеширует пароль
- Создает юзера в БД
- Отправляет confirmation письмо (опционально)
```

#### 5. FileUploadService
```php
Функции:
- uploadFile(UploadedFile $file, User $user): MediaObject
- deleteFile(MediaObject $media): void
- getFileHash(UploadedFile $file): string

Особенности:
- Генерирует уникальное имя файла
- Проверяет размер и MIME тип
- Хеширует для поиска дубликатов
```

#### 6. TranslationService
```php
Функции:
- translate(string $key, string $locale = 'ru'): string
- getAvailableLocales(): array
```

---

## 📦 Data Access Layer (Repositories)

### TaskRepository (72KB, самый большой!)

**Специализированные методы для оптимизации:**

```php
// Основные queries
findUserTasks($user, $status, $includeArchived, $onlyParentTasks)
findTodayTasks($user, $filters, $onlyWithSubtasks)
findUpcomingTasks($user, DateInterface $from, $to)
findOverdueTasks($user)
findTasksWithParent($parentTaskId)
findTasksByTag($tag, $user)
findArchivedTasks($user)

// Фильтрация (сложные WHERE условия)
applyFilters(QueryBuilder $qb, TaskFilterDto $filters)
addStatusFilter($qb, $statuses)
addPriorityFilter($qb, $priorities)
addDateRangeFilter($qb, $from, $to)
addTagFilter($qb, $tags)

// Оптимизация N+1 проблем
Eager loading через leftJoin:
  - tags (для отображения в списке)
  - user (владелец)
  - recurrenceRule (для повторяющихся)
  - subtasks (для дерева задач)
```

### Другие Repositories

| Repository | Методы | Особенность |
|-----------|--------|-----------|
| **UserRepository** | findByEmail, findByGoogleId | Поиск по email/Google ID |
| **TagRepository** | findOrCreateByNames, findByUser | Создание/поиск тегов |
| **RecurrenceRuleRepository** | findActive, findByTemplate | Поиск активных правил повторения |
| **MediaObjectRepository** | findByHash, findByUser | Поиск дубликатов по хешу |
| **TaskAttachmentRepository** | findByTask | Вложения к задаче |

---

## 🔐 Аутентификация и Авторизация

### JWT (JSON Web Tokens) Authentication

```
Поток аутентификации:

1. Классическая (email/password):
   POST /api/auth
   { "email": "user@gmail.com", "password": "secret" }
   ↓
   SecurityBundle: json_login -> checks credentials
   ↓
   LexikJWTAuthenticationBundle: generates JWT token
   ↓
   Response: { "token": "eyJ0eXAi...", "refreshToken": "abc123...", "refreshTokenExpiration": 1735689600 }

2. Google OAuth 2.0:
   POST /api/auth/google
   { "credential": "google_id_token" }
   ↓
   GoogleAuthenticator.loadUserFromDecodedJwt() decodes JWT with Google public keys
   ↓
   Создает или получает User по email
   ↓
   Response: JWT + RefreshToken

3. Refresh Token:
   POST /api/token/refresh
   { "refreshToken": "abc123..." }
   ↓
   GesdinetJWTRefreshTokenBundle validates refresh token
   ↓
   Генерирует новый JWT токен
   ↓
   Response: { "token": "new_jwt", "refreshToken": "new_refresh", ... }
```

### Конфигурация Security

```yaml
# config/packages/security.yaml

Firewalls:
  dev:        Отключена для dev tools
  admin:      Form login для /admin (с CSRF)
  api:        
    - pattern: ^/api/
    - stateless: true (no sessions)
    - provider: users (Doctrine User entity)
    - entry_point: jwt
    - json_login: /api/auth
    - jwt: ~
    - refresh_jwt: /api/token/refresh

Access Control:
  - ^/admin/login                   PUBLIC_ACCESS
  - ^/admin                          ROLE_ADMIN
  - ^/api/auth                       PUBLIC_ACCESS
  - ^/api/token/refresh              PUBLIC_ACCESS
  - ^/api/users (POST only)          PUBLIC_ACCESS (регистрация)
  - ^/api/auth/google                PUBLIC_ACCESS
  - ^/api                            IS_AUTHENTICATED_FULLY

Password Hashers: bcrypt (auto)
```

### Security Voters

Для granular permissions (владелец может редактировать только свои задачи):

```php
// Примеры Voters:
TaskVoter::canEdit(Task $task, User $user)
  → только владелец user_id === $user->id

TagVoter::canEdit(Tag $tag, User $user)
  → только владелец user_id === $user->id
```

### JWT Конфигурация

```yaml
lexik_jwt_authentication:
  secret_key: '%env(resolve:JWT_SECRET_KEY)%'      # Private key (RSA)
  public_key: '%env(resolve:JWT_PUBLIC_KEY)%'      # Public key (RSA)
  pass_phrase: '%env(JWT_PASSPHRASE)%'             # Passphrase for private key
  token_ttl: 3600                                  # 1 час для access token

gesdinet_jwt_refresh_token:
  refresh_token_class: App\Entity\RefreshToken
  ttl: '%env(int:JWT_REFRESH_TOKEN_TTL)%'          # 30 дней (обычно)
  ttl_update: '%env(bool:JWT_REFRESH_TOKEN_TTL_UPDATE)%'
  token_parameter_name: refreshToken
  return_expiration: true                          # Возвращать expiration timestamp
```

### Google OAuth2 Integration

```php
// GoogleAuthController.php
1. Frontend отправляет Google credential (ID token)
2. Декодируется с публичными ключами Google (JWK set)
3. Извлекается email и другая информация
4. Создается или получается User по email
5. Генерируется JWT токен
6. Возвращается access token + refresh token

Зависимости:
- firebase/php-jwt              (JWT декодирование)
- league/oauth2-google          (OAuth2 flow)
- knpuniversity/oauth2-client-bundle
```

---

## 🔄 CORS (Cross-Origin Resource Sharing)

```yaml
# config/packages/nelmio_cors.yaml

nelmio_cors:
  defaults:
    origin_regex: true
    allow_origin:
      - '^https?://localhost(:[0-9]+)?$'    # Local dev: localhost:3000, :5173, etc.
      - '^https://task\.nesty\.by$'         # Production domain
    allow_methods: [GET, OPTIONS, POST, PUT, PATCH, DELETE]
    allow_headers: [Content-Type, Authorization, X-Requested-With, Cache-Control]
    expose_headers: [Link, Content-Length, Content-Range]
    allow_credentials: true                  # Для cookies/auth
    max_age: 3600                           # Кеширование preflight запросов

  paths:
    '^/api':
      allow_origin: [^https?://localhost(:[0-9]+)?$, ^https://task\.nesty\.by$]
      allow_headers: ['*']
      allow_methods: [GET, OPTIONS, POST, PUT, PATCH, DELETE]
      allow_credentials: true
      max_age: 3600
```

**Production Notes:**
- ✅ НИКОГДА не используйте `allow_origin: ['*']` с credentials!
- ✅ Явно указывайте домены (regex)
- ✅ Localhost доступен в dev, но запрещен в prod
- ✅ OPTIONS preflight запросы кешируются на 1 час

---

## 📊 Production Конфигурация

### Doctrine ORM Оптимизация для Production

```yaml
# config/packages/prod/doctrine.yaml

doctrine:
  orm:
    # 1. Отключаем автогенерацию proxy классов
    #    Proxy должны быть сгенерированы один раз при deploy
    auto_generate_proxy_classes: false
    proxy_dir: '%kernel.build_dir%/doctrine/orm/Proxies'

    # 2. Query Cache (кеширование парсинга DQL запросов)
    #    APCu - самый быстрый in-memory кеш
    query_cache_driver:
      type: pool
      pool: doctrine.system_cache_pool

    # 3. Metadata Cache (КРИТИЧНО для производительности!)
    #    Кеширует метаданные всех Entity и их связей
    #    БЕЗ этого производительность деградирует в 5-10 раз!
    metadata_cache_driver:
      type: pool
      pool: doctrine.system_cache_pool

    # 4. Result Cache (опциональный, выборочное кеширование результатов)
    result_cache_driver:
      type: pool
      pool: doctrine.result_cache_pool

framework:
  cache:
    pools:
      # Metadata + Query Cache (долгое хранение)
      doctrine.system_cache_pool:
        adapter: cache.adapter.apcu
        default_lifetime: 3600  # 1 час (редко меняется)

      # Result Cache (короткое хранение)
      doctrine.result_cache_pool:
        adapter: cache.adapter.apcu
        default_lifetime: 300   # 5 минут

# APCu требует расширения PHP
# php.ini: extension=apcu.so
# Доступные адаптеры: apcu, redis, memcached, filesystem
```

### Environment Конфигурация

```bash
# .env.docker.prod (FAIL-FAST принцип безопасности)
POSTGRES_DB=${POSTGRES_DB}              # БЕЗ fallback!
POSTGRES_USER=${POSTGRES_USER}          # БЕЗ fallback!
POSTGRES_PASSWORD=${POSTGRES_PASSWORD}  # БЕЗ fallback!
RABBITMQ_USER=${RABBITMQ_USER}          # БЕЗ fallback!
RABBITMQ_PASSWORD=${RABBITMQ_PASSWORD}  # БЕЗ fallback!

# apps/backend/.env.prod
APP_ENV=prod
APP_SECRET=${APP_SECRET}                # БЕЗ fallback (Fail-Fast!)
APP_DEBUG=false
DATABASE_URL="postgresql://${POSTGRES_USER}:${POSTGRES_PASSWORD}@psql16:5432/${POSTGRES_DB}?..."
JWT_PASSPHRASE=${JWT_PASSPHRASE}        # БЕЗ fallback!
GOOGLE_CLIENT_ID=${GOOGLE_CLIENT_ID}    # БЕЗ fallback!
GOOGLE_CLIENT_SECRET=${GOOGLE_CLIENT_SECRET}
```

**Production Deployment:**
```bash
# Docker build с передачей credentials
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml up -d

# Все переменные ДОЛЖНЫ быть переданы (нет fallback!)
# Если не переданы → контейнер упадет с ошибкой ✅
```

---

## 📚 DTO (Data Transfer Objects)

### Request DTOs (`src/Dto/Request/`)

**Task DTOs:**
- `CreateTaskDto`: title, description, status, priority, startDate, dueDate, parentTaskId, tags, mediaIds
- `UpdateTaskDto`: Поля подлежащие обновлению
- `TaskFilterDto`: Параметры фильтрации для сложного поиска

**User DTOs:**
- `UserRegistrationRequestDto`: email, password, passwordConfirm
- `UpdateProfileDto`: name, avatar, language, timezone
- `UpdatePasswordDto`: oldPassword, newPassword, passwordConfirm
- `UpdateThemeDto`: theme (light/dark)
- `UpdateNotificationsDto`: notification settings

**Recurrence DTOs:**
- `CreateRecurrenceDto`: recurrenceType, interval, daysOfWeek, dayOfMonth, maxOccurrences, endDate

### Response DTOs (`src/Dto/Response/`)

**TaskResponseDto**: Полная информация о задаче + подзадачи + теги + вложения

Использует Symfony Serializer с Groups для разных уровней детализации:
- `task:list`   - миниялаль информация (для списков)
- `task:read`   - полная информация (для деталей)
- `task:write`  - поля для редактирования

---

## 🏗️ Слоистая Архитектура (Layered Architecture)

```
┌────────────────────────────────────────────────┐
│                    API Gateway (Nginx)          │
│              /api → port 8089                   │
└────────────────────────────────────────────────┘
                          ↓
┌────────────────────────────────────────────────┐
│         Presentation Layer (HTTP)               │
│              Controllers (8 API)                │
│         - TaskController                        │
│         - TagController                         │
│         - AnalyticsController                   │
│         - etc.                                  │
└────────────────────────────────────────────────┘
                          ↓
┌────────────────────────────────────────────────┐
│       Application/Business Logic Layer          │
│              Services (11)                      │
│         - TaskService                           │
│         - RecurrenceService                     │
│         - AnalyticsService                      │
│         - etc.                                  │
│                                                 │
│       Security Layer                            │
│         - SecurityVoters                        │
│         - GoogleAuthenticator                   │
└────────────────────────────────────────────────┘
                          ↓
┌────────────────────────────────────────────────┐
│      Data Access Layer (Repositories)           │
│         - TaskRepository (72KB)                 │
│         - UserRepository                        │
│         - TagRepository                         │
│         - RecurrenceRuleRepository              │
│         - etc.                                  │
└────────────────────────────────────────────────┘
                          ↓
┌────────────────────────────────────────────────┐
│   Persistence Layer (Doctrine ORM)              │
│      - EntityManager                            │
│      - Query Builder                            │
│      - Lazy loading / Eager loading             │
│      - Caching (APCu in production)             │
└────────────────────────────────────────────────┘
                          ↓
┌────────────────────────────────────────────────┐
│       Database Layer (PostgreSQL 16)            │
│      - Tables with 11 compound indexes          │
│      - Transactions with savepoints             │
│      - Connection pooling                       │
└────────────────────────────────────────────────┘
```

---

## 🎯 Request/Response Flow Example

### Создание задачи

```
1. HTTP REQUEST
   POST /api/tasks
   Authorization: Bearer eyJ0eXAi...
   Content-Type: application/json
   {
     "title": "Buy groceries",
     "description": "Milk, cheese, bread",
     "priority": "high",
     "dueDate": "2025-11-15T17:00:00Z",
     "tags": ["shopping", "personal"]
   }

2. ROUTING
   routes.yaml → TaskController::create()

3. SECURITY
   JWT Firewall: Validates token, loads User from email in token
   IsGranted('ROLE_USER'): User must be authenticated

4. DESERIALIZATION
   SymfonySerializer: JSON → CreateTaskDto
   Validator: Validates constraints (required fields, lengths, etc)

5. BUSINESS LOGIC
   TaskService::createTask(CreateTaskDto $dto, User $user)
   - Creates Task entity
   - Finds or creates Tags
   - Loads MediaObjects from database
   - Persists to EntityManager
   - Flushes transaction

6. SERIALIZATION
   Task → JSON using Serializer with Groups:
   {
     "id": 42,
     "title": "Buy groceries",
     "description": "...",
     "status": "pending",
     "priority": "high",
     "dueDate": "2025-11-15T17:00:00Z",
     "tags": [
       { "id": 1, "name": "shopping", "color": "#3B82F6" },
       { "id": 2, "name": "personal", "color": "#10B981" }
     ],
     "createdAt": "2025-11-14T12:30:00Z",
     "updatedAt": "2025-11-14T12:30:00Z"
   }

7. HTTP RESPONSE
   201 Created
   Location: /api/tasks/42
   Content-Type: application/json
   [JSON body above]
```

---

## 🗂️ Файловая Структура Сущностей

```
src/Entity/
├── AbstractEntity.php              # Base class с id, createdAt, updatedAt
├── User.php                        (325 строк, 7165 bytes)
├── Task.php                        (458 строк, 12147 bytes)
├── Tag.php                         (180 строк, 4221 bytes)
├── RecurrenceRule.php              (200+ строк)
├── MediaObject.php                 (Upload files with deduplication)
├── TaskAttachment.php              (File associations)
├── RefreshToken.php                (JWT refresh tokens)
└── AuditLog.php                    (Optional audit trail)
```

---

## 🔧 Миграции (Database)

```
migrations/
├── Version20251027000000.php        # Initial schema (users, tasks, tags)
├── Version20251027133432.php        # Task indices + relationships
├── Version20251102_AddMediaObjects.php     # File upload support
├── Version20251102_AddRecurrenceRules.php  # Recurring tasks
├── Version20251106_FixRecurrenceRulesSequence.php  # Sequence fix
├── Version20251108234939.php        # Latest schema updates
├── Version20251109163500.php
├── Version20251110091500.php
├── Version20251111150822.php        # Recent updates
└── Version20251102_AddTaskAttachments.php  # Task attachments
```

**Миграции запускаются автоматически:**
```bash
docker exec backend-php83 php bin/console doctrine:migrations:migrate
```

---

## 📈 Производительность

### Оптимизированные Запросы

**Без N+1 проблем благодаря Eager Loading:**

```php
// TaskRepository::findUserTasks()
$qb->leftJoin('t.tags', 'tag')
   ->leftJoin('t.user', 'u')
   ->leftJoin('t.recurrenceRule', 'rr')
   ->leftJoin('t.subtasks', 'st')
   ->addSelect('tag', 'u', 'rr', 'st')
   // ↑ Все данные загружаются в ONE query!
```

### Индексы БД

**11 compound indexes для быстрого поиска:**
- По user_id + status
- По user_id + priority
- По user_id + due_date
- По user_id + parent_task_id (для подзадач)
- И т.д.

### Кеширование (Production)

**APCu in-memory кеш для Doctrine:**
- Metadata Cache (1 час TTL): ~5-10x ускорение
- Query Cache (1 час TTL): Кеширование парсинга DQL
- Result Cache (5 мин TTL): Выборочное кеширование результатов

---

## ✅ Проверка Качества Кода

```bash
# PHP Code Style (PSR-12 + PHP 8.3)
make cs-fixer-fix

# Static Analysis (PHPStan level 5)
make phpstan

# Unit + Integration Tests
make test

# All checks
make quality-check
```

---

## 🎨 Технологический Стек Резюме

| Компонент | Версия | Назначение |
|-----------|--------|-----------|
| **Symfony** | 7.1.* | Фреймворк |
| **PHP** | 8.3+ | Runtime |
| **PostgreSQL** | 16 | СУБД |
| **Doctrine ORM** | 3.2 | ORM |
| **Doctrine Migrations** | 3.3 | Версионирование схемы |
| **Lexik JWT** | 3.1 | JWT аутентификация |
| **Gesdinet JWT Refresh** | 1.3 | Refresh token поддержка |
| **Nelmio CORS** | 2.5 | CORS заголовки |
| **Nelmio API Doc** | 4.29 | Swagger/OpenAPI |
| **EasyAdmin** | 4.18 | Admin панель |
| **Firebase JWT** | 6.11 | Google OAuth JWT декодирование |
| **League OAuth2 Google** | 4.0 | Google OAuth2 провайдер |
| **Symfony Messenger** | 7.1.* | Async messages (RabbitMQ) |
| **Symfony Validator** | 7.1.* | Input validation |
| **Symfony Serializer** | 7.1.* | JSON (de)serialization |

---

## 🚀 Ключевые Особенности Architecture

✅ **SOLID принципы:**
- Single Responsibility (контроллеры тонкие, логика в сервисах)
- Open/Closed (Strategy pattern для повторяющихся задач)
- Liskov Substitution (наследование Entity)
- Interface Segregation (DTOs для фронтенда)
- Dependency Inversion (Autowiring сервисов)

✅ **Security-First:**
- JWT + Google OAuth 2.0
- CORS явно сконфигурирован
- Fail-Fast для production credentials
- Security Voters для access control
- Password хеширование (bcrypt)

✅ **Performance:**
- Compound индексы на часто фильтруемые поля
- Eager loading для избежания N+1
- APCu кеширование в production
- Connection pooling
- Optimized DQL queries

✅ **Testability:**
- DTO layer изолирует сущности
- Services легко тестировать (минимум зависимостей)
- Repositories абстрагируют работу с БД
- PHPUnit + Zenstruck Foundry

✅ **Maintainability:**
- Type hints везде (PHP 8.3)
- Enums вместо констант (TaskStatus, TaskPriority)
- Миграции для версионирования схемы
- OpenAPI документация
- Слоистая архитектура

---

## 📞 Основные Эндпоинты

```
Authentication:
  POST /api/auth                 - Login (email/password)
  POST /api/auth/google          - Google OAuth
  POST /api/token/refresh        - Refresh JWT token

Tasks:
  GET    /api/tasks              - List (с фильтрацией)
  POST   /api/tasks              - Create
  GET    /api/tasks/{id}         - Get details
  PUT    /api/tasks/{id}         - Update
  PATCH  /api/tasks/{id}/status  - Change status
  DELETE /api/tasks/{id}         - Delete
  POST   /api/tasks/{id}/archive - Archive
  POST   /api/tasks/{id}/subtask - Create subtask

Tags:
  GET    /api/tags               - List
  POST   /api/tags               - Create
  PUT    /api/tags/{id}          - Update
  DELETE /api/tags/{id}          - Delete

Analytics:
  GET    /api/analytics/summary  - Overall stats
  GET    /api/analytics/daily    - Daily breakdown
  GET    /api/analytics/by-tag   - Tasks by tag

User Profile:
  GET    /api/profile            - Current user
  PATCH  /api/profile            - Update profile
  PATCH  /api/profile/password   - Change password
  PATCH  /api/profile/theme      - Theme settings
```

---

**Документ создан**: 2025-11-14  
**Для**: TaskFlow Project  
**Версия**: 1.0
