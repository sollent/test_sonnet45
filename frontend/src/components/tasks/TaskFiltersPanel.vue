<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/stores/task.store'
import { TaskPriority, TaskStatus, type TaskFiltersState } from '@/types/task.types'
import MultiSelect from 'primevue/multiselect'
import Calendar from 'primevue/calendar'

const { t } = useI18n()
const taskStore = useTaskStore()

// Simple debounce implementation
function debounce<T extends (...args: any[]) => any>(
  func: T,
  wait: number
): (...args: Parameters<T>) => void {
  let timeout: ReturnType<typeof setTimeout> | null = null
  return function(this: any, ...args: Parameters<T>) {
    const context = this
    if (timeout) clearTimeout(timeout)
    timeout = setTimeout(() => func.apply(context, args), wait)
  }
}

// UI State
const isExpanded = ref(false)
const searchQuery = ref('')
const taskTypeFilter = ref<'all' | 'active' | 'completed'>('all')
const dateFrom = ref<Date | null>(null)
const dateTo = ref<Date | null>(null)

// Local filter state
const localFilters = ref<TaskFiltersState & { assignee?: string }>({
  tags: [],
  completed: null,
  dateFrom: null,
  dateTo: null,
  priorities: [],
  statuses: [],
  assignee: ''
})

// Priority options
const priorityOptions = computed(() => [
  { label: t('tasks.priority_low'), value: TaskPriority.LOW, color: '#10b981' },
  { label: t('tasks.priority_medium'), value: TaskPriority.MEDIUM, color: '#f59e0b' },
  { label: t('tasks.priority_high'), value: TaskPriority.HIGH, color: '#ef4444' },
  { label: t('tasks.priority_urgent'), value: TaskPriority.URGENT, color: '#dc2626' }
])

// Status options
const statusOptions = computed(() => [
  { label: t('tasks.status_pending'), value: TaskStatus.PENDING, color: '#6b7280' },
  { label: t('tasks.status_in_progress'), value: TaskStatus.IN_PROGRESS, color: '#3b82f6' },
  { label: t('tasks.status_completed'), value: TaskStatus.COMPLETED, color: '#10b981' },
  { label: t('tasks.status_cancelled'), value: TaskStatus.CANCELLED, color: '#ef4444' }
])

