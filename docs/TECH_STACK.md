# 🛠 Tech Stack - Complete Technology Overview

> **TL;DR**: Modern full-stack application built with Symfony 7.1 (PHP 8.3), PostgreSQL, Redis, Vue.js 3.4, TypeScript 5.4, and containerized with Docker. Every technology choice is justified by performance, scalability, and developer experience.

---

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Backend Stack](#backend-stack)
- [Frontend Stack](#frontend-stack)
- [Infrastructure](#infrastructure)
- [Third-Party Services](#third-party-services)
- [Development Tools](#development-tools)
- [Technology Justifications](#technology-justifications)
- [Version Requirements](#version-requirements)

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                         CLIENT                              │
│              (Browser - Vue.js 3 SPA)                       │
└─────────────────────────┬───────────────────────────────────┘
                          │ HTTPS/REST
┌─────────────────────────▼───────────────────────────────────┐
│                      BACKEND API                            │
│           (Symfony 7.1 + PHP 8.3)                          │
└─────────┬─────────────────────────────────┬─────────────────┘
          │                                 │
┌─────────▼──────────┐          ┌─────────▼────────────┐
│   PostgreSQL 15    │          │    Redis 7.2         │
│  (Primary Data)    │          │  (Cache + Sessions)  │
└────────────────────┘          └──────────────────────┘
```

**Architecture Style:** Layered Monolith (Backend) + SPA (Frontend)

---

## Backend Stack

### Core Framework

#### **Symfony 7.1**
```json
"symfony/framework-bundle": "7.1.*"
```

**Why Symfony?**
- **Enterprise-grade**: Battle-tested framework used by millions
- **SOLID principles**: Built-in dependency injection, follows best practices
- **Rich ecosystem**: Extensive bundle library (JWT, CORS, Doctrine, etc.)
- **Performance**: Optimized for high-performance applications
- **Documentation**: Best-in-class documentation and community support
- **Type safety**: Works seamlessly with PHP 8.3 typed properties

**Key Features Used:**
- Controllers (thin HTTP layer)
- Dependency Injection Container
- Event Dispatcher (cache invalidation)
- Serializer (DTO transformation)
- Validator (request validation)
- Security component (JWT authentication)

---

### Programming Language

#### **PHP 8.3**
```json
"php": ">=8.3"
```

**Why PHP 8.3?**
- **Modern syntax**: Enums, attributes, readonly properties, typed properties
- **Performance**: JIT compiler, 30% faster than PHP 7.4
- **Type safety**: Strict types, union types, nullable types
- **Developer experience**: Much better than older PHP versions

**PHP 8.3 Features We Use:**
```php
// Enums (TaskStatus, TaskPriority)
enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
}

// Readonly properties
final readonly class TaskResponseDto
{
    public function __construct(
        public int $id,
        public string $title,
        public TaskStatus $status,
    ) {}
}

// Typed properties
private readonly LoggerInterface $logger;
```

---

### Database

#### **PostgreSQL 15**
```yaml
# docker-compose.yml
postgres:15-alpine
```

**Why PostgreSQL?**
- **ACID compliance**: Guaranteed data integrity
- **JSON support**: Native JSONB for flexible data (tags, metadata)
- **Performance**: Advanced query optimizer, efficient indexing
- **Reliability**: Industry-standard for mission-critical applications
- **Rich data types**: Arrays, JSONB, UUID, full-text search
- **Advanced features**: CTEs, window functions, materialized views

**Database Features Used:**
```sql
-- Recursive queries (task hierarchy)
WITH RECURSIVE subtasks AS (
    SELECT * FROM task WHERE id = :parent_id
    UNION ALL
    SELECT t.* FROM task t
    INNER JOIN subtasks s ON t.parent_id = s.id
)
SELECT * FROM subtasks;

-- JSONB columns (future-proof)
ALTER TABLE task ADD COLUMN metadata JSONB;

-- Advanced indexing
CREATE INDEX idx_task_user_status ON task (user_id, status);
CREATE INDEX idx_task_due_date ON task (due_date) WHERE due_date IS NOT NULL;
```

---

### ORM

#### **Doctrine ORM 3.2**
```json
"doctrine/orm": "^3.2",
"doctrine/doctrine-bundle": "^2.12"
```

**Why Doctrine?**
- **Abstraction**: Database-agnostic (can switch from PostgreSQL to MySQL)
- **Type safety**: Strong PHP type mapping
- **Migrations**: Version-controlled database schema
- **Lazy loading**: Efficient relationship loading
- **Repository pattern**: Built-in data access abstraction
- **DQL**: Object-oriented query language

**Doctrine Features Used:**
```php
// Entities with relationships
#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Task::class)]
    private ?Task $parent = null;

    #[ORM\ManyToMany(targetEntity: Tag::class)]
    private Collection $tags;
}

// Custom repositories
class TaskRepository extends ServiceEntityRepository
{
    public function findByUserAndStatus(User $user, TaskStatus $status): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }
}
```

---

### Cache Layer

#### **Redis 7.2**
```yaml
# docker-compose.yml
redis:7.2-alpine
```

**Why Redis?**
- **Speed**: In-memory storage, sub-millisecond response times
- **Data structures**: Strings, hashes, lists, sets (flexible caching)
- **TTL support**: Automatic key expiration
- **Persistence**: Optional RDB/AOF for data durability
- **Simplicity**: Easy to use, reliable
- **Scalability**: Can be clustered for horizontal scaling

**Redis Usage Pattern:**
```php
// SimpleRedisCache service (native Redis client)
final class SimpleRedisCache
{
    private \Redis $redis;

    public function get(string $key): mixed
    {
        $data = $this->redis->get($this->prefix . $key);
        return $data ? json_decode($data, true) : null;
    }

    public function set(string $key, mixed $value, int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        return $this->redis->setex(
            $this->prefix . $key,
            $ttl,
            json_encode($value)
        );
    }
}
```

**Cache Keys Pattern:**
```
app:prod:user_tasks_list:uid_5           → User 5's task list
app:prod:analytics_overview:uid_5        → User 5's analytics overview
app:prod:analytics_completion:uid_5      → User 5's completion timeline
```

---

### Authentication & Security

#### **JWT (JSON Web Tokens)**
```json
"lexik/jwt-authentication-bundle": "^3.1",
"firebase/php-jwt": "^6.11",
"gesdinet/jwt-refresh-token-bundle": "^1.3"
```

**Why JWT?**
- **Stateless**: No server-side session storage needed
- **Scalable**: Works across multiple servers (no sticky sessions)
- **Standard**: Industry-standard RFC 7519
- **Flexible**: Can store user claims in token payload
- **Secure**: RS256 algorithm (asymmetric encryption)

**JWT Implementation:**
```yaml
# config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 1800 # 30 minutes
```

**Token Structure:**
```json
// Access Token (30 min)
{
  "iat": 1641024000,
  "exp": 1641025800,
  "roles": ["ROLE_USER"],
  "username": "user@example.com"
}

// Refresh Token (7 days)
{
  "token": "abc123...",
  "valid": "2025-01-12T00:00:00+00:00"
}
```

---

#### **Google OAuth2**
```json
"knpuniversity/oauth2-client-bundle": "^2.18",
"league/oauth2-google": "^4.0"
```

**Why Google OAuth?**
- **User convenience**: No password to remember
- **Security**: Leverages Google's security infrastructure
- **Trust**: Users trust Google authentication
- **Fast onboarding**: One-click sign-in

---

### API Documentation

#### **Nelmio API Doc Bundle**
```json
"nelmio/api-doc-bundle": "^4.29"
```

**Why Nelmio?**
- **OpenAPI 3.0**: Industry-standard API documentation format
- **Swagger UI**: Interactive API documentation
- **Auto-generation**: Extracts docs from PHP attributes
- **Developer-friendly**: Easy to test endpoints

---

### CORS Handling

#### **Nelmio CORS Bundle**
```json
"nelmio/cors-bundle": "^2.5"
```

**Why Nelmio CORS?**
- **Flexible**: Fine-grained CORS configuration
- **Secure**: Prevents unauthorized cross-origin requests
- **Easy**: Simple YAML configuration

```yaml
# config/packages/nelmio_cors.yaml
nelmio_cors:
    paths:
        '^/api':
            origin_regex: true
            allow_origin: ['http://localhost:5173']
            allow_methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
            allow_headers: ['Content-Type', 'Authorization']
            max_age: 3600
```

---

### Date/Time Handling

#### **Carbon**
```json
"nesbot/carbon": "^3.9"
```

**Why Carbon?**
- **Developer-friendly**: Fluent API for date manipulation
- **Timezone aware**: Handles timezones correctly
- **Localization**: Supports multiple languages
- **Testing**: Easy to mock time in tests

```php
// Carbon usage examples
$task->setDueDate(Carbon::parse($dto->dueDate));
$isOverdue = Carbon::now()->greaterThan($task->getDueDate());
$completed = $task->getCompletedAt()->diffForHumans(); // "2 hours ago"
```

---

## Frontend Stack

### Core Framework

#### **Vue.js 3.4.21**
```json
"vue": "^3.4.21"
```

**Why Vue.js 3?**
- **Composition API**: Better code organization, type safety
- **Performance**: Virtual DOM, efficient reactivity
- **TypeScript support**: First-class TypeScript integration
- **Small bundle size**: ~30KB minified + gzipped
- **Developer experience**: Excellent devtools, hot reload
- **Progressive**: Can be adopted incrementally

**Vue 3 Features Used:**
```typescript
// Composition API with TypeScript
const { tasks, loading } = defineProps<{
  tasks: Task[]
  loading: boolean
}>()

// Reactive state
const selectedTask = ref<Task | null>(null)
const filter = reactive<TaskFilter>({
  status: null,
  priority: null
})

// Computed properties
const completedTasks = computed(() =>
  tasks.value.filter(t => t.status === TaskStatus.COMPLETED)
)

// Lifecycle hooks
onMounted(async () => {
  await taskStore.fetchTasks()
})
```

---

### Programming Language

#### **TypeScript 5.4.0**
```json
"typescript": "^5.4.0"
```

**Why TypeScript?**
- **Type safety**: Catch errors at compile-time, not runtime
- **IntelliSense**: Better autocomplete in IDEs
- **Refactoring**: Safe refactoring with confidence
- **Documentation**: Types serve as inline documentation
- **Scalability**: Essential for large codebases

**TypeScript Strict Mode:**
```json
// tsconfig.json
{
  "compilerOptions": {
    "strict": true,
    "noImplicitAny": true,
    "strictNullChecks": true,
    "strictFunctionTypes": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true
  }
}
```

**Type Safety Example:**
```typescript
// NO 'any' types allowed!

// ❌ BAD
const task: any = await fetchTask() // Loses all type safety

// ✅ GOOD
interface Task {
  id: number
  title: string
  status: TaskStatus
  dueDate: string | null
}

const task: Task = await taskService.getTask(id)
task.title // TypeScript knows this is a string
```

---

### State Management

#### **Pinia 2.1.7**
```json
"pinia": "^2.1.7"
```

**Why Pinia?**
- **TypeScript-first**: Better type inference than Vuex
- **Composition API**: Uses same API as Vue 3
- **Devtools**: Excellent Vue Devtools integration
- **Lightweight**: Only ~1KB minified + gzipped
- **Modular**: Easy to split stores by domain

**Pinia Store Example:**
```typescript
export const useTaskStore = defineStore('task', () => {
  // State
  const tasks = ref<Task[]>([])
  const loading = ref(false)

  // Getters
  const completedTasks = computed(() =>
    tasks.value.filter(t => t.status === TaskStatus.COMPLETED)
  )

  // Actions
  async function fetchTasks(): Promise<void> {
    loading.value = true
    try {
      tasks.value = await taskService.getTasks()
    } finally {
      loading.value = false
    }
  }

  return { tasks, loading, completedTasks, fetchTasks }
})
```

---

### UI Component Library

#### **PrimeVue 3.50.0**
```json
"primevue": "^3.50.0",
"primeicons": "^7.0.0"
```

**Why PrimeVue?**
- **Rich components**: 80+ ready-to-use components
- **Customizable**: Theming system, CSS variables
- **Accessible**: WCAG 2.0 compliant
- **Responsive**: Mobile-first design
- **Active development**: Regular updates, bug fixes
- **Documentation**: Excellent docs with examples

**PrimeVue Components Used:**
- **DataTable**: Task lists with sorting, filtering
- **Calendar**: Date picker for task dates
- **Dialog**: Modals for create/edit task
- **Toast**: Notifications
- **Dropdown**: Select inputs
- **Chip**: Tags display
- **ProgressBar**: Loading states
- **Chart**: Analytics visualizations

---

### Routing

#### **Vue Router 4.3.0**
```json
"vue-router": "^4.3.0"
```

**Why Vue Router?**
- **Official**: Maintained by Vue.js core team
- **Type-safe**: Full TypeScript support
- **Code splitting**: Lazy-load routes
- **Navigation guards**: Authentication, authorization
- **History mode**: Clean URLs without hash

**Router Configuration:**
```typescript
const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'), // Lazy-loaded
      meta: { requiresAuth: true }
    },
    {
      path: '/analytics',
      name: 'analytics',
      component: () => import('@/views/AnalyticsView.vue'),
      meta: { requiresAuth: true }
    }
  ]
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  } else {
    next()
  }
})
```

---

### HTTP Client

#### **Axios 1.6.7**
```json
"axios": "^1.6.7"
```

**Why Axios?**
- **Interceptors**: Request/response transformation
- **Automatic transforms**: JSON parsing
- **Browser support**: Works in all modern browsers
- **Error handling**: Consistent error structure
- **TypeScript support**: Type definitions included

---

### Build Tool

#### **Vite 5.1.5**
```json
"vite": "^5.1.5"
```

**Why Vite?**
- **Lightning fast**: ES modules, no bundling in dev
- **HMR**: Hot Module Replacement (instant updates)
- **Build speed**: 10-100x faster than Webpack
- **Plugin ecosystem**: Rich plugin support
- **Modern**: Built for modern browsers (ES2015+)

**Vite Configuration:**
```typescript
// vite.config.ts
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  },
  build: {
    target: 'esnext',
    rollupOptions: {
      output: {
        manualChunks: {
          'vendor': ['vue', 'vue-router', 'pinia'],
          'ui': ['primevue']
        }
      }
    }
  }
})
```

---

### Internationalization

#### **Vue I18n 9.14.5**
```json
"vue-i18n": "^9.14.5"
```

**Why Vue I18n?**
- **Official**: Vue.js ecosystem project
- **Type-safe**: TypeScript support for translations
- **Pluralization**: Built-in plural rules
- **Number/Date formatting**: Locale-aware formatting
- **Lazy loading**: Load translations on-demand

**I18n Example:**
```typescript
// locales/en.ts
export default {
  task: {
    create: 'Create Task',
    edit: 'Edit Task',
    delete: 'Delete Task',
    status: {
      pending: 'Pending',
      in_progress: 'In Progress',
      completed: 'Completed'
    }
  }
}

