<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/stores/task.store'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Skeleton from 'primevue/skeleton'
import Chip from 'primevue/chip'
import TaskCard from '@/components/tasks/TaskCard.vue'
import CreateTaskDialog from '@/components/tasks/CreateTaskDialog.vue'
import TaskDetailsSidebar from '@/components/tasks/TaskDetailsSidebar.vue'
import FloatingActionButton from '@/components/ui/FloatingActionButton.vue'
import type { Task } from '@/types/task.types'

const router = useRouter()
const { t, locale } = useI18n()
const { user, logout } = useAuth()
const { showSuccess, showError } = useToast()
const taskStore = useTaskStore()

const searchQuery = ref('')
const selectedView = ref('all')
const displayMode = ref<'cards' | 'table'>('cards') // New: view mode toggle
const expandedGroups = ref<string[]>(['today', 'tomorrow', 'this_week', 'no_date']) // Expanded groups by default
const isDetailsOpen = ref(false)
const selectedTask = ref<Task | null>(null)
const isCreateDialogVisible = ref(false)
const editingTask = ref<Task | null>(null)

// Fetch data on mount
onMounted(async () => {
  try {
    await Promise.all([
      taskStore.fetchTasks(),
      taskStore.fetchStatistics(),
      taskStore.fetchTags()
    ])
  } catch (error) {
    showError(t('errors.unknown_error'))
  }
})

const views = computed(() => [
  { id: 'all', label: t('tasks.all_tasks'), icon: 'pi pi-list', count: taskStore.statistics?.total || 0, color: '#667eea' },
  { id: 'today', label: t('tasks.today_tasks'), icon: 'pi pi-calendar', count: taskStore.todayTasks.length, color: '#10b981' },
  { id: 'overdue', label: t('tasks.overdue_tasks'), icon: 'pi pi-exclamation-circle', count: taskStore.overdueTasks.length, color: '#ef4444' },
  { id: 'upcoming', label: t('tasks.upcoming_tasks'), icon: 'pi pi-clock', count: 0, color: '#f59e0b' },
])

const filteredTasks = computed(() => {
  let tasks = taskStore.tasks
  
  // Filter by view
  if (selectedView.value === 'today') {
    tasks = taskStore.todayTasks
  } else if (selectedView.value === 'overdue') {
    tasks = taskStore.overdueTasks
  } else if (selectedView.value === 'completed') {
    tasks = taskStore.completedTasks
  }
  
  // Filter by search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    tasks = tasks.filter(task => 
      task.title.toLowerCase().includes(query) ||
      task.description?.toLowerCase().includes(query)
    )
  }
  
  // Add dateGroup for table view grouping
  return tasks.map(task => ({
    ...task,
    dateGroup: getTaskDateGroup(task)
  }))
})

// Tasks grouped for table view
const tasksGroupedForTable = computed(() => {
  const groups: Record<string, any[]> = {}
  
  // Group tasks by date
  filteredTasks.value.forEach(task => {
    const group = task.dateGroup
    if (!groups[group]) {
      groups[group] = []
    }
    groups[group].push(task)
  })
  
  // Create array of groups with metadata
  const groupPriority: Record<string, number> = {
    'overdue': 1,
    'today': 2,
    'tomorrow': 3,
    'this_week': 4,
    'no_date': 999
  }
  
  const result = Object.entries(groups).map(([key, tasks]) => ({
    key,
    label: getGroupLabel(key),
    icon: getGroupIcon(key),
    colorClass: getGroupColorClass(key),
    badgeText: getGroupBadgeText(key),
    count: tasks.length,
    tasks: tasks.sort((a, b) => {
      if (a.dueDate && b.dueDate) {
        return new Date(a.dueDate).getTime() - new Date(b.dueDate).getTime()
      }
      return 0
    }),
    priority: groupPriority[key] || 100
  }))
  
  // Sort groups by priority
  result.sort((a, b) => a.priority - b.priority)
  
  return result
})

