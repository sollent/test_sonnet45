# 🎨 Архитектура Frontend - Vue 3 Composition API

> **Кратко**: Vue 3 с Composition API + TypeScript строгий режим. Паттерн Smart/Dumb компонентов. Pinia для управления состоянием. Composables для переиспользуемой логики. Все типизировано без использования `any`.

---

## Содержание

- [Обзор архитектуры](#обзор-архитектуры)
- [Паттерны Composition API](#паттерны-composition-api)
- [Организация компонентов](#организация-компонентов)
- [Архитектура Composables](#архитектура-composables)
- [Строгий режим TypeScript](#строгий-режим-typescript)
- [Примеры кода](#примеры-кода)

---

## Обзор архитектуры

```
frontend/src/
├── views/                  # Smart компоненты (страницы)
│   ├── HomeView.vue
│   ├── AnalyticsView.vue
│   └── CalendarView.vue
├── components/             # Dumb компоненты (переиспользуемые)
│   ├── tasks/
│   │   ├── TaskCard.vue
│   │   ├── TaskList.vue
│   │   └── TaskForm.vue
│   ├── ui/
│   │   ├── BaseButton.vue
│   │   └── LoadingSpinner.vue
│   └── modals/
│       └── TaskDetailModal.vue
├── stores/                 # Pinia хранилища (глобальное состояние)
│   ├── task.store.ts
│   ├── auth.store.ts
│   └── loader.store.ts
├── composables/            # Переиспользуемая логика
│   ├── useTaskCompletion.ts
│   ├── useAuth.ts
│   └── useToast.ts
├── services/               # API вызовы
│   ├── api.service.ts
│   ├── task.service.ts
│   └── auth.service.ts
├── types/                  # TypeScript типы
│   ├── task.types.ts
│   └── api.types.ts
├── router/                 # Vue Router
│   └── index.ts
├── i18n/                   # Интернационализация
│   └── locales/
│       ├── en.ts
│       └── ru.ts
└── main.ts                 # Точка входа приложения
```

---

## Паттерны Composition API

### Почему Composition API?

✅ Лучшая поддержка TypeScript
✅ Переиспользуемость логики (composables)
✅ Более чистая организация кода
✅ Меньший размер бандла

### Паттерн Setup Script

```vue
<script setup lang="ts">
// ✅ ХОРОШО: синтаксис <script setup> (Composition API)
import { ref, computed, onMounted } from 'vue'
import { useTaskStore } from '@/stores/task.store'
import type { Task } from '@/types/task.types'

// Props с TypeScript
const props = defineProps<{
  task: Task
  editable?: boolean
}>()

// Emits с TypeScript
const emit = defineEmits<{
  update: [task: Task]
  delete: [id: number]
}>()

// Реактивное состояние
const isEditing = ref(false)
const localTitle = ref(props.task.title)

// Вычисляемые свойства
const isOverdue = computed(() => {
  if (!props.task.dueDate) return false
  return new Date(props.task.dueDate) < new Date()
})

// Методы
function handleSave() {
  emit('update', {
    ...props.task,
    title: localTitle.value
  })
  isEditing.value = false
}

// Хуки жизненного цикла
onMounted(() => {
  console.log('Компонент примонтирован')
})
</script>

<template>
  <div class="task-card">
    <h3>{{ task.title }}</h3>
    <span v-if="isOverdue" class="overdue">Просрочено!</span>
    <button v-if="editable" @click="handleSave">Сохранить</button>
  </div>
</template>
```

---

## Организация компонентов

### Smart vs Dumb компоненты

#### Smart компоненты (Views)
**Расположение:** `/src/views/`

**Ответственность:**
✅ Получение данных из хранилищ
✅ Обработка бизнес-логики
✅ Управление состоянием компонента
✅ Обработка маршрутизации

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

#### Dumb компоненты (Переиспользуемые)
**Расположение:** `/src/components/`

**Ответственность:**
✅ Получение данных через props
✅ Генерация событий для родителя
✅ БЕЗ прямого доступа к хранилищу
✅ Высокая переиспользуемость

```vue
<!-- components/tasks/TaskList.vue -->
<script setup lang="ts">
import type { Task } from '@/types/task.types'
import TaskCard from './TaskCard.vue'

// Только props и emits, БЕЗ доступа к хранилищу
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

## Архитектура Composables

### Что такое Composables?

**Composables = Переиспользуемая логика, извлеченная из компонентов**

### Пример: useTaskCompletion

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
   * Подсчет незавершенных подзадач рекурсивно
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
   * Переключение завершения задачи с подтверждением
   */
  async function toggleTaskCompletion(task: Task): Promise<void> {
    // Если уже завершена, просто отменяем завершение
    if (task.isCompleted) {
      showSuccess(t('tasks.task_reopened'))
      await taskStore.toggleTaskCompletion(task.id)
      return
    }

    // Подсчитываем незавершенные подзадачи
    const uncompletedCount = countUncompletedSubtasks(task)

    // Показываем подтверждение, если есть незавершенные подзадачи
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

### Использование в компонентах

```vue
<script setup lang="ts">
import { useTaskCompletion } from '@/composables/useTaskCompletion'
import type { Task } from '@/types/task.types'

const props = defineProps<{
  task: Task
}>()

// ✅ Используем composable
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

## Строгий режим TypeScript

### Конфигурация

```json
// tsconfig.json
{
  "compilerOptions": {
    "strict": true,                    // Включить все строгие проверки
    "noImplicitAny": true,             // Нет типов 'any'
    "strictNullChecks": true,          // Безопасность null
    "strictFunctionTypes": true,       // Безопасность типов функций
    "noUnusedLocals": true,            // Нет неиспользуемых переменных
    "noUnusedParameters": true         // Нет неиспользуемых параметров
  }
}
```

### Примеры типобезопасности

```typescript
// ✅ ХОРОШО: Строго типизировано
interface Task {
  id: number
  title: string
  status: TaskStatus
  dueDate: string | null
  subtasks?: Task[]
}

const task: Task = await taskService.getTask(id)
task.title // TypeScript знает, что это string
task.dueDate // TypeScript знает, что это string | null

// ❌ ПЛОХО: Использование 'any'
const task: any = await taskService.getTask(id)
task.unknownProperty // Нет ошибки! Опасно!

// ✅ ХОРОШО: Enum для статуса
enum TaskStatus {
  PENDING = 'pending',
  IN_PROGRESS = 'in_progress',
  COMPLETED = 'completed'
}

const status: TaskStatus = TaskStatus.COMPLETED

// ✅ ХОРОШО: Union типы
type Priority = 'low' | 'medium' | 'high' | 'urgent'
const priority: Priority = 'high' // Типобезопасно!
const invalid: Priority = 'invalid' // ❌ Ошибка TypeScript

// ✅ ХОРОШО: Обобщенные типы
function useState<T>(initialValue: T): [T, (value: T) => void] {
  const value = ref<T>(initialValue)
  const setValue = (newValue: T) => {
    value.value = newValue
  }
  return [value.value, setValue]
}

const [count, setCount] = useState<number>(0)
setCount(10) // ✅ OK
setCount('10') // ❌ Ошибка TypeScript

// ✅ ХОРОШО: Type guards
function isTask(obj: any): obj is Task {
  return obj && typeof obj.id === 'number' && typeof obj.title === 'string'
}

const data: unknown = await fetchData()
if (isTask(data)) {
  console.log(data.title) // TypeScript знает, что data это Task
}
```

---

## Примеры кода

### Полный пример компонента

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
        <Tag v-if="isOverdue" severity="danger">Просрочено</Tag>
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

## Лучшие практики

### ДЕЛАТЬ ✅

✅ **Использовать Composition API** - синтаксис `<script setup>`
✅ **Строгий режим TypeScript** - Без типов `any`
✅ **Паттерн Smart/Dumb** - Разделение ответственности
✅ **Composables для логики** - Переиспользуемые функции
✅ **Props для потока данных** - Явные зависимости
✅ **Emits для событий** - Четкая коммуникация с родителем
✅ **Computed для производного состояния** - Реактивные вычисления
✅ **onMounted для побочных эффектов** - Хуки жизненного цикла

### НЕ ДЕЛАТЬ ❌

❌ **Options API** - Используйте вместо него Composition API
❌ **Типы `any`** - Всегда указывайте типы
❌ **Прямой доступ к хранилищу в dumb компонентах** - Используйте props
❌ **Бизнес-логика в шаблонах** - Переместите в methods/computed
❌ **Мутирование props** - Генерируйте события вместо этого
❌ **Глобальные переменные** - Используйте stores/composables
❌ **Глубокая вложенность** - Извлекайте компоненты

---

## Связанные документы

### Обязательно прочитайте далее
- **[Управление состоянием](STATE_MANAGEMENT.md)** - Pinia хранилища
- **[Интеграция с API](API_INTEGRATION.md)** - Настройка Axios

### Для справки
- **[Технологический стек](../TECH_STACK.md)** - Frontend технологии

---

*Последнее обновление: 2025-01-05*
*Версия архитектуры: 1.0*
