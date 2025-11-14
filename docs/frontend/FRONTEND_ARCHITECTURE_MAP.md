# 🎨 Frontend TaskFlow - Полная Карта Архитектуры

## 📊 Обзор Проекта

**Наименование**: TaskFlow Frontend
**Технический Стек**: Vue.js 3.4 + TypeScript 5.4 + Pinia 2.1
**Паттерны**: Composition API + Smart/Dumb Components + Composables
**Размер**: 80 файлов TypeScript/Vue (33 компонента, 10 composables, 3 store)
**CSS Фреймворк**: PrimeVue 3.50 (Tree-shaking оптимизирован)
**Интернационализация**: vue-i18n 9.14 (EN/RU поддержка)
**PWA**: Vite-plugin-pwa (Service Worker + Offline первый)

---

## 🏗️ Архитектура в Слоях

```
┌─────────────────────────────────────────────────────────────┐
│                    Views (Smart Компоненты)                 │
│  TasksDashboardView, CalendarView, AnalyticsView, Profile   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│              Components (Dumb Компоненты)                   │
│  TaskCard, TaskDialog, TaskFilters, AdvancedFiltersModal    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│        Composables (Переиспользуемая Логика)                │
│  useTaskCompletion, useAuth, useToast, useTagSuggestions    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│            Pinia Stores (Глобальное Состояние)              │
│      TaskStore (709 lines), AuthStore, LoaderStore          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│            Services (API Слой + Логика)                     │
│  api.service (Axios + interceptors), task.service,          │
│  auth.service, tag.service, analytics.service, etc.         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│           Types (TypeScript Интерфейсы)                     │
│  task.types, auth.types, api.types, profile.types           │
└─────────────────────────────────────────────────────────────┘
```

---

## 📂 Структура Директорий

