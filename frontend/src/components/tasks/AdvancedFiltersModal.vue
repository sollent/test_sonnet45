<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/stores/task.store'
import { TaskPriority, TaskStatus, type TaskFiltersState } from '@/types/task.types'
import Calendar from 'primevue/calendar'
import Chip from 'primevue/chip'
import InputText from 'primevue/inputtext'
import Skeleton from 'primevue/skeleton'

const { t } = useI18n()
const taskStore = useTaskStore()

// Mobile detection
const isMobile = ref(window.innerWidth < 768)

const props = defineProps<{
  visible: boolean
}>()

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'apply'): void
}>()

// Quick filter presets for modal with translations
const quickPresets = computed(() => [
  { id: 'all', label: t('tasks.all_tasks') },
  { id: 'my', label: t('tasks.my_tasks') },
  { id: 'week', label: t('tasks.this_week') },
  { id: 'important', label: t('tasks.important') },
  { id: 'team', label: t('tasks.team') }
])

const activePreset = ref('all')

// Local filter state
const localFilters = ref<TaskFiltersState>({
  tags: [],
  completed: null,
  dateFrom: null,
  dateTo: null,
  priorities: [],
  statuses: []
})

const taskType = ref<'all' | 'active' | 'completed'>('all')
const dateRange = ref<Date[] | null>(null)

// Extract dateFrom and dateTo from range
const dateFrom = computed(() => dateRange.value?.[0] || null)
const dateTo = computed(() => dateRange.value?.[1] || null)

// Options with translations
const priorityOptions = computed(() => [
  { label: t('tasks.priority_low'), value: TaskPriority.LOW, color: '#10b981' },
  { label: t('tasks.priority_medium'), value: TaskPriority.MEDIUM, color: '#f59e0b' },
  { label: t('tasks.priority_high'), value: TaskPriority.HIGH, color: '#ef4444' },
  { label: t('tasks.priority_urgent'), value: TaskPriority.URGENT, color: '#dc2626' }
])

const statusOptions = computed(() => [
  { label: t('tasks.status_pending'), value: TaskStatus.PENDING, color: '#6b7280' },
  { label: t('tasks.status_in_progress'), value: TaskStatus.IN_PROGRESS, color: '#3b82f6' },
  { label: t('tasks.status_completed'), value: TaskStatus.COMPLETED, color: '#10b981' },
  { label: t('tasks.status_cancelled'), value: TaskStatus.CANCELLED, color: '#ef4444' }
])

const popularTags = computed(() => taskStore.tags.slice(0, 8))

// Tag search
const tagSearchQuery = ref('')
const searchedTags = ref<any[]>([])
const isSearchingTags = ref(false)

// Debounce timer for tag search
let tagSearchTimeout: ReturnType<typeof setTimeout> | null = null

async function handleTagSearch() {
  if (tagSearchTimeout) {
    clearTimeout(tagSearchTimeout)
  }
  
  const query = tagSearchQuery.value.trim()
  
  if (!query) {
    searchedTags.value = []
    return
  }
  
  tagSearchTimeout = setTimeout(async () => {
    isSearchingTags.value = true
    try {
      // Search in all tags
      searchedTags.value = taskStore.tags.filter(tag => 
        tag.name.toLowerCase().includes(query.toLowerCase())
      )
    } catch (error) {
      console.error('Error searching tags:', error)
    } finally {
      isSearchingTags.value = false
    }
  }, 300)
}

// Count active filters
const activeCount = computed(() => {
  let count = 0
  if (localFilters.value.priorities.length > 0) count++
  if (localFilters.value.statuses.length > 0) count++
  if (localFilters.value.tags.length > 0) count++
  if (dateFrom.value || dateTo.value) count++
  if (taskType.value !== 'all') count++
  return count
})

// Toggle functions
function togglePriority(priority: TaskPriority) {
  const index = localFilters.value.priorities.indexOf(priority)
  if (index > -1) {
    localFilters.value.priorities.splice(index, 1)
  } else {
    localFilters.value.priorities.push(priority)
  }
}

