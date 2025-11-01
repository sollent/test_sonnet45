<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/stores/task.store'
import type { TaskFiltersState } from '@/types/task.types'
import { TaskPriority } from '@/types/task.types'

const { t } = useI18n()
const taskStore = useTaskStore()

interface QuickFilter {
  id: string
  label: string
  icon: string
  color: string
  filters: Partial<TaskFiltersState>
}

const quickFilters: QuickFilter[] = [
  {
    id: 'urgent',
    label: 'Срочные задачи',
    icon: 'pi pi-bolt',
    color: '#dc2626',
    filters: {
      priorities: [TaskPriority.URGENT]
    }
  },
  {
    id: 'today',
    label: 'На сегодня',
    icon: 'pi pi-calendar',
    color: '#8b5cf6',
    filters: {
      // This will be handled by view filter
    }
  },
  {
    id: 'overdue',
    label: 'Просроченные',
    icon: 'pi pi-exclamation-triangle',
    color: '#ef4444',
    filters: {
      // This will be handled by view filter
    }
  },
  {
    id: 'my-tasks',
    label: 'Мои задачи',
    icon: 'pi pi-user',
    color: '#3b82f6',
    filters: {
      // This will be handled by assignee filter when implemented
    }
  },
  {
    id: 'completed-week',
    label: 'Завершенные за неделю',
    icon: 'pi pi-check-circle',
    color: '#10b981',
    filters: {
      completed: true,
      // Date range will be calculated
    }
  }
]

const activeFilter = ref<string | null>(null)

function applyQuickFilter(filter: QuickFilter) {
  if (activeFilter.value === filter.id) {
    // Deactivate filter
    activeFilter.value = null
    taskStore.clearFilters()
  } else {
    // Activate filter
    activeFilter.value = filter.id
    
    // Special handling for date-based filters
    if (filter.id === 'completed-week') {
      const today = new Date()
      const weekAgo = new Date(today)
      weekAgo.setDate(today.getDate() - 7)
      
      taskStore.setFilters({
        ...filter.filters,
        dateFrom: formatDateForApi(weekAgo),
        dateTo: formatDateForApi(today)
      } as TaskFiltersState)
    } else if (filter.id === 'today') {
      // Switch to today view
      taskStore.setCurrentView('today')
    } else if (filter.id === 'overdue') {
      // Switch to overdue view
      taskStore.setCurrentView('overdue')
    } else {
      // Apply regular filters
      taskStore.setFilters({
        tags: [],
        completed: null,
        dateFrom: null,
        dateTo: null,
        priorities: [],
        statuses: [],
        ...filter.filters
      } as TaskFiltersState)
    }
  }
}

function formatDateForApi(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}
</script>

<template>
  <div class="quick-filters">
    <div class="quick-filters__list">
      <button
        v-for="filter in quickFilters"
        :key="filter.id"
        :class="['quick-filter', { 'quick-filter--active': activeFilter === filter.id }]"
        @click="applyQuickFilter(filter)"
      >
        <span class="quick-filter__icon" :style="{ color: filter.color }">
          <i :class="filter.icon"></i>
        </span>
        <span class="quick-filter__label">
          {{ filter.label }}
        </span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.quick-filters {
  margin-bottom: 0.75rem;
}

.quick-filters__list {
  display: flex;
  gap: 0.5rem;
  overflow-x: auto;
  padding: 0.25rem 0;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.quick-filters__list::-webkit-scrollbar {
  display: none;
}

.quick-filter {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: white;
  border: 1.5px solid #e9ecef;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #495057;
  white-space: nowrap;
  cursor: pointer;
  transition: all 0.15s ease;
  flex-shrink: 0;
}

.quick-filter:hover {
  background: #f8f9fa;
  border-color: #dee2e6;
  transform: scale(1.02);
}

.quick-filter--active {
  background: white;
  border-color: #6366f1;
  color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.quick-filter__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.125rem;
  height: 1.125rem;
  font-size: 1rem;
  flex-shrink: 0;
}

.quick-filter__label {
  line-height: 1.3;
  font-size: 0.875rem;
}

.quick-filter--active .quick-filter__label {
  font-weight: 600;
}

/* Mobile */
@media (max-width: 768px) {
  .quick-filters {
    margin-bottom: 0.5rem;
  }

  .quick-filters__list {
    gap: 0.375rem;
  }

  .quick-filter {
    padding: 0.5rem 0.875rem;
    font-size: 0.813rem;
    border-radius: 10px;
  }

  .quick-filter__icon {
    width: 1rem;
    height: 1rem;
    font-size: 0.938rem;
  }

  .quick-filter__label {
    font-size: 0.813rem;
  }
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .quick-filter {
    background: var(--p-surface-800);
    border-color: var(--p-surface-700);
    color: var(--p-text-color);
  }

  .quick-filter:hover {
    background: var(--p-surface-700);
    border-color: var(--p-surface-600);
  }

  .quick-filter--active {
    background: var(--p-surface-800);
    border-color: #6366f1;
    color: #6366f1;
  }
}
</style>