```
apps/frontend/src/
│
├── components/                    # 33 Vue компонента (dumb)
│   ├── ui/                       # Base компоненты (8)
│   │   ├── BaseButton.vue
│   │   ├── FloatingActionButton.vue
│   │   ├── LanguageSwitcher.vue
│   │   ├── GlobalLanguageSwitcher.vue
│   │   ├── FileUploader.vue
│   │   ├── SimpleFileUploader.vue
│   │   └── __tests__/
│   │
│   ├── tasks/                    # Task управление (12)
│   │   ├── TaskCard.vue          # Dumb: рендер одной задачи
│   │   ├── TaskDialog.vue        # Smart: модал для создания/редактирования
│   │   ├── CreateTaskForm.vue    # Dumb: форма создания
│   │   ├── CreateTaskDialog.vue  # Smart: модал + форма
│   │   ├── TaskDetailsSidebar.vue # Smart: боковая панель деталей
│   │   ├── TaskFilters.vue       # Smart: панель фильтров
│   │   ├── TaskFiltersPanel.vue  # Dumb: UI фильтров
│   │   ├── AdvancedFiltersModal.vue # Smart: расширенные фильтры
│   │   ├── QuickFilters.vue      # Smart: быстрые фильтры
│   │   ├── CompletedTasksList.vue # Dumb: список завершенных
│   │   ├── DayHeaderWithProgress.vue # Dumb: заголовок дня
│   │   ├── RecurrenceSettings.vue # Smart: настройки повторения
│   │   └── TaskTreeModal.vue     # Smart: дерево подзадач
│   │
│   ├── analytics/                # Графики (8)
│   │   ├── TopTagsChart.vue
│   │   ├── PriorityBreakdownChart.vue
│   │   ├── ActivityHeatmapChart.vue
│   │   ├── GoalsProgressChart.vue
│   │   ├── CompletionTimelineChart.vue
│   │   ├── StatusDistributionChart.vue
│   │   ├── WeekdayProductivityChart.vue
│   │   └── InsightsPanel.vue
│   │
│   ├── forms/                    # Формы (2)
│   │   ├── LoginForm.vue
│   │   ├── RegisterForm.vue
│   │   └── __tests__/
│   │
│   ├── auth/                     # Аутентификация (1)
│   │   └── GoogleLoginButton.vue
│   │
│   ├── layout/                   # Макеты (1)
│   │   └── AuthLayout.vue
│   │
│   ├── common/                   # Общие (2)
│   │   └── OfflineModal.vue
│   │
│   ├── AppLoader.vue             # Глобальный лоадер
│   └── App.vue                   # Root компонент
│
├── views/                        # 7 страниц (smart компоненты)
│   ├── TasksDashboardView.vue   # Main - список задач с фильтрами
│   ├── CalendarView.vue         # Календарь задач
│   ├── AnalyticsView.vue        # Графики и статистика
│   ├── ProfileView.vue          # Профиль пользователя
│   ├── LoginView.vue            # Форма входа
│   ├── RegisterView.vue         # Форма регистрации
│   ├── HomeView.vue             # Home страница
│   └── LandingPage.vue          # Landing page
│
├── stores/                       # 3 Pinia store
│   ├── auth.store.ts            # Auth состояние + actions
│   ├── task.store.ts            # Task состояние (709 строк)
│   ├── loader.store.ts          # UI loader состояние
│   └── __tests__/
│       └── auth.store.spec.ts
│
├── services/                     # 10 API сервисов
│   ├── api.service.ts           # Axios + interceptors (request/response)
│   ├── task.service.ts          # Task API endpoints
│   ├── auth.service.ts          # Auth API + JWT логика
│   ├── tag.service.ts           # Tag API
│   ├── analytics.service.ts     # Analytics API
│   ├── attachment.service.ts    # File upload API
│   ├── media.service.ts         # Media API
│   ├── profile.service.ts       # Profile API
│   ├── translation.service.ts   # Translation API
│   └── __tests__/
│       └── auth.service.spec.ts
│
├── composables/                  # 10 Composables (переиспользуемая логика)
│   ├── useTaskCompletion.ts     # Логика завершения задач
│   ├── useAuth.ts               # Composable для auth
│   ├── useToast.ts              # Toast notifications
│   ├── useFormValidation.ts     # Form validation
│   ├── useTagSuggestions.ts     # Tag suggestions
│   ├── useOfflineDetection.ts   # Offline/online detection
│   ├── useEnumTranslations.ts   # Enum translation helper
│   ├── usePrimeVueLocale.ts     # PrimeVue locale management
│   └── __tests__/
│
├── types/                        # TypeScript интерфейсы
│   ├── task.types.ts            # Task, Tag, RecurrenceRule, etc.
│   ├── auth.types.ts            # User, LoginCredentials, AuthResponse
│   ├── api.types.ts             # ErrorResponse, API интерфейсы
│   ├── profile.types.ts         # Profile данные
│   └── google.d.ts              # Google Login типы
│
├── router/
│   └── index.ts                 # Vue Router конфигурация (7 маршрутов)
│
├── i18n/                        # Интернационализация
│   ├── index.ts                 # i18n конфигурация
│   ├── primevue-locales.ts      # PrimeVue локализация
│   └── locales/
│       ├── en.ts                # English переводы
│       └── ru.ts                # Russian переводы
│
├── config/
│   └── constants.ts             # API URLs, storage keys, routes
│
├── plugins/
│   └── primevue.ts              # PrimeVue setup (tree-shaking)
│
├── utils/                       # Утилиты
│   └── [различные helpers]
│
├── assets/
│   └── styles/
│       └── main.css             # Global styles
│
├── tests/
│   └── setup.ts                 # Vitest конфигурация
│
└── main.ts                      # Entry point
```

---

## 🔧 Ключевые Технологии

### Core Фреймворк
- **Vue.js 3.4.21** - UI фреймворк
  - Composition API (не Options API)
  - Reactive Refs & Computed
  - Lifecycle Hooks

