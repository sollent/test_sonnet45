# 📋 Task Manager - Полная документация проекта

## 🎯 Обзор проекта

**Task Manager** - современное веб-приложение для управления задачами с поддержкой подзадач, тегов, календаря и древовидной структуры. Проект построен на современном стеке технологий с акцентом на UX/UX и производительность.

### Основные возможности
- ✅ Создание и управление задачами с подзадачами (неограниченная вложенность)
- 📅 Календарный вид (месяц/неделя) с визуализацией задач
- 🏷️ Система тегов для категоризации
- 🔍 Поиск и фильтрация задач
- 📊 Статистика и прогресс выполнения
- 🌐 Мультиязычность (RU/EN)
- 🎨 Адаптивный дизайн (mobile-first)
- ⚡ Оптимистичные обновления UI

---

## 🏗️ Архитектура проекта

```
test_sonnet45/
├── backend/                    # Symfony 6.4 API
│   ├── config/                 # Конфигурация приложения
│   ├── migrations/             # Миграции базы данных
│   ├── src/
│   │   ├── Controller/         # API контроллеры
│   │   ├── Entity/             # Doctrine entities
│   │   ├── Repository/         # Doctrine repositories
│   │   ├── Service/            # Бизнес-логика
│   │   ├── DTO/                # Data Transfer Objects
│   │   ├── EventSubscriber/    # Event subscribers
│   │   └── Security/           # Аутентификация и авторизация
│   └── docker-compose.yml      # Docker конфигурация
│
└── frontend/                   # Vue.js 3 + TypeScript
    ├── src/
    │   ├── assets/             # Статические ресурсы
    │   ├── components/         # Vue компоненты
    │   │   ├── auth/           # Компоненты аутентификации
    │   │   ├── forms/          # Формы
    │   │   ├── tasks/          # Компоненты задач
    │   │   └── AppLoader.vue   # Загрузочный экран
    │   ├── composables/        # Переиспользуемая логика
    │   ├── i18n/               # Интернационализация
    │   ├── router/             # Vue Router
    │   ├── services/           # API сервисы
    │   ├── stores/             # Pinia stores
    │   ├── types/              # TypeScript типы
    │   ├── utils/              # Утилиты
    │   └── views/              # Страницы приложения
    └── package.json
```

---

## 🔧 Технический стек

### Backend
- **Framework**: Symfony 6.4
- **PHP**: 8.3
- **Database**: PostgreSQL 15
- **ORM**: Doctrine
- **Authentication**: LexikJWTAuthenticationBundle + JWTRefreshTokenBundle
- **API Documentation**: Nelmio API Doc Bundle
- **Validation**: Symfony Validator
- **Serialization**: Symfony Serializer

### Frontend
- **Framework**: Vue.js 3 (Composition API)
- **Language**: TypeScript (strict mode)
- **State Management**: Pinia
- **Routing**: Vue Router
- **UI Library**: PrimeVue
- **HTTP Client**: Axios
- **i18n**: Vue I18n
- **Build Tool**: Vite

### DevOps
- **Containerization**: Docker + Docker Compose
- **Web Server**: Nginx (backend proxy)
- **Development Server**: Vite Dev Server (frontend)

---

## 📊 Модель данных

### User (Пользователь)
```php
- id: int (PK)
- email: string (unique)
- password: string (hashed)
- name: string
- createdAt: DateTime
- updatedAt: DateTime
- tasks: Task[] (OneToMany)
- refreshTokens: RefreshToken[] (OneToMany)
```

### Task (Задача)
```php
- id: int (PK)
- title: string (required)
- description: string (nullable)
- status: enum (pending, in_progress, completed)
- priority: enum (low, medium, high, urgent)
- startDate: DateTime (nullable)
- dueDate: DateTime (nullable)
- isCompleted: boolean
- completionProgress: int (0-100)
- user: User (ManyToOne)
- parent: Task (ManyToOne, nullable) // Родительская задача
- subtasks: Task[] (OneToMany) // Подзадачи
- tags: Tag[] (ManyToMany)
- createdAt: DateTime
- updatedAt: DateTime
```

**Важные особенности Task:**
- Поддержка неограниченной вложенности подзадач (self-referencing)
- Автоматический расчет `completionProgress` на основе подзадач
- Каскадное удаление подзадач при удалении родительской задачи
- Валидация: нельзя установить задачу родителем самой себе

