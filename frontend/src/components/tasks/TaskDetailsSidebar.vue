<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/stores/task.store'
import { useToast } from '@/composables/useToast'
import { useTaskCompletion } from '@/composables/useTaskCompletion'
import { useTagSuggestions } from '@/composables/useTagSuggestions'
import SimpleFileUploader from '@/components/ui/SimpleFileUploader.vue'
import mediaService from '@/services/media.service'
import type { TaskAttachment } from '@/types/task.types'
import Sidebar from 'primevue/sidebar'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import Chip from 'primevue/chip'
import AutoComplete from 'primevue/autocomplete'
import Skeleton from 'primevue/skeleton'
import Divider from 'primevue/divider'
import { useConfirm } from 'primevue/useconfirm'
import { TaskStatus, TaskPriority, TASK_PRIORITY_CONFIG, TASK_STATUS_CONFIG, type Task, type UpdateTaskRequest, type Tag as TaskTag } from '@/types/task.types'
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
const {
  popularTags,
  isLoadingPopular,
  searchSuggestions,
  searchTags,
  initialize: initializeTagSuggestions
} = useTagSuggestions()
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

// Local task state for optimistic updates
const localTask = ref<Task | null>(null)
const isLoadingFullTask = ref(false)

// Computed for v-model compatibility
const localVisible = computed({
  get: () => props.showSidebar || props.visible || false,
  set: (value) => {
    emit('update:showSidebar', value)
    emit('update:visible', value)
  }
})

// Use local task if available, otherwise use props
const currentTask = computed(() => localTask.value || props.selectedTask || props.task)

// Watch for prop changes to sync local task and load full data
watch(() => props.selectedTask || props.task, async (newTask) => {
  if (newTask) {
    localTask.value = { ...newTask }
    
    // Load full task with subtasks if not already loaded
    if (!newTask.priorityLabel || !newTask.statusLabel) {
      isLoadingFullTask.value = true
      try {
        const fullTask = await taskStore.fetchTask(newTask.id)
        localTask.value = { ...fullTask }
      } catch (error) {
        console.error('Failed to load full task:', error)
      } finally {
        isLoadingFullTask.value = false
      }
    }
    
    // Initialize tag suggestions when opening sidebar in edit mode
    if (editMode.value) {
      initializeTagSuggestions(7)
    }
  } else {
    localTask.value = null
  }
}, { immediate: true, deep: true })

// Initialize tag suggestions when entering edit mode
watch(editMode, (isEdit) => {
  if (isEdit && currentTask.value) {
    initializeTagSuggestions(7)
  }
})

// Helper function to add popular tag to edit form
function addPopularTagToEdit(tag: TaskTag) {
  if (!editData.value.tags) {
    editData.value.tags = []
  }
  
  const tagName = tag.name.trim()
  // Check if tag already exists (case-insensitive)
  const exists = editData.value.tags.some(t => t.toLowerCase() === tagName.toLowerCase())
  
  if (!exists) {
    editData.value.tags.push(tagName)
  }
}

// Handle tag search for autocomplete
async function handleTagSearch(event: any) {
  const query = event.query?.trim()
  if (query && query.length > 0) {
    await searchTags(query)
  }
}

// Add free-text tag on Enter for main edit form
function onEditTagsKeydown(event: KeyboardEvent) {
  if (event.key !== 'Enter') return
  const target = event.target as HTMLInputElement | null
  const value = target?.value?.trim()
  if (!value) return
  if (!editData.value.tags) {
    editData.value.tags = []
  }
  const exists = editData.value.tags.some(t => t.toLowerCase() === value.toLowerCase())
  if (!exists) {
    editData.value.tags.push(value)
  }
  if (target) {
    target.value = ''
  }
}

