<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/stores/task.store'
import { TaskPriority, TaskStatus, type TaskFiltersState } from '@/types/task.types'
import Calendar from 'primevue/calendar'
import Chip from 'primevue/chip'

const { t } = useI18n()
const taskStore = useTaskStore()

const props = defineProps<{
  visible: boolean
}>()

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'apply'): void
}>()

// Quick filter presets for modal
const quickPresets = [
  { id: 'all', label: 'Все задачи', active: true },
  { id: 'my', label: 'Мои задачи', active: false },
  { id: 'week', label: 'На этой неделе', active: false },
  { id: 'important', label: 'Важные', active: false },
  { id: 'team', label: 'Командные', active: false }
]

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
const dateFrom = ref<Date | null>(null)
const dateTo = ref<Date | null>(null)

// Computed minimum date for dateTo (should be >= dateFrom)
const minDateTo = computed(() => dateFrom.value || undefined)

// Options
const priorityOptions = [
  { label: 'Низкий', value: TaskPriority.LOW, color: '#10b981' },
  { label: 'Средний', value: TaskPriority.MEDIUM, color: '#f59e0b' },
  { label: 'Высокий', value: TaskPriority.HIGH, color: '#ef4444' },
  { label: 'Срочный', value: TaskPriority.URGENT, color: '#dc2626' }
]

const statusOptions = [
  { label: 'Ожидание', value: TaskStatus.PENDING, color: '#6b7280' },
  { label: 'В процессе', value: TaskStatus.IN_PROGRESS, color: '#3b82f6' },
  { label: 'Завершена', value: TaskStatus.COMPLETED, color: '#10b981' },
  { label: 'Отменена', value: TaskStatus.CANCELLED, color: '#ef4444' }
]

const popularTags = computed(() => taskStore.tags.slice(0, 5))

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
  emit('update:visible', false)
}

function apply() {
  // Format dates
  if (dateFrom.value) {
    const year = dateFrom.value.getFullYear()
    const month = String(dateFrom.value.getMonth() + 1).padStart(2, '0')
    const day = String(dateFrom.value.getDate()).padStart(2, '0')
    localFilters.value.dateFrom = `${year}-${month}-${day}`
  } else {
    localFilters.value.dateFrom = null
  }

  if (dateTo.value) {
    const year = dateTo.value.getFullYear()
    const month = String(dateTo.value.getMonth() + 1).padStart(2, '0')
    const day = String(dateTo.value.getDate()).padStart(2, '0')
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
  dateFrom.value = null
  dateTo.value = null
  taskType.value = 'all'
  activePreset.value = 'all'
}

function removeTag(tagId: number) {
  toggleTag(tagId)
}
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
                <button :class="['type-btn', { active: taskType === 'all' }]" @click="taskType = 'all'">
                  {{ t('tasks.all_tasks') }}
                </button>
                <button :class="['type-btn', { active: taskType === 'active' }]" @click="taskType = 'active'">
                  {{ t('tasks.active') }}
                </button>
                <button :class="['type-btn', { active: taskType === 'completed' }]" @click="taskType = 'completed'">
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
                <button @click="localFilters.tags = []" class="add-tag-btn">
                  + {{ t('tasks.add_tag') }}
                </button>
              </div>
              
              <!-- Popular Tags -->
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
            </section>

            <!-- Period -->
            <section class="filter-section">
              <h3 class="section-title">{{ t('tasks.period') }}</h3>
              <div class="date-range-row">
                <div class="date-field-wrapper">
                  <label class="date-label">{{ t('tasks.date_from') }}</label>
                  <Calendar
                    v-model="dateFrom"
                    placeholder="Начало"
                    dateFormat="dd.mm.yy"
                    :showIcon="true"
                    :manualInput="false"
                    appendTo="body"
                    class="date-input"
                  />
                </div>
                <div class="date-separator">
                  <i class="pi pi-arrow-right"></i>
                </div>
                <div class="date-field-wrapper">
                  <label class="date-label">{{ t('tasks.date_to') }}</label>
                  <Calendar
                    v-model="dateTo"
                    placeholder="Конец"
                    dateFormat="dd.mm.yy"
                    :showIcon="true"
                    :manualInput="false"
                    :minDate="minDateTo"
                    appendTo="body"
                    class="date-input"
                  />
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
  z-index: 100000;
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

.tags-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
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
.date-range-row {
  display: flex;
  align-items: flex-end;
  gap: 0.75rem;
}

.date-field-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.date-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.date-separator {
  display: flex;
  align-items: center;
  justify-content: center;
  padding-bottom: 0.5rem;
  color: #adb5bd;
  font-size: 0.875rem;
}

.date-input :deep(.p-calendar) {
  width: 100%;
}

.date-input :deep(.p-calendar-w-btn) {
  width: 100%;
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

