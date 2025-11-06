<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/stores/task.store'
import { useToast } from '@/composables/useToast'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import AutoComplete from 'primevue/autocomplete'
import Chip from 'primevue/chip'
import Skeleton from 'primevue/skeleton'
import { TaskStatus, TaskPriority, type CreateTaskRequest, type Task, type Tag as TaskTag } from '@/types/task.types'
import { useTagSuggestions } from '@/composables/useTagSuggestions'

interface Props {
  visible: boolean
  task?: Task | null
}

interface Emits {
  (e: 'update:visible', value: boolean): void
  (e: 'task-created', task: Task): void
  (e: 'task-updated', task: Task): void
}

const props = withDefaults(defineProps<Props>(), {
  visible: false,
  task: null
})

const emit = defineEmits<Emits>()

const { t } = useI18n()
const taskStore = useTaskStore()
const { showSuccess, showError } = useToast()
const {
  popularTags,
  isLoadingPopular,
  searchSuggestions,
  searchTags,
  initialize: initializeTagSuggestions
} = useTagSuggestions()

// Form data
const formData = ref<CreateTaskRequest>({
  title: '',
  description: null,
  status: TaskStatus.PENDING,
  priority: TaskPriority.MEDIUM,
  startDate: null,
  dueDate: null,
  tags: []
})

const isSubmitting = ref(false)
const errors = ref<Record<string, string>>({})

// Quick date/time selection
const selectedDay = ref<'today' | 'tomorrow' | 'dayAfter' | null>(null)
const quickTimeStart = ref<string>('')
const quickTimeEnd = ref<string>('')
const showAdvancedDate = ref(false)

// Computed
const isEditMode = computed(() => !!props.task)
const dialogTitle = computed(() => 
  isEditMode.value ? t('tasks.edit_task') : t('tasks.new_task')
)

const statusOptions = computed(() => [
  { label: t('tasks.status_pending'), value: TaskStatus.PENDING },
  { label: t('tasks.status_in_progress'), value: TaskStatus.IN_PROGRESS },
  { label: t('tasks.status_completed'), value: TaskStatus.COMPLETED },
  { label: t('tasks.status_cancelled'), value: TaskStatus.CANCELLED }
])

const priorityOptions = computed(() => [
  { label: t('tasks.priority_low'), value: TaskPriority.LOW },
  { label: t('tasks.priority_medium'), value: TaskPriority.MEDIUM },
  { label: t('tasks.priority_high'), value: TaskPriority.HIGH },
  { label: t('tasks.priority_urgent'), value: TaskPriority.URGENT }
])

// Watch for task changes to populate form in edit mode
// Initialize tags when dialog opens
watch(() => props.visible, (isVisible) => {
  if (isVisible && !isEditMode.value) {
    initializeTagSuggestions(7)
  }
})

watch(() => props.task, (newTask) => {
  if (newTask) {
    formData.value = {
      title: newTask.title,
      description: newTask.description,
      status: newTask.status,
      priority: newTask.priority,
      startDate: newTask.startDate,
      dueDate: newTask.dueDate,
      tags: newTask.tags.map(tag => tag.name)
    }
  } else {
    resetForm()
  }
}, { immediate: true })

// Methods
function resetForm() {
  formData.value = {
    title: '',
    description: null,
    status: TaskStatus.PENDING,
    priority: TaskPriority.MEDIUM,
    startDate: null,
    dueDate: null,
    tags: []
  }
  errors.value = {}
  selectedDay.value = null
  quickTimeStart.value = ''
  quickTimeEnd.value = ''
  showAdvancedDate.value = false
}

// Quick date selection methods
function selectQuickDay(day: 'today' | 'tomorrow' | 'dayAfter') {
  selectedDay.value = day
  updateDatesFromQuickSelection()
}

