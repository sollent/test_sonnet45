<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/stores/task.store'
import { TaskPriority, TaskStatus, type TaskFiltersState } from '@/types/task.types'
import MultiSelect from 'primevue/multiselect'
import Calendar from 'primevue/calendar'

const { t } = useI18n()
const taskStore = useTaskStore()

// UI State
const isExpanded = ref(false)
const taskTypeFilter = ref<'all' | 'active' | 'completed'>('all')
const dateFrom = ref<Date | null>(null)
const dateTo = ref<Date | null>(null)

// Popular tags for quick access
const popularTags = computed(() => taskStore.tags.slice(0, 5))

// Local filter state
const localFilters = ref<TaskFiltersState>({
  tags: [],
  completed: null,
  dateFrom: null,
  dateTo: null,
  priorities: [],
  statuses: []
})

// Priority options
const priorityOptions = [
  { label: 'Низкий', value: TaskPriority.LOW, color: '#10b981' },
  { label: 'Средний', value: TaskPriority.MEDIUM, color: '#f59e0b' },
  { label: 'Высокий', value: TaskPriority.HIGH, color: '#ef4444' },
  { label: 'Срочный', value: TaskPriority.URGENT, color: '#dc2626' }
]

// Status options
const statusOptions = [
  { label: 'В ожидании', value: TaskStatus.PENDING, color: '#6b7280' },
  { label: 'В процессе', value: TaskStatus.IN_PROGRESS, color: '#3b82f6' },
  { label: 'Завершена', value: TaskStatus.COMPLETED, color: '#10b981' },
  { label: 'Отменена', value: TaskStatus.CANCELLED, color: '#ef4444' }
]

// Count active filters
const activeFiltersCount = computed(() => {
  let count = 0
  if (localFilters.value.tags.length > 0) count++
  if (localFilters.value.completed !== null) count++
  if (localFilters.value.dateFrom || localFilters.value.dateTo) count++
  if (localFilters.value.priorities.length > 0) count++
  if (localFilters.value.statuses.length > 0) count++
  return count
})

