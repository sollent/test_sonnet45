<template>
  <div class="create-task-form">
    <div class="form-field">
      <label>{{ t('tasks.title') }} *</label>
      <InputText
        v-model="form.title"
        :placeholder="t('tasks.title_placeholder')"
        class="w-full"
        :class="{ 'p-invalid': errors.title }"
      />
      <small v-if="errors.title" class="p-error">{{ errors.title }}</small>
    </div>

    <div class="form-field">
      <label>{{ t('tasks.description') }}</label>
      <Textarea
        v-model="form.description"
        :placeholder="t('tasks.description_placeholder')"
        rows="3"
        class="w-full"
      />
    </div>

    <div class="form-row">
      <div class="form-field">
        <label>{{ t('tasks.status') }}</label>
        <Dropdown
          v-model="form.status"
          :options="statusOptions"
          optionLabel="label"
          optionValue="value"
          class="w-full"
        />
      </div>

      <div class="form-field">
        <label>{{ t('tasks.priority') }}</label>
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
        <label>{{ t('tasks.start_date') }}</label>
        <Calendar
          v-model="form.startDate"
          :placeholder="t('common.select_date')"
          showTime
          hourFormat="24"
          class="w-full"
        />
      </div>

      <div class="form-field">
        <label>{{ t('tasks.due_date') }}</label>
        <Calendar
          v-model="form.dueDate"
          :placeholder="t('common.select_date')"
          showTime
          hourFormat="24"
          class="w-full"
        />
      </div>
    </div>

    <div class="form-field">
      <label>{{ t('tasks.tags') }}</label>
      <Chips
        v-model="form.tags"
        :placeholder="t('tasks.tags_placeholder')"
        class="w-full chips-full-width"
      />
      <small class="help-text">{{ t('tasks.tags_help') }}</small>
    </div>

    <div class="form-actions">
      <Button
        :label="t('common.cancel')"
        severity="secondary"
        @click="handleCancel"
      />
      <Button
        :label="t('common.save')"
        :loading="isSubmitting"
        @click="handleSubmit"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import Chips from 'primevue/chips'
import Button from 'primevue/button'
import { useTaskStore } from '@/stores/task.store'
import { TaskStatus, TaskPriority } from '@/types/task.types'
import type { CreateTaskRequest } from '@/types/task.types'

const { t } = useI18n()
const taskStore = useTaskStore()

const props = defineProps<{
  initialDate?: Date | null
  parentTaskId?: number | null
}>()

const emit = defineEmits<{
  'task-created': [task: any]
  'cancel': []
}>()

const form = reactive({
  title: '',
  description: '',
  status: TaskStatus.PENDING,
  priority: TaskPriority.MEDIUM,
  startDate: null as Date | null,
  dueDate: null as Date | null,
  tags: [] as string[],
  parentTaskId: props.parentTaskId || null
})

const errors = reactive({
  title: ''
})

const isSubmitting = ref(false)

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
    const taskData: CreateTaskRequest = {
      title: form.title,
      description: form.description || undefined,
      status: form.status,
      priority: form.priority,
      startDate: form.startDate?.toISOString() || undefined,
      dueDate: form.dueDate?.toISOString() || undefined,
      tags: form.tags.length > 0 ? form.tags : undefined,
      parentTaskId: form.parentTaskId || undefined
    }
    
    const task = await taskStore.createTask(taskData)
    emit('task-created', task)
  } catch (error) {
    // Error is handled by the store
  } finally {
    isSubmitting.value = false
  }
}

function handleCancel() {
  emit('cancel')
}

onMounted(() => {
  if (props.initialDate) {
    form.startDate = props.initialDate
    form.dueDate = new Date(props.initialDate)
    form.dueDate.setHours(form.dueDate.getHours() + 1)
  }
})
</script>

<style scoped>
.create-task-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-field label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #475569;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.w-full {
  width: 100%;
}

.chips-full-width :deep(.p-chips-multiple-container) {
  width: 100%;
}

.help-text {
  font-size: 0.75rem;
  color: #64748b;
}

.p-error {
  color: #ef4444;
  font-size: 0.75rem;
}

@media (max-width: 640px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
