# 🗂 State Management - Pinia Stores

> **TL;DR**: Pinia stores for global state management. TaskStore for tasks, AuthStore for authentication. Actions vs Getters pattern. Optimistic UI updates for instant feedback. Cache synchronization with backend Redis.

---

## Table of Contents

- [Pinia Store Pattern](#pinia-store-pattern)
- [TaskStore](#taskstore)
- [AuthStore](#authstore)
- [Actions vs Getters](#actions-vs-getters)
- [Optimistic Updates](#optimistic-updates)
- [Cache Synchronization](#cache-synchronization)

---

## Pinia Store Pattern

### Why Pinia?

✅ **TypeScript-first** - Better type inference than Vuex
✅ **Composition API** - Uses same patterns as Vue 3
✅ **Modular** - Easy to split by domain
✅ **Devtools** - Excellent debugging
✅ **Lightweight** - Only ~1KB

### Store Structure

```typescript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useMyStore = defineStore('my-store', () => {
  // State (ref)
  const items = ref<Item[]>([])
  const loading = ref(false)

  // Getters (computed)
  const completedItems = computed(() =>
    items.value.filter(item => item.completed)
  )

  // Actions (functions)
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

**Location:** `/src/stores/task.store.ts`

### Complete TaskStore Implementation

```typescript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { taskService } from '@/services/task.service'
import type { Task, TaskFilters, TaskStatistics } from '@/types/task.types'

export const useTaskStore = defineStore('task', () => {
  // ===== STATE =====
  const tasks = ref<Task[]>([])
  const selectedTask = ref<Task | null>(null)
  const statistics = ref<TaskStatistics | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // ===== GETTERS =====
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

  // ===== ACTIONS =====
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
    // Update task in store if it exists
    const index = tasks.value.findIndex(t => t.id === id)
    if (index !== -1) {
      tasks.value[index] = task
    }
    return task
  }

  async function createTask(data: CreateTaskRequest): Promise<Task> {
    const newTask = await taskService.createTask(data)

    // ✅ Optimistic update: Add immediately to local state
    tasks.value.unshift(newTask)

    // Invalidate statistics cache
    statistics.value = null

    return newTask
  }

  async function updateTask(id: number, data: UpdateTaskRequest): Promise<Task> {
    // ✅ Optimistic update: Update local state immediately
    const index = tasks.value.findIndex(t => t.id === id)
    if (index !== -1) {
      const optimisticTask = { ...tasks.value[index], ...data }
      tasks.value[index] = optimisticTask
    }

    try {
      const updatedTask = await taskService.updateTask(id, data)

      // Replace with server response
      if (index !== -1) {
        tasks.value[index] = updatedTask
      }

      // Invalidate statistics cache
      statistics.value = null

      return updatedTask
    } catch (error) {
      // ❌ Rollback on error
      await fetchTasks()
      throw error
    }
  }

  async function toggleTaskCompletion(id: number): Promise<Task> {
    const index = tasks.value.findIndex(t => t.id === id)
    if (index === -1) throw new Error('Task not found')

    const task = tasks.value[index]
    const newStatus = task.isCompleted ? 'pending' : 'completed'

    // ✅ Optimistic update
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
      // ❌ Rollback
      tasks.value[index] = task
      throw error
    }
  }

  async function deleteTask(id: number): Promise<void> {
    const index = tasks.value.findIndex(t => t.id === id)
    if (index === -1) return

    // ✅ Optimistic delete
    const deletedTask = tasks.value.splice(index, 1)[0]

    try {
      await taskService.deleteTask(id)
      statistics.value = null
    } catch (error) {
      // ❌ Rollback
      tasks.value.splice(index, 0, deletedTask)
      throw error
    }
  }

  async function fetchStatistics(): Promise<TaskStatistics> {
    if (statistics.value) {
      return statistics.value // Return cached
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
    // State
    tasks,
    selectedTask,
    statistics,
    isLoading,
    error,
    // Getters
    pendingTasks,
    completedTasks,
    todayTasks,
    overdueTasks,
    tasksByPriority,
    // Actions
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

**Location:** `/src/stores/auth.store.ts`

```typescript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/auth.service'
import type { User, LoginResponse } from '@/types/auth.types'
import { STORAGE_KEYS } from '@/config/constants'

export const useAuthStore = defineStore('auth', () => {
  // ===== STATE =====
  const user = ref<User | null>(null)
  const accessToken = ref<string | null>(null)
  const refreshToken = ref<string | null>(null)
  const isLoading = ref(false)

  // ===== GETTERS =====
  const isAuthenticated = computed(() => !!accessToken.value && !!user.value)
  const userName = computed(() => user.value?.name || user.value?.email || '')
  const userEmail = computed(() => user.value?.email || '')

  // ===== ACTIONS =====
  async function loginWithGoogle(credential: string): Promise<void> {
    isLoading.value = true

    try {
      const response: LoginResponse = await authService.loginWithGoogle(credential)

      // Store tokens
      accessToken.value = response.token
      refreshToken.value = response.refreshToken

      // Store in localStorage
      localStorage.setItem(STORAGE_KEYS.ACCESS_TOKEN, response.token)
      localStorage.setItem(STORAGE_KEYS.REFRESH_TOKEN, response.refreshToken)

      // Fetch user profile
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

    // Clear localStorage
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
    // State
    user,
    accessToken,
    refreshToken,
    isLoading,
    // Getters
    isAuthenticated,
    userName,
    userEmail,
    // Actions
    loginWithGoogle,
    fetchUser,
    logout,
    restoreSession
  }
})
```

---

## Actions vs Getters

### When to Use Getters

✅ Derived state (computed from existing state)
✅ Filtering/sorting
✅ Aggregations
✅ Synchronous transformations

```typescript
// ✅ GOOD: Getter for derived state
const completedCount = computed(() =>
  tasks.value.filter(t => t.isCompleted).length
)

const completionRate = computed(() => {
  if (tasks.value.length === 0) return 0
  return (completedCount.value / tasks.value.length) * 100
})
```

### When to Use Actions

✅ Async operations (API calls)
✅ State mutations
✅ Side effects
✅ Complex business logic

```typescript
// ✅ GOOD: Action for async operation
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

## Optimistic Updates

### What is Optimistic UI?

**Update UI immediately, then sync with server**

**Benefits:**
- Instant user feedback
- Perceived performance boost
- Better UX

### Implementation Pattern

```typescript
async function updateTask(id: number, data: UpdateTaskRequest): Promise<Task> {
  const index = tasks.value.findIndex(t => t.id === id)
  if (index === -1) throw new Error('Task not found')

  // 1️⃣ Save original state (for rollback)
  const originalTask = { ...tasks.value[index] }

  // 2️⃣ Optimistic update (immediate UI update)
  tasks.value[index] = { ...originalTask, ...data }

  try {
    // 3️⃣ API call (happens in background)
    const updatedTask = await taskService.updateTask(id, data)

    // 4️⃣ Replace with server response (confirmation)
    tasks.value[index] = updatedTask

    return updatedTask
  } catch (error) {
    // 5️⃣ Rollback on error (restore original state)
    tasks.value[index] = originalTask

    // Show error to user
    showError('Failed to update task')

    throw error
  }
}
```

### Example: Checkbox Toggle

```typescript
// User clicks checkbox
async function toggleTaskCompletion(id: number): Promise<void> {
  // ✅ UI updates instantly (0ms)
  const task = tasks.value.find(t => t.id === id)
  if (task) {
    task.isCompleted = !task.isCompleted
  }

  try {
    // API call (~35ms in background)
    await taskService.updateTask(id, { status: task.isCompleted ? 'completed' : 'pending' })
  } catch (error) {
    // ❌ Rollback if failed
    if (task) {
      task.isCompleted = !task.isCompleted
    }
  }
}
```

**Result:** User sees instant feedback, API sync happens invisibly

---

## Cache Synchronization

### Frontend ↔ Backend Cache Sync

```
Frontend Store (Pinia)  ↔  Backend Cache (Redis)  ↔  Database (PostgreSQL)
```

### Synchronization Strategies

#### 1. UPDATE Strategy (Tasks)

**Backend:** Updates cache when data changes
**Frontend:** Always fetches latest from backend

```typescript
// Frontend: Fetch tasks
async function fetchTasks() {
  // Backend returns cached data (0.5ms) or DB data (~100ms)
  tasks.value = await taskService.getTasks()
}

// Backend: Update cache after create
public function createTask(CreateTaskDto $dto, User $user): Task
{
    $task = new Task();
    // ... create task

    $this->em->persist($task);
    $this->em->flush();

    // ✅ Update cache immediately
    $this->taskCache->updateAfterCreate($user, $task);

    return $task;
}
```

#### 2. INVALIDATE Strategy (Analytics)

**Backend:** Deletes cache when data changes
**Frontend:** Always fetches fresh data

```typescript
// Frontend: Fetch analytics
async function fetchAnalytics() {
  // First request: Cache miss → DB query (~134ms) → Cache SET
  // Subsequent requests: Cache hit (0.19ms)
  analytics.value = await analyticsService.getOverview()
}

// Backend: Invalidate cache after task change
public function updateTask(Task $task): void
{
    $this->em->flush();

    // ✅ Invalidate analytics cache
    $this->analyticsCache->invalidateUserAnalytics($user);

    // Next request will rebuild cache from DB
}
```

---

## Best Practices

### DO's ✅

✅ **Use Composition API syntax** - `defineStore('name', () => {})`
✅ **Separate state/getters/actions** - Clear organization
✅ **Optimistic updates** - Instant UI feedback
✅ **Error handling** - Rollback on failure
✅ **Type everything** - No `any` types
✅ **Clear cache on logout** - Call `clearTasks()`

### DON'Ts ❌

❌ **Mutate state directly from components** - Use actions
❌ **Duplicate state** - Single source of truth
❌ **Forget to handle errors** - Always try/catch
❌ **Skip rollback** - Optimistic updates need rollback
❌ **Cache forever** - Invalidate when needed

---

## Related Documents

### Must Read Next
- **[Architecture](ARCHITECTURE.md)** - Component patterns
- **[API Integration](API_INTEGRATION.md)** - Service layer

### For Reference
- **[Backend Cache System](../backend/CACHE_SYSTEM.md)** - Redis caching

---

*Last updated: 2025-01-05*
*State management version: 1.0*
