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
        </div>
        <div class="header-actions">
          <p v-if="!isMobile" class="header-subtitle">{{ user?.email }}</p>
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
    <div v-if="viewMode === 'month'" class="calendar-month-view">
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
              v-for="(task, idx) in day.tasks.slice(0, 3)"
              :key="task.id"
              class="task-dot"
              :class="[
                `priority-${task.priority.toLowerCase()}`,
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
              v-for="task in activeTasks"
              :key="task.id"
              :task="task"
              @click="selectTask"
              @toggle-complete="handleToggleComplete"
            />
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
              v-for="task in completedTasks"
              :key="task.id"
              :task="task"
              @click="selectTask"
              @toggle-complete="handleToggleComplete"
            />
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
            <div class="tasks-overlay">
              <div
                v-for="task in getTasksWithPosition(day.tasks)"
                :key="task.id"
                class="timeline-task"
                :class="`priority-${task.priority.toLowerCase()} status-${task.status.toLowerCase()}`"
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
                  :class="`priority-${task.priority.toLowerCase()}`"
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
const route = useRoute()
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

// Computed properties for active and completed tasks
const activeTasks = computed(() => {
  if (!selectedDay.value) return []
  return selectedDay.value.tasks.filter((task: Task) => !task.isCompleted)
})

const completedTasks = computed(() => {
  if (!selectedDay.value) return []
  return selectedDay.value.tasks.filter((task: Task) => task.isCompleted)
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
const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  
  // Get first Monday
  const startDate = new Date(firstDay)
  const dayOfWeek = startDate.getDay()
  const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek
  startDate.setDate(startDate.getDate() + diff)
  
  const days = []
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  for (let i = 0; i < 42; i++) {
    const date = new Date(startDate)
    date.setDate(startDate.getDate() + i)
    date.setHours(0, 0, 0, 0)
    
    const dayTasks = monthTasks.value.filter(task => {
      // Check if task should appear on this day
      const taskStartDate = task.startDate ? new Date(task.startDate) : null
      const taskDueDate = task.dueDate ? new Date(task.dueDate) : null
      
      if (taskStartDate) {
        const normalizedStart = new Date(taskStartDate)
        normalizedStart.setHours(0, 0, 0, 0)
        if (normalizedStart.getTime() === date.getTime()) return true
      }
      
      if (taskDueDate) {
        const normalizedDue = new Date(taskDueDate)
        normalizedDue.setHours(0, 0, 0, 0)
        if (normalizedDue.getTime() === date.getTime()) return true
      }
      
      // Task spans across this day
      if (taskStartDate && taskDueDate) {
        const normalizedStart = new Date(taskStartDate)
        normalizedStart.setHours(0, 0, 0, 0)
        const normalizedDue = new Date(taskDueDate)
        normalizedDue.setHours(0, 0, 0, 0)
        
        if (normalizedStart.getTime() <= date.getTime() && normalizedDue.getTime() >= date.getTime()) {
          return true
        }
      }
      
      // For overdue tasks, show them on today if they're still incomplete
      if (task.isOverdue && !task.isCompleted && date.toDateString() === today.toDateString()) {
        return true
      }
      
      return false
    })
    
    // Check if all tasks are completed
    const hasOnlyCompletedTasks = dayTasks.length > 0 && dayTasks.every(task => task.isCompleted)
    const isCurrentMonthDay = date.getMonth() === month
    
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
function getTasksWithPosition(tasks: Task[]): any[] {
  return tasks.map(task => {
    const startDate = task.startDate ? new Date(task.startDate) : new Date(task.dueDate!)
    const endDate = task.dueDate ? new Date(task.dueDate) : startDate
    
    const startHour = startDate.getHours() + startDate.getMinutes() / 60
    const endHour = endDate.getHours() + endDate.getMinutes() / 60
    const duration = endHour - startHour
    
    return {
      ...task,
      startHour,
      duration: Math.max(duration, 0.5) // Minimum 30 minutes height
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

async function selectTask(task: Task) {
  try {
    // Load full task details including subtasks
    const fullTask = await taskStore.fetchTask(task.id)
    selectedTask.value = fullTask
    showTaskDetails.value = true
  } catch (error: any) {
    showError(error.message || t('errors.fetch_failed'))
  }
}

function openNewTaskDialog(date: Date) {
  newTaskDate.value = date
  editingTask.value = null
  parentTaskId.value = null
  showNewTaskDialog.value = true
}

async function handleToggleComplete(task: Task) {
  const selectedDate = selectedDay.value ? new Date(selectedDay.value.date) : null
  try {
    await fetchTasks()
    if (selectedDate) {
      await setSelectedDayByDate(selectedDate)
    }
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

  await fetchTasks()

  if (targetDate) {
    await setSelectedDayByDate(targetDate)
  }
}

async function handleTaskUpdated() {
  const selectedDate = selectedDay.value ? new Date(selectedDay.value.date) : null
  await fetchTasks()
  
  if (selectedDate) {
    await setSelectedDayByDate(selectedDate)
  }
  
  // Reload selected task if it's still open
  if (selectedTask.value && showTaskDetails.value) {
    try {
      const updatedTask = await taskStore.fetchTask(selectedTask.value.id)
      selectedTask.value = updatedTask
    } catch (error) {
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
  await fetchTasks()
  
  if (selectedDate) {
    await setSelectedDayByDate(selectedDate)
  }
}

// Fetch tasks
async function fetchTasks() {
  isLoading.value = true
  try {
    if (viewMode.value === 'month') {
      const year = currentDate.value.getFullYear()
      const month = currentDate.value.getMonth() + 1
      
      // Fetch tasks for current, previous, and next month to ensure proper display across month boundaries
      const [prevMonthTasks, currentMonthTasks, nextMonthTasks] = await Promise.all([
        taskService.getTasksForMonth(month === 1 ? year - 1 : year, month === 1 ? 12 : month - 1, true),
        taskService.getTasksForMonth(year, month, true),
        taskService.getTasksForMonth(month === 12 ? year + 1 : year, month === 12 ? 1 : month + 1, true)
      ])
      
      monthTasks.value = [...prevMonthTasks, ...currentMonthTasks, ...nextMonthTasks]
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

// Watchers
watch(viewMode, fetchTasks)
watch(currentDate, fetchTasks)

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
onMounted(() => {
  fetchTasks()
  navigateToday()
  window.addEventListener('resize', handleResize)
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
