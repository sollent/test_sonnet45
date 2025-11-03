<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useTaskStore } from '@/stores/task.store'

const { t } = useI18n()
const router = useRouter()
const taskStore = useTaskStore()

interface QuickFilter {
  id: string
  label: string
  icon: string
  view: string
  priority?: string[]
  status?: string[]
  count?: number
}

const quickFilters = computed(() => [
  {
    id: 'today',
    label: t('quick_filters.today'),
    icon: 'pi pi-calendar',
    view: 'today'
  },
  {
    id: 'urgent',
    label: t('quick_filters.urgent'),
    icon: 'pi pi-bolt',
    view: 'all',
    priority: ['high', 'urgent']
  },
  {
    id: 'overdue',
    label: t('quick_filters.overdue'),
    icon: 'pi pi-clock',
    view: 'overdue'
  },
  {
    id: 'in-progress',
    label: t('quick_filters.in_progress'),
    icon: 'pi pi-play',
    view: 'all',
    status: ['in_progress']
  }
])

const activeFilters = ref<string[]>([])

const emit = defineEmits<{
  (e: 'filters-change', filters: QuickFilter[]): void
}>()

function toggleFilter(filter: QuickFilter) {
  const index = activeFilters.value.indexOf(filter.id)

  if (index > -1) {
    // Remove filter (отжать кнопку)
    activeFilters.value.splice(index, 1)
  } else {
    // Add filter (нажать кнопку)
    activeFilters.value.push(filter.id)
  }

  // Emit all active filters
  const activeFilterObjects = quickFilters.value.filter(f => activeFilters.value.includes(f.id))
  emit('filters-change', activeFilterObjects)
}

function isActive(filterId: string): boolean {
  return activeFilters.value.includes(filterId)
}
</script>

<template>
  <div class="quick-filters">
    <button
      v-for="filter in quickFilters"
      :key="filter.id"
      :class="['quick-filter-btn', { active: isActive(filter.id) }]"
      @click="toggleFilter(filter)"
    >
      <i :class="filter.icon"></i>
      <span>{{ filter.label }}</span>
      <span v-if="filter.count" class="count-badge">{{ filter.count }}</span>
    </button>
  </div>
</template>

<style scoped>
.quick-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding: 0;
}

.quick-filter-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 0.875rem;
  background: white;
  border: 1.5px solid #e9ecef;
  border-radius: 20px;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #495057;
  white-space: nowrap;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  flex-shrink: 0;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.quick-filter-btn i {
  font-size: 0.875rem;
  color: inherit;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 1em;
  height: 1em;
}

.quick-filter-btn:hover {
  background: #f8f9fa;
  border-color: #dee2e6;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.quick-filter-btn:active {
  transform: translateY(0);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.quick-filter-btn.active {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  border-color: transparent;
  color: white;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}

.quick-filter-btn.active i {
  color: white;
}

.count-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.125rem;
  height: 1.125rem;
  padding: 0 0.25rem;
  background: rgba(0, 0, 0, 0.08);
  border-radius: 6px;
  font-size: 0.6875rem;
  font-weight: 700;
}

.quick-filter-btn.active .count-badge {
  background: rgba(255, 255, 255, 0.25);
  color: white;
}

/* Mobile - компактный wrap дизайн */
@media (max-width: 768px) {
  .quick-filters {
    gap: 0.5rem;
    padding: 0;
  }

  .quick-filter-btn {
    padding: 0.5rem 0.875rem;
    font-size: 0.8125rem;
    border-radius: 16px;
    gap: 0.375rem;
  }

  .quick-filter-btn i {
    font-size: 0.875rem;
  }

  .count-badge {
    min-width: 1.125rem;
    height: 1.125rem;
    font-size: 0.6875rem;
  }
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .quick-filter-btn {
    background: var(--p-surface-800);
    border-color: var(--p-surface-700);
    color: var(--p-text-color);
  }

  .quick-filter-btn:hover {
    background: var(--p-surface-700);
    border-color: var(--p-surface-600);
  }

  .quick-filter-btn.active {
    background: #6366f1;
    border-color: #6366f1;
    color: white;
  }
}
</style>