function updateDatesFromQuickSelection() {
  if (!selectedDay.value) return

  const baseDate = new Date()

  // Reset time to current time rounded to next 30 minutes
  const currentMinutes = baseDate.getMinutes()
  const roundedMinutes = Math.ceil(currentMinutes / 30) * 30
  baseDate.setMinutes(roundedMinutes, 0, 0)

  // Set the day
  switch (selectedDay.value) {
    case 'tomorrow':
      baseDate.setDate(baseDate.getDate() + 1)
      break
    case 'dayAfter':
      baseDate.setDate(baseDate.getDate() + 2)
      break
  }

  // If we have time values, apply them
  if (quickTimeStart.value && quickTimeEnd.value) {
    const [startHour, startMin] = quickTimeStart.value.split(':').map(Number)
    const [endHour, endMin] = quickTimeEnd.value.split(':').map(Number)

    const startDate = new Date(baseDate)
    startDate.setHours(startHour, startMin, 0, 0)

    const endDate = new Date(baseDate)
    endDate.setHours(endHour, endMin, 0, 0)

    formData.value.startDate = startDate.toISOString()
    formData.value.dueDate = endDate.toISOString()
  } else {
    // Default: 1-hour task starting at next rounded hour
    const startDate = new Date(baseDate)
    const endDate = new Date(baseDate)
    endDate.setHours(endDate.getHours() + 1)

    formData.value.startDate = startDate.toISOString()
    formData.value.dueDate = endDate.toISOString()

    // Update quick time fields to show the default
    quickTimeStart.value = `${String(startDate.getHours()).padStart(2, '0')}:${String(startDate.getMinutes()).padStart(2, '0')}`
    quickTimeEnd.value = `${String(endDate.getHours()).padStart(2, '0')}:${String(endDate.getMinutes()).padStart(2, '0')}`
  }
}

function onQuickTimeChange() {
  updateDatesFromQuickSelection()
}

// Watch for manual date changes to sync with quick selection
watch([() => formData.value.startDate, () => formData.value.dueDate], ([newStart, newDue]) => {
  if (!newStart || !newDue) {
    selectedDay.value = null
    quickTimeStart.value = ''
    quickTimeEnd.value = ''
    return
  }

  const start = new Date(newStart)
  const due = new Date(newDue)

  // Check if dates are on the same day
  if (start.toDateString() !== due.toDateString()) {
    selectedDay.value = null
    quickTimeStart.value = ''
    quickTimeEnd.value = ''
    return
  }

  // Check which day it is
  const today = new Date()
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  const dayAfter = new Date()
  dayAfter.setDate(dayAfter.getDate() + 2)

  if (start.toDateString() === today.toDateString()) {
    selectedDay.value = 'today'
  } else if (start.toDateString() === tomorrow.toDateString()) {
    selectedDay.value = 'tomorrow'
  } else if (start.toDateString() === dayAfter.toDateString()) {
    selectedDay.value = 'dayAfter'
  } else {
    selectedDay.value = null
  }

  // Update time fields
  if (selectedDay.value) {
    quickTimeStart.value = `${String(start.getHours()).padStart(2, '0')}:${String(start.getMinutes()).padStart(2, '0')}`
    quickTimeEnd.value = `${String(due.getHours()).padStart(2, '0')}:${String(due.getMinutes()).padStart(2, '0')}`
  }
})

function validateForm(): boolean {
  errors.value = {}

  if (!formData.value.title?.trim()) {
    errors.value.title = t('validation.task_title_required')
  } else if (formData.value.title.length > 255) {
    errors.value.title = t('validation.task_title_max_length')
  }

  if (formData.value.description && formData.value.description.length > 5000) {
    errors.value.description = t('validation.task_description_max_length')
  }

  if (formData.value.startDate && formData.value.dueDate) {
    const start = new Date(formData.value.startDate)
    const due = new Date(formData.value.dueDate)
    if (due < start) {
      errors.value.dueDate = t('validation.due_date_after_start')
    }
  }

  return Object.keys(errors.value).length === 0
}

// Tag handling
async function handleTagSearch(event: any) {
  const query = event.query?.trim()
  if (query && query.length > 0) {
    await searchTags(query)
  }
}

function onCreateDialogTagsKeydown(event: KeyboardEvent) {
  if (event.key !== 'Enter') return
  const target = event.target as HTMLInputElement | null
  const value = target?.value?.trim()
  if (!value) return
  if (!formData.value.tags) {
    formData.value.tags = []
  }
  const exists = formData.value.tags.some(t => t.toLowerCase() === value.toLowerCase())
  if (!exists) {
    formData.value.tags.push(value)
  }
  if (target) {
    target.value = ''
  }
}

function addPopularTag(tag: TaskTag) {
  if (!formData.value.tags) {
    formData.value.tags = []
  }
  
  const tagName = tag.name.trim()
  const exists = formData.value.tags.some(t => t.toLowerCase() === tagName.toLowerCase())
  
  if (!exists) {
    formData.value.tags.push(tagName)
  }
}