// Component usage
<template>
  <h1>{{ $t('task.create') }}</h1>
  <p>{{ $t('task.status.pending') }}</p>
</template>
```

---

### Charts & Visualizations

#### **ECharts 6.0.0**
```json
"echarts": "^6.0.0",
"vue-echarts": "^8.0.1"
```

**Why ECharts?**
- **Powerful**: Supports 20+ chart types
- **Performance**: Canvas rendering, handles large datasets
- **Customizable**: Full control over styling
- **Responsive**: Auto-resize, mobile-friendly
- **Interactive**: Zoom, pan, tooltips

---

### Utilities

#### **VueUse 10.9.0**
```json
"@vueuse/core": "^10.9.0"
```

**Why VueUse?**
- **Composables**: 200+ utility composables
- **Tree-shakable**: Only import what you use
- **Type-safe**: Full TypeScript support
- **Well-tested**: High code coverage

**VueUse Examples:**
```typescript
import { useLocalStorage, useDebounceFn, useIntersectionObserver } from '@vueuse/core'

// Persistent state
const theme = useLocalStorage('theme', 'light')

// Debounced search
const debouncedSearch = useDebounceFn(search, 300)

// Infinite scroll
useIntersectionObserver(target, ([{ isIntersecting }]) => {
  if (isIntersecting) loadMore()
})
```

---

## Infrastructure

### Containerization

#### **Docker & Docker Compose**
```yaml
# docker-compose.yml
version: '3.8'

