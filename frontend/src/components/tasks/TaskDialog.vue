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

      <div class="form-row">
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
import { ref, reactive, watch, onMounted } from 'vue'
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
import type { Task, CreateTaskRequest, UpdateTaskRequest, Tag as TaskTag } from '@/types/task.types'

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

const form = reactive({
  title: '',
  description: '',
  status: TaskStatus.PENDING,
  priority: TaskPriority.MEDIUM,
  startDate: null as Date | null,
  dueDate: null as Date | null,
  tags: [] as string[]
})

const errors = reactive({
  title: ''
})

const statusOptions = [
  { label: t('tasks.status_pending'), value: TaskStatus.PENDING },
  { label: t('tasks.status_in_progress'), value: TaskStatus.IN_PROGRESS },
  { label: t('tasks.status_completed'), value: TaskStatus.COMPLETED }
]

const priorityOptions = [
  { label: t('tasks.priority_low'), value: TaskPriority.LOW },
  { label: t('tasks.priority_medium'), value: TaskPriority.MEDIUM },
  { label: t('tasks.priority_high'), value: TaskPriority.HIGH },
  { label: t('tasks.priority_urgent'), value: TaskPriority.URGENT }
]

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
  errors.title = ''
}

function validateForm(): boolean {
  errors.title = ''
  
  if (!form.title.trim()) {
    errors.title = t('tasks.title_required')
    return false
  }
  
  return true
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
        tags: form.tags.length > 0 ? form.tags : undefined
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
        parentTaskId: props.parentTaskId || undefined
      }
      
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
</style>