// Format date for API
function formatDateForApi(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// Apply filters
function applyFilters() {
  taskStore.setFilters(localFilters.value)
}

// Clear filters
function clearFilters() {
  localFilters.value = {
    tags: [],
    completed: null,
    dateFrom: null,
    dateTo: null,
    priorities: [],
    statuses: []
  }
  dateFrom.value = null
  dateTo.value = null
  taskTypeFilter.value = 'all'
  taskStore.clearFilters()
}

// Toggle priority
function togglePriority(priority: TaskPriority) {
  const index = localFilters.value.priorities.indexOf(priority)
  if (index > -1) {
    localFilters.value.priorities.splice(index, 1)
  } else {
    localFilters.value.priorities.push(priority)
  }
  applyFilters()
}

// Toggle status
function toggleStatus(status: TaskStatus) {
  const index = localFilters.value.statuses.indexOf(status)
  if (index > -1) {
    localFilters.value.statuses.splice(index, 1)
  } else {
    localFilters.value.statuses.push(status)
  }
  applyFilters()
}

// Toggle tag
function toggleTag(tagId: number) {
  const index = localFilters.value.tags.indexOf(tagId)
  if (index > -1) {
    localFilters.value.tags.splice(index, 1)
  } else {
    localFilters.value.tags.push(tagId)
  }
  applyFilters()
}

// Handle date change
function handleDateChange() {
  if (dateFrom.value) {
    localFilters.value.dateFrom = formatDateForApi(dateFrom.value)
  } else {
    localFilters.value.dateFrom = null
  }
  
  if (dateTo.value) {
    localFilters.value.dateTo = formatDateForApi(dateTo.value)
  } else {
    localFilters.value.dateTo = null
  }
  
  applyFilters()
}

// Watch task type filter
watch(taskTypeFilter, (newValue) => {
  if (newValue === 'completed') {
    localFilters.value.completed = true
  } else if (newValue === 'active') {
    localFilters.value.completed = false
  } else {
    localFilters.value.completed = null
  }
  applyFilters()
})
</script>

<template>
  <div class="filters-container">
    <!-- Compact Header -->
    <button @click="isExpanded = !isExpanded" class="filters-toggle">
      <div class="toggle-left">
        <i class="pi pi-filter"></i>
        <span>{{ t('tasks.filters') }}</span>
        <span v-if="activeFiltersCount > 0" class="count-badge">{{ activeFiltersCount }}</span>
      </div>
      <i :class="['pi', isExpanded ? 'pi-chevron-up' : 'pi-chevron-down']" class="chevron"></i>
    </button>

    <!-- Compact Filters Panel -->
    <Transition name="slide">
      <div v-show="isExpanded" class="filters-content">
        <!-- Grid Layout for Compact View -->
        <div class="filters-grid">
          <!-- Тип задач -->
          <div class="filter-block">
            <div class="block-label">
              <i class="pi pi-list"></i>
              {{ t('tasks.task_type') }}
            </div>
            <div class="btn-group">
              <button :class="['btn-option', { active: taskTypeFilter === 'all' }]" @click="taskTypeFilter = 'all'">
                {{ t('tasks.all_tasks') }}
              </button>
              <button :class="['btn-option', { active: taskTypeFilter === 'active' }]" @click="taskTypeFilter = 'active'">
                {{ t('tasks.active') }}
              </button>
              <button :class="['btn-option', { active: taskTypeFilter === 'completed' }]" @click="taskTypeFilter = 'completed'">
                {{ t('tasks.completed') }}
              </button>
            </div>
          </div>

          <!-- Приоритет -->
          <div class="filter-block">
            <div class="block-label">
              <i class="pi pi-flag"></i>
              {{ t('tasks.priority') }}
            </div>
            <div class="options-wrap">
              <button
                v-for="priority in priorityOptions"
                :key="priority.value"
                :class="['option-pill', { active: localFilters.priorities.includes(priority.value) }]"
                @click="togglePriority(priority.value)"
              >
                <span class="dot" :style="{ background: priority.color }"></span>
                {{ priority.label }}
              </button>
            </div>
          </div>

          <!-- Статус -->
          <div class="filter-block">
            <div class="block-label">
              <i class="pi pi-info-circle"></i>
              {{ t('tasks.status_execution') }}
            </div>
            <div class="options-wrap">
              <button
                v-for="status in statusOptions"
                :key="status.value"
                :class="['option-pill', { active: localFilters.statuses.includes(status.value) }]"
                @click="toggleStatus(status.value)"
              >
                <span class="dot" :style="{ background: status.color }"></span>
                {{ status.label }}
              </button>
            </div>
          </div>

          <!-- Теги -->
          <div class="filter-block">
            <div class="block-label">
              <i class="pi pi-tags"></i>
              {{ t('tasks.tags') }}
            </div>
            <div class="tags-row">
              <button
                v-for="tag in popularTags"
                :key="tag.id"
                :class="['tag-pill', { active: localFilters.tags.includes(tag.id) }]"
                @click="toggleTag(tag.id)"
              >
                <span class="tag-avatar" :style="{ background: tag.color }">
                  {{ tag.name.charAt(0).toUpperCase() }}
                </span>
                {{ tag.name }}
              </button>
            </div>
            <MultiSelect
              v-model="localFilters.tags"
              :options="taskStore.tags"
              optionLabel="name"
              optionValue="id"
              :placeholder="t('tasks.select_tags')"
              class="tags-select"
              @change="applyFilters"
            />
          </div>

          <!-- Период -->
          <div class="filter-block">
            <div class="block-label">
              <i class="pi pi-calendar"></i>
              {{ t('tasks.period') }}
            </div>
            <div class="date-row">
              <Calendar
                v-model="dateFrom"
                :placeholder="t('tasks.date_from')"
                dateFormat="dd.mm.yy"
                showIcon
                class="compact-date"
                @date-select="handleDateChange"
                @clear-click="handleDateChange"
              />
              <span class="dash">—</span>
              <Calendar
                v-model="dateTo"
                :placeholder="t('tasks.date_to')"
                dateFormat="dd.mm.yy"
                showIcon
                class="compact-date"
                @date-select="handleDateChange"
                @clear-click="handleDateChange"
              />
            </div>
          </div>
        </div>

        <!-- Compact Actions -->
        <div class="actions-bar">
          <button @click="applyFilters" class="apply-btn">
            <i class="pi pi-check"></i>
            {{ t('tasks.apply_filters') }}
          </button>
          <button @click="clearFilters" :disabled="activeFiltersCount === 0" class="clear-btn">
            <i class="pi pi-times"></i>
            {{ t('tasks.clear_all') }}
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.filters-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
  margin-bottom: 1rem;
}

