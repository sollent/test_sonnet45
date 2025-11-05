<template>
  <Dialog
    v-model:visible="visible"
    modal
    :header="t('tasks.subtasks_tree')"
    :style="dialogStyle"
    :breakpoints="{ '960px': '100vw', '640px': '100vw' }"
    :maximizable="!isMobile"
    class="task-tree-modal"
  >
    <div class="tree-modal-container">
      <div class="tree-modal-header">
        <div class="header-main">
          <h2 class="tree-modal-title">
            <i class="pi pi-sitemap" />
            <span class="title-text">{{ truncateText(task?.title || '', 50) }}</span>
          </h2>
          <div class="tree-modal-stats">
            <span class="stat-item">
              <span class="stat-number">{{ totalTasks }}</span>
              <i class="pi pi-list" />
            </span>
            <span class="stat-item completed">
              <span class="stat-number">{{ completedTasks }}</span>
              <i class="pi pi-check-circle" />
            </span>
          </div>
        </div>
      </div>

      <div class="tree-modal-content" ref="treeContent">
        <OrganizationChart 
          v-model:selectionKeys="selectionKeys" 
          :value="chartData" 
          :collapsible="true"
          selectionMode="none" 
          class="full-task-org-chart"
        >
          <template #default="slotProps">
            <div class="tree-node-card"
              :class="{ 
                'root-node': slotProps.node.type === 'root',
                'completed-node': getNodeCompleted(slotProps.node.data?.id) 
              }"
              @click="handleNodeClick(slotProps.node)"
            >
              <div class="node-content">
                <div class="node-header">
                  <Checkbox 
                    v-if="slotProps.node.type !== 'root'"
                    :modelValue="getNodeCompleted(slotProps.node.data?.id)"
                    @update:modelValue="(value) => handleToggleComplete(slotProps.node, value)"
                    :binary="true"
                    class="node-checkbox"
                    @click.stop
                  />
                  <span class="node-title" :class="{ 'completed': getNodeCompleted(slotProps.node.data?.id) }">
                    {{ truncateText(slotProps.node.label, 35) }}
                  </span>
                </div>
                
                <div v-if="slotProps.node.data?.description" class="node-description">
                  {{ truncateText(slotProps.node.data.description, 50) }}
                </div>
                
                <div class="node-footer">
                  <div class="node-meta">
                    <span v-if="slotProps.node.data?.priority" 
                      class="node-priority" 
                      :class="'priority-' + (typeof slotProps.node.data.priority === 'string' ? slotProps.node.data.priority.toLowerCase() : slotProps.node.data.priority.value.toLowerCase())">
                      {{ getPriorityLabel(slotProps.node.data.priority) }}
                    </span>
                    <span v-if="slotProps.node.data?.dueDate" class="node-date">
                      <i class="pi pi-calendar" />
                      {{ formatDate(slotProps.node.data.dueDate) }}
                    </span>
                    <span v-if="slotProps.node.children?.length" class="node-subtasks">
                      <i class="pi pi-sitemap" />
                      {{ slotProps.node.children.length }}
                    </span>
                  </div>
                  
                  <div v-if="slotProps.node.data?.tags?.length" class="node-tags">
                    <span v-for="tag in slotProps.node.data.tags.slice(0, 2)" 
                      :key="tag.id" 
                      class="node-tag">
                      #{{ tag.name }}
                    </span>
                    <span v-if="slotProps.node.data.tags.length > 2" class="node-tag more">
                      +{{ slotProps.node.data.tags.length - 2 }}
                    </span>
                  </div>
                </div>
              </div>
              
              <div class="node-actions">
                <Button
                  icon="pi pi-plus"
                  severity="secondary"
                  text
                  rounded
                  size="small"
                  @click.stop="handleAddChild(slotProps.node)"
                  v-tooltip="t('tasks.add_subtask')"
                />
                <Button
                  v-if="slotProps.node.type !== 'root'"
                  icon="pi pi-trash"
                  severity="danger"
                  text
                  rounded
                  size="small"
                  @click.stop="handleDeleteNode(slotProps.node)"
                  v-tooltip="t('tasks.delete_task')"
                />
              </div>
            </div>
          </template>
        </OrganizationChart>
      </div>

      <div class="tree-modal-footer">
        <div class="tree-legend">
          <span class="legend-item" v-tooltip="t('tasks.main_task')">
            <i class="pi pi-folder" style="color: #8b92a8;" />
          </span>
          <span class="legend-item" v-tooltip="t('tasks.status_pending')">
            <i class="pi pi-circle" style="color: #94a3b8;" />
          </span>
          <span class="legend-item" v-tooltip="t('tasks.status_completed')">
            <i class="pi pi-check-circle" style="color: #10b981;" />
          </span>
        </div>
        <Button
          icon="pi pi-times"
          severity="secondary"
          text
          rounded
          @click="visible = false"
          v-tooltip="t('common.close')"
        />
      </div>

      <!-- Completion Loader Overlay -->
      <Transition name="fade">
        <div v-if="isCompletingTask" class="completion-overlay">
          <div class="completion-card">
            <div class="completion-icon">
              <div class="spinner-ring"></div>
              <i class="pi pi-check" />
            </div>

            <div class="completion-content">
              <h3 class="completion-title">{{ completingTaskTitle }}</h3>
              <p class="completion-subtitle">{{ completingTaskSubtitle }}</p>

              <div class="completion-progress">
                <div class="progress-bar-container">
                  <div class="progress-bar-fill" :style="{ width: completionProgress + '%' }"></div>
                </div>
                <div class="progress-info">
                  <span class="progress-percentage">{{ completionProgress }}%</span>
                  <span class="progress-count">
                    {{ completedSubtasksCount }} / {{ totalSubtasksToComplete }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </Dialog>

  <!-- Task Details Sidebar -->
  <TaskDetailsSidebar
    v-if="selectedTask"
    :visible="showTaskDetails"
    :task="selectedTask"
    @update:visible="showTaskDetails = $event"
    @task-updated="handleTaskUpdated"
    @task-deleted="handleTaskDeleted"
    :pt="{ root: { style: 'z-index: 1200' } }"
  />

  <!-- Create/Edit Task Dialog -->
  <Dialog 
    v-model:visible="showTaskDialog" 
    :header="isEditMode ? t('tasks.edit_task') : t('tasks.create_subtask')"
    :style="{ width: '600px' }"
    :breakpoints="{ '960px': '75vw', '640px': '95vw' }"
    modal
    :pt="{ root: { style: 'z-index: 1300' } }"
  >
    <div class="task-form">
      <div class="form-field">
        <label>{{ t('tasks.title') }} *</label>
        <InputText 
          v-model="taskForm.title" 
          :placeholder="t('tasks.title_placeholder')"
          class="w-full"
        />
      </div>
      
      <div class="form-field">
        <label>{{ t('tasks.description') }}</label>
        <Textarea 
          v-model="taskForm.description" 
          :placeholder="t('tasks.description_placeholder')"
          rows="3"
          class="w-full"
        />
      </div>
      
      <div class="form-row">
        <div class="form-field">
          <label>{{ t('tasks.status') }}</label>
          <Dropdown 
            v-model="taskForm.status" 
            :options="statusOptions" 
            optionLabel="label" 
            optionValue="value"
            class="w-full"
          />
        </div>
        
        <div class="form-field">
          <label>{{ t('tasks.priority') }}</label>
          <Dropdown 
            v-model="taskForm.priority" 
            :options="priorityOptions" 
            optionLabel="label" 
            optionValue="value"
            class="w-full"
          />
        </div>
      </div>
    </div>
    
    <template #footer>
      <Button 
        :label="t('common.cancel')" 
        severity="secondary" 
        @click="showTaskDialog = false" 
      />
      <Button 
        :label="t('common.save')" 
        @click="saveTask" 
        :loading="isSaving"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import Dialog from 'primevue/dialog'
import OrganizationChart from 'primevue/organizationchart'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import { useConfirm } from 'primevue/useconfirm'
import { useTaskStore } from '@/stores/task.store'
import { useToast } from '@/composables/useToast'
import { useTaskCompletion } from '@/composables/useTaskCompletion'
import TaskDetailsSidebar from '@/components/tasks/TaskDetailsSidebar.vue'
import type { Task } from '@/types/task.types'
import { TaskStatus, TaskPriority } from '@/types/task.types'
import { taskService } from '@/services/task.service'

const { t } = useI18n()
const taskStore = useTaskStore()
const confirm = useConfirm()
const { showSuccess, showError } = useToast()
const { toggleTaskCompletion } = useTaskCompletion()

const props = defineProps<{
  task: Task | null
  modelValue: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'task-updated': [task?: Task]
}>()

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Refs
const treeContent = ref<HTMLElement>()
const selectionKeys = ref<Record<string, boolean>>({})
const showTaskDialog = ref(false)
const showTaskDetails = ref(false)
const isEditMode = ref(false)
const currentTaskId = ref<number | null>(null)
const parentTaskId = ref<number | null>(null)
const selectedTask = ref<Task | null>(null)
const isSaving = ref(false)
const isMobile = ref(false)
const windowWidth = ref(window.innerWidth)

// Completion loading state
const isCompletingTask = ref(false)
const completingTaskTitle = ref('')
const completingTaskSubtitle = ref('') // subtitle/description for loader
const completionProgress = ref(0)
const totalSubtasksToComplete = ref(0)
const completedSubtasksCount = ref(0)

// Store completed states locally as a Map for better performance
const completedStatesMap = ref(new Map<number, boolean>())

// Dialog style based on screen size - mobile always fullscreen
const dialogStyle = computed(() => {
  if (isMobile.value) {
    return { 
      width: '100vw', 
      height: '100vh',
      margin: 0,
      maxHeight: '100vh'
    }
  }
  return { 
    width: '95vw', 
    height: '94vh',
    maxHeight: '94vh',
    maxWidth: '1400px'
  }
})

// Forms
const taskForm = ref({
  title: '',
  description: '',
  status: TaskStatus.PENDING,
  priority: TaskPriority.MEDIUM,
  startDate: null as Date | null,
  dueDate: null as Date | null,
  tags: [] as string[]
})

const statusOptions = computed(() => [
  { label: t('tasks.status_pending'), value: TaskStatus.PENDING },
  { label: t('tasks.status_in_progress'), value: TaskStatus.IN_PROGRESS },
  { label: t('tasks.status_completed'), value: TaskStatus.COMPLETED }
])

const priorityOptions = computed(() => [
  { label: t('tasks.priority_low'), value: TaskPriority.LOW },
  { label: t('tasks.priority_medium'), value: TaskPriority.MEDIUM },
  { label: t('tasks.priority_high'), value: TaskPriority.HIGH }
])

// Lifecycle
onMounted(() => {
  checkMobile()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

// Initialize completed states when task changes
watch(() => props.task, (newTask) => {
  if (newTask) {
    initializeCompletedStates(newTask)
  }
}, { immediate: true, deep: true })

function initializeCompletedStates(task: Task) {
  const newMap = new Map<number, boolean>()
  
  function collectStates(t: Task) {
    newMap.set(t.id, t.isCompleted || false)
    if (t.subtasks && Array.isArray(t.subtasks)) {
      t.subtasks.forEach(subtask => collectStates(subtask))
    }
  }
  
  collectStates(task)
  completedStatesMap.value = newMap
}

function handleResize() {
  windowWidth.value = window.innerWidth
  checkMobile()
}

function checkMobile() {
  isMobile.value = window.innerWidth < 768
}

// Lock/unlock height to prevent UI jumps during updates
function lockContentHeight() {
  if (treeContent.value) {
    const currentHeight = treeContent.value.offsetHeight
    // Disable transitions and lock height
    treeContent.value.style.transition = 'none'
    treeContent.value.style.minHeight = `${currentHeight}px`
    treeContent.value.style.maxHeight = `${currentHeight}px`
    treeContent.value.style.overflow = 'hidden'
  }
}

function unlockContentHeight() {
  if (treeContent.value) {
    // Remove height locks
    treeContent.value.style.minHeight = ''
    treeContent.value.style.maxHeight = ''
    treeContent.value.style.overflow = ''
    // Re-enable transitions after a frame
    requestAnimationFrame(() => {
      if (treeContent.value) {
        treeContent.value.style.transition = ''
      }
    })
  }
}

// Get node completed status
function getNodeCompleted(nodeId: number | undefined): boolean {
  if (!nodeId) return false
  return completedStatesMap.value.get(nodeId) || false
}

// Build chart nodes
function buildChartNodes(taskNode: Task): any[] {
  if (!taskNode.subtasks || !Array.isArray(taskNode.subtasks) || taskNode.subtasks.length === 0) {
    return []
  }
  
  const children = taskNode.subtasks.map(st => {
    const isCompleted = completedStatesMap.value.get(st.id) || false
    return {
      key: String(st.id),
      label: st.title,
      data: st,
      type: isCompleted ? 'completed' : 'pending',
      styleClass: isCompleted ? 'completed-node' : '',
      children: buildChartNodes(st),
      selectable: false
    }
  })
  
  return children
}

const chartData = computed(() => {
  if (!props.task) {
    return { label: 'No Data' }
  }
  
  const rootNode = {
    key: String(props.task.id),
    label: props.task.title,
    data: props.task,
    type: 'root',
    styleClass: 'root-node',
    children: buildChartNodes(props.task),
    selectable: false
  }
  
  return rootNode
})

// Statistics based on local state
const totalTasks = computed(() => {
  if (!props.task) return 0
  
  function countTasks(task: Task): number {
    let count = 1
    if (task.subtasks && Array.isArray(task.subtasks)) {
      task.subtasks.forEach(st => {
        count += countTasks(st)
      })
    }
    return count
  }
  
  return countTasks(props.task)
})

const completedTasks = computed(() => {
  if (!props.task) return 0
  
  function countCompleted(task: Task): number {
    const isCompleted = completedStatesMap.value.get(task.id) || false
    let count = isCompleted ? 1 : 0
    if (task.subtasks && Array.isArray(task.subtasks)) {
      task.subtasks.forEach(st => {
        count += countCompleted(st)
      })
    }
    return count
  }
  
  return countCompleted(props.task)
})

// Utilities
function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('ru-RU', { 
    day: 'numeric', 
    month: 'short' 
  })
}

function truncateText(text: string, length: number): string {
  if (!text || text.length <= length) return text
  return text.substring(0, length) + '...'
}

function getPriorityLabel(priority: any): string {
  // Always use translation for correct locale
  const priorityValue = typeof priority === 'string' ? priority : (priority.value || priority)
  const map: Record<string, string> = {
    'low': t('tasks.priority_low'),
    'medium': t('tasks.priority_medium'),
    'high': t('tasks.priority_high'),
    'urgent': t('tasks.priority_urgent')
  }
  return map[priorityValue.toLowerCase()] || priorityValue
}

// Handlers
function handleNodeClick(node: any) {
  if (node.type === 'root' || !node.data) return
  
  selectedTask.value = node.data
  showTaskDetails.value = true
}

async function handleToggleComplete(node: any, checked: boolean) {
  if (!node.data) return

  const taskId = node.data.id
  const task = node.data as Task

  // For unchecking, show loader and update
  if (!checked) {
    // Lock height before updates
    lockContentHeight()

    // Show loader for unchecking (reopening task)
    isCompletingTask.value = true
    completingTaskTitle.value = t('tasks.reopening_task')
    completingTaskSubtitle.value = t('tasks.reopening_task_message', { title: task.title })
    totalSubtasksToComplete.value = 1
    completedSubtasksCount.value = 0
    completionProgress.value = 0

    // Quick progress animation
    const interval = setInterval(() => {
      if (completionProgress.value < 100) {
        completionProgress.value += 25
        completedSubtasksCount.value = Math.min(1, Math.ceil(completionProgress.value / 100))
      } else {
        clearInterval(interval)
      }
    }, 60)

    // Update local state immediately
    const newMap = new Map(completedStatesMap.value)
    newMap.set(taskId, checked)
    completedStatesMap.value = newMap

    // Show success notification immediately (before API call)
    showSuccess(t('tasks.task_reopened'))

    try {
      await taskService.toggleTask(taskId)
      await refreshTask()

      // Delay to let async updates settle and keep loader visible
      await new Promise(resolve => setTimeout(resolve, 400))
    } catch (error: any) {
      // Revert on error
      const revertMap = new Map(completedStatesMap.value)
      revertMap.set(taskId, !checked)
      completedStatesMap.value = revertMap
      showError(error.message || t('errors.unknown_error'))
    } finally {
      // Hide loader
      clearInterval(interval)
      isCompletingTask.value = false
      completingTaskTitle.value = ''
      completingTaskSubtitle.value = ''
      completionProgress.value = 0
      completedSubtasksCount.value = 0
      totalSubtasksToComplete.value = 0

      // Small delay before unlocking
      await new Promise(resolve => setTimeout(resolve, 100))
      unlockContentHeight()
    }
    return
  }

  // For checking (completing), use the confirmation flow

  // Temporarily update UI to show intent
  const newMap = new Map(completedStatesMap.value)
  newMap.set(taskId, true)
  completedStatesMap.value = newMap

  // Count uncompleted subtasks
  const countUncompletedSubtasks = (t: Task): number => {
    if (!t.subtasks || t.subtasks.length === 0) return 0
    let count = 0
    for (const subtask of t.subtasks) {
      if (!subtask.isCompleted) {
        count += 1 + countUncompletedSubtasks(subtask)
      }
    }
    return count
  }

  const uncompletedCount = countUncompletedSubtasks(task)
  let progressInterval: ReturnType<typeof setInterval> | null = null

  try {
    // Use the completion handler with confirmation
    await toggleTaskCompletion(
      task,
      // onSuccess: called AFTER completion
      async () => {
        await refreshTask()

        // Add delay before hiding loader to mask any UI jumps
        // This gives time for all async updates to settle
        await new Promise(resolve => setTimeout(resolve, 500))

        // Hide loader
        if (progressInterval) clearInterval(progressInterval)
        isCompletingTask.value = false
        completingTaskTitle.value = ''
        completingTaskSubtitle.value = ''
        completionProgress.value = 0
        completedSubtasksCount.value = 0
        totalSubtasksToComplete.value = 0

        // Unlock height after loader is hidden
        await new Promise(resolve => setTimeout(resolve, 100))
        unlockContentHeight()
      },
      // onBeforeComplete: called IMMEDIATELY after "Да" click, BEFORE requests
      () => {
        // Lock height to prevent UI jumps during update
        lockContentHeight()

        // Always show loader to mask any UI jumps
        isCompletingTask.value = true
        completingTaskTitle.value = t('tasks.completing_task')

        if (uncompletedCount > 0) {
          // Task has subtasks - show full progress
          completingTaskSubtitle.value = t('tasks.completing_task_with_subtasks_message', { title: task.title, count: uncompletedCount })
          totalSubtasksToComplete.value = uncompletedCount + 1 // +1 for the main task
          completedSubtasksCount.value = 0
          completionProgress.value = 0

          // Simulate progress updates
          progressInterval = setInterval(() => {
            if (completedSubtasksCount.value < totalSubtasksToComplete.value) {
              completedSubtasksCount.value++
              completionProgress.value = Math.round((completedSubtasksCount.value / totalSubtasksToComplete.value) * 100)
            } else {
              if (progressInterval) clearInterval(progressInterval)
            }
          }, 150) // Faster interval for smoother animation
        } else {
          // Task has no subtasks - show simple loader without progress
          completingTaskSubtitle.value = t('tasks.completing_task_message', { title: task.title })
          totalSubtasksToComplete.value = 1
          completedSubtasksCount.value = 0
          completionProgress.value = 0

          // Quick progress to 100%
          progressInterval = setInterval(() => {
            if (completionProgress.value < 100) {
              completionProgress.value += 20
              completedSubtasksCount.value = Math.min(1, Math.ceil(completionProgress.value / 100))
            } else {
              if (progressInterval) clearInterval(progressInterval)
            }
          }, 80) // Fast progress for simple tasks
        }
      }
    )
  } catch (error) {
    // User cancelled or error occurred
    // Revert the checkbox
    const revertMap = new Map(completedStatesMap.value)
    revertMap.set(taskId, false)
    completedStatesMap.value = revertMap

    // Hide loader in case it was shown
    if (progressInterval) clearInterval(progressInterval)
    isCompletingTask.value = false
    completingTaskTitle.value = ''
    completingTaskSubtitle.value = ''
    completionProgress.value = 0
    completedSubtasksCount.value = 0
    totalSubtasksToComplete.value = 0

    // Unlock height on error/cancel
    unlockContentHeight()
  }
}

function handleAddChild(node: any) {
  isEditMode.value = false
  currentTaskId.value = null
  parentTaskId.value = Number(node.key)
  
  taskForm.value = {
    title: '',
    description: '',
    status: TaskStatus.PENDING,
    priority: TaskPriority.MEDIUM,
    startDate: null,
    dueDate: null,
    tags: []
  }
  
  showTaskDialog.value = true
}

async function handleEditNode(node: any) {
  try {
    const task = await taskStore.fetchTask(Number(node.key))
    
    isEditMode.value = true
    currentTaskId.value = task.id
    parentTaskId.value = null
    
    taskForm.value = {
      title: task.title,
      description: task.description || '',
      status: task.status,
      priority: task.priority,
      startDate: task.startDate ? new Date(task.startDate) : null,
      dueDate: task.dueDate ? new Date(task.dueDate) : null,
      tags: task.tags && Array.isArray(task.tags) ? task.tags.map(t => t.name) : []
    }
    
    showTaskDialog.value = true
  } catch (error: any) {
    showError(error.message || t('errors.unknown_error'))
  }
}

function handleDeleteNode(node: any) {
  confirm.require({
    message: t('tasks.delete_confirmation'),
    header: t('tasks.delete_task'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: t('common.yes'),
    rejectLabel: t('common.no'),
    accept: async () => {
      try {
        await taskStore.deleteTask(Number(node.key))
        await refreshTask()
        showSuccess(t('tasks.task_deleted'))
      } catch (error: any) {
        showError(error.message || t('errors.unknown_error'))
      }
    }
  })
}

// Handle task updates from sidebar
async function handleTaskUpdated(updateInfo?: {
  type: 'subtask-added' | 'subtask-edited' | 'subtask-toggled' | 'subtask-deleted' | 'tags-updated'
  title?: string
  taskTitle?: string
}) {
  // Lock height before updates
  lockContentHeight()

  // Show loader with context-specific text
  isCompletingTask.value = true

  // Set loader title and subtitle based on operation type
  if (updateInfo) {
    switch (updateInfo.type) {
      case 'subtask-added':
        completingTaskTitle.value = t('tasks.adding_subtask')
        completingTaskSubtitle.value = t('tasks.adding_subtask_message', { title: updateInfo.title || '' })
        break
      case 'subtask-edited':
        completingTaskTitle.value = t('tasks.editing_subtask')
        completingTaskSubtitle.value = t('tasks.editing_subtask_message', { title: updateInfo.taskTitle || '' })
        break
      case 'subtask-toggled':
        completingTaskTitle.value = t('tasks.toggling_subtask')
        completingTaskSubtitle.value = t('tasks.toggling_subtask_message', { title: updateInfo.taskTitle || '' })
        break
      case 'subtask-deleted':
        completingTaskTitle.value = t('tasks.deleting_subtask')
        completingTaskSubtitle.value = t('tasks.deleting_subtask_message', { title: updateInfo.taskTitle || '' })
        break
      case 'tags-updated':
        completingTaskTitle.value = t('tasks.updating_tags')
        completingTaskSubtitle.value = t('tasks.updating_tags_message', { title: updateInfo.taskTitle || '' })
        break
    }
  } else {
    completingTaskTitle.value = t('tasks.updating_task')
    completingTaskSubtitle.value = t('tasks.updating_task_message')
  }

  totalSubtasksToComplete.value = 1
  completedSubtasksCount.value = 0
  completionProgress.value = 0

  // Quick progress animation
  const interval = setInterval(() => {
    if (completionProgress.value < 100) {
      completionProgress.value += 20
      completedSubtasksCount.value = Math.min(1, Math.ceil(completionProgress.value / 100))
    } else {
      clearInterval(interval)
    }
  }, 80)

  try {
    // Close sidebar
    showTaskDetails.value = false
    selectedTask.value = null

    // Refresh task data
    await refreshTask()

    // Keep loader visible for smooth UX
    await new Promise(resolve => setTimeout(resolve, 400))
  } finally {
    // Hide loader
    clearInterval(interval)
    isCompletingTask.value = false
    completingTaskTitle.value = ''
    completingTaskSubtitle.value = ''
    completionProgress.value = 0
    completedSubtasksCount.value = 0
    totalSubtasksToComplete.value = 0

    // Small delay before unlocking
    await new Promise(resolve => setTimeout(resolve, 100))
    unlockContentHeight()
  }
}

// Handle task deletion from sidebar
async function handleTaskDeleted() {
  showTaskDetails.value = false
  selectedTask.value = null
  await refreshTask()
}

// Save functions
async function saveTask() {
  if (!taskForm.value.title.trim()) {
    showError(t('tasks.title_required'))
    return
  }
  
  isSaving.value = true
  
  try {
    if (isEditMode.value && currentTaskId.value) {
      await taskStore.updateTask(currentTaskId.value, {
        ...taskForm.value,
        startDate: taskForm.value.startDate?.toISOString() || undefined,
        dueDate: taskForm.value.dueDate?.toISOString() || undefined
      })
    } else {
      await taskStore.createTask({
        ...taskForm.value,
        parentTaskId: parentTaskId.value || undefined,
        startDate: taskForm.value.startDate?.toISOString() || undefined,
        dueDate: taskForm.value.dueDate?.toISOString() || undefined
      })
    }
    
    showTaskDialog.value = false
    await refreshTask()
    showSuccess(t('tasks.task_saved'))
  } catch (error: any) {
    showError(error.message || t('errors.unknown_error'))
  } finally {
    isSaving.value = false
  }
}

async function refreshTask() {
  if (props.task) {
    const freshTask = await taskStore.fetchTask(props.task.id)
    // Update completed states IMMEDIATELY to prevent flickering
    initializeCompletedStates(freshTask)
    emit('task-updated', freshTask)
  }
}
</script>

<style scoped>
.task-tree-modal :deep(.p-dialog-content) {
  padding: 0;
  height: calc(100% - 60px);
  overflow: hidden;
}

.tree-modal-container {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.tree-modal-header {
  padding: 0.75rem 1rem;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #e2e8f0;
}

.header-main {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.tree-modal-title {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #1e293b;
  flex: 1;
  min-width: 0;
}

.tree-modal-title i {
  flex-shrink: 0;
  font-size: 1rem;
  color: #6366f1;
}

.title-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.tree-modal-stats {
  display: flex;
  gap: 1rem;
  flex-shrink: 0;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
  color: #64748b;
  background: white;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}

.stat-item.completed {
  color: #10b981;
  border-color: #bbf7d0;
  background: #f0fdf4;
}

.stat-number {
  font-weight: 600;
  font-size: 0.9375rem;
}

.tree-modal-content {
  flex: 1;
  overflow: auto;
  padding: 1rem;
  background: #f8f9fa;
  min-height: 0; /* Important for flex shrinking */
}

/* Compact tree layout */
.full-task-org-chart {
  width: 100%;
  min-height: 100%;
}

/* Disable transitions to prevent height jumps during updates */
.full-task-org-chart :deep(*) {
  transition: none !important;
}

.full-task-org-chart :deep(.p-organizationchart-table) {
  margin: 0 auto;
}

.full-task-org-chart :deep(.p-organizationchart-node) {
  padding: 0.25rem !important;
}

.full-task-org-chart :deep(.p-organizationchart-node-content) {
  border: none !important;
  background: transparent !important;
  padding: 0 !important;
}

.full-task-org-chart :deep(.p-organizationchart-line-down),
.full-task-org-chart :deep(.p-organizationchart-line-left),
.full-task-org-chart :deep(.p-organizationchart-line-right) {
  background: #cbd5e0 !important;
}

.full-task-org-chart :deep(.p-organizationchart-node-toggle) {
  background: #94a3b8 !important;
  color: white !important;
  border: none !important;
  width: 20px !important;
  height: 20px !important;
  border-radius: 50% !important;
  font-size: 0.75rem !important;
}

/* Node card styles */
.tree-node-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.625rem;
  width: 200px;
  transition: all 0.2s ease;
  cursor: pointer;
  position: relative;
}

.tree-node-card:hover {
  border-color: #cbd5e0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.tree-node-card.root-node {
  background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%);
  border-color: #c7d2fe;
}

.tree-node-card.completed-node {
  background: #f0fdf4;
  border-color: #bbf7d0;
  opacity: 0.85;
}

.node-content {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.node-header {
  display: flex;
  align-items: flex-start;
  gap: 0.375rem;
}

.node-checkbox {
  margin-top: 1px;
  flex-shrink: 0;
}

.node-checkbox :deep(.p-checkbox-box) {
  width: 18px;
  height: 18px;
}

.node-checkbox :deep(.p-checkbox-icon) {
  font-size: 0.75rem;
}

.node-title {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #1e293b;
  line-height: 1.2;
  word-break: break-word;
  flex: 1;
  padding-right: 1.5rem;
}

.node-title.completed {
  text-decoration: line-through;
  color: #94a3b8;
}

.node-description {
  font-size: 0.6875rem;
  color: #64748b;
  line-height: 1.25;
  margin-top: 0.125rem;
}

.node-footer {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.node-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
  font-size: 0.6875rem;
}

.node-priority {
  padding: 0.0625rem 0.25rem;
  border-radius: 3px;
  font-weight: 500;
}

.node-priority.priority-low {
  background: #f0f9ff;
  color: #0284c7;
}

.node-priority.priority-medium {
  background: #fef3c7;
  color: #d97706;
}

.node-priority.priority-high {
  background: #fee2e2;
  color: #dc2626;
}

.node-date,
.node-subtasks {
  display: flex;
  align-items: center;
  gap: 0.125rem;
  color: #64748b;
}

.node-date i,
.node-subtasks i {
  font-size: 0.5rem;
}

.node-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.125rem;
}

.node-tag {
  font-size: 0.625rem;
  padding: 0.0625rem 0.25rem;
  background: #f1f5f9;
  color: #64748b;
  border-radius: 3px;
}

.node-actions {
  position: absolute;
  top: 0.25rem;
  right: 0.25rem;
  display: flex;
  gap: 0;
  opacity: 0;
  transition: opacity 0.2s;
  background: white;
  border-radius: 6px;
  padding: 0.125rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.tree-node-card:hover .node-actions {
  opacity: 1;
}

.node-actions :deep(.p-button) {
  width: 24px !important;
  height: 24px !important;
}

.node-actions :deep(.p-button .p-button-icon) {
  font-size: 0.75rem;
}

/* Task form styles */
.task-form {
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

.w-full {
  width: 100%;
}

.tree-modal-footer {
  padding: 0.5rem 1rem;
  background: white;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-height: 3rem;
}

.tree-legend {
  display: flex;
  gap: 1rem;
}

.legend-item {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: #f8fafc;
  border-radius: 6px;
  cursor: help;
  transition: all 0.2s;
}

.legend-item:hover {
  background: #e2e8f0;
}

.legend-item i {
  font-size: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
  .tree-modal-header {
    padding: 0.5rem 0.75rem;
  }

  .header-main {
    gap: 0.5rem;
  }

  .tree-modal-title {
    font-size: 1rem;
    gap: 0.375rem;
  }

  .tree-modal-title i {
    font-size: 0.875rem;
  }

  .tree-modal-stats {
    gap: 0.5rem;
  }

  .stat-item {
    padding: 0.125rem 0.375rem;
    font-size: 0.75rem;
  }

  .stat-number {
    font-size: 0.8125rem;
  }

  .tree-modal-content {
    padding: 0.75rem;
  }

  .tree-node-card {
    width: 180px;
    padding: 0.5rem;
  }

  .tree-modal-footer {
    padding: 0.375rem 0.75rem;
    min-height: 2.5rem;
  }

  .tree-legend {
    gap: 0.5rem;
  }

  .legend-item {
    width: 28px;
    height: 28px;
  }

  .legend-item i {
    font-size: 0.875rem;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  /* Hide dialog header on mobile to save space */
  :deep(.p-dialog-header) {
    padding: 0.75rem !important;
  }

  :deep(.p-dialog-title) {
    font-size: 1rem !important;
  }

  :deep(.p-dialog-header-icon) {
    width: 1.75rem !important;
    height: 1.75rem !important;
  }
}

/* Dialog overrides for better space usage */
.task-tree-modal :deep(.p-dialog) {
  display: flex;
  flex-direction: column;
}

.task-tree-modal :deep(.p-dialog-header) {
  flex-shrink: 0;
  padding: 0.75rem 1rem !important;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #e2e8f0;
}

.task-tree-modal :deep(.p-dialog-title) {
  font-size: 1.125rem !important;
  font-weight: 600 !important;
}

.task-tree-modal :deep(.p-dialog-content) {
  padding: 0 !important;
  height: 100%;
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
  overflow: hidden;
}

/* Mobile specific dialog overrides */
@media (max-width: 768px) {
  .task-tree-modal :deep(.p-dialog-header) {
    padding: 0.5rem 0.75rem !important;
  }

  .task-tree-modal :deep(.p-dialog-title) {
    font-size: 0.9375rem !important;
  }

  .task-tree-modal :deep(.p-dialog-header-icon) {
    width: 1.5rem !important;
    height: 1.5rem !important;
  }
}

/* Completion Loader Overlay */
.completion-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.3s ease;
}

.completion-card {
  background: white;
  border-radius: 16px;
  padding: 2.5rem 2rem;
  max-width: 440px;
  width: 90%;
  box-shadow:
    0 20px 25px -5px rgba(0, 0, 0, 0.1),
    0 10px 10px -5px rgba(0, 0, 0, 0.04);
  animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  text-align: center;
}

.completion-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 1.5rem;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.spinner-ring {
  position: absolute;
  width: 100%;
  height: 100%;
  border: 4px solid #e2e8f0;
  border-top-color: #6366f1;
  border-right-color: #6366f1;
  border-radius: 50%;
  animation: spin 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
}

.completion-icon i {
  font-size: 2rem;
  color: #6366f1;
  z-index: 1;
  animation: pulse 1.5s ease-in-out infinite;
}

.completion-content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.completion-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.completion-subtitle {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
  font-weight: 500;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  padding: 0 1rem;
}

.completion-progress {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.progress-bar-container {
  width: 100%;
  height: 12px;
  background: #f1f5f9;
  border-radius: 100px;
  overflow: hidden;
  position: relative;
}

.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%);
  border-radius: 100px;
  transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.progress-bar-fill::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  bottom: 0;
  right: 0;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255, 255, 255, 0.3) 50%,
    transparent 100%
  );
  animation: shimmer 1.5s infinite;
}

.progress-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
}

.progress-percentage {
  font-weight: 700;
  color: #6366f1;
  font-size: 1.125rem;
}

.progress-count {
  color: #64748b;
  font-weight: 600;
}

.completion-message {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  color: #64748b;
  margin: 0.5rem 0 0;
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-radius: 8px;
}

.completion-message i {
  color: #6366f1;
}

/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.1);
    opacity: 0.8;
  }
}

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Mobile responsive */
@media (max-width: 640px) {
  .completion-card {
    padding: 2rem 1.5rem;
    max-width: 90%;
  }

  .completion-icon {
    width: 64px;
    height: 64px;
  }

  .completion-title {
    font-size: 1.125rem;
  }

  .progress-percentage {
    font-size: 1rem;
  }
}
</style>