// Group tasks by date
const groupedTasks = computed(() => {
  const groups: Record<string, { label: string; tasks: Task[] }> = {}
  
  filteredTasks.value.forEach(task => {
    let groupKey = 'no-date'
    let groupLabel = t('tasks.no_due_date')
    
    if (task.dueDate) {
      const dueDate = new Date(task.dueDate)
      const today = new Date()
      today.setHours(0, 0, 0, 0)
      const tomorrow = new Date(today)
      tomorrow.setDate(tomorrow.getDate() + 1)
      
      const dueDateStart = new Date(dueDate)
      dueDateStart.setHours(0, 0, 0, 0)
      
      if (dueDateStart.getTime() === today.getTime()) {
        groupKey = 'today'
        groupLabel = t('tasks.today_tasks')
      } else if (dueDateStart.getTime() === tomorrow.getTime()) {
        groupKey = 'tomorrow'
        groupLabel = t('tasks.tomorrow_tasks')
      } else if (dueDateStart < today) {
        groupKey = 'overdue'
        groupLabel = t('tasks.overdue_tasks')
      } else {
        const isoDate = dueDate.toISOString().split('T')[0]
        groupKey = isoDate || 'no-date'
        const currentLocale = locale.value
        groupLabel = dueDate.toLocaleDateString(currentLocale === 'ru' ? 'ru-RU' : 'en-US', { 
          weekday: 'long', 
          year: 'numeric', 
          month: 'long', 
          day: 'numeric' 
        })
      }
    }
    
    if (!groups[groupKey]) {
      groups[groupKey] = { label: groupLabel, tasks: [] }
    }
    groups[groupKey]!.tasks.push(task)
  })
  
  // Convert to array and sort
  const sortedGroups = Object.entries(groups).sort(([keyA], [keyB]) => {
    const order = ['overdue', 'today', 'tomorrow']
    const indexA = order.indexOf(keyA)
    const indexB = order.indexOf(keyB)
    
    if (indexA !== -1 && indexB !== -1) return indexA - indexB
    if (indexA !== -1) return -1
    if (indexB !== -1) return 1
    if (keyA === 'no-date') return 1
    if (keyB === 'no-date') return -1
    return keyA.localeCompare(keyB)
  })
  
  return sortedGroups.map(([key, group]) => ({
    key,
    ...group
  }))
})

function handleLogout() {
  logout()
  showSuccess(t('success.logout_success'))
  router.push('/login')
}

function selectView(viewId: string) {
  selectedView.value = viewId
  
  // Fetch tasks with appropriate filter
  if (viewId === 'today') {
    taskStore.fetchTasks({ view: 'today' })
  } else if (viewId === 'overdue') {
    taskStore.fetchTasks({ view: 'overdue' })
  } else if (viewId === 'upcoming') {
    taskStore.fetchTasks({ view: 'upcoming' })
  } else {
    taskStore.fetchTasks()
  }
}

async function handleToggleTask(task: Task) {
  try {
    await taskStore.toggleTaskCompletion(task.id)
    showSuccess(t('tasks.task_updated'))
  } catch (error) {
    showError(t('errors.unknown_error'))
  }
}

function handleTaskClick(task: Task) {
  selectedTask.value = task
  isDetailsOpen.value = true
}

function handleCreateTask() {
  editingTask.value = null
  isCreateDialogVisible.value = true
}

function handleTaskCreated() {
  taskStore.fetchTasks()
  taskStore.fetchStatistics()
}

function handleTaskUpdated() {
  taskStore.fetchTasks()
  taskStore.fetchStatistics()
  if (selectedTask.value) {
    taskStore.fetchTask(selectedTask.value.id).then(updatedTask => {
      selectedTask.value = updatedTask
    })
  }
}

function handleTaskDeleted() {
  isDetailsOpen.value = false
  selectedTask.value = null
  taskStore.fetchTasks()
  taskStore.fetchStatistics()
}

function handleCloseSidebar() {
  isDetailsOpen.value = false
  selectedTask.value = null
}

// Helper functions for DataTable
function getPrioritySeverity(priority: string) {
  const severityMap: Record<string, 'danger' | 'warn' | 'info' | 'success'> = {
    urgent: 'danger',
    high: 'warn',
    medium: 'info',
    low: 'success'
  }
  return severityMap[priority] || 'info'
}

function getStatusSeverity(status: string) {
  const severityMap: Record<string, 'secondary' | 'info' | 'success' | 'danger'> = {
    pending: 'secondary',
    in_progress: 'info',
    completed: 'success',
    cancelled: 'danger'
  }
  return severityMap[status] || 'secondary'
}