### Tag (Тег)
```php
- id: int (PK)
- name: string (unique per user)
- color: string (hex color)
- user: User (ManyToOne)
- tasks: Task[] (ManyToMany)
```

### RefreshToken (Токен обновления)
```php
- id: int (PK)
- refreshToken: string (unique)
- username: string
- valid: DateTime
```

---

## 🔐 Аутентификация и авторизация

### JWT Authentication
- **Access Token**: Короткоживущий токен (1 час)
- **Refresh Token**: Долгоживущий токен (7 дней)
- **Хранение**: LocalStorage (access token), HttpOnly Cookie (refresh token - планируется)

### Endpoints
```
POST /api/register          # Регистрация
POST /api/login            # Вход
POST /api/token/refresh    # Обновление токена
POST /api/logout           # Выход
GET  /api/user             # Получение текущего пользователя
```

### Google OAuth
- Реализован через Google Sign-In API
- Backend endpoint: `POST /api/auth/google`
- Автоматическое создание пользователя при первом входе

---

## 📡 API Endpoints

### Tasks
```
GET    /api/tasks                    # Список задач
GET    /api/tasks/{id}               # Детали задачи
POST   /api/tasks                    # Создание задачи
PUT    /api/tasks/{id}               # Обновление задачи
DELETE /api/tasks/{id}               # Удаление задачи
POST   /api/tasks/{id}/toggle        # Переключение статуса (completed/pending)
GET    /api/tasks/statistics         # Статистика задач
GET    /api/tasks/overdue            # Просроченные задачи
GET    /api/tasks/week               # Задачи на неделю
GET    /api/tasks/month/{year}/{month} # Задачи на месяц
GET    /api/tasks/day                # Задачи на день
```

### Tags
```
GET    /api/tags                     # Список тегов
POST   /api/tags                     # Создание тега
PUT    /api/tags/{id}                # Обновление тега
DELETE /api/tags/{id}                # Удаление тега
GET    /api/tags/popular             # Популярные теги
```

### Фильтрация и сортировка
Все эндпоинты поддерживают query параметры:
- `?status=pending` - фильтр по статусу
- `?priority=high` - фильтр по приоритету
- `?tag=Работа` - фильтр по тегу
- `?includeSubtasks=true` - включить подзадачи в ответ
- `?search=текст` - поиск по названию/описанию

---

## 🎨 Frontend архитектура

### Структура компонентов

#### Views (Страницы)
- **LandingPage.vue** - Лендинг для незарегистрированных пользователей
- **LoginView.vue** - Страница входа
- **RegisterView.vue** - Страница регистрации
- **TasksDashboardView.vue** - Главная страница с задачами
- **CalendarView.vue** - Календарный вид

#### Components

##### Auth Components
- **LoginForm.vue** - Форма входа
- **RegisterForm.vue** - Форма регистрации
- **GoogleLoginButton.vue** - Кнопка входа через Google

##### Task Components
- **TaskCard.vue** - Карточка задачи (используется в списках)
- **TaskDetailsSidebar.vue** - Боковая панель с деталями задачи
- **TaskFilters.vue** - Фильтры и поиск задач
- **TaskTreeModal.vue** - Модальное окно с древовидной структурой
- **CreateTaskDialog.vue** - Диалог создания задачи

##### Other Components
- **AppLoader.vue** - Анимированный загрузочный экран

### Pinia Stores

#### authStore
```typescript
state: {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  loading: boolean
  error: string | null
}

actions: {
  login(email, password)
  register(userData)
  logout()
  fetchUser()
  loginWithGoogle(credential)
}
```

#### taskStore
```typescript
state: {
  tasks: Task[]
  selectedTask: Task | null
  loading: boolean
  error: string | null
  statistics: Statistics
}

actions: {
  fetchTasks(filters?)
  fetchTask(id)
  createTask(taskData)
  updateTask(id, taskData)
  deleteTask(id)
  toggleTaskCompletion(id)      // С оптимистичным обновлением
  fetchStatistics()
  getTasksForWeek(startDate)
  getTasksForMonth(year, month)
  getTasksForDay(date)
}
```

#### loaderStore
```typescript
state: {
  isVisible: boolean
  loaderKey: number
}

actions: {
  show()      // Показать загрузчик
  finish()    // Скрыть загрузчик
  reset()     // Сбросить и показать заново
}
```