services:
  postgres:
    image: postgres:15-alpine
    environment:
      POSTGRES_DB: task_manager
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: postgres

  redis:
    image: redis:7.2-alpine
    command: redis-server --appendonly yes

  php:
    image: php:8.3-fpm-alpine
    volumes:
      - ./backend:/var/www/html
```

**Why Docker?**
- **Consistency**: Same environment across dev, staging, prod
- **Isolation**: Each service in its own container
- **Portability**: Runs anywhere Docker runs
- **Easy setup**: `docker-compose up` and you're running
- **Version control**: Infrastructure as code

---

### Web Server

#### **Nginx**
```nginx
# nginx.conf
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass php:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

**Why Nginx?**
- **Performance**: Handles 10,000+ concurrent connections
- **Lightweight**: Low memory footprint
- **Reverse proxy**: Forwards requests to PHP-FPM
- **Static files**: Serves assets efficiently

---

## Third-Party Services

### Google OAuth2
- **Purpose**: User authentication
- **Service**: Google Identity Platform
- **Implementation**: One Tap sign-in

### Google Fonts
- **Purpose**: Typography
- **Fonts**: Inter, Roboto

---

## Development Tools

### Backend Development

#### **Composer**
- **Version**: 2.x
- **Purpose**: PHP dependency management

