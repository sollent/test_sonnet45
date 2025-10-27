# Frontend Architecture

Подробное описание архитектуры frontend приложения.

## 📐 Архитектурные принципы

### 1. SOLID Principles

#### Single Responsibility Principle (SRP)
- Каждый компонент отвечает за одну задачу
- Сервисы изолированы по функциональности
- Composables инкапсулируют переиспользуемую логику

#### Open/Closed Principle (OCP)
- Базовые компоненты (`BaseButton`) расширяемы через props
- Layouts принимают slots для гибкости
- API service использует interceptors для расширения

#### Liskov Substitution Principle (LSP)
- Все формы следуют единому контракту
- UI компоненты взаимозаменяемы
- TypeScript interfaces обеспечивают контракты

#### Interface Segregation Principle (ISP)
- Типы разделены по доменам (auth, api)
- Props interfaces содержат только необходимое
- Composables предоставляют только нужные методы

#### Dependency Inversion Principle (DIP)
- Компоненты зависят от абстракций (composables)
- Services используют interfaces для типизации
- Pinia stores инкапсулируют бизнес-логику

### 2. Composition API Pattern

```typescript
// ✅ Правильно: Composition API с script setup
<script setup lang="ts">
import { ref, computed } from 'vue'

interface Props {
  title: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  submit: [value: string]
}>()

const value = ref('')
const isValid = computed(() => value.value.length > 0)
</script>
```

### 3. Smart/Dumb Components Pattern

#### Smart Components (Views)
```typescript
// src/views/DashboardView.vue
// - Управляют состоянием через stores
// - Содержат бизнес-логику
// - Обрабатывают роутинг
// - Работают с API
```

#### Dumb Components (UI)
```typescript
// src/components/ui/BaseButton.vue
// - Получают данные через props
// - Emit события наверх
// - Чисто презентационные
// - Переиспользуемые
```

### 4. Feature-Based Structure

```
components/
  ├── forms/          # Feature: Forms
  │   ├── LoginForm.vue
  │   └── RegisterForm.vue
  ├── layout/         # Feature: Layout
  │   └── AuthLayout.vue
  └── ui/             # Feature: Base UI
      └── BaseButton.vue
```

## 🧩 Component Architecture

### Layout Components

#### AuthLayout.vue
```typescript
// Назначение: Обертка для страниц авторизации
// Ответственность:
// - Красивый фон с анимациями
// - Центрирование контента
// - Единый стиль для auth страниц
// - Slots: default, footer

// Использование:
<AuthLayout title="Welcome" subtitle="Sign in">
  <LoginForm />
  <template #footer>
    <p>Don't have account? <RouterLink to="/register">Sign up</RouterLink></p>
  </template>
</AuthLayout>
```

### Form Components

#### LoginForm.vue
```typescript
// Назначение: Форма входа
// Ответственность:
// - Сбор credentials (email, password)
// - Валидация на клиенте
// - Отображение ошибок
// - Emit события submit
// - Loading состояния

// Зависимости:
// - useAuth() - для login
// - useToast() - для уведомлений
// - useFormValidation() - для валидации
```

#### RegisterForm.vue
```typescript
// Назначение: Форма регистрации
// Ответственность:
// - Сбор данных регистрации
// - Валидация паролей (совпадение)
// - Password strength indicator
// - Emit события submit

// Зависимости: аналогично LoginForm
```

### UI Components

#### BaseButton.vue
```typescript
// Назначение: Переиспользуемая кнопка
// Props:
// - variant: 'primary' | 'secondary' | 'outline' | 'text'
// - size: 'small' | 'medium' | 'large'
// - loading: boolean
// - disabled: boolean
// - fullWidth: boolean

// Особенности:
// - Gradient backgrounds
// - Ripple effect
// - Loading spinner
// - Hover animations
```

## 🔌 Composables Architecture

### useAuth()
```typescript
// Назначение: Упрощенный доступ к auth store
// Возвращает:
// - isAuthenticated: ComputedRef<boolean>
// - user: ComputedRef<User | null>
// - isLoading: ComputedRef<boolean>
// - error: ComputedRef<string | null>
// - login(credentials): Promise<void>
// - register(credentials): Promise<void>
// - logout(): void

// Пример использования:
const { isAuthenticated, user, login } = useAuth()
```