function toggleStatus(status: TaskStatus) {
  const index = localFilters.value.statuses.indexOf(status)
  if (index > -1) {
    localFilters.value.statuses.splice(index, 1)
  } else {
    localFilters.value.statuses.push(status)
  }
  
  // Auto-switch task type when completed status is selected/deselected
  const hasCompletedStatus = localFilters.value.statuses.includes(TaskStatus.COMPLETED)
  if (hasCompletedStatus && taskType.value !== 'completed') {
    // If completed status is selected and task type is not 'completed', switch to 'completed'
    taskType.value = 'completed'
  }
}

function setTaskType(type: 'all' | 'active' | 'completed') {
  taskType.value = type
  
  // If switching to 'active', remove 'completed' status if it's selected
  if (type === 'active') {
    const completedIndex = localFilters.value.statuses.indexOf(TaskStatus.COMPLETED)
    if (completedIndex > -1) {
      localFilters.value.statuses.splice(completedIndex, 1)
    }
  }
}

function toggleTag(tagId: number) {
  const index = localFilters.value.tags.indexOf(tagId)
  if (index > -1) {
    localFilters.value.tags.splice(index, 1)
  } else {
    localFilters.value.tags.push(tagId)
  }
}

function close() {
  // Clear tag search on close
  tagSearchQuery.value = ''
  searchedTags.value = []
  if (tagSearchTimeout) {
    clearTimeout(tagSearchTimeout)
  }
  
  emit('update:visible', false)
}

function apply() {
  // Format dates from range
  if (dateRange.value && dateRange.value[0]) {
    const start = dateRange.value[0]
    const year = start.getFullYear()
    const month = String(start.getMonth() + 1).padStart(2, '0')
    const day = String(start.getDate()).padStart(2, '0')
    localFilters.value.dateFrom = `${year}-${month}-${day}`
  } else {
    localFilters.value.dateFrom = null
  }

  if (dateRange.value && dateRange.value[1]) {
    const end = dateRange.value[1]
    const year = end.getFullYear()
    const month = String(end.getMonth() + 1).padStart(2, '0')
    const day = String(end.getDate()).padStart(2, '0')
    localFilters.value.dateTo = `${year}-${month}-${day}`
  } else {
    localFilters.value.dateTo = null
  }

  // Set completed based on task type
  if (taskType.value === 'completed') {
    localFilters.value.completed = true
  } else if (taskType.value === 'active') {
    localFilters.value.completed = false
  } else {
    localFilters.value.completed = null
  }

  taskStore.setFilters(localFilters.value)
  emit('apply')
  close()
}

function clearAll() {
  localFilters.value = {
    tags: [],
    completed: null,
    dateFrom: null,
    dateTo: null,
    priorities: [],
    statuses: []
  }
  dateRange.value = null
  taskType.value = 'all'
  activePreset.value = 'all'
  
  // Clear tag search
  tagSearchQuery.value = ''
  searchedTags.value = []
  if (tagSearchTimeout) {
    clearTimeout(tagSearchTimeout)
  }
}

function removeTag(tagId: number) {
  toggleTag(tagId)
}

function clearDateRange() {
  dateRange.value = null
}

function formatDateRange(start: Date | null, end: Date | null): string {
  if (!start) return ''
  
  const formatDate = (date: Date) => {
    return date.toLocaleDateString(t('app.locale') === 'ru' ? 'ru-RU' : 'en-US', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    })
  }
  
  if (!end || start.getTime() === end.getTime()) {
    return formatDate(start)
  }
  
  return `${formatDate(start)} - ${formatDate(end)}`
}

// Handle window resize
function handleResize() {
  isMobile.value = window.innerWidth < 768
}

// Lifecycle
onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

