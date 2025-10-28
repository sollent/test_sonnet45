<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/stores/task.store'
import { useToast } from '@/composables/useToast'
import Sidebar from 'primevue/sidebar'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import Chip from 'primevue/chip'
import Chips from 'primevue/chips'
import Divider from 'primevue/divider'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import { TaskStatus, TaskPriority, TASK_PRIORITY_CONFIG, TASK_STATUS_CONFIG, type Task, type UpdateTaskRequest } from '@/types/task.types'

interface Props {
  visible: boolean
  task: Task | null
}

interface Emits {
  (e: 'update:visible', value: boolean): void
  (e: 'task-updated'): void
  (e: 'task-deleted'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const { t } = useI18n()
const taskStore = useTaskStore()
const { showSuccess, showError } = useToast()
const confirm = useConfirm()

// Local state
const editMode = ref(false)
const isSubmitting = ref(false)
const newSubtaskTitle = ref('')
const isAddingSubtask = ref(false)

// Edit form data
const editData = ref<UpdateTaskRequest>({})

// Computed
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

const priorityConfig = computed(() => {
  if (!props.task) return null
  return TASK_PRIORITY_CONFIG[props.task.priority]
})

const statusConfig = computed(() => {
  if (!props.task) return null
  return TASK_STATUS_CONFIG[props.task.status]
})

const completionPercentage = computed(() => {
  if (!props.task || props.task.subtasks.length === 0) {
    return props.task?.isCompleted ? 100 : 0
  }
  return Math.round(props.task.completionProgress)
})

// Watch for task changes
watch(() => props.task, (newTask, oldTask) => {
  // Only reset if the task ID changes, or if there was no task before.
  if (newTask && (!oldTask || newTask.id !== oldTask.id)) {
    resetEditData()
    editMode.value = false
  }
}, { immediate: true })

// Methods
function resetEditData() {
  if (!props.task) return
  
  editData.value = {
    title: props.task.title,
    description: props.task.description,
    status: props.task.status,
    priority: props.task.priority,
    startDate: props.task.startDate,
    dueDate: props.task.dueDate,
    tags: props.task.tags.map(tag => tag.name)
  }
}

function toggleEditMode() {
  if (editMode.value) {
    resetEditData()
  }
  editMode.value = !editMode.value
}

async function handleSave() {
  if (!props.task) return

  isSubmitting.value = true

  try {
    await taskStore.updateTask(props.task.id, editData.value)
    showSuccess(t('tasks.task_updated'))
    editMode.value = false
    emit('task-updated')
  } catch (error: any) {
    showError(error.message || t('errors.unknown_error'))
  } finally {
    isSubmitting.value = false
  }
}

async function handleToggleComplete() {
  if (!props.task) return

  try {
    await taskStore.toggleTaskCompletion(props.task.id)
    showSuccess(t('tasks.task_updated'))
    emit('task-updated')
  } catch (error: any) {
    showError(error.message || t('errors.unknown_error'))
  }
}

async function handleDelete() {
  if (!props.task) return

  confirm.require({
    message: t('tasks.delete_task'),
    header: t('common.confirm'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await taskStore.deleteTask(props.task!.id)
        showSuccess(t('tasks.task_deleted'))
        emit('task-deleted')
        emit('update:visible', false)
      } catch (error: any) {
        showError(error.message || t('errors.unknown_error'))
      }
    }
  })
}

async function handleAddSubtask() {
  if (!props.task || !newSubtaskTitle.value.trim()) return

  isAddingSubtask.value = true

  try {
    await taskStore.createTask({
      title: newSubtaskTitle.value,
      parentTaskId: props.task.id,
      status: TaskStatus.PENDING,
      priority: TaskPriority.MEDIUM
    })
    
    // Refresh task details
    await taskStore.fetchTask(props.task.id)
    
    newSubtaskTitle.value = ''
    showSuccess(t('tasks.task_created'))
    emit('task-updated')
  } catch (error: any) {
    showError(error.message || t('errors.unknown_error'))
  } finally {
    isAddingSubtask.value = false
  }
}

async function handleToggleSubtask(subtaskId: number) {
  try {
    await taskStore.toggleTaskCompletion(subtaskId)
    
    // Refresh parent task
    if (props.task) {
      await taskStore.fetchTask(props.task.id)
    }
    emit('task-updated')
  } catch (error: any) {
    showError(error.message || t('errors.unknown_error'))
  }
}

function formatDate(dateString: string | null): string {
  if (!dateString) return t('tasks.no_due_date')
  
  const date = new Date(dateString)
  return date.toLocaleString('ru-RU', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function handleClose() {
  editMode.value = false
  emit('update:visible', false)
}
</script>

<template>
  <Sidebar
    :visible="visible"
    position="right"
    :style="{ width: '90vw', maxWidth: '500px' }"
    :showCloseIcon="false"
    @update:visible="(val) => emit('update:visible', val)"
  >
    <template #header>
      <div class="drawer-header">
        <h3 class="drawer-title">{{ t('tasks.task_details') }}</h3>
        <div class="drawer-actions">
          <Button
            v-if="!editMode"
            icon="pi pi-pencil"
            severity="secondary"
            text
            rounded
            @click="toggleEditMode"
            :aria-label="t('tasks.edit_task')"
          />
          <Button
            icon="pi pi-times"
            severity="secondary"
            text
            rounded
            @click="handleClose"
            :aria-label="t('common.close')"
          />
        </div>
      </div>
    </template>

    <div v-if="task" class="task-details">
      <!-- Priority and Status Badges -->
      <div class="badges-row">
        <Chip
          :label="t(`tasks.priority_${task.priority}`)"
          :style="{ 
            backgroundColor: priorityConfig?.color + '20',
            color: priorityConfig?.color,
            fontWeight: 600
          }"
        />
        <Chip
          :label="t(`tasks.status_${task.status}`)"
          :style="{ 
            backgroundColor: statusConfig?.color + '20',
            color: statusConfig?.color,
            fontWeight: 600
          }"
        />
      </div>