function formatDate(dateString: string | Date) {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('ru-RU', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

function formatTime(dateString: string | Date) {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('ru-RU', {
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

// Group helper functions for DataTable
function getTaskDateGroup(task: any): string {
  if (!task.dueDate) return 'no_date'
  
  const dueDate = new Date(task.dueDate)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const tomorrow = new Date(today)
  tomorrow.setDate(tomorrow.getDate() + 1)
  const nextWeek = new Date(today)
  nextWeek.setDate(nextWeek.getDate() + 7)
  
  const dueDateStart = new Date(dueDate)
  dueDateStart.setHours(0, 0, 0, 0)
  
  if (dueDateStart < today) return 'overdue'
  if (dueDateStart.getTime() === today.getTime()) return 'today'
  if (dueDateStart.getTime() === tomorrow.getTime()) return 'tomorrow'
  if (dueDateStart <= nextWeek) return 'this_week'
  
  // Group by specific date for future dates
  return dueDate.toISOString().split('T')[0]
}

function getGroupLabel(dateGroup: string): string {
  if (dateGroup === 'overdue') return t('tasks.overdue_tasks')
  if (dateGroup === 'today') return t('tasks.today_tasks')
  if (dateGroup === 'tomorrow') return t('tasks.tomorrow_tasks')
  if (dateGroup === 'this_week') return t('tasks.this_week')
  if (dateGroup === 'no_date') return t('tasks.no_due_date')
  
  // Format specific date with current locale
  const date = new Date(dateGroup)
  const currentLocale = locale.value
  return new Intl.DateTimeFormat(currentLocale === 'ru' ? 'ru-RU' : 'en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  }).format(date)
}

function getGroupIcon(dateGroup: string): string {
  if (dateGroup === 'overdue') return 'pi pi-exclamation-triangle'
  if (dateGroup === 'today') return 'pi pi-calendar-plus'
  if (dateGroup === 'tomorrow') return 'pi pi-calendar'
  if (dateGroup === 'this_week') return 'pi pi-clock'
  if (dateGroup === 'no_date') return 'pi pi-inbox'
  return 'pi pi-calendar'
}

function getGroupColorClass(dateGroup: string): string {
  if (dateGroup === 'overdue') return 'group-overdue'
  if (dateGroup === 'today') return 'group-today'
  if (dateGroup === 'tomorrow') return 'group-tomorrow'
  if (dateGroup === 'this_week') return 'group-this-week'
  if (dateGroup === 'no_date') return 'group-no-date'
  return 'group-future'
}

function getGroupTaskCount(dateGroup: string): number {
  return filteredTasks.value.filter((task: any) => task.dateGroup === dateGroup).length
}

function getGroupBadgeText(dateGroup: string): string {
  const count = getGroupTaskCount(dateGroup)
  if (dateGroup === 'overdue') return `${count} просрочено`
  if (dateGroup === 'today') return `${count} на сегодня`
  if (dateGroup === 'tomorrow') return `${count} на завтра`
  if (dateGroup === 'this_week') return `${count} на этой неделе`
  return `${count}`
}
</script>

<template>
  <div class="tasks-dashboard">
    <!-- Animated Background -->
    <div class="dashboard-background">
      <div class="background-gradient"></div>
      <div class="background-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
      </div>
    </div>

    <!-- Header -->
    <header class="dashboard-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="header-title">{{ t('tasks.my_tasks') }}</h1>
          <p class="header-subtitle">{{ t('dashboard.welcome_back') }}, {{ user?.email }}</p>
        </div>
        <div class="header-actions">
          <Button 
            icon="pi pi-sign-out"
            severity="secondary"
            text
            rounded
            @click="handleLogout"
            :aria-label="t('dashboard.logout_button')"
          />
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <div class="dashboard-container">
      <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
          <!-- Search Box -->
          <div class="sidebar-section search-section">
            <span class="p-input-icon-left w-full">
              <i class="pi pi-search" />
              <InputText 
                v-model="searchQuery"
                :placeholder="t('tasks.search_placeholder')"
                class="w-full"
              />
            </span>
          </div>

          <!-- Views Navigation -->
          <nav class="sidebar-section views-section">
            <h3 class="sidebar-section-title">{{ t('tasks.filter_by') }}</h3>
            <div class="views-list">
              <button
                v-for="view in views"
                :key="view.id"
                :class="['view-item', { 'view-item-active': selectedView === view.id }]"
                @click="selectView(view.id)"
              >
                <i :class="view.icon" :style="{ color: view.color }" />
                <span class="view-label">{{ view.label }}</span>
                <span v-if="view.count > 0" class="view-count">
                  {{ view.count }}
                </span>
              </button>
            </div>
          </nav>

          <!-- Tags Section -->
          <div class="sidebar-section tags-section">
            <h3 class="sidebar-section-title">{{ t('tags.most_used') }}</h3>
            <div class="tags-list">
              <button
                v-for="tag in taskStore.mostUsedTags"
                :key="tag.id"
                class="tag-item"
              >
                <span class="tag-dot" :style="{ backgroundColor: tag.color }"></span>
                <span class="tag-name">{{ tag.name }}</span>
                <span class="tag-usage">{{ tag.usageCount }}</span>
              </button>
            </div>
          </div>

          <!-- Statistics Card -->
          <div v-if="taskStore.statistics" class="sidebar-section stats-section">
            <h3 class="sidebar-section-title">{{ t('tasks.total_tasks') }}</h3>
            <div class="stats-card">
              <div class="stat-item">
                <div class="stat-icon pending">
                  <i class="pi pi-clock"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value">{{ taskStore.statistics.pending }}</div>
                  <div class="stat-label">{{ t('tasks.pending_tasks') }}</div>
                </div>
              </div>
              <div class="stat-item">
                <div class="stat-icon progress">
                  <i class="pi pi-play"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value">{{ taskStore.statistics.in_progress }}</div>
                  <div class="stat-label">{{ t('tasks.in_progress_tasks') }}</div>
                </div>
              </div>
              <div class="stat-item">
                <div class="stat-icon completed">
                  <i class="pi pi-check"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value">{{ taskStore.statistics.completed }}</div>
                  <div class="stat-label">{{ t('tasks.completed_tasks_count') }}</div>
                </div>
              </div>
              <div class="stat-item">
                <div class="stat-icon overdue">
                  <i class="pi pi-exclamation-circle"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value">{{ taskStore.statistics.overdue }}</div>
                  <div class="stat-label">{{ t('tasks.overdue_tasks_count') }}</div>
                </div>
              </div>
            </div>
          </div>
        </aside>

        <!-- Task List -->
        <main class="main-content">
          <!-- View Mode Toggle -->
          <div class="view-toggle">
            <Button
              :icon="displayMode === 'cards' ? 'pi pi-th-large' : 'pi pi-th-large'"
              :severity="displayMode === 'cards' ? 'primary' : 'secondary'"
              :outlined="displayMode !== 'cards'"
              @click="displayMode = 'cards'"
              :aria-label="t('tasks.cards_view')"
            />
            <Button
              :icon="displayMode === 'table' ? 'pi pi-list' : 'pi pi-list'"
              :severity="displayMode === 'table' ? 'primary' : 'secondary'"
              :outlined="displayMode !== 'table'"
              @click="displayMode = 'table'"
              :aria-label="t('tasks.table_view')"
            />
          </div>

          <!-- Loading State -->
          <div v-if="taskStore.isLoading" class="tasks-container">
            <Skeleton height="120px" class="mb-4" borderRadius="16px" />
            <Skeleton height="120px" class="mb-4" borderRadius="16px" />
            <Skeleton height="120px" class="mb-4" borderRadius="16px" />
          </div>

          <!-- Empty State -->
          <div v-else-if="filteredTasks.length === 0" class="empty-state">
          <div class="empty-state-icon">
            <i class="pi pi-inbox" />
          </div>
          <h3 class="empty-state-title">{{ t('tasks.no_tasks') }}</h3>
          <p class="empty-state-description">{{ t('tasks.no_tasks_description') }}</p>
          </div>

          <!-- Tasks Grid with Grouping (Cards View) -->
          <div v-else-if="displayMode === 'cards'" class="tasks-container">
            <div
              v-for="group in groupedTasks"
              :key="group.key"
              class="task-group"
            >
              <h3 class="task-group-title">
                {{ group.label }}
                <span class="task-group-count">{{ group.tasks.length }}</span>
              </h3>
              <div class="task-group-list">
                <TaskCard
                  v-for="task in group.tasks"
                  :key="task.id"
                  :task="task"
                  :selected="selectedTask?.id === task.id"
                  @click="handleTaskClick"
                  @toggle-complete="handleToggleTask"
                />
              </div>
            </div>
          </div>

          <!-- Tasks List (Table View) with Custom Grouping -->
          <div v-else class="list-container">
            <!-- List Header -->
            <div class="list-header">
              <span class="list-title">{{ t('tasks.all_tasks') }}</span>
              <span class="list-count">{{ filteredTasks.length }} {{ t('tasks.tasks') }}</span>
            </div>

            <!-- Groups -->
            <div
              v-for="group in tasksGroupedForTable"
              :key="group.key"
              class="list-group"
            >
              <!-- Group Header -->
              <div class="group-date-header">
                <span class="group-date-label">{{ group.label }}</span>
              </div>

              <!-- Tasks List for this group -->
              <div class="tasks-list">
                <div
                  v-for="task in group.tasks"
                  :key="task.id"
                  :class="['task-item', { 'task-completed': task.isCompleted }]"
                  @click="handleTaskClick(task)"
                >
                  <!-- Checkbox -->
                  <input
                    type="checkbox"
                    :checked="task.isCompleted"
                    @click.stop
                    @change="handleToggleTask(task)"
                    class="task-checkbox"
                  />

                  <!-- Task Content -->
                  <div class="task-content">
                    <!-- Title & Arrow -->
                    <div class="task-title-row">
                      <span :class="['task-title', { 'completed': task.isCompleted }]">
                        {{ task.title }}
                      </span>
                      <i class="pi pi-angle-right task-arrow" />
                    </div>

                    <!-- Meta Info -->
                    <div v-if="!task.isCompleted" class="task-meta">
                      <!-- Tags -->
                      <span
                        v-for="tag in task.tags"
                        :key="tag.id"
                        class="task-tag"
                      >
                        # {{ tag.name }}
                      </span>

                      <!-- Subtasks -->
                      <span
                        v-if="task.subtasks && task.subtasks.length > 0"
                        class="task-subtasks"
                      >
                        {{ task.subtasks.filter((s: any) => s.isCompleted).length }}/{{ task.subtasks.length }}
                      </span>
                    </div>
                  </div>

                  <!-- Time -->
                  <div class="task-time">
                    {{ task.dueDate ? formatTime(task.dueDate) : '' }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>

    <!-- Floating Action Button -->
    <FloatingActionButton
      icon="pi pi-plus"
      :label="t('tasks.create_task')"
      position="bottom-left"
      @click="handleCreateTask"
    />

    <!-- Create/Edit Task Dialog -->
    <CreateTaskDialog
      v-model:visible="isCreateDialogVisible"
      :task="editingTask"
      @task-created="handleTaskCreated"
      @task-updated="handleTaskUpdated"
    />

    <!-- Task Details Sidebar -->
    <TaskDetailsSidebar
      v-model:visible="isDetailsOpen"
      :task="selectedTask"
      @task-updated="handleTaskUpdated"
      @task-deleted="handleTaskDeleted"
    />
  </div>
</template>

<style scoped>
/* ===== Dashboard Container ===== */
.tasks-dashboard {
  min-height: 100vh;
  position: relative;
  background-color: #fafafa;
}

/* ===== Animated Background ===== */
.dashboard-background {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}

.background-gradient {
  position: absolute;
  inset: 0;
  background: radial-gradient(
    circle at 20% 50%,
    rgba(102, 126, 234, 0.08) 0%,
    transparent 50%
  ),
  radial-gradient(
    circle at 80% 80%,
    rgba(118, 75, 162, 0.08) 0%,
    transparent 50%
  );
}

.background-shapes {
  position: absolute;
  inset: 0;
  overflow: hidden;
}

.shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
  animation: float 25s ease-in-out infinite;
}

.shape-1 {
  width: 500px;
  height: 500px;
  background: rgba(102, 126, 234, 0.1);
  top: -250px;
  left: -250px;
  animation-delay: 0s;
}

.shape-2 {
  width: 400px;
  height: 400px;
  background: rgba(118, 75, 162, 0.1);
  bottom: -200px;
  right: -200px;
  animation-delay: 8s;
}

.shape-3 {
  width: 350px;
  height: 350px;
  background: rgba(16, 185, 129, 0.08);
  top: 40%;
  right: 15%;
  animation-delay: 16s;
}

@keyframes float {
  0%, 100% {
    transform: translate(0, 0) scale(1);
  }
  33% {
    transform: translate(40px, -40px) scale(1.1);
  }
  66% {
    transform: translate(-30px, 30px) scale(0.9);
  }
}

/* ===== Header ===== */
.dashboard-header {
  position: relative;
  z-index: 10;
  background: white;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  padding: 1.5rem 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.header-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.header-left {
  flex: 1;
}

.header-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 0.25rem 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.header-subtitle {
  color: #718096;
  margin: 0;
  font-size: 0.9375rem;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.create-task-button {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
  transition: all 0.3s ease;
}

.create-task-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.create-task-button:active {
  transform: translateY(0);
}

/* ===== Main Container ===== */
.dashboard-container {
  position: relative;
  z-index: 1;
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

.dashboard-layout {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 2rem;
  align-items: start;
}

/* ===== Sidebar ===== */
.sidebar {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  position: sticky;
  top: 2rem;
}

.sidebar-section {
  background: white;
  border-radius: 20px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(226, 232, 240, 0.5);
}

.sidebar-section-title {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #a0aec0;
  margin: 0 0 1rem 0;
}

/* Search Section */
.search-section {
  padding: 1rem;
}

.search-section :deep(.p-inputtext) {
  width: 100%;
  padding: 0.875rem 1rem 0.875rem 2.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 0.9375rem;
  transition: all 0.2s ease;
  background: #f8fafc;
}

.search-section :deep(.p-inputtext:hover) {
  border-color: #cbd5e0;
  background: white;
}

.search-section :deep(.p-inputtext:focus) {
  border-color: #667eea !important;
  background: white;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
  outline: none !important;
}

.search-section :deep(.p-inputtext) {
  outline: none !important;
}

.search-section :deep(.p-inputtext:focus-visible) {
  outline: none !important;
}

.search-section :deep(.p-input-icon-left) {
  position: relative;
}

.search-section :deep(.p-input-icon-left > i) {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
  font-size: 1.125rem;
  z-index: 1;
}

.search-section :deep(.p-input-icon-left > .p-inputtext) {
  padding-left: 2.75rem;
}

/* Views Section */
.views-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.view-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  border-radius: 12px;
  border: none;
  background: transparent;
  color: #4a5568;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: left;
  font-size: 0.9375rem;
  font-weight: 500;
  position: relative;
  overflow: hidden;
}

.view-item::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  opacity: 0;
  transition: opacity 0.2s ease;
  border-radius: 12px;
}

.view-item:hover {
  background: #f7fafc;
  color: #2d3748;
}

.view-item-active {
  color: white;
  font-weight: 600;
}

.view-item-active::before {
  opacity: 1;
}

.view-item i,
.view-item .view-label,
.view-item .view-count {
  position: relative;
  z-index: 1;
}

.view-label {
  flex: 1;
}

.view-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  min-width: 1.5rem;
  height: 1.5rem;
  border-radius: 50%;
  padding: 0 0.25rem;
  transition: all 0.2s ease;
  background-color: #eef2ff;
  color: #4338ca;
}

.view-item-active .view-count {
  background: rgba(255, 255, 255, 0.25);
  color: white;
}

/* Tags Section */
.tags-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.tag-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  border: none;
  background: transparent;
  color: #4a5568;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: left;
  font-size: 0.875rem;
}

.tag-item:hover {
  background: #f7fafc;
  transform: translateX(4px);
}

.tag-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.tag-name {
  flex: 1;
  font-weight: 500;
}

.tag-usage {
  font-size: 0.75rem;
  color: #a0aec0;
  font-weight: 600;
}

/* Statistics Section */
.stats-card {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.125rem;
  flex-shrink: 0;
}

.stat-icon.pending {
  background: rgba(102, 126, 234, 0.1);
  color: #667eea;
}

.stat-icon.progress {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.stat-icon.completed {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.stat-icon.overdue {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  line-height: 1;
  margin-bottom: 0.25rem;
}

.stat-label {
  font-size: 0.75rem;
  color: #718096;
  font-weight: 500;
}

/* ===== Main Content ===== */
.main-content {
  min-height: 500px;
}

/* Empty State */
.empty-state {
  background: white;
  border-radius: 24px;
  padding: 4rem 2rem;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(226, 232, 240, 0.5);
}

.empty-state-icon {
  width: 120px;
  height: 120px;
  margin: 0 auto 2rem;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
  color: #667eea;
}

.empty-state-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 0.75rem 0;
}

.empty-state-description {
  color: #718096;
  margin: 0 0 2rem 0;
  font-size: 1rem;
}

.empty-state-button {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
  border: none !important;
  padding: 1rem 2.5rem !important;
  font-size: 1.125rem !important;
  font-weight: 600 !important;
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.35) !important;
  transition: all 0.3s ease !important;
}

.empty-state-button:hover {
  transform: translateY(-3px) !important;
  box-shadow: 0 12px 30px rgba(102, 126, 234, 0.45) !important;
}

.empty-state-button:active {
  transform: translateY(-1px) !important;
}

/* Tasks Container */
.tasks-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Task Groups */
.task-group {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.task-group-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
  padding-left: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  position: sticky;
  top: 0;
  background: linear-gradient(to bottom, #fafafa 0%, rgba(250, 250, 250, 0.95) 80%, transparent 100%);
  padding-top: 1rem;
  padding-bottom: 0.5rem;
  z-index: 10;
}

.task-group-count {
  font-size: 0.875rem;
  font-weight: 600;
  color: white;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.25);
}

.task-group-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 1.25rem;
}

/* Task Card */
.task-card {
  background: white;
  border-radius: 20px;
  padding: 1.5rem;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border: 2px solid transparent;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  gap: 1rem;
  position: relative;
  overflow: hidden;
}

.task-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.task-card:hover {
  border-color: rgba(102, 126, 234, 0.3);
  box-shadow: 0 12px 40px rgba(102, 126, 234, 0.15);
  transform: translateY(-4px);
}

.task-card:hover::before {
  opacity: 1;
}

.task-completed {
  opacity: 0.65;
}

.task-overdue {
  border-color: rgba(239, 68, 68, 0.3);
}

.task-overdue::before {
  background: #ef4444;
  opacity: 1;
}

/* Task Header */
.task-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.task-checkbox {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  border: 2px solid #cbd5e0;
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  color: white;
}

.task-checkbox:hover {
  border-color: #667eea;
  background: rgba(102, 126, 234, 0.1);
}

.checkbox-checked {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border-color: #10b981;
}

.task-priority-badge {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
}

.priority-low {
  background: rgba(107, 114, 128, 0.1);
  color: #6b7280;
}

.priority-medium {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.priority-high {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.priority-urgent {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

/* Task Body */
.task-body {
  flex: 1;
}

.task-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 0.5rem 0;
  line-height: 1.4;
}

.task-completed .task-title {
  text-decoration: line-through;
  color: #a0aec0;
}

.task-description {
  font-size: 0.875rem;
  color: #718096;
  margin: 0;
  line-height: 1.6;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Task Footer */
.task-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(0, 0, 0, 0.06);
}

.task-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  flex: 1;
}

.task-tag {
  padding: 0.25rem 0.75rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
  border: 1px solid;
  transition: all 0.2s ease;
}

.task-tag:hover {
  transform: translateY(-1px);
}

.task-tag-more {
  padding: 0.25rem 0.5rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
  background: #f7fafc;
  color: #718096;
}

.task-due-date {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.8125rem;
  color: #718096;
  font-weight: 500;
}

/* ===== Responsive Design ===== */
@media (max-width: 1200px) {
  .dashboard-layout {
    grid-template-columns: 280px 1fr;
  }
  
  .tasks-container {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  }
}

@media (max-width: 1024px) {
  .dashboard-layout {
    grid-template-columns: 1fr;
  }
  
  .sidebar {
    position: static;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
  }
  
  .search-section {
    grid-column: 1 / -1;
  }
}

@media (max-width: 768px) {
  .dashboard-container {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  
  .header-actions {
    justify-content: space-between;
  }
  
  .tasks-container {
    grid-template-columns: 1fr;
  }
  
  .sidebar {
    grid-template-columns: 1fr;
  }
}

/* ===== View Toggle ===== */
.view-toggle {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  justify-content: flex-end;
}

.view-toggle :deep(.p-button) {
  padding: 0.75rem;
  border-radius: 12px;
}

/* ===== List View Styles ===== */
.list-container {
  padding: 0;
  margin: 0;
}

.list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 1.5rem 2rem 1.5rem;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 16px;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
}

.list-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  letter-spacing: -0.02em;
}

.list-count {
  font-size: 0.875rem;
  font-weight: 600;
  color: #667eea;
  background: rgba(102, 126, 234, 0.1);
  padding: 0.375rem 0.875rem;
  border-radius: 20px;
}

/* List Group */
.list-group {
  margin-bottom: 2.5rem;
}

.group-date-header {
  position: sticky;
  top: 0;
  z-index: 10;
  padding: 0.75rem 1.25rem;
  margin-bottom: 0.75rem;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid rgba(226, 232, 240, 0.8);
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
  will-change: transform;
}

.group-date-header:hover {
  background: #ffffff;
  border-color: rgba(102, 126, 234, 0.2);
}

.group-date-label {
  font-size: 0.8125rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Tasks List */
.tasks-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* Task Item */
.task-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  background: white;
  border-radius: 12px;
  cursor: pointer;
  transition: box-shadow 0.2s ease, border-color 0.2s ease;
  position: relative;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(226, 232, 240, 0.6);
}

.task-item:hover {
  box-shadow: 0 4px 12px 0 rgba(102, 126, 234, 0.15);
  border-color: rgba(102, 126, 234, 0.3);
}

/* Checkbox */
.task-checkbox {
  width: 22px;
  height: 22px;
  margin-top: 0.125rem;
  cursor: pointer;
  border: 2.5px solid #cbd5e1;
  border-radius: 50%;
  appearance: none;
  -webkit-appearance: none;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  flex-shrink: 0;
}

.task-checkbox:checked {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: #667eea;
  position: relative;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
}

.task-checkbox:checked::after {
  content: '✓';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
}

.task-checkbox:hover {
  border-color: #667eea;
  border-width: 2.5px;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

/* Task Content */
.task-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 0;
}

.task-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.task-title {
  font-size: 0.9375rem;
  font-weight: 500;
  color: #1e293b;
  line-height: 1.4;
  flex: 1;
  word-break: break-word;
}

.task-title.completed {
  text-decoration: line-through;
  color: #94a3b8;
}

.task-arrow {
  color: #cbd5e1;
  font-size: 1rem;
  flex-shrink: 0;
}

.task-item:hover .task-arrow {
  color: #667eea;
}

/* Task Meta */
.task-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  font-size: 0.8125rem;
}

.task-tag {
  color: #667eea;
  font-weight: 500;
}

.task-subtasks {
  color: #64748b;
  font-weight: 500;
}

/* Task Time */
.task-time {
  font-size: 0.8125rem;
  color: #94a3b8;
  font-weight: 500;
  white-space: nowrap;
  flex-shrink: 0;
  margin-top: 0.125rem;
}

/* Completed Task */
.task-item.task-completed {
  opacity: 0.7;
  background: #f8fafc;
  border-color: rgba(203, 213, 225, 0.5);
}

.task-item.task-completed:hover {
  opacity: 0.8;
  box-shadow: 0 2px 8px 0 rgba(0, 0, 0, 0.08);
}

.task-item.task-completed .task-title {
  text-decoration: line-through;
  color: #94a3b8;
}


/* Date Group Styles */
.group-header {
  padding: 1rem 1.5rem;
  margin: 0 -1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 12px;
  transition: all 0.3s ease;
}

.group-header-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
}

.group-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.group-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.group-label {
  font-size: 1.125rem;
  font-weight: 700;
  color: #2d3748;
  text-transform: capitalize;
}

.group-count {
  font-size: 0.875rem;
  color: #718096;
}

.group-badge :deep(.p-chip) {
  font-weight: 600;
  padding: 0.5rem 1rem;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* ===== Group Color Themes ===== */
/* Overdue */
.date-group-header.group-overdue,
.group-overdue {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.12) 0%, rgba(220, 38, 38, 0.08) 100%);
  border-left: 4px solid #ef4444;
}

.date-group-header.group-overdue .group-icon-wrapper,
.group-overdue .group-icon {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.date-group-header.group-overdue .group-date-badge,
.group-overdue .group-badge :deep(.p-chip) {
  background: #fee2e2 !important;
  color: #991b1b !important;
}

/* Today */
.date-group-header.group-today,
.group-today {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(5, 150, 105, 0.08) 100%);
  border-left: 4px solid #10b981;
}

.date-group-header.group-today .group-icon-wrapper,
.group-today .group-icon {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.date-group-header.group-today .group-date-badge,
.group-today .group-badge :deep(.p-chip) {
  background: #d1fae5 !important;
  color: #065f46 !important;
}

/* Tomorrow */
.date-group-header.group-tomorrow,
.group-tomorrow {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.12) 0%, rgba(37, 99, 235, 0.08) 100%);
  border-left: 4px solid #3b82f6;
}

