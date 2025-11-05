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
  TASKS_OVERDUE: '/api/tasks/overdue',
  TASKS_UNSCHEDULED: '/api/tasks/unscheduled',
  TASK_BY_ID: (id: number) => `/api/tasks/${id}`,
  TASK_COMPLETE: (id: number) => `/api/tasks/${id}/complete`,
  TASK_TOGGLE: (id: number) => `/api/tasks/${id}/toggle`,
  TASK_ARCHIVE: (id: number) => `/api/tasks/${id}/archive`,
  TASK_UNARCHIVE: (id: number) => `/api/tasks/${id}/unarchive`,
  TASK_STATISTICS: '/api/tasks/statistics',
  TASK_REORDER: '/api/tasks/reorder',
  CALENDAR_MONTH: '/api/tasks/calendar/month',
  CALENDAR_WEEK: '/api/tasks/calendar/week',
  CALENDAR_DAY: '/api/tasks/calendar/day'
}

class TaskService {
  private formatDateForApi(date: Date): string {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }

  /**
   * Get list of tasks with filters
   */
  async getTasks(filters?: TaskFilters, limit?: number, offset?: number): Promise<Task[]> {
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

    // New filter parameters
    if (filters?.tags && filters.tags.length > 0) {
      filters.tags.forEach(tagId => params.append('tags[]', String(tagId)))
    }
    if (filters?.completed !== undefined && filters.completed !== null) {
      params.append('completed', String(filters.completed))
    }
    if (filters?.dateFrom) {
      params.append('dateFrom', filters.dateFrom)
    }
    if (filters?.dateTo) {
      params.append('dateTo', filters.dateTo)
    }
    if (filters?.priorities && filters.priorities.length > 0) {
      filters.priorities.forEach(priority => params.append('priorities[]', priority))
    }
    if (filters?.statuses && filters.statuses.length > 0) {
      filters.statuses.forEach(status => params.append('statuses[]', status))
    }

    // Pagination parameters
    if (limit !== undefined) {
      params.append('limit', String(limit))
    }
    if (offset !== undefined) {
      params.append('offset', String(offset))
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
    // Add query parameter to include subtasks
    const url = `${API_ENDPOINTS.TASK_BY_ID(id)}?includeSubtasks=true`
    const { data } = await apiClient.get<Task>(url)
    console.log('API Response for task', id, ':', data)
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
   * Get tasks for calendar month view
   */
  async getTasksForMonth(year: number, month: number, includeCompleted = true): Promise<Task[]> {
    const params = new URLSearchParams({
      year: year.toString(),
      month: month.toString(),
      includeCompleted: includeCompleted.toString()
    })
    const { data } = await apiClient.get<Task[]>(`${API_ENDPOINTS.CALENDAR_MONTH}?${params}`)
    return data
  }

  /**
   * Get tasks for calendar week view
   */
  async getTasksForWeek(weekStart: Date, includeCompleted = true): Promise<Task[]> {
    const params = new URLSearchParams({
      weekStart: this.formatDateForApi(weekStart),
      includeCompleted: includeCompleted.toString()
    })
    const { data } = await apiClient.get<Task[]>(`${API_ENDPOINTS.CALENDAR_WEEK}?${params}`)
    return data
  }

  /**
   * Get tasks for specific day
   */
  async getTasksForDay(date: Date, includeCompleted = true): Promise<Task[]> {
    const params = new URLSearchParams({
      date: this.formatDateForApi(date),
      includeCompleted: includeCompleted.toString()
    })
    const { data } = await apiClient.get<Task[]>(`${API_ENDPOINTS.CALENDAR_DAY}?${params}`)
    return data
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

  async getOverdueTasksPaginated(page: number, limit: number, filters?: TaskFilters): Promise<{ tasks: Task[], total: number }> {
    const params = new URLSearchParams()
    params.append('page', String(page))
    params.append('limit', String(limit))

    // Add filter parameters
    if (filters?.tags && filters.tags.length > 0) {
      filters.tags.forEach(tagId => params.append('tags[]', String(tagId)))
    }
    if (filters?.completed !== undefined && filters.completed !== null) {
      params.append('completed', String(filters.completed))
    }
    if (filters?.dateFrom) {
      params.append('dateFrom', filters.dateFrom)
    }
    if (filters?.dateTo) {
      params.append('dateTo', filters.dateTo)
    }
    if (filters?.priorities && filters.priorities.length > 0) {
      filters.priorities.forEach(priority => params.append('priorities[]', priority))
    }
    if (filters?.statuses && filters.statuses.length > 0) {
      filters.statuses.forEach(status => params.append('statuses[]', status))
    }

    const queryString = params.toString()
    const url = queryString ? `${API_ENDPOINTS.TASKS_OVERDUE}?${queryString}` : API_ENDPOINTS.TASKS_OVERDUE

    const { data } = await apiClient.get<{ tasks: Task[], total: number }>(url)
    return data
  }

  async getUnscheduledTasksPaginated(page: number, limit: number, filters?: TaskFilters): Promise<{ tasks: Task[], total: number }> {
    const params = new URLSearchParams()
    params.append('page', String(page))
    params.append('limit', String(limit))

    // Add filter parameters
    if (filters?.tags && filters.tags.length > 0) {
      filters.tags.forEach(tagId => params.append('tags[]', String(tagId)))
    }
    if (filters?.completed !== undefined && filters.completed !== null) {
      params.append('completed', String(filters.completed))
    }
    if (filters?.dateFrom) {
      params.append('dateFrom', filters.dateFrom)
    }
    if (filters?.dateTo) {
      params.append('dateTo', filters.dateTo)
    }
    if (filters?.priorities && filters.priorities.length > 0) {
      filters.priorities.forEach(priority => params.append('priorities[]', priority))
    }
    if (filters?.statuses && filters.statuses.length > 0) {
      filters.statuses.forEach(status => params.append('statuses[]', status))
    }

    const queryString = params.toString()
    const url = queryString ? `${API_ENDPOINTS.TASKS_UNSCHEDULED}?${queryString}` : API_ENDPOINTS.TASKS_UNSCHEDULED

    const { data } = await apiClient.get<{ tasks: Task[], total: number }>(url)
    return data
  }
}

export const taskService = new TaskService()