// Count active filters
const activeFiltersCount = computed(() => {
  let count = 0
  if (localFilters.value.tags.length > 0) count++
  if (localFilters.value.completed !== null) count++
  if (localFilters.value.dateFrom || localFilters.value.dateTo) count++
  if (localFilters.value.priorities.length > 0) count++
  if (localFilters.value.statuses.length > 0) count++
  if (searchQuery.value) count++
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
    statuses: [],
    assignee: ''
  }
  dateFrom.value = null
  dateTo.value = null
  searchQuery.value = ''
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

// Handle search with debounce
const handleSearch = debounce(() => {
  if (searchQuery.value) {
    taskStore.setSearchQuery(searchQuery.value)
  } else {
    taskStore.clearSearch()
  }
}, 300)

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

// Save filter as preset
function saveFilter() {
  // TODO: Implement save filter functionality
  console.log('Save filter:', localFilters.value)
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

// Toggle tags
function toggleTag(tagId: number) {
  const index = localFilters.value.tags.indexOf(tagId)
  if (index > -1) {
    localFilters.value.tags.splice(index, 1)
  } else {
    localFilters.value.tags.push(tagId)
  }
  applyFilters()
}
</script>

<template>
  <div class="filters-container">
    <!-- Header with toggle -->
    <div class="filters-header" @click="isExpanded = !isExpanded">
      <div class="filters-title">
        <i class="pi pi-filter"></i>
        <span>{{ t('tasks.filters') }}</span>
        <span v-if="activeFiltersCount > 0" class="filter-badge">
          {{ activeFiltersCount }}
        </span>
      </div>
      <button 
        class="expand-toggle"
        :aria-label="isExpanded ? t('tasks.hide_filters') : t('tasks.show_filters')"
      >
        <i :class="['pi', isExpanded ? 'pi-chevron-up' : 'pi-chevron-down']"></i>
      </button>
    </div>

    <!-- Filters Panel -->
    <Transition name="filters-slide">
      <div v-show="isExpanded" class="filters-panel">
        <div class="filters-grid">
          <!-- Search -->
          <div class="filter-section filter-section--search">
            <div class="filter-input-group">
              <i class="pi pi-search input-icon"></i>
              <input 
                type="text"
                v-model="searchQuery"
                @input="handleSearch"
                :placeholder="t('tasks.search_placeholder')"
                class="filter-search-input"
              />
            </div>
          </div>

          <!-- Task Type Filter -->
          <div class="filter-section">
            <label class="filter-label">
              <i class="pi pi-list"></i>
              <span>{{ t('tasks.task_type') }}</span>
            </label>
            <div class="button-group">
              <button 
                :class="['filter-button', { active: taskTypeFilter === 'all' }]"
                @click="taskTypeFilter = 'all'"
              >
                {{ t('tasks.all_tasks') }}
              </button>
              <button 
                :class="['filter-button', { active: taskTypeFilter === 'active' }]"
                @click="taskTypeFilter = 'active'"
              >
                {{ t('tasks.active') }}
              </button>
              <button 
                :class="['filter-button', { active: taskTypeFilter === 'completed' }]"
                @click="taskTypeFilter = 'completed'"
              >
                {{ t('tasks.completed') }}
              </button>
            </div>
          </div>

          <!-- Tags -->
          <div class="filter-section">
            <label class="filter-label">
              <i class="pi pi-tags"></i>
              <span>{{ t('tasks.tags') }}</span>
            </label>
            <MultiSelect
              v-model="localFilters.tags"
              :options="taskStore.tags"
              optionLabel="name"
              optionValue="id"
              :placeholder="t('tasks.select_tags')"
              :maxSelectedLabels="3"
              class="w-full filter-multiselect"
              @change="applyFilters"
            />
          </div>

          <!-- Date Range -->
          <div class="filter-section">
            <label class="filter-label">
              <i class="pi pi-calendar"></i>
              <span>{{ t('tasks.period') }}</span>
            </label>
            <div class="date-range-inputs">
              <Calendar
                v-model="dateFrom"
                :placeholder="t('tasks.date_from')"
                dateFormat="dd.mm.yy"
                class="date-input"
                @date-select="handleDateChange"
                @clear-click="handleDateChange"
              />
              <span class="date-separator">—</span>
              <Calendar
                v-model="dateTo"
                :placeholder="t('tasks.date_to')"
                dateFormat="dd.mm.yy"
                class="date-input"
                @date-select="handleDateChange"
                @clear-click="handleDateChange"
              />
            </div>
          </div>

          <!-- Priority -->
          <div class="filter-section">
            <label class="filter-label">
              <i class="pi pi-flag"></i>
              <span>{{ t('tasks.priority') }}</span>
            </label>
            <div class="priority-pills">
              <button
                v-for="priority in priorityOptions"
                :key="priority.value"
                :class="['priority-pill', { active: localFilters.priorities.includes(priority.value) }]"
                :style="{ '--pill-color': priority.color }"
                @click="togglePriority(priority.value)"
              >
                <span class="pill-dot" :style="{ background: priority.color }"></span>
                {{ priority.label }}
              </button>
            </div>
          </div>

          <!-- Status -->
          <div class="filter-section">
            <label class="filter-label">
              <i class="pi pi-info-circle"></i>
              <span>{{ t('tasks.status_execution') }}</span>
            </label>
            <div class="status-pills">
              <button
                v-for="status in statusOptions"
                :key="status.value"
                :class="['status-pill', { active: localFilters.statuses.includes(status.value) }]"
                :style="{ '--pill-color': status.color }"
                @click="toggleStatus(status.value)"
              >
                <span class="status-indicator" :style="{ background: status.color }"></span>
                {{ status.label }}
              </button>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="filter-actions">
          <button 
            @click="clearFilters"
            :disabled="activeFiltersCount === 0"
            class="clear-button"
          >
            <i class="pi pi-times"></i>
            {{ t('tasks.clear_all') }}
          </button>
          <button 
            @click="saveFilter"
            class="save-button"
            v-if="activeFiltersCount > 0"
          >
            <i class="pi pi-bookmark"></i>
            {{ t('tasks.save_filter') }}
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.filters-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  margin-bottom: 1.5rem;
}

.filters-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
  border-bottom: 1px solid #e9ecef;
  cursor: pointer;
  transition: background 0.2s ease;
}

.filters-header:hover {
  background: linear-gradient(135deg, #f0f1f3 0%, #fafbfc 100%);
}

.filters-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 600;
  font-size: 0.938rem;
  color: #495057;
}

.filters-title i {
  color: #6366f1;
  font-size: 1.125rem;
}

.filter-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.5rem;
  height: 1.5rem;
  padding: 0 0.375rem;
  background: #6366f1;
  color: white;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 700;
}

.expand-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  color: #6c757d;
  cursor: pointer;
  transition: all 0.2s ease;
}

.expand-toggle:hover {
  background: #f8f9fa;
  border-color: #6366f1;
  color: #6366f1;
}

/* Filters Panel */
.filters-panel {
  padding: 1.25rem;
  background: white;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.25rem;
  margin-bottom: 1.25rem;
}

.filter-section {
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
}

.filter-section--search {
  grid-column: 1 / -1;
}

.filter-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #6c757d;
}

.filter-label i {
  font-size: 0.875rem;
  color: #adb5bd;
}

/* Search Input */
.filter-input-group {
  position: relative;
}

.input-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #adb5bd;
  font-size: 1rem;
  pointer-events: none;
}

.filter-search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.75rem;
  background: #f8f9fa;
  border: 2px solid transparent;
  border-radius: 12px;
  font-size: 0.938rem;
  color: #212529;
  transition: all 0.2s ease;
}