#### **Symfony CLI**
```bash
symfony server:start
symfony console make:entity
symfony console doctrine:migrations:migrate
```

#### **PHPStan**
```json
"phpstan/phpstan": "^2.1"
```
- **Purpose**: Static analysis, type checking
- **Level**: 8 (maximum strictness)

#### **PHP CS Fixer**
```json
"friendsofphp/php-cs-fixer": "^3.66"
```
- **Purpose**: Code style enforcement
- **Standard**: PSR-12

#### **PHPUnit**
```json
"phpunit/phpunit": "^9.5"
```
- **Purpose**: Unit & integration testing
- **Coverage**: Target 80%+

---

### Frontend Development

#### **npm/pnpm**
- **Version**: npm 9.x / pnpm 8.x
- **Purpose**: JavaScript dependency management

#### **ESLint**
- **Purpose**: JavaScript/TypeScript linting
- **Config**: Vue.js + TypeScript

#### **Vitest**
```json
"vitest": "^4.0.3"
```
- **Purpose**: Unit testing
- **Why Vitest?**: Fast, Vite-native, Jest-compatible API

---

### Database Tools

#### **DBeaver / pgAdmin**
- **Purpose**: Database management
- **Connection**: PostgreSQL 15

#### **RedisInsight / redis-cli**
- **Purpose**: Redis monitoring, debugging
```bash
docker exec -it redis redis-cli
> KEYS app:prod:*
> GET app:prod:user_tasks_list:uid_5
```