/* Compact Toggle */
.filters-toggle {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  background: transparent;
  border: none;
  cursor: pointer;
  transition: background 0.15s ease;
}

.filters-toggle:hover {
  background: #fafbfc;
}

.toggle-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  font-size: 0.875rem;
  color: #495057;
}

.toggle-left i {
  color: #6366f1;
  font-size: 0.938rem;
}

.count-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.25rem;
  height: 1.25rem;
  padding: 0 0.313rem;
  background: #6366f1;
  color: white;
  border-radius: 8px;
  font-size: 0.688rem;
  font-weight: 700;
}

.chevron {
  color: #adb5bd;
  font-size: 0.813rem;
  transition: transform 0.2s ease;
}

/* Compact Content */
.filters-content {
  padding: 0 1rem 0.875rem 1rem;
  border-top: 1px solid #f1f3f5;
}

/* Compact Grid */
.filters-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.875rem;
  padding: 0.875rem 0;
}

.filter-block {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.block-label {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.688rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  color: #868e96;
}

.block-label i {
  font-size: 0.75rem;
  color: #adb5bd;
}

/* Segmented Control - Compact */
.btn-group {
  display: flex;
  background: #f8f9fa;
  border-radius: 8px;
  padding: 0.188rem;
  gap: 0.188rem;
}

.btn-option {
  flex: 1;
  padding: 0.375rem 0.625rem;
  background: transparent;
  border: none;
  border-radius: 6px;
  font-size: 0.813rem;
  font-weight: 500;
  color: #6c757d;
  cursor: pointer;
  transition: all 0.12s ease;
  white-space: nowrap;
}

.btn-option.active {
  background: white;
  color: #6366f1;
  font-weight: 600;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

/* Compact Pills */
.options-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
}

.option-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  background: white;
  border: 1.5px solid #e9ecef;
  border-radius: 10px;
  font-size: 0.813rem;
  font-weight: 500;
  color: #495057;
  cursor: pointer;
  transition: all 0.12s ease;
}

.dot {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  flex-shrink: 0;
}

.option-pill:hover {
  background: #f8f9fa;
  transform: translateY(-1px);
}

.option-pill.active {
  background: #6366f1;
  border-color: #6366f1;
  color: white;
  font-weight: 600;
  box-shadow: 0 2px 6px rgba(99, 102, 241, 0.25);
}

.option-pill.active .dot {
  background: white !important;
}

/* Tags Compact */
.tags-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
  margin-bottom: 0.5rem;
}

.tag-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.313rem 0.688rem;
  background: white;
  border: 1.5px solid #e9ecef;
  border-radius: 10px;
  font-size: 0.813rem;
  font-weight: 500;
  color: #495057;
  cursor: pointer;
  transition: all 0.12s ease;
}

.tag-avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.125rem;
  height: 1.125rem;
  border-radius: 5px;
  font-size: 0.625rem;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
}

.tag-pill:hover {
  background: #f8f9fa;
  transform: translateY(-1px);
}

.tag-pill.active {
  background: #6366f1;
  border-color: #6366f1;
  color: white;
}

.tag-pill.active .tag-avatar {
  background: rgba(255, 255, 255, 0.25) !important;
}

.tags-select :deep(.p-multiselect) {
  width: 100%;
  background: #f8f9fa;
  border: 1.5px solid #e9ecef;
  border-radius: 8px;
  min-height: 36px;
  font-size: 0.813rem;
}

.tags-select :deep(.p-multiselect:hover) {
  border-color: #dee2e6;
}

.tags-select :deep(.p-multiselect.p-focus) {
  background: white;
  border-color: #6366f1;
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.08);
}