.date-group-header.group-tomorrow .group-icon-wrapper,
.group-tomorrow .group-icon {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.date-group-header.group-tomorrow .group-date-badge,
.group-tomorrow .group-badge :deep(.p-chip) {
  background: #dbeafe !important;
  color: #1e40af !important;
}

/* This Week */
.date-group-header.group-this-week,
.group-this-week {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.12) 0%, rgba(217, 119, 6, 0.08) 100%);
  border-left: 4px solid #f59e0b;
}

.date-group-header.group-this-week .group-icon-wrapper,
.group-this-week .group-icon {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.date-group-header.group-this-week .group-date-badge,
.group-this-week .group-badge :deep(.p-chip) {
  background: #fef3c7 !important;
  color: #92400e !important;
}

/* No Date */
.date-group-header.group-no-date,
.group-no-date {
  background: linear-gradient(135deg, rgba(107, 114, 128, 0.12) 0%, rgba(75, 85, 99, 0.08) 100%);
  border-left: 4px solid #6b7280;
}

.date-group-header.group-no-date .group-icon-wrapper,
.group-no-date .group-icon {
  background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
}

.date-group-header.group-no-date .group-date-badge,
.group-no-date .group-badge :deep(.p-chip) {
  background: #e5e7eb !important;
  color: #374151 !important;
}

/* Future */
.date-group-header.group-future,
.group-future {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.12) 0%, rgba(118, 75, 162, 0.08) 100%);
  border-left: 4px solid #667eea;
}

