import { ref, watch, type Ref } from 'vue'
import { tagService } from '@/services/tag.service'
import type { Tag } from '@/types/task.types'
import { useToast } from './useToast'

/**
 * Composable for tag suggestions and autocomplete functionality
 * Used in task creation and editing forms
 */
export function useTagSuggestions() {
  const { showError } = useToast()

  // Popular tags (most used)
  const popularTags = ref<Tag[]>([])
  const isLoadingPopular = ref(false)
  const popularTagsError = ref<string | null>(null)

  // Search suggestions
  const searchSuggestions = ref<Tag[]>([])
  const isSearching = ref(false)
  const searchQuery = ref('')

  /**
   * Fetch most popular tags
   */
  async function fetchPopularTags(limit: number = 7): Promise<void> {
    isLoadingPopular.value = true
    popularTagsError.value = null

    try {
      popularTags.value = await tagService.getMostUsedTags(limit)
    } catch (error: any) {
      popularTagsError.value = error.message || 'Failed to load popular tags'
      console.error('Failed to fetch popular tags:', error)
    } finally {
      isLoadingPopular.value = false
    }
  }

  /**
   * Search tags by query
   */
  async function searchTags(query: string): Promise<void> {
    if (!query || query.trim().length === 0) {
      searchSuggestions.value = []
      return
    }

    isSearching.value = true
    searchQuery.value = query

    try {
      searchSuggestions.value = await tagService.searchTags(query)
    } catch (error: any) {
      console.error('Failed to search tags:', error)
      searchSuggestions.value = []
    } finally {
      isSearching.value = false
    }
  }

  /**
   * Add tag to the list of selected tags
   * Returns true if added, false if already exists
   */
  function addTagToList(tag: Tag, currentTags: Ref<string[]>): boolean {
    const tagName = tag.name.trim()
    
    // Check if tag already exists (case-insensitive)
    const exists = currentTags.value.some(t => t.toLowerCase() === tagName.toLowerCase())
    
    if (exists) {
      return false
    }

    currentTags.value.push(tagName)
    return true
  }

  /**
   * Clear search suggestions
   */
  function clearSearchSuggestions(): void {
    searchSuggestions.value = []
    searchQuery.value = ''
  }

  /**
   * Initialize: fetch popular tags
   */
  function initialize(limit?: number): void {
    fetchPopularTags(limit)
  }

  return {
    // Popular tags
    popularTags,
    isLoadingPopular,
    popularTagsError,
    fetchPopularTags,

    // Search
    searchSuggestions,
    isSearching,
    searchQuery,
    searchTags,
    clearSearchSuggestions,

    // Helpers
    addTagToList,
    initialize
  }
}

