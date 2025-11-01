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
        <h2 class="tree-modal-title">
          <i class="pi pi-sitemap" style="margin-right: 0.75rem;" />
          {{ task?.title }}
        </h2>
        <div class="tree-modal-stats">
          <span class="stat-item">
            <i class="pi pi-list" />
            {{ totalTasks }} {{ t('tasks.tasks_total') }}
          </span>
          <span class="stat-item completed">
            <i class="pi pi-check-circle" />
            {{ completedTasks }} {{ t('tasks.status_completed').toLowerCase() }}
          </span>
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
                      :class="'priority-' + slotProps.node.data.priority.toLowerCase()">
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
                  icon="pi pi-pencil" 
                  severity="secondary"
                  text 
                  rounded 
                  size="small"
                  @click.stop="handleEditNode(slotProps.node)" 
                  v-tooltip="t('tasks.edit_task')"
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
          <span class="legend-item">
            <i class="pi pi-folder" style="color: #8b92a8;" />
            {{ t('tasks.main_task') }}
          </span>
          <span class="legend-item">
            <i class="pi pi-circle" style="color: #94a3b8;" />
            {{ t('tasks.status_pending') }}
          </span>
          <span class="legend-item">
            <i class="pi pi-check-circle" style="color: #10b981;" />
            {{ t('tasks.status_completed') }}
          </span>
        </div>
        <Button 
          :label="t('common.close')" 
          severity="secondary" 
          @click="visible = false" 
        />
      </div>
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
  'task-updated': []
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

// Store completed states locally as a Map for better performance
const completedStatesMap = ref(new Map<number, boolean>())

// Dialog style based on screen size - mobile always fullscreen
const dialogStyle = computed(() => {
  if (isMobile.value) {
    return { width: '100vw', height: '100vh' }
  }
  return { width: '90vw', height: '90vh' }
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

const statusOptions = [
  { label: t('tasks.status_pending'), value: TaskStatus.PENDING },
  { label: t('tasks.status_in_progress'), value: TaskStatus.IN_PROGRESS },
  { label: t('tasks.status_completed'), value: TaskStatus.COMPLETED }
]

const priorityOptions = [
  { label: t('tasks.priority_low'), value: TaskPriority.LOW },
  { label: t('tasks.priority_medium'), value: TaskPriority.MEDIUM },
  { label: t('tasks.priority_high'), value: TaskPriority.HIGH }
]

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
}, { immediate: true, deep: false })

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

function getPriorityLabel(priority: string): string {
  const map: Record<string, string> = {
    'low': t('tasks.priority_low'),
    'medium': t('tasks.priority_medium'),
    'high': t('tasks.priority_high')
  }
  return map[priority.toLowerCase()] || priority
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
  
  // For unchecking, just update immediately
  if (!checked) {
    // Update local state immediately
    const newMap = new Map(completedStatesMap.value)
    newMap.set(taskId, checked)
    completedStatesMap.value = newMap
    
    // Show success notification immediately (before API call)
    showSuccess(t('tasks.task_reopened'))
    
    try {
      await taskService.toggleTask(taskId)
      await refreshTask()
    } catch (error: any) {
      // Revert on error
      const revertMap = new Map(completedStatesMap.value)
      revertMap.set(taskId, !checked)
      completedStatesMap.value = revertMap
      showError(error.message || t('errors.unknown_error'))
    }
    return
  }
  
  // For checking (completing), use the confirmation flow
  const task = node.data as Task
  
  // Temporarily update UI to show intent
  const newMap = new Map(completedStatesMap.value)
  newMap.set(taskId, true)
  completedStatesMap.value = newMap
  
  // Use the completion handler with confirmation
  await toggleTaskCompletion(task, async () => {
    await refreshTask()
  })
  
  // If user cancelled, revert the checkbox
  const currentTask = await taskStore.fetchTask(taskId)
  if (!currentTask.isCompleted) {
    const revertMap = new Map(completedStatesMap.value)
    revertMap.set(taskId, false)
    completedStatesMap.value = revertMap
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
async function handleTaskUpdated() {
  showTaskDetails.value = false
  selectedTask.value = null
  await refreshTask()
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
    // Reinitialize completed states with fresh data
    initializeCompletedStates(freshTask)
    emit('task-updated')
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
  padding: 1.5rem;
  background: linear-gradient(135deg, #e0e7ff 0%, #f0f4ff 100%);
  border-bottom: 1px solid #e2e8f0;
}

.tree-modal-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.75rem 0;
  display: flex;
  align-items: center;
  color: #1e293b;
}

.tree-modal-stats {
  display: flex;
  gap: 2rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9375rem;
  color: #64748b;
}

.stat-item.completed {
  color: #10b981;
}

.tree-modal-content {
  flex: 1;
  overflow: auto;
  padding: 2rem;
  background: #f8f9fa;
}

/* Compact tree layout */
.full-task-org-chart {
  width: 100%;
  min-height: 100%;
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
  border-radius: 10px;
  padding: 0.75rem;
  width: 220px;
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
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
  line-height: 1.3;
  word-break: break-word;
  flex: 1;
  padding-right: 2rem;
}

.node-title.completed {
  text-decoration: line-through;
  color: #94a3b8;
}

.node-description {
  font-size: 0.75rem;
  color: #64748b;
  line-height: 1.3;
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
  padding: 1.5rem;
  background: white;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.tree-legend {
  display: flex;
  gap: 1.5rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #6b7280;
}

/* Responsive */
@media (max-width: 768px) {
  .tree-modal-header {
    padding: 1rem;
  }

  .tree-modal-title {
    font-size: 1.25rem;
  }

  .tree-modal-content {
    padding: 1rem;
  }

  .tree-node-card {
    width: 200px;
  }

  .tree-modal-footer {
    flex-direction: column;
    gap: 1rem;
  }

  .tree-legend {
    flex-wrap: wrap;
    justify-content: center;
    font-size: 0.75rem;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>