### Composables

#### useTaskCompletion
Управляет логикой завершения задач с подзадачами:
- Проверяет наличие незавершенных подзадач
- Показывает диалог подтверждения
- Рекурсивно завершает все подзадачи
- Поддерживает оптимистичные обновления

```typescript
const {
  toggleTaskCompletion,
  handleCheckboxChange,
  completeTaskWithSubtasks
} = useTaskCompletion()
```

---

## ⚡ Оптимистичные обновления UI

### Концепция
Для улучшения UX все операции обновления задач выполняются оптимистично:
1. UI обновляется **мгновенно** (без ожидания ответа сервера)
2. Запрос отправляется на backend в фоне
3. При успехе - UI остается в новом состоянии
4. При ошибке - UI откатывается к исходному состоянию

### Реализация

#### В taskStore (основные задачи)
```typescript
async toggleTaskCompletion(id: number): Promise<void> {
  // 1. Сохраняем оригинальное состояние
  const originalTask = { ...tasks.value[taskIndex] }
  
  // 2. Оптимистично обновляем UI
  const optimisticTask = {
    ...originalTask,
    isCompleted: !originalTask.isCompleted,
    status: !originalTask.isCompleted ? 'completed' : 'pending'
  }
  tasks.value[taskIndex] = optimisticTask
  
  try {
    // 3. Отправляем запрос
    const updatedTask = await taskService.toggleTask(id)
    
    // 4. Обновляем реальными данными
    tasks.value[taskIndex] = updatedTask
  } catch (err) {
    // 5. Откатываем при ошибке
    tasks.value[taskIndex] = originalTask
    throw err
  }
}
```

#### В TaskCard (чекбоксы задач)
```typescript
async function handleToggleComplete(event: Event) {
  event.stopPropagation()
  event.preventDefault()
  
  const checked = checkbox.checked
  
  // 1. Эмитим оптимистичное обновление
  const optimisticTask = {
    ...props.task,
    isCompleted: checked,
    status: checked ? TaskStatus.COMPLETED : TaskStatus.PENDING
  }
  emit('task-updated', optimisticTask)
  
  // 2. Вызываем handler с callback
  await handleCheckboxChange(props.task, checked, (updatedTask) => {
    emit('task-updated', updatedTask)
  })
}
```

#### В CalendarView/TasksDashboardView
```typescript
function handleTaskCardUpdated(updatedTask: Task) {
  // Обновляем локальные массивы задач без перезагрузки
  const taskIndex = monthTasks.value.findIndex(t => t.id === updatedTask.id)
  if (taskIndex !== -1) {
    monthTasks.value[taskIndex] = updatedTask
  }
  
  // Обновляем selectedTask если нужно
  if (selectedTask.value?.id === updatedTask.id) {
    selectedTask.value = updatedTask
  }
}
```

### Важно!
- **НЕ** перезагружаем весь список задач после обновления
- **НЕ** используем `fetchTasks()` после каждого изменения
- Обновляем только конкретную задачу в массиве
- Это обеспечивает плавность UI и отсутствие моргания

---

## 📅 Календарь - особенности реализации

### Месячный вид
- Отображает задачи в виде точек (dots) на датах
- Цвет точки зависит от приоритета задачи
- Зеленая точка = завершенная задача
- Дни с **только** завершенными задачами подсвечиваются зеленым

### Недельный вид (Timeline)
- Почасовая сетка (00:00 - 23:00)
- Задачи отображаются в виде карточек в соответствующих временных слотах
- Поддержка drag-and-drop (в разработке)

### Логика отображения задач на дату

**Важно!** Задача отображается на дате если:
1. `startDate` совпадает с датой
2. `dueDate` совпадает с датой
3. Задача "растянута" между `startDate` и `dueDate` (включает эту дату)

```typescript
function isTaskOnDate(task: Task, date: Date): boolean {
  const targetDate = normalizeDateValue(date)
  
  if (task.startDate) {
    const start = normalizeDateValue(task.startDate)
    if (isSameDay(start, targetDate)) return true
    
    if (task.dueDate) {
      const due = normalizeDateValue(task.dueDate)
      return targetDate >= start && targetDate <= due
    }
  }
  
  if (task.dueDate) {
    const due = normalizeDateValue(task.dueDate)
    if (isSameDay(due, targetDate)) return true
  }
  
  return false
}
```