### useToast()
```typescript
// Назначение: Упрощенный доступ к PrimeVue Toast
// Методы:
// - showSuccess(message, title?)
// - showError(message, title?)
// - showInfo(message, title?)
// - showWarn(message, title?)

// Пример:
const { showSuccess, showError } = useToast()
showSuccess('Login successful!', 'Welcome')
```

### useFormValidation()
```typescript
// Назначение: Валидация форм
// Возвращает:
// - errors: Ref<Record<string, string>>
// - hasErrors: ComputedRef<boolean>
// - validateField(name, value, rules): boolean
// - clearErrors(): void
// - emailRules: ValidationRule[]
// - passwordRules: ValidationRule[]

// Пример:
const { errors, validateField, emailRules } = useFormValidation()
validateField('email', email, emailRules)
```

## 📦 Services Architecture

### API Service
```typescript
// src/services/api.service.ts
// Ответственность:
// - Создание axios instance
// - Request interceptor (добавление JWT)
// - Response interceptor (обработка 401, refresh token)
// - Централизованная обработка ошибок

// Interceptors:
// Request: добавляет Authorization header
// Response: 
//   - 401 -> try refresh token -> retry request
//   - Other errors -> reject
```

### Auth Service
```typescript
// src/services/auth.service.ts
// Методы:
// - login(credentials): Promise<AuthResponse>
// - register(credentials): Promise<RegisterResponse>
// - refreshToken(request): Promise<AuthResponse>
// - getCurrentUser(): Promise<User>
// - loginWithGoogle(idToken): Promise<AuthResponse>

// Особенности:
// - Типизированные запросы/ответы
// - Использует apiClient
// - Не содержит логику состояния
```

## 🏪 State Management (Pinia)

### Auth Store
```typescript
// src/stores/auth.store.ts
// State:
// - user: User | null
// - accessToken: string | null
// - refreshToken: string | null
// - isLoading: boolean
// - error: string | null

// Getters:
// - isAuthenticated: boolean
// - userEmail: string
// - userRoles: string[]

// Actions:
// - login(credentials)
// - register(credentials)
// - logout()
// - fetchCurrentUser()
// - refreshAccessToken()
// - initializeAuth()

// Особенности:
// - Composition API style
// - Синхронизация с localStorage
// - Автоматическая инициализация при загрузке
```

## 🛣️ Routing Architecture

### Route Configuration
```typescript
// src/router/index.ts
// Routes:
// - / (Home) - public
// - /login - guest only (redirect if authenticated)
// - /register - guest only
// - /dashboard - requires auth
// - /profile - requires auth

// Meta fields:
// - requiresAuth: boolean - требует авторизацию
// - guestOnly: boolean - только для гостей
```

### Navigation Guards
```typescript
// beforeEach guard:
// 1. Инициализирует auth если токен в localStorage
// 2. Проверяет requiresAuth
// 3. Проверяет guestOnly
// 4. Редиректит соответственно
```

## 🎨 Styling Architecture

### CSS Architecture
```
assets/styles/
  └── main.css          # Global styles & CSS variables
```

### CSS Variables System
```css
/* Цветовая палитра */
--primary-[50-900]      # Blue scale
--secondary-[50-900]    # Purple scale
--gray-[50-900]         # Neutral scale

/* Semantic colors */
--success, --warning, --error

/* Background layers */
--bg-primary, --bg-secondary, --bg-tertiary

/* Typography */
--text-primary, --text-secondary, --text-tertiary

/* Effects */
--shadow-sm/md/lg/xl
--transition-fast/base/slow
```

### Animation System
```css
/* Keyframes */
@keyframes fadeIn { ... }
@keyframes slideUp { ... }
@keyframes slideDown { ... }
@keyframes scaleIn { ... }

/* Utility classes */
.animate-fade-in
.animate-slide-up
.animate-slide-down
.animate-scale-in
```

### Component Styling Approach
```vue
<style scoped>
/* 1. Base styles */
.component {
  /* Layout properties */
  /* Visual properties */
  /* Typography */
}

/* 2. Modifiers */
.component--variant { ... }
.component--size { ... }

/* 3. States */
.component:hover { ... }
.component:focus { ... }
.component:disabled { ... }

/* 4. Responsive */
@media (max-width: 768px) { ... }
</style>
```

