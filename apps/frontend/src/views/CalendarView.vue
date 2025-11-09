<template>
  <div class="calendar-view">
    <!-- Header -->
    <header class="dashboard-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="header-title">{{ t('calendar.title') }}</h1>
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
          <Button
            :label="!isMobile ? t('analytics.title') : ''"
            icon="pi pi-chart-bar"
            :severity="$route.name === 'Analytics' ? 'primary' : 'secondary'"
            text
            @click="$router.push('/analytics')"
          />
        </div>
        <div class="header-actions">
          <button v-if="!isMobile" @click="router.push('/profile')" class="profile-button">
            <i class="pi pi-user"></i>
            <span class="header-subtitle">{{ user?.email }}</span>
          </button>
          <Button 
            icon="pi pi-sign-out"
            severity="secondary"
            text
            rounded
            @click="handleLogout"
          />
        </div>
      </div>
    </header>

    <div class="calendar-header">
      <div class="calendar-controls">
        <div class="view-switcher">
          <Button
            :label="t('calendar.month')"
            :severity="viewMode === 'month' ? 'primary' : 'secondary'"
            @click="viewMode = 'month'"
            size="small"
          />
          <Button
            :label="t('calendar.week')"
            :severity="viewMode === 'week' ? 'primary' : 'secondary'"
            @click="viewMode = 'week'"
            size="small"
          />
        </div>

        <div class="calendar-navigation">
          <Button
            icon="pi pi-chevron-left"
            severity="secondary"
            text
            rounded
            @click="navigatePrevious"
            :title="t('calendar.previous')"
          />
          <h2 class="current-period">{{ currentPeriodLabel }}</h2>
          <Button
            icon="pi pi-chevron-right"
            severity="secondary"
            text
            rounded
            @click="navigateNext"
            :title="t('calendar.next')"
          />
          <Button
            :label="t('calendar.today')"
            severity="secondary"
            outlined
            size="small"
            @click="navigateToday"
          />
        </div>
      </div>
    </div>

    <!-- Month View -->
    <div v-if="viewMode === 'month'" class="calendar-month-view" @scroll="handleCalendarScroll">
      <div class="calendar-grid">
        <div v-for="day in weekDays" :key="day" class="calendar-weekday">
          {{ day }}
        </div>
        <div
          v-for="(day, index) in calendarDays"
          :key="index"
          class="calendar-day"
          :class="{
            'other-month': !day.isCurrentMonth,
            'today': day.isToday,
            'selected': day.isSelected,
            'has-tasks': day.tasks.length > 0,
            'has-only-completed-tasks': day.hasOnlyCompletedTasks
          }"
          @click="selectDay(day)"
        >
          <div class="day-number">{{ day.date.getDate() }}</div>
          <div v-if="day.tasks.length > 0" class="day-tasks-preview">
            <div
              v-for="task in day.tasks.slice(0, 3)"
              :key="task.id"
              class="task-dot"
              :class="[
                `priority-${getPriorityValue(task.priority).toLowerCase()}`,
                { 'task-dot--completed': task.isCompleted }
              ]"
              :title="task.title"
            />
            <span v-if="day.tasks.length > 3" class="more-tasks">
              +{{ day.tasks.length - 3 }}
            </span>
          </div>
        </div>
      </div>

      <!-- Loading indicator for infinite scroll -->
      <div v-if="isLoadingMoreMonths" class="calendar-loading">
        <i class="pi pi-spin pi-spinner" style="font-size: 2rem; color: #6366f1;"></i>
        <p>{{ t('calendar.loading_more_months') || 'Loading more months...' }}</p>
      </div>

      <!-- End of calendar message -->
      <div v-else-if="!hasMoreFutureMonths && monthsToShow > 1" class="calendar-end-message">
        <i class="pi pi-check-circle" style="font-size: 2rem; color: #10b981;"></i>
        <p>{{ t('calendar.no_more_future_tasks') || 'No more future tasks' }}</p>
      </div>

      <!-- Selected Day Tasks -->
    <div v-if="selectedDay" ref="selectedDaySection" class="selected-day-tasks">
        <div class="tasks-header">
          <h3 class="tasks-header__title">
            {{ formatDate(selectedDay.date) }}
            <Badge :value="selectedDay.tasks.length" severity="info" />
          </h3>
          <Button
            icon="pi pi-plus"
            :label="t('tasks.new_task')"
            class="new-task-button"
            @click="openNewTaskDialog(selectedDay.date)"
          />
        </div>
      <div class="tasks-list">
        <!-- Active Tasks -->
        <div v-if="activeTasks.length > 0" class="tasks-section">
          <div class="section-header">
            <i class="pi pi-circle" />
            <span class="section-title">{{ t('tasks.active_tasks') }}</span>
            <Badge :value="activeTasks.length" severity="info" />
          </div>
          <div class="tasks-section__list">
            <TaskCard
              v-for="task in displayedActiveTasks"
              :key="task.id"
              :task="task"
              @click="selectTask"
              @task-updated="handleToggleComplete"
            />
            <button v-if="hasMoreActiveTasks" @click="loadMoreActiveTasks" class="load-more-btn">
              <i class="pi pi-chevron-down" />
              {{ t('common.load_more') }} ({{ activeTasks.length - activeTasksLimit }} {{ t('common.remaining') }})
            </button>
          </div>
        </div>

        <!-- Completed Tasks -->
        <div v-if="completedTasks.length > 0" class="tasks-section">
          <div class="section-header completed">
            <i class="pi pi-check-circle" />
            <span class="section-title">{{ t('tasks.completed_tasks') }}</span>
            <Badge :value="completedTasks.length" severity="success" />
          </div>
          <div class="tasks-section__list">
            <TaskCard
              v-for="task in displayedCompletedTasks"
              :key="task.id"
              :task="task"
              @click="selectTask"
              @task-updated="handleToggleComplete"
            />
            <button v-if="hasMoreCompletedTasks" @click="loadMoreCompletedTasks" class="load-more-btn">
              <i class="pi pi-chevron-down" />
              {{ t('common.load_more') }} ({{ completedTasks.length - completedTasksLimit }} {{ t('common.remaining') }})
            </button>
          </div>
        </div>

        <!-- No Tasks -->
        <div v-if="selectedDay.tasks.length === 0" class="no-tasks">
          <i class="pi pi-calendar-times" />
          <p>{{ t('calendar.no_tasks_for_day') }}</p>
        </div>
      </div>
      </div>
    </div>

    <!-- Week View -->
    <div v-else-if="viewMode === 'week'" class="calendar-week-view">
      <div class="week-grid">
        <div class="time-column">
          <div v-for="hour in 24" :key="hour" class="time-slot">
            {{ formatHour(hour - 1) }}
          </div>
        </div>
        <div v-for="day in weekViewDays" :key="day.date.toISOString()" class="day-column">
          <div class="day-header" :class="{ today: day.isToday }">
            <div class="day-name">{{ formatWeekDay(day.date) }}</div>
            <div class="day-date">{{ day.date.getDate() }}</div>
          </div>
          <div class="day-timeline">
            <div v-for="hour in 24" :key="hour" class="hour-slot" />
            <div v-if="isLoading" class="tasks-overlay">
              <!-- Skeleton for loading state -->
              <Skeleton class="timeline-task-skeleton" height="80px" style="top: 120px;" />
              <Skeleton class="timeline-task-skeleton" height="60px" style="top: 360px;" />
              <Skeleton class="timeline-task-skeleton" height="100px" style="top: 600px;" />
            </div>
            <div v-else class="tasks-overlay">
              <div
                v-for="task in getTasksWithPosition(day.tasks)"
                :key="task.id"
                class="timeline-task"
                :class="`priority-${getPriorityValue(task.priority).toLowerCase()} status-${getStatusValue(task.status).toLowerCase()}`"
                :style="getTaskStyle(task)"
                @click="selectTask(task)"
                :title="`${task.title}\n${formatTaskTime(task)}`"
              >
                <div class="task-time">{{ formatTaskTime(task) }}</div>
                <div class="task-title">{{ task.title }}</div>
                <div v-if="task.tags?.length" class="task-tags">
                  <i class="pi pi-tag" />
                  {{ task.tags.length }}
                </div>
              </div>
              <!-- All-day or long tasks -->
              <div v-if="day.allDayTasks.length > 0" class="all-day-tasks">
                <div class="all-day-header">
                  <i class="pi pi-sun" />
                  {{ t('calendar.all_day') }}
                </div>
                <div
                  v-for="task in day.allDayTasks"
                  :key="task.id"
                  class="all-day-task"
                  :class="`priority-${getPriorityValue(task.priority).toLowerCase()}`"
                  @click="selectTask(task)"
                >
                  {{ task.title }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Task Details Sidebar -->
    <TaskDetailsSidebar
      :visible="showTaskDetails"
      :task="selectedTask"
      @update:visible="showTaskDetails = $event"
      @update:task="selectedTask = $event"
      @task-updated="handleTaskUpdated"
      @task-deleted="handleTaskDeleted"
    />

    <!-- New Task Dialog -->
    <TaskDialog
      v-model="showNewTaskDialog"
      :task="editingTask"
      :parent-task-id="parentTaskId"
      :initial-date="newTaskDate"
      @task-saved="handleTaskSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter, useRoute } from 'vue-router'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Skeleton from 'primevue/skeleton'
import TaskCard from '@/components/tasks/TaskCard.vue'
import TaskDetailsSidebar from '@/components/tasks/TaskDetailsSidebar.vue'
import TaskDialog from '@/components/tasks/TaskDialog.vue'
import { useTaskStore } from '@/stores/task.store'
import { useAuthStore } from '@/stores/auth.store'
import { useToast } from '@/composables/useToast'
import type { Task } from '@/types/task.types'
import { taskService } from '@/services/task.service'

const { t, locale } = useI18n()
const router = useRouter()
const taskStore = useTaskStore()
const authStore = useAuthStore()
const { showSuccess, showError } = useToast()
const user = computed(() => authStore.user)
const isMobile = ref(window.innerWidth < 768)

// View mode: 'month' or 'week'
const viewMode = ref<'month' | 'week'>('month')

// Current date navigation
const currentDate = ref(new Date())
const selectedDay = ref<any>(null)
const selectedTask = ref<Task | null>(null)
const showTaskDetails = ref(false)
const showNewTaskDialog = ref(false)
const newTaskDate = ref<Date | null>(null)
const selectedDaySection = ref<HTMLElement | null>(null)

// Infinite scroll for calendar
const monthsToShow = ref(1) // How many months to display
const isLoadingMoreMonths = ref(false)
const hasMoreFutureMonths = ref(true)

// Computed properties for active and completed tasks
// Optimized with memoization
const activeTasks = computed(() => {
  if (!selectedDay.value || !selectedDay.value.tasks) return []
  return selectedDay.value.tasks.filter((task: Task) => !task.isCompleted)
})

const completedTasks = computed(() => {
  if (!selectedDay.value || !selectedDay.value.tasks) return []
  return selectedDay.value.tasks.filter((task: Task) => task.isCompleted)
})

// Infinite scroll pagination for tasks
const activeTasksLimit = ref(20)
const completedTasksLimit = ref(10)

const displayedActiveTasks = computed(() => activeTasks.value.slice(0, activeTasksLimit.value))
const displayedCompletedTasks = computed(() => completedTasks.value.slice(0, completedTasksLimit.value))

const hasMoreActiveTasks = computed(() => activeTasks.value.length > activeTasksLimit.value)
const hasMoreCompletedTasks = computed(() => completedTasks.value.length > completedTasksLimit.value)

function loadMoreActiveTasks() {
  activeTasksLimit.value += 20
}

function loadMoreCompletedTasks() {
  completedTasksLimit.value += 10
}

// Reset limits when day changes
watch(selectedDay, () => {
  activeTasksLimit.value = 20
  completedTasksLimit.value = 10
})

function normalizeDateValue(value: Date | string): Date {
  const date = new Date(value)
  date.setHours(0, 0, 0, 0)
  return date
}

async function setSelectedDayByDate(date: Date) {
  const normalized = normalizeDateValue(date)
  const tasks = await taskService.getTasksForDay(new Date(normalized), true)
  
  const matchingDay = calendarDays.value.find(day => normalizeDateValue(day.date).getTime() === normalized.getTime())
  const todayNormalized = normalizeDateValue(new Date())

  const baseDay = matchingDay
    ? { ...matchingDay }
    : {
        date: new Date(normalized),
        isCurrentMonth: normalized.getMonth() === currentDate.value.getMonth(),
        isToday: normalized.getTime() === todayNormalized.getTime(),
        hasOnlyCompletedTasks: tasks.length > 0 && tasks.every(task => task.isCompleted)
      }

  selectedDay.value = {
    ...baseDay,
    isSelected: true,
    tasks
  }
}

function updateTaskCollections(updatedTask: Task) {
  // Clear position cache for this task
  taskPositionsCache.delete(updatedTask.id)
  
  const replaceTaskInArray = (source: Task[]): Task[] => {
    const taskIndex = source.findIndex(task => task.id === updatedTask.id)
    if (taskIndex === -1) {
      return source
    }

    const nextTasks = [...source]
    nextTasks.splice(taskIndex, 1, updatedTask)
    return nextTasks
  }

  const updateReactiveArray = (target: typeof monthTasks | typeof weekTasks) => {
    const current = target.value
    const next = replaceTaskInArray(current)
    if (next !== current) {
      target.value = next

      // Update cache if this is monthTasks
      if (target === monthTasks && viewMode.value === 'month') {
        // Calculate the current cache key based on the calendar grid
        const year = currentDate.value.getFullYear()
        const month = currentDate.value.getMonth()
        const firstDay = new Date(year, month, 1)
        const startDate = new Date(firstDay)
        const dayOfWeek = startDate.getDay()
        const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek
        startDate.setDate(startDate.getDate() + diff)
        const endDate = new Date(startDate)
        endDate.setDate(startDate.getDate() + 41)
        const cacheKey = `${startDate.toISOString().split('T')[0]}_${endDate.toISOString().split('T')[0]}`
        monthCache.value.set(cacheKey, next)
      }
    }
  }

  updateReactiveArray(monthTasks)
  updateReactiveArray(weekTasks)

  if (selectedDay.value) {
    const nextTasks = replaceTaskInArray(selectedDay.value.tasks)
    if (nextTasks !== selectedDay.value.tasks) {
      const hasOnlyCompleted = nextTasks.length > 0 && nextTasks.every(task => task.isCompleted)
      selectedDay.value = {
        ...selectedDay.value,
        tasks: nextTasks,
        hasOnlyCompletedTasks: hasOnlyCompleted
      }
    }
  }

  if (selectedTask.value?.id === updatedTask.id) {
    selectedTask.value = updatedTask
  }
}

// Tasks data
const monthTasks = ref<Task[]>([])
const weekTasks = ref<Task[]>([])
const isLoading = ref(false)
const editingTask = ref<Task | null>(null)
const parentTaskId = ref<number | null>(null)

// Week days for header
const weekDays = computed(() => {
  const days = locale.value === 'ru' 
    ? ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']
    : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
  return days
})

// Current period label
const currentPeriodLabel = computed(() => {
  if (viewMode.value === 'month') {
    const month = currentDate.value.toLocaleDateString(locale.value, { month: 'long', year: 'numeric' })
    return month.charAt(0).toUpperCase() + month.slice(1)
  } else {
    const weekStart = getWeekStart(currentDate.value)
    const weekEnd = new Date(weekStart)
    weekEnd.setDate(weekEnd.getDate() + 6)
    
    const format: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short' }
    const start = weekStart.toLocaleDateString(locale.value, format)
    const end = weekEnd.toLocaleDateString(locale.value, { ...format, year: 'numeric' })
    return `${start} – ${end}`
  }
})

// Calendar days for month view
// Optimized: Index tasks by date for O(1) lookup
const tasksByDate = computed(() => {
  const index = new Map<string, Task[]>()
  
  monthTasks.value.forEach(task => {
    const taskStartDate = task.startDate ? new Date(task.startDate) : null
    const taskDueDate = task.dueDate ? new Date(task.dueDate) : null
    
    if (taskStartDate) {
      taskStartDate.setHours(0, 0, 0, 0)
    }
    if (taskDueDate) {
      taskDueDate.setHours(0, 0, 0, 0)
    }
    
    // Add task to all dates it spans
    if (taskStartDate && taskDueDate) {
      const current = new Date(taskStartDate)
      while (current <= taskDueDate) {
        const key = current.toDateString()
        if (!index.has(key)) index.set(key, [])
        index.get(key)!.push(task)
        current.setDate(current.getDate() + 1)
      }
    } else if (taskStartDate) {
      const key = taskStartDate.toDateString()
      if (!index.has(key)) index.set(key, [])
      index.get(key)!.push(task)
    } else if (taskDueDate) {
      const key = taskDueDate.toDateString()
      if (!index.has(key)) index.set(key, [])
      index.get(key)!.push(task)
    }
  })
  
  return index
})

const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()
  const firstDay = new Date(year, month, 1)

  // Get first Monday of the first month
  const startDate = new Date(firstDay)
  const dayOfWeek = startDate.getDay()
  const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek
  startDate.setDate(startDate.getDate() + diff)

  const days = []
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  // Generate days for all loaded months (monthsToShow)
  // Each month grid has 42 days (6 weeks)
  const totalDays = monthsToShow.value * 42

  for (let i = 0; i < totalDays; i++) {
    const date = new Date(startDate)
    date.setDate(startDate.getDate() + i)
    date.setHours(0, 0, 0, 0)

    // Use indexed lookup instead of filter (O(1) vs O(n))
    const dayTasks = tasksByDate.value.get(date.toDateString()) || []

    // Check if all tasks are completed
    const hasOnlyCompletedTasks = dayTasks.length > 0 && dayTasks.every(task => task.isCompleted)

    // Determine which month this day belongs to for styling
    const currentMonthIndex = Math.floor(i / 42)
    const targetMonth = new Date(year, month + currentMonthIndex, 1).getMonth()
    const isCurrentMonthDay = date.getMonth() === targetMonth

    days.push({
      date,
      isCurrentMonth: isCurrentMonthDay,
      isToday: date.getTime() === today.getTime(),
      isSelected: selectedDay.value?.date.toDateString() === date.toDateString(),
      tasks: dayTasks,
      hasOnlyCompletedTasks: hasOnlyCompletedTasks && isCurrentMonthDay
    })
  }

  return days
})