/* Date Inputs Compact */
.date-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.compact-date {
  flex: 1;
}

.compact-date :deep(.p-calendar) {
  width: 100%;
}

.compact-date :deep(.p-inputtext) {
  width: 100%;
  padding: 0.5rem 0.75rem;
  background: #f8f9fa;
  border: 1.5px solid #e9ecef;
  border-radius: 8px;
  font-size: 0.813rem;
  color: #495057;
  transition: all 0.12s ease;
}

.compact-date :deep(.p-inputtext:hover) {
  border-color: #dee2e6;
}

.compact-date :deep(.p-inputtext:focus) {
  background: white;
  border-color: #6366f1;
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.08);
}

.compact-date :deep(.p-datepicker-trigger) {
  color: #6366f1;
  font-size: 0.875rem;
}

.dash {
  color: #adb5bd;
  font-weight: 500;
  font-size: 0.813rem;
}

/* Compact Actions */
.actions-bar {
  display: flex;
  gap: 0.5rem;
  padding-top: 0.75rem;
  border-top: 1px solid #f1f3f5;
}

.apply-btn,
.clear-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 8px;
  font-size: 0.813rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.apply-btn {
  flex: 1;
  background: #6366f1;
  color: white;
}

.apply-btn:hover {
  background: #5558e3;
  transform: translateY(-1px);
  box-shadow: 0 3px 8px rgba(99, 102, 241, 0.25);
}

.clear-btn {
  background: white;
  color: #dc3545;
  border: 1.5px solid #e9ecef;
}

.clear-btn:hover:not(:disabled) {
  background: #fff5f5;
  border-color: #dc3545;
}

.clear-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

/* Animations */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.25s ease;
  overflow: hidden;
}

.slide-enter-from,
.slide-leave-to {
  opacity: 0;
  max-height: 0;
  padding-top: 0;
  padding-bottom: 0;
}

.slide-enter-to,
.slide-leave-from {
  opacity: 1;
  max-height: 400px;
}

/* Mobile */
@media (max-width: 768px) {
  .filters-toggle {
    padding: 0.625rem 0.875rem;
  }

  .filters-content {
    padding: 0 0.875rem 0.75rem 0.875rem;
  }

  .filters-grid {
    grid-template-columns: 1fr;
    gap: 0.75rem;
    padding: 0.75rem 0;
  }

  .btn-group {
    flex-direction: row;
  }

  .btn-option {
    font-size: 0.75rem;
    padding: 0.375rem 0.5rem;
  }

  .options-wrap {
    gap: 0.313rem;
  }

  .option-pill {
    padding: 0.313rem 0.625rem;
    font-size: 0.75rem;
  }

  .tag-pill {
    padding: 0.25rem 0.625rem;
    font-size: 0.75rem;
  }

  .tag-avatar {
    width: 1rem;
    height: 1rem;
    font-size: 0.563rem;
  }

  .date-row {
    flex-direction: column;
    gap: 0.375rem;
  }

  .dash {
    display: none;
  }

  .actions-bar {
    flex-direction: row;
    gap: 0.375rem;
  }

  .apply-btn,
  .clear-btn {
    font-size: 0.75rem;
    padding: 0.5rem 0.875rem;
  }
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .filters-container {
    background: var(--p-surface-900);
  }

  .filters-toggle:hover {
    background: var(--p-surface-800);
  }

  .filters-content {
    border-top-color: var(--p-surface-700);
  }

  .btn-group {
    background: var(--p-surface-800);
  }

  .btn-option.active {
    background: var(--p-surface-700);
  }

  .option-pill,
  .tag-pill {
    background: var(--p-surface-800);
    border-color: var(--p-surface-700);
    color: var(--p-text-color);
  }

  .tags-select :deep(.p-multiselect),
  .compact-date :deep(.p-inputtext) {
    background: var(--p-surface-800);
    border-color: var(--p-surface-700);
    color: var(--p-text-color);
  }

  .clear-btn {
    background: var(--p-surface-800);
    border-color: var(--p-surface-700);
  }

  .actions-bar {
    border-top-color: var(--p-surface-700);
  }
}
</style>
