<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/stores/task.store'
import { useToast } from '@/composables/useToast'
import { useTaskCompletion } from '@/composables/useTaskCompletion'
import Sidebar from 'primevue/sidebar'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import Chip from 'primevue/chip'
import Chips from 'primevue/chips'
import Divider from 'primevue/divider'
import { useConfirm } from 'primevue/useconfirm'
import { TaskStatus, TaskPriority, TASK_PRIORITY_CONFIG, TASK_STATUS_CONFIG, type Task, type UpdateTaskRequest } from '@/types/task.types'
import { taskService } from '@/services/task.service'
import TaskTreeModal from './TaskTreeModal.vue'

interface Props {
  showSidebar?: boolean
  selectedTask?: Task | null
  // Support old props for backward compatibility
  visible?: boolean
  task?: Task | null
}

interface Emits {
  (e: 'update:showSidebar', value: boolean): void
  (e: 'update:selectedTask', value: Task | null): void
  (e: 'update:visible', value: boolean): void
  (e: 'update:task', value: Task | null): void
  (e: 'task-updated'): void
  (e: 'task-deleted'): void
}

const props = withDefaults(defineProps<Props>(), {
  showSidebar: false,
  selectedTask: null,
  visible: false,
  task: null
})
const emit = defineEmits<Emits>()

const { t } = useI18n()
const taskStore = useTaskStore()
const { showSuccess, showError } = useToast()
const { toggleTaskCompletion } = useTaskCompletion()
const confirm = useConfirm()

// Local state
const editMode = ref(false)
const isSubmitting = ref(false)
const newSubtaskTitle = ref('')
const isAddingSubtask = ref(false)
const editingSubtaskId = ref<number | null>(null)
const editingSubtaskTitle = ref('')

// Full subtask editor state
const isSubtaskView = ref(false)
const currentSubtask = ref<Task | null>(null)
const subtaskEditData = ref<UpdateTaskRequest>({})
const isSubtaskSubmitting = ref(false)

// Tree modal state
const showTreeModal = ref(false)

// Edit form data
const editData = ref<UpdateTaskRequest>({})

// Computed for v-model compatibility
const localVisible = computed({
  get: () => props.showSidebar || props.visible || false,
  set: (value) => {
    emit('update:showSidebar', value)
    emit('update:visible', value)
  }
})

const currentTask = computed(() => props.selectedTask || props.task)

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
  if (!currentTask.value) return null
  return TASK_PRIORITY_CONFIG[currentTask.value.priority]
})

const statusConfig = computed(() => {
  if (!currentTask.value) return null
  return TASK_STATUS_CONFIG[currentTask.value.status]
})

const completionPercentage = computed(() => {
  if (!currentTask.value || currentTask.value.subtasks.length === 0) {
    return currentTask.value?.isCompleted ? 100 : 0
  }
  return Math.round(currentTask.value.completionProgress)
})

const totalSubtasks = computed(() => {
  if (!currentTask.value) return 0
  
  function countSubtasks(task: Task): number {
    let count = task.subtasks?.length || 0
    task.subtasks?.forEach(st => {
      count += countSubtasks(st)
    })
    return count
  }
  
  return countSubtasks(currentTask.value)
})

// Watch for task changes
watch(() => currentTask.value, (newTask, oldTask) => {
  // Only reset if the task ID changes, or if there was no task before.
  if (newTask && (!oldTask || newTask.id !== oldTask.id)) {
    resetEditData()
    editMode.value = false
    
    // Debug: log task structure
    if (newTask) {
      console.log('Task loaded:', newTask)
      console.log('Subtasks:', newTask.subtasks)
      if (newTask.subtasks && newTask.subtasks.length > 0) {
        console.log('First subtask:', newTask.subtasks[0])
        console.log('First subtask has subtasks?', newTask.subtasks[0].subtasks)
      }
    }
  }
}, { immediate: true })