async function handleSubmit() {
  if (!validateForm()) {
    return
  }

  isSubmitting.value = true

  try {
    if (isEditMode.value && props.task) {
      const updatedTask = await taskStore.updateTask(props.task.id, formData.value)
      showSuccess(t('tasks.task_updated'))
      emit('task-updated', updatedTask)
    } else {
      const newTask = await taskStore.createTask(formData.value)
      showSuccess(t('tasks.task_created'))
      emit('task-created', newTask)
    }

    handleClose()
  } catch (error: any) {
    showError(error.message || t('errors.unknown_error'))
  } finally {
    isSubmitting.value = false
  }
}

function handleClose() {
  resetForm()
  emit('update:visible', false)
}
</script>

<template>
  <Dialog 
    :visible="visible"
    modal
    :header="dialogTitle"
    :style="{ width: '90vw', maxWidth: '600px' }"
    :dismissableMask="true"
    @update:visible="(val) => emit('update:visible', val)"
  >
    <template #header>
      <div class="dialog-header">
        <div class="dialog-header-icon">
          <i :class="isEditMode ? 'pi pi-pencil' : 'pi pi-plus-circle'" />
        </div>
        <div class="dialog-header-content">
          <h3 class="dialog-title">{{ dialogTitle }}</h3>
          <p class="dialog-subtitle">
            {{ isEditMode ? t('tasks.edit_task') : t('tasks.no_tasks_description') }}
          </p>
        </div>
      </div>
    </template>

    <div class="task-form">
      <!-- Title -->
      <div class="form-field">
        <label for="task-title" class="field-label">
          {{ t('tasks.task_title') }}
          <span class="required">*</span>
        </label>
        <InputText
          id="task-title"
          v-model="formData.title"
          :placeholder="t('tasks.title_placeholder')"
          :class="{ 'p-invalid': errors.title }"
          class="w-full"
          autofocus
        />
        <small v-if="errors.title" class="p-error">{{ errors.title }}</small>
      </div>

      <!-- Description -->
      <div class="form-field">
        <label for="task-description" class="field-label">
          {{ t('tasks.task_description') }}
        </label>
        <Textarea
          id="task-description"
          v-model="formData.description"
          :placeholder="t('tasks.description_placeholder')"
          :class="{ 'p-invalid': errors.description }"
          rows="4"
          class="w-full"
          autoResize
        />
        <small v-if="errors.description" class="p-error">{{ errors.description }}</small>
      </div>

      <!-- Quick Date & Time Selection -->
      <div class="quick-datetime-section">
        <div class="section-header">
          <div class="section-title">
            <i class="pi pi-calendar-times" />
            <span>{{ t('tasks.schedule_task') }}</span>
          </div>
          <button
            type="button"
            class="advanced-toggle"
            @click="showAdvancedDate = !showAdvancedDate"
          >
            <i :class="showAdvancedDate ? 'pi pi-chevron-up' : 'pi pi-chevron-down'" />
            <span>{{ showAdvancedDate ? t('tasks.hide_advanced') : t('tasks.show_advanced') }}</span>
          </button>
        </div>

        <div v-if="!showAdvancedDate" class="quick-datetime-content">
          <!-- Day Selection Chips -->
          <div class="day-chips">
            <button
              type="button"
              class="day-chip"
              :class="{ 'active': selectedDay === 'today' }"
              @click="selectQuickDay('today')"
            >
              <i class="pi pi-sun" />
              <span>{{ t('tasks.today') }}</span>
            </button>
            <button
              type="button"
              class="day-chip"
              :class="{ 'active': selectedDay === 'tomorrow' }"
              @click="selectQuickDay('tomorrow')"
            >
              <i class="pi pi-cloud" />
              <span>{{ t('tasks.tomorrow') }}</span>
            </button>
            <button
              type="button"
              class="day-chip"
              :class="{ 'active': selectedDay === 'dayAfter' }"
              @click="selectQuickDay('dayAfter')"
            >
              <i class="pi pi-moon" />
              <span>{{ t('tasks.day_after_tomorrow') }}</span>
            </button>
          </div>

          <!-- Time Range Selection -->
          <div v-if="selectedDay" class="time-range-selector">
            <div class="time-input-group">
              <label class="time-label">
                <i class="pi pi-clock" />
                <span>{{ t('tasks.start_time') }}</span>
              </label>
              <input
                type="time"
                v-model="quickTimeStart"
                @change="onQuickTimeChange"
                class="time-input"
                :step="300"
              />
            </div>
            <div class="time-arrow">
              <i class="pi pi-arrow-right" />
            </div>
            <div class="time-input-group">
              <label class="time-label">
                <i class="pi pi-flag-fill" />
                <span>{{ t('tasks.end_time') }}</span>
              </label>
              <input
                type="time"
                v-model="quickTimeEnd"
                @change="onQuickTimeChange"
                class="time-input"
                :step="300"
              />
            </div>
          </div>

          <!-- Quick Time Presets -->
          <div v-if="selectedDay" class="time-presets">
            <span class="preset-label">{{ t('tasks.quick_presets') }}:</span>
            <button
              type="button"
              class="time-preset"
              @click="quickTimeStart = '09:00'; quickTimeEnd = '10:00'; onQuickTimeChange()"
            >
              9:00 - 10:00
            </button>
            <button
              type="button"
              class="time-preset"
              @click="quickTimeStart = '14:00'; quickTimeEnd = '15:00'; onQuickTimeChange()"
            >
              14:00 - 15:00
            </button>
            <button
              type="button"
              class="time-preset"
              @click="quickTimeStart = '17:00'; quickTimeEnd = '18:00'; onQuickTimeChange()"
            >
              17:00 - 18:00
            </button>
          </div>
        </div>

        <!-- Advanced Date Selection (Traditional) -->
        <div v-if="showAdvancedDate" class="form-row">
          <div class="form-field">
            <label for="start-date" class="field-label">
              {{ t('tasks.start_date') }}
            </label>
            <Calendar
              id="start-date"
              v-model="formData.startDate"
              showTime
              hourFormat="24"
              :placeholder="t('common.select_date')"
              :stepMinute="10"
              class="w-full"
              dateFormat="dd.mm.yy"
            />
          </div>

          <div class="form-field">
            <label for="due-date" class="field-label">
              {{ t('tasks.due_date') }}
            </label>
            <Calendar
              id="due-date"
              v-model="formData.dueDate"
              showTime
              hourFormat="24"
              :placeholder="t('common.select_date')"
              :stepMinute="10"
              :class="{ 'p-invalid': errors.dueDate }"
              class="w-full"
              dateFormat="dd.mm.yy"
            />
            <small v-if="errors.dueDate" class="p-error">{{ errors.dueDate }}</small>
          </div>
        </div>
      </div>

      <!-- Status and Priority -->
      <div class="form-row">
        <div class="form-field">
          <label for="task-status" class="field-label">
            {{ t('tasks.task_status') }}
          </label>
          <Dropdown
            id="task-status"
            v-model="formData.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            :placeholder="t('tasks.task_status')"
            class="w-full"
          />
        </div>

        <div class="form-field">
          <label for="task-priority" class="field-label">
            {{ t('tasks.task_priority') }}
          </label>
          <Dropdown
            id="task-priority"
            v-model="formData.priority"
            :options="priorityOptions"
            optionLabel="label"
            optionValue="value"
            :placeholder="t('tasks.task_priority')"
            class="w-full"
          />
        </div>
      </div>

      <!-- Tags -->
      <div class="form-field">
        <label for="task-tags" class="field-label">
          {{ t('tasks.tags') }}
        </label>
        <AutoComplete
          id="task-tags"
          v-model="formData.tags"
          :suggestions="searchSuggestions.map(t => t.name)"
          :placeholder="t('tasks.add_tag_placeholder')"
          multiple
          class="w-full autocomplete-tags"
          @complete="handleTagSearch"
          :forceSelection="false"
          :pt="{ input: { onKeydown: onCreateDialogTagsKeydown } }"
          :appendTo="'self'"
        />
        <small class="field-hint">{{ t('tasks.tags_hint') }}</small>
        
        <!-- Popular tags -->
        <div class="popular-tags">
          <small class="popular-tags-label">{{ t('tasks.popular_tags') }}:</small>
          
          <!-- Skeleton loaders -->
          <div v-if="isLoadingPopular" class="popular-tags-list">
            <Skeleton v-for="i in 7" :key="i" width="4rem" height="1.75rem" borderRadius="16px" class="tag-skeleton" />
          </div>
          
          <!-- Popular tags chips -->
          <div v-else-if="popularTags.length > 0" class="popular-tags-list">
            <Chip
              v-for="tag in popularTags"
              :key="tag.id"
              :label="tag.name"
              :style="{ 
                backgroundColor: tag.color + '20',
                color: tag.color,
                border: `1px solid ${tag.color}40`,
                fontWeight: 600
              }"
              class="popular-tag-chip"
              @click="addPopularTag(tag)"
            />
          </div>
          
          <small v-else class="no-tags-hint">{{ t('tasks.no_popular_tags') }}</small>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <Button
          :label="t('common.cancel')"
          severity="secondary"
          text
          @click="handleClose"
          :disabled="isSubmitting"
        />
        <Button
          :label="t('common.save')"
          :loading="isSubmitting"
          @click="handleSubmit"
        />
      </div>
    </template>
  </Dialog>