// Watch for modal visibility changes
watch(() => props.visible, (newVisible) => {
  if (newVisible) {
    // Load current filters when opening
    localFilters.value = { ...taskStore.activeFilters }
    
    // Auto-set task type based on completed status in filters
    const hasCompletedStatus = localFilters.value.statuses.includes(TaskStatus.COMPLETED)
    if (hasCompletedStatus) {
      taskType.value = 'completed'
    } else {
      // Determine task type from completed filter
      if (taskStore.activeFilters.completed === true) {
        taskType.value = 'completed'
      } else if (taskStore.activeFilters.completed === false) {
        taskType.value = 'active'
      } else {
        taskType.value = 'all'
      }
    }
    
    // Load date range
    const hasDateFrom = taskStore.activeFilters.dateFrom
    const hasDateTo = taskStore.activeFilters.dateTo
    
    if (hasDateFrom || hasDateTo) {
      const start = hasDateFrom ? new Date(taskStore.activeFilters.dateFrom!) : null
      const end = hasDateTo ? new Date(taskStore.activeFilters.dateTo!) : null
      dateRange.value = start && end ? [start, end] : (start ? [start] : null)
    } else {
      dateRange.value = null
    }
  } else {
    // Clear tag search when closing
    tagSearchQuery.value = ''
    searchedTags.value = []
    if (tagSearchTimeout) {
      clearTimeout(tagSearchTimeout)
    }
  }
})
</script>

