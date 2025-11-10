# Frontend Tests Documentation

Документация по тестированию Vue.js 3 frontend приложения.

## 📊 Общая статистика

**Всего: 115 тестов (100% успешно)**

- ✅ **62 Unit тестов** - изолированное тестирование логики
- ✅ **53 Component тестов** - тестирование UI компонентов

**Время выполнения:** ~3.8 секунды

## 🚀 Быстрый старт

### Запуск тестов

```bash
# Все тесты в watch mode
npm run test

# Single run (для CI)
npm run test:run

# С UI интерфейсом
npm run test:ui

# С coverage отчетом
npm run test:coverage
```

### Запуск конкретных тестов

```bash
# Только unit тесты
npm run test:run -- src/composables src/services src/stores

# Только component тесты
npm run test:run -- src/components src/views

# Конкретный файл
npm run test:run -- src/components/ui/__tests__/BaseButton.spec.ts
```

## 📁 Структура

```
src/
├── tests/
│   ├── setup.ts                    # Глобальная конфигурация
│   └── README.md                   # Этот файл
│
├── composables/
│   ├── useFormValidation.ts
│   ├── useToast.ts
│   └── __tests__/
│       ├── useFormValidation.spec.ts    # 18 тестов
│       └── useToast.spec.ts             # 14 тестов
│
├── services/
│   ├── api.service.ts
│   ├── auth.service.ts
│   └── __tests__/
│       └── auth.service.spec.ts         # 11 тестов
│
├── stores/
│   ├── auth.store.ts
│   └── __tests__/
│       └── auth.store.spec.ts           # 19 тестов
│
├── components/
│   ├── ui/
│   │   ├── BaseButton.vue
│   │   └── __tests__/
│   │       └── BaseButton.spec.ts       # 29 тестов
│   └── forms/
│       ├── LoginForm.vue
│       └── __tests__/
│           └── LoginForm.spec.ts        # 10 тестов
│
└── views/
    ├── HomeView.vue
    └── __tests__/
        └── HomeView.spec.ts             # 14 тестов
```

## 🧪 Типы тестов

### Unit Tests (62 теста)

Изолированное тестирование бизнес-логики без DOM.

**Что тестируем:**
- Composables (функции с Vue reactivity)
- Services (API клиенты)
- Stores (Pinia state management)
- Утилиты и хелперы

**Пример:**
```typescript
import { describe, it, expect } from 'vitest'
import { useFormValidation } from '../useFormValidation'

describe('useFormValidation', () => {
  it('should validate email correctly', () => {
    const { validateEmail } = useFormValidation()
    
    expect(validateEmail('test@example.com')).toBe(true)
    expect(validateEmail('invalid')).toBe(false)
  })
})
```

### Component Tests (53 теста)

Тестирование Vue компонентов с DOM и user interactions.

**Что тестируем:**
- Рендеринг компонентов
- Props и события
- User interactions (клики, ввод текста)
- Условный рендеринг
- Accessibility

**Пример:**
```typescript
import { render, screen } from '@testing-library/vue'
import userEvent from '@testing-library/user-event'
import BaseButton from '../BaseButton.vue'

it('should emit click event', async () => {
  const user = userEvent.setup()
  const handleClick = vi.fn()
  
  render(BaseButton, {
    props: {
      label: 'Click me',
      onClick: handleClick,
    },
  })
  
  await user.click(screen.getByRole('button'))
  
  expect(handleClick).toHaveBeenCalled()
})
```

## 🛠️ Технологии

### Testing Framework
- **Vitest 4.0.3** - Fast Vite-native test runner
  - Hot Module Replacement
  - TypeScript support out of the box
  - Jest-compatible API
  - Blazing fast execution

### Testing Libraries
- **@testing-library/vue** - User-centric component testing
- **@testing-library/user-event** - Realistic user interactions
- **@testing-library/jest-dom** - Custom DOM matchers
- **@vue/test-utils** - Official Vue testing utilities

### Environment
- **happy-dom** - Lightweight DOM implementation
  - Faster than jsdom
  - Good enough for most tests
  - Full DOM API support

## ⚙️ Конфигурация

### vite.config.ts

```typescript
export default defineConfig({
  test: {
    globals: true,              // Глобальные describe, it, expect
    environment: 'happy-dom',   // DOM environment
    setupFiles: ['./src/tests/setup.ts'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      exclude: [
        'node_modules/',
        'src/tests/',
        '**/*.spec.ts',
        '**/*.d.ts',
      ]
    }
  }
})
```

### src/tests/setup.ts

Глобальная конфигурация для всех тестов:

**Моки:**
- `window.matchMedia` - для media queries
- `localStorage` - полнофункциональная реализация
- `fetch` - для HTTP запросов

**Расширения:**
- jest-dom matchers (`toBeInTheDocument`, `toBeDisabled`, etc.)
- Vue Test Utils global config

## 📝 Паттерны написания тестов

### AAA Pattern (Arrange-Act-Assert)

```typescript
it('should do something', () => {
  // Arrange - подготовка
  const input = 'test input'
  const expected = 'expected result'
  
  // Act - действие
  const result = someFunction(input)
  
  // Assert - проверка
  expect(result).toBe(expected)
})
```

### User-centric approach

Тестируем как пользователь, не implementation details:

```typescript
// ❌ BAD - тестируем implementation
expect(wrapper.vm.internalState).toBe(true)

// ✅ GOOD - тестируем видимое поведение
expect(screen.getByText('Success')).toBeInTheDocument()
```

### Accessibility-first

Используем роли и labels:

