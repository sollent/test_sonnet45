<template>
  <Dialog
    v-model:visible="visible"
    :modal="true"
    :style="{ width: '600px' }"
    :breakpoints="{ '960px': '75vw', '640px': '95vw' }"
    :dismissableMask="true"
    :closeOnEscape="true"
  >
    <template #header>
      <div class="dialog-header">
        <i class="pi pi-plus-circle dialog-icon" />
        <div>
          <h3 class="dialog-title">{{ isEditMode ? t('tasks.edit_task') : t('tasks.new_task') }}</h3>
          <p class="dialog-subtitle">
            {{ isEditMode ? t('tasks.edit_task_description') : t('tasks.create_task_description') }}
          </p>
        </div>
      </div>
    </template>

    <div class="task-form">
      <div class="form-field">
        <label class="field-label">
          {{ t('tasks.title') }} <span class="required">*</span>
        </label>
        <InputText
          v-model="form.title"
          :placeholder="t('tasks.title_placeholder')"
          :class="{ 'p-invalid': errors.title }"
          class="w-full"
          autofocus
        />
        <small v-if="errors.title" class="p-error">{{ errors.title }}</small>
      </div>

      <div class="form-field">
        <label class="field-label">{{ t('tasks.description') }}</label>
        <Textarea
          v-model="form.description"
          :placeholder="t('tasks.description_placeholder')"
          rows="3"
          class="w-full"
        />
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
            <label class="field-label">{{ t('tasks.start_date') }}</label>
            <Calendar
              v-model="form.startDate"
              :placeholder="t('common.select_date')"
              showTime
              hourFormat="24"
              dateFormat="dd.mm.yy"
              :stepMinute="10"
              class="w-full"
            />
          </div>

          <div class="form-field">
            <label class="field-label">{{ t('tasks.due_date') }}</label>
            <Calendar
              v-model="form.dueDate"
              :placeholder="t('common.select_date')"
              showTime
              hourFormat="24"
              dateFormat="dd.mm.yy"
              :stepMinute="10"
              class="w-full"
            />
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-field">
          <label class="field-label">{{ t('tasks.status') }}</label>
          <Dropdown
            v-model="form.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
        </div>

        <div class="form-field">
          <label class="field-label">{{ t('tasks.priority') }}</label>
          <Dropdown
            v-model="form.priority"
            :options="priorityOptions"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
        </div>
      </div>

      <div class="form-field">
        <label class="field-label">{{ t('tasks.tags') }}</label>
        <AutoComplete
          v-model="form.tags"
          :suggestions="searchSuggestions.map(t => t.name)"
          :placeholder="t('tasks.tags_placeholder')"
          multiple
          class="w-full autocomplete-tags"
          @complete="handleTagSearch"
          :forceSelection="false"
          :pt="{ input: { onKeydown: onTagsKeydown } }"
          :appendTo="'self'"
        />
        <small class="field-hint">{{ t('tasks.tags_help') }}</small>
        
        <!-- Popular tags -->
        <div v-if="!isEditMode" class="popular-tags">
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

      <!-- File Attachments (всегда доступны) -->
      <div class="form-field">
        <label class="field-label">
          <i class="pi pi-paperclip"></i>
          {{ t('tasks.attachments') }}
        </label>
        <SimpleFileUploader 
          :attachments="localAttachments"
          @upload="handleFileUpload"
          @delete="handleFileDelete"
          @view="handleFileView"
        />
      </div>

      <!-- Recurrence Settings (не показываем для подзадач и при редактировании) -->
      <div v-if="!parentTaskId && !isEditMode" class="form-field recurrence-field">
        <RecurrenceSettings 
          v-model="recurrenceSettings"
        />
      </div>

      <div v-if="parentTaskId" class="parent-info">
        <i class="pi pi-info-circle" />
        {{ t('tasks.creating_subtask') }}
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <Button
          :label="t('common.cancel')"
          severity="secondary"
          outlined
          @click="handleCancel"
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