      <!-- Title -->
      <div class="detail-section">
        <label class="detail-label">{{ t('tasks.task_title') }}</label>
        <InputText
          v-if="editMode"
          v-model="editData.title"
          class="w-full"
          :placeholder="t('tasks.title_placeholder')"
        />
        <h2 v-else class="task-title">{{ task.title }}</h2>
      </div>

      <!-- Description -->
      <div class="detail-section">
        <label class="detail-label">{{ t('tasks.task_description') }}</label>
        <Textarea
          v-if="editMode"
          v-model="editData.description"
          class="w-full"
          rows="4"
          autoResize
          :placeholder="t('tasks.description_placeholder')"
        />
        <p v-else-if="task.description" class="task-description">
          {{ task.description }}
        </p>
        <p v-else class="text-muted">{{ t('tasks.description_placeholder') }}</p>
      </div>

      <Divider />

      <!-- Dates -->
      <div class="detail-section">
        <div class="date-row">
          <div class="date-item">
            <i class="pi pi-calendar-plus" />
            <div>
              <label class="detail-label-small">{{ t('tasks.start_date') }}</label>
              <Calendar
                v-if="editMode"
                v-model="editData.startDate"
                showTime
                hourFormat="24"
                :placeholder="t('common.select_date')"
                class="w-full"
                dateFormat="dd.mm.yy"
              />
              <p v-else class="date-value">{{ formatDate(task.startDate) }}</p>
            </div>
          </div>
          