- **TypeScript 5.4** - Strict mode
  - `noUnusedLocals`: true
  - `noUnusedParameters`: true
  - `strict`: true
  - Path aliases (`@/*`)

### Управление Состоянием & Маршрутизация
- **Pinia 2.1.7** - State management
  - 3 stores: auth, task, loader
  - Composition API синтаксис
  - Actions & Getters
  - Persistence in localStorage

- **Vue Router 4.3** - Client-side routing
  - 7 маршрутов
  - Meta guards (requiresAuth, guestOnly)
  - beforeEach navigation guard для auth

### API & HTTP
- **Axios 1.6.7** - HTTP клиент
  - Request interceptor (Authorization Bearer token)
  - Response interceptor (401 handling, token refresh)
  - Failed request queue для параллельных запросов при refresh

- **10 API Services** - Декораторы для Axios
  - api.service (базовый Axios instance)
  - task.service (CRUD + фильтрация)
  - auth.service (JWT + Google OAuth)
  - tag.service, analytics.service, etc.

### UI & Стилизация
- **PrimeVue 3.50** - UI компонент библиотека
  - Tree-shaking оптимизирован (только использованные компоненты)
  - 25+ компонентов (Button, Dialog, Sidebar, etc.)
  - CSS Переменные для theming
  - Theme: lara-light-blue

- **PrimeIcons 7** - Иконки
  - 6000+ иконки
  - CSS based

- **ECharts 6.0 + vue-echarts 8.0** - Графики
  - 8 chart компонентов в Analytics view

### Интернационализация
- **vue-i18n 9.14.5** - i18n
  - 2 языка: EN, RU
  - Динамическое переключение
  - Sync с localStorage & PrimeVue locale
  - 2 JSON файла со всеми строками

### PWA & Производительность
- **Vite 5.1.5** - Bundler
  - Hot Module Replacement (HMR)
  - Lazy loading маршрутов
  - CSS code splitting
  - Manual chunk splitting (vue-vendor, primevue-vendor, echarts-vendor, utils)
  - Sourcemaps только для staging

- **vite-plugin-pwa 1.1** - PWA поддержка
  - Service Worker регистрация
  - Workbox runtime caching
  - Manifest (standalone mode)
  - Offline поддержка

- **vite-plugin-compression** - Сжатие
  - Gzip (.gz)
  - Brotli (.br)
  - Threshold: 10KB

### Тестирование
- **Vitest 4.0.3** - Unit тесты
  - happy-dom окружение
  - 7 тестовых файлов
  - 115 юнит тестов

- **@vue/test-utils 2.4.6** - Vue компонент тестирование
  - Component mocking
  - Event emitting

- **@testing-library/vue 8.1** - Testing library интеграция
  - DOM queries
  - User event simulation

- **Playwright 1.56.1** - E2E тесты
  - 15+ тестовых сценариев
  - Page Object Model pattern
  - Fixtures для auth

### Дополнительные Инструменты
- **@vueuse/core 10.9** - Vue composition utilities
  - useLocalStorage, useMediaQuery, useInfiniteScroll, и т.д.

- **date-fns 4.1** - Дата утилиты
  - Форматирование, расчеты, парсинг

- **zod 3.22** - Validation (опционально используется)

- **workbox-window 7.3** - PWA service worker интеграция

---

## 🎯 Smart vs Dumb Компоненты

### Smart Компоненты (Container Components)
```
✅ Views/ директория
✅ Используют stores (Pinia)
✅ Содержат business logic
✅ Вызывают API services
✅ Управляют фильтрами, состоянием
✅ Примеры: TasksDashboardView, CalendarView, AnalyticsView
```

### Dumb Компоненты (Presentational Components)
```
✅ Components/ директория
✅ Props-driven (получают данные через props)
✅ Emits для communication
✅ Pure UI логика
✅ Переиспользуемые
✅ Примеры: TaskCard, TaskFiltersPanel, DayHeaderWithProgress
```