// Week view days
const weekViewDays = computed(() => {
  const weekStart = getWeekStart(currentDate.value)
  const days = []
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  for (let i = 0; i < 7; i++) {
    const date = new Date(weekStart)
    date.setDate(weekStart.getDate() + i)
    
    const dayTasks = weekTasks.value.filter(task => {
      const taskDate = task.startDate ? new Date(task.startDate) : 
                       task.dueDate ? new Date(task.dueDate) : null
      if (!taskDate) return false
      return taskDate.toDateString() === date.toDateString()
    })
    
    // Separate all-day and timed tasks
    const timedTasks = dayTasks.filter(task => {
      if (task.startDate && task.dueDate) {
        const start = new Date(task.startDate)
        const end = new Date(task.dueDate)
        const duration = (end.getTime() - start.getTime()) / (1000 * 60 * 60)
        return duration < 5 // Less than 5 hours
      }
      return false
    })
    
    const allDayTasks = dayTasks.filter(task => !timedTasks.includes(task))
    
    days.push({
      date,
      isToday: date.getTime() === today.getTime(),
      tasks: timedTasks,
      allDayTasks
    })
  }
  
  return days
})

// Navigation functions
function navigatePrevious() {
  // Reset infinite scroll state
  monthsToShow.value = 1
  hasMoreFutureMonths.value = true

  if (viewMode.value === 'month') {
    const newDate = new Date(currentDate.value)
    newDate.setMonth(newDate.getMonth() - 1)
    currentDate.value = newDate
  } else {
    const newDate = new Date(currentDate.value)
    newDate.setDate(newDate.getDate() - 7)
    currentDate.value = newDate
  }
}