### Синхронизация с backend
- Frontend использует `formatDateForApi(date)` для форматирования дат: `YYYY-MM-DD`
- Backend использует `findTasksByDateRange()` для фильтрации задач
- Обе стороны нормализуют даты к полуночи (00:00:00) для корректного сравнения

---

## 🎨 Дизайн система

### Цветовая палитра
```css
/* Primary colors */
--primary-color: #6366f1;        /* Indigo */
--primary-hover: #4f46e5;

/* Status colors */
--success-color: #10b981;        /* Green */
--warning-color: #f59e0b;        /* Amber */
--danger-color: #ef4444;         /* Red */
--info-color: #3b82f6;           /* Blue */

/* Priority colors */
--priority-low: #6b7280;         /* Gray */
--priority-medium: #f59e0b;      /* Amber */
--priority-high: #ef4444;        /* Red */
--priority-urgent: #dc2626;      /* Dark Red */

/* Neutral colors */
--text-primary: #1f2937;
--text-secondary: #6b7280;
--bg-primary: #ffffff;
--bg-secondary: #f9fafb;
--border-color: #e5e7eb;
```

### Border Radius
- Карточки задач: `10px`
- Кнопки: `8px`
- Инпуты: `8px`
- Модальные окна: `12px`
- Сайдбар: `12px`

### Transitions
```css
--transition-fast: 0.15s ease;
--transition-normal: 0.3s ease;
--transition-slow: 0.5s ease;
```

### Focus States
- Убраны двойные borders
- Используется единый `box-shadow` для фокуса
- Цвет: `rgba(99, 102, 241, 0.15)`

---

## 🌐 Интернационализация (i18n)

### Поддерживаемые языки
- Русский (ru) - по умолчанию
- Английский (en)

### Структура переводов
```
i18n/
├── locales/
│   ├── ru.ts    # Русские переводы
│   └── en.ts    # Английские переводы
└── index.ts     # Конфигурация i18n
```

### Использование
```vue
<script setup lang="ts">
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
</script>

<template>
  <h1>{{ t('tasks.title') }}</h1>
</template>
```

### Ключевые секции переводов
- `auth.*` - Аутентификация
- `tasks.*` - Задачи
- `calendar.*` - Календарь
- `common.*` - Общие элементы
- `errors.*` - Сообщения об ошибках
- `loader.*` - Загрузочный экран

---

## 🔄 Жизненный цикл задачи

### 1. Создание задачи
```
User -> CreateTaskDialog -> taskStore.createTask() -> POST /api/tasks
     <- Task created <- 201 Response <- Backend validation
```

### 2. Обновление задачи
```
User clicks checkbox -> TaskCard.handleToggleComplete()
  -> Optimistic UI update (instant)
  -> taskStore.toggleTaskCompletion()
  -> POST /api/tasks/{id}/toggle
  <- Real data update
  (or rollback on error)
```

### 3. Удаление задачи
```
User -> TaskDetailsSidebar -> Confirm dialog
  -> taskStore.deleteTask() -> DELETE /api/tasks/{id}
  -> Remove from local state
  -> Close sidebar
  -> Show success toast
```

### 4. Завершение задачи с подзадачами
```
User clicks checkbox -> Check for uncompleted subtasks
  -> Show confirmation dialog
  -> User confirms
  -> Recursively complete all subtasks
  -> Complete parent task
  -> Update UI
  -> Show success toast
```

---

## 🚀 Производительность и оптимизация

### Frontend оптимизации

#### 1. Оптимистичные обновления
- Мгновенный отклик UI
- Запросы в фоне
- Откат при ошибках

#### 2. Ленивая загрузка
```typescript
// Lazy load views
const CalendarView = () => import('./views/CalendarView.vue')
```

#### 3. Computed свойства
```typescript
// Кэшируются и пересчитываются только при изменении зависимостей
const activeTasks = computed(() => 
  tasks.value.filter(t => !t.isCompleted)
)
```

#### 4. Debounce для поиска
```typescript
const debouncedSearch = debounce((query: string) => {
  searchTasks(query)
}, 300)
```

### Backend оптимизации

#### 1. Eager loading
```php
// Загружаем связи одним запросом
$qb->leftJoin('t.subtasks', 's')
   ->leftJoin('t.tags', 'tag')
   ->addSelect('s', 'tag');
```