<script setup lang="ts">
import { ref, reactive, watch, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import AutoComplete from 'primevue/autocomplete'
import Chip from 'primevue/chip'
import Skeleton from 'primevue/skeleton'
import Button from 'primevue/button'
import { useTaskStore } from '@/stores/task.store'
import { useToast } from '@/composables/useToast'
import { useTagSuggestions } from '@/composables/useTagSuggestions'
import { TaskStatus, TaskPriority } from '@/types/task.types'
import type { Task, CreateTaskRequest, UpdateTaskRequest, Tag as TaskTag, TaskAttachment, RecurrenceSettings as RecurrenceSettingsType } from '@/types/task.types'
import SimpleFileUploader from '@/components/ui/SimpleFileUploader.vue'
import RecurrenceSettings from '@/components/tasks/RecurrenceSettings.vue'
import mediaService from '@/services/media.service'

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

const props = defineProps<{
  modelValue: boolean
  task?: Task | null
  parentTaskId?: number | null
  initialDate?: Date | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'task-saved': [task: Task]
}>()

const visible = ref(props.modelValue)
const isEditMode = ref(false)
const isSubmitting = ref(false)
const localAttachments = ref<TaskAttachment[]>([])
const pendingFiles = ref<File[]>([]) // Файлы ожидающие загрузки
const recurrenceSettings = ref<RecurrenceSettingsType>({
  enabled: false,
  rule: undefined
})

const form = reactive({
  title: '',
  description: '',
  status: TaskStatus.PENDING,
  priority: TaskPriority.MEDIUM,
  startDate: null as Date | null,
  dueDate: null as Date | null,
  tags: [] as string[],
  mediaIds: [] as number[]
})

// Quick date/time selection
const selectedDay = ref<'today' | 'tomorrow' | 'dayAfter' | null>(null)
const quickTimeStart = ref<string>('')
const quickTimeEnd = ref<string>('')
const showAdvancedDate = ref(false)

const errors = reactive({
  title: ''
})

const statusOptions = computed(() => [
  { label: t('tasks.status_pending'), value: TaskStatus.PENDING },
  { label: t('tasks.status_in_progress'), value: TaskStatus.IN_PROGRESS },
  { label: t('tasks.status_completed'), value: TaskStatus.COMPLETED }
])

const priorityOptions = computed(() => [
  { label: t('tasks.priority_low'), value: TaskPriority.LOW },
  { label: t('tasks.priority_medium'), value: TaskPriority.MEDIUM },
  { label: t('tasks.priority_high'), value: TaskPriority.HIGH },
  { label: t('tasks.priority_urgent'), value: TaskPriority.URGENT }
])

watch(() => props.modelValue, (newVal) => {
  visible.value = newVal
  if (newVal) {
    initializeForm()
  }
})

watch(visible, (newVal) => {
  emit('update:modelValue', newVal)
})

function initializeForm() {
  if (props.task) {
    isEditMode.value = true
    form.title = props.task.title
    form.description = props.task.description || ''
    form.status = props.task.status
    form.priority = props.task.priority
    form.startDate = props.task.startDate ? new Date(props.task.startDate) : null
    form.dueDate = props.task.dueDate ? new Date(props.task.dueDate) : null
    form.tags = props.task.tags?.map(tag => tag.name) || []
    localAttachments.value = props.task.attachments || []
    form.mediaIds = props.task.attachments?.map(a => a.id) || []
  } else {
    isEditMode.value = false
    resetForm()
    
    // Initialize tag suggestions for new tasks
    initializeTagSuggestions(7)
    
    if (props.initialDate) {
      form.startDate = new Date(props.initialDate)
      const endDate = new Date(props.initialDate)
      endDate.setHours(endDate.getHours() + 1)
      form.dueDate = endDate
    }
  }
}

function addPopularTag(tag: TaskTag) {
  const tagName = tag.name.trim()
  
  // Check if tag already exists (case-insensitive)
  const exists = form.tags.some(t => t.toLowerCase() === tagName.toLowerCase())
  
  if (!exists) {
    form.tags.push(tagName)
  }
}

async function handleTagSearch(event: any) {
  const query = event.query?.trim()
  if (query && query.length > 0) {
    await searchTags(query)
  }
}

function onTagsKeydown(event: KeyboardEvent) {
  if (event.key !== 'Enter') return
  const target = event.target as HTMLInputElement | null
  const value = target?.value?.trim()
  if (!value) return
  const exists = form.tags.some(t => t.toLowerCase() === value.toLowerCase())
  if (!exists) {
    form.tags.push(value)
  }
  if (target) {
    // Clear the input without affecting selected tags
    target.value = ''
  }
}

function resetForm() {
  form.title = ''
  form.description = ''
  form.status = TaskStatus.PENDING
  form.priority = TaskPriority.MEDIUM
  form.startDate = null
  form.dueDate = null
  form.tags = []
  form.mediaIds = []
  errors.title = ''
  localAttachments.value = []
  selectedDay.value = null
  quickTimeStart.value = ''
  quickTimeEnd.value = ''
  showAdvancedDate.value = false
  pendingFiles.value = []
  recurrenceSettings.value = {
    enabled: false,
    rule: undefined
  }
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

  // Apply time if set
  if (quickTimeStart.value) {
    const [hours, minutes] = quickTimeStart.value.split(':').map(Number)
    const startDate = new Date(baseDate)
    startDate.setHours(hours, minutes, 0, 0)
    form.startDate = startDate
  }

  if (quickTimeEnd.value) {
    const [hours, minutes] = quickTimeEnd.value.split(':').map(Number)
    const endDate = new Date(baseDate)
    endDate.setHours(hours, minutes, 0, 0)
    form.dueDate = endDate
  }
}

function onQuickTimeChange() {
  updateDatesFromQuickSelection()
}

// Watch for date changes to sync with quick selection
watch(() => [form.startDate, form.dueDate], ([newStart, newEnd]) => {
  if (!newStart && !newEnd) {
    selectedDay.value = null
    quickTimeStart.value = ''
    quickTimeEnd.value = ''
    return
  }

  // Check if dates match quick selection
  if (newStart) {
    const start = new Date(newStart)
    const today = new Date()
    today.setHours(0, 0, 0, 0)

    const tomorrow = new Date(today)
    tomorrow.setDate(tomorrow.getDate() + 1)

    const dayAfter = new Date(today)
    dayAfter.setDate(dayAfter.getDate() + 2)

    start.setHours(0, 0, 0, 0)

    if (start.getTime() === today.getTime()) {
      selectedDay.value = 'today'
    } else if (start.getTime() === tomorrow.getTime()) {
      selectedDay.value = 'tomorrow'
    } else if (start.getTime() === dayAfter.getTime()) {
      selectedDay.value = 'dayAfter'
    } else {
      selectedDay.value = null
    }

    // Update time
    const startTime = new Date(newStart)
    quickTimeStart.value = `${String(startTime.getHours()).padStart(2, '0')}:${String(startTime.getMinutes()).padStart(2, '0')}`
  }

  if (newEnd) {
    const endTime = new Date(newEnd)
    quickTimeEnd.value = `${String(endTime.getHours()).padStart(2, '0')}:${String(endTime.getMinutes()).padStart(2, '0')}`
  }
})

function validateForm(): boolean {
  errors.title = ''
  
  if (!form.title.trim()) {
    errors.title = t('tasks.title_required')
    return false
  }
  
  return true
}

// Handle file upload - загружаем файл СРАЗУ и получаем ID
async function handleFileUpload(file: File) {
  try {
    console.log('Uploading file:', file.name)
    const mediaObject = await mediaService.uploadFile(file)
    console.log('Media object created:', mediaObject)
    localAttachments.value.push(mediaObject)
    form.mediaIds.push(mediaObject.id)
    console.log('Current mediaIds:', form.mediaIds)
    showSuccess(t('tasks.file_uploaded'))
  } catch (error: any) {
    console.error('Upload error:', error)
    showError(error.message || t('tasks.upload_error'))
  }
}

// Handle file delete
async function handleFileDelete(attachmentId: number) {
  try {
    await mediaService.deleteMedia(attachmentId)
    localAttachments.value = localAttachments.value.filter(a => a.id !== attachmentId)
    form.mediaIds = form.mediaIds.filter(id => id !== attachmentId)
    showSuccess(t('tasks.file_deleted'))
  } catch (error: any) {
    showError(error.message || t('tasks.delete_error'))
  }
}

// Handle file view
function handleFileView(attachment: TaskAttachment) {
  const url = mediaService.getFileUrl(attachment.filePath)
  window.open(url, '_blank')
}

async function handleSubmit() {
  if (!validateForm()) return
  
  isSubmitting.value = true
  
  try {
    let result: Task
    
    if (isEditMode.value && props.task) {
      const updateData: UpdateTaskRequest = {
        title: form.title,
        description: form.description || undefined,
        status: form.status,
        priority: form.priority,
        startDate: form.startDate?.toISOString() || undefined,
        dueDate: form.dueDate?.toISOString() || undefined,
        tags: form.tags.length > 0 ? form.tags : undefined,
        mediaIds: form.mediaIds.length > 0 ? form.mediaIds : undefined
      }
      
      result = await taskStore.updateTask(props.task.id, updateData)
      showSuccess(t('tasks.task_updated'))
    } else {
      const createData: CreateTaskRequest = {
        title: form.title,
        description: form.description || undefined,
        status: form.status,
        priority: form.priority,
        startDate: form.startDate?.toISOString() || undefined,
        dueDate: form.dueDate?.toISOString() || undefined,
        tags: form.tags.length > 0 ? form.tags : undefined,
        mediaIds: form.mediaIds,
        parentTaskId: props.parentTaskId || undefined,
        recurrence: recurrenceSettings.value.enabled ? recurrenceSettings.value.rule || null : null
      }
      
      console.log('Creating task with data:', createData)
      console.log('Media IDs:', form.mediaIds)
      
      result = await taskStore.createTask(createData)
      showSuccess(t('tasks.task_created'))
    }
    
    emit('task-saved', result)
    visible.value = false
    resetForm()
  } catch (error: any) {
    showError(error.message || t('errors.unknown_error'))
  } finally {
    isSubmitting.value = false
  }
}

function handleCancel() {
  visible.value = false
  resetForm()
}

onMounted(() => {
  if (props.modelValue) {
    initializeForm()
  }
})
</script>

<style scoped>
.dialog-header {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.dialog-icon {
  font-size: 1.5rem;
  color: #6366f1;
}

.dialog-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
}

.dialog-subtitle {
  margin: 0.25rem 0 0;
  font-size: 0.875rem;
  color: #64748b;
}

.task-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding: 0.5rem 0;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #475569;
}