// Add free-text tag on Enter for subtask editor
function onSubtaskTagsKeydown(event: KeyboardEvent) {
  if (event.key !== 'Enter') return
  const target = event.target as HTMLInputElement | null
  const value = target?.value?.trim()
  if (!value) return
  if (!subtaskEditData.value.tags) {
    subtaskEditData.value.tags = []
  }
  const exists = subtaskEditData.value.tags.some(t => t.toLowerCase() === value.toLowerCase())
  if (!exists) {
    subtaskEditData.value.tags.push(value)
  }
  if (target) {
    target.value = ''
  }
}

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
  const priorityValue = typeof currentTask.value.priority === 'string' 
    ? currentTask.value.priority 
    : currentTask.value.priority.value
  return TASK_PRIORITY_CONFIG[priorityValue]
})

const statusConfig = computed(() => {
  if (!currentTask.value) return null
  const statusValue = typeof currentTask.value.status === 'string' 
    ? currentTask.value.status 
    : currentTask.value.status.value
  return TASK_STATUS_CONFIG[statusValue]
})

const completionPercentage = computed(() => {
  if (!currentTask.value) {
    return 0
  }
  if (!currentTask.value.subtasks || currentTask.value.subtasks.length === 0) {
    return currentTask.value.isCompleted ? 100 : 0
  }
  
  // Calculate progress from localTask if available, otherwise use completionProgress from server
  const task = localTask.value || currentTask.value
  const total = task.subtaskCount || task.subtasks?.length || 0
  const completed = task.completedSubtaskCount ?? task.subtasks?.filter(s => s.isCompleted).length ?? 0
  
  if (total === 0) {
    return currentTask.value.isCompleted ? 100 : 0
  }
  
  return Math.round((completed / total) * 100)
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
  
  const statusValue = typeof currentTask.value.status === 'string' 
    ? currentTask.value.status 
    : currentTask.value.status.value
    
  const priorityValue = typeof currentTask.value.priority === 'string' 
    ? currentTask.value.priority 
    : currentTask.value.priority.value
    
  editData.value = {
    title: currentTask.value.title,
    description: currentTask.value.description,
    status: statusValue,
    priority: priorityValue,
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
    const subtaskStatusValue = typeof st.status === 'string' 
      ? st.status 
      : st.status.value
      
    const subtaskPriorityValue = typeof st.priority === 'string' 
      ? st.priority 
      : st.priority.value
      
    subtaskEditData.value = {
      title: st.title,
      description: st.description,
      status: subtaskStatusValue,
      priority: subtaskPriorityValue,
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
    // Refresh parent task to reflect changes in localTask
    if (currentTask.value) {
      const updatedTask = await taskStore.fetchTask(currentTask.value.id)
      // Update localTask to sync with server
      if (localTask.value) {
        localTask.value = { ...updatedTask }
        if (props.selectedTask) {
          emit('update:selectedTask', localTask.value)
        }
        if (props.task) {
          emit('update:task', localTask.value)
        }
      }
    }
    // Don't emit task-updated for subtask operations - they don't affect the main task list
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

  // Store original task for rollback
  const originalTask = { ...currentTask.value }
  const newIsCompleted = !currentTask.value.isCompleted
  const newStatus = newIsCompleted ? TaskStatus.COMPLETED : TaskStatus.PENDING

  // Optimistic update - update localTask immediately for instant UI update
  localTask.value = {
    ...currentTask.value,
    isCompleted: newIsCompleted,
    status: newStatus
  } as Task

  // Also update props via emit for parent component
  if (props.selectedTask) {
    emit('update:selectedTask', localTask.value)
  }
  if (props.task) {
    emit('update:task', localTask.value)
  }

  // Show success notification immediately
  showSuccess(newIsCompleted ? t('tasks.task_completed') : t('tasks.task_reopened'))

  // Make API call in background
  try {
    await taskStore.toggleTaskCompletion(currentTask.value.id)
    
    // Fetch updated task to get all changes (including subtasks completion)
    const updatedTask = await taskStore.fetchTask(currentTask.value.id)
    
    // Update localTask with real data from server
    localTask.value = { ...updatedTask }
    
    // Update props with real data
    if (props.selectedTask) {
      emit('update:selectedTask', updatedTask)
    }
    if (props.task) {
      emit('update:task', updatedTask)
    }
    
    emit('task-updated')
  } catch (error: any) {
    // Rollback on error - restore original task
    localTask.value = { ...originalTask }
    
    if (props.selectedTask) {
      emit('update:selectedTask', originalTask)
    }
    if (props.task) {
      emit('update:task', originalTask)
    }
    
    showError(error.message || t('errors.unknown_error'))
  }
}

async function handleDelete() {
  if (!currentTask.value) return

  confirm.require({
    message: t('tasks.delete_task'),
    header: t('common.confirm'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      const taskId = currentTask.value!.id
      
      // Optimistic update - close sidebar and emit deletion immediately
      emit('task-deleted')
      emit('update:visible', false)
      
      // Show success notification immediately
      showSuccess(t('tasks.task_deleted'))
      
      // Make API call in background
      try {
        await taskStore.deleteTask(taskId)
      } catch (error: any) {
        // Rollback - reopen sidebar and show error
        emit('update:visible', true)
        showError(error.message || t('errors.unknown_error'))
        
        // Re-emit task-updated to refresh UI
        emit('task-updated')
      }
    }
  })
}

async function handleAddSubtask() {
  if (!currentTask.value || !newSubtaskTitle.value.trim()) return

  const subtaskTitle = newSubtaskTitle.value.trim()
  const originalSubtasks = localTask.value?.subtasks ? [...localTask.value.subtasks] : []
  
  // Create optimistic subtask
  const optimisticSubtask: Task = {
    id: Date.now(), // Temporary ID
    title: subtaskTitle,
    description: null,
    status: TaskStatus.PENDING,
    priority: TaskPriority.MEDIUM,
    isCompleted: false,
    parentTaskId: currentTask.value.id,
    subtasks: [],
    tags: [],
    startDate: null,
    dueDate: null,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
    isArchived: false,
    subtaskCount: 0,
    completedSubtaskCount: 0,
    hasNestedSubtasks: false
  }

  // Optimistic update - add subtask immediately
  if (localTask.value) {
    localTask.value = {
      ...localTask.value,
      subtasks: [...(localTask.value.subtasks || []), optimisticSubtask],
      subtaskCount: (localTask.value.subtaskCount || 0) + 1
    }
    
    // Update props
    if (props.selectedTask) {
      emit('update:selectedTask', localTask.value)
    }
    if (props.task) {
      emit('update:task', localTask.value)
    }
  }

  // Clear input immediately
  newSubtaskTitle.value = ''
  
  // Show success notification immediately
  showSuccess(t('tasks.task_created'))
  
  isAddingSubtask.value = true

  // Make API call in background
  try {
    const createdTask = await taskStore.createTask({
      title: subtaskTitle,
      parentTaskId: currentTask.value.id,
      status: TaskStatus.PENDING,
      priority: TaskPriority.MEDIUM
    })
    
    // Update localTask with real task from server (replace temporary one by index to preserve order)
    if (localTask.value) {
      const subtasks = localTask.value.subtasks || []
      const tempIndex = subtasks.findIndex(s => s.id === optimisticSubtask.id)
      if (tempIndex !== -1) {
        // Replace temporary subtask with real one at the same position to preserve order
        subtasks[tempIndex] = createdTask
      } else {
        // If not found, add at the end (shouldn't happen, but fallback)
        subtasks.push(createdTask)
      }
      
      localTask.value = {
        ...localTask.value,
        subtasks: [...subtasks], // Preserve order
        subtaskCount: localTask.value.subtaskCount || subtasks.length
      }
      
      // Update props
      if (props.selectedTask) {
        emit('update:selectedTask', localTask.value)
      }
      if (props.task) {
        emit('update:task', localTask.value)
      }
    }
    
    // Don't emit task-updated for subtask operations - they don't affect the main task list
  } catch (error: any) {
    // Rollback on error
    if (localTask.value) {
      localTask.value = {
        ...localTask.value,
        subtasks: originalSubtasks,
        subtaskCount: originalSubtasks.length
      }
      
      // Restore input
      newSubtaskTitle.value = subtaskTitle
      
      // Update props
      if (props.selectedTask) {
        emit('update:selectedTask', localTask.value)
      }
      if (props.task) {
        emit('update:task', localTask.value)
      }
    }
    
    showError(error.message || t('errors.unknown_error'))
  } finally {
    isAddingSubtask.value = false
  }
}

async function handleToggleSubtask(subtaskId: number) {
  if (!localTask.value?.subtasks) return
  
  // Find subtask in localTask
  const subtask = localTask.value.subtasks.find(s => s.id === subtaskId)
  if (!subtask) return

  // Store original subtask for rollback
  const originalSubtask = { ...subtask }
  const newIsCompleted = !subtask.isCompleted
  const newStatus = newIsCompleted ? TaskStatus.COMPLETED : TaskStatus.PENDING

  // Optimistic update - update subtask state immediately
  if (localTask.value.subtasks) {
    const wasCompleted = subtask.isCompleted
    const subtasks = localTask.value.subtasks.map(s => 
      s.id === subtaskId 
        ? { 
            ...s, 
            isCompleted: newIsCompleted, 
            status: newStatus 
          } 
        : s
    )
    
    // Update completed count based on state change
    let newCompletedCount = localTask.value.completedSubtaskCount || 0
    if (newIsCompleted && !wasCompleted) {
      // Was not completed, now completing - increment
      newCompletedCount += 1
    } else if (!newIsCompleted && wasCompleted) {
      // Was completed, now uncompleting - decrement
      newCompletedCount = Math.max(0, newCompletedCount - 1)
    }
    
    localTask.value = {
      ...localTask.value,
      subtasks: [...subtasks],
      completedSubtaskCount: newCompletedCount
    }
    
    // Update props
    if (props.selectedTask) {
      emit('update:selectedTask', localTask.value)
    }
    if (props.task) {
      emit('update:task', localTask.value)
    }
  }

  try {
    // Fetch the subtask to check if it has its own subtasks (for confirmation dialog)
    const fullSubtask = await taskStore.fetchTask(subtaskId)
    
    // Use the new completion handler with confirmation
    await toggleTaskCompletion(fullSubtask, async () => {
      // Fetch updated parent task to sync with server
      if (currentTask.value) {
        const updatedTask = await taskStore.fetchTask(currentTask.value.id)
        
        // Update localTask but preserve order and keep optimistic updates
        if (localTask.value?.subtasks) {
          const subtasks = localTask.value.subtasks.map(s => {
            const serverSubtask = updatedTask.subtasks?.find(ss => ss.id === s.id)
            return serverSubtask || s
          })
          
          localTask.value = {
            ...localTask.value,
            subtasks: [...subtasks],
            subtaskCount: updatedTask.subtaskCount || localTask.value.subtaskCount,
            completedSubtaskCount: updatedTask.completedSubtaskCount || localTask.value.completedSubtaskCount
          }
          
          // Update props
          if (props.selectedTask) {
            emit('update:selectedTask', localTask.value)
          }
          if (props.task) {
            emit('update:task', localTask.value)
          }
        }
      }
      // Don't emit task-updated for subtask operations - they don't affect the main task list
    })
  } catch (error: any) {
    // Rollback on error
    if (localTask.value?.subtasks) {
      const wasCompleted = originalSubtask.isCompleted
      
      const subtasks = localTask.value.subtasks.map(s => 
        s.id === subtaskId ? { ...originalSubtask } : s
      )
      
      // Restore completed count - reverse what we did
      let restoredCompletedCount = localTask.value.completedSubtaskCount || 0
      if (newIsCompleted && !wasCompleted) {
        // We tried to complete (incremented), but failed - decrement back
        restoredCompletedCount = Math.max(0, restoredCompletedCount - 1)
      } else if (!newIsCompleted && wasCompleted) {
        // We tried to uncomplete (decremented), but failed - increment back
        restoredCompletedCount += 1
      }
      
      localTask.value = {
        ...localTask.value,
        subtasks: [...subtasks],
        completedSubtaskCount: restoredCompletedCount
      }
      
      // Update props
      if (props.selectedTask) {
        emit('update:selectedTask', localTask.value)
      }
      if (props.task) {
        emit('update:task', localTask.value)
      }
    }
    
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

  const newTitle = editingSubtaskTitle.value.trim()
  const subtaskId = editingSubtaskId.value
  
  // Store original subtask for rollback
  const originalSubtask = localTask.value?.subtasks?.find(s => s.id === subtaskId)
  if (!originalSubtask) {
    cancelEditSubtask()
    return
  }

  // Optimistic update - update subtask title immediately
  if (localTask.value?.subtasks) {
    const subtasks = localTask.value.subtasks.map(s => 
      s.id === subtaskId ? { ...s, title: newTitle } : s
    )
    
    localTask.value = {
      ...localTask.value,
      subtasks: [...subtasks]
    }
    
    // Update props
    if (props.selectedTask) {
      emit('update:selectedTask', localTask.value)
    }
    if (props.task) {
      emit('update:task', localTask.value)
    }
  }

  // Exit edit mode immediately
  cancelEditSubtask()
  
  // Show success notification immediately
  showSuccess(t('tasks.task_updated'))

  // Make API call in background
  try {
    await taskStore.updateTask(subtaskId, { title: newTitle })
    
    // Fetch updated task to sync with server
    if (currentTask.value) {
      const updatedTask = await taskStore.fetchTask(currentTask.value.id)
      // Update localTask but preserve order
      if (localTask.value?.subtasks) {
        const serverSubtask = updatedTask.subtasks?.find(s => s.id === subtaskId)
        if (serverSubtask) {
          const subtasks = localTask.value.subtasks.map(s => 
            s.id === subtaskId ? { ...serverSubtask } : s
          )
          localTask.value = {
            ...localTask.value,
            subtasks: [...subtasks]
          }
          
          // Update props
          if (props.selectedTask) {
            emit('update:selectedTask', localTask.value)
          }
          if (props.task) {
            emit('update:task', localTask.value)
          }
        }
      }
    }
    
    // Don't emit task-updated for subtask operations - they don't affect the main task list
  } catch (error: any) {
    // Rollback on error
    if (localTask.value?.subtasks && originalSubtask) {
      const subtasks = localTask.value.subtasks.map(s => 
        s.id === subtaskId ? { ...originalSubtask } : s
      )
      
      localTask.value = {
        ...localTask.value,
        subtasks: [...subtasks]
      }
      
      // Update props
      if (props.selectedTask) {
        emit('update:selectedTask', localTask.value)
      }
      if (props.task) {
        emit('update:task', localTask.value)
      }
    }
    
    showError(error.message || t('errors.unknown_error'))
  }
}

async function handleDeleteSubtask(subtaskId: number) {
  confirm.require({
    message: t('tasks.delete_task'),
    header: t('common.confirm'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      // Store original subtasks for rollback
      const originalSubtasks = localTask.value?.subtasks ? [...localTask.value.subtasks] : []
      const deletedSubtask = originalSubtasks.find(s => s.id === subtaskId)
      
      // Optimistic update - remove subtask immediately
      if (localTask.value?.subtasks) {
        const subtasks = localTask.value.subtasks.filter(s => s.id !== subtaskId)
        
        localTask.value = {
          ...localTask.value,
          subtasks: [...subtasks],
          subtaskCount: Math.max(0, (localTask.value.subtaskCount || 0) - 1)
        }
        
        // Update props
        if (props.selectedTask) {
          emit('update:selectedTask', localTask.value)
        }
        if (props.task) {
          emit('update:task', localTask.value)
        }
      }

      // Show success notification immediately
      showSuccess(t('tasks.task_deleted'))

      // Make API call in background
      try {
        await taskStore.deleteTask(subtaskId)
        
        // Sync with server (but preserve order if subtask was already removed)
        if (currentTask.value && deletedSubtask) {
          const updatedTask = await taskStore.fetchTask(currentTask.value.id)
          // Update localTask but preserve order - only update if subtask still exists in our list
          if (localTask.value?.subtasks) {
            const serverSubtask = updatedTask.subtasks?.find(s => s.id === subtaskId)
            if (!serverSubtask) {
              // Subtask was deleted on server, our optimistic update was correct
              // Just sync other fields if needed
              localTask.value = {
                ...localTask.value,
                subtaskCount: updatedTask.subtaskCount || localTask.value.subtaskCount,
                completedSubtaskCount: updatedTask.completedSubtaskCount || localTask.value.completedSubtaskCount
              }
              
              // Update props
              if (props.selectedTask) {
                emit('update:selectedTask', localTask.value)
              }
              if (props.task) {
                emit('update:task', localTask.value)
              }
            }
          }
        }
        
        // Don't emit task-updated for subtask operations - they don't affect the main task list
      } catch (error: any) {
        // Rollback on error
        if (localTask.value && deletedSubtask) {
          localTask.value = {
            ...localTask.value,
            subtasks: originalSubtasks,
            subtaskCount: originalSubtasks.length
          }
          
          // Update props
          if (props.selectedTask) {
            emit('update:selectedTask', localTask.value)
          }
          if (props.task) {
            emit('update:task', localTask.value)
          }
        }
        
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

// Handle file upload
async function handleFileUpload(file: File) {
  if (!localTask.value?.id) return
  
  try {
    // 1. Загружаем файл и получаем MediaObject
    const mediaObject = await mediaService.uploadFile(file)
    
    // 2. Обновляем задачу с новым mediaId
    const currentMediaIds = localTask.value.attachments?.map(a => a.id) || []
    const newMediaIds = [...currentMediaIds, mediaObject.id]
    
    const updateData = {
      mediaIds: newMediaIds
    }
    
    const updatedTask = await taskStore.updateTask(localTask.value.id, updateData)
    localTask.value = { ...updatedTask }
    
    showSuccess(t('tasks.file_uploaded'))
    
    // Update props
    if (props.selectedTask) {
      emit('update:selectedTask', localTask.value)
    }
    if (props.task) {
      emit('update:task', localTask.value)
    }
  } catch (error: any) {
    showError(error.message || 'Upload error')
  }
}

// Handle file delete
async function handleFileDelete(attachmentId: number) {
  if (!localTask.value?.id) return
  
  try {
    // 1. Удаляем MediaObject
    await mediaService.deleteMedia(attachmentId)
    
    // 2. Обновляем задачу без этого mediaId
    const currentMediaIds = localTask.value.attachments?.map(a => a.id) || []
    const newMediaIds = currentMediaIds.filter(id => id !== attachmentId)
    
    const updateData = {
      mediaIds: newMediaIds
    }
    
    const updatedTask = await taskStore.updateTask(localTask.value.id, updateData)
    localTask.value = { ...updatedTask }
    
    showSuccess(t('tasks.file_deleted'))
    
    // Update props
    if (props.selectedTask) {
      emit('update:selectedTask', localTask.value)
    }
    if (props.task) {
      emit('update:task', localTask.value)
    }
  } catch (error: any) {
    showError(error.message || 'Delete error')
  }
}

// Handle file view
function handleFileView(attachment: TaskAttachment) {
  const url = mediaService.getFileUrl(attachment.filePath)
  window.open(url, '_blank')
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
        <Skeleton v-if="isLoadingFullTask || !currentTask.priorityLabel" width="120px" height="32px" borderRadius="16px" />
        <Chip
          v-else
          :label="currentTask.priorityLabel"
          :style="{ 
            backgroundColor: priorityConfig?.color + '20',
            color: priorityConfig?.color,
            fontWeight: 600
          }"
        />
        
        <Skeleton v-if="isLoadingFullTask || !currentTask.statusLabel" width="120px" height="32px" borderRadius="16px" />
        <Chip
          v-else
          :label="currentTask.statusLabel"
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
                :stepMinute="10"
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
                :stepMinute="10"
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
        <div v-if="editMode" class="tags-edit-container">
          <AutoComplete
            v-model="editData.tags"
            :suggestions="searchSuggestions.map(t => t.name)"
            :placeholder="t('tasks.add_tag_placeholder')"
            multiple
            class="w-full autocomplete-tags"
            @complete="handleTagSearch"
            :forceSelection="false"
            :pt="{ input: { onKeydown: onEditTagsKeydown } }"
            :appendTo="'self'"
          />
          
          <!-- Popular tags -->
          <div class="popular-tags">
            <small class="popular-tags-label">{{ t('tasks.popular_tags') }}:</small>
            
            <!-- Skeleton loaders -->
            <div v-if="isLoadingPopular" class="popular-tags-list">
              <Skeleton v-for="i in 7" :key="i" width="3.5rem" height="1.4rem" borderRadius="16px" class="tag-skeleton" />
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
                @click="addPopularTagToEdit(tag)"
              />
            </div>
            
            <small v-else class="no-tags-hint">{{ t('tasks.no_popular_tags') }}</small>
          </div>
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
            <span v-if="currentTask.subtasks && currentTask.subtasks.length > 0" class="subtasks-count">
              {{ currentTask.completedSubtaskCount ?? currentTask.subtasks.filter(s => s.isCompleted).length }} /
              {{ currentTask.subtaskCount ?? currentTask.subtasks.length }}
            </span>
          </label>
          <div v-if="currentTask.subtasks && currentTask.subtasks.length > 0" class="progress-bar">
            <div class="progress-fill" :style="{ width: completionPercentage + '%' }" />
          </div>
        </div>

        <!-- Subtasks List -->
        <div v-if="currentTask?.subtasks && currentTask.subtasks.length > 0" class="subtasks-list">
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

      <!-- File Attachments -->
      <div v-if="currentTask" class="detail-section">
        <label class="detail-label">
          <i class="pi pi-paperclip"></i>
          {{ t('tasks.attachments') }}
        </label>
        <SimpleFileUploader 
          :attachments="currentTask.attachments"
          @upload="handleFileUpload"
          @delete="handleFileDelete"
          @view="handleFileView"
        />
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
                {{ subtask.subtaskCount ?? subtask.subtasks.length }}
              </span>
            </div>
            <div v-if="currentTask.subtasks && currentTask.subtasks.length > 3" class="compact-tree-more">
              <i class="pi pi-ellipsis-h" />
              {{ t('tasks.and_more', { count: (currentTask.subtaskCount ?? currentTask.subtasks.length) - 3 }) }}
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
              <Calendar v-model="subtaskEditData.startDate" showTime hourFormat="24" :placeholder="t('common.select_date')" :stepMinute="10" class="w-full" dateFormat="dd.mm.yy" />
            </div>
          </div>
          <div class="date-item">
            <i class="pi pi-calendar-minus" />
            <div>
              <label class="detail-label-small">{{ t('tasks.due_date') }}</label>
              <Calendar v-model="subtaskEditData.dueDate" showTime hourFormat="24" :placeholder="t('common.select_date')" :stepMinute="10" class="w-full" dateFormat="dd.mm.yy" />
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
        <AutoComplete
          v-model="subtaskEditData.tags"
          :suggestions="searchSuggestions.map(t => t.name)"
          :placeholder="t('tasks.add_tag_placeholder')"
          multiple
          class="w-full autocomplete-tags"
          @complete="handleTagSearch"
          :forceSelection="false"
          :pt="{ input: { onKeydown: onSubtaskTagsKeydown } }"
          :appendTo="'self'"
        />
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
  align-items: center; /* keep icon vertically centered */
  gap: 0.75rem;
}

.date-item > i {
  font-size: 1.25rem;
  color: #667eea;
  margin-top: 0; /* prevent drifting */
  line-height: 1; /* avoid baseline shifts */
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.25rem;
  height: 1.25rem;
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
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1rem;
  height: 1rem;
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

/* Remove all outlines from inputs and buttons (scoped under sidebar content) */
.task-details :deep(.p-inputtext),
.task-details :deep(.p-textarea),
.task-details :deep(.p-dropdown),
.task-details :deep(.p-calendar),
.task-details :deep(.p-chips),
.task-details :deep(.p-chips .p-chips-multiple-container),
.task-details :deep(.p-chips .p-chips-input-token input),
.task-details :deep(.p-button) {
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
.task-details :deep(.p-textarea:focus),
.task-details :deep(.p-textarea:focus-visible),
.task-details :deep(.p-dropdown:focus),
.task-details :deep(.p-dropdown:focus-visible),
.task-details :deep(.p-calendar:focus),
.task-details :deep(.p-calendar:focus-visible),
.task-details :deep(.p-chips:focus),
.task-details :deep(.p-chips:focus-visible),
.task-details :deep(.p-chips .p-chips-multiple-container:focus),
.task-details :deep(.p-chips .p-chips-multiple-container:focus-visible),
.task-details :deep(.p-chips .p-chips-input-token input:focus),
.task-details :deep(.p-chips .p-chips-input-token input:focus-visible),
.task-details :deep(.p-button:focus),
.task-details :deep(.p-button:focus-visible) {
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

.task-details :deep(.p-button) {
  font-weight: 600;
}

.task-details :deep(.p-button:not(.p-button-text):not(.p-button-outlined)) {
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

/* Popular tags */
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

.tags-edit-container {
  width: 100%;
}

.tags-edit-container .autocomplete-tags :deep(.p-autocomplete),
.tags-edit-container .autocomplete-tags :deep(.p-autocomplete-multiple-container) {
  width: 100% !important;
  padding: 0.35rem 0.5rem; /* inner spacing */
  border-radius: 12px; /* softer corners */
  border: 1px solid #e5e7eb; /* subtle border */
  transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

.tags-edit-container .autocomplete-tags :deep(.p-autocomplete-input) {
  width: 100% !important;
}

/* Remove inner input border/outline inside AutoComplete */
.tags-edit-container .autocomplete-tags :deep(.p-inputtext) {
  border: 0 !important;
  box-shadow: none !important;
  outline: none !important;
  background: transparent !important;
}

/* Focus state */
.tags-edit-container .autocomplete-tags :deep(.p-inputwrapper-focus .p-autocomplete-multiple-container),
.tags-edit-container .autocomplete-tags :deep(.p-autocomplete-multiple-container.p-focus) {
  border-color: rgba(99, 102, 241, 0.55) !important;
  box-shadow: 0 0 0 5px rgba(99, 102, 241, 0.16) !important;
}

/* Empty results spacing */
.tags-edit-container :deep(.p-autocomplete-panel) {
  border-radius: 12px;
  overflow: hidden;
}

.tags-edit-container :deep(.p-autocomplete-panel .p-autocomplete-empty-message) {
  padding: 0.9rem 1rem !important;
  color: #475569;
}

.tags-edit-container :deep(.p-autocomplete-panel .p-autocomplete-items .p-autocomplete-item) {
  padding: 0.55rem 0.9rem;
}
</style>