## 📱 Responsive Design Strategy

### Breakpoints
```typescript
const BREAKPOINTS = {
  mobile: '< 768px',
  tablet: '768px - 1024px',
  desktop: '> 1024px'
}
```

### Mobile-First Approach
```css
/* 1. Base styles (mobile) */
.component {
  font-size: 0.875rem;
  padding: 0.75rem;
}

/* 2. Tablet enhancement */
@media (min-width: 768px) {
  .component {
    font-size: 1rem;
    padding: 1rem;
  }
}

/* 3. Desktop enhancement */
@media (min-width: 1024px) {
  .component {
    font-size: 1.125rem;
    padding: 1.25rem;
  }
}
```

## 🔒 Type Safety

### TypeScript Configuration
```json
{
  "strict": true,                      // Все строгие проверки
  "noUnusedLocals": true,             // Нет неиспользуемых переменных
  "noUnusedParameters": true,         // Нет неиспользуемых параметров
  "noFallthroughCasesInSwitch": true, // Switch без fallthrough
  "noUncheckedIndexedAccess": true    // Безопасный доступ к массивам
}
```

### Type Organization
```
types/
  ├── auth.types.ts    # Authentication types
  ├── api.types.ts     # API response types
  └── env.d.ts         # Environment variables
```

### Generic Patterns
```typescript
// API Response wrapper
interface ApiResponse<T> {
  data: T
  status: number
}

// Error handling
interface ErrorResponse {
  message: string
  code: number
  errors?: ValidationError[]
}
```

## 🚀 Performance Optimizations

### Code Splitting
```typescript
// Route-level code splitting
const DashboardView = () => import('@/views/DashboardView.vue')
```

### Lazy Loading
```typescript
// Component lazy loading
defineAsyncComponent(() => import('@/components/HeavyComponent.vue'))
```

### Computed Properties
```typescript
// Мемоизация вычислений
const filteredItems = computed(() => 
  items.value.filter(item => item.active)
)
```

## 🧪 Testing Strategy

### Component Testing
```typescript
// Тестировать:
// - Props rendering
// - Events emission
// - User interactions
// - Conditional rendering

// Пример:
describe('BaseButton', () => {
  it('emits click event', async () => {
    const wrapper = mount(BaseButton)
    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toBeTruthy()
  })
})
```

### Composables Testing
```typescript
// Тестировать:
// - Return values
// - Side effects
// - Error handling

describe('useAuth', () => {
  it('returns authenticated state', () => {
    const { isAuthenticated } = useAuth()
    expect(isAuthenticated.value).toBe(false)
  })
})
```

## 📚 Best Practices Summary

### DO ✅
- Use TypeScript strict mode
- Follow component naming conventions
- Use Composition API with script setup
- Implement proper error handling
- Add loading states
- Use semantic HTML
- Add ARIA labels
- Follow responsive design patterns
- Use CSS variables
- Leverage PrimeVue components

### DON'T ❌
- Use `any` type
- Mix Options API with Composition API
- Ignore accessibility
- Hardcode values
- Skip error handling
- Use inline styles
- Ignore TypeScript errors
- Create God components
- Duplicate code
- Skip validation

## 🔄 Data Flow

```
User Input
    ↓
Component (View)
    ↓
Composable (useAuth)
    ↓
Pinia Store (authStore)
    ↓
Service (authService)
    ↓
API Client (axios)
    ↓
Backend API
    ↓
Response
    ↓
Store Updates
    ↓
Component Re-renders
```

## 🎯 Future Improvements

1. **Testing**: Добавить unit tests (Vitest)
2. **E2E Testing**: Cypress или Playwright
3. **i18n**: Мультиязычность (vue-i18n)
4. **PWA**: Progressive Web App capabilities
5. **Error Tracking**: Sentry integration
6. **Analytics**: Google Analytics / Mixpanel
7. **Performance Monitoring**: Web Vitals
8. **SEO**: Meta tags management
9. **Dark Mode**: Theme switcher
10. **Storybook**: Component documentation

---

**Документация актуальна на:** 26 октября 2025