function navigateNext() {
  // Reset infinite scroll state
  monthsToShow.value = 1
  hasMoreFutureMonths.value = true

  if (viewMode.value === 'month') {
    const newDate = new Date(currentDate.value)
    newDate.setMonth(newDate.getMonth() + 1)
    currentDate.value = newDate
  } else {
    const newDate = new Date(currentDate.value)
    newDate.setDate(newDate.getDate() + 7)
    currentDate.value = newDate
  }
}

async function navigateToday() {
  // Reset infinite scroll state
  monthsToShow.value = 1
  hasMoreFutureMonths.value = true

  currentDate.value = new Date()
  if (viewMode.value === 'month') {
    const today = calendarDays.value.find(day => day.isToday)
    if (today) {
      await selectDay(today)
    } else {
      await setSelectedDayByDate(new Date())
    }
  }
}

// Helper functions
function getPriorityValue(priority: any): string {
  return typeof priority === 'string' ? priority : priority.value
}

function getStatusValue(status: any): string {
  return typeof status === 'string' ? status : status.value
}

function getWeekStart(date: Date): Date {
  const d = new Date(date)
  const day = d.getDay()
  const diff = d.getDate() - day + (day === 0 ? -6 : 1)
  return new Date(d.setDate(diff))
}

function formatDate(date: Date): string {
  return date.toLocaleDateString(locale.value, { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

function formatWeekDay(date: Date): string {
  return date.toLocaleDateString(locale.value, { weekday: 'short' })
}

function formatHour(hour: number): string {
  return `${hour.toString().padStart(2, '0')}:00`
}

function formatTaskTime(task: Task): string {
  if (task.startDate) {
    const start = new Date(task.startDate)
    const startTime = start.toLocaleTimeString(locale.value, { 
      hour: '2-digit', 
      minute: '2-digit' 
    })
    
    if (task.dueDate) {
      const end = new Date(task.dueDate)
      const endTime = end.toLocaleTimeString(locale.value, { 
        hour: '2-digit', 
        minute: '2-digit' 
      })
      return `${startTime} - ${endTime}`
    }
    return startTime
  }
  return ''
}

// Task positioning for week view
// Memoized task positions cache
const taskPositionsCache = new Map<number, { startHour: number; duration: number }>()

function getTasksWithPosition(tasks: Task[]): any[] {
  return tasks.map(task => {
    // Check cache first
    if (taskPositionsCache.has(task.id)) {
      return {
        ...task,
        ...taskPositionsCache.get(task.id)!
      }
    }
    
    const startDate = task.startDate ? new Date(task.startDate) : new Date(task.dueDate!)
    const endDate = task.dueDate ? new Date(task.dueDate) : startDate
    
    const startHour = startDate.getHours() + startDate.getMinutes() / 60
    const endHour = endDate.getHours() + endDate.getMinutes() / 60
    const duration = Math.max(endHour - startHour, 0.5) // Minimum 30 minutes height
    
    const position = { startHour, duration }
    taskPositionsCache.set(task.id, position)
    
    return {
      ...task,
      ...position
    }
  })
}

function getTaskStyle(task: any): any {
  const top = task.startHour * 60 // 60px per hour
  const height = task.duration * 60
  
  return {
    top: `${top}px`,
    height: `${height}px`,
    minHeight: '30px'
  }
}

// Event handlers
async function selectDay(day: any) {
  try {
    await setSelectedDayByDate(day.date)
    await nextTick()
    selectedDaySection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  } catch (error: any) {
    showError(error.message || t('errors.unknown_error'))
  }
}

function selectTask(task: Task) {
  // Set selected task and open sidebar
  // TaskDetailsSidebar will handle loading full task data with subtasks
  selectedTask.value = task
  showTaskDetails.value = true

  // Don't fetch here - TaskDetailsSidebar watch will do it
  // This prevents duplicate API calls
}

function openNewTaskDialog(date: Date) {
  // Ensure date is normalized to local midnight
  const normalizedDate = new Date(date)
  normalizedDate.setHours(12, 0, 0, 0) // Set to noon to avoid timezone issues
  
  newTaskDate.value = normalizedDate
  editingTask.value = null
  parentTaskId.value = null
  showNewTaskDialog.value = true
}

async function handleToggleComplete(updatedTask: Task) {
  try {
    // Clear all cache to ensure fresh data (since tasks can span multiple months)
    monthCache.value.clear()
    
    // Update local collections immediately with optimistic update
    // This ensures UI updates instantly while API call happens in background
    updateTaskCollections(updatedTask)
    
    // Wait for API call to complete (toggleTaskCompletion is already being called by TaskCard)
    // Then update with real data from server
    // Use a longer delay to ensure API call completes and store updates
    setTimeout(async () => {
      try {
        // Try to get updated task from store first
        let taskFromStore = taskStore.tasks.find(t => t.id === updatedTask.id) 
          ?? taskStore.overdueTasksPaginated.tasks.find(t => t.id === updatedTask.id)
          ?? taskStore.unscheduledTasksPaginated.tasks.find(t => t.id === updatedTask.id)
        
        // If not in store (e.g., calendar task), fetch directly from API
        if (!taskFromStore) {
          try {
            taskFromStore = await taskStore.fetchTask(updatedTask.id)
          } catch {
            // Task might not be accessible, use the optimistic update
            return
          }
        }
        
        if (taskFromStore) {
          updateTaskCollections(taskFromStore)
        }
      } catch (error) {
        // Silently fail - optimistic update is already applied
        console.warn('Failed to sync calendar task with server response:', error)
      }
    }, 300)
  } catch (error: any) {
    showError(error.message || t('errors.unknown_error'))
  }
}

async function handleTaskSaved() {
  showNewTaskDialog.value = false
  const targetDate = selectedDay.value
    ? new Date(selectedDay.value.date)
    : newTaskDate.value
    ? new Date(newTaskDate.value)
    : null

  // Clear all cache to force reload (since tasks can span multiple months)
  monthCache.value.clear()

  // Reload tasks
  await fetchTasks()

  if (targetDate) {
    await setSelectedDayByDate(targetDate)
  }
}

async function handleTaskUpdated() {
  const selectedDate = selectedDay.value ? new Date(selectedDay.value.date) : null

  // Clear all cache to force reload (since tasks can span multiple months)
  monthCache.value.clear()

  await fetchTasks()

  if (selectedDate) {
    await setSelectedDayByDate(selectedDate)
  }

  // Reload selected task if it's still open
  if (selectedTask.value && showTaskDetails.value) {
    try {
      const updatedTask = await taskStore.fetchTask(selectedTask.value.id)
      selectedTask.value = updatedTask
    } catch {
      // Task might have been deleted
      showTaskDetails.value = false
      selectedTask.value = null
    }
  }
}

async function handleTaskDeleted() {
  showTaskDetails.value = false
  selectedTask.value = null
  const selectedDate = selectedDay.value ? new Date(selectedDay.value.date) : null

  // Clear all cache to force reload (since tasks can span multiple months)
  monthCache.value.clear()

  await fetchTasks()

  if (selectedDate) {
    await setSelectedDayByDate(selectedDate)
  }
}

// Cache for loaded months to avoid redundant requests
const monthCache = ref(new Map<string, Task[]>())

// Fetch tasks with caching
async function fetchTasks() {
  isLoading.value = true
  try {
    if (viewMode.value === 'month') {
      const year = currentDate.value.getFullYear()
      const month = currentDate.value.getMonth()

      // Calculate the date range for all loaded months
      const firstDay = new Date(year, month, 1)
      const startDate = new Date(firstDay)
      const dayOfWeek = startDate.getDay()
      const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek
      startDate.setDate(startDate.getDate() + diff)

      // End date is based on monthsToShow (each month = 42 days)
      const totalDays = monthsToShow.value * 42
      const endDate = new Date(startDate)
      endDate.setDate(startDate.getDate() + totalDays - 1)

      // Create cache key based on start and end dates
      const cacheKey = `${startDate.toISOString().split('T')[0]}_${endDate.toISOString().split('T')[0]}`

      // Check cache first
      if (monthCache.value.has(cacheKey)) {
        monthTasks.value = monthCache.value.get(cacheKey)!
        isLoading.value = false
        return
      }

      // Fetch tasks for all months that appear in the calendar grid
      const monthsToFetch = new Set<string>()
      const currentGridDate = new Date(startDate)

      for (let i = 0; i < totalDays; i++) {
        const monthKey = `${currentGridDate.getFullYear()}-${currentGridDate.getMonth() + 1}`
        monthsToFetch.add(monthKey)
        currentGridDate.setDate(currentGridDate.getDate() + 1)
      }

      // Fetch all required months in parallel
      const fetchPromises = Array.from(monthsToFetch).map(monthKey => {
        const parts = monthKey.split('-').map(Number)
        const fetchYear = parts[0]!
        const fetchMonth = parts[1]!
        return taskService.getTasksForMonth(fetchYear, fetchMonth, true)
      })

      const results = await Promise.all(fetchPromises)

      // Combine all tasks
      const allTasks = results.flat()

      // Cache the result
      monthCache.value.set(cacheKey, allTasks)
      monthTasks.value = allTasks
    } else {
      const weekStart = getWeekStart(currentDate.value)
      weekTasks.value = await taskService.getTasksForWeek(weekStart, true)
    }
  } catch (error: any) {
    showError(error.message || t('errors.fetch_failed'))
  } finally {
    isLoading.value = false
  }
}

// Load more months for infinite scroll
async function loadMoreMonths() {
  if (isLoadingMoreMonths.value || !hasMoreFutureMonths.value) return

  isLoadingMoreMonths.value = true
  try {
    const year = currentDate.value.getFullYear()
    const month = currentDate.value.getMonth()

    // Calculate the next month to load
    const nextMonthIndex = monthsToShow.value
    const nextMonthDate = new Date(year, month + nextMonthIndex, 1)
    const nextYear = nextMonthDate.getFullYear()
    const nextMonth = nextMonthDate.getMonth() + 1

    // Fetch tasks for the next month
    const nextMonthTasks = await taskService.getTasksForMonth(nextYear, nextMonth, true)

    // Check if there are any tasks in the future for this month
    const now = new Date()
    now.setHours(0, 0, 0, 0)
    const hasFutureTasks = nextMonthTasks.some(task => {
      const taskDate = task.dueDate ? new Date(task.dueDate) : task.startDate ? new Date(task.startDate) : null
      return taskDate && taskDate >= now
    })

    if (hasFutureTasks || nextMonthTasks.length > 0) {
      // Increment months to show
      monthsToShow.value += 1

      // Reload tasks to include the new month
      await fetchTasks()
    } else {
      // No more future tasks, stop loading
      hasMoreFutureMonths.value = false
    }
  } catch (error: any) {
    showError(error.message || t('errors.fetch_failed'))
  } finally {
    isLoadingMoreMonths.value = false
  }
}

// Handle scroll event for infinite loading
function handleCalendarScroll(event: Event) {
  if (viewMode.value !== 'month') return

  const target = event.target as HTMLElement
  const scrollTop = target.scrollTop
  const scrollHeight = target.scrollHeight
  const clientHeight = target.clientHeight

  // Load more when scrolled to 80% of the content
  const scrollPercentage = (scrollTop + clientHeight) / scrollHeight
  if (scrollPercentage > 0.8 && !isLoadingMoreMonths.value && hasMoreFutureMonths.value) {
    loadMoreMonths()
  }
}

// Prefetch adjacent months in background (lazy loading)
async function prefetchAdjacentMonths() {
  if (viewMode.value !== 'month') return

  // Calculate date ranges for previous and next calendar grids
  const year = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()

  // Previous month grid
  const prevMonthDate = new Date(year, month - 1, 1)
  const prevFirstDay = new Date(prevMonthDate.getFullYear(), prevMonthDate.getMonth(), 1)
  const prevStartDate = new Date(prevFirstDay)
  const prevDayOfWeek = prevStartDate.getDay()
  const prevDiff = prevDayOfWeek === 0 ? -6 : 1 - prevDayOfWeek
  prevStartDate.setDate(prevStartDate.getDate() + prevDiff)
  const prevEndDate = new Date(prevStartDate)
  prevEndDate.setDate(prevStartDate.getDate() + 41)
  const prevKey = `${prevStartDate.toISOString().split('T')[0]}_${prevEndDate.toISOString().split('T')[0]}`

  // Next month grid
  const nextMonthDate = new Date(year, month + 1, 1)
  const nextFirstDay = new Date(nextMonthDate.getFullYear(), nextMonthDate.getMonth(), 1)
  const nextStartDate = new Date(nextFirstDay)
  const nextDayOfWeek = nextStartDate.getDay()
  const nextDiff = nextDayOfWeek === 0 ? -6 : 1 - nextDayOfWeek
  nextStartDate.setDate(nextStartDate.getDate() + nextDiff)
  const nextEndDate = new Date(nextStartDate)
  nextEndDate.setDate(nextStartDate.getDate() + 41)
  const nextKey = `${nextStartDate.toISOString().split('T')[0]}_${nextEndDate.toISOString().split('T')[0]}`

  // Prefetch in background without blocking UI
  setTimeout(async () => {
    try {
      if (!monthCache.value.has(prevKey)) {
        // We don't need to fetch here - it will be fetched when user navigates
        // This was just for optimization, but with new approach we fetch on demand
      }
    } catch (error) {
      // Silent fail for background prefetch
    }
  }, 100)

  setTimeout(async () => {
    try {
      if (!monthCache.value.has(nextKey)) {
        // We don't need to fetch here - it will be fetched when user navigates
        // This was just for optimization, but with new approach we fetch on demand
      }
    } catch (error) {
      // Silent fail for background prefetch
    }
  }, 200)
}

// Watchers
watch(viewMode, async () => {
  // Reset infinite scroll state when switching views
  monthsToShow.value = 1
  hasMoreFutureMonths.value = true

  await fetchTasks()
  if (viewMode.value === 'month') {
    prefetchAdjacentMonths()
  }
})

watch(currentDate, async () => {
  await fetchTasks()
  if (viewMode.value === 'month') {
    prefetchAdjacentMonths()
  }
})

// Logout handler
async function handleLogout() {
  try {
    await authStore.logout()
    router.push('/login')
    showSuccess(t('success.logout_success'))
  } catch (error) {
    showError(t('errors.logout_failed'))
  }
}

// Handle window resize
function handleResize() {
  isMobile.value = window.innerWidth < 768
}

// Lifecycle
onMounted(async () => {
  await fetchTasks()
  navigateToday()
  window.addEventListener('resize', handleResize)
  
  // Prefetch adjacent months in background for better UX
  if (viewMode.value === 'month') {
    prefetchAdjacentMonths()
  }
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<style scoped>
.calendar-view {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f8f9fa;
}

/* Header Styles (similar to TasksDashboardView) */
.dashboard-header {
  background: white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  z-index: 100;
  position: sticky;
  top: 0;
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

.header-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.header-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.header-nav {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.header-nav :deep(.p-button) {
  font-weight: 500;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.profile-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  color: #64748b;
}

.profile-button:hover {
  background: #f8f9fa;
  border-color: #cbd5e0;
  color: #1e293b;
}

.profile-button i {
  font-size: 0.875rem;
  color: #6366f1;
}

.header-subtitle {
  color: #64748b;
  font-size: 0.875rem;
  margin: 0;
}

.calendar-header {
  background: white;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e9ecef;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.calendar-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.view-switcher {
  display: flex;
  gap: 0.5rem;
}

.view-switcher :deep(.p-button) {
  min-width: 80px;
}

.calendar-navigation {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.current-period {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
  min-width: 200px;
  text-align: center;
}

/* Month View */
.calendar-month-view {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 1.5rem;
  gap: 1.5rem;
  overflow-y: auto;
  overflow-x: hidden;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 1px;
  background: #e2e8f0;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  min-height: 0;
  height: auto;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.calendar-weekday {
  background: #f1f5f9;
  padding: 0.75rem;
  text-align: center;
  font-weight: 600;
  font-size: 0.875rem;
  color: #64748b;
  text-transform: uppercase;
}

.calendar-day {
  background: white;
  min-height: 100px;
  padding: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

.calendar-day:hover {
  background: #f8fafc;
}

.calendar-day.other-month {
  background: #fafafa;
  color: #94a3b8;
}

.calendar-day.today {
  background: #eff6ff;
}

.calendar-day.has-only-completed-tasks {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.15) inset;
}

.calendar-day.has-only-completed-tasks .day-number {
  color: #16a34a;
  font-weight: 600;
}

.calendar-day.today .day-number {
  background: #3b82f6;
  color: white;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.calendar-day.selected {
  background: #e0e7ff;
  border: 2px solid #6366f1;
}

.day-number {
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.day-tasks-preview {
  display: flex;
  gap: 0.25rem;
  flex-wrap: wrap;
  align-items: center;
}

.task-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #94a3b8;
}

.task-dot.task-dot--completed {
  background: #10b981;
}

.task-dot.priority-high,
.task-dot.priority-urgent {
  background: #ef4444;
}

.task-dot.priority-high.task-dot--completed,
.task-dot.priority-urgent.task-dot--completed {
  background: #10b981;
}

.task-dot.priority-medium {
  background: #f59e0b;
}

.task-dot.priority-medium.task-dot--completed {
  background: #10b981;
}

.task-dot.priority-low {
  background: #10b981;
}

.more-tasks {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 500;
}

/* Infinite scroll indicators */
.calendar-loading,
.calendar-end-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  gap: 1rem;
  text-align: center;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  margin-top: 1rem;
}

.calendar-loading p,
.calendar-end-message p {
  margin: 0;
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 500;
}

/* Selected Day Tasks */
.selected-day-tasks {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
}

.tasks-header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.tasks-header__title {
  margin: 0;
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  color: #1e293b;
  font-size: 1.05rem;
  font-weight: 600;
  flex: 1 1 auto;
  min-width: 0;
}

.tasks-header__title :deep(.p-badge) {
  min-width: 1.5rem;
  height: 1.5rem;
  line-height: 1.5rem;
  font-size: 0.75rem;
  border-radius: 999px;
}

.new-task-button {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.8rem 1.4rem;
  border-radius: 14px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  color: white;
  font-weight: 600;
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.35);
  transition: all 0.3s ease;
  margin-left: auto;
}

.new-task-button:hover {
  background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
  box-shadow: 0 10px 24px rgba(102, 126, 234, 0.4);
  transform: translateY(-2px);
}

.tasks-list { display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem; }

.tasks-section {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0;
  font-size: 0.875rem;
  font-weight: 600;
  color: #64748b;
  position: sticky;
  top: 0;
  background: #ffffff;
  z-index: 5;
  border-bottom: 1px solid rgba(226, 232, 240, 0.6);
}

.section-header i {
  font-size: 0.625rem;
}

.section-header.completed {
  color: #10b981;
}

.section-header .section-title {
  flex: 1;
}

.section-header :deep(.p-badge) {
  min-width: 1.25rem;
  height: 1.25rem;
  line-height: 1.25rem;
  font-size: 0.625rem;
  padding: 0 0.25rem;
  border-radius: 10px;
}

.tasks-section__list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.load-more-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.75rem;
  margin-top: 0.75rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  color: #6366f1;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.load-more-btn:hover {
  background: linear-gradient(135deg, #eef2ff 0%, #f5f8ff 100%);
  border-color: #6366f1;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.15);
}

.load-more-btn:active {
  transform: translateY(0);
}

.load-more-btn i {
  font-size: 0.875rem;
  transition: transform 0.2s;
}

.load-more-btn:hover i {
  transform: translateY(2px);
}

.tasks-section__list :deep(.task-card) {
  width: 100%;
}

/* Inline list-view styles (aligned with dashboard list view) */
/* Removed legacy list-item styles now that TaskCard is used */

.no-tasks {
  text-align: center;
  padding: 2rem;
  color: #94a3b8;
}

.no-tasks i {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

/* Week View */
.calendar-week-view {
  flex: 1;
  padding: 1.5rem;
  overflow: auto;
}

.week-grid {
  display: flex;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  min-height: 600px;
  overflow: auto;
}

.time-column {
  width: 60px;
  border-right: 1px solid #e2e8f0;
  padding-top: 50px;
}

.time-slot {
  height: 60px;
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  color: #64748b;
  border-bottom: 1px solid #f1f5f9;
}

.day-column {
  flex: 1;
  min-width: 120px;
  border-right: 1px solid #e2e8f0;
}

.day-column:last-child {
  border-right: none;
}

.day-header {
  height: 50px;
  padding: 0.5rem;
  text-align: center;
  border-bottom: 2px solid #e2e8f0;
  background: #f8fafc;
}

.day-header.today {
  background: #eff6ff;
}

.day-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #64748b;
}

.day-date {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
}

.day-timeline {
  position: relative;
  height: calc(24 * 60px);
}

.hour-slot {
  height: 60px;
  border-bottom: 1px solid #f1f5f9;
}

.tasks-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 0 0.25rem;
}

.timeline-task-skeleton {
  position: absolute;
  left: 4px;
  right: 4px;
  border-radius: 4px;
  opacity: 0.7;
}

.timeline-task {
  position: absolute;
  left: 4px;
  right: 4px;
  background: #e0e7ff;
  border-left: 3px solid #6366f1;
  border-radius: 4px;
  padding: 0.25rem 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
  overflow: hidden;
  font-size: 0.75rem;
}

.timeline-task:hover {
  transform: translateX(2px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.timeline-task.priority-high,
.timeline-task.priority-urgent {
  background: #fee2e2;
  border-left-color: #ef4444;
}

.timeline-task.priority-medium {
  background: #fed7aa;
  border-left-color: #f59e0b;
}

.timeline-task.priority-low {
  background: #d1fae5;
  border-left-color: #10b981;
}

.timeline-task.status-completed {
  opacity: 0.6;
}

.task-time {
  font-weight: 600;
  color: #475569;
  margin-bottom: 0.125rem;
}

.task-title {
  color: #1e293b;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.task-tags {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  color: #64748b;
  margin-top: 0.125rem;
}

.all-day-tasks {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  padding: 0.5rem;
  margin-bottom: 0.5rem;
}

.all-day-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 0.5rem;
}

.all-day-task {
  background: white;
  border-left: 3px solid #6366f1;
  border-radius: 4px;
  padding: 0.375rem 0.5rem;
  margin-bottom: 0.25rem;
  cursor: pointer;
  font-size: 0.8125rem;
  transition: all 0.2s ease;
}

.all-day-task:hover {
  transform: translateX(2px);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.all-day-task.priority-high,
.all-day-task.priority-urgent {
  border-left-color: #ef4444;
}

.all-day-task.priority-medium {
  border-left-color: #f59e0b;
}

.all-day-task.priority-low {
  border-left-color: #10b981;
}

/* Responsive */
@media (max-width: 768px) {
  .calendar-controls {
    flex-direction: column;
  }

  .current-period {
    font-size: 1rem;
    min-width: 150px;
  }

  .calendar-grid {
    gap: 0;
  }

  .calendar-day {
    min-height: 80px;
    padding: 0.375rem;
  }

  .day-column {
    min-width: 100px;
  }

  .time-column {
    width: 50px;
  }

  .tasks-header {
    align-items: stretch;
  }

  .tasks-header__title {
    flex: 1 1 100%;
  }

  .new-task-button {
    width: 100%;
    margin-left: 0;
    justify-content: center;
  }
}
</style>