```typescript
// ✅ Accessibility-friendly
screen.getByRole('button', { name: 'Submit' })
screen.getByLabelText('Email')

// ❌ Fragile
screen.getByTestId('submit-btn')
screen.getByClassName('email-input')
```

## 🎯 Лучшие практики

### 1. Изоляция тестов

```typescript
describe('MyComponent', () => {
  beforeEach(() => {
    // Сброс state перед каждым тестом
    vi.clearAllMocks()
    localStorage.clear()
  })
  
  it('test 1', () => { /* ... */ })
  it('test 2', () => { /* ... */ })
})
```

### 2. Один тест - одна проверка

```typescript
// ✅ GOOD
it('should validate email', () => {
  expect(validateEmail('test@example.com')).toBe(true)
})

it('should reject invalid email', () => {
  expect(validateEmail('invalid')).toBe(false)
})

// ❌ BAD - слишком много проверок в одном тесте
it('should validate email', () => {
  expect(validateEmail('test@example.com')).toBe(true)
  expect(validateEmail('invalid')).toBe(false)
  expect(validateEmail('')).toBe(false)
  // ... много других проверок
})
```

### 3. Описательные имена

```typescript
// ✅ GOOD
it('should disable submit button when form is invalid', () => {})
it('should show error message when login fails', () => {})

// ❌ BAD
it('test1', () => {})
it('button disabled', () => {})
```

### 4. Минимальное мокирование

Мокируем только внешние зависимости:

```typescript
// ✅ GOOD - мокируем внешний API
vi.mock('@/services/api.service')

// ❌ BAD - мокируем внутреннюю логику
vi.mock('@/utils/validators')
```

### 5. Async/Await

Всегда используйте `async/await` для асинхронных операций:

```typescript
it('should fetch user data', async () => {
  const user = await fetchUser()
  expect(user).toBeDefined()
})

it('should wait for element', async () => {
  render(MyComponent)
  const element = await screen.findByText('Loaded')
  expect(element).toBeInTheDocument()
})
```

## 🔍 Debugging тестов

### Watch mode с UI

```bash
npm run test:ui
```

Откроется браузер с интерактивным интерфейсом:
- Фильтрация тестов
- Просмотр coverage
- Повторный запуск failed тестов

### Debug в VSCode

Добавьте в `.vscode/launch.json`:

```json
{
  "type": "node",
  "request": "launch",
  "name": "Debug Vitest Tests",
  "runtimeExecutable": "npm",
  "runtimeArgs": ["run", "test"],
  "console": "integratedTerminal"
}
```

### Console.log в тестах

```typescript
it('debug test', () => {
  const result = someFunction()
  console.log('Result:', result) // Будет виден в output
  expect(result).toBeDefined()
})
```

## 📚 Полезные матчеры

### Jest-DOM matchers

```typescript
// DOM presence
expect(element).toBeInTheDocument()
expect(element).not.toBeInTheDocument()

// Visibility
expect(element).toBeVisible()
expect(element).not.toBeVisible()

// Attributes
expect(button).toBeDisabled()
expect(input).toHaveAttribute('type', 'email')
expect(element).toHaveClass('active')

// Values
expect(input).toHaveValue('test')
expect(select).toHaveValue('option1')

// Text content
expect(element).toHaveTextContent('Hello')
```

### Vitest matchers

```typescript
// Basic
expect(value).toBe(expected)
expect(value).toEqual(expected)
expect(value).toBeTruthy()
expect(value).toBeFalsy()

// Arrays
expect(array).toContain(item)
expect(array).toHaveLength(3)

// Objects
expect(obj).toHaveProperty('key')
expect(obj).toMatchObject({ key: 'value' })

// Functions
expect(fn).toHaveBeenCalled()
expect(fn).toHaveBeenCalledWith(arg1, arg2)
expect(fn).toHaveBeenCalledTimes(2)

// Async
await expect(promise).resolves.toBe(value)
await expect(promise).rejects.toThrow()
```

## 🐛 Решение проблем

### Тест не находит элемент

```typescript
// Проблема
expect(screen.getByText('Hello')).toBeInTheDocument()
// Error: Unable to find element

// Решение 1: Используйте findBy для async
await screen.findByText('Hello')

// Решение 2: Используйте queryBy для проверки отсутствия
expect(screen.queryByText('Hello')).not.toBeInTheDocument()

// Решение 3: Debug DOM
screen.debug() // Покажет HTML в console
```

### localStorage не работает

Убедитесь что setup.ts импортирован в vite.config.ts:

```typescript
export default defineConfig({
  test: {
    setupFiles: ['./src/tests/setup.ts'], // Важно!
  }
})
```

### PrimeVue компоненты не работают

Создайте моки для PrimeVue компонентов:

```typescript
vi.mock('primevue/inputtext', () => ({
  default: {
    name: 'InputText',
    template: '<input v-bind="$attrs" />',
  },
}))
```

## 📖 Дополнительные ресурсы

### Документация
- [Vitest](https://vitest.dev/)
- [Testing Library](https://testing-library.com/docs/vue-testing-library/intro/)
- [Vue Test Utils](https://test-utils.vuejs.org/)
- [Vue.js Testing Guide](https://vuejs.org/guide/scaling-up/testing.html)

### Статьи
- [Common mistakes with Testing Library](https://kentcdodds.com/blog/common-mistakes-with-react-testing-library)
- [Testing Implementation Details](https://kentcdodds.com/blog/testing-implementation-details)
- [Write tests. Not too many. Mostly integration.](https://kentcdodds.com/blog/write-tests)

---

**Версия:** 1.0  
**Дата:** 26 октября 2025  
**Статус:** ✅ Production-ready