<template>
  <Transition name="modal">
    <div v-if="visible" class="modal-overlay" @click="close">
      <div class="modal-container" @click.stop>
        <!-- Header -->
        <div class="modal-header">
          <h2>{{ t('tasks.filters') }}</h2>
          <button @click="close" class="close-button">
            <i class="pi pi-times"></i>
          </button>
        </div>

        <!-- Content -->
        <div class="modal-content">
          <!-- Quick Presets -->
          <div class="presets-scroll">
            <button
              v-for="preset in quickPresets"
              :key="preset.id"
              :class="['preset-chip', { active: activePreset === preset.id }]"
              @click="activePreset = preset.id"
            >
              {{ preset.label }}
            </button>
          </div>

          <!-- Filters Sections -->
          <div class="filters-sections">
            <!-- Type -->
            <section class="filter-section">
              <h3 class="section-title">{{ t('tasks.task_type') }}</h3>
              <div class="type-buttons">
                <button :class="['type-btn', { active: taskType === 'all' }]" @click="setTaskType('all')">
                  {{ t('tasks.all_tasks') }}
                </button>
                <button :class="['type-btn', { active: taskType === 'active' }]" @click="setTaskType('active')">
                  {{ t('tasks.active') }}
                </button>
                <button :class="['type-btn', { active: taskType === 'completed' }]" @click="setTaskType('completed')">
                  {{ t('tasks.completed') }}
                </button>
              </div>
            </section>

            <!-- Priority -->
            <section class="filter-section">
              <h3 class="section-title">{{ t('tasks.priority') }}</h3>
              <div class="options-grid">
                <button
                  v-for="priority in priorityOptions"
                  :key="priority.value"
                  :class="['option-chip', { active: localFilters.priorities.includes(priority.value) }]"
                  @click="togglePriority(priority.value)"
                >
                  <span class="option-dot" :style="{ background: priority.color }"></span>
                  {{ priority.label }}
                </button>
              </div>
            </section>

            <!-- Status -->
            <section class="filter-section">
              <h3 class="section-title">{{ t('tasks.status') }}</h3>
              <div class="options-grid">
                <button
                  v-for="status in statusOptions"
                  :key="status.value"
                  :class="['option-chip', { active: localFilters.statuses.includes(status.value) }]"
                  @click="toggleStatus(status.value)"
                >
                  <span class="option-dot" :style="{ background: status.color }"></span>
                  {{ status.label }}
                </button>
              </div>
            </section>

            <!-- Tags -->
            <section class="filter-section">
              <h3 class="section-title">{{ t('tasks.tags') }}</h3>
              
              <!-- Tag Search -->
              <div class="tag-search-wrapper">
                <span class="p-input-icon-left" style="width: 100%;">
                  <i class="pi pi-search" />
                  <InputText
                    v-model="tagSearchQuery"
                    :placeholder="t('tasks.search_tags')"
                    class="tag-search-input"
                    @input="handleTagSearch"
                  />
                </span>
              </div>
              
              <!-- Selected Tags -->
              <div v-if="localFilters.tags.length > 0" class="selected-tags">
                <Chip
                  v-for="tagId in localFilters.tags"
                  :key="tagId"
                  :label="taskStore.tags.find(t => t.id === tagId)?.name"
                  removable
                  @remove="removeTag(tagId)"
                  class="tag-chip-selected"
                />
              </div>
              
              <!-- Search Results -->
              <div v-if="tagSearchQuery" class="search-results-section">
                <div v-if="isSearchingTags" class="tags-loading">
                  <Skeleton v-for="i in 3" :key="i" height="40px" borderRadius="8px" />
                </div>
                <div v-else-if="searchedTags.length > 0" class="tags-grid search-results">
                  <button
                    v-for="tag in searchedTags"
                    :key="tag.id"
                    :class="['tag-option', { active: localFilters.tags.includes(tag.id) }]"
                    @click="toggleTag(tag.id)"
                  >
                    <span class="tag-avatar" :style="{ background: tag.color }">
                      {{ tag.name.charAt(0).toUpperCase() }}
                    </span>
                    {{ tag.name }}
                  </button>
                </div>
                <div v-else class="no-results">
                  <i class="pi pi-search" />
                  <p>{{ t('common.no_results') }}</p>
                </div>
              </div>
              
              <!-- Popular Tags (always visible) -->
              <div v-if="!tagSearchQuery || searchedTags.length > 0" class="popular-tags-section">
                <h4 class="popular-tags-title">{{ t('tasks.popular_tags') }}</h4>
                <div class="tags-grid">
                  <button
                    v-for="tag in popularTags"
                    :key="tag.id"
                    :class="['tag-option', { active: localFilters.tags.includes(tag.id) }]"
                    @click="toggleTag(tag.id)"
                  >
                    <span class="tag-avatar" :style="{ background: tag.color }">
                      {{ tag.name.charAt(0).toUpperCase() }}
                    </span>
                    {{ tag.name }}
                  </button>
                </div>
              </div>
            </section>

            <!-- Period -->
            <section class="filter-section">
              <h3 class="section-title">{{ t('tasks.period') }}</h3>
              <div class="date-range-wrapper">
                <Calendar
                  v-model="dateRange"
                  selectionMode="range"
                  :placeholder="t('tasks.select_date_range')"
                  dateFormat="dd.mm.yy"
                  :showIcon="true"
                  :manualInput="false"
                  :numberOfMonths="isMobile ? 1 : 2"
                  :inline="false"
                  :touchUI="isMobile"
                  class="date-range-input"
                  :showButtonBar="true"
                  panelClass="custom-date-panel"
                />
                <div v-if="dateRange && dateRange[0]" class="selected-range-display">
                  <i class="pi pi-calendar" />
                  <span>
                    {{ formatDateRange(dateRange[0], dateRange[1]) }}
                  </span>
                  <button @click="clearDateRange" class="clear-date-btn">
                    <i class="pi pi-times" />
                  </button>
                </div>
              </div>
            </section>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="modal-footer">
          <button @click="clearAll" class="footer-action footer-action--clear">
            <i class="pi pi-trash"></i>
            {{ t('tasks.clear') }}
          </button>
          <button @click="apply" class="footer-action footer-action--apply">
            {{ t('tasks.apply_filters') }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
/* Modal Overlay */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1100;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.modal-container {
  width: 100%;
  max-width: 600px;
  max-height: 92vh;
  background: white;
  border-radius: 24px 24px 0 0;
  display: flex;
  flex-direction: column;
  box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.15);
  position: relative;
  z-index: 100000;
}

