<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/stores/task.store'
import InputText from 'primevue/inputtext'
import Chip from 'primevue/chip'

interface Props {
  searchQuery: string
  selectedView: string
}

interface Emits {
  (e: 'update:searchQuery', value: string): void
  (e: 'update:selectedView', value: string): void
  (e: 'select-view', viewId: string): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const { t } = useI18n()
const taskStore = useTaskStore()

const internalSearchQuery = computed({
  get: () => props.searchQuery,
  set: (value) => emit('update:searchQuery', value)
})

const activeCount = computed(() => taskStore.todayTasks.length + taskStore.upcomingTasks.length)

const views = computed(() => [
  { id: 'all', label: t('tasks.all_tasks'), icon: 'pi pi-list', count: activeCount.value, color: '#667eea' },
  { id: 'today', label: t('tasks.today_tasks'), icon: 'pi pi-calendar', count: taskStore.todayTasks.length, color: '#10b981' },
  { id: 'upcoming', label: t('tasks.upcoming_tasks'), icon: 'pi pi-clock', count: taskStore.upcomingTasks.length, color: '#f59e0b' },
  { id: 'overdue', label: t('tasks.overdue_tasks'), icon: 'pi pi-exclamation-circle', count: taskStore.overdueTotal || taskStore.statistics?.overdue || 0, color: '#ef4444' },
  { id: 'unscheduled', label: t('tasks.unscheduled_tasks'), icon: 'pi pi-inbox', count: taskStore.unscheduledTotal || 0, color: '#64748b' }
])

function handleSelectView(viewId: string) {
  emit('update:selectedView', viewId)
  emit('select-view', viewId)
}
</script>

<template>
  <div class="task-filters-sidebar">
    <!-- Search Box (hidden on mobile) -->
    <div class="sidebar-section search-section">
      <span class="p-input-icon-left w-full">
        <i class="pi pi-search" />
        <InputText 
          v-model="internalSearchQuery"
          :placeholder="t('tasks.search_placeholder')"
          class="w-full"
        />
      </span>
    </div>

    <!-- Views Navigation -->
    <nav class="sidebar-section views-section">
      <h3 class="sidebar-section-title">{{ t('tasks.filter_by') }}</h3>
      <div class="views-list">
        <button
          v-for="view in views"
          :key="view.id"
          :class="['view-item', { 'view-item-active': selectedView === view.id }]"
          @click="handleSelectView(view.id)"
        >
          <i :class="view.icon" :style="{ color: view.color }" />
          <span class="view-label">{{ view.label }}</span>
          <span v-if="view.count > 0" class="view-count">
            {{ view.count }}
          </span>
        </button>
      </div>
    </nav>

    <!-- Tags Section -->
    <div class="sidebar-section tags-section">
      <h3 class="sidebar-section-title">{{ t('tags.most_used') }}</h3>
      <div class="tags-list">
        <button
          v-for="tag in taskStore.mostUsedTags"
          :key="tag.id"
          class="tag-item"
        >
          <span class="tag-dot" :style="{ backgroundColor: tag.color }"></span>
          <span class="tag-name">{{ tag.name }}</span>
          <span class="tag-usage">{{ tag.usageCount }}</span>
        </button>
      </div>
    </div>

