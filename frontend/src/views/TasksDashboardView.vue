<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/stores/task.store'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import Button from 'primevue/button'
import Sidebar from 'primevue/sidebar'
import Skeleton from 'primevue/skeleton'
import InputText from 'primevue/inputtext'
import Paginator, { type PageState } from 'primevue/paginator'
import TaskCard from '@/components/tasks/TaskCard.vue'
import TaskDetailsSidebar from '@/components/tasks/TaskDetailsSidebar.vue'
import FloatingActionButton from '@/components/ui/FloatingActionButton.vue'
import TaskFilters from '@/components/tasks/TaskFilters.vue'
import QuickFilters from '@/components/tasks/QuickFilters.vue'
import AdvancedFiltersModal from '@/components/tasks/AdvancedFiltersModal.vue'
import TaskDialog from '@/components/tasks/TaskDialog.vue'
import type { Task } from '@/types/task.types'

const router = useRouter()
const { t, locale } = useI18n()
const { user, logout } = useAuth()
const { showSuccess, showError } = useToast()
const taskStore = useTaskStore()

const searchQuery = ref('')
const selectedView = ref('all')
const isDetailsOpen = ref(false)
const selectedTask = ref<Task | null>(null)
const isCreateDialogVisible = ref(false)
const editingTask = ref<Task | null>(null)
const isFiltersVisible = ref(false)
const isFiltersPanelVisible = ref(false)
const displayMode = ref<'cards' | 'list'>('cards')
const overduePage = ref(1)
const overdueLimit = ref(20)

// Active filters count
const activeFiltersCount = computed(() => {
  const filters = taskStore.activeFilters
  let count = 0
  if (filters.tags.length > 0) count++
  if (filters.priorities.length > 0) count++
  if (filters.statuses.length > 0) count++
  if (filters.completed !== null) count++
  if (filters.dateFrom || filters.dateTo) count++
  return count
})

// Handle quick filter change
function handleQuickFilterChange(view: string) {
  console.log('Quick filter changed:', view)
  // Handle view change based on quick filter
}

// Handle filters apply
function handleFiltersApply() {
  console.log('Filters applied')
}
const unscheduledPage = ref(1)
const unscheduledLimit = ref(20)

const currentLoading = computed(() => {
  if (selectedView.value === 'overdue') return taskStore.isOverdueLoading
  if (selectedView.value === 'unscheduled') return taskStore.isUnscheduledLoading
  return taskStore.isLoading
})

// Simple breakpoint detection
const isMobile = ref(window.innerWidth < 1024)
const onResize = () => {
  isMobile.value = window.innerWidth < 1024
}

onMounted(() => {
  window.addEventListener('resize', onResize)
  // Fetch data
  selectView(selectedView.value)
  taskStore.fetchStatistics()
  taskStore.fetchTags()
})

const displayedTasks = computed(() => {
  let tasks: Task[] = []
  
  switch (selectedView.value) {
    case 'today':
    tasks = taskStore.todayTasks
      break
    case 'overdue':
      tasks = taskStore.overdueTasksPaginated.tasks
      break
    case 'unscheduled':
      tasks = taskStore.unscheduledTasksPaginated.tasks
      break
    case 'upcoming':
      tasks = taskStore.upcomingTasks
      break
    case 'all':
    default:
      tasks = taskStore.tasks
      break
  }
  
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    return tasks.filter(task => 
      task.title.toLowerCase().includes(query) ||
      task.description?.toLowerCase().includes(query)
    )
  }
  
  return tasks
})