/* Header */
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  border-bottom: 1px solid #f1f3f5;
  flex-shrink: 0;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.375rem;
  font-weight: 700;
  color: #212529;
}

.close-button {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  background: #f8f9fa;
  border: none;
  border-radius: 50%;
  color: #6c757d;
  cursor: pointer;
  transition: all 0.15s ease;
}

.close-button:hover {
  background: #e9ecef;
  color: #495057;
  transform: scale(1.05);
}

.close-button i {
  font-size: 1.125rem;
}

/* Content */
.modal-content {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 1.5rem;
  -webkit-overflow-scrolling: touch;
}

/* Quick Presets */
.presets-scroll {
  display: flex;
  gap: 0.5rem;
  overflow-x: auto;
  padding-bottom: 1.25rem;
  margin-bottom: 1.25rem;
  border-bottom: 1px solid #f1f3f5;
  scrollbar-width: none;
}

.presets-scroll::-webkit-scrollbar {
  display: none;
}

.preset-chip {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  background: #f8f9fa;
  border: 1.5px solid #e9ecef;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #495057;
  white-space: nowrap;
  cursor: pointer;
  transition: all 0.15s ease;
  flex-shrink: 0;
}

.preset-chip:hover {
  background: #e9ecef;
}

.preset-chip.active {
  background: #6366f1;
  border-color: #6366f1;
  color: white;
  font-weight: 600;
}

/* Filter Sections */
.filters-sections {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.filter-section {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.section-title {
  margin: 0;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: #868e96;
}

/* Type Buttons */
.type-buttons {
  display: flex;
  gap: 0;
  background: #f8f9fa;
  border-radius: 10px;
  padding: 0.25rem;
}

.type-btn {
  flex: 1;
  padding: 0.5rem 1rem;
  background: transparent;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #6c757d;
  cursor: pointer;
  transition: all 0.15s ease;
}

.type-btn.active {
  background: white;
  color: #6366f1;
  font-weight: 600;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

/* Options Grid */
.options-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
}

.option-chip {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  background: white;
  border: 1.5px solid #e9ecef;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #495057;
  cursor: pointer;
  transition: all 0.15s ease;
  text-align: left;
}

.option-dot {
  width: 0.625rem;
  height: 0.625rem;
  border-radius: 50%;
  flex-shrink: 0;
}

.option-chip:hover {
  background: #f8f9fa;
  transform: translateY(-1px);
}

.option-chip.active {
  background: #6366f1;
  border-color: #6366f1;
  color: white;
  font-weight: 600;
}

.option-chip.active .option-dot {
  background: white !important;
}

/* Tags */
.selected-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 10px;
  margin-bottom: 0.75rem;
}

.tag-chip-selected :deep(.p-chip) {
  background: #6366f1;
  color: white;
  font-weight: 500;
}

.add-tag-btn {
  padding: 0.375rem 0.875rem;
  background: white;
  border: 1.5px dashed #dee2e6;
  border-radius: 8px;
  font-size: 0.813rem;
  font-weight: 500;
  color: #6c757d;
  cursor: pointer;
  transition: all 0.15s ease;
}

.add-tag-btn:hover {
  border-color: #6366f1;
  color: #6366f1;
  background: #f8f9fc;
}

.tag-search-wrapper {
  margin-bottom: 1rem;
  position: relative;
}

.tag-search-wrapper .p-input-icon-left {
  width: 100%;
  display: block;
}

.tag-search-wrapper .p-input-icon-left > i {
  position: absolute;
  left: 0.875rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 0.875rem;
}

.tag-search-input {
  width: 100%;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  padding: 0.625rem 0.875rem 0.625rem 2.5rem !important;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.tag-search-input:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.search-results-section {
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.popular-tags-section {
  margin-top: 1rem;
}

.popular-tags-title {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: #94a3b8;
  margin: 0 0 0.75rem 0;
}

.tags-loading {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.no-results {
  text-align: center;
  padding: 1.5rem 1rem;
  color: #94a3b8;
}

.no-results i {
  font-size: 2rem;
  margin-bottom: 0.5rem;
  opacity: 0.5;
}

.no-results p {
  margin: 0;
  font-size: 0.875rem;
}

.tags-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  max-height: 240px;
  overflow-y: auto;
  padding: 0.25rem;
}

.tags-grid::-webkit-scrollbar {
  width: 6px;
}

.tags-grid::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.tags-grid::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 3px;
}

.tags-grid::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.tag-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: white;
  border: 1.5px solid #e9ecef;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #495057;
  cursor: pointer;
  transition: all 0.15s ease;
}

.tag-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 6px;
  font-size: 0.688rem;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
}

