/**
 * Task Store - Pinia store for task management
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { taskService } from '@/services/task.service'
import { tagService } from '@/services/tag.service'
import type {
  Task,
  Tag,
  CreateTaskRequest,
  UpdateTaskRequest,
  TaskStatistics,
  TaskFilters,
  TaskFiltersState,
  TagUsage
} from '@/types/task.types'

export const useTaskStore = defineStore('task', () => {
  // State
  const tasks = ref<Task[]>([])
  const tags = ref<Tag[]>([])
  const selectedTask = ref<Task | null>(null)
  const statistics = ref<TaskStatistics | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const currentFilter = ref<TaskFilters>({})

  const overdueTasksPaginated = ref<{ tasks: Task[], total: number }>({ tasks: [], total: 0 })
  const isOverdueLoading = ref(false)
  const unscheduledTasksPaginated = ref<{ tasks: Task[], total: number }>({ tasks: [], total: 0 })
  const isUnscheduledLoading = ref(false)
  
  // Filters state
  const activeFilters = ref<TaskFiltersState>({
    tags: [],
    completed: null,
    dateFrom: null,
    dateTo: null,
    priorities: [],
    statuses: []
  })
  
  // Search state
  const searchQuery = ref<string>('')
  const currentView = ref<string>('all')

  // Getters
  const pendingTasks = computed(() => 
    tasks.value.filter(t => t.status === 'pending' && !t.isArchived)
  )

  const inProgressTasks = computed(() => 
    tasks.value.filter(t => t.status === 'in_progress' && !t.isArchived)
  )

  const completedTasks = computed(() => 
    tasks.value.filter(t => t.status === 'completed' && !t.isArchived)
  )

  const todayTasks = computed(() => {
    const todayStart = new Date()
    todayStart.setHours(0, 0, 0, 0)
    const todayEnd = new Date(todayStart)
    todayEnd.setHours(23, 59, 59, 999)

    return tasks.value.filter(task => {
      const dueDate = task.dueDate ? new Date(task.dueDate) : null
      const startDate = task.startDate ? new Date(task.startDate) : null

      if (dueDate) {
        dueDate.setHours(0, 0, 0, 0)
        if (dueDate.getTime() === todayStart.getTime()) {
          return true
        }
      }

      if (!dueDate && startDate) {
        startDate.setHours(0, 0, 0, 0)
        if (startDate.getTime() === todayStart.getTime()) {
          return true
        }
      }

      return false
    })
  })

  const overdueTasks = computed(() => overdueTasksPaginated.value.tasks)
  
  const upcomingTasks = computed(() => {
    const todayEnd = new Date()
    todayEnd.setHours(23, 59, 59, 999)

    return tasks.value.filter(task => {
      const dueDate = task.dueDate ? new Date(task.dueDate) : null
      const startDate = task.startDate ? new Date(task.startDate) : null

      if (dueDate) {
        return dueDate.getTime() > todayEnd.getTime()
      }

      if (startDate) {
        return startDate.getTime() > todayEnd.getTime()
      }

      // Tasks without specific dates are treated as upcoming backlog
      return true
    })
  })

  const overdueTotal = computed(() => overdueTasksPaginated.value.total)
  const unscheduledTasks = computed(() => unscheduledTasksPaginated.value.tasks)
  const unscheduledTotal = computed(() => unscheduledTasksPaginated.value.total)

  const tasksByPriority = computed(() => {
    const grouped: Record<string, Task[]> = {
      urgent: [],
      high: [],
      medium: [],
      low: []
    }
    
    tasks.value.forEach(task => {
      if (!task.isArchived && task.priority) {
        const priorityKey = typeof task.priority === 'string' 
          ? task.priority.toLowerCase() 
          : task.priority.value.toLowerCase()
        if (grouped[priorityKey]) {
          grouped[priorityKey].push(task)
        }
      }
    })
    
    return grouped
  })

  const mostUsedTags = computed(() => 
    [...tags.value].sort((a, b) => b.usageCount - a.usageCount).slice(0, 5)
  )

  // Actions
  async function fetchTasks(filters?: TaskFilters, append: boolean = false, limit?: number, offset?: number): Promise<number> {
    isLoading.value = true
    error.value = null
    currentFilter.value = filters || {}

    try {
      const newTasks = await taskService.getTasks(filters, limit, offset)

      let addedCount = newTasks.length

      if (append) {
        // Append new tasks, avoiding duplicates
        const existingIds = new Set(tasks.value.map(t => t.id))
        const uniqueNewTasks = newTasks.filter(t => !existingIds.has(t.id))
        tasks.value = [...tasks.value, ...uniqueNewTasks]
        addedCount = uniqueNewTasks.length
      } else {
        // Replace tasks
        tasks.value = newTasks
      }

      return addedCount
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch tasks'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function fetchTask(id: number): Promise<Task> {
    isLoading.value = true
    error.value = null

    try {
      const task = await taskService.getTask(id)
      console.log('Fetched task from API:', task)
      console.log('Task has subtasks:', task.subtasks?.length || 0)
      if (task.subtasks && task.subtasks.length > 0) {
        console.log('First subtask:', task.subtasks[0])
      }
      selectedTask.value = task
      
      // Update task in list if it exists
      const index = tasks.value.findIndex(t => t.id === id)
      if (index !== -1) {
        tasks.value[index] = task
      }
      
      return task
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch task'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function createTask(taskData: CreateTaskRequest): Promise<Task> {
    isLoading.value = true
    error.value = null

    try {
      const newTask = await taskService.createTask(taskData)
      tasks.value.unshift(newTask)
      return newTask
    } catch (err: any) {
      error.value = err.message || 'Failed to create task'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function updateTask(id: number, taskData: UpdateTaskRequest): Promise<Task> {
    isLoading.value = true
    error.value = null

    try {
      const updatedTask = await taskService.updateTask(id, taskData)

      const index = tasks.value.findIndex(t => t.id === id)
      if (index !== -1) {
        tasks.value[index] = updatedTask
      }

      if (selectedTask.value?.id === id) {
        selectedTask.value = updatedTask
      }

      return updatedTask
    } catch (err: any) {
      error.value = err.message || 'Failed to update task'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function deleteTask(id: number): Promise<void> {
    // Optimistic update - remove from UI immediately
    const taskIndex = tasks.value.findIndex(t => t.id === id)
    const overdueTaskIndex = overdueTasksPaginated.value.tasks.findIndex(t => t.id === id)
    const unscheduledTaskIndex = unscheduledTasksPaginated.value.tasks.findIndex(t => t.id === id)
    
    // Store original state for rollback
    const originalTasks = [...tasks.value]
    const originalOverdueTasks = [...overdueTasksPaginated.value.tasks]
    const originalUnscheduledTasks = [...unscheduledTasksPaginated.value.tasks]
    const originalSelectedTask = selectedTask.value

    // Optimistic update - remove from all locations immediately
    if (taskIndex !== -1) {
      tasks.value = tasks.value.filter(t => t.id !== id)
    }
    
    if (overdueTaskIndex !== -1) {
      overdueTasksPaginated.value.tasks = overdueTasksPaginated.value.tasks.filter(t => t.id !== id)
      overdueTasksPaginated.value.total = Math.max(0, overdueTasksPaginated.value.total - 1)
    }
    
    if (unscheduledTaskIndex !== -1) {
      unscheduledTasksPaginated.value.tasks = unscheduledTasksPaginated.value.tasks.filter(t => t.id !== id)
      unscheduledTasksPaginated.value.total = Math.max(0, unscheduledTasksPaginated.value.total - 1)
    }
    
    if (selectedTask.value?.id === id) {
      selectedTask.value = null
    }

    try {
      // Make API call in background
      await taskService.deleteTask(id)
    } catch (err: any) {
      // Rollback on error
      tasks.value = originalTasks
      overdueTasksPaginated.value.tasks = originalOverdueTasks
      overdueTasksPaginated.value.total = originalOverdueTasks.length
      unscheduledTasksPaginated.value.tasks = originalUnscheduledTasks
      unscheduledTasksPaginated.value.total = originalUnscheduledTasks.length
      selectedTask.value = originalSelectedTask

      error.value = err.message || 'Failed to delete task'
      throw err
    }
  }

  async function toggleTaskCompletion(id: number): Promise<void> {
    // Find task in all possible locations
    const taskIndex = tasks.value.findIndex(t => t.id === id)
    const overdueTaskIndex = overdueTasksPaginated.value.tasks.findIndex(t => t.id === id)
    const unscheduledTaskIndex = unscheduledTasksPaginated.value.tasks.findIndex(t => t.id === id)
    
    // Check if task exists in store (for optimistic updates)
    const taskExistsInStore = taskIndex !== -1 || overdueTaskIndex !== -1 || unscheduledTaskIndex !== -1
    
    // Get original task from any location if it exists in store
    let originalTask: Task | null = null
    if (taskIndex !== -1) {
      originalTask = { ...tasks.value[taskIndex] }
    } else if (overdueTaskIndex !== -1) {
      originalTask = { ...overdueTasksPaginated.value.tasks[overdueTaskIndex] }
    } else if (unscheduledTaskIndex !== -1) {
      originalTask = { ...unscheduledTasksPaginated.value.tasks[unscheduledTaskIndex] }
    }

    // If task exists in store, do optimistic update
    if (taskExistsInStore && originalTask) {
      const newStatus = !originalTask.isCompleted ? 'completed' : 'pending'
      const optimisticTask = {
        ...originalTask,
        isCompleted: !originalTask.isCompleted,
        status: typeof originalTask.status === 'object' 
          ? { ...originalTask.status, value: newStatus }
          : newStatus
      } as Task

      // Optimistic update - update UI immediately in all locations
      if (taskIndex !== -1) {
        tasks.value[taskIndex] = optimisticTask
      }
      
      if (overdueTaskIndex !== -1) {
        overdueTasksPaginated.value.tasks[overdueTaskIndex] = optimisticTask
      }
      
      if (unscheduledTaskIndex !== -1) {
        unscheduledTasksPaginated.value.tasks[unscheduledTaskIndex] = optimisticTask
      }
      
      if (selectedTask.value?.id === id) {
        selectedTask.value = optimisticTask
      }
    }

    try {
      // Always make API call, even if task is not in store (e.g., calendar tasks)
      const updatedTask = await taskService.toggleTask(id)
      
      // Ensure isCompleted is in sync with status
      const statusValue = typeof updatedTask.status === 'string' 
        ? updatedTask.status 
        : updatedTask.status.value
      updatedTask.isCompleted = statusValue === 'completed'
      
      // Update with real data from server in all locations (if task exists in store)
      const currentTaskIndex = tasks.value.findIndex(t => t.id === id)
      if (currentTaskIndex !== -1) {
        tasks.value[currentTaskIndex] = updatedTask
      }
      
      const currentOverdueIndex = overdueTasksPaginated.value.tasks.findIndex(t => t.id === id)
      if (currentOverdueIndex !== -1) {
        overdueTasksPaginated.value.tasks[currentOverdueIndex] = updatedTask
      }
      
      const currentUnscheduledIndex = unscheduledTasksPaginated.value.tasks.findIndex(t => t.id === id)
      if (currentUnscheduledIndex !== -1) {
        unscheduledTasksPaginated.value.tasks[currentUnscheduledIndex] = updatedTask
      }
      
      if (selectedTask.value?.id === id) {
        selectedTask.value = updatedTask
      }
    } catch (err: any) {
      // Rollback on error only if task was in store
      if (taskExistsInStore && originalTask) {
        if (taskIndex !== -1) {
          tasks.value[taskIndex] = originalTask
        }
        
        if (overdueTaskIndex !== -1) {
          overdueTasksPaginated.value.tasks[overdueTaskIndex] = originalTask
        }
        
        if (unscheduledTaskIndex !== -1) {
          unscheduledTasksPaginated.value.tasks[unscheduledTaskIndex] = originalTask
        }
        
        if (selectedTask.value?.id === id) {
          selectedTask.value = originalTask
        }
      }
      
      error.value = err.message || 'Failed to toggle task'
      throw err
    }
  }

  async function archiveTask(id: number): Promise<void> {
    try {
      const updatedTask = await taskService.archiveTask(id)
      
      const index = tasks.value.findIndex(t => t.id === id)
      if (index !== -1) {
        tasks.value[index] = updatedTask
      }
      
      if (selectedTask.value?.id === id) {
        selectedTask.value = updatedTask
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to archive task'
      throw err
    }
  }

  async function unarchiveTask(id: number): Promise<void> {
    try {
      const updatedTask = await taskService.unarchiveTask(id)
      
      const index = tasks.value.findIndex(t => t.id === id)
      if (index !== -1) {
        tasks.value[index] = updatedTask
      }
      
      if (selectedTask.value?.id === id) {
        selectedTask.value = updatedTask
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to unarchive task'
      throw err
    }
  }

  async function fetchOverdueTasksPaginated(page: number, limit: number, filters?: TaskFiltersState): Promise<void> {
    isOverdueLoading.value = true
    error.value = null
    try {
      // Use provided filters or active filters
      const filtersToUse = filters || activeFilters.value
      const queryFilters = buildQueryFilters(filtersToUse)
      overdueTasksPaginated.value = await taskService.getOverdueTasksPaginated(page, limit, queryFilters)
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch overdue tasks'
    } finally {
      isOverdueLoading.value = false
    }
  }

  async function fetchUnscheduledTasksPaginated(page: number, limit: number, filters?: TaskFiltersState): Promise<void> {
    isUnscheduledLoading.value = true
    error.value = null
    try {
      // Use provided filters or active filters
      const filtersToUse = filters || activeFilters.value
      const queryFilters = buildQueryFilters(filtersToUse)
      unscheduledTasksPaginated.value = await taskService.getUnscheduledTasksPaginated(page, limit, queryFilters)
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch unscheduled tasks'
    } finally {
      isUnscheduledLoading.value = false
    }
  }
  
  // Helper to build query filters from state
  function buildQueryFilters(filters: TaskFiltersState): TaskFilters {
    const queryFilters: TaskFilters = {}

    if (filters.tags && filters.tags.length > 0) {
      queryFilters.tags = filters.tags
    }

    if (filters.completed !== null && filters.completed !== undefined) {
      queryFilters.completed = filters.completed
    }

    if (filters.dateFrom) {
      queryFilters.dateFrom = filters.dateFrom
    }

    if (filters.dateTo) {
      queryFilters.dateTo = filters.dateTo
    }

    if (filters.priorities && filters.priorities.length > 0) {
      queryFilters.priorities = filters.priorities
    }

    if (filters.statuses && filters.statuses.length > 0) {
      queryFilters.statuses = filters.statuses
    }

    return queryFilters
  }

  async function fetchStatistics(): Promise<void> {
    try {
      statistics.value = await taskService.getStatistics()
    } catch (err: any) {
      console.error('Failed to fetch statistics:', err)
    }
  }

  async function fetchTags(): Promise<void> {
    try {
      tags.value = await tagService.getTags()
    } catch (err: any) {
      console.error('Failed to fetch tags:', err)
    }
  }

  async function createTag(name: string, color?: string): Promise<Tag> {
    try {
      const newTag = await tagService.createTag({ name, color })
      tags.value.push(newTag)
      return newTag
    } catch (err: any) {
      error.value = err.message || 'Failed to create tag'
      throw err
    }
  }

  async function deleteTag(id: number): Promise<void> {
    try {
      await tagService.deleteTag(id)
      tags.value = tags.value.filter(t => t.id !== id)
      
      // Remove tag from all tasks
      tasks.value.forEach(task => {
        task.tags = task.tags.filter(t => t.id !== id)
      })
    } catch (err: any) {
      error.value = err.message || 'Failed to delete tag'
      throw err
    }
  }

  function selectTask(task: Task | null): void {
    selectedTask.value = task
  }

  function clearError(): void {
    error.value = null
  }

  // Filter actions
  function setFilters(filters: TaskFiltersState): void {
    activeFilters.value = { ...filters }

    // Build query params for API using the buildQueryFilters helper
    const queryFilters: TaskFilters = {
      ...currentFilter.value,
      ...buildQueryFilters(filters)
    }

    // Refetch tasks with new filters
    fetchTasks(queryFilters)
  }
  
  function clearFilters(): void {
    activeFilters.value = {
      tags: [],
      completed: null,
      dateFrom: null,
      dateTo: null,
      priorities: [],
      statuses: []
    }
    
    // Refetch tasks without filters
    const baseFilter: TaskFilters = {
      view: currentFilter.value.view || 'all'
    }
    fetchTasks(baseFilter)
  }
  
  function hasActiveFilters(): boolean {
    return (
      activeFilters.value.tags.length > 0 ||
      activeFilters.value.completed !== null ||
      activeFilters.value.dateFrom !== null ||
      activeFilters.value.dateTo !== null ||
      activeFilters.value.priorities.length > 0 ||
      activeFilters.value.statuses.length > 0 ||
      searchQuery.value !== ''
    )
  }
  
  // Set search query
  function setSearchQuery(query: string): void {
    searchQuery.value = query
    // Re-fetch tasks with search
    const queryFilters: TaskFilters = {
      ...currentFilter.value,
      search: query
    }
    fetchTasks(queryFilters)
  }
  
  // Clear search
  function clearSearch(): void {
    searchQuery.value = ''
    fetchTasks(currentFilter.value)
  }
  
  // Set current view
  function setCurrentView(view: string): void {
    currentView.value = view
    currentFilter.value = { view } as TaskFilters
    fetchTasks(currentFilter.value)
  }

  function resetStore(): void {
    tasks.value = []
    tags.value = []
    selectedTask.value = null
    statistics.value = null
    isLoading.value = false
    error.value = null
    currentFilter.value = {}
    activeFilters.value = {
      tags: [],
      completed: null,
      dateFrom: null,
      dateTo: null,
      priorities: [],
      statuses: []
    }
  }

  return {
    // State
    tasks,
    tags,
    selectedTask,
    statistics,
    isLoading,
    error,
    currentFilter,
    activeFilters,
    searchQuery,
    currentView,
    
    // Getters
    pendingTasks,
    inProgressTasks,
    completedTasks,
    overdueTasks,
    todayTasks,
    tasksByPriority,
    mostUsedTags,
    upcomingTasks,
    overdueTasksPaginated,
    overdueTotal,
    unscheduledTasks,
    unscheduledTasksPaginated,
    unscheduledTotal,
    isOverdueLoading,
    isUnscheduledLoading,
    
    // Actions
    fetchTasks,
    fetchTask,
    createTask,
    updateTask,
    deleteTask,
    toggleTaskCompletion,
    archiveTask,
    unarchiveTask,
    fetchStatistics,
    fetchTags,
    createTag,
    deleteTag,
    selectTask,
    clearError,
    resetStore,
    fetchOverdueTasksPaginated,
    fetchUnscheduledTasksPaginated,
    setFilters,
    clearFilters,
    hasActiveFilters,
    setSearchQuery,
    clearSearch,
    setCurrentView
  }
})

