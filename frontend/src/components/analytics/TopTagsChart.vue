<template>
  <div class="chart-container">
    <div class="chart-header">
      <h3 class="chart-title">{{ t('analytics.top_tags') }}</h3>
    </div>
    <div class="chart-body">
      <div v-if="tags.length === 0" class="no-data">
        <i class="pi pi-tag"></i>
        <p>{{ t('analytics.no_tags_yet') }}</p>
      </div>
      <div v-else class="tags-list">
        <div v-for="(tag, index) in tags" :key="tag.id" class="tag-item">
          <div class="tag-rank">{{ index + 1 }}</div>
          <div class="tag-info">
            <div class="tag-name-row">
              <span class="tag-avatar" :style="{ background: tag.color }">
                {{ tag.name.charAt(0).toUpperCase() }}
              </span>
              <span class="tag-name">{{ tag.name }}</span>
            </div>
            <div class="tag-stats">
              <span class="tag-count">{{ tag.count }} {{ t('analytics.tasks_count') }}</span>
              <span class="tag-completion">{{ tag.completionRate }}% {{ t('analytics.completed') }}</span>
            </div>
          </div>
          <div class="tag-progress">
            <div class="progress-bar">
              <div 
                class="progress-fill" 
                :style="{ 
                  width: `${tag.completionRate}%`,
                  background: tag.completionRate >= 80 ? '#10b981' : tag.completionRate >= 60 ? '#3b82f6' : '#f59e0b'
                }"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps<{
  tags: Array<{
    id: number
    name: string
    color: string
    count: number
    completionRate: number
    totalTasks: number
    completedTasks: number
  }>
}>()
</script>

<style scoped>
.chart-container {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(226, 232, 240, 0.5);
}

.chart-header {
  margin-bottom: 1.5rem;
}

.chart-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.no-data {
  text-align: center;
  padding: 3rem 1rem;
  color: #94a3b8;
}

.no-data i {
  font-size: 3rem;
  margin-bottom: 0.75rem;
  opacity: 0.5;
}

.no-data p {
  margin: 0;
  font-size: 0.9375rem;
}

.tags-list {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.tag-item {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 1rem;
  align-items: center;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 10px;
  transition: all 0.2s;
}

.tag-item:hover {
  background: #f1f5f9;
  transform: translateX(4px);
}

.tag-rank {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
  font-weight: 700;
  font-size: 0.875rem;
  border-radius: 8px;
  flex-shrink: 0;
}

.tag-info {
  flex: 1;
  min-width: 0;
}

.tag-name-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.tag-avatar {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 0.75rem;
  flex-shrink: 0;
}

.tag-name {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.9375rem;
}

.tag-stats {
  display: flex;
  gap: 1rem;
  font-size: 0.8125rem;
  color: #64748b;
}

.tag-progress {
  width: 120px;
  flex-shrink: 0;
}

.progress-bar {
  height: 8px;
  background: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  transition: width 0.6s ease;
  border-radius: 4px;
}

@media (max-width: 768px) {
  .tag-item {
    grid-template-columns: auto 1fr;
    gap: 0.75rem;
  }
  
  .tag-progress {
    grid-column: 2 / 3;
    width: 100%;
  }
}
</style>