.required {
  color: #ef4444;
}

.field-hint {
  font-size: 0.75rem;
  color: #64748b;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.w-full {
  width: 100%;
}

.autocomplete-tags :deep(.p-autocomplete-multiple-container) {
  width: 100%;
  padding: 0.5rem 0.625rem; /* inner spacing */
  border-radius: 12px; /* softer corners */
  border: 1px solid #e5e7eb; /* subtle border */
  transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

.autocomplete-tags :deep(.p-autocomplete-input) {
  width: 100%;
}

/* Remove inner input border/outline inside AutoComplete */
.autocomplete-tags :deep(.p-inputtext) {
  border: 0 !important;
  box-shadow: none !important;
  outline: none !important;
  background: transparent !important;
}

/* Focus state on container instead of harsh blue outline */
.autocomplete-tags :deep(.p-inputwrapper-focus .p-autocomplete-multiple-container),
.autocomplete-tags :deep(.p-autocomplete-multiple-container.p-focus) {
  border-color: rgba(99, 102, 241, 0.55) !important;
  box-shadow: 0 0 0 5px rgba(99, 102, 241, 0.16) !important;
}

/* Better spacing for empty results message */
/* Empty results spacing - overlay can be outside component tree */
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

.p-error {
  color: #ef4444;
  font-size: 0.75rem;
}

.parent-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: #f0f9ff;
  border: 1px solid #bfdbfe;
  border-radius: 6px;
  color: #1e40af;
  font-size: 0.875rem;
}

