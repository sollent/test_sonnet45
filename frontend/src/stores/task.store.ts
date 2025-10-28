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
  TaskFilters
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

  const overdueTasks = computed(() => 
    tasks.value.filter(t => t.isOverdue && !t.isCompleted && !t.isArchived)
  )

  const todayTasks = computed(() => {
    const today = new Date().toISOString().split('T')[0]
    return tasks.value.filter(t => {
      if (t.isArchived || t.isCompleted) return false
      const taskDate = t.dueDate?.split('T')[0] || t.startDate?.split('T')[0]
      return taskDate === today
    })
  })

  const tasksByPriority = computed(() => {
    const grouped: Record<string, Task[]> = {
      urgent: [],
      high: [],
      medium: [],
      low: []
    }
    
    tasks.value.forEach(task => {
      if (!task.isArchived && task.priority) {
        const priorityKey = task.priority.toLowerCase()
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
  async function fetchTasks(filters?: TaskFilters): Promise<void> {
    isLoading.value = true
    error.value = null
    currentFilter.value = filters || {}

    try {
      tasks.value = await taskService.getTasks(filters)
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
      await fetchStatistics()
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
      
      await fetchStatistics()
      return updatedTask
    } catch (err: any) {
      error.value = err.message || 'Failed to update task'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function deleteTask(id: number): Promise<void> {
    isLoading.value = true
    error.value = null

    try {
      await taskService.deleteTask(id)
      tasks.value = tasks.value.filter(t => t.id !== id)
      
      if (selectedTask.value?.id === id) {
        selectedTask.value = null
      }
      
      await fetchStatistics()
    } catch (err: any) {
      error.value = err.message || 'Failed to delete task'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function toggleTaskCompletion(id: number): Promise<void> {
    try {
      const updatedTask = await taskService.toggleTask(id)
      
      const index = tasks.value.findIndex(t => t.id === id)
      if (index !== -1) {
        tasks.value[index] = updatedTask
      }
      
      if (selectedTask.value?.id === id) {
        selectedTask.value = updatedTask
      }
      
      await fetchStatistics()
    } catch (err: any) {
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

  function resetStore(): void {
    tasks.value = []
    tags.value = []
    selectedTask.value = null
    statistics.value = null
    isLoading.value = false
    error.value = null
    currentFilter.value = {}
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
    
    // Getters
    pendingTasks,
    inProgressTasks,
    completedTasks,
    overdueTasks,
    todayTasks,
    tasksByPriority,
    mostUsedTags,
    
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
    resetStore
  }
})

