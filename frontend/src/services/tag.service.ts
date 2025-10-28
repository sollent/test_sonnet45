/**
 * Tag Service - handles all tag-related API calls
 */

import { apiClient } from './api.service'
import type { Tag, CreateTagRequest, UpdateTagRequest } from '@/types/task.types'

const API_ENDPOINTS = {
  TAGS: '/api/tags',
  TAG_BY_ID: (id: number) => `/api/tags/${id}`,
  TAGS_MOST_USED: '/api/tags/most-used'
}

class TagService {
  /**
   * Get list of tags
   */
  async getTags(search?: string, limit?: number): Promise<Tag[]> {
    const params = new URLSearchParams()
    
    if (search) {
      params.append('search', search)
    }
    if (limit) {
      params.append('limit', String(limit))
    }

    const queryString = params.toString()
    const url = queryString ? `${API_ENDPOINTS.TAGS}?${queryString}` : API_ENDPOINTS.TAGS

    const { data } = await apiClient.get<Tag[]>(url)
    return data
  }

  /**
   * Get most used tags
   */
  async getMostUsedTags(limit: number = 5): Promise<Tag[]> {
    const { data } = await apiClient.get<Tag[]>(
      `${API_ENDPOINTS.TAGS_MOST_USED}?limit=${limit}`
    )
    return data
  }

  /**
   * Get single tag by ID
   */
  async getTag(id: number): Promise<Tag> {
    const { data } = await apiClient.get<Tag>(API_ENDPOINTS.TAG_BY_ID(id))
    return data
  }

  /**
   * Create new tag
   */
  async createTag(tagData: CreateTagRequest): Promise<Tag> {
    const { data } = await apiClient.post<Tag>(API_ENDPOINTS.TAGS, tagData)
    return data
  }

  /**
   * Update existing tag
   */
  async updateTag(id: number, tagData: UpdateTagRequest): Promise<Tag> {
    const { data } = await apiClient.put<Tag>(API_ENDPOINTS.TAG_BY_ID(id), tagData)
    return data
  }

  /**
   * Delete tag
   */
  async deleteTag(id: number): Promise<void> {
    await apiClient.delete(API_ENDPOINTS.TAG_BY_ID(id))
  }

  /**
   * Search tags
   */
  async searchTags(query: string): Promise<Tag[]> {
    return this.getTags(query)
  }
}

export const tagService = new TagService()