const groupedTasks = computed(() => {
  const groups: Record<string, { label: string; tasks: Task[] }> = {}
  
  displayedTasks.value.forEach(task => {
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
  if (viewId === 'overdue') {
    overduePage.value = 1
    taskStore.fetchOverdueTasksPaginated(overduePage.value, overdueLimit.value)
  } else if (viewId === 'unscheduled') {
    unscheduledPage.value = 1
    taskStore.fetchUnscheduledTasksPaginated(unscheduledPage.value, unscheduledLimit.value)
  } else {
    taskStore.fetchTasks({ view: 'all' })
  }
  
  if (isMobile.value) {
    isFiltersVisible.value = false
  }
}

async function refreshCurrentView() {
  if (selectedView.value === 'overdue') {
    await taskStore.fetchOverdueTasksPaginated(overduePage.value, overdueLimit.value)
  } else if (selectedView.value === 'unscheduled') {
    await taskStore.fetchUnscheduledTasksPaginated(unscheduledPage.value, unscheduledLimit.value)
  } else {
    await taskStore.fetchTasks({ view: 'all' })
  }
}

function onOverduePageChange(event: PageState) {
  overduePage.value = event.page + 1
  overdueLimit.value = event.rows
  taskStore.fetchOverdueTasksPaginated(overduePage.value, overdueLimit.value)
}

function onUnscheduledPageChange(event: PageState) {
  unscheduledPage.value = event.page + 1
  unscheduledLimit.value = event.rows
  taskStore.fetchUnscheduledTasksPaginated(unscheduledPage.value, unscheduledLimit.value)
}

async function handleToggleTask(task: Task) {
  try {
    await taskStore.toggleTaskCompletion(task.id)
    showSuccess(t('tasks.task_updated'))
  } catch (error) {
    showError(t('errors.unknown_error'))
  }
}

async function handleTaskCardUpdated(updatedTask: Task) {
  if (selectedTask.value?.id === updatedTask.id) {
    selectedTask.value = updatedTask
  }

  try {
    await taskStore.fetchStatistics()
  } catch (error) {
    console.error('Failed to refresh task statistics', error)
  }
}

function handleTaskClick(task: Task) {
  selectedTask.value = task
  isDetailsOpen.value = true

  taskStore.fetchTask(task.id)
    .then(fullTask => {
      if (selectedTask.value?.id === fullTask.id) {
        selectedTask.value = fullTask
      }
    })
    .catch((error: any) => {
      console.error('Failed to load task details', error)
      showError(t('errors.fetch_failed'))
    })
}

function handleCreateTask() {
  editingTask.value = null
  isCreateDialogVisible.value = true
}

async function handleTaskCreated() {
  await refreshCurrentView()
  await taskStore.fetchStatistics()
}

async function handleTaskSaved() {
  await refreshCurrentView()
  await taskStore.fetchStatistics()
  isCreateDialogVisible.value = false
}

async function handleTaskUpdated() {
  // Only update selected task if it exists, without reloading all tasks
  // This prevents unnecessary API calls when working with subtasks
  if (selectedTask.value) {
    try {
      const updatedTask = await taskStore.fetchTask(selectedTask.value.id)
      selectedTask.value = updatedTask
      
      // Update task in store if it exists there
      const taskIndex = taskStore.tasks.findIndex(t => t.id === updatedTask.id)
      if (taskIndex !== -1) {
        taskStore.tasks[taskIndex] = updatedTask
      }
      
      // Also update in paginated lists if exists
      const overdueIndex = taskStore.overdueTasksPaginated.tasks.findIndex(t => t.id === updatedTask.id)
      if (overdueIndex !== -1) {
        taskStore.overdueTasksPaginated.tasks[overdueIndex] = updatedTask
      }
      
      const unscheduledIndex = taskStore.unscheduledTasksPaginated.tasks.findIndex(t => t.id === updatedTask.id)
      if (unscheduledIndex !== -1) {
        taskStore.unscheduledTasksPaginated.tasks[unscheduledIndex] = updatedTask
      }
    } catch (error) {
      console.error('Failed to refresh selected task', error)
    }
  }
  
  // Update statistics in background (without blocking)
  taskStore.fetchStatistics().catch(error => {
    console.error('Failed to refresh task statistics', error)
  })
}

async function handleTaskDeleted() {
  isDetailsOpen.value = false
  selectedTask.value = null
  await refreshCurrentView()
  await taskStore.fetchStatistics()
}
</script>

<template>
  <div class="tasks-dashboard">
    <!-- Animated Background -->
    <div class="dashboard-background">
      <div class="background-gradient"></div>
    </div>

    <!-- Header -->
    <header class="dashboard-header">
      <div class="header-content">
        <div class="header-left">
          <Button 
            v-if="isMobile"
            icon="pi pi-bars"
            severity="secondary"
            text
            rounded
            @click="isFiltersVisible = true"
            aria-label="Open filters"
            class="mobile-filter-button"
          />
          <h1 class="header-title">{{ t('tasks.my_tasks') }}</h1>
        </div>
        <div class="header-nav">
          <Button
            :label="!isMobile ? t('tasks.my_tasks') : ''"
            icon="pi pi-list"
            :severity="$route.name === 'Dashboard' ? 'primary' : 'secondary'"
            text
            @click="$router.push('/dashboard')"
          />
          <Button
            :label="!isMobile ? t('calendar.title') : ''"
            icon="pi pi-calendar"
            :severity="$route.name === 'Calendar' ? 'primary' : 'secondary'"
            text
            @click="$router.push('/calendar')"
          />
        </div>
        <div class="header-actions">
           <p class="header-subtitle">{{ user?.email }}</p>
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
        <!-- Desktop Sidebar -->
        <aside v-if="!isMobile" class="sidebar">
          <TaskFilters 
            v-model:searchQuery="searchQuery" 
            v-model:selectedView="selectedView"
            @select-view="selectView"
          />
        </aside>

        <!-- Mobile Sidebar -->
        <Sidebar v-else v-model:visible="isFiltersVisible" position="left">
           <TaskFilters 
            v-model:searchQuery="searchQuery" 
            v-model:selectedView="selectedView"
            @select-view="selectView"
          />
        </Sidebar>

        <!-- Task List -->
        <main class="main-content">
          <!-- Top Controls Bar -->
          <div class="top-controls">
            <!-- Filters Button (Mobile) -->
            <Button
              v-if="isMobile"
              icon="pi pi-filter"
              :label="t('tasks.filters')"
              severity="secondary"
              outlined
              @click="isFiltersPanelVisible = true"
              :badge="taskStore.hasActiveFilters() ? String(taskStore.activeFilters.tags.length + taskStore.activeFilters.priorities.length + taskStore.activeFilters.statuses.length + (taskStore.activeFilters.completed !== null ? 1 : 0) + (taskStore.activeFilters.dateFrom || taskStore.activeFilters.dateTo ? 1 : 0)) : undefined"
              badgeClass="p-badge-danger"
            />
            
            <!-- Desktop View Toggle -->
            <div v-if="!isMobile" class="view-toggle">
              <Button
                :icon="'pi pi-th-large'"
              :severity="displayMode === 'cards' ? 'primary' : 'secondary'"
              :outlined="displayMode !== 'cards'"
              @click="displayMode = 'cards'"
              :aria-label="t('tasks.cards_view')"
            />
            <Button
                :icon="'pi pi-list'"
                :severity="displayMode === 'list' ? 'primary' : 'secondary'"
                :outlined="displayMode !== 'list'"
                @click="displayMode = 'list'"
              :aria-label="t('tasks.table_view')"
            />
            </div>
          </div>
          
          <!-- Mobile Search -->
          <div v-if="isMobile" class="mobile-search-container">
            <span class="p-input-icon-left w-full">
              <i class="pi pi-search" />
              <InputText 
                v-model="searchQuery"
                :placeholder="t('tasks.search_placeholder')"
                class="w-full"
              />
            </span>
          </div>
          
        <!-- Quick Filters Row (Desktop & Mobile) -->
        <div class="filters-row">
          <div class="quick-filters-wrapper">
            <QuickFilters @filter-change="handleQuickFilterChange" />
          </div>
          <button @click="isFiltersPanelVisible = true" class="advanced-filters-btn">
            <i class="pi pi-sliders-h"></i>
            <span class="btn-text">{{ t('tasks.filters') }}</span>
            <span v-if="taskStore.hasActiveFilters()" class="filters-count">
              {{ activeFiltersCount }}
            </span>
          </button>
          </div>

          <!-- Loading State -->
          <div v-if="currentLoading" class="tasks-container">
            <Skeleton height="120px" class="mb-4" borderRadius="16px" />
            <Skeleton height="120px" class="mb-4" borderRadius="16px" />
            <Skeleton height="120px" class="mb-4" borderRadius="16px" />
          </div>

          <!-- Empty State -->
          <div v-else-if="displayedTasks.length === 0" class="empty-state">
          <div class="empty-state-icon">
            <i class="pi pi-inbox" />
          </div>
          <h3 class="empty-state-title">{{ t('tasks.no_tasks') }}</h3>
          <p class="empty-state-description">{{ t('tasks.no_tasks_description') }}</p>
          </div>

          <!-- Cards/Grid View -->
          <div v-else-if="displayMode === 'cards'" class="tasks-container">
            <div v-for="group in groupedTasks" :key="group.key" class="task-group">
              <h3 class="task-group-title">
                {{ group.label }}
                <span class="task-group-count">{{ group.tasks.length }}</span>
              </h3>
              <div class="task-group-list">
                <TaskCard
                  v-for="task in group.tasks"
                  :key="`${task.id}:${task.isCompleted ? 1 : 0}`"
                  :task="task"
                  :selected="selectedTask?.id === task.id"
                  @click="handleTaskClick"
                  @task-updated="handleTaskCardUpdated"
                />
              </div>
            </div>
          </div>

          <!-- List View -->
          <div v-else class="list-container">
            <div v-for="group in groupedTasks" :key="group.key" class="list-group">
              <div class="group-date-header">
                <span class="group-date-label">{{ group.label }}</span>
                <span class="task-group-count">{{ group.tasks.length }}</span>
              </div>
              <div class="tasks-list">
                <div
                  v-for="task in group.tasks"
                  :key="`${task.id}:${task.isCompleted ? 1 : 0}`"
                  :class="['task-item', { 'task-completed': task.isCompleted }]"
                  @click="handleTaskClick(task)"
                >
                  <input
                    type="checkbox"
                    :checked="task.isCompleted"
                    @click.stop
                    @change="handleToggleTask(task)"
                    class="task-checkbox"
                  />
                  <div class="task-content">
                    <div class="task-title-row">
                      <span :class="['task-title', { 'completed': task.isCompleted }]">
                        {{ task.title }}
                      </span>
                      <i class="pi pi-angle-right task-arrow" />
                    </div>
                    <div v-if="!task.isCompleted" class="task-meta">
                      <span
                        v-for="tag in task.tags"
                        :key="tag.id"
                        class="task-tag"
                      >
                        # {{ tag.name }}
                      </span>
                      <span v-if="(task.subtaskCount ?? 0) > 0" class="task-subtasks">
                        {{ task.completedSubtaskCount ?? 0 }}/{{ task.subtaskCount ?? 0 }}
                      </span>
                    </div>
                  </div>
                  <div class="task-time">{{ task.dueDate ? new Date(task.dueDate).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' }) : '' }}</div>
                  </div>
                </div>
              </div>
            </div>

          <!-- Paginator for Paginated Views -->
          <div v-if="['overdue', 'unscheduled'].includes(selectedView)" class="paginator-wrapper">
            <Paginator
              :rows="selectedView === 'overdue' ? overdueLimit : unscheduledLimit"
              :total-records="selectedView === 'overdue' ? taskStore.overdueTotal : taskStore.unscheduledTotal"
              :rows-per-page-options="[10, 20, 50]"
              :pageLinkSize="isMobile ? 4 : 5"
              template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              @page="selectedView === 'overdue' ? onOverduePageChange($event) : onUnscheduledPageChange($event)"
              class="custom-paginator"
            />
          </div>
        </main>
      </div>
    </div>

    <!-- FAB, Dialogs, Sidebars -->
    <FloatingActionButton
      icon="pi pi-plus"
      :label="t('tasks.create_task')"
      position="bottom-left"
      @click="handleCreateTask"
    />
    <TaskDialog
      v-model="isCreateDialogVisible"
      :task="editingTask"
      @task-saved="handleTaskSaved"
    />
    <TaskDetailsSidebar
      :visible="isDetailsOpen"
      :task="selectedTask"
      @update:visible="isDetailsOpen = $event"
      @task-updated="handleTaskUpdated"
      @task-deleted="handleTaskDeleted"
    />
    
    <!-- Advanced Filters Modal -->
    <AdvancedFiltersModal 
      :visible="isFiltersPanelVisible" 
      @update:visible="isFiltersPanelVisible = $event"
      @apply="handleFiltersApply"
    />
  </div>
</template>

<style scoped>
/* ===== Main Layout ===== */
.tasks-dashboard {
  min-height: 100vh;
  position: relative;
  background-color: #f8f9fa;
}

.dashboard-background {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}

/* ===== Top Controls ===== */
.top-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  gap: 1rem;
}

/* ===== Filters Panel ===== */
/* Filters Row */
.filters-row {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.quick-filters-wrapper {
  flex: 1;
  min-width: 0;
}

.advanced-filters-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.125rem;
  background: white;
  border: 1.5px dashed #dee2e6;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #495057;
  white-space: nowrap;
  cursor: pointer;
  transition: all 0.2s ease;
  flex-shrink: 0;
  align-self: flex-start;
}

.advanced-filters-btn i {
  font-size: 1rem;
  color: #6366f1;
  flex-shrink: 0;
}

.btn-text {
  color: inherit;
}

.advanced-filters-btn:hover {
  background: #f8f9fa;
  border-color: #6366f1;
  border-style: solid;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.filters-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.25rem;
  height: 1.25rem;
  padding: 0 0.313rem;
  background: #6366f1;
  color: white;
  border-radius: 8px;
  font-size: 0.688rem;
  font-weight: 700;
}

/* Mobile specific overrides */
@media (max-width: 768px) {
  .filters-row {
    flex-direction: row;
    align-items: flex-start;
    gap: 0.75rem;
    margin-bottom: 1rem;
  }

  .quick-filters-wrapper {
    flex: 1;
  }

  .advanced-filters-btn {
    padding: 0.625rem;
    min-width: 3.5rem;
    flex-direction: column;
    gap: 0.25rem;
  }

  .advanced-filters-btn .btn-text {
    display: none;
  }

  .advanced-filters-btn i {
    font-size: 1.25rem;
  }

  .filters-count {
    position: absolute;
    top: -0.375rem;
    right: -0.375rem;
  }
}

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
    rgba(102, 126, 234, 0.06) 0%,
    transparent 40%
  ),
  radial-gradient(
    circle at 80% 80%,
    rgba(118, 75, 162, 0.06) 0%,
    transparent 40%
  );
}

.dashboard-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  padding: 1rem 0;
}

.header-content {
  max-width: 1600px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.header-nav {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.header-nav :deep(.p-button) {
  font-weight: 500;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.mobile-filter-button {
  margin-right: 0.5rem;
}

.header-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0;
}

.header-subtitle {
  color: #718096;
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 500;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.dashboard-container {
  position: relative;
  z-index: 1;
  max-width: 1600px;
  margin: 0 auto;
  padding: 2rem;
}

.dashboard-layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 2rem;
  align-items: flex-start;
}

.sidebar {
  position: sticky;
  top: 100px;
}

.main-content {
  min-height: 500px;
  padding-bottom: 6rem; /* Space for floating buttons */
}

@media (max-width: 768px) {
  .main-content {
    padding-bottom: 7rem; /* Extra space for mobile floating buttons */
  }
}

/* ===== Mobile Search ===== */
.mobile-search-container {
  margin-bottom: 1.5rem;
  position: relative;
}

/* ===== Desktop View Toggle ===== */
.view-toggle {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
  margin-bottom: 1rem;
}

.view-toggle :deep(.p-button .p-button-icon) {
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.mobile-search-container :deep(.p-inputtext) {
  border-radius: 12px;
  border: 2px solid #e2e8f0;
  padding-left: 2.75rem;
  font-size: 1rem;
  width: 100%;
}

.mobile-search-container :deep(.p-input-icon-left > i) {
  left: 1rem;
  color: #a0aec0;
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
}


/* ===== Empty & Loading States ===== */
.empty-state, .tasks-container {
  padding-top: 1rem;
}

.empty-state {
  background: white;
  border-radius: 12px;
  padding: 4rem 2rem;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(226, 232, 240, 0.5);
}

.empty-state-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 1.5rem;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
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
  margin: 0;
  font-size: 1rem;
}

/* ===== Task Groups ===== */
.tasks-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

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
  padding: 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  position: sticky;
  top: 85px; /* Adjust based on header height */
  background: linear-gradient(to bottom, #f8f9fa 80%, transparent 100%);
  z-index: 10;
}

.task-group-count {
  font-size: 0.875rem;
  font-weight: 600;
  color: #667eea;
  background: #eef2ff;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
}

.task-group-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.25rem;
}

/* ===== List View Styles ===== */
.list-container {
  padding: 0;
  margin: 0;
}

.list-group {
  margin-bottom: 2rem;
}

.group-date-header {
  padding: 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  position: sticky;
  top: 85px; /* align with cards header */
  background: linear-gradient(to bottom, #f8f9fa 80%, transparent 100%);
  z-index: 10;
}

.group-date-label {
  font-size: 0.875rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.tasks-list { display: flex; flex-direction: column; gap: 0.5rem; }

.task-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  background: white;
  border-radius: 12px;
  border: 1px solid rgba(226,232,240,0.6);
  box-shadow: 0 1px 2px rgba(0,0,0,0.03);
  cursor: pointer;
}

.task-item.task-completed { opacity: 0.75; background: #f8fafc; }

.task-checkbox {
  width: 20px; height: 20px; margin-top: 0.125rem; cursor: pointer;
  border: 2px solid #cbd5e1; border-radius: 4px; appearance: none; -webkit-appearance: none;
}
.task-checkbox:checked { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-color: #667eea; }
.task-checkbox:checked::after { content: '✓'; display: block; color: white; font-size: 0.75rem; text-align: center; line-height: 16px; }

.task-content { flex: 1; display: flex; flex-direction: column; gap: 0.25rem; }
.task-title-row { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; }
.task-title { font-size: 0.9375rem; color: #1e293b; font-weight: 500; }
.task-title.completed { text-decoration: line-through; color: #94a3b8; }
.task-arrow { color: #cbd5e1; }
.task-item:hover .task-arrow { color: #667eea; }
.task-meta { display: flex; gap: 0.75rem; flex-wrap: wrap; font-size: 0.8125rem; }
.task-tag { color: #667eea; font-weight: 500; }
.task-subtasks { color: #64748b; font-weight: 500; }
.task-time { font-size: 0.8125rem; color: #94a3b8; font-weight: 500; white-space: nowrap; }

/* ===== Mobile & Responsive Design ===== */
@media (max-width: 1200px) {
  .task-group-list {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  }
}

@media (max-width: 1024px) {
  .dashboard-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .dashboard-container {
    padding: 1rem;
  }
  .header-content {
    padding: 0 1rem;
  }
  .header-title {
    font-size: 1.5rem;
  }
  .header-subtitle {
    display: none;
  }
  .task-group-list {
    grid-template-columns: 1fr;
  gap: 0.75rem;
  }
}

.paginator-wrapper {
  margin-top: 2rem;
  display: flex;
  justify-content: center;
}

.custom-paginator {
  --paginator-bg: #ffffff;
  --paginator-border: rgba(226, 232, 240, 0.8);
  --paginator-hover: #f8fafc;
  background: var(--paginator-bg);
  border: 1px solid var(--paginator-border);
  border-radius: 14px;
  box-shadow: 0 12px 32px rgba(100, 116, 139, 0.12);
  padding: 0.5rem 1rem;
  animation: fadeIn 0.25s ease;
}

.custom-paginator :deep(.p-paginator-page) {
  border-radius: 10px;
  margin: 0 0.25rem;
  transition: all 0.2s ease;
}

.custom-paginator :deep(.p-paginator-page:not(.p-highlight):hover) {
  background: var(--paginator-hover);
  transform: translateY(-1px);
}

.custom-paginator :deep(.p-paginator-page.p-highlight) {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  border: none;
  color: white;
  box-shadow: 0 8px 18px rgba(99, 102, 241, 0.35);
}

.custom-paginator :deep(.p-paginator-prev),
.custom-paginator :deep(.p-paginator-next),
.custom-paginator :deep(.p-paginator-first),
.custom-paginator :deep(.p-paginator-last) {
  border-radius: 10px;
  transition: all 0.2s ease;
}

.custom-paginator :deep(.p-paginator-prev:not(.p-disabled):hover),
.custom-paginator :deep(.p-paginator-next:not(.p-disabled):hover),
.custom-paginator :deep(.p-paginator-first:not(.p-disabled):hover),
.custom-paginator :deep(.p-paginator-last:not(.p-disabled):hover) {
  background: var(--paginator-hover);
  transform: translateY(-1px);
}

.fade-up-enter-active,
.fade-up-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.fade-up-enter-from,
.fade-up-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
  .paginator-wrapper {
    padding: 0 0.75rem;
  }

  .custom-paginator {
    width: 100%;
    padding: 0.65rem 0.85rem;
    border-radius: 16px;
    border: none;
    box-shadow: 0 18px 45px rgba(79, 70, 229, 0.18);
    background: linear-gradient(160deg, rgba(255, 255, 255, 0.9) 0%, rgba(244, 246, 255, 0.95) 100%);
    backdrop-filter: blur(10px);
  }

  .custom-paginator {
    overflow: hidden;
  }

  .custom-paginator :deep(.p-paginator),
  .custom-paginator :deep(.p-paginator-content) {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    width: 100%;
  }

  .custom-paginator :deep(.p-paginator-content > *) {
    flex-shrink: 0;
  }

  .custom-paginator :deep(.p-paginator-first),
  .custom-paginator :deep(.p-paginator-last),
  .custom-paginator :deep(.p-dropdown),
  .custom-paginator :deep(.p-paginator-current),
  .custom-paginator :deep(.p-paginator-pages .p-paginator-page-break) {
    display: none !important;
  }

  .custom-paginator :deep(.p-paginator-prev),
  .custom-paginator :deep(.p-paginator-next) {
    display: inline-flex !important;
    flex-shrink: 0;
    width: 34px;
    height: 34px;
    border-radius: 12px;
    margin: 0;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.92);
    border: 1px solid rgba(99, 102, 241, 0.16);
    color: #475569;
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.18);
    cursor: pointer;
  }

  .custom-paginator :deep(.p-paginator-prev:not(.p-disabled):hover),
  .custom-paginator :deep(.p-paginator-next:not(.p-disabled):hover) {
    background: rgba(99, 102, 241, 0.08);
    transform: translateY(-1px);
  }

  .custom-paginator :deep(.p-paginator-pages) {
    display: flex !important;
    flex-shrink: 0;
    align-items: center;
    gap: 0.3rem;
    flex-wrap: nowrap;
  }

  .custom-paginator :deep(.p-paginator-page) {
    display: inline-flex !important;
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    border-radius: 10px;
    margin: 0;
    font-size: 0.75rem;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(99, 102, 241, 0.16);
    color: #475569;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.15);
    align-items: center;
    justify-content: center;
  cursor: pointer;
}

  .custom-paginator :deep(.p-paginator-page.p-highlight) {
    background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%);
    color: #fff;
    box-shadow: 0 12px 26px rgba(99, 102, 241, 0.48);
  }
}
</style>