.recurrence-field {
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
  margin-top: 1rem;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.popular-tags {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 0.5rem;
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

@media (max-width: 640px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}

/* Quick Date & Time Selection Styles */
.quick-datetime-section {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 16px;
  padding: 1.25rem;
  margin: 1.25rem 0;
  border: 1px solid rgba(148, 163, 184, 0.1);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  gap: 1rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #334155;
  min-width: 0; /* Allow text to shrink */
}

.section-title i {
  color: #8b5cf6;
  font-size: 1.125rem;
  flex-shrink: 0;
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
  white-space: nowrap;
  flex-shrink: 0;
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
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.125rem;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  color: #475569;
  font-size: 0.9375rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  flex: 1;
  min-width: 120px;
  justify-content: center;
}

.day-chip:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.day-chip.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: transparent;
  color: white;
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
}

.day-chip.active i {
  color: white;
}

.day-chip i {
  font-size: 1.125rem;
  color: #8b5cf6;
}

/* Time Range Selector */
.time-range-selector {
  display: flex;
  align-items: center;
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
  font-weight: 500;
  color: #64748b;
}

.time-label i {
  font-size: 0.875rem;
  color: #8b5cf6;
}

.time-input {
  padding: 0.625rem 0.875rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 500;
  color: #1e293b;
  background: #f8fafc;
  transition: all 0.2s;
}

.time-input:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.time-arrow {
  color: #cbd5e1;
  font-size: 1.125rem;
  padding: 0 0.5rem;
}

/* Quick Time Presets */
.time-presets {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  flex-wrap: wrap;
}

.preset-label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: #64748b;
  margin-right: 0.25rem;
}