</template>

<style scoped>
.dialog-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  width: 100%;
}

.dialog-header-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.dialog-header-content {
  flex: 1;
}

.dialog-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 0.25rem 0;
}

.dialog-subtitle {
  font-size: 0.875rem;
  color: #718096;
  margin: 0;
}

.task-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding: 1rem 0;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.field-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #2d3748;
}

.required {
  color: #ef4444;
  margin-left: 0.25rem;
}

.field-hint {
  font-size: 0.75rem;
  color: #a0aec0;
  margin-top: -0.25rem;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

/* PrimeVue component overrides */
:deep(.p-inputtext),
:deep(.p-textarea),
:deep(.p-dropdown),
:deep(.p-calendar-input-icon) {
  font-size: 0.9375rem;
}

:deep(.p-inputtext:focus),
:deep(.p-textarea:focus),
:deep(.p-dropdown:focus),
:deep(.p-calendar:focus),
:deep(.p-chips .p-chips-multiple-container:focus-within) {
  border-color: #667eea !important;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.2) !important;
  outline: none !important;
}

:deep(.p-inputtext),
:deep(.p-textarea),
:deep(.p-dropdown),
:deep(.p-calendar),
:deep(.p-chips),
:deep(.p-chips .p-chips-multiple-container),
:deep(.p-chips .p-chips-input-token input) {
  outline: none !important;
}

:deep(.p-inputtext:focus),
:deep(.p-inputtext:focus-visible),
:deep(.p-textarea:focus),
:deep(.p-textarea:focus-visible),
:deep(.p-dropdown:focus),
:deep(.p-dropdown:focus-visible),
:deep(.p-calendar:focus),
:deep(.p-calendar:focus-visible),
:deep(.p-chips:focus),
:deep(.p-chips:focus-visible),
:deep(.p-chips .p-chips-multiple-container:focus),
:deep(.p-chips .p-chips-multiple-container:focus-visible),
:deep(.p-chips .p-chips-input-token input:focus),
:deep(.p-chips .p-chips-input-token input:focus-visible) {
  outline: none !important;
}

:deep(.p-chips .p-chips-multiple-container) {
  padding: 0.5rem;
  gap: 0.5rem;
  width: 100%;
}

:deep(.p-chips .p-chips-token) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 0.375rem 0.75rem;
  border-radius: 8px;
  font-size: 0.875rem;
  color: white;
}

