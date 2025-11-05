<script setup lang="ts">
import { ref, computed, defineEmits, defineProps } from 'vue'
import { useI18n } from 'vue-i18n'
import { format } from 'date-fns'
import Checkbox from 'primevue/checkbox'
import Badge from 'primevue/badge'
import Tag from 'primevue/tag'
import type { Task } from '@/types/task.types'

interface Props {
  tasks: Task[]
  expanded?: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'toggle-complete': [task: Task]
  'task-click': [task: Task]
}>()

const { t } = useI18n()
const isExpanded = ref(props.expanded ?? true)

// Calculate tag statistics
const tagStats = computed(() => {
  const stats = new Map<string, number>()

  props.tasks.forEach(task => {
    if (task.tags && task.tags.length > 0) {
      task.tags.forEach(tag => {
        const tagName = typeof tag === 'string' ? tag : tag.name
        stats.set(tagName, (stats.get(tagName) || 0) + 1)
      })
    }
  })

  // Sort by count and take top tags
  return Array.from(stats.entries())
    .sort((a, b) => b[1] - a[1])
    .slice(0, 3)
})

// Format time for display
function formatTime(dateString: string | null | undefined): string {
  if (!dateString) return ''
  try {
    return format(new Date(dateString), 'HH:mm')
  } catch {
    return ''
  }
}

// Get priority color
function getPriorityColor(priority: any): string {
  const priorityValue = typeof priority === 'string' ? priority : priority?.value

  switch(priorityValue?.toLowerCase()) {
    case 'urgent': return '#ef4444'  // Red
    case 'high': return '#f97316'    // Orange
    case 'medium': return '#eab308'  // Yellow
    case 'low': return '#10b981'     // Green
    default: return '#6b7280'        // Gray
  }
}

function toggleComplete(task: Task) {
  emit('toggle-complete', task)
}

function handleTaskClick(task: Task) {
  emit('task-click', task)
}
</script>

<template>
  <div class="completed-tasks-container">
    <!-- Header with count and expand/collapse -->
    <div class="completed-header" @click="isExpanded = !isExpanded">
      <div class="header-left">
        <i
          :class="['pi', isExpanded ? 'pi-chevron-down' : 'pi-chevron-right']"
          style="font-size: 0.75rem; margin-right: 0.5rem;"
        />
        <span class="completed-label">✅ {{ t('tasks.completed') }}</span>
        <Badge :value="tasks.length" severity="success" />
      </div>
    </div>

    <!-- Completed tasks list -->
    <transition name="expand">
      <div v-if="isExpanded" class="completed-tasks-list">
        <div
          v-for="task in tasks"
          :key="task.id"
          class="completed-task-item"
        >
          <!-- Checkbox -->
          <div class="task-checkbox-wrapper">
            <Checkbox
              :model-value="true"
              binary
              class="completed-checkbox"
              @update:modelValue="toggleComplete(task)"
            />
          </div>

          <!-- Task content -->
          <div
            class="task-content"
            @click="handleTaskClick(task)"
          >
            <span class="task-title">{{ task.title }}</span>

            <!-- Priority indicator -->
            <div
              v-if="task.priority"
              class="priority-dot"
              :style="{ backgroundColor: getPriorityColor(task.priority) }"
            />
          </div>

          <!-- Time -->
          <span class="task-time">
            {{ formatTime(task.completedAt || task.dueDate) }}
          </span>
        </div>

        <!-- Tag statistics -->
        <div v-if="tagStats.length > 0" class="tag-stats">
          <div
            v-for="[tagName, count] in tagStats"
            :key="tagName"
            class="tag-stat"
          >
            <span class="tag-name">{{ tagName }}:</span>
            <span class="tag-count">{{ count }}</span>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.completed-tasks-container {
  background: white;
  border-radius: 12px;
  padding: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  margin-top: 0.5rem;
}

.completed-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  user-select: none;
  padding: 0.25rem 0;
  transition: opacity 0.2s;
}

.completed-header:hover {
  opacity: 0.8;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.completed-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #10b981;
}

.completed-tasks-list {
  margin-top: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.completed-task-item {
  display: flex;
  align-items: center;
  padding: 0.5rem;
  border-radius: 8px;
  transition: all 0.2s;
  min-height: 2.5rem;
  background: #f8f9fa;
}

.completed-task-item:hover {
  background: #f1f3f5;
}

.task-checkbox-wrapper {
  margin-right: 0.75rem;
}

.completed-checkbox :deep(.p-checkbox-box) {
  width: 1.25rem;
  height: 1.25rem;
  background: #10b981 !important;
  border-color: #10b981 !important;
}

.completed-checkbox :deep(.p-checkbox-icon) {
  font-size: 0.75rem;
}

.task-content {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  min-width: 0;
}

.task-title {
  font-size: 0.875rem;
  color: #6b7280;
  text-decoration: line-through;
  text-overflow: ellipsis;
  overflow: hidden;
  white-space: nowrap;
  flex: 1;
}

.priority-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  flex-shrink: 0;
}

.task-time {
  font-size: 0.75rem;
  color: #9ca3af;
  margin-left: auto;
  flex-shrink: 0;
}

/* Tag statistics */
.tag-stats {
  display: flex;
  gap: 1.5rem;
  padding: 0.75rem 0.5rem 0.25rem;
  margin-top: 0.75rem;
  border-top: 1px solid #e5e7eb;
  font-size: 0.8125rem;
}

.tag-stat {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.tag-name {
  color: #6b7280;
  font-weight: 500;
}

.tag-count {
  color: #10b981;
  font-weight: 600;
}

/* Expand/collapse animation */
.expand-enter-active,
.expand-leave-active {
  transition: all 0.3s ease;
  transform-origin: top;
}

.expand-enter-from {
  transform: scaleY(0);
  opacity: 0;
}

.expand-leave-to {
  transform: scaleY(0);
  opacity: 0;
}

/* Mobile responsiveness */
@media (max-width: 640px) {
  .completed-tasks-container {
    padding: 0.75rem;
  }

  .completed-task-item {
    padding: 0.375rem;
  }

  .task-title {
    font-size: 0.8125rem;
  }

  .tag-stats {
    gap: 1rem;
    font-size: 0.75rem;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .completed-tasks-container {
    background: #1f2937;
  }

  .completed-task-item {
    background: #111827;
  }

  .completed-task-item:hover {
    background: #1f2937;
  }

  .task-title {
    color: #9ca3af;
  }

  .tag-stats {
    border-top-color: #374151;
  }

  .tag-name {
    color: #9ca3af;
  }
}
</style>