          <div class="date-item">
            <i class="pi pi-calendar-minus" :class="{ 'text-danger': task.isOverdue }" />
            <div>
              <label class="detail-label-small">{{ t('tasks.due_date') }}</label>
              <Calendar
                v-if="editMode"
                v-model="editData.dueDate"
                showTime
                hourFormat="24"
                :placeholder="t('common.select_date')"
                class="w-full"
                dateFormat="dd.mm.yy"
              />
              <p v-else class="date-value" :class="{ 'text-danger': task.isOverdue }">
                {{ formatDate(task.dueDate) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Status and Priority Selectors in Edit Mode -->
      <div v-if="editMode" class="detail-section">
        <div class="form-row">
          <div>
            <label class="detail-label-small">{{ t('tasks.task_status') }}</label>
          <Dropdown
            v-model="editData.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
          </div>
          <div>
            <label class="detail-label-small">{{ t('tasks.task_priority') }}</label>
          <Dropdown
            v-model="editData.priority"
            :options="priorityOptions"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
          </div>
        </div>
      </div>

      <Divider />

      <!-- Tags -->
      <div class="detail-section">
        <label class="detail-label">{{ t('tasks.tags') }}</label>
        <div v-if="editMode">
          <Chips
            v-model="editData.tags"
            :placeholder="t('tasks.add_tag_placeholder')"
            separator=","
            class="w-full"
          />
        </div>
        <div v-else class="tags-container">
          <Chip
            v-for="tag in task.tags"
            :key="tag.id"
            :label="tag.name"
            :style="{ 
              backgroundColor: tag.color + '20',
              color: tag.color,
              fontWeight: 600
            }"
          />
        </div>
      </div>

      <Divider />

      <!-- Subtasks -->
      <div class="detail-section">
        <div class="subtasks-header">
          <label class="detail-label">
            {{ t('tasks.subtasks') }}
            <span v-if="task.subtasks.length > 0" class="subtasks-count">
              {{ task.subtasks.filter(s => s.isCompleted).length }} / {{ task.subtasks.length }}
            </span>
          </label>
          <div v-if="task.subtasks.length > 0" class="progress-bar">
            <div class="progress-fill" :style="{ width: completionPercentage + '%' }" />
          </div>
        </div>

        <!-- Subtasks List -->
        <div v-if="task.subtasks.length > 0" class="subtasks-list">
          <div
            v-for="subtask in task.subtasks"
            :key="subtask.id"
            class="subtask-item"
          >
            <button
              class="subtask-checkbox"
              :class="{ 'checked': subtask.isCompleted }"
              @click="handleToggleSubtask(subtask.id)"
            >
              <i v-if="subtask.isCompleted" class="pi pi-check" />
            </button>
            <span class="subtask-title" :class="{ 'completed': subtask.isCompleted }">
              {{ subtask.title }}
            </span>
          </div>
        </div>

        <!-- Add Subtask -->
        <div class="add-subtask-form">
          <InputText
            v-model="newSubtaskTitle"
            :placeholder="t('tasks.add_subtask')"
            class="w-full"
            @keyup.enter="handleAddSubtask"
          />
          <Button
            icon="pi pi-plus"
            severity="secondary"
            @click="handleAddSubtask"
            :loading="isAddingSubtask"
            :disabled="!newSubtaskTitle.trim()"
          />
        </div>
      </div>

      <Divider />

      <!-- Metadata -->
      <div class="detail-section metadata">
        <div class="metadata-item">
          <i class="pi pi-clock" />
          <span>{{ t('profile.created_at') }}: {{ formatDate(task.createdAt) }}</span>
        </div>
        <div class="metadata-item">
          <i class="pi pi-refresh" />
          <span>{{ t('profile.updated_at') }}: {{ formatDate(task.updatedAt) }}</span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="action-buttons">
        <template v-if="editMode">
          <Button
            :label="t('common.cancel')"
            severity="secondary"
            outlined
            @click="toggleEditMode"
            class="w-full"
          />
          <Button
            :label="t('common.save')"
            @click="handleSave"
            :loading="isSubmitting"
            class="w-full"
          />
        </template>
        <template v-else>
          <Button
            :label="task.isCompleted ? t('tasks.mark_incomplete') : t('tasks.mark_complete')"
            :icon="task.isCompleted ? 'pi pi-times-circle' : 'pi pi-check-circle'"
            :severity="task.isCompleted ? 'secondary' : 'success'"
            outlined
            @click="handleToggleComplete"
            class="w-full"
          />
          <Button
            :label="t('tasks.delete_task')"
            icon="pi pi-trash"
            severity="danger"
            outlined
            @click="handleDelete"
            class="w-full"
          />
        </template>
      </div>
    </div>

    <ConfirmDialog />
  </Sidebar>
</template>

<style scoped>
.drawer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  padding-right: 1rem;
}

.drawer-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
}

