/**
 * Task Service - handles all task-related API calls
 */

import { apiClient } from './api.service'
import type {
  Task,
  CreateTaskRequest,
  UpdateTaskRequest,
  TaskStatistics,
  TaskFilters
} from '@/types/task.types'

const API_ENDPOINTS = {
  TASKS: '/api/tasks',
  TASK_BY_ID: (id: number) => `/api/tasks/${id}`,
  TASK_COMPLETE: (id: number) => `/api/tasks/${id}/complete`,
  TASK_TOGGLE: (id: number) => `/api/tasks/${id}/toggle`,
  TASK_ARCHIVE: (id: number) => `/api/tasks/${id}/archive`,
  TASK_UNARCHIVE: (id: number) => `/api/tasks/${id}/unarchive`,
  TASK_STATISTICS: '/api/tasks/statistics',
  TASK_REORDER: '/api/tasks/reorder'
}

class TaskService {
  /**
   * Get list of tasks with filters
   */
  async getTasks(filters?: TaskFilters): Promise<Task[]> {
    const params = new URLSearchParams()
    
    if (filters?.status) {
      params.append('status', filters.status)
    }
    if (filters?.archived !== undefined) {
      params.append('archived', String(filters.archived))
    }
    if (filters?.tag) {
      params.append('tag', String(filters.tag))
    }
    if (filters?.search) {
      params.append('search', filters.search)
    }
    if (filters?.view) {
      params.append('view', filters.view)
    }

    const queryString = params.toString()
    const url = queryString ? `${API_ENDPOINTS.TASKS}?${queryString}` : API_ENDPOINTS.TASKS

    const { data } = await apiClient.get<Task[]>(url)
    return data
  }

  /**
   * Get single task by ID
   */
  async getTask(id: number): Promise<Task> {
    const { data } = await apiClient.get<Task>(API_ENDPOINTS.TASK_BY_ID(id))
    return data
  }

  /**
   * Create new task
   */
  async createTask(taskData: CreateTaskRequest): Promise<Task> {
    const { data } = await apiClient.post<Task>(API_ENDPOINTS.TASKS, taskData)
    return data
  }

  /**
   * Update existing task
   */
  async updateTask(id: number, taskData: UpdateTaskRequest): Promise<Task> {
    const { data } = await apiClient.put<Task>(API_ENDPOINTS.TASK_BY_ID(id), taskData)
    return data
  }

  /**
   * Delete task
   */
  async deleteTask(id: number): Promise<void> {
    await apiClient.delete(API_ENDPOINTS.TASK_BY_ID(id))
  }

  /**
   * Mark task as completed
   */
  async completeTask(id: number): Promise<Task> {
    const { data } = await apiClient.post<Task>(API_ENDPOINTS.TASK_COMPLETE(id))
    return data
  }

  /**
   * Toggle task completion status
   */
  async toggleTask(id: number): Promise<Task> {
    const { data } = await apiClient.post<Task>(API_ENDPOINTS.TASK_TOGGLE(id))
    return data
  }

  /**
   * Archive task
   */
  async archiveTask(id: number): Promise<Task> {
    const { data } = await apiClient.post<Task>(API_ENDPOINTS.TASK_ARCHIVE(id))
    return data
  }

  /**
   * Unarchive task
   */
  async unarchiveTask(id: number): Promise<Task> {
    const { data } = await apiClient.post<Task>(API_ENDPOINTS.TASK_UNARCHIVE(id))
    return data
  }

  /**
   * Get task statistics
   */
  async getStatistics(): Promise<TaskStatistics> {
    const { data } = await apiClient.get<TaskStatistics>(API_ENDPOINTS.TASK_STATISTICS)
    return data
  }

  /**
   * Reorder tasks
   */
  async reorderTasks(taskIds: number[]): Promise<void> {
    await apiClient.post(API_ENDPOINTS.TASK_REORDER, { taskIds })
  }

  /**
   * Get today's tasks
   */
  async getTodayTasks(): Promise<Task[]> {
    return this.getTasks({ view: 'today' })
  }

  /**
   * Get overdue tasks
   */
  async getOverdueTasks(): Promise<Task[]> {
    return this.getTasks({ view: 'overdue' })
  }

  /**
   * Get upcoming tasks
   */
  async getUpcomingTasks(): Promise<Task[]> {
    return this.getTasks({ view: 'upcoming' })
  }

  /**
   * Search tasks
   */
  async searchTasks(query: string): Promise<Task[]> {
    return this.getTasks({ search: query })
  }

  /**
   * Get tasks by tag
   */
  async getTasksByTag(tagId: number): Promise<Task[]> {
    return this.getTasks({ tag: tagId })
  }
}

export const taskService = new TaskService()