:deep(.p-chips .p-chips-token .p-chips-token-label) {
  color: white;
}

:deep(.p-chips .p-chips-token .p-chips-token-icon) {
  color: white;
}

:deep(.p-button) {
  font-weight: 600;
  padding: 0.75rem 1.5rem;
}

:deep(.p-button:not(.p-button-text)) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
}

:deep(.p-button:not(.p-button-text):hover) {
  background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
}

/* Quick Date & Time Selection Styles */
.quick-datetime-section {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 16px;
  padding: 1.25rem;
  border: 1px solid rgba(148, 163, 184, 0.1);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #334155;
}

.section-title i {
  color: #8b5cf6;
  font-size: 1.125rem;
}

.advanced-toggle {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  color: #64748b;
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.advanced-toggle:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #475569;
}

.quick-datetime-content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Day Selection Chips */
.day-chips {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.day-chip {
  flex: 1;
  min-width: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.875rem 1.25rem;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  color: #64748b;
  font-size: 0.9375rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
  overflow: hidden;
}

.day-chip::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
  opacity: 0;
  transition: opacity 0.3s;
}

.day-chip:hover {
  border-color: #cbd5e1;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.day-chip.active {
  color: white;
  border-color: transparent;
}

.day-chip.active::before {
  opacity: 1;
}

