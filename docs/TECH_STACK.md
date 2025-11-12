# 🛠 Технологический стек - Полный обзор технологий

> **TL;DR**: Современное full-stack приложение, построенное на Symfony 7.1 (PHP 8.3), PostgreSQL, Vue.js 3.4, TypeScript 5.4 и контейнеризированное с помощью Docker. Каждый выбор технологии обоснован производительностью, масштабируемостью и удобством разработки.

---

## Содержание

- [Обзор архитектуры](#обзор-архитектуры)
- [Backend стек](#backend-стек)
- [Frontend стек](#frontend-стек)
- [Инфраструктура](#инфраструктура)
- [Сторонние сервисы](#сторонние-сервисы)
- [Инструменты разработки](#инструменты-разработки)
- [Обоснование выбора технологий](#обоснование-выбора-технологий)
- [Требования к версиям](#требования-к-версиям)

---

## Обзор архитектуры

```
┌─────────────────────────────────────────────────────────────┐
│                         КЛИЕНТ                              │
│              (Браузер - Vue.js 3 SPA)                       │
└─────────────────────────┬───────────────────────────────────┘
                          │ HTTPS/REST
┌─────────────────────────▼───────────────────────────────────┐
│                      BACKEND API                            │
│           (Symfony 7.1 + PHP 8.3)                          │
└─────────┬───────────────────────────────────────────────────┘
          │
┌─────────▼──────────┐
│   PostgreSQL 15    │
│  (Основные данные) │
└────────────────────┘
```

**Стиль архитектуры:** Монолит со слоями (Backend) + SPA (Frontend)

---

## Backend стек

### Основной фреймворк

#### **Symfony 7.1**
```json
"symfony/framework-bundle": "7.1.*"
```

**Почему Symfony?**
- **Корпоративный уровень**: Проверенный в боях фреймворк, используемый миллионами
- **SOLID принципы**: Встроенная инъекция зависимостей, следует лучшим практикам
- **Богатая экосистема**: Обширная библиотека бандлов (JWT, CORS, Doctrine и т.д.)
- **Производительность**: Оптимизирован для высокопроизводительных приложений
- **Документация**: Лучшая документация и поддержка сообщества в своем классе
- **Типобезопасность**: Отлично работает с типизированными свойствами PHP 8.3

**Используемые ключевые возможности:**
- Контроллеры (тонкий HTTP слой)
- Контейнер инъекции зависимостей
- Диспетчер событий (инвалидация кеша)
- Сериализатор (трансформация DTO)
- Валидатор (валидация запросов)
- Компонент безопасности (JWT аутентификация)

---

### Язык программирования

#### **PHP 8.3**
```json
"php": ">=8.3"
```

**Почему PHP 8.3?**
- **Современный синтаксис**: Перечисления, атрибуты, readonly свойства, типизированные свойства
- **Производительность**: JIT компилятор, на 30% быстрее чем PHP 7.4
- **Типобезопасность**: Строгие типы, union типы, nullable типы
- **Опыт разработки**: Намного лучше, чем старые версии PHP

**Возможности PHP 8.3, которые мы используем:**
```php
// Перечисления (TaskStatus, TaskPriority)
enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
}

// Readonly свойства
final readonly class TaskResponseDto
{
    public function __construct(
        public int $id,
        public string $title,
        public TaskStatus $status,
    ) {}
}

// Типизированные свойства
private readonly LoggerInterface $logger;
```

---

### База данных

#### **PostgreSQL 15**
```yaml
# docker-compose.yml
postgres:15-alpine
```

**Почему PostgreSQL?**
- **ACID совместимость**: Гарантированная целостность данных
- **Поддержка JSON**: Нативный JSONB для гибких данных (теги, метаданные)
- **Производительность**: Продвинутый оптимизатор запросов, эффективная индексация
- **Надежность**: Промышленный стандарт для критически важных приложений
- **Богатые типы данных**: Массивы, JSONB, UUID, полнотекстовый поиск
- **Продвинутые возможности**: CTE, оконные функции, материализованные представления

**Используемые возможности базы данных:**
```sql
-- Рекурсивные запросы (иерархия задач)
WITH RECURSIVE subtasks AS (
    SELECT * FROM task WHERE id = :parent_id
    UNION ALL
    SELECT t.* FROM task t
    INNER JOIN subtasks s ON t.parent_id = s.id
)
SELECT * FROM subtasks;

-- JSONB колонки (задел на будущее)
ALTER TABLE task ADD COLUMN metadata JSONB;

-- Продвинутая индексация
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

**Почему Doctrine?**
- **Абстракция**: Не зависит от базы данных (можно переключиться с PostgreSQL на MySQL)
- **Типобезопасность**: Строгая типизация PHP
- **Миграции**: Схема базы данных под контролем версий
- **Ленивая загрузка**: Эффективная загрузка связей
- **Паттерн репозиторий**: Встроенная абстракция доступа к данным
- **DQL**: Объектно-ориентированный язык запросов

**Используемые возможности Doctrine:**
```php
// Сущности со связями
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

// Пользовательские репозитории
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

### Аутентификация и безопасность

#### **JWT (JSON Web Tokens)**
```json
"lexik/jwt-authentication-bundle": "^3.1",
"firebase/php-jwt": "^6.11",
"gesdinet/jwt-refresh-token-bundle": "^1.3"
```

**Почему JWT?**
- **Без состояния**: Не требуется серверное хранилище сессий
- **Масштабируемость**: Работает на нескольких серверах (без sticky sessions)
- **Стандарт**: Промышленный стандарт RFC 7519
- **Гибкость**: Можно хранить утверждения пользователя в payload токена
- **Безопасность**: Алгоритм RS256 (асимметричное шифрование)

**Реализация JWT:**
```yaml
# config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 1800 # 30 минут
```

**Структура токена:**
```json
// Access Token (30 мин)
{
  "iat": 1641024000,
  "exp": 1641025800,
  "roles": ["ROLE_USER"],
  "username": "user@example.com"
}

// Refresh Token (7 дней)
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

**Почему Google OAuth?**
- **Удобство для пользователя**: Не нужно запоминать пароль
- **Безопасность**: Использует инфраструктуру безопасности Google
- **Доверие**: Пользователи доверяют аутентификации Google
- **Быстрая регистрация**: Вход в один клик

---

### Документация API

#### **Nelmio API Doc Bundle**
```json
"nelmio/api-doc-bundle": "^4.29"
```

**Почему Nelmio?**
- **OpenAPI 3.0**: Промышленный стандарт формата документации API
- **Swagger UI**: Интерактивная документация API
- **Автогенерация**: Извлекает документацию из PHP атрибутов
- **Удобство для разработчиков**: Легко тестировать эндпоинты

---

### Обработка CORS

#### **Nelmio CORS Bundle**
```json
"nelmio/cors-bundle": "^2.5"
```

**Почему Nelmio CORS?**
- **Гибкость**: Детальная настройка CORS
- **Безопасность**: Предотвращает несанкционированные межсайтовые запросы
- **Простота**: Простая конфигурация YAML

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

### Работа с датой/временем

#### **Carbon**
```json
"nesbot/carbon": "^3.9"
```

**Почему Carbon?**
- **Удобство для разработчиков**: Fluent API для манипуляций с датами
- **Учет часовых поясов**: Корректно обрабатывает часовые пояса
- **Локализация**: Поддержка нескольких языков
- **Тестирование**: Легко мокировать время в тестах

```php
// Примеры использования Carbon
$task->setDueDate(Carbon::parse($dto->dueDate));
$isOverdue = Carbon::now()->greaterThan($task->getDueDate());
$completed = $task->getCompletedAt()->diffForHumans(); // "2 часа назад"
```

---

## Frontend стек

### Основной фреймворк

#### **Vue.js 3.4.21**
```json
"vue": "^3.4.21"
```

**Почему Vue.js 3?**
- **Composition API**: Лучшая организация кода, типобезопасность
- **Производительность**: Виртуальный DOM, эффективная реактивность
- **Поддержка TypeScript**: Первоклассная интеграция с TypeScript
- **Небольшой размер бандла**: ~30KB минифицированный + gzip
- **Опыт разработки**: Отличные devtools, горячая перезагрузка
- **Прогрессивный**: Может быть внедрен постепенно

**Используемые возможности Vue 3:**
```typescript
// Composition API с TypeScript
const { tasks, loading } = defineProps<{
  tasks: Task[]
  loading: boolean
}>()

// Реактивное состояние
const selectedTask = ref<Task | null>(null)
const filter = reactive<TaskFilter>({
  status: null,
  priority: null
})

// Вычисляемые свойства
const completedTasks = computed(() =>
  tasks.value.filter(t => t.status === TaskStatus.COMPLETED)
)

// Хуки жизненного цикла
onMounted(async () => {
  await taskStore.fetchTasks()
})
```

---

### Язык программирования

#### **TypeScript 5.4.0**
```json
"typescript": "^5.4.0"
```

**Почему TypeScript?**
- **Типобезопасность**: Ловим ошибки на этапе компиляции, а не во время выполнения
- **IntelliSense**: Лучшее автодополнение в IDE
- **Рефакторинг**: Безопасный рефакторинг с уверенностью
- **Документация**: Типы служат встроенной документацией
- **Масштабируемость**: Необходим для больших кодовых баз

**Строгий режим TypeScript:**
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

**Пример типобезопасности:**
```typescript
// НИКАКИХ типов 'any' не разрешено!

// ❌ ПЛОХО
const task: any = await fetchTask() // Теряет всю типобезопасность

// ✅ ХОРОШО
interface Task {
  id: number
  title: string
  status: TaskStatus
  dueDate: string | null
}

const task: Task = await taskService.getTask(id)
task.title // TypeScript знает, что это строка
```

---

### Управление состоянием

#### **Pinia 2.1.7**
```json
"pinia": "^2.1.7"
```

**Почему Pinia?**
- **TypeScript-first**: Лучший вывод типов чем у Vuex
- **Composition API**: Использует тот же API что и Vue 3
- **Devtools**: Отличная интеграция с Vue Devtools
- **Легковесность**: Всего ~1KB минифицированный + gzip
- **Модульность**: Легко разделить сторы по доменам

**Пример стора Pinia:**
```typescript
export const useTaskStore = defineStore('task', () => {
  // Состояние
  const tasks = ref<Task[]>([])
  const loading = ref(false)

  // Геттеры
  const completedTasks = computed(() =>
    tasks.value.filter(t => t.status === TaskStatus.COMPLETED)
  )

  // Действия
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

### Библиотека UI компонентов

#### **PrimeVue 3.50.0**
```json
"primevue": "^3.50.0",
"primeicons": "^7.0.0"
```

**Почему PrimeVue?**
- **Богатые компоненты**: 80+ готовых к использованию компонентов
- **Настраиваемость**: Система тем, CSS переменные
- **Доступность**: Соответствует WCAG 2.0
- **Адаптивность**: Mobile-first дизайн
- **Активная разработка**: Регулярные обновления, исправления багов
- **Документация**: Отличная документация с примерами

**Используемые компоненты PrimeVue:**
- **DataTable**: Списки задач с сортировкой, фильтрацией
- **Calendar**: Выбор даты для задач
- **Dialog**: Модальные окна для создания/редактирования задач
- **Toast**: Уведомления
- **Dropdown**: Выпадающие списки
- **Chip**: Отображение тегов
- **ProgressBar**: Состояния загрузки
- **Chart**: Визуализации аналитики

---

### Маршрутизация

#### **Vue Router 4.3.0**
```json
"vue-router": "^4.3.0"
```

**Почему Vue Router?**
- **Официальный**: Поддерживается командой Vue.js
- **Типобезопасность**: Полная поддержка TypeScript
- **Разделение кода**: Ленивая загрузка маршрутов
- **Навигационные стражи**: Аутентификация, авторизация
- **Режим истории**: Чистые URL без хэша

**Конфигурация роутера:**
```typescript
const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'), // Ленивая загрузка
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

// Навигационный страж
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

### HTTP клиент

#### **Axios 1.6.7**
```json
"axios": "^1.6.7"
```

**Почему Axios?**
- **Перехватчики**: Трансформация запросов/ответов
- **Автоматические трансформации**: Парсинг JSON
- **Поддержка браузеров**: Работает во всех современных браузерах
- **Обработка ошибок**: Последовательная структура ошибок
- **Поддержка TypeScript**: Включены определения типов

---

### Инструмент сборки

#### **Vite 5.1.5**
```json
"vite": "^5.1.5"
```

**Почему Vite?**
- **Молниеносная скорость**: ES модули, без бандлинга в dev режиме
- **HMR**: Горячая замена модулей (мгновенные обновления)
- **Скорость сборки**: В 10-100 раз быстрее чем Webpack
- **Экосистема плагинов**: Богатая поддержка плагинов
- **Современный**: Создан для современных браузеров (ES2015+)

**Конфигурация Vite:**
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

### Интернационализация

#### **Vue I18n 9.14.5**
```json
"vue-i18n": "^9.14.5"
```

**Почему Vue I18n?**
- **Официальный**: Проект экосистемы Vue.js
- **Типобезопасность**: Поддержка TypeScript для переводов
- **Плюрализация**: Встроенные правила множественного числа
- **Форматирование чисел/дат**: Форматирование с учетом локали
- **Ленивая загрузка**: Загрузка переводов по требованию

**Пример I18n:**
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

// Использование в компоненте
<template>
  <h1>{{ $t('task.create') }}</h1>
  <p>{{ $t('task.status.pending') }}</p>
</template>
```

---

### Графики и визуализации

#### **ECharts 6.0.0**
```json
"echarts": "^6.0.0",
"vue-echarts": "^8.0.1"
```

**Почему ECharts?**
- **Мощность**: Поддержка 20+ типов графиков
- **Производительность**: Рендеринг на Canvas, обрабатывает большие наборы данных
- **Настраиваемость**: Полный контроль над стилями
- **Адаптивность**: Автоизменение размера, mobile-friendly
- **Интерактивность**: Зум, панорамирование, подсказки

---

### Утилиты

#### **VueUse 10.9.0**
```json
"@vueuse/core": "^10.9.0"
```

**Почему VueUse?**
- **Composables**: 200+ утилитных композаблов
- **Tree-shakable**: Импортируете только то, что используете
- **Типобезопасность**: Полная поддержка TypeScript
- **Хорошо протестировано**: Высокое покрытие кода

**Примеры VueUse:**
```typescript
import { useLocalStorage, useDebounceFn, useIntersectionObserver } from '@vueuse/core'

// Персистентное состояние
const theme = useLocalStorage('theme', 'light')

// Debounced поиск
const debouncedSearch = useDebounceFn(search, 300)

// Бесконечная прокрутка
useIntersectionObserver(target, ([{ isIntersecting }]) => {
  if (isIntersecting) loadMore()
})
```

---

## Инфраструктура

### Контейнеризация

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

  php:
    image: php:8.3-fpm-alpine
    volumes:
      - ./backend:/var/www/html
```

**Почему Docker?**
- **Согласованность**: Одинаковое окружение для dev, staging, prod
- **Изоляция**: Каждый сервис в своем контейнере
- **Переносимость**: Работает везде, где работает Docker
- **Простая настройка**: `docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml up -d` (dev режим)
- **Контроль версий**: Инфраструктура как код

---

### Веб-сервер

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

**Почему Nginx?**
- **Производительность**: Обрабатывает 10,000+ одновременных соединений
- **Легковесность**: Низкое потребление памяти
- **Reverse proxy**: Перенаправляет запросы к PHP-FPM
- **Статические файлы**: Эффективно обслуживает ассеты

---

## Сторонние сервисы

### Google OAuth2
- **Назначение**: Аутентификация пользователей
- **Сервис**: Google Identity Platform
- **Реализация**: One Tap sign-in

### Google Fonts
- **Назначение**: Типографика
- **Шрифты**: Inter, Roboto

---

## Инструменты разработки

### Backend разработка

#### **Composer**
- **Версия**: 2.x
- **Назначение**: Управление зависимостями PHP

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
- **Назначение**: Статический анализ, проверка типов
- **Уровень**: 8 (максимальная строгость)

#### **PHP CS Fixer**
```json
"friendsofphp/php-cs-fixer": "^3.66"
```
- **Назначение**: Принудительное соблюдение стиля кода
- **Стандарт**: PSR-12

#### **PHPUnit**
```json
"phpunit/phpunit": "^9.5"
```
- **Назначение**: Unit и интеграционное тестирование
- **Покрытие**: Цель 80%+

---

### Frontend разработка

#### **npm/pnpm**
- **Версия**: npm 9.x / pnpm 8.x
- **Назначение**: Управление зависимостями JavaScript

#### **ESLint**
- **Назначение**: Линтинг JavaScript/TypeScript
- **Конфигурация**: Vue.js + TypeScript

#### **Vitest**
```json
"vitest": "^4.0.3"
```
- **Назначение**: Unit тестирование
- **Почему Vitest?**: Быстрый, нативный для Vite, Jest-совместимый API

---

### Инструменты для базы данных

#### **DBeaver / pgAdmin**
- **Назначение**: Управление базой данных
- **Подключение**: PostgreSQL 15

#### **RedisInsight / redis-cli**
- **Назначение**: Мониторинг Redis, отладка
```bash
docker exec -it redis redis-cli
> KEYS app:prod:*
> GET app:prod:user_tasks_list:uid_5
```

---

### Тестирование API

#### **Postman / Insomnia**
- **Назначение**: Тестирование эндпоинтов API
- **Коллекции**: Все эндпоинты задокументированы

#### **Swagger UI**
- **URL**: `http://localhost:8000/api/doc`
- **Назначение**: Интерактивная документация API

---

## Обоснование выбора технологий

### Почему этот стек?

#### **Backend: Symfony + PHP**
✅ **Корпоративный уровень**: Используется крупными компаниями (Spotify, Trivago)
✅ **Типобезопасность**: PHP 8.3 + строгие типы = меньше багов
✅ **Производительность**: JIT компилятор PHP 8.3 для быстрого выполнения
✅ **Экосистема**: Зрелые бандлы для всего (JWT, OAuth, CORS)
✅ **Документация**: Лучшая документация в классе + огромное сообщество

#### **Frontend: Vue.js + TypeScript**
✅ **Опыт разработки**: Composition API + TypeScript = радость
✅ **Производительность**: Виртуальный DOM + реактивность = быстрый UI
✅ **Типобезопасность**: Ловим ошибки до того, как их увидят пользователи
✅ **Библиотека компонентов**: PrimeVue = не изобретаем колесо заново
✅ **Инструментарий**: Vite = мгновенный HMR, быстрые сборки

#### **База данных: PostgreSQL**
✅ **Надежность**: ACID совместимость, проверено в боях
✅ **Возможности**: JSON, рекурсия, продвинутая индексация
✅ **Производительность**: Оптимизатор запросов, эффективные соединения
✅ **Масштабируемость**: Может обрабатывать миллионы строк

---

## Требования к версиям

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

### Инфраструктура

```yaml
postgres: "15-alpine"
php: "8.3-fpm-alpine"
nginx: "1.25-alpine"
node: "20-alpine"
```

---

## Матрица зависимостей

| Компонент | Технология | Версия | Назначение | Рассмотренные альтернативы |
|-----------|-----------|---------|---------|------------------------|
| **Backend фреймворк** | Symfony | 7.1 | API, маршрутизация, DI | Laravel (слишком тяжелый), API Platform (избыточный) |
| **Язык** | PHP | 8.3 | Бизнес-логика | PHP 8.2 (отсутствуют возможности) |
| **База данных** | PostgreSQL | 15 | Хранение данных | MySQL (меньше возможностей), MongoDB (не реляционная) |
| **ORM** | Doctrine | 3.2 | Доступ к данным | Eloquent (только Laravel), Raw SQL (слишком ручной) |
| **Аутентификация** | JWT + OAuth2 | - | Аутентификация | На основе сессий (не масштабируется), Auth0 (дорого) |
| **Frontend фреймворк** | Vue.js | 3.4 | UI | React (более сложный), Angular (слишком тяжелый) |
| **Язык** | TypeScript | 5.4 | Типобезопасность | JavaScript (нет типов), Flow (устарел) |
| **Управление состоянием** | Pinia | 2.1 | Глобальное состояние | Vuex (устарел), Zustand (только React) |
| **UI библиотека** | PrimeVue | 3.50 | Компоненты | Vuetify (только Material Design), Quasar (слишком жесткий) |
| **Инструмент сборки** | Vite | 5.1 | Dev сервер, бандлинг | Webpack (медленнее), Parcel (менее зрелый) |
| **HTTP клиент** | Axios | 1.6 | API вызовы | Fetch API (меньше возможностей), ky (менее популярный) |
| **Тестирование (BE)** | PHPUnit | 9.5 | Unit тесты | Pest (слишком новый), Codeception (слишком тяжелый) |
| **Тестирование (FE)** | Vitest | 4.0 | Unit тесты | Jest (медленнее), Mocha (ручная настройка) |
| **Графики** | ECharts | 6.0 | Визуализации | Chart.js (меньше возможностей), D3.js (слишком низкоуровневый) |

---

## Бенчмарки производительности

### Backend

```
GET /api/tasks                    →  50-100ms
GET /api/analytics/overview       →  35-50ms
GET /api/analytics/dashboard      →  100-150ms
GET /api/analytics/completion     →  40-60ms
```

### Frontend

```
Начальная загрузка:        1.2s (Time to Interactive)
Навигация по маршруту:    50ms
Рендеринг компонента:     30ms (100 задач)
Размер бандла:            ~300KB (gzipped)
```

---

## Связанные документы

### Обязательно прочитать далее
- **[Архитектура](backend/ARCHITECTURE.md)** - Как эти технологии работают вместе
- **[Рабочий процесс разработки](guides/DEVELOPMENT_WORKFLOW.md)** - Настройка стека

### Для справки
- **[Схема базы данных](backend/DATABASE.md)** - Дизайн PostgreSQL
- **[Интеграция API](frontend/API_INTEGRATION.md)** - Конфигурация Axios

---

*Последнее обновление: 2025-01-05*
*Версия технологического стека: 1.0*
