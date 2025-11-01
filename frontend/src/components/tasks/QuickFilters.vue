<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

const { t } = useI18n()
const router = useRouter()

interface QuickFilter {
  id: string
  label: string
  icon: string
  view: string
  count?: number
}

const quickFilters: QuickFilter[] = [
  {
    id: 'today',
    label: 'На сегодня',
    icon: 'pi pi-calendar',
    view: 'today'
  },
  {
    id: 'urgent',
    label: 'Срочные',
    icon: 'pi pi-bolt',
    view: 'urgent',
    count: 12
  },
  {
    id: 'overdue',
    label: 'Просроченные',
    icon: 'pi pi-clock',
    view: 'overdue',
    count: 3
  },
  {
    id: 'in-progress',
    label: 'В процессе',
    icon: 'pi pi-play',
    view: 'in-progress'
  }
]

const activeFilter = ref<string>('today')

const emit = defineEmits<{
  (e: 'filter-change', view: string): void
}>()

function selectFilter(filter: QuickFilter) {
  activeFilter.value = filter.id
  emit('filter-change', filter.view)
}
</script>

<template>
  <div class="quick-filters">
    <button
      v-for="filter in quickFilters"
      :key="filter.id"
      :class="['quick-filter-btn', { active: activeFilter === filter.id }]"
      @click="selectFilter(filter)"
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
  gap: 0.625rem;
  padding: 0.5rem 0;
}

.quick-filter-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.125rem;
  background: white;
  border: 1.5px solid #e9ecef;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #495057;
  white-space: nowrap;
  cursor: pointer;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.quick-filter-btn i {
  font-size: 1rem;
  color: inherit;
}

.quick-filter-btn:hover {
  background: #f8f9fa;
  border-color: #dee2e6;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.quick-filter-btn.active {
  background: #6366f1;
  border-color: #6366f1;
  color: white;
  font-weight: 600;
  box-shadow: 0 2px 12px rgba(99, 102, 241, 0.3);
}

.quick-filter-btn.active i {
  color: white;
}

.count-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.375rem;
  height: 1.375rem;
  padding: 0 0.375rem;
  background: rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 700;
}

.quick-filter-btn.active .count-badge {
  background: rgba(255, 255, 255, 0.25);
  color: white;
}

/* Mobile */
@media (max-width: 768px) {
  .quick-filters {
    flex-direction: column;
    gap: 0.625rem;
    padding: 0.5rem 0;
  }

  .quick-filter-btn {
    width: 100%;
    justify-content: flex-start;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    border-radius: 12px;
  }

  .quick-filter-btn i {
    font-size: 1rem;
  }

  .count-badge {
    margin-left: auto;
    min-width: 1.5rem;
    height: 1.5rem;
    font-size: 0.75rem;
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