    <!-- Statistics Card -->
    <div v-if="taskStore.statistics" class="sidebar-section stats-section">
      <h3 class="sidebar-section-title">{{ t('tasks.total_tasks') }}</h3>
      <div class="stats-card">
        <div class="stat-item">
          <div class="stat-icon pending">
            <i class="pi pi-clock"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ taskStore.statistics.pending }}</div>
            <div class="stat-label">{{ t('tasks.pending_tasks') }}</div>
          </div>
        </div>
        <div class="stat-item">
          <div class="stat-icon progress">
            <i class="pi pi-play"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ taskStore.statistics.in_progress }}</div>
            <div class="stat-label">{{ t('tasks.in_progress_tasks') }}</div>
          </div>
        </div>
        <div class="stat-item">
          <div class="stat-icon completed">
            <i class="pi pi-check"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ taskStore.statistics.completed }}</div>
            <div class="stat-label">{{ t('tasks.completed_tasks_count') }}</div>
          </div>
        </div>
        <div class="stat-item">
          <div class="stat-icon overdue">
            <i class="pi pi-exclamation-circle"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ taskStore.statistics.overdue }}</div>
            <div class="stat-label">{{ t('tasks.overdue_tasks_count') }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.task-filters-sidebar {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.sidebar-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(226, 232, 240, 0.5);
}

.sidebar-section-title {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #a0aec0;
  margin: 0 0 1rem 0;
}

/* Search Section */
.search-section {
  padding: 1rem;
}

@media (max-width: 1024px) {
  .search-section {
    display: none;
  }
}

.search-section :deep(.p-inputtext) {
  width: 100%;
  padding: 0.875rem 1rem 0.875rem 2.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9375rem;
  transition: all 0.2s ease;
  background: #f8fafc;
}

.search-section :deep(.p-inputtext:hover) {
  border-color: #cbd5e0;
  background: white;
}

.search-section :deep(.p-inputtext:focus) {
  border-color: #667eea !important;
  background: white;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
  outline: none !important;
}

.search-section :deep(.p-inputtext) {
  outline: none !important;
}

.search-section :deep(.p-inputtext:focus-visible) {
  outline: none !important;
}

.search-section :deep(.p-input-icon-left) {
  position: relative;
}

.search-section :deep(.p-input-icon-left > i) {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
  font-size: 1.125rem;
  z-index: 1;
}

.search-section :deep(.p-input-icon-left > .p-inputtext) {
  padding-left: 2.75rem;
}

/* Views Section */
.views-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.view-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  border-radius: 8px;
  border: none;
  background: transparent;
  color: #4a5568;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: left;
  font-size: 0.9375rem;
  font-weight: 500;
  position: relative;
  overflow: hidden;
}
.view-item i {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.25rem;
  height: 1.25rem;
  line-height: 1; /* avoid baseline shifts while icon font loads */
  font-size: 1.125rem;
}


.view-item::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  opacity: 0;
  transition: opacity 0.2s ease;
  border-radius: 8px;
}

.view-item:hover {
  background: #f7fafc;
  color: #2d3748;
}

.view-item-active {
  color: white;
  font-weight: 600;
}

.view-item-active::before {
  opacity: 1;
}

.view-item i,
.view-item .view-label,
.view-item .view-count {
  position: relative;
  z-index: 1;
}

.view-label {
  flex: 1;
}

.view-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  min-width: 1.5rem;
  height: 1.5rem;
  border-radius: 50%;
  padding: 0 0.25rem;
  transition: all 0.2s ease;
  background-color: #eef2ff;
  color: #4338ca;
}

.view-item-active .view-count {
  background: rgba(255, 255, 255, 0.25);
  color: white;
}

/* Tags Section */
.tags-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.tag-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  border: none;
  background: transparent;
  color: #4a5568;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: left;
  font-size: 0.875rem;
}

.tag-item:hover {
  background: #f7fafc;
  transform: translateX(4px);
}

.tag-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.tag-name {
  flex: 1;
  font-weight: 500;
}

.tag-usage {
  font-size: 0.75rem;
  color: #a0aec0;
  font-weight: 600;
}

/* Statistics Section */
.stats-card {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.125rem;
  flex-shrink: 0;
}

.stat-icon.pending {
  background: rgba(102, 126, 234, 0.1);
  color: #667eea;
}

.stat-icon.progress {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.stat-icon.completed {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.stat-icon.overdue {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  line-height: 1;
  margin-bottom: 0.25rem;
}

.stat-label {
  font-size: 0.75rem;
  color: #718096;
  font-weight: 500;
}
</style>