.day-chip i,
.day-chip span {
  position: relative;
  z-index: 1;
}

.day-chip i {
  font-size: 1.125rem;
}

/* Time Range Selector */
.time-range-selector {
  display: flex;
  align-items: flex-end;
  gap: 1rem;
  padding: 1rem;
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.time-input-group {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.time-label {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #64748b;
}

.time-label i {
  font-size: 0.875rem;
  color: #8b5cf6;
}

.time-input {
  padding: 0.75rem;
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  color: #334155;
  font-size: 1rem;
  font-weight: 600;
  transition: all 0.2s;
  cursor: pointer;
}

.time-input:hover {
  background: white;
  border-color: #cbd5e1;
}

.time-input:focus {
  outline: none;
  background: white;
  border-color: #8b5cf6;
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.time-arrow {
  display: flex;
  align-items: center;
  justify-content: center;
  padding-bottom: 0.5rem;
  color: #cbd5e1;
  font-size: 1.25rem;
}

/* Time Presets */
.time-presets {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.preset-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #64748b;
}

.time-preset {
  padding: 0.5rem 1rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  color: #64748b;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.time-preset:hover {
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(236, 72, 153, 0.1) 100%);
  border-color: rgba(139, 92, 246, 0.3);
  color: #8b5cf6;
  transform: scale(1.05);
}

.time-preset:active {
  transform: scale(0.98);
}

/* Responsive */
@media (max-width: 640px) {
  .form-row {
    grid-template-columns: 1fr;
  }

  .dialog-header-icon {
    width: 40px;
    height: 40px;
    font-size: 1.25rem;
  }

  .dialog-title {
    font-size: 1.25rem;
  }

  .day-chips {
    flex-direction: column;
  }

  .day-chip {
    min-width: unset;
  }

  .time-range-selector {
    flex-direction: column;
    align-items: stretch;
  }

  .time-arrow {
    transform: rotate(90deg);
    padding: 0.5rem 0;
  }

  .time-presets {
    justify-content: center;
  }
}

.autocomplete-tags :deep(.p-autocomplete-multiple-container) {
  width: 100%;
  padding: 0.4rem 0.5rem; /* inner spacing */
  border-radius: 12px; /* softer corners */
  border: 1px solid #e5e7eb; /* subtle border */
  transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

.autocomplete-tags :deep(.p-autocomplete-input) {
  width: 100%;
}

.popular-tags {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 0.75rem;
}

.popular-tags-label {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 500;
}

.popular-tags-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.popular-tag-chip {
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.75rem;
  padding: 0.375rem 0.75rem;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.popular-tag-chip:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.popular-tag-chip:active {
  transform: translateY(0);
}

.tag-skeleton {
  display: inline-block;
  margin-right: 0.5rem;
}

.no-tags-hint {
  font-size: 0.75rem;
  color: #94a3b8;
  font-style: italic;
}

/* Remove inner input border/outline inside AutoComplete */
.autocomplete-tags :deep(.p-inputtext) {
  border: 0 !important;
  box-shadow: none !important;
  outline: none !important;
  background: transparent !important;
}

/* Focus state */
.autocomplete-tags :deep(.p-inputwrapper-focus .p-autocomplete-multiple-container),
.autocomplete-tags :deep(.p-autocomplete-multiple-container.p-focus) {
  border-color: rgba(99, 102, 241, 0.55) !important;
  box-shadow: 0 0 0 5px rgba(99, 102, 241, 0.16) !important;
}

/* Empty results spacing */
/* Empty results spacing - overlay may live outside */
:deep(.p-autocomplete-panel) {
  border-radius: 12px;
  overflow: hidden;
}

:deep(.p-autocomplete-panel .p-autocomplete-empty-message) {
  padding: 0.9rem 1rem !important;
  color: #475569;
}

:deep(.p-autocomplete-panel .p-autocomplete-items .p-autocomplete-item) {
  padding: 0.55rem 0.9rem;
}
</style>