---

## 💾 Pinia Stores (3 store)

### 1. AuthStore (Аутентификация)
**Расположение**: `src/stores/auth.store.ts`

```typescript
State:
  - user: User | null
  - accessToken: string | null
  - refreshToken: string | null
  - isLoading: boolean
  - error: string | null

Getters:
  - isAuthenticated: boolean
  - userEmail: string
  - userRoles: string[]

Actions:
  - login(credentials): Promise<void>
  - register(credentials): Promise<void>
  - loginWithGoogle(credential): Promise<void>
  - logout(): void
  - fetchCurrentUser(): Promise<void>
  - refreshAccessToken(): Promise<void>
  - initializeAuth(): Promise<void>
```

**Примечание**: Использует localStorage для persistence

### 2. TaskStore (Управление Задачами)
**Расположение**: `src/stores/task.store.ts` (709 строк)

```typescript
State:
  - tasks: Task[]
  - tags: Tag[]
  - selectedTask: Task | null
  - statistics: TaskStatistics | null
  - currentFilter: TaskFilters
  - activeFilters: TaskFiltersState
  - searchQuery: string
  - overdueTasksPaginated: { tasks, total }
  - unscheduledTasksPaginated: { tasks, total }

Getters:
  - pendingTasks: Task[] (фильтрованные)
  - inProgressTasks: Task[]
  - completedTasks: Task[]
  - todayTasks: Task[]
  - overdueTasks: Task[]
  - upcomingTasks: Task[]
  - filteredTasks: Task[] (с поиском)

Actions:
  - fetchTasks(filters): Promise<void>
  - createTask(data): Promise<Task>
  - updateTask(id, data): Promise<Task>
  - deleteTask(id): Promise<void>
  - toggleTaskCompletion(id): Promise<void>
  - completeWithSubtasks(id): Promise<void>
  - fetchTags(): Promise<void>
  - createTag(name, color): Promise<Tag>
  - applyFilters(filters): Promise<void>
  - clearFilters(): void
```

**Примечание**: Большой store для управления сложным состоянием задач

### 3. LoaderStore (UI Loader)
**Расположение**: `src/stores/loader.store.ts` (32 строки)

```typescript
State:
  - isVisible: boolean
  - loaderKey: number

Actions:
  - show(): void
  - finish(): void
  - reset(): void
```

---

## 🔌 Composables (10)

### 1. useTaskCompletion.ts
**Использование**: Логика завершения задач с подзадачами
**Функции**:
- `countUncompletedSubtasks(task)` - рекурсивный подсчет
- `toggleTaskCompletion(task, onSuccess, onBeforeComplete)` - с подтверждением
- `handleCheckboxChange(task, checked, onSuccess)` - из чекбокса
- `completeTaskWithSubtasks(taskId)` - завершить все сразу

**Примечание**: Интегрирован с useConfirm и useToast

### 2. useAuth.ts
**Использование**: Wrapper над AuthStore
**Функции**:
- `isAuthenticated`, `user`, `isLoading`, `error` - computed
- `login(credentials)`
- `register(credentials)`
- `loginWithGoogle(credential)`
- `logout()`

### 3. useToast.ts
**Использование**: Toast notifications
**Функции**:
- `showSuccess(message, duration)`
- `showError(message, duration)`
- `showInfo(message, duration)`
- `showWarning(message, duration)`

### 4. useFormValidation.ts
**Использование**: Form validation логика
**Функции**:
- `validateEmail(email)` - regex проверка
- `validatePassword(password)` - constraints проверка
- `validateRequired(value)` - empty check

### 5. useTagSuggestions.ts
**Использование**: Autocompletion для тагов
**Функции**:
- `getSuggestions(query, existingTags)` - поиск и фильтрация

