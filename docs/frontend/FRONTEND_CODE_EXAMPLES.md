# 💻 Frontend - Примеры Кода для Быстрого Старта

## 📌 Оглавление

1. [Создание Smart компонента (View)](#создание-smart-компонента)
2. [Создание Dumb компонента](#создание-dumb-компонента)
3. [Работа с Pinia Store](#работа-с-pinia-store)
4. [Создание Composable](#создание-composable)
5. [API Service вызовы](#api-service-вызовы)
6. [Работа с i18n](#работа-с-i18n)
7. [Типизация в TypeScript](#типизация-в-typescript)

---

## Создание Smart компонента

**Расположение**: `src/views/MyFeatureView.vue`

```vue
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useTaskStore } from '@/stores/task.store'
import { useToast } from '@/composables/useToast'
import { useI18n } from 'vue-i18n'
import type { Task } from '@/types/task.types'

// Imports PrimeVue компонентов если используются
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'

// Stores
const taskStore = useTaskStore()

// Composables
const { showSuccess, showError } = useToast()
const { t } = useI18n()

// State (локальное)
const isLoading = ref(false)
const isDialogVisible = ref(false)
const selectedTask = ref<Task | null>(null)

// Computed properties
const activeTasks = computed(() => taskStore.pendingTasks)
const isEmpty = computed(() => activeTasks.value.length === 0)

// Lifecycle
onMounted(async () => {
  await loadTasks()
})

// Functions
async function loadTasks(): Promise<void> {
  isLoading.value = true
  try {
    await taskStore.fetchTasks()
  } catch (error) {
    showError(t('errors.failed_to_load_tasks'))
  } finally {
    isLoading.value = false
  }
}

function openTaskDialog(task: Task): void {
  selectedTask.value = task
  isDialogVisible.value = true
}

async function handleTaskSaved(task: Task): Promise<void> {
  try {
    await taskStore.updateTask(task.id, task)
    showSuccess(t('tasks.task_updated'))
    isDialogVisible.value = false
  } catch (error) {
    showError(t('errors.failed_to_save_task'))
  }
}
</script>

<template>
  <div class="feature-view">
    <!-- Header -->
    <div class="header">
      <h1>{{ t('views.my_feature') }}</h1>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="loader">
      <p>{{ t('common.loading') }}</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="isEmpty" class="empty-state">
      <p>{{ t('tasks.no_tasks') }}</p>
      <Button @click="$router.push('/dashboard')">
        {{ t('common.go_back') }}
      </Button>
    </div>

    <!-- Content -->
    <div v-else class="content">
      <div 
        v-for="task in activeTasks" 
        :key="task.id"
        class="task-item"
        @click="openTaskDialog(task)"
      >
        <h3>{{ task.title }}</h3>
        <p>{{ task.description }}</p>
      </div>
    </div>

    <!-- Dialog -->
    <Dialog 
      v-model:visible="isDialogVisible"
      :header="selectedTask?.title"
      modal
    >
      <!-- Dialog content here -->
    </Dialog>
  </div>
</template>

<style scoped>
.feature-view {
  padding: 20px;
}

.header {
  margin-bottom: 20px;
}

.loader,
.empty-state {
  text-align: center;
  padding: 40px;
}

.content {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
}

.task-item {
  padding: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.task-item:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}
</style>
```

---

## Создание Dumb компонента

**Расположение**: `src/components/tasks/MyTaskItem.vue`

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import Chip from 'primevue/chip'
import Button from 'primevue/button'
import type { Task } from '@/types/task.types'

// Props
interface Props {
  task: Task
  selected?: boolean
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  selected: false,
  loading: false
})

// Emits
const emit = defineEmits<{
  click: [task: Task]
  edit: [task: Task]
  delete: [id: number]
}>()

// Composables
const { t } = useI18n()

// Computed
const isCompleted = computed(() => props.task.isCompleted)
const isOverdue = computed(() => {
  if (!props.task.dueDate || isCompleted.value) return false
  return new Date(props.task.dueDate) < new Date()
})

const statusColor = computed(() => {
  if (isCompleted.value) return 'success'
  if (isOverdue.value) return 'danger'
  return 'info'
})

// Functions
function handleClick(): void {
  emit('click', props.task)
}

function handleEdit(e: Event): void {
  e.stopPropagation()
  emit('edit', props.task)
}

function handleDelete(e: Event): void {
  e.stopPropagation()
  emit('delete', props.task.id)
}
</script>

<template>
  <div 
    class="task-item"
    :class="{ 
      'selected': selected, 
      'completed': isCompleted,
      'overdue': isOverdue
    }"
    @click="handleClick"
  >
    <!-- Task Title -->
    <div class="task-header">
      <h4>{{ task.title }}</h4>
      <Chip 
        :label="statusColor"
        :severity="statusColor"
        size="small"
      />
    </div>

    <!-- Task Description -->
    <p v-if="task.description" class="task-description">
      {{ task.description }}
    </p>

    <!-- Task Meta -->
    <div class="task-meta">
      <span v-if="task.dueDate" class="due-date">
        {{ new Date(task.dueDate).toLocaleDateString() }}
      </span>
      <span v-if="task.tags.length > 0" class="tags">
        {{ task.tags.map(t => t.name).join(', ') }}
      </span>
    </div>

    <!-- Actions -->
    <div class="actions">
      <Button
        icon="pi pi-pencil"
        size="small"
        text
        @click="handleEdit"
      />
      <Button
        icon="pi pi-trash"
        size="small"
        text
        severity="danger"
        :loading="loading"
        @click="handleDelete"
      />
    </div>
  </div>
</template>

<style scoped>
.task-item {
  padding: 12px 16px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.task-item:hover {
  background-color: #f9fafb;
  border-color: #d1d5db;
}

.task-item.selected {
  background-color: #e0e7ff;
  border-color: #4f46e5;
}

.task-item.completed {
  opacity: 0.6;
}

.task-item.overdue {
  border-color: #ef4444;
  background-color: #fef2f2;
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.task-header h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.task-description {
  margin: 8px 0;
  color: #6b7280;
  font-size: 14px;
}

.task-meta {
  display: flex;
  gap: 12px;
  font-size: 12px;
  color: #9ca3af;
  margin: 8px 0;
}

.actions {
  display: flex;
  gap: 4px;
  margin-top: 8px;
}
</style>
```

---

## Работа с Pinia Store

**Расположение**: `src/stores/my-feature.store.ts`

```typescript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { myFeatureService } from '@/services/my-feature.service'
import type { MyFeatureItem } from '@/types/my-feature.types'

export const useMyFeatureStore = defineStore('myFeature', () => {
  // State
  const items = ref<MyFeatureItem[]>([])
  const selectedItem = ref<MyFeatureItem | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const activeItems = computed(() => 
    items.value.filter(item => !item.archived)
  )

  const itemCount = computed(() => activeItems.value.length)

  // Actions
  async function fetchItems(): Promise<void> {
    isLoading.value = true
    error.value = null

    try {
      const data = await myFeatureService.getItems()
      items.value = data
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to fetch items'
      error.value = errorMessage
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function createItem(data: Omit<MyFeatureItem, 'id'>): Promise<MyFeatureItem> {
    isLoading.value = true
    error.value = null

    try {
      const newItem = await myFeatureService.createItem(data)
      items.value.push(newItem)
      return newItem
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to create item'
      error.value = errorMessage
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function updateItem(id: number, data: Partial<MyFeatureItem>): Promise<MyFeatureItem> {
    const index = items.value.findIndex(item => item.id === id)
    
    if (index === -1) {
      throw new Error('Item not found')
    }

    isLoading.value = true
    error.value = null

    try {
      const updated = await myFeatureService.updateItem(id, data)
      items.value[index] = updated
      return updated
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to update item'
      error.value = errorMessage
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function deleteItem(id: number): Promise<void> {
    const index = items.value.findIndex(item => item.id === id)
    
    if (index === -1) {
      throw new Error('Item not found')
    }

    isLoading.value = true
    error.value = null

    try {
      await myFeatureService.deleteItem(id)
      items.value.splice(index, 1)
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to delete item'
      error.value = errorMessage
      throw err
    } finally {
      isLoading.value = false
    }
  }

  function selectItem(item: MyFeatureItem): void {
    selectedItem.value = item
  }

  function clearSelection(): void {
    selectedItem.value = null
  }

  return {
    // State
    items,
    selectedItem,
    isLoading,
    error,
    // Getters
    activeItems,
    itemCount,
    // Actions
    fetchItems,
    createItem,
    updateItem,
    deleteItem,
    selectItem,
    clearSelection
  }
})
```

---

## Создание Composable

**Расположение**: `src/composables/useMyLogic.ts`

```typescript
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useMyFeatureStore } from '@/stores/my-feature.store'
import { useToast } from '@/composables/useToast'
import type { MyFeatureItem } from '@/types/my-feature.types'

export function useMyLogic() {
  // Dependencies
  const { t } = useI18n()
  const store = useMyFeatureStore()
  const { showSuccess, showError } = useToast()

  // Local state
  const isProcessing = ref(false)

  // Derived state
  const itemsByCategory = computed(() => {
    const grouped: Record<string, MyFeatureItem[]> = {}
    
    store.items.forEach(item => {
      const category = item.category || 'uncategorized'
      if (!grouped[category]) {
        grouped[category] = []
      }
      grouped[category].push(item)
    })

    return grouped
  })

  // Functions
  async function processItem(item: MyFeatureItem): Promise<void> {
    if (isProcessing.value) return

    isProcessing.value = true

    try {
      // Your business logic here
      await store.updateItem(item.id, { processed: true })
      showSuccess(t('common.success'))
    } catch (error: unknown) {
      const errorMessage = error instanceof Error ? error.message : t('errors.unknown_error')
      showError(errorMessage)
      throw error
    } finally {
      isProcessing.value = false
    }
  }

  async function bulkProcess(items: MyFeatureItem[]): Promise<void> {
    isProcessing.value = true

    try {
      const promises = items.map(item => processItem(item))
      await Promise.all(promises)
      showSuccess(t('common.all_items_processed'))
    } catch (error: unknown) {
      const errorMessage = error instanceof Error ? error.message : t('errors.unknown_error')
      showError(errorMessage)
    } finally {
      isProcessing.value = false
    }
  }

  return {
    isProcessing,
    itemsByCategory,
    processItem,
    bulkProcess
  }
}
```

---

## API Service вызовы

**Расположение**: `src/services/my-feature.service.ts`

```typescript
import { apiClient } from './api.service'
import type { MyFeatureItem, CreateMyFeatureItemRequest } from '@/types/my-feature.types'

const API_ENDPOINTS = {
  ITEMS: '/api/my-feature/items',
  ITEM_BY_ID: (id: number) => `/api/my-feature/items/${id}`,
  ITEM_PROCESS: (id: number) => `/api/my-feature/items/${id}/process`
} as const

class MyFeatureService {
  /**
   * Get all items
   */
  async getItems(): Promise<MyFeatureItem[]> {
    const { data } = await apiClient.get<MyFeatureItem[]>(API_ENDPOINTS.ITEMS)
    return data
  }

  /**
   * Get single item
   */
  async getItem(id: number): Promise<MyFeatureItem> {
    const { data } = await apiClient.get<MyFeatureItem>(
      API_ENDPOINTS.ITEM_BY_ID(id)
    )
    return data
  }

  /**
   * Create new item
   */
  async createItem(request: CreateMyFeatureItemRequest): Promise<MyFeatureItem> {
    const { data } = await apiClient.post<MyFeatureItem>(
      API_ENDPOINTS.ITEMS,
      request
    )
    return data
  }

  /**
   * Update item
   */
  async updateItem(
    id: number,
    request: Partial<MyFeatureItem>
  ): Promise<MyFeatureItem> {
    const { data } = await apiClient.put<MyFeatureItem>(
      API_ENDPOINTS.ITEM_BY_ID(id),
      request
    )
    return data
  }

  /**
   * Delete item
   */
  async deleteItem(id: number): Promise<void> {
    await apiClient.delete(API_ENDPOINTS.ITEM_BY_ID(id))
  }

  /**
   * Process item (custom endpoint)
   */
  async processItem(id: number): Promise<MyFeatureItem> {
    const { data } = await apiClient.post<MyFeatureItem>(
      API_ENDPOINTS.ITEM_PROCESS(id)
    )
    return data
  }
}

export const myFeatureService = new MyFeatureService()
```

---

## Работа с i18n

### Использование в компонентах

```vue
<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t, locale, d, n } = useI18n()

// t() - переводы
const message = t('common.welcome')

// d() - форматирование дат
const formattedDate = d(new Date(), 'short')

// n() - форматирование чисел
const formattedNumber = n(1234.56)

// Переключение языка
function switchLanguage(newLocale: string) {
  locale.value = newLocale
  localStorage.setItem('locale', newLocale)
}
</script>

<template>
  <div>
    <!-- Простой перевод -->
    <h1>{{ t('views.my_feature') }}</h1>

    <!-- Перевод с интерполяцией -->
    <p>{{ t('messages.welcome_user', { name: 'John' }) }}</p>

    <!-- Перевод во множественном числе -->
    <p>{{ t('items.count', itemCount) }}</p>

    <!-- Форматирование дат -->
    <p>{{ d(new Date(), 'long') }}</p>

    <!-- Переключение языка -->
    <button @click="switchLanguage('en')">English</button>
    <button @click="switchLanguage('ru')">Русский</button>
  </div>
</template>
```

### Добавление новых переводов

**Расположение**: `src/i18n/locales/en.ts`

```typescript
export default {
  common: {
    welcome: 'Welcome',
    success: 'Success',
    error: 'Error'
  },
  
  views: {
    my_feature: 'My Feature'
  },
  
  messages: {
    welcome_user: 'Welcome, {name}!'
  },
  
  items: {
    count: 'no items | one item | {count} items'
  }
}
```

---

## Типизация в TypeScript

### Определение типов

**Расположение**: `src/types/my-feature.types.ts`

```typescript
/**
 * My Feature Types
 */

export enum ItemStatus {
  ACTIVE = 'active',
  INACTIVE = 'inactive',
  PROCESSING = 'processing',
  ERROR = 'error'
}

export interface MyFeatureItem {
  id: number
  name: string
  description?: string
  status: ItemStatus
  category?: string
  processed: boolean
  createdAt: string
  updatedAt: string
}

export interface CreateMyFeatureItemRequest {
  name: string
  description?: string
  category?: string
}

export interface UpdateMyFeatureItemRequest extends Partial<CreateMyFeatureItemRequest> {
  status?: ItemStatus
  processed?: boolean
}

export interface MyFeatureStats {
  total: number
  active: number
  processed: number
  errorRate: number
}

export interface PaginatedResponse<T> {
  items: T[]
  total: number
  page: number
  perPage: number
}

// Utility types
export type ItemKey = keyof MyFeatureItem
export type ItemWithoutId = Omit<MyFeatureItem, 'id'>
export type ItemRequired = Required<MyFeatureItem>
```

### Использование типов в компонентах

```typescript
import type { MyFeatureItem, ItemStatus } from '@/types/my-feature.types'

// Props с типизацией
interface Props {
  item: MyFeatureItem
  editable?: boolean
  statuses?: ItemStatus[]
}

const props = defineProps<Props>()

// Emits с типизацией
const emit = defineEmits<{
  update: [item: MyFeatureItem]
  delete: [id: number]
}>()

// Functions with types
function handleUpdate(item: MyFeatureItem): void {
  emit('update', item)
}

const processingItems = computed<MyFeatureItem[]>(() => {
  return props.item.status === ItemStatus.PROCESSING ? [props.item] : []
})
```

---

## ✅ Лучшие Практики

### 1. Всегда типизируйте Props и Emits

```typescript
// ✅ ХОРОШО
const props = defineProps<{ item: Task; editable?: boolean }>()
const emit = defineEmits<{ update: [item: Task] }>()

// ❌ ПЛОХО
const props = defineProps({ item: Object })
const emit = defineEmits(['update'])
```

### 2. Используйте Composables для переиспользуемой логики

```typescript
// ✅ ХОРОШО
const { isCompleted, toggleCompletion } = useTaskCompletion()

// ❌ ПЛОХО (логика в компоненте)
const isCompleted = ref(false)
const toggleCompletion = () => { isCompleted.value = !isCompleted.value }
```

### 3. Все API вызовы через Services

```typescript
// ✅ ХОРОШО
const task = await taskService.getTask(id)

// ❌ ПЛОХО
const { data } = await axios.get(`/api/tasks/${id}`)
```

### 4. Используйте Pinia для глобального состояния

```typescript
// ✅ ХОРОШО
const store = useTaskStore()
const tasks = computed(() => store.tasks)

// ❌ ПЛОХО (локальное состояние в разных компонентах)
const tasks = ref<Task[]>([])
```

### 5. Используйте i18n для всех строк пользователя

```typescript
// ✅ ХОРОШО
showSuccess(t('tasks.task_completed'))

// ❌ ПЛОХО
showSuccess('Task completed')
```

---

## 🧪 Тестирование Компонента

**Расположение**: `src/components/MyComponent.spec.ts`

```typescript
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import MyComponent from './MyComponent.vue'
import type { Task } from '@/types/task.types'

describe('MyComponent.vue', () => {
  let wrapper: any

  beforeEach(() => {
    const mockTask: Task = {
      id: 1,
      title: 'Test Task',
      description: 'Test Description',
      status: 'pending',
      priority: 'high',
      isCompleted: false,
      isOverdue: false,
      tags: [],
      startDate: null,
      dueDate: null,
      completedAt: null,
      parentTaskId: null
    }

    wrapper = mount(MyComponent, {
      props: {
        task: mockTask
      }
    })
  })

  it('renders task title', () => {
    expect(wrapper.find('h3').text()).toBe('Test Task')
  })

  it('emits click event when clicked', async () => {
    await wrapper.find('.task-item').trigger('click')
    expect(wrapper.emitted('click')).toBeTruthy()
  })

  it('emits edit event when edit button is clicked', async () => {
    const editButton = wrapper.find('[icon="pi pi-pencil"]')
    await editButton.trigger('click')
    expect(wrapper.emitted('edit')).toBeTruthy()
  })

  it('shows completed state when task is completed', async () => {
    await wrapper.setProps({
      task: { ...wrapper.props('task'), isCompleted: true }
    })
    expect(wrapper.find('.task-item').classes()).toContain('completed')
  })
})
```

---

**Версия**: 1.0
**Дата**: 2025-11-14
**Автор**: Claude Code AI

