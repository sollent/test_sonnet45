# ⚡ Frontend - Быстрая Шпаргалка

## 🚀 Быстрый Старт Frontend

### Установка и Запуск

```bash
# Перейти во frontend
cd apps/frontend

# Установить зависимости
npm install

# Запустить dev сервер (HMR на порту 3000)
npm run dev

# Type checking
npm run type-check

# Собрать для production
npm run build

# Запустить тесты
npm run test:run

# Запустить E2E тесты
npm run test:e2e

# Lint & fix
npm run lint
```

---

## 📂 Структура Проекта (TL;DR)

```
src/
├── views/              # Smart компоненты (страницы)
├── components/         # Dumb компоненты (UI элементы)
├── stores/            # Pinia (auth, task, loader)
├── services/          # API сервисы (10 файлов)
├── composables/       # Переиспользуемая логика (10)
├── types/            # TypeScript типы (5 файлов)
├── router/           # Vue Router (7 маршрутов)
├── i18n/             # Интернационализация (EN, RU)
└── main.ts           # Entry point
```

---

## 🧩 Smart vs Dumb Компоненты

### Smart (Views)
- Используют Stores
- Вызывают Services
- Управляют состоянием
- Примеры: TasksDashboardView, CalendarView

### Dumb (Components)
- Получают данные через Props
- Отправляют события через Emits
- Чистая UI логика
- Переиспользуемые
- Примеры: TaskCard, TaskFiltersPanel

---

## 💾 Pinia Stores (3 основных)

### AuthStore
```typescript
store.user              // текущий пользователь
store.isAuthenticated   // залогинен ли
store.login()           // вход
store.logout()          // выход
store.refreshAccessToken() // обновить токен
```

### TaskStore (709 строк)
```typescript
store.tasks             // все задачи
store.pendingTasks      // ожидающие
store.completedTasks    // завершенные
store.todayTasks        // на сегодня
store.fetchTasks()      // загрузить
store.createTask()      // создать
store.updateTask()      // обновить
store.deleteTask()      // удалить
```

### LoaderStore
```typescript
store.show()            // показать лоадер
store.finish()          // скрыть лоадер
store.reset()           // сброс
```

---

## 🔌 Composables (10)

| Composable | Назначение | Основные функции |
|-----------|-----------|-------------------|
| `useAuth()` | Авторизация | `login`, `logout`, `isAuthenticated` |
| `useTaskCompletion()` | Завершение задач | `toggleTaskCompletion`, `handleCheckboxChange` |
| `useToast()` | Уведомления | `showSuccess`, `showError`, `showInfo` |
| `useFormValidation()` | Валидация | `validateEmail`, `validatePassword` |
| `useTagSuggestions()` | Автозаполнение | `getSuggestions` |
| `useOfflineDetection()` | Offline статус | `isOnline`, `isModalVisible` |
| `useEnumTranslations()` | Перевод enum | `getPriorityLabel`, `getStatusLabel` |
| `usePrimeVueLocale()` | PrimeVue локаль | `updateLocale` |
| `useI18n()` (vue-i18n) | i18n | `t()`, `locale.value` |
| `useConfirm()` (PrimeVue) | Диалог подтверждения | `require()` |

---

## 📡 API Services (10)

### Все через api.service.ts

```typescript
// api.service.ts имеет:
// - Request interceptor (добавляет Authorization token)
// - Response interceptor (обрабатывает 401, обновляет токены)
// - Failed queue для параллельных запросов

// Использование в сервисах:
const { data } = await apiClient.get('/api/endpoint')
const { data } = await apiClient.post('/api/endpoint', payload)
```

### Специализированные Services

| Service | Основные методы |
|---------|-----------------|
| `taskService` | getTasks, createTask, updateTask, deleteTask, toggleTaskCompletion |
| `authService` | login, register, loginWithGoogle, getCurrentUser, refreshToken |
| `tagService` | getTags, createTag, updateTag, deleteTag |
| `analyticsService` | getTaskStatistics, getProductivityData, getChartData |
| `attachmentService` | uploadAttachment, getAttachment, deleteAttachment |
| `mediaService` | uploadImage, getImageUrl |
| `profileService` | getProfile, updateProfile, changePassword |
| `translationService` | getTranslations |

---

## 🌐 Vue Router (7 маршрутов)

```typescript
/              → Landing (public)
/home          → Home (public)
/login         → LoginView (guests only)
/register      → RegisterView (guests only)
/dashboard     → TasksDashboardView (auth required) ← Main
/calendar      → CalendarView (auth required)
/profile       → ProfileView (auth required)
/analytics     → AnalyticsView (auth required)
```

### Navigation Guard
```typescript
// beforeEach hook проверяет:
// - requiresAuth (нужна авторизация)
// - guestOnly (только для гостей)
// - Редиректит при необходимости
```

---

## 🎨 Composition API Паттерны

### Setup Script (ВСЕГДА используйте!)

```vue
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { Task } from '@/types/task.types'

const props = defineProps<{ task: Task }>()
const emit = defineEmits<{ update: [task: Task] }>()

const isEditing = ref(false)
const isCompleted = computed(() => props.task.isCompleted)

onMounted(() => { /* init */ })
</script>
```

