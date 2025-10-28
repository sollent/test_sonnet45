<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import Checkbox from 'primevue/checkbox'
import Chip from 'primevue/chip'
import type { Task } from '@/types/task.types'
import { TaskPriority, TaskStatus } from '@/types/task.types'

interface Props {
  task: Task
  selected?: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'click': [task: Task]
  'toggle-complete': [task: Task]
}>()

const { t } = useI18n()
const i18n = useI18n()

// Priority configuration
const priorityConfig = computed(() => {
  const configs = {
    [TaskPriority.LOW]: {
      color: '#6B7280',
      bgColor: 'rgba(107, 114, 128, 0.1)',
      icon: 'pi-chevron-down',
      label: t('tasks.priority_low')
    },
    [TaskPriority.MEDIUM]: {
      color: '#3B82F6',
      bgColor: 'rgba(59, 130, 246, 0.1)',
      icon: 'pi-minus',
      label: t('tasks.priority_medium')
    },
    [TaskPriority.HIGH]: {
      color: '#F59E0B',
      bgColor: 'rgba(245, 158, 11, 0.1)',
      icon: 'pi-chevron-up',
      label: t('tasks.priority_high')
    },
    [TaskPriority.URGENT]: {
      color: '#EF4444',
      bgColor: 'rgba(239, 68, 68, 0.1)',
      icon: 'pi-exclamation-triangle',
      label: t('tasks.priority_urgent')
    }
  }
  return configs[props.task.priority]
})


const isCompleted = computed(() => props.task.status === TaskStatus.COMPLETED)

const isOverdue = computed(() => {
  if (!props.task.dueDate || isCompleted.value) return false
  return new Date(props.task.dueDate) < new Date()
})

const formattedDueDate = computed(() => {
  if (!props.task.dueDate) return null
  
  const dueDate = new Date(props.task.dueDate)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const tomorrow = new Date(today)
  tomorrow.setDate(tomorrow.getDate() + 1)
  
  const dueDateStart = new Date(dueDate)
  dueDateStart.setHours(0, 0, 0, 0)
  
  if (dueDateStart.getTime() === today.getTime()) {
    return t('tasks.today_tasks')
  } else if (dueDateStart.getTime() === tomorrow.getTime()) {
    return t('tasks.tomorrow_tasks')
  }
  
  return new Intl.DateTimeFormat(i18n.locale.value === 'ru' ? 'ru-RU' : 'en-US').format(dueDate)
})

function handleClick() {
  emit('click', props.task)
}

function handleToggleComplete(event: Event) {
  event.stopPropagation()
  emit('toggle-complete', props.task)
}
</script>

<template>
  <div 
    class="task-card"
    :class="{ 
      'task-card--completed': isCompleted, 
      'task-card--overdue': isOverdue,
      'task-card--selected': selected
    }"
    @click="handleClick"
  >
    <!-- Priority Indicator -->
    <div 
      class="task-card__priority-indicator"
      :style="{ backgroundColor: priorityConfig.color }"
    />

    <div class="task-card__content">
      <!-- Header -->
      <div class="task-card__header">
        <div class="task-card__checkbox">
          <Checkbox
            :model-value="isCompleted"
            :binary="true"
            @click="handleToggleComplete"
          />
        </div>

        <div class="task-card__main">
          <h3 class="task-card__title" :class="{ 'task-card__title--completed': isCompleted }">
            {{ task.title }}
          </h3>

          <p v-if="task.description" class="task-card__description">
            {{ task.description }}
          </p>
        </div>
      </div>

      <!-- Footer -->
      <div class="task-card__footer">
        <div class="task-card__meta">
          <!-- Priority Badge -->
          <div 
            class="task-card__badge task-card__priority"
            :style="{ 
              color: priorityConfig.color,
              backgroundColor: priorityConfig.bgColor
            }"
          >
            <i :class="['pi', priorityConfig.icon]" />
            <span>{{ priorityConfig.label }}</span>
          </div>

          <!-- Due Date -->
          <div v-if="formattedDueDate" class="task-card__due-date" :class="{ 'task-card__due-date--overdue': isOverdue }">
            <i class="pi pi-calendar" />
            <span>{{ formattedDueDate }}</span>
          </div>

          <!-- Subtasks Count -->
          <div v-if="task.subtasks && task.subtasks.length > 0" class="task-card__subtasks">
            <i class="pi pi-list" />
            <span>{{ task.subtasks.filter(s => s.isCompleted).length }}/{{ task.subtasks.length }}</span>
          </div>
        </div>

        <!-- Tags -->
        <div v-if="task.tags && task.tags.length > 0" class="task-card__tags">
          <Chip
            v-for="tag in task.tags.slice(0, 3)"
            :key="tag.id"
            :label="tag.name"
            :style="{ 
              backgroundColor: `${tag.color}20`,
              color: tag.color,
              border: `1px solid ${tag.color}40`
            }"
            class="task-card__tag"
          />
          <span v-if="task.tags.length > 3" class="task-card__more-tags">
            +{{ task.tags.length - 3 }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.task-card {
  position: relative;
  display: flex;
  background: white;
  border-radius: 16px;
  padding: 1.25rem;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  border: 2px solid transparent;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.task-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  border-color: rgba(102, 126, 234, 0.2);
}

.task-card--selected {
  border-color: #667eea;
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
  transform: translateY(-2px);
}

.task-card--completed {
  opacity: 0.7;
}

.task-card--overdue {
  border-left: 3px solid #ef4444;
}

/* Priority Indicator */
.task-card__priority-indicator {
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  opacity: 0;
  transition: opacity 0.25s ease;
}

.task-card:hover .task-card__priority-indicator {
  opacity: 1;
}

.task-card--selected .task-card__priority-indicator {
  opacity: 1;
}

/* Content */
.task-card__content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  min-width: 0;
}