---

### API Testing

#### **Postman / Insomnia**
- **Purpose**: API endpoint testing
- **Collections**: All endpoints documented

#### **Swagger UI**
- **URL**: `http://localhost:8000/api/doc`
- **Purpose**: Interactive API documentation

---

## Technology Justifications

### Why This Stack?

#### **Backend: Symfony + PHP**
✅ **Enterprise-grade**: Used by enterprise companies (Spotify, Trivago)
✅ **Type safety**: PHP 8.3 + strict types = fewer bugs
✅ **Performance**: PHP 8.3 JIT + Redis = sub-millisecond responses
✅ **Ecosystem**: Mature bundles for everything (JWT, OAuth, CORS)
✅ **Documentation**: Best-in-class docs + huge community

#### **Frontend: Vue.js + TypeScript**
✅ **Developer experience**: Composition API + TypeScript = joy
✅ **Performance**: Virtual DOM + reactivity = fast UI
✅ **Type safety**: Catch errors before users see them
✅ **Component library**: PrimeVue = don't reinvent the wheel
✅ **Tooling**: Vite = instant HMR, fast builds

#### **Database: PostgreSQL**
✅ **Reliability**: ACID compliance, battle-tested
✅ **Features**: JSON, recursion, advanced indexing
✅ **Performance**: Query optimizer, efficient joins
✅ **Scalability**: Can handle millions of rows