.filter-search-input:focus {
  outline: none;
  background: white;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.filter-search-input::placeholder {
  color: #adb5bd;
}

/* MultiSelect */
.filter-multiselect :deep(.p-multiselect) {
  background: #f8f9fa;
  border: 2px solid transparent;
  border-radius: 10px;
  min-height: 42px;
}

.filter-multiselect :deep(.p-multiselect:not(.p-disabled):hover) {
  border-color: #dee2e6;
}

.filter-multiselect :deep(.p-multiselect:not(.p-disabled).p-focus) {
  background: white;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Button Group */
.button-group {
  display: flex;
  gap: 0;
  background: #f8f9fa;
  border-radius: 10px;
  padding: 0.25rem;
}

.filter-button {
  flex: 1;
  padding: 0.5rem 1rem;
  background: transparent;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #6c757d;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-button:hover {
  color: #495057;
}

.filter-button.active {
  background: white;
  color: #6366f1;
  font-weight: 600;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Date Range */
.date-range-inputs {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.date-input {
  flex: 1;
}

.date-input :deep(.p-inputtext) {
  padding: 0.625rem 1rem;
  background: #f8f9fa;
  border: 2px solid transparent;
  border-radius: 10px;
  font-size: 0.875rem;
  transition: all 0.2s ease;
}

.date-input :deep(.p-inputtext:focus) {
  background: white;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.date-separator {
  color: #adb5bd;
  font-weight: 500;
}

/* Priority Pills */
.priority-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.priority-pill {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.875rem;
  background: white;
  border: 2px solid #dee2e6;
  border-radius: 20px;
  font-size: 0.813rem;
  font-weight: 500;
  color: #495057;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pill-dot {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  transition: transform 0.2s ease;
}

.priority-pill:hover {
  border-color: var(--pill-color);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.priority-pill.active {
  background: var(--pill-color);
  border-color: var(--pill-color);
  color: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.priority-pill.active .pill-dot {
  background: white !important;
  transform: scale(1.2);
}

/* Status Pills */
.status-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.status-pill {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.875rem;
  background: white;
  border: 2px solid #dee2e6;
  border-radius: 10px;
  font-size: 0.813rem;
  font-weight: 500;
  color: #495057;
  cursor: pointer;
  transition: all 0.2s ease;
}

.status-indicator {
  width: 0.625rem;
  height: 0.625rem;
  border-radius: 50%;
  transition: transform 0.2s ease;
}

.status-pill:hover {
  border-color: var(--pill-color);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.status-pill.active {
  border-color: var(--pill-color);
  background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,255,255,0.7));
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.status-pill.active .status-indicator {
  transform: scale(1.4);
  box-shadow: 0 0 0 2px rgba(255,255,255,0.8);
}

/* Filter Actions */
.filter-actions {
  display: flex;
  gap: 0.75rem;
  padding-top: 1.25rem;
  border-top: 1px solid #e9ecef;
}

.clear-button,
.save-button {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  border: 2px solid #dee2e6;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #495057;
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;
}

.clear-button:hover:not(:disabled) {
  border-color: #dc3545;
  color: #dc3545;
  background: #fff5f5;
}

.clear-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.save-button {
  margin-left: auto;
  border-color: #6366f1;
  background: #6366f1;
  color: white;
}

.save-button:hover {
  background: #5558e3;
  border-color: #5558e3;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

/* Animations */
.filters-slide-enter-active,
.filters-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.filters-slide-enter-from {
  opacity: 0;
  max-height: 0;
}

.filters-slide-leave-to {
  opacity: 0;
  max-height: 0;
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .filters-container {
    border-radius: 12px;
    margin-bottom: 1rem;
  }

  .filters-header {
    padding: 0.875rem 1rem;
  }

  .filters-panel {
    padding: 1rem;
  }

  .filters-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .date-range-inputs {
    flex-direction: column;
    align-items: stretch;
  }

  .date-separator {
    display: none;
  }

  .filter-actions {
    flex-direction: column;
    gap: 0.5rem;
  }

  .clear-button,
  .save-button {
    width: 100%;
    justify-content: center;
  }

  .save-button {
    margin-left: 0;
  }
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .filters-container {
    background: var(--p-surface-900);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  }

  .filters-header {
    background: var(--p-surface-900);
    border-bottom-color: var(--p-surface-700);
  }

  .filters-title {
    color: var(--p-text-color);
  }

  .expand-toggle {
    background: var(--p-surface-800);
    border-color: var(--p-surface-700);
    color: var(--p-text-muted-color);
  }

  .filters-panel {
    background: var(--p-surface-900);
  }

  .filter-search-input {
    background: var(--p-surface-800);
    color: var(--p-text-color);
  }

  .button-group {
    background: var(--p-surface-800);
  }

  .filter-button.active {
    background: var(--p-surface-700);
  }

  .priority-pill,
  .status-pill {
    background: var(--p-surface-800);
    border-color: var(--p-surface-700);
    color: var(--p-text-color);
  }

  .clear-button,
  .save-button {
    background: var(--p-surface-800);
    border-color: var(--p-surface-700);
    color: var(--p-text-color);
  }
}
</style>