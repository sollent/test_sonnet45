import { apiClient } from './api.service'
import type { Tag } from '@/types/task.types'

export interface TagSearchResponse {
  tags: Tag[]
}

class TagService {
  private readonly API_URL = '/api/tags'

  /**
   * Get most popular/used tags
   */
  async getMostUsedTags(limit: number = 7): Promise<Tag[]> {
    const response = await apiClient.get<Tag[]>(`${this.API_URL}/most-used`, {
      params: { limit }
    })
    return response.data
  }

  /**
   * Search tags by query
   */
  async searchTags(query: string): Promise<Tag[]> {
    if (!query || query.trim().length === 0) {
      return []
    }
    
    const response = await apiClient.get<Tag[]>(`${this.API_URL}`, {
      params: { search: query.trim() }
    })
    return response.data
  }

  /**
   * Get all user tags
   */
  async getAllTags(limit?: number): Promise<Tag[]> {
    const response = await apiClient.get<Tag[]>(`${this.API_URL}`, {
      params: limit ? { limit } : {}
    })
    return response.data
  }

  /**
   * Create a new tag
   */
  async createTag(name: string, color?: string): Promise<Tag> {
    const response = await apiClient.post<Tag>(`${this.API_URL}`, {
      name,
      color: color || '#3B82F6'
    })
    return response.data
  }
}

export const tagService = new TagService()
