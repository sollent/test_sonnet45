import { apiClient } from './api.service'

export interface AnalyticsOverview {
  totalTasks: number
  completedThisWeek: number
  weeklyChange: number
  weeklyChangePercent: number
  averageCompletionTime: number
  currentStreak: number
  onTimeCompletionRate: number
  mostProductiveDay: string | null
  pending: number
  inProgress: number
  completed: number
  overdue: number
}

export interface TimelineData {
  dates: string[]
  created: number[]
  completed: number[]
  overdue: number[]
}

export interface StatusDistribution {
  pending: number
  in_progress: number
  completed: number
  cancelled: number
  total: number
}

export interface PriorityStats {
  total: number
  completed: number
  inProgress: number
  pending: number
}

export interface PriorityBreakdown {
  low: PriorityStats
  medium: PriorityStats
  high: PriorityStats
  urgent: PriorityStats
}

export interface TopTag {
  id: number
  name: string
  color: string
  count: number
  completionRate: number
  totalTasks: number
  completedTasks: number
}

export interface Insight {
  type: string
  icon: string
  message: string
  sentiment: 'positive' | 'negative' | 'warning' | 'info'
}

export interface InsightsResponse {
  insights: Insight[]
}

class AnalyticsService {
  private baseURL = '/api/analytics'

  /**
   * Get overview statistics
   */
  async getOverview(): Promise<AnalyticsOverview> {
    const response = await apiClient.get<AnalyticsOverview>(`${this.baseURL}/overview`)
    return response.data
  }

  /**
   * Get completion timeline data
   */
  async getCompletionTimeline(period: number = 30): Promise<TimelineData> {
    const response = await apiClient.get<TimelineData>(
      `${this.baseURL}/completion-timeline?period=${period}`
    )
    return response.data
  }

  /**
   * Get completion timeline by custom date range
   */
  async getCompletionTimelineByDateRange(dateFrom: string, dateTo: string): Promise<TimelineData> {
    const response = await apiClient.get<TimelineData>(
      `${this.baseURL}/completion-timeline?dateFrom=${dateFrom}&dateTo=${dateTo}`
    )
    return response.data
  }

  /**
   * Get status distribution
   */
  async getStatusDistribution(): Promise<StatusDistribution> {
    const response = await apiClient.get<StatusDistribution>(`${this.baseURL}/status-distribution`)
    return response.data
  }

  /**
   * Get priority breakdown
   */
  async getPriorityBreakdown(): Promise<PriorityBreakdown> {
    const response = await apiClient.get<PriorityBreakdown>(`${this.baseURL}/priority-breakdown`)
    return response.data
  }

  /**
   * Get productivity heatmap
   */
  async getProductivityHeatmap(year?: number): Promise<Record<string, number>> {
    const currentYear = year || new Date().getFullYear()
    const response = await apiClient.get<Record<string, number>>(
      `${this.baseURL}/productivity-heatmap?year=${currentYear}`
    )
    return response.data
  }

  /**
   * Get weekday productivity
   */
  async getWeekdayProductivity(): Promise<Record<string, number>> {
    const response = await apiClient.get<Record<string, number>>(`${this.baseURL}/weekday-productivity`)
    return response.data
  }

  /**
   * Get top tags
   */
  async getTopTags(limit: number = 5): Promise<TopTag[]> {
    const response = await apiClient.get<TopTag[]>(`${this.baseURL}/top-tags?limit=${limit}`)
    return response.data
  }

  /**
   * Get insights and recommendations
   */
  async getInsights(): Promise<InsightsResponse> {
    const response = await apiClient.get<InsightsResponse>(`${this.baseURL}/insights`)
    return response.data
  }
}

export const analyticsService = new AnalyticsService()