/* Header */
.task-card__header {
  display: flex;
  gap: 0.875rem;
  align-items: flex-start;
}

.task-card__checkbox {
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.task-card__checkbox :deep(.p-checkbox) {
  width: 20px;
  height: 20px;
}

.task-card__checkbox :deep(.p-checkbox .p-checkbox-box) {
  width: 20px;
  height: 20px;
  border-radius: 6px;
  border: 2px solid #cbd5e0;
  transition: all 0.2s ease;
}

.task-card__checkbox :deep(.p-checkbox:hover .p-checkbox-box) {
  border-color: #667eea;
}

.task-card__checkbox :deep(.p-checkbox .p-checkbox-box.p-highlight) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: #667eea;
}

.task-card__main {
  flex: 1;
  min-width: 0;
}

.task-card__title {
  font-size: 1rem;
  font-weight: 600;
  color: #1a202c;
  margin: 0 0 0.375rem 0;
  line-height: 1.4;
  transition: all 0.2s ease;
}

.task-card__title--completed {
  text-decoration: line-through;
  color: #a0aec0;
}

.task-card__description {
  font-size: 0.875rem;
  color: #718096;
  margin: 0;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Footer */
.task-card__footer {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding-left: 2rem;
}

.task-card__meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.task-card__badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 8px;
  font-size: 0.8125rem;
  font-weight: 500;
  transition: all 0.2s ease;
}

.task-card__badge i {
  font-size: 0.875rem;
}

.task-card__due-date {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.8125rem;
  color: #718096;
  font-weight: 500;
}

.task-card__due-date--overdue {
  color: #ef4444;
  font-weight: 600;
}

.task-card__due-date i {
  font-size: 0.875rem;
}

.task-card__subtasks {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.8125rem;
  color: #718096;
  font-weight: 500;
}

.task-card__subtasks i {
  font-size: 0.875rem;
}

/* Tags */
.task-card__tags {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.task-card__tag {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem 0.625rem;
  border-radius: 6px;
}

.task-card__tag :deep(.p-chip-text) {
  font-size: 0.75rem;
  font-weight: 600;
}

.task-card__more-tags {
  font-size: 0.75rem;
  color: #a0aec0;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  background: #f7fafc;
  border-radius: 6px;
}

/* Responsive */
@media (max-width: 768px) {
  .task-card {
    padding: 1rem;
  }

  .task-card__footer {
    padding-left: 1.75rem;
  }

  .task-card__meta {
    gap: 0.5rem;
  }

  .task-card__badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
  }
}

@media (max-width: 768px) {
  .task-card {
    padding: 0.75rem 1rem;
    flex-direction: row;
    align-items: center;
  }
  .task-card__content {
    gap: 0.375rem;
    padding-left: 0.75rem;
  }
  .task-card__header {
    gap: 0.75rem;
    align-items: center;
  }
  .task-card__title {
    font-size: 0.9375rem;
    margin-bottom: 0.125rem;
  }
  .task-card__description {
    display: none;
  }
  .task-card__footer {
    padding-left: 0;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }
  .task-card__priority-indicator {
    display: none;
  }
  .task-card__checkbox {
    margin-top: 0;
  }
  .task-card__tags {
    display: none; /* Hide tags on smallest view to save space */
  }
}
</style>