.date-group-header.group-future .group-icon-wrapper,
.group-future .group-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.date-group-header.group-future .group-date-badge,
.group-future .group-badge :deep(.p-chip) {
  background: #e0e7ff !important;
  color: #4338ca !important;
}

/* Hide row group toggle icon (we want always expanded) */
.tasks-table :deep(.p-rowgroup-toggler) {
  cursor: pointer;
}

.tasks-table :deep(.p-datatable-tbody > tr.p-rowgroup-header) {
  background: transparent !important;
}

.tasks-table :deep(.p-datatable-tbody > tr.p-rowgroup-header:hover) {
  background: transparent !important;
}

/* Responsive Table */
@media (max-width: 1024px) {
  .table-container {
    padding: 1rem;
  }
  
  .tasks-table :deep(.p-datatable-tbody > tr > td),
  .tasks-table :deep(.p-datatable-thead > tr > th) {
    padding: 0.75rem;
  }
  
  .group-header {
    padding: 0.75rem 1rem;
    margin: 0 -1rem;
  }
  
  .group-icon {
    width: 40px;
    height: 40px;
    font-size: 1.25rem;
  }
  
  .group-label {
    font-size: 1rem;
  }
}

@media (max-width: 768px) {
  .view-toggle {
    display: none; /* Hide view toggle on mobile */
  }
  
  .group-header {
    flex-direction: column;
    gap: 0.75rem;
    align-items: flex-start;
  }
  
  .group-badge {
    width: 100%;
  }
  
  .group-badge :deep(.p-chip) {
    width: 100%;
    justify-content: center;
  }
}
</style>