// Methods
function resetEditData() {
  if (!currentTask.value) return
  
  editData.value = {
    title: currentTask.value.title,
    description: currentTask.value.description,
    status: currentTask.value.status,
    priority: currentTask.value.priority,
    startDate: currentTask.value.startDate,
    dueDate: currentTask.value.dueDate,
    tags: currentTask.value.tags.map(tag => tag.name)
  }
}

function toggleEditMode() {
  if (editMode.value) {
    resetEditData()
  }
  editMode.value = !editMode.value
}

async function openSubtaskEditor(subtaskId: number) {
  try {
    const st = await taskService.getTask(subtaskId)
    currentSubtask.value = st
    subtaskEditData.value = {
      title: st.title,
      description: st.description,
      status: st.status,
      priority: st.priority,
      startDate: st.startDate,
      dueDate: st.dueDate,
      tags: st.tags.map(t => t.name)
    }
    isSubtaskView.value = true
    editMode.value = true
  } catch (e: any) {
    showError(e.message || t('errors.unknown_error'))
  }
}

function closeSubtaskEditor() {
  isSubtaskView.value = false
  currentSubtask.value = null
  editMode.value = false
}

async function handleSaveSubtask() {
  if (!currentSubtask.value) return
  isSubtaskSubmitting.value = true
  try {
    await taskStore.updateTask(currentSubtask.value.id, subtaskEditData.value)
    showSuccess(t('tasks.task_updated'))
    // refresh parent task to reflect changes
    if (currentTask.value) await taskStore.fetchTask(currentTask.value.id)
    emit('task-updated')
    closeSubtaskEditor()
  } catch (e: any) {
    showError(e.message || t('errors.unknown_error'))
  } finally {
    isSubtaskSubmitting.value = false
  }
}

// Handler for tree modal updates
async function handleTreeUpdate() {
  // Refresh the current task data
  if (currentTask.value) {
    await taskStore.fetchTask(currentTask.value.id)
    emit('task-updated')
  }
}