#### **Cache: Redis**
✅ **Speed**: Sub-millisecond reads (0.19ms - 0.54ms measured)
✅ **Simple**: Set/Get operations, TTL support
✅ **Reliable**: Mature, well-tested, widely used
✅ **Flexible**: Supports multiple data structures

---

## Version Requirements

### Backend

```json
{
  "php": ">=8.3",
  "symfony/framework-bundle": "7.1.*",
  "doctrine/orm": "^3.2",
  "lexik/jwt-authentication-bundle": "^3.1",
  "nelmio/cors-bundle": "^2.5",
  "nesbot/carbon": "^3.9"
}
```

### Frontend

```json
{
  "vue": "^3.4.21",
  "typescript": "^5.4.0",
  "pinia": "^2.1.7",
  "vue-router": "^4.3.0",
  "primevue": "^3.50.0",
  "axios": "^1.6.7",
  "vite": "^5.1.5"
}
```

### Infrastructure

```yaml
postgres: "15-alpine"
redis: "7.2-alpine"
php: "8.3-fpm-alpine"
nginx: "1.25-alpine"
node: "20-alpine"
```

---

## Dependency Matrix

| Component | Technology | Version | Purpose | Alternatives Considered |
|-----------|-----------|---------|---------|------------------------|
| **Backend Framework** | Symfony | 7.1 | API, routing, DI | Laravel (too heavy), API Platform (overkill) |
| **Language** | PHP | 8.3 | Business logic | PHP 8.2 (missing features) |
| **Database** | PostgreSQL | 15 | Data persistence | MySQL (less features), MongoDB (not relational) |
| **Cache** | Redis | 7.2 | Performance | Memcached (less features), APCu (not distributed) |
| **ORM** | Doctrine | 3.2 | Data access | Eloquent (Laravel-only), Raw SQL (too manual) |
| **Auth** | JWT + OAuth2 | - | Authentication | Session-based (not scalable), Auth0 (expensive) |
| **Frontend Framework** | Vue.js | 3.4 | UI | React (more complex), Angular (too heavy) |
| **Language** | TypeScript | 5.4 | Type safety | JavaScript (no types), Flow (deprecated) |
| **State Management** | Pinia | 2.1 | Global state | Vuex (outdated), Zustand (React-only) |
| **UI Library** | PrimeVue | 3.50 | Components | Vuetify (Material Design only), Quasar (too opinionated) |
| **Build Tool** | Vite | 5.1 | Dev server, bundling | Webpack (slower), Parcel (less mature) |
| **HTTP Client** | Axios | 1.6 | API calls | Fetch API (less features), ky (less popular) |
| **Testing (BE)** | PHPUnit | 9.5 | Unit tests | Pest (too new), Codeception (too heavy) |
| **Testing (FE)** | Vitest | 4.0 | Unit tests | Jest (slower), Mocha (manual setup) |
| **Charts** | ECharts | 6.0 | Visualizations | Chart.js (less features), D3.js (too low-level) |

---

## Performance Benchmarks

### Backend (with Redis cache)

```
GET /api/tasks                    →  0.5ms (vs 100ms without cache)
GET /api/analytics/overview       →  0.24ms (vs 35ms)
GET /api/analytics/dashboard      →  0.19ms (vs 134ms)
GET /api/analytics/completion     →  0.54ms (vs 45ms)

Cache Hit Rate: ~95%
Cache Miss Penalty: +50-100ms
```

### Frontend

```
Initial Load:        1.2s (Time to Interactive)
Route Navigation:    50ms
Component Render:    30ms (100 tasks)
Bundle Size:         ~300KB (gzipped)
```

---

## Related Documents

### Must Read Next
- **[Architecture](backend/ARCHITECTURE.md)** - How these technologies work together
- **[Development Workflow](guides/DEVELOPMENT_WORKFLOW.md)** - Setting up the stack

### For Reference
- **[Database Schema](backend/DATABASE.md)** - PostgreSQL design
- **[Cache System](backend/CACHE_SYSTEM.md)** - Redis implementation
- **[API Integration](frontend/API_INTEGRATION.md)** - Axios configuration

---

*Last updated: 2025-01-05*
*Tech stack version: 1.0*