.tag-option:hover {
  background: #f8f9fa;
  transform: translateY(-1px);
}

.tag-option.active {
  background: #6366f1;
  border-color: #6366f1;
  color: white;
}

.tag-option.active .tag-avatar {
  background: rgba(255, 255, 255, 0.25) !important;
}

/* Date Range */
.date-range-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.date-range-input {
  width: 100%;
}

.date-range-input :deep(.p-calendar) {
  width: 100%;
}

.date-range-input :deep(.p-inputtext) {
  width: 100%;
  padding: 0.625rem 2.5rem 0.625rem 0.875rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  font-size: 0.875rem;
}

.date-range-input :deep(.p-inputtext:focus) {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.selected-range-display {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: linear-gradient(135deg, #eef2ff 0%, #f5f8ff 100%);
  border: 1px solid #c7d2fe;
  border-radius: 8px;
  color: #4f46e5;
  font-size: 0.875rem;
  font-weight: 500;
}

.selected-range-display i {
  color: #6366f1;
  font-size: 0.875rem;
}

.clear-date-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  margin-left: auto;
  background: transparent;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  color: #94a3b8;
  transition: all 0.2s;
}

.clear-date-btn:hover {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

/* Ensure Calendar panel appears above modal */
.date-range-input :deep(.p-datepicker) {
  z-index: 1200 !important;
}

/* Mobile Calendar Touch UI */
.date-range-input :deep(.p-datepicker-touch-ui) {
  position: fixed !important;
  top: 50% !important;
  left: 50% !important;
  transform: translate(-50%, -50%) !important;
  width: 90vw !important;
  max-width: 400px !important;
  z-index: 100100 !important;
  border-radius: 16px !important;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
}

/* Touch UI Mask/Overlay */
:deep(.p-datepicker-mask) {
  z-index: 100099 !important;
  background: rgba(0, 0, 0, 0.6) !important;
}

.date-range-input :deep(.p-datepicker-touch-ui .p-datepicker-header) {
  padding: 1rem !important;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
  border-radius: 16px 16px 0 0 !important;
  color: white !important;
}

.date-range-input :deep(.p-datepicker-touch-ui .p-datepicker-title) {
  color: white !important;
}

.date-range-input :deep(.p-datepicker-touch-ui .p-datepicker-prev),
.date-range-input :deep(.p-datepicker-touch-ui .p-datepicker-next) {
  color: white !important;
}

.date-range-input :deep(.p-datepicker-touch-ui .p-datepicker-prev:hover),
.date-range-input :deep(.p-datepicker-touch-ui .p-datepicker-next:hover) {
  background: rgba(255, 255, 255, 0.2) !important;
}

.date-range-input :deep(.p-datepicker-touch-ui td > span) {
  width: 2.5rem !important;
  height: 2.5rem !important;
  font-size: 0.875rem !important;
  border-radius: 8px !important;
}

.date-range-input :deep(.p-datepicker-touch-ui td > span.p-highlight) {
  background: #6366f1 !important;
  color: white !important;
}

.date-range-input :deep(.p-datepicker-touch-ui .p-datepicker-buttonbar) {
  padding: 0.75rem 1rem !important;
  border-radius: 0 0 16px 16px !important;
}

.date-input :deep(.p-inputtext) {
  width: 100%;
  padding: 0.625rem 1rem;
  background: #f8f9fa;
  border: 1.5px solid #e9ecef;
  border-radius: 10px;
  font-size: 0.875rem;
  transition: all 0.15s ease;
  cursor: pointer;
}

.date-input :deep(.p-inputtext:hover) {
  background: #ffffff;
  border-color: #dee2e6;
}

.date-input :deep(.p-inputtext:focus) {
  background: white;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
  outline: none;
}

.date-input :deep(.p-datepicker-trigger) {
  color: #6366f1;
  cursor: pointer;
  transition: color 0.15s ease;
}

.date-input :deep(.p-datepicker-trigger:hover) {
  color: #5558e3;
}

.date-input :deep(.p-button.p-datepicker-trigger) {
  background: transparent;
  border: none;
  color: #6366f1;
  width: 2.5rem;
  height: 2.5rem;
}

/* Footer */
.modal-footer {
  display: flex;
  gap: 0.75rem;
  padding: 1.25rem 1.5rem;
  background: white;
  border-top: 1px solid #f1f3f5;
  flex-shrink: 0;
}

.footer-action {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.875rem;
  border: none;
  border-radius: 12px;
  font-size: 0.938rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.footer-action--clear {
  background: #f8f9fa;
  color: #dc3545;
  border: 1.5px solid #e9ecef;
  flex: 0 0 auto;
  padding: 0.875rem 1.25rem;
}

.footer-action--clear:hover {
  background: #fff5f5;
  border-color: #dc3545;
  transform: scale(1.02);
}

.footer-action--apply {
  background: #6366f1;
  color: white;
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
}

.footer-action--apply:hover {
  background: #5558e3;
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(99, 102, 241, 0.35);
}

/* Animations */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-active .modal-container,
.modal-leave-active .modal-container {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-container {
  transform: translateY(100%);
}

.modal-leave-to .modal-container {
  transform: translateY(100%);
}

/* Mobile Specific */
@media (max-width: 768px) {
  .modal-overlay {
    align-items: stretch;
    z-index: 100000;
  }

  .modal-container {
    max-width: 100%;
    max-height: 100vh;
    height: 100vh;
    border-radius: 0;
  }

  .modal-header {
    padding: 1.25rem 1.5rem;
  }

  .modal-content {
    padding: 1.25rem 1.5rem;
  }

  .options-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
  }

  .option-chip {
    padding: 0.625rem 0.875rem;
    font-size: 0.813rem;
  }

  .date-range-row {
    flex-direction: column;
    align-items: stretch;
    gap: 0.75rem;
  }

  .date-separator {
    display: none;
  }

  .date-input :deep(.p-inputtext) {
    font-size: 0.813rem;
  }
}

/* Tablet */
@media (min-width: 769px) and (max-width: 1024px) {
  .modal-container {
    max-width: 500px;
  }
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .modal-container {
    background: var(--p-surface-900);
  }

  .modal-header,
  .modal-footer {
    background: var(--p-surface-900);
    border-color: var(--p-surface-700);
  }

  .type-buttons {
    background: var(--p-surface-800);
  }

  .type-btn.active {
    background: var(--p-surface-700);
  }

  .option-chip,
  .tag-option,
  .preset-chip {
    background: var(--p-surface-800);
    border-color: var(--p-surface-700);
    color: var(--p-text-color);
  }

  .date-input :deep(.p-inputtext) {
    background: var(--p-surface-800);
    border-color: var(--p-surface-700);
    color: var(--p-text-color);
  }

  .footer-action--clear {
    background: var(--p-surface-800);
    border-color: var(--p-surface-700);
  }
}
</style>