### 6. useOfflineDetection.ts
**Использование**: Offline/online статус
**Функции**:
- `isOnline` - computed (из Service Worker + navigator.onLine)
- `isModalVisible` - modal state
- Автоматическое показывание модала при offline

### 7. useEnumTranslations.ts
**Использование**: Перевод enums
**Функции**:
- `getPriorityLabel(priority)` - translate enum
- `getStatusLabel(status)`

### 8. usePrimeVueLocale.ts
**Использование**: Управление PrimeVue локалью
**Функции**:
- `updateLocale(locale)` - switch locale + persist

### 9-10. Другие composables
- useI18n (из vue-i18n)
- useConfirm (из PrimeVue)

---

## 📡 API Services (10)

### Структура API сервисов

```typescript
// api.service.ts - базовый Axios с interceptors
├── Request Interceptor
│   ├── Добавляет Authorization Bearer token
│   ├── Добавляет Accept-Language header
│   └── Пропускает refresh endpoint
├── Response Interceptor
│   ├── Обработка 401 (token refresh)
│   ├── Управление failedQueue для параллельных запросов
│   ├── Обработка network errors
│   └── Offline detection
└── processQueue & clearAuth helpers

// Специализированные сервисы
├── task.service.ts
│   ├── getTasks(filters, limit, offset)
│   ├── createTask(data)
│   ├── updateTask(id, data)
│   ├── deleteTask(id)
│   ├── toggleTaskCompletion(id)
│   ├── archiveTask(id)
│   ├── getTaskStatistics()
│   └── getCalendarTasks(month/week/day)
├── auth.service.ts
│   ├── login(email, password)
│   ├── register(email, password)
│   ├── loginWithGoogle(credential)
│   ├── refreshToken(token)
│   └── getCurrentUser()
├── tag.service.ts
│   ├── getTags()
│   ├── createTag(name, color)
│   ├── updateTag(id, data)
│   └── deleteTag(id)
├── analytics.service.ts
│   ├── getTaskStatistics()
│   ├── getProductivityData()
│   ├── getChartData(type)
│   └── getMetrics(dateRange)
├── attachment.service.ts
│   ├── uploadAttachment(file)
│   ├── getAttachment(id)
│   └── deleteAttachment(id)
├── media.service.ts
│   ├── uploadImage(file)
│   └── getImageUrl(id)
├── profile.service.ts
│   ├── getProfile()
│   ├── updateProfile(data)
│   └── changePassword(oldPassword, newPassword)
└── translation.service.ts
    └── getTranslations(locale)
```

---

## 🌐 Vue Router Конфигурация

**Расположение**: `src/router/index.ts`

```typescript
Routes (7):
├── / (Landing)           - meta: { requiresAuth: false }
├── /home                 - meta: { requiresAuth: false }
├── /login                - meta: { requiresAuth: false, guestOnly: true }
├── /register             - meta: { requiresAuth: false, guestOnly: true }
├── /dashboard            - meta: { requiresAuth: true } ← Main view
├── /calendar             - meta: { requiresAuth: true }
├── /analytics            - meta: { requiresAuth: true }
└── /profile              - meta: { requiresAuth: true }

Navigation Guards:
  ├── beforeEach hook
  ├── Инициализирует auth при первой загрузке
  ├── Проверяет requiresAuth и guestOnly
  └── Редиректит на логин если нужна авторизация
```

---

## 📦 Vite Конфигурация

### Build Оптимизация

