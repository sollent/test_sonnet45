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
            class="w-full"
          />
        </div>
      </div>

      <div class="form-field">
        <label class="field-label">{{ t('tasks.tags') }}</label>
        <Chips
          v-model="form.tags"
          :placeholder="t('tasks.tags_placeholder')"
          class="w-full chips-full-width"
          separator=","
        />
        <small class="field-hint">{{ t('tasks.tags_help') }}</small>
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
import Chips from 'primevue/chips'
import Button from 'primevue/button'
import { useTaskStore } from '@/stores/task.store'
import { useToast } from '@/composables/useToast'
import { TaskStatus, TaskPriority } from '@/types/task.types'
import type { Task, CreateTaskRequest, UpdateTaskRequest } from '@/types/task.types'

const { t } = useI18n()
const taskStore = useTaskStore()
const { showSuccess, showError } = useToast()

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
    
    if (props.initialDate) {
      form.startDate = new Date(props.initialDate)
      const endDate = new Date(props.initialDate)
      endDate.setHours(endDate.getHours() + 1)
      form.dueDate = endDate
    }
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

.chips-full-width :deep(.p-chips-multiple-container) {
  width: 100%;
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

@media (max-width: 640px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