async function handleSave() {
  if (!currentTask.value) return

  isSubmitting.value = true

  try {
    await taskStore.updateTask(currentTask.value.id, editData.value)
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
  if (!currentTask.value) return

  // Use the new completion handler with confirmation for subtasks
  await toggleTaskCompletion(currentTask.value, async () => {
    // Refresh the current task to get updated state
    if (currentTask.value) {
      const updatedTask = await taskStore.fetchTask(currentTask.value.id)
      // Update the local task reference
      if (props.selectedTask) {
        emit('update:selectedTask', updatedTask)
      }
      if (props.task) {
        emit('update:task', updatedTask)
      }
    }
    emit('task-updated')
  })
}

async function handleDelete() {
  if (!currentTask.value) return

  confirm.require({
    message: t('tasks.delete_task'),
    header: t('common.confirm'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await taskStore.deleteTask(currentTask.value!.id)
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
  if (!currentTask.value || !newSubtaskTitle.value.trim()) return

  isAddingSubtask.value = true

  try {
    await taskStore.createTask({
      title: newSubtaskTitle.value,
      parentTaskId: currentTask.value.id,
      status: TaskStatus.PENDING,
      priority: TaskPriority.MEDIUM
    })
    
    // Refresh task details
    await taskStore.fetchTask(currentTask.value.id)
    
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
    // Fetch the subtask to check if it has its own subtasks
    const subtask = await taskStore.fetchTask(subtaskId)
    
    // Use the new completion handler with confirmation
    await toggleTaskCompletion(subtask, async () => {
      // Refresh parent task
      if (currentTask.value) {
        await taskStore.fetchTask(currentTask.value.id)
      }
      emit('task-updated')
    })
  } catch (error: any) {
    showError(error.message || t('errors.unknown_error'))
  }
}

// Subtask inline editing
function startEditSubtask(subtask: Task) {
  editingSubtaskId.value = subtask.id
  editingSubtaskTitle.value = subtask.title
}

function cancelEditSubtask() {
  editingSubtaskId.value = null
  editingSubtaskTitle.value = ''
}

async function saveEditSubtask() {
  if (!editingSubtaskId.value || !editingSubtaskTitle.value.trim()) {
    cancelEditSubtask()
    return
  }
  try {
    await taskStore.updateTask(editingSubtaskId.value, { title: editingSubtaskTitle.value.trim() })
    if (currentTask.value) await taskStore.fetchTask(currentTask.value.id)
    showSuccess(t('tasks.task_updated'))
    emit('task-updated')
  } catch (error: any) {
    showError(error.message || t('errors.unknown_error'))
  } finally {
    cancelEditSubtask()
  }
}

async function handleDeleteSubtask(subtaskId: number) {
  confirm.require({
    message: t('tasks.delete_task'),
    header: t('common.confirm'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await taskStore.deleteTask(subtaskId)
        if (currentTask.value) await taskStore.fetchTask(currentTask.value.id)
        showSuccess(t('tasks.task_deleted'))
        emit('task-updated')
      } catch (error: any) {
        showError(error.message || t('errors.unknown_error'))
      }
    }
  })
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
    v-model:visible="localVisible"
    position="right"
    :style="{ width: '90vw', maxWidth: '500px' }"
    :showCloseIcon="false"
  >
    <template #header>
      <div class="drawer-header">
        <div class="drawer-title" style="display:flex;align-items:center;gap:.5rem;">
          <Button v-if="isSubtaskView" icon="pi pi-arrow-left" text rounded severity="secondary" @click="closeSubtaskEditor" :aria-label="t('common.back')" />
          <h3 class="drawer-title">{{ isSubtaskView ? t('tasks.edit_task') : t('tasks.task_details') }}</h3>
        </div>
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

    <!-- Parent task details -->
    <div v-if="currentTask && !isSubtaskView" class="task-details">
      <!-- Priority and Status Badges -->
      <div class="badges-row">
        <Chip
          :label="t(`tasks.priority_${currentTask.priority}`)"
          :style="{ 
            backgroundColor: priorityConfig?.color + '20',
            color: priorityConfig?.color,
            fontWeight: 600
          }"
        />
        <Chip
          :label="t(`tasks.status_${currentTask.status}`)"
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
        <h2 v-else class="task-title">{{ currentTask.title }}</h2>
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
        <p v-else-if="currentTask.description" class="task-description">
          {{ currentTask.description }}
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
              <p v-else class="date-value">{{ formatDate(currentTask.startDate) }}</p>
            </div>
          </div>
          
          <div class="date-item">
            <i class="pi pi-calendar-minus" :class="{ 'text-danger': currentTask.isOverdue }" />
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
              <p v-else class="date-value" :class="{ 'text-danger': currentTask.isOverdue }">
                {{ formatDate(currentTask.dueDate) }}
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
            v-for="tag in currentTask.tags"
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
            <span v-if="currentTask.subtasks.length > 0" class="subtasks-count">
              {{ currentTask.subtasks.filter(s => s.isCompleted).length }} / {{ currentTask.subtasks.length }}
            </span>
          </label>
          <div v-if="currentTask.subtasks.length > 0" class="progress-bar">
            <div class="progress-fill" :style="{ width: completionPercentage + '%' }" />
          </div>
        </div>

        <!-- Subtasks List -->
        <div v-if="currentTask.subtasks.length > 0" class="subtasks-list">
          <div
            v-for="subtask in currentTask.subtasks"
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
            <template v-if="editingSubtaskId === subtask.id">
              <InputText
                v-model="editingSubtaskTitle"
                class="subtask-edit-input"
                @keyup.enter="saveEditSubtask"
                @keyup.esc="cancelEditSubtask"
              />
            </template>
            <template v-else>
              <span class="subtask-title" :class="{ 'completed': subtask.isCompleted }">
                {{ subtask.title }}
              </span>
            </template>
            <div class="subtask-actions">
              <template v-if="editingSubtaskId === subtask.id">
                <Button icon="pi pi-check" rounded text severity="success" @click="saveEditSubtask" :aria-label="t('common.save')" />
                <Button icon="pi pi-times" rounded text severity="secondary" @click="cancelEditSubtask" :aria-label="t('common.cancel')" />
              </template>
              <template v-else>
                <Button icon="pi pi-pencil" rounded text severity="secondary" @click="startEditSubtask(subtask)" :aria-label="t('tasks.edit_task')" />
                <Button icon="pi pi-external-link" rounded text severity="secondary" @click="openSubtaskEditor(subtask.id)" :aria-label="t('tasks.edit_task')" />
                <Button icon="pi pi-trash" rounded text severity="danger" @click="handleDeleteSubtask(subtask.id)" :aria-label="t('tasks.delete_task')" />
              </template>
            </div>
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
      <!-- Compact Tree View -->
      <div v-if="currentTask && currentTask.subtasks && currentTask.subtasks.length > 0" class="detail-section">
        <div class="tree-section-header">
          <div>
            <label class="detail-label">
              <i class="pi pi-sitemap" style="margin-right: 0.5rem;" />
              {{ t('tasks.tree_structure') }}
            </label>
          </div>
          <Button 
            icon="pi pi-external-link" 
            :label="t('tasks.view_full_tree')"
            severity="info"
            size="small"
            outlined
            @click="showTreeModal = true"
          />
        </div>
        
        <!-- Compact tree preview -->
        <div class="compact-tree-container">
          <div class="compact-tree-node root">
            <i class="pi pi-folder" />
            <span>{{ currentTask.title }}</span>
            <span class="badge">{{ totalSubtasks }}</span>
          </div>
          <div class="compact-tree-children">
            <div 
              v-for="subtask in currentTask.subtasks.slice(0, 3)" 
              :key="subtask.id"
              class="compact-tree-node"
              :class="{ 'completed': subtask.isCompleted }"
            >
              <i :class="subtask.isCompleted ? 'pi pi-check-circle' : 'pi pi-circle'" />
              <span>{{ subtask.title }}</span>
              <span v-if="subtask.subtasks && subtask.subtasks.length > 0" class="badge">
                {{ subtask.subtasks.length }}
              </span>
            </div>
            <div v-if="currentTask.subtasks.length > 3" class="compact-tree-more">
              <i class="pi pi-ellipsis-h" />
              {{ t('tasks.and_more', { count: currentTask.subtasks.length - 3 }) }}
            </div>
          </div>
        </div>
      </div>

      <Divider />

      <!-- Metadata -->
      <div class="detail-section metadata">
        <div class="metadata-item">
          <i class="pi pi-clock" />
          <span>{{ t('profile.created_at') }}: {{ formatDate(currentTask.createdAt) }}</span>
        </div>
        <div class="metadata-item">
          <i class="pi pi-refresh" />
          <span>{{ t('profile.updated_at') }}: {{ formatDate(currentTask.updatedAt) }}</span>
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
            :label="currentTask.isCompleted ? t('tasks.mark_incomplete') : t('tasks.mark_complete')"
            :icon="currentTask.isCompleted ? 'pi pi-times-circle' : 'pi pi-check-circle'"
            :severity="currentTask.isCompleted ? 'secondary' : 'success'"
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

    <!-- Subtask full edit view -->
    <div v-else-if="currentSubtask && isSubtaskView" class="task-details">
      <!-- Title -->
      <div class="detail-section">
        <label class="detail-label">{{ t('tasks.task_title') }}</label>
        <InputText v-model="subtaskEditData.title" class="w-full" :placeholder="t('tasks.title_placeholder')" />
      </div>

      <!-- Description -->
      <div class="detail-section">
        <label class="detail-label">{{ t('tasks.task_description') }}</label>
        <Textarea v-model="subtaskEditData.description" class="w-full" rows="4" autoResize :placeholder="t('tasks.description_placeholder')" />
      </div>

      <Divider />

      <!-- Dates -->
      <div class="detail-section">
        <div class="date-row">
          <div class="date-item">
            <i class="pi pi-calendar-plus" />
            <div>
              <label class="detail-label-small">{{ t('tasks.start_date') }}</label>
              <Calendar v-model="subtaskEditData.startDate" showTime hourFormat="24" :placeholder="t('common.select_date')" class="w-full" dateFormat="dd.mm.yy" />
            </div>
          </div>
          <div class="date-item">
            <i class="pi pi-calendar-minus" />
            <div>
              <label class="detail-label-small">{{ t('tasks.due_date') }}</label>
              <Calendar v-model="subtaskEditData.dueDate" showTime hourFormat="24" :placeholder="t('common.select_date')" class="w-full" dateFormat="dd.mm.yy" />
            </div>
          </div>
        </div>
      </div>

      <!-- Status and Priority -->
      <div class="detail-section">
        <div class="form-row">
          <div>
            <label class="detail-label-small">{{ t('tasks.task_status') }}</label>
            <Dropdown v-model="subtaskEditData.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div>
            <label class="detail-label-small">{{ t('tasks.task_priority') }}</label>
            <Dropdown v-model="subtaskEditData.priority" :options="priorityOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
        </div>
      </div>

      <Divider />

      <!-- Tags -->
      <div class="detail-section">
        <label class="detail-label">{{ t('tasks.tags') }}</label>
        <Chips v-model="subtaskEditData.tags" :placeholder="t('tasks.add_tag_placeholder')" separator="," class="w-full" />
      </div>

      <Divider />

      <div class="action-buttons">
        <Button :label="t('common.cancel')" severity="secondary" outlined class="w-full" @click="closeSubtaskEditor" />
        <Button :label="t('common.save')" class="w-full" :loading="isSubtaskSubmitting" @click="handleSaveSubtask" />
      </div>
    </div>
  </Sidebar>

  <!-- Full Tree Modal -->
  <TaskTreeModal
    v-model="showTreeModal"
    :task="currentTask"
    @task-updated="handleTreeUpdate"
  />
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
  align-items: flex-start;
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
  flex: 1 1 auto;
  min-width: 0;
  font-size: 0.9375rem;
  color: #2d3748;
  font-weight: 500;
  overflow-wrap: anywhere;
}

.subtask-title.completed {
  text-decoration: line-through;
  color: #a0aec0;
}

.subtask-actions {
  display: flex;
  gap: 0.25rem;
  margin-left: auto;
  flex-shrink: 0;
  align-self: flex-start;
}

.subtask-edit-input {
  flex: 1 1 auto;
  min-width: 0;
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

/* Make chips input full width */
:deep(.p-chips .p-chips-multiple-container) {
  width: 100%;
  padding: 0.5rem;
  gap: 0.5rem;
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

/* Compact Tree Styles */
.tree-section-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.compact-tree-container {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 1rem;
  max-height: 300px;
  overflow-y: auto;
}

.compact-tree-node {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  margin-bottom: 0.5rem;
  border: 1px solid #e2e8f0;
  transition: all 0.2s ease;
  font-size: 0.9375rem;
}

.compact-tree-node:hover {
  border-color: #667eea;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
}

.compact-tree-node.root {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  font-weight: 600;
  margin-bottom: 1rem;
}

.compact-tree-node.root i {
  color: white;
}

.compact-tree-node.completed {
  opacity: 0.7;
  background: #f0fdf4;
}

.compact-tree-node.completed span:not(.badge) {
  text-decoration: line-through;
  color: #6b7280;
}

.compact-tree-node i {
  font-size: 1rem;
  color: #667eea;
}

.compact-tree-node.completed i {
  color: #10b981;
}

.compact-tree-node span:first-of-type {
  flex: 1;
}

.compact-tree-node .badge {
  background: #667eea;
  color: white;
  padding: 0.125rem 0.375rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.compact-tree-children {
  padding-left: 1.5rem;
  border-left: 2px solid #e2e8f0;
  margin-left: 0.75rem;
}

.compact-tree-more {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  color: #6b7280;
  font-size: 0.875rem;
  font-style: italic;
}
</style>