.time-preset {
  padding: 0.375rem 0.75rem;
  background: rgba(102, 126, 234, 0.08);
  border: 1px solid rgba(102, 126, 234, 0.2);
  border-radius: 8px;
  color: #667eea;
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.time-preset:hover {
  background: rgba(102, 126, 234, 0.15);
  border-color: rgba(102, 126, 234, 0.3);
  transform: translateY(-1px);
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .quick-datetime-section {
    padding: 1rem;
  }

  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }

  .section-title {
    font-size: 0.875rem;
  }

  .advanced-toggle {
    width: 100%;
    justify-content: center;
    padding: 0.5rem;
  }

  .advanced-toggle span {
    display: inline-block;
  }

  /* Keep days horizontal but make them smaller */
  .day-chips {
    display: flex;
    flex-direction: row;
    gap: 0.5rem;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 0.25rem;
  }

  .day-chip {
    flex: 0 0 auto;
    min-width: auto;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
  }

  .day-chip i {
    display: none; /* Hide icons on mobile to save space */
  }

  /* Keep time selector horizontal */
  .time-range-selector {
    flex-direction: row;
    padding: 0.75rem;
    gap: 0.5rem;
  }

  .time-input-group {
    min-width: 0;
  }

  .time-label {
    font-size: 0.75rem;
  }

  .time-label i {
    display: none; /* Hide icons to save space */
  }

  .time-label span {
    white-space: nowrap;
  }

  .time-input {
    padding: 0.5rem 0.375rem;
    font-size: 0.875rem;
  }

  .time-arrow {
    font-size: 1rem;
    padding: 0 0.25rem;
  }

  /* Time presets adjustments */
  .time-presets {
    gap: 0.5rem;
  }

  .preset-label {
    display: none; /* Hide label on mobile */
  }

  .time-preset {
    font-size: 0.75rem;
    padding: 0.375rem 0.625rem;
  }
}
</style>
