import { Page } from '@playwright/test'

const API_BASE_URL = 'http://localhost:8089'

interface TaskData {
  title: string
  description?: string
  due_date?: string
  priority?: 'low' | 'medium' | 'high' | 'urgent'
  status?: 'pending' | 'in_progress' | 'completed' | 'canceled'
  tags?: string[]
}

interface AuthTokens {
  token: string
  refreshToken: string
}

/**
 * Login via API and get JWT tokens
 */
export async function loginViaAPI(email: string, password: string): Promise<AuthTokens> {
  const response = await fetch(`${API_BASE_URL}/api/auth`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ email, password })
  })

  if (!response.ok) {
    throw new Error(`Login failed: ${response.status} ${response.statusText}`)
  }

  const data = await response.json()
  return {
    token: data.token,
    refreshToken: data.refreshToken
  }
}

/**
 * Create a task via API
 */
export async function createTaskViaAPI(taskData: TaskData, token: string): Promise<any> {
  const response = await fetch(`${API_BASE_URL}/api/tasks`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(taskData)
  })

  if (!response.ok) {
    throw new Error(`Create task failed: ${response.status} ${response.statusText}`)
  }

  return await response.json()
}

/**
 * Create multiple tasks via API
 */
export async function createTasksViaAPI(tasks: TaskData[], token: string): Promise<any[]> {
  const createdTasks = []
  for (const taskData of tasks) {
    const task = await createTaskViaAPI(taskData, token)
    createdTasks.push(task)
  }
  return createdTasks
}

/**
 * Delete a task via API
 */
export async function deleteTaskViaAPI(taskId: number, token: string): Promise<void> {
  const response = await fetch(`${API_BASE_URL}/api/tasks/${taskId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`
    }
  })

  if (!response.ok && response.status !== 404) {
    throw new Error(`Delete task failed: ${response.status} ${response.statusText}`)
  }
}

/**
 * Delete multiple tasks via API
 */
export async function deleteTasksViaAPI(taskIds: number[], token: string): Promise<void> {
  for (const taskId of taskIds) {
    await deleteTaskViaAPI(taskId, token)
  }
}

/**
 * Set auth tokens in browser storage (for tests that need to continue with UI)
 */
export async function setAuthTokensInStorage(page: Page, tokens: AuthTokens): Promise<void> {
  await page.evaluate((tokens) => {
    localStorage.setItem('access_token', tokens.token)
    localStorage.setItem('refresh_token', tokens.refreshToken)
  }, tokens)
}

/**
 * Helper to prepare test environment: login via API and set tokens in browser
 */
export async function prepareAuthenticatedSession(page: Page, email: string, password: string): Promise<AuthTokens> {
  // Login via API
  const tokens = await loginViaAPI(email, password)

  // Set tokens in browser storage
  await setAuthTokensInStorage(page, tokens)

  return tokens
}

/**
 * Create tasks for tests with proper date handling
 */
export function getTaskDate(dateType: 'today' | 'tomorrow' | 'yesterday'): string {
  const date = new Date()

  switch (dateType) {
    case 'today':
      // Return today's date
      break
    case 'tomorrow':
      date.setDate(date.getDate() + 1)
      break
    case 'yesterday':
      date.setDate(date.getDate() - 1)
      break
  }

  return date.toISOString().split('T')[0] // Format: YYYY-MM-DD
}