#### 2. Индексы базы данных
```php
#[ORM\Index(columns: ['user_id', 'status'])]
#[ORM\Index(columns: ['due_date'])]
#[ORM\Index(columns: ['is_completed'])]
```

#### 3. Pagination
```php
// Ограничение результатов
$qb->setMaxResults(100);
```

---

## 🧪 Тестирование

### Backend тесты (PHPUnit)
```bash
cd backend
docker-compose exec php bin/phpunit
```

### Frontend тесты (Vitest)
```bash
cd frontend
npm run test
```

### E2E тесты (Playwright)
```bash
cd frontend
npm run test:e2e
```

---

## 🐛 Известные проблемы и решения

### Проблема: Даты сдвигаются на день
**Причина**: Использование `toISOString()` приводит к конвертации в UTC
**Решение**: Использовать `formatDateForApi()` для форматирования дат

### Проблема: Двойные borders на фокусе
**Причина**: Конфликт стилей PrimeVue и кастомных стилей
**Решение**: Переопределить focus styles в `main.css`

### Проблема: Задачи "моргают" при обновлении
**Причина**: Полная перезагрузка списка после каждого изменения
**Решение**: Использовать оптимистичные обновления и точечное обновление массивов

### Проблема: Подзадачи не обновляются в реальном времени
**Причина**: Props не реактивны при изменении вложенных объектов
**Решение**: Использовать `localTask` ref для локального состояния (устарело, теперь используем прямое обновление через composable)

---

## 📝 Соглашения о коде

### Backend (Symfony)

#### Naming Conventions
- **Classes**: PascalCase (`TaskService`, `TaskController`)
- **Methods**: camelCase (`createTask`, `findByUser`)
- **Variables**: camelCase (`$taskData`, `$userId`)
- **Constants**: UPPER_SNAKE_CASE (`MAX_TASKS_PER_PAGE`)

#### Architecture Principles
- **SOLID**: Следуем всем принципам SOLID
- **Thin Controllers**: Контроллеры только для валидации и вызова сервисов
- **Service Layer**: Вся бизнес-логика в сервисах
- **Repository Pattern**: Queries только в репозиториях
- **DTO Pattern**: Используем DTO для Request/Response

#### Code Style
```php
// ✅ Good
#[Route('/api/tasks', methods: ['POST'])]
public function create(
    #[MapRequestPayload] CreateTaskDTO $dto,
    TaskService $taskService
): JsonResponse {
    $task = $taskService->createTask($dto, $this->getUser());
    return $this->json($task, 201);
}

// ❌ Bad
public function create(Request $request) {
    $data = json_decode($request->getContent());
    $task = new Task();
    $task->setTitle($data->title);
    // ... direct entity manipulation in controller
}
```

### Frontend (Vue.js + TypeScript)

#### Naming Conventions
- **Components**: PascalCase (`TaskCard.vue`, `TaskDetailsSidebar.vue`)
- **Composables**: camelCase with `use` prefix (`useTaskCompletion`)
- **Variables**: camelCase (`taskList`, `isLoading`)
- **Types/Interfaces**: PascalCase (`Task`, `TaskStatus`)
- **Constants**: UPPER_SNAKE_CASE (`API_BASE_URL`)

#### Component Structure
```vue
<script setup lang="ts">
// 1. Imports
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

// 2. Props & Emits
interface Props {
  task: Task
}
const props = defineProps<Props>()

const emit = defineEmits<{
  'task-updated': [task: Task]
}>()

// 3. Composables
const { t } = useI18n()

// 4. Reactive state
const isLoading = ref(false)

// 5. Computed properties
const taskTitle = computed(() => props.task.title)

// 6. Methods
function handleClick() {
  // ...
}

// 7. Lifecycle hooks
onMounted(() => {
  // ...
})
</script>

<template>
  <!-- Template -->
</template>

<style scoped>
/* Styles */
</style>
```

#### TypeScript Rules
- **Strict mode**: Всегда включен
- **No `any`**: Использовать `unknown` с type guards
- **Type everything**: Props, emits, state, API responses
- **Use type guards**: Для runtime проверок

