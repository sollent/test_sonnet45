# 🎨 Frontend Architecture - Vue 3 Composition API

> **TL;DR**: Vue 3 with Composition API + TypeScript strict mode. Smart/Dumb component pattern. Pinia for state management. Composables for reusable logic. Everything is type-safe with zero `any` types.

---

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Composition API Patterns](#composition-api-patterns)
- [Component Organization](#component-organization)
- [Composables Architecture](#composables-architecture)
- [TypeScript Strict Mode](#typescript-strict-mode)
- [Code Examples](#code-examples)

---

## Architecture Overview

```
frontend/src/
├── views/                  # Smart components (pages)
│   ├── HomeView.vue
│   ├── AnalyticsView.vue
│   └── CalendarView.vue
├── components/             # Dumb components (reusable)
│   ├── tasks/
│   │   ├── TaskCard.vue
│   │   ├── TaskList.vue
│   │   └── TaskForm.vue
│   ├── ui/
│   │   ├── BaseButton.vue
│   │   └── LoadingSpinner.vue
│   └── modals/
│       └── TaskDetailModal.vue
├── stores/                 # Pinia stores (global state)
│   ├── task.store.ts
│   ├── auth.store.ts
│   └── loader.store.ts
├── composables/            # Reusable logic
│   ├── useTaskCompletion.ts
│   ├── useAuth.ts
│   └── useToast.ts
├── services/               # API calls
│   ├── api.service.ts
│   ├── task.service.ts
│   └── auth.service.ts
├── types/                  # TypeScript types
│   ├── task.types.ts
│   └── api.types.ts
├── router/                 # Vue Router
│   └── index.ts
├── i18n/                   # Internationalization
│   └── locales/
│       ├── en.ts
│       └── ru.ts
└── main.ts                 # App entry point
```

---

## Composition API Patterns

### Why Composition API?

✅ Better TypeScript support
✅ Logic reusability (composables)
✅ Cleaner code organization
✅ Smaller bundle size

### Setup Script Pattern

```vue
<script setup lang="ts">
// ✅ GOOD: <script setup> syntax (Composition API)
import { ref, computed, onMounted } from 'vue'
import { useTaskStore } from '@/stores/task.store'
import type { Task } from '@/types/task.types'

// Props with TypeScript
const props = defineProps<{
  task: Task
  editable?: boolean
}>()

// Emits with TypeScript
const emit = defineEmits<{
  update: [task: Task]
  delete: [id: number]
}>()

// Reactive state
const isEditing = ref(false)
const localTitle = ref(props.task.title)

// Computed properties
const isOverdue = computed(() => {
  if (!props.task.dueDate) return false
  return new Date(props.task.dueDate) < new Date()
})

// Methods
function handleSave() {
  emit('update', {
    ...props.task,
    title: localTitle.value
  })
  isEditing.value = false
}

// Lifecycle hooks
onMounted(() => {
  console.log('Component mounted')
})
</script>

<template>
  <div class="task-card">
    <h3>{{ task.title }}</h3>
    <span v-if="isOverdue" class="overdue">Overdue!</span>
    <button v-if="editable" @click="handleSave">Save</button>
  </div>
</template>
```

---

## Component Organization

### Smart vs Dumb Components

#### Smart Components (Views)
**Location:** `/src/views/`

**Responsibilities:**
✅ Fetch data from stores
✅ Handle business logic
✅ Manage component state
✅ Handle routing

```vue
<!-- views/TasksView.vue -->
<script setup lang="ts">
import { onMounted } from 'vue'
import { useTaskStore } from '@/stores/task.store'
import TaskList from '@/components/tasks/TaskList.vue'

const taskStore = useTaskStore()

onMounted(async () => {
  await taskStore.fetchTasks()
})

function handleTaskUpdate(task: Task) {
  taskStore.updateTask(task.id, task)
}
</script>

<template>
  <div class="tasks-view">
    <TaskList
      :tasks="taskStore.tasks"
      :loading="taskStore.isLoading"
      @update="handleTaskUpdate"
    />
  </div>
</template>
```

#### Dumb Components (Reusable)
**Location:** `/src/components/`

**Responsibilities:**
✅ Receive data via props
✅ Emit events to parent
✅ NO direct store access
✅ Highly reusable

```vue
<!-- components/tasks/TaskList.vue -->
<script setup lang="ts">
import type { Task } from '@/types/task.types'
import TaskCard from './TaskCard.vue'

// Only props and emits, NO store access
const props = defineProps<{
  tasks: Task[]
  loading: boolean
}>()

const emit = defineEmits<{
  update: [task: Task]
  delete: [id: number]
}>()
</script>

<template>
  <div class="task-list">
    <LoadingSpinner v-if="loading" />
    <TaskCard
      v-for="task in tasks"
      :key="task.id"
      :task="task"
      @update="emit('update', $event)"
      @delete="emit('delete', $event)"
    />
  </div>
</template>
```

---

## Composables Architecture

### What are Composables?

**Composables = Reusable logic extracted from components**

### Example: useTaskCompletion

```typescript
// composables/useTaskCompletion.ts
import { useI18n } from 'vue-i18n'
import { useConfirm } from 'primevue/useconfirm'
import { useTaskStore } from '@/stores/task.store'
import { useToast } from '@/composables/useToast'
import type { Task } from '@/types/task.types'

export function useTaskCompletion() {
  const { t } = useI18n()
  const confirm = useConfirm()
  const taskStore = useTaskStore()
  const { showSuccess, showError } = useToast()

  /**
   * Count uncompleted subtasks recursively
   */
  function countUncompletedSubtasks(task: Task): number {
    let count = 0

    if (task.subtasks && Array.isArray(task.subtasks)) {
      for (const subtask of task.subtasks) {
        if (!subtask.isCompleted) {
          count++
        }
        count += countUncompletedSubtasks(subtask)
      }
    }

    return count
  }

  /**
   * Toggle task completion with confirmation
   */
  async function toggleTaskCompletion(task: Task): Promise<void> {
    // If already completed, just uncomplete
    if (task.isCompleted) {
      showSuccess(t('tasks.task_reopened'))
      await taskStore.toggleTaskCompletion(task.id)
      return
    }

    // Count uncompleted subtasks
    const uncompletedCount = countUncompletedSubtasks(task)

    // Show confirmation if has uncompleted subtasks
    if (uncompletedCount > 0) {
      confirm.require({
        message: t('tasks.complete_with_subtasks_message', { count: uncompletedCount }),
        accept: async () => {
          showSuccess(t('tasks.task_completed'))
          await completeTaskWithSubtasks(task.id)
        }
      })
    } else {
      showSuccess(t('tasks.task_completed'))
      await taskStore.toggleTaskCompletion(task.id)
    }
  }

  async function completeTaskWithSubtasks(taskId: number): Promise<Task> {
    await taskStore.toggleTaskCompletion(taskId)
    const task = await taskStore.fetchTask(taskId)

    if (task.subtasks && Array.isArray(task.subtasks)) {
      await completeSubtasksRecursively(task.subtasks)
    }

    return taskStore.fetchTask(taskId)
  }

  async function completeSubtasksRecursively(subtasks: Task[]): Promise<void> {
    for (const subtask of subtasks) {
      if (!subtask.isCompleted) {
        await taskStore.updateTask(subtask.id, { status: 'completed' })

        if (subtask.subtasks) {
          await completeSubtasksRecursively(subtask.subtasks)
        }
      }
    }
  }

  return {
    countUncompletedSubtasks,
    toggleTaskCompletion,
    completeTaskWithSubtasks
  }
}
```

### Usage in Components

```vue
<script setup lang="ts">
import { useTaskCompletion } from '@/composables/useTaskCompletion'
import type { Task } from '@/types/task.types'

const props = defineProps<{
  task: Task
}>()

// ✅ Use composable
const { toggleTaskCompletion } = useTaskCompletion()

async function handleCheckboxChange(checked: boolean) {
  await toggleTaskCompletion(props.task)
}
</script>

<template>
  <Checkbox
    :modelValue="task.isCompleted"
    @update:modelValue="handleCheckboxChange"
  />
</template>
```

---

## TypeScript Strict Mode

### Configuration

```json
// tsconfig.json
{
  "compilerOptions": {
    "strict": true,                    // Enable all strict checks
    "noImplicitAny": true,             // No 'any' types
    "strictNullChecks": true,          // Null safety
    "strictFunctionTypes": true,       // Function type safety
    "noUnusedLocals": true,            // No unused variables
    "noUnusedParameters": true         // No unused params
  }
}
```

### Type Safety Examples

```typescript
// ✅ GOOD: Strongly typed
interface Task {
  id: number
  title: string
  status: TaskStatus
  dueDate: string | null
  subtasks?: Task[]
}

const task: Task = await taskService.getTask(id)
task.title // TypeScript knows this is string
task.dueDate // TypeScript knows this is string | null

// ❌ BAD: Using 'any'
const task: any = await taskService.getTask(id)
task.unknownProperty // No error! Dangerous!

// ✅ GOOD: Enum for status
enum TaskStatus {
  PENDING = 'pending',
  IN_PROGRESS = 'in_progress',
  COMPLETED = 'completed'
}

const status: TaskStatus = TaskStatus.COMPLETED

// ✅ GOOD: Union types
type Priority = 'low' | 'medium' | 'high' | 'urgent'
const priority: Priority = 'high' // Type-safe!
const invalid: Priority = 'invalid' // ❌ TypeScript error

// ✅ GOOD: Generic types
function useState<T>(initialValue: T): [T, (value: T) => void] {
  const value = ref<T>(initialValue)
  const setValue = (newValue: T) => {
    value.value = newValue
  }
  return [value.value, setValue]
}

const [count, setCount] = useState<number>(0)
setCount(10) // ✅ OK
setCount('10') // ❌ TypeScript error

// ✅ GOOD: Type guards
function isTask(obj: any): obj is Task {
  return obj && typeof obj.id === 'number' && typeof obj.title === 'string'
}

const data: unknown = await fetchData()
if (isTask(data)) {
  console.log(data.title) // TypeScript knows data is Task
}
```

---

## Code Examples

### Complete Component Example

```vue
<!-- components/tasks/TaskCard.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import { useTaskCompletion } from '@/composables/useTaskCompletion'
import type { Task } from '@/types/task.types'

const props = defineProps<{
  task: Task
  editable?: boolean
}>()

const emit = defineEmits<{
  update: [task: Task]
  delete: [id: number]
  select: [task: Task]
}>()

const { toggleTaskCompletion } = useTaskCompletion()

const isOverdue = computed(() => {
  if (!props.task.dueDate) return false
  const dueDate = new Date(props.task.dueDate)
  const now = new Date()
  return dueDate < now && !props.task.isCompleted
})

const priorityClass = computed(() => {
  const priorityMap = {
    urgent: 'priority-urgent',
    high: 'priority-high',
    medium: 'priority-medium',
    low: 'priority-low'
  }
  return priorityMap[props.task.priority] || 'priority-medium'
})

async function handleCheckboxChange(checked: boolean) {
  await toggleTaskCompletion(props.task)
}

function handleEdit() {
  emit('select', props.task)
}

function handleDelete() {
  emit('delete', props.task.id)
}
</script>

<template>
  <Card :class="['task-card', priorityClass, { overdue: isOverdue }]">
    <template #header>
      <div class="task-header">
        <Checkbox
          :modelValue="task.isCompleted"
          @update:modelValue="handleCheckboxChange"
        />
        <h3>{{ task.title }}</h3>
        <Tag v-if="isOverdue" severity="danger">Overdue</Tag>
      </div>
    </template>

    <template #content>
      <p v-if="task.description">{{ task.description }}</p>

      <div class="task-metadata">
        <span v-if="task.dueDate">
          <i class="pi pi-calendar" />
          {{ formatDate(task.dueDate) }}
        </span>
        <span>
          <i class="pi pi-tag" />
          {{ task.priorityLabel }}
        </span>
      </div>

      <div v-if="task.tags && task.tags.length" class="task-tags">
        <Chip
          v-for="tag in task.tags"
          :key="tag.id"
          :label="tag.name"
          :style="{ backgroundColor: tag.color }"
        />
      </div>
    </template>

    <template #footer>
      <div class="task-actions">
        <Button
          v-if="editable"
          icon="pi pi-pencil"
          text
          @click="handleEdit"
        />
        <Button
          icon="pi pi-trash"
          severity="danger"
          text
          @click="handleDelete"
        />
      </div>
    </template>
  </Card>
</template>

<style scoped>
.task-card {
  margin-bottom: 1rem;
}

.task-card.overdue {
  border-left: 4px solid #dc2626;
}

.priority-urgent {
  border-left-color: #dc2626;
}

.priority-high {
  border-left-color: #ea580c;
}

.priority-medium {
  border-left-color: #3b82f6;
}

.priority-low {
  border-left-color: #6b7280;
}

.task-header {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.task-metadata {
  display: flex;
  gap: 1rem;
  margin-top: 0.5rem;
  color: #6b7280;
}

.task-tags {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.task-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}
</style>
```

---

## Best Practices

### DO's ✅

✅ **Use Composition API** - `<script setup>` syntax
✅ **TypeScript strict mode** - Zero `any` types
✅ **Smart/Dumb pattern** - Separate concerns
✅ **Composables for logic** - Reusable functions
✅ **Props for data flow** - Explicit dependencies
✅ **Emits for events** - Clear parent communication
✅ **Computed for derived state** - Reactive calculations
✅ **onMounted for side effects** - Lifecycle hooks

### DON'Ts ❌

❌ **Options API** - Use Composition API instead
❌ **`any` types** - Always specify types
❌ **Direct store access in dumb components** - Use props
❌ **Business logic in templates** - Move to methods/computed
❌ **Mutating props** - Emit events instead
❌ **Global variables** - Use stores/composables
❌ **Deep nesting** - Extract components

---

## Related Documents

### Must Read Next
- **[State Management](STATE_MANAGEMENT.md)** - Pinia stores
- **[API Integration](API_INTEGRATION.md)** - Axios setup

### For Reference
- **[Tech Stack](../TECH_STACK.md)** - Frontend technologies

---

*Last updated: 2025-01-05*
*Architecture version: 1.0*