### Composable

```typescript
export function useMyLogic() {
  const store = useStore()
  const isLoading = ref(false)
  
  async function doSomething() {
    isLoading.value = true
    try {
      await store.action()
    } finally {
      isLoading.value = false
    }
  }
  
  return { isLoading, doSomething }
}
```

---

## 🔒 TypeScript Strict Mode

```typescript
// ✅ ХОРОШО
const props = defineProps<{ task: Task }>()
const emit = defineEmits<{ update: [task: Task] }>()
const x: string = 'hello'

// ❌ ПЛОХО
const props = defineProps({ task: Object })
const emit = defineEmits(['update'])
const x: any = 'hello'
```

---

## 🌍 Интернационализация (i18n)

```typescript
// Использование
const { t, locale } = useI18n()
const message = t('tasks.task_completed')
const isRu = locale.value === 'ru'

// Переключение
import { setLocale } from '@/i18n'
setLocale('ru')  // Синхронизирует везде

// Переводы
src/i18n/locales/
├── en.ts  // ~500+ строк
└── ru.ts  // ~500+ строк
```

---

## 📦 Ключевые Технологии

| Технология | Версия | Назначение |
|-----------|--------|-----------|
| Vue.js | 3.4 | UI фреймворк |
| TypeScript | 5.4 | Типизация (strict mode) |
| Pinia | 2.1 | State management |
| Vue Router | 4.3 | Маршрутизация (7 маршрутов) |
| Axios | 1.6 | HTTP клиент |
| PrimeVue | 3.50 | UI компоненты (tree-shaking) |
| vue-i18n | 9.14 | i18n (EN, RU) |
| ECharts | 6.0 | Графики (8 компонентов) |
| Vite | 5.1 | Bundler |
| Vitest | 4.0 | Unit тесты |
| Playwright | 1.56 | E2E тесты |

---

## ✅ Чеклист Нового Компонента

- [ ] Это Smart (используется как view) или Dumb (переиспользуемый)?
- [ ] TypeScript: `defineProps<T>()` и `defineEmits<T>()`?
- [ ] Логика в Composables (не в компоненте)?
- [ ] API вызовы через Services?
- [ ] Состояние в Stores (не в components)?
- [ ] i18n для всех строк?
- [ ] No `any` types?
- [ ] Тесты написаны?

---

## 🐛 Частые Ошибки

| Ошибка | Решение |
|--------|---------|
| `any` типы | Используйте точные типы из `types/` |
| Options API | Используйте `<script setup>` |
| Логика в компонентах | Переместите в Composables |
| API вызовы напрямую | Используйте Services |
| Состояние в Components | Используйте Pinia Stores |
| Неоткомпилированные типы | Запустите `npm run type-check` |
| Нет перевода строк | Используйте `i18n` везде |

---

## 🧪 Тестирование

### Unit Тест

```typescript
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import MyComponent from './MyComponent.vue'

describe('MyComponent', () => {
  it('renders correctly', () => {
    const wrapper = mount(MyComponent, { props: { task } })
    expect(wrapper.find('h3').text()).toBe('Test')
  })
})
```

### Запуск тестов

```bash
npm run test:run         # unit тесты
npm run test:e2e         # E2E тесты
npm run test:coverage    # Coverage
```

---

## 🔥 PWA & Производительность

### PWA Features
- Service Worker (offline first)
- Runtime caching API (NetworkFirst)
- Precache статических файлов
- Manifest для standalone режима

### Bundle Optimization
- Manual chunk splitting (Vue, PrimeVue, ECharts отдельно)
- Gzip + Brotli compression
- CSS code splitting
- Lazy loading routes

### Целевые метрики
```
Production bundle: ~300KB (gzip ~100KB, brotli ~80KB)
Initial load: < 3 seconds
Lighthouse score: 90+
```

---

## 📊 Статистика Проекта

- 33 компонента (8 ui + 12 tasks + 8 analytics)
- 7 views (pages)
- 3 stores (auth, task, loader)
- 10 services (API)
- 10 composables
- 5 type файлов
- 115+ unit тестов
- 15+ E2E сценариев
- 2 языка (EN, RU)
- ~8,000 строк кода

---

## 🔗 Полная Документация

- **FRONTEND_ARCHITECTURE_MAP.md** - полная архитектура
- **FRONTEND_CODE_EXAMPLES.md** - примеры кода
- **docs/frontend/ARCHITECTURE.md** - паттерны Composition API
- **docs/frontend/STATE_MANAGEMENT.md** - Pinia stores
- **docs/frontend/COMPONENTS.md** - библиотека компонентов
- **docs/frontend/API_INTEGRATION.md** - API интеграция

---

## 🎯 Быстрые Переходы

```bash
# Запустить development сервер
npm run dev

# Type check (перед коммитом)
npm run type-check

# Запустить тесты
npm run test:run

# E2E тесты
npm run test:e2e

# Production build
npm run build
```

---

**Версия**: 1.0
**Дата**: 2025-11-14
**Для быстрого доступа**: Сохраните в закладки!