```typescript
// ✅ Good
interface Task {
  id: number
  title: string
  isCompleted: boolean
}

function isTask(value: unknown): value is Task {
  return (
    typeof value === 'object' &&
    value !== null &&
    'id' in value &&
    'title' in value
  )
}

// ❌ Bad
function handleTask(task: any) {
  console.log(task.title) // No type safety
}
```

---

## 🔐 Безопасность

### Backend Security
- **Password Hashing**: Bcrypt с cost factor 12
- **JWT Tokens**: Подписаны приватным ключом
- **CORS**: Настроен только для разрешенных доменов
- **SQL Injection**: Защита через Doctrine ORM
- **XSS**: Автоматическая экранизация в Twig
- **CSRF**: Токены для форм

### Frontend Security
- **XSS Protection**: Vue автоматически экранирует данные
- **Token Storage**: Access token в localStorage (планируется переход на httpOnly cookies)
- **API Calls**: Всегда через Axios interceptors с токеном
- **Input Validation**: Клиентская валидация + серверная

---

## 🚀 Deployment

### Backend (Docker)
```bash
cd backend
docker-compose up -d
docker-compose exec php composer install
docker-compose exec php bin/console doctrine:migrations:migrate
docker-compose exec php bin/console lexik:jwt:generate-keypair
```

### Frontend (Vite)
```bash
cd frontend
npm install
npm run build
# Deploy dist/ folder to hosting
```

### Environment Variables

#### Backend (.env)
```env
DATABASE_URL="postgresql://user:pass@db:5432/taskmanager"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
```

#### Frontend (.env)
```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_GOOGLE_CLIENT_ID=your_google_client_id
```

---

## 📚 Полезные команды

### Backend
```bash
# Создать миграцию
docker-compose exec php bin/console make:migration

# Применить миграции
docker-compose exec php bin/console doctrine:migrations:migrate

# Создать entity
docker-compose exec php bin/console make:entity

# Очистить кэш
docker-compose exec php bin/console cache:clear

# Загрузить фикстуры
docker-compose exec php bin/console doctrine:fixtures:load
```

### Frontend
```bash
# Запустить dev server
npm run dev

# Собрать production
npm run build

# Проверить типы
npm run type-check

# Lint
npm run lint

# Format
npm run format
```

---

## 🎯 Roadmap

### Ближайшие планы
- [ ] Drag & Drop в календаре (перемещение задач по времени)
- [ ] Push уведомления
- [ ] Повторяющиеся задачи (recurring tasks)
- [ ] Шаблоны задач
- [ ] Экспорт задач (PDF, Excel)
- [ ] Темная тема
- [ ] Мобильное приложение (Capacitor)

### Долгосрочные планы
- [ ] Командная работа (shared tasks)
- [ ] Комментарии к задачам
- [ ] Файловые вложения
- [ ] Интеграция с календарями (Google Calendar, Outlook)
- [ ] API для сторонних приложений
- [ ] Webhooks

---

## 👥 Для разработчиков

### Как начать работу

1. **Клонировать репозиторий**
```bash
git clone <repo-url>
cd test_sonnet45
```

2. **Запустить backend**
```bash
cd backend
docker-compose up -d
docker-compose exec php composer install
docker-compose exec php bin/console doctrine:migrations:migrate
docker-compose exec php bin/console lexik:jwt:generate-keypair
```

3. **Запустить frontend**
```bash
cd frontend
npm install
npm run dev
```

4. **Открыть в браузере**
```
http://localhost:3000
```

### Структура Git веток
- `main` - production код
- `develop` - development код
- `feature/*` - новые фичи
- `bugfix/*` - исправления багов
- `hotfix/*` - срочные исправления

### Commit Convention
```
feat: добавлена поддержка drag & drop
fix: исправлена ошибка с датами в календаре
docs: обновлена документация
style: исправлены отступы
refactor: рефакторинг TaskCard
test: добавлены тесты для TaskService
chore: обновлены зависимости
```

---

## 📞 Контакты и поддержка

### Тестовые аккаунты
- Email: `sollent98@gmail.com` / Password: `Pahan1998`
- Email: `vladislikedev@gmail.com` / Password: `Pahan1998`

### Полезные ссылки
- API Documentation: `http://localhost:8000/api/doc`
- Frontend: `http://localhost:3000`
- Backend: `http://localhost:8000`

---

## 📄 Лицензия

Проект разработан для личного использования.

---

**Последнее обновление**: 01.11.2025

**Версия документации**: 1.0.0