```typescript
// Manual Chunk Splitting для оптимального кеширования
manualChunks: {
  'vue-vendor': ['vue', 'vue-router', 'pinia'],
  'primevue-vendor': ['primevue/config', 'primevue/toastservice', ...],
  'primevue-components': ['primevue/autocomplete', 'primevue/button', ...],
  'echarts-vendor': ['echarts/core', 'vue-echarts'],
  'utils': ['axios', 'date-fns', '@vueuse/core', 'zod']
}

// Compression
├── Gzip (.gz)
└── Brotli (.br) - лучше для большинства браузеров

// CSS Code Splitting: true
// Terser minify с drop_console в production

// Asset Naming: [hash] для долгосрочного кеширования
├── js/[name]-[hash].js
├── css/[name]-[hash].css
├── images/[name]-[hash][ext]
└── fonts/[name]-[hash][ext]
```

### Proxy Конфигурация

```typescript
server: {
  port: 3000,
  proxy: {
    '/api': {
      target: 'http://localhost:8089',
      changeOrigin: true
    }
  }
}
```

### PWA Конфигурация

```typescript
VitePWA({
  registerType: 'autoUpdate',
  workbox: {
    globPatterns: ['**/*.{js,css,html,svg,woff2}'],
    runtimeCaching: [
      // API calls - NetworkFirst
      { urlPattern: /\/api\/.*/i, handler: 'NetworkFirst', ... },
      // Images - CacheFirst
      { urlPattern: /\.(png|jpg|svg|gif)$/i, handler: 'CacheFirst', ... },
      // Fonts - CacheFirst (1 year)
      { urlPattern: /\.(woff|woff2|ttf)$/i, handler: 'CacheFirst', ... }
    ]
  }
})
```

---

## 🧪 Тестирование

### Структура Тестов

```
src/
├── components/
│   ├── ui/__tests__/
│   └── forms/__tests__/
├── composables/__tests__/
├── stores/__tests__/
└── services/__tests__/
```

### Виды Тестов (по примерам)

1. **Unit Тесты** - composables, services
2. **Component Тесты** - компоненты с @vue/test-utils
3. **Integration Тесты** - store + service
4. **E2E Тесты** - Playwright (e2e/ директория)

### Примеры

**Unit Тест Composable**:
```typescript
describe('useTaskCompletion', () => {
  it('should count uncompleted subtasks recursively', () => {
    const task = createMockTask()
    const count = countUncompletedSubtasks(task)
    expect(count).toBe(2)
  })
})
```

**Component Тест**:
```typescript
describe('TaskCard.vue', () => {
  it('should emit task-updated when checkbox is checked', async () => {
    const wrapper = mount(TaskCard, { props: { task } })
    const checkbox = wrapper.find('input[type="checkbox"]')
    await checkbox.setValue(true)
    expect(wrapper.emitted('task-updated')).toBeTruthy()
  })
})
```

---

## 🎨 Паттерны Composition API

### Setup Script Pattern

```vue
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useTaskStore } from '@/stores/task.store'
import type { Task } from '@/types/task.types'

// Props с типизацией
interface Props {
  task: Task
  editable?: boolean
}
const props = defineProps<Props>()

// Emits с типизацией
const emit = defineEmits<{
  update: [task: Task]
}>()

// Реактивное состояние
const isEditing = ref(false)

// Вычисляемые свойства
const isOverdue = computed(() => {
  if (!props.task.dueDate) return false
  return new Date(props.task.dueDate) < new Date()
})

// Lifecycle
onMounted(async () => {
  // инициализация
})

// Функции
function handleSave() {
  emit('update', modifiedTask)
}
</script>

<template>
  <div v-if="!isEditing" @click="isEditing = true">
    {{ props.task.title }}
  </div>
  <input
    v-else
    v-model="editedTitle"
    @blur="handleSave"
  />
</template>
```

### Composable Pattern

```typescript
export function useMyLogic() {
  const store = useStore()
  
  const derivedValue = computed(() => {
    return store.value.something
  })
  
  async function doSomething() {
    // логика
    await store.action()
  }
  
  return {
    derivedValue,
    doSomething
  }
}
```

---

## 📊 TypeScript Strict Mode