.drawer-actions {
  display: flex;
  gap: 0.5rem;
}

.task-details {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding: 1rem;
}

.badges-row {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.detail-section {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.detail-label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #a0aec0;
}

.detail-label-small {
  font-size: 0.75rem;
  font-weight: 600;
  color: #718096;
  display: block;
  margin-bottom: 0.25rem;
}

.task-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
  line-height: 1.4;
}

.task-description {
  font-size: 0.9375rem;
  color: #4a5568;
  line-height: 1.6;
  margin: 0;
  white-space: pre-wrap;
}

.text-muted {
  color: #a0aec0;
  font-style: italic;
}

.date-row {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.date-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}

.date-item > i {
  font-size: 1.25rem;
  color: #667eea;
  margin-top: 0.25rem;
}

.date-item > div {
  flex: 1;
}

.date-value {
  font-size: 0.9375rem;
  color: #2d3748;
  font-weight: 500;
  margin: 0;
}

.text-danger {
  color: #ef4444 !important;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.tags-container {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.subtasks-header {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.subtasks-count {
  font-size: 0.875rem;
  color: #667eea;
  margin-left: 0.5rem;
  font-weight: 600;
}

.progress-bar {
  height: 6px;
  background: #e2e8f0;
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981 0%, #059669 100%);
  transition: width 0.3s ease;
}

.subtasks-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.subtask-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: #f7fafc;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.subtask-item:hover {
  background: #edf2f7;
}

.subtask-checkbox {
  width: 24px;
  height: 24px;
  border: 2px solid #cbd5e0;
  border-radius: 6px;
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.subtask-checkbox:hover {
  border-color: #667eea;
}

.subtask-checkbox.checked {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border-color: #10b981;
  color: white;
}

.subtask-title {
  flex: 1;
  font-size: 0.9375rem;
  color: #2d3748;
  font-weight: 500;
}

.subtask-title.completed {
  text-decoration: line-through;
  color: #a0aec0;
}

.add-subtask-form {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  margin-top: 0.5rem;
}

.metadata {
  gap: 0.5rem;
}

.metadata-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  color: #718096;
}

.metadata-item > i {
  color: #a0aec0;
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding-top: 1rem;
}

/* PrimeVue overrides */
:deep(.p-sidebar-header) {
  padding: 1.5rem 1rem;
  border-bottom: 1px solid #e2e8f0;
}

:deep(.p-sidebar-content) {
  padding: 0;
}

:deep(.p-chip) {
  padding: 0.375rem 0.75rem;
  border-radius: 8px;
  font-size: 0.8125rem;
}

:deep(.p-divider) {
  margin: 0;
}

/* Remove all outlines from inputs and buttons */
:deep(.p-inputtext),
:deep(.p-textarea),
:deep(.p-dropdown),
:deep(.p-calendar),
:deep(.p-chips),
:deep(.p-chips .p-chips-multiple-container),
:deep(.p-chips .p-chips-input-token input),
:deep(.p-button) {
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
:deep(.p-chips .p-chips-input-token input:focus-visible),
:deep(.p-button:focus),
:deep(.p-button:focus-visible) {
  outline: none !important;
}

/* Single border on focus */
:deep(.p-inputtext:focus),
:deep(.p-textarea:focus),
:deep(.p-dropdown:focus),
:deep(.p-calendar:focus),
:deep(.p-chips .p-chips-multiple-container:focus) {
  border-color: #667eea !important;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.2) !important;
}

:deep(.p-button) {
  font-weight: 600;
}

:deep(.p-button:not(.p-button-text):not(.p-button-outlined)) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
}

/* Responsive */
@media (max-width: 640px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>

