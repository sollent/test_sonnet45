# 🗂 Управление Состоянием - Pinia Stores

> **TL;DR**: Pinia stores для управления глобальным состоянием. TaskStore для задач, AuthStore для аутентификации. Паттерн Actions vs Getters. Оптимистичные обновления UI для мгновенной обратной связи.

---

## Содержание

- [Паттерн Pinia Store](#паттерн-pinia-store)
- [TaskStore](#taskstore)
- [AuthStore](#authstore)
- [Actions vs Getters](#actions-vs-getters)
- [Оптимистичные Обновления](#оптимистичные-обновления)

---

## Паттерн Pinia Store

### Почему Pinia?

✅ **TypeScript-first** - Лучший вывод типов, чем в Vuex
✅ **Composition API** - Использует те же паттерны, что и Vue 3
✅ **Модульность** - Легко разделить по доменам
✅ **Devtools** - Отличная отладка
✅ **Легковесность** - Всего ~1KB

### Структура Store

```typescript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useMyStore = defineStore('my-store', () => {
  // Состояние (ref)
  const items = ref<Item[]>([])
  const loading = ref(false)

  // Геттеры (computed)
  const completedItems = computed(() =>
    items.value.filter(item => item.completed)
  )

  // Действия (functions)
  async function fetchItems() {
    loading.value = true
    try {
      items.value = await api.getItems()
    } finally {
      loading.value = false
    }
  }

  return { items, loading, completedItems, fetchItems }
})
```

---

## TaskStore

**Расположение:** `/src/stores/task.store.ts`

### Полная Реализация TaskStore

```typescript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { taskService } from '@/services/task.service'
import type { Task, TaskFilters, TaskStatistics } from '@/types/task.types'

export const useTaskStore = defineStore('task', () => {
  // ===== СОСТОЯНИЕ =====
  const tasks = ref<Task[]>([])
  const selectedTask = ref<Task | null>(null)
  const statistics = ref<TaskStatistics | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // ===== ГЕТТЕРЫ =====
  const pendingTasks = computed(() =>
    tasks.value.filter(t => t.status === 'pending' && !t.isArchived)
  )

  const completedTasks = computed(() =>
    tasks.value.filter(t => t.status === 'completed' && !t.isArchived)
  )

  const todayTasks = computed(() => {
    const today = new Date()
    today.setHours(0, 0, 0, 0)

    return tasks.value.filter(task => {
      const dueDate = task.dueDate ? new Date(task.dueDate) : null
      return dueDate && dueDate.toDateString() === today.toDateString()
    })
  })

  const overdueTasks = computed(() => {
    const now = new Date()
    return tasks.value.filter(task => {
      const dueDate = task.dueDate ? new Date(task.dueDate) : null
      return dueDate && dueDate < now && !task.isCompleted
    })
  })

  const tasksByPriority = computed(() => {
    return {
      urgent: tasks.value.filter(t => t.priority === 'urgent'),
      high: tasks.value.filter(t => t.priority === 'high'),
      medium: tasks.value.filter(t => t.priority === 'medium'),
      low: tasks.value.filter(t => t.priority === 'low')
    }
  })

  // ===== ДЕЙСТВИЯ =====
  async function fetchTasks(filters?: TaskFilters): Promise<void> {
    isLoading.value = true
    error.value = null

    try {
      tasks.value = await taskService.getTasks(filters)
    } catch (e: any) {
      error.value = e.message
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function fetchTask(id: number): Promise<Task> {
    const task = await taskService.getTask(id)
    // Обновить задачу в store, если она существует
    const index = tasks.value.findIndex(t => t.id === id)
    if (index !== -1) {
      tasks.value[index] = task
    }
    return task
  }

  async function createTask(data: CreateTaskRequest): Promise<Task> {
    const newTask = await taskService.createTask(data)

    // ✅ Оптимистичное обновление: добавить сразу в локальное состояние
    tasks.value.unshift(newTask)

    // Инвалидировать кэш статистики
    statistics.value = null

    return newTask
  }

  async function updateTask(id: number, data: UpdateTaskRequest): Promise<Task> {
    // ✅ Оптимистичное обновление: обновить локальное состояние немедленно
    const index = tasks.value.findIndex(t => t.id === id)
    if (index !== -1) {
      const optimisticTask = { ...tasks.value[index], ...data }
      tasks.value[index] = optimisticTask
    }

    try {
      const updatedTask = await taskService.updateTask(id, data)

      // Заменить ответом сервера
      if (index !== -1) {
        tasks.value[index] = updatedTask
      }

      // Инвалидировать кэш статистики
      statistics.value = null

      return updatedTask
    } catch (error) {
      // ❌ Откат при ошибке
      await fetchTasks()
      throw error
    }
  }

  async function toggleTaskCompletion(id: number): Promise<Task> {
    const index = tasks.value.findIndex(t => t.id === id)
    if (index === -1) throw new Error('Task not found')

    const task = tasks.value[index]
    const newStatus = task.isCompleted ? 'pending' : 'completed'

    // ✅ Оптимистичное обновление
    tasks.value[index] = {
      ...task,
      status: newStatus,
      isCompleted: !task.isCompleted,
      completedAt: !task.isCompleted ? new Date().toISOString() : null
    }

    try {
      const updated = await taskService.updateTask(id, { status: newStatus })
      tasks.value[index] = updated
      statistics.value = null
      return updated
    } catch (error) {
      // ❌ Откат
      tasks.value[index] = task
      throw error
    }
  }

  async function deleteTask(id: number): Promise<void> {
    const index = tasks.value.findIndex(t => t.id === id)
    if (index === -1) return

    // ✅ Оптимистичное удаление
    const deletedTask = tasks.value.splice(index, 1)[0]

    try {
      await taskService.deleteTask(id)
      statistics.value = null
    } catch (error) {
      // ❌ Откат
      tasks.value.splice(index, 0, deletedTask)
      throw error
    }
  }

  async function fetchStatistics(): Promise<TaskStatistics> {
    if (statistics.value) {
      return statistics.value // Вернуть из кэша
    }

    statistics.value = await taskService.getStatistics()
    return statistics.value
  }

  function clearTasks() {
    tasks.value = []
    selectedTask.value = null
    statistics.value = null
  }

  return {
    // Состояние
    tasks,
    selectedTask,
    statistics,
    isLoading,
    error,
    // Геттеры
    pendingTasks,
    completedTasks,
    todayTasks,
    overdueTasks,
    tasksByPriority,
    // Действия
    fetchTasks,
    fetchTask,
    createTask,
    updateTask,
    toggleTaskCompletion,
    deleteTask,
    fetchStatistics,
    clearTasks
  }
})
```

---

## AuthStore

**Расположение:** `/src/stores/auth.store.ts`

```typescript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/auth.service'
import type { User, LoginResponse } from '@/types/auth.types'
import { STORAGE_KEYS } from '@/config/constants'

export const useAuthStore = defineStore('auth', () => {
  // ===== СОСТОЯНИЕ =====
  const user = ref<User | null>(null)
  const accessToken = ref<string | null>(null)
  const refreshToken = ref<string | null>(null)
  const isLoading = ref(false)

  // ===== ГЕТТЕРЫ =====
  const isAuthenticated = computed(() => !!accessToken.value && !!user.value)
  const userName = computed(() => user.value?.name || user.value?.email || '')
  const userEmail = computed(() => user.value?.email || '')

  // ===== ДЕЙСТВИЯ =====
  async function loginWithGoogle(credential: string): Promise<void> {
    isLoading.value = true

    try {
      const response: LoginResponse = await authService.loginWithGoogle(credential)

      // Сохранить токены
      accessToken.value = response.token
      refreshToken.value = response.refreshToken

      // Сохранить в localStorage
      localStorage.setItem(STORAGE_KEYS.ACCESS_TOKEN, response.token)
      localStorage.setItem(STORAGE_KEYS.REFRESH_TOKEN, response.refreshToken)

      // Загрузить профиль пользователя
      await fetchUser()
    } finally {
      isLoading.value = false
    }
  }

  async function fetchUser(): Promise<void> {
    user.value = await authService.getProfile()
    localStorage.setItem(STORAGE_KEYS.USER, JSON.stringify(user.value))
  }

  function logout(): void {
    user.value = null
    accessToken.value = null
    refreshToken.value = null

    // Очистить localStorage
    localStorage.removeItem(STORAGE_KEYS.ACCESS_TOKEN)
    localStorage.removeItem(STORAGE_KEYS.REFRESH_TOKEN)
    localStorage.removeItem(STORAGE_KEYS.USER)
  }

  function restoreSession(): void {
    const token = localStorage.getItem(STORAGE_KEYS.ACCESS_TOKEN)
    const refresh = localStorage.getItem(STORAGE_KEYS.REFRESH_TOKEN)
    const userData = localStorage.getItem(STORAGE_KEYS.USER)

    if (token && refresh && userData) {
      accessToken.value = token
      refreshToken.value = refresh
      user.value = JSON.parse(userData)
    }
  }

  return {
    // Состояние
    user,
    accessToken,
    refreshToken,
    isLoading,
    // Геттеры
    isAuthenticated,
    userName,
    userEmail,
    // Действия
    loginWithGoogle,
    fetchUser,
    logout,
    restoreSession
  }
})
```

---

## Actions vs Getters

### Когда Использовать Getters

✅ Производное состояние (вычисленное из существующего состояния)
✅ Фильтрация/сортировка
✅ Агрегации
✅ Синхронные преобразования

```typescript
// ✅ ХОРОШО: Геттер для производного состояния
const completedCount = computed(() =>
  tasks.value.filter(t => t.isCompleted).length
)

const completionRate = computed(() => {
  if (tasks.value.length === 0) return 0
  return (completedCount.value / tasks.value.length) * 100
})
```

### Когда Использовать Actions

✅ Асинхронные операции (API вызовы)
✅ Мутации состояния
✅ Побочные эффекты
✅ Сложная бизнес-логика

```typescript
// ✅ ХОРОШО: Действие для асинхронной операции
async function createTask(data: CreateTaskRequest): Promise<Task> {
  isLoading.value = true
  try {
    const task = await taskService.createTask(data)
    tasks.value.push(task)
    return task
  } finally {
    isLoading.value = false
  }
}
```

---

## Оптимистичные Обновления

### Что такое Оптимистичный UI?

**Обновить UI немедленно, затем синхронизировать с сервером**

**Преимущества:**
- Мгновенная обратная связь пользователю
- Повышение воспринимаемой производительности
- Лучший UX

### Паттерн Реализации

```typescript
async function updateTask(id: number, data: UpdateTaskRequest): Promise<Task> {
  const index = tasks.value.findIndex(t => t.id === id)
  if (index === -1) throw new Error('Task not found')

  // 1️⃣ Сохранить исходное состояние (для отката)
  const originalTask = { ...tasks.value[index] }

  // 2️⃣ Оптимистичное обновление (немедленное обновление UI)
  tasks.value[index] = { ...originalTask, ...data }

  try {
    // 3️⃣ API вызов (происходит в фоновом режиме)
    const updatedTask = await taskService.updateTask(id, data)

    // 4️⃣ Заменить ответом сервера (подтверждение)
    tasks.value[index] = updatedTask

    return updatedTask
  } catch (error) {
    // 5️⃣ Откат при ошибке (восстановить исходное состояние)
    tasks.value[index] = originalTask

    // Показать ошибку пользователю
    showError('Failed to update task')

    throw error
  }
}
```

### Пример: Переключение Чекбокса

```typescript
// Пользователь кликает чекбокс
async function toggleTaskCompletion(id: number): Promise<void> {
  // ✅ UI обновляется мгновенно (0ms)
  const task = tasks.value.find(t => t.id === id)
  if (task) {
    task.isCompleted = !task.isCompleted
  }

  try {
    // API вызов (~35ms в фоновом режиме)
    await taskService.updateTask(id, { status: task.isCompleted ? 'completed' : 'pending' })
  } catch (error) {
    // ❌ Откат при ошибке
    if (task) {
      task.isCompleted = !task.isCompleted
    }
  }
}
```

**Результат:** Пользователь видит мгновенную обратную связь, синхронизация с API происходит незаметно

---

## Лучшие Практики

### ДЕЛАЙТЕ ✅

✅ **Используйте синтаксис Composition API** - `defineStore('name', () => {})`
✅ **Разделяйте state/getters/actions** - Четкая организация
✅ **Оптимистичные обновления** - Мгновенная обратная связь UI
✅ **Обработка ошибок** - Откат при ошибке
✅ **Типизируйте все** - Никаких `any` типов
✅ **Очищайте кэш при выходе** - Вызывайте `clearTasks()`

### НЕ ДЕЛАЙТЕ ❌

❌ **Мутировать состояние напрямую из компонентов** - Используйте действия
❌ **Дублировать состояние** - Единственный источник истины
❌ **Забывать обрабатывать ошибки** - Всегда try/catch
❌ **Пропускать откат** - Оптимистичные обновления требуют отката
❌ **Кэшировать навсегда** - Инвалидируйте при необходимости

---

## Связанные Документы

### Обязательно Прочитайте Далее
- **[Архитектура](ARCHITECTURE.md)** - Паттерны компонентов
- **[API Интеграция](API_INTEGRATION.md)** - Слой сервисов

---

*Последнее обновление: 2025-01-05*
*Версия управления состоянием: 1.0*