```typescript
// tsconfig.json
{
  "compilerOptions": {
    "strict": true,                      // Все проверки включены
    "noUnusedLocals": true,             // Ошибка на неиспользованные переменные
    "noUnusedParameters": true,         // Ошибка на неиспользованные параметры
    "noFallthroughCasesInSwitch": true, // Ошибка на switch fallthrough
    "noUncheckedIndexedAccess": true    // Типизированный доступ к массивам
  }
}

// ❌ ПЛОХО: type any
const data: any = await fetchData()

// ✅ ХОРОШО: точная типизация
interface ApiResponse {
  tasks: Task[]
  total: number
}
const data: ApiResponse = await fetchData()
```

---

## 🌍 Интернационализация (i18n)

### Структура

```
src/i18n/
├── index.ts                    # Конфигурация
├── primevue-locales.ts         # PrimeVue локали
└── locales/
    ├── en.ts                   # ~500+ строк переводов
    └── ru.ts                   # ~500+ строк переводов
```

### Использование

```vue
<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

// Использование
const message = t('tasks.task_completed')
const isRussian = locale.value === 'ru'
</script>

<template>
  <p>{{ t('common.welcome') }}</p>
  <p>{{ t('tasks.priority_low') }}</p>
</template>
```

### Переключение локали

```typescript
import { setLocale } from '@/i18n'

function switchToRussian() {
  setLocale('ru')  // Синхронизирует i18n + PrimeVue + localStorage
}
```

---

## 🔒 TypeScript Типы

### Task Related Types

```typescript
export enum TaskStatus {
  PENDING = 'pending',
  IN_PROGRESS = 'in_progress',
  COMPLETED = 'completed',
  CANCELLED = 'cancelled'
}

export enum TaskPriority {
  LOW = 'low',
  MEDIUM = 'medium',
  HIGH = 'high',
  URGENT = 'urgent'
}

export interface Task {
  id: number
  title: string
  description: string | null
  status: TaskStatus
  priority: TaskPriority
  startDate: string | null
  dueDate: string | null
  completedAt: string | null
  parentTaskId: number | null
  subtasks?: Task[]
  tags: Tag[]
  attachments?: TaskAttachment[]
  isArchived?: boolean
  isCompleted: boolean
  isOverdue: boolean
}

export type RecurrenceType = 'daily' | 'weekly' | 'monthly' | 'yearly' | 'custom'

export interface RecurrenceRule {
  id?: number
  recurrenceType: RecurrenceType
  interval?: number
  daysOfWeek?: number[]
  dayOfMonth?: number
  monthOfYear?: number
  endDate?: string | null
  maxOccurrences?: number | null
  timeOfDay?: string | null
  isActive?: boolean
  previewDates?: string[]
}
```

---

## 📈 Производительность

### Bundle Size (Целевые Метрики)

```
Development:
  - Main app: ~500KB (uncompressed)
  - JS chunks: split по 3-5 chunks

Production (с compression):
  - Initial bundle: ~300KB (gzip ~100KB, brotli ~80KB)
  - Lazy loaded routes: ~150KB (gzip ~50KB)
  - CSS: ~100KB (gzip ~20KB)
  - Fonts: ~200KB
  ──────────────────
  Total: ~800KB (gzip ~250KB)
```

### Оптимизация Реализована

✅ Manual chunk splitting (Vue, PrimeVue, ECharts отдельно)
✅ Lazy loading маршрутов (`() => import()`)
✅ Tree-shaking PrimeVue компонентов
✅ CSS code splitting
✅ Gzip + Brotli compression
✅ Service Worker precache 40+ файлов
✅ Runtime caching API calls (NetworkFirst)
✅ Image & font caching (CacheFirst)

---

## 🔑 Критические Паттерны

### 1. Composition API Setup Script

```vue
<script setup lang="ts">
// ✅ ВСЕГДА используйте setup script
// ❌ НЕ используйте Options API
</script>
```

### 2. Пропсы & Emits с Типизацией

```typescript
// ✅ ХОРОШО
const props = defineProps<{ task: Task }>()
const emit = defineEmits<{ update: [task: Task] }>()

// ❌ ПЛОХО
const props = defineProps({ task: Object })
```

### 3. Composables вместо миксинов

```typescript
// ✅ ХОРОШО
const { isCompleted, toggleCompletion } = useTaskCompletion()

// ❌ ПЛОХО
mixins: [taskCompletionMixin]
```

### 4. Pinia stores вместо Vuex

```typescript
// ✅ ХОРОШО
const store = useTaskStore()
store.fetchTasks()

// ❌ ПЛОХО
dispatch('tasks/fetchTasks')
```

### 5. Smart/Dumb разделение

```typescript
// ✅ ХОРОШО (Smart в views)
const store = useTaskStore()
const tasks = computed(() => store.tasks)

// ✅ ХОРОШО (Dumb в components)
const props = defineProps<{ tasks: Task[] }>()
```

### 6. Нет `any` типов

```typescript
// ✅ ХОРОШО
interface ApiResponse {
  tasks: Task[]
}
const data: ApiResponse = await fetchData()

// ❌ ПЛОХО
const data: any = await fetchData()
```

---

## 🚀 Development Workflow

### Запуск Frontend

```bash
# Development (с HMR)
cd apps/frontend && npm run dev
# http://localhost:3000

# Build для production
npm run build

# Type checking
npm run type-check

# Testing
npm run test:run
npm run test:e2e

# Lint & fix
npm run lint
```

---

## ✅ Чеклист Качества Кода

- [ ] **Composition API**: setup script, no Options API
- [ ] **TypeScript**: strict mode, no `any`, точные типы
- [ ] **Props/Emits**: defineProps<T>(), defineEmits<T>()
- [ ] **Components**: Smart (views) vs Dumb (components)
- [ ] **State**: Pinia stores only, no local state в views
- [ ] **Composables**: переиспользуемая логика в composables
- [ ] **API**: all calls through services
- [ ] **Tests**: unit + component + e2e
- [ ] **i18n**: все строки переведены
- [ ] **PWA**: Service Worker работает offline

---

## 🎯 Взаимодействие между Слоями

```
UserInteraction (View)
        ↓
  useAuth() Composable
        ↓
  AuthStore.login() Action
        ↓
  AuthService.login() (API call)
        ↓
  API Interceptor (Authorization header)
        ↓
  Backend: /api/auth
        ↓
  Response (200 OK + tokens)
        ↓
  API Interceptor (save tokens)
        ↓
  Store (update state)
        ↓
  Composable (return response)
        ↓
  View (redirect, show message)
```

---

## 📊 Статистика Проекта

- **Компонентов**: 33 (8 ui + 12 tasks + 8 analytics + 2 forms + 1 auth + 1 layout + 1 common)
- **Views**: 7 (Dashboard, Calendar, Analytics, Profile, Login, Register, Landing)
- **Stores**: 3 (Auth, Task, Loader)
- **Services**: 10 (API + Task + Auth + Tag + Analytics + Attachment + Media + Profile + Translation)
- **Composables**: 10
- **Types**: 5 файлов интерфейсов
- **Тесты**: 115+ юнит тестов, 15+ E2E сценариев
- **Языки**: 2 (EN, RU)
- **Строк кода**: ~8,000 (src/)

---

## 🔗 Ссылки на Документацию

- [Vue.js 3 Docs](https://vuejs.org)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)
- [PrimeVue Docs](https://primevue.org/)
- [Pinia Docs](https://pinia.vuejs.org/)
- [Vite Docs](https://vitejs.dev/)
- [vue-i18n Docs](https://vue-i18n.intlify.dev/)

---

**Версия**: 1.0
**Дата**: 2025-11-14
**Автор**: Claude Code AI
**Уровень детализации**: Medium

