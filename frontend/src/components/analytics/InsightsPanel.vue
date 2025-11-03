<template>
  <div class="insights-container">
    <div class="insights-header">
      <h3 class="insights-title">
        <i class="pi pi-lightbulb"></i>
        {{ t('analytics.insights_and_recommendations') }}
      </h3>
    </div>
    <div class="insights-body">
      <div v-if="insights.length === 0" class="no-insights">
        <i class="pi pi-info-circle"></i>
        <p>{{ t('analytics.no_insights_yet') }}</p>
      </div>
      <div v-else class="insights-grid">
        <div 
          v-for="(insight, index) in insights" 
          :key="index"
          class="insight-card"
          :class="`sentiment-${insight.sentiment}`"
        >
          <div class="insight-icon">
            <i :class="`pi ${insight.icon}`"></i>
          </div>
          <div class="insight-content">
            <p class="insight-message">{{ insight.message }}</p>
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
  insights: Array<{
    type: string
    icon: string
    message: string
    sentiment: 'positive' | 'negative' | 'warning' | 'info'
  }>
}>()
</script>

<style scoped>
.insights-container {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(226, 232, 240, 0.5);
}

.insights-header {
  margin-bottom: 1.5rem;
}

.insights-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.insights-title i {
  color: #f59e0b;
  font-size: 1.25rem;
}

.no-insights {
  text-align: center;
  padding: 2rem 1rem;
  color: #94a3b8;
}

.no-insights i {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
  opacity: 0.5;
}

.no-insights p {
  margin: 0;
  font-size: 0.9375rem;
}

.insights-grid {
  display: grid;
  gap: 1rem;
}

.insight-card {
  display: flex;
  gap: 1rem;
  padding: 1.25rem;
  border-radius: 10px;
  transition: all 0.2s;
  border-left: 4px solid;
}

.insight-card.sentiment-positive {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  border-color: #10b981;
}

.insight-card.sentiment-warning {
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
  border-color: #f59e0b;
}

.insight-card.sentiment-info {
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  border-color: #3b82f6;
}

.insight-card.sentiment-negative {
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  border-color: #ef4444;
}

.insight-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.insight-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.sentiment-positive .insight-icon i {
  color: #10b981;
  font-size: 1.25rem;
}

.sentiment-warning .insight-icon i {
  color: #f59e0b;
  font-size: 1.25rem;
}

.sentiment-info .insight-icon i {
  color: #3b82f6;
  font-size: 1.25rem;
}

.sentiment-negative .insight-icon i {
  color: #ef4444;
  font-size: 1.25rem;
}

.insight-content {
  flex: 1;
  display: flex;
  align-items: center;
}

.insight-message {
  margin: 0;
  font-size: 0.9375rem;
  line-height: 1.6;
  color: #1e293b;
  font-weight: 500;
}

@media (max-width: 768px) {
  .insight-card {
    padding: 1rem;
  }
  
  .insight-icon {
    width: 36px;
    height: 36px;
  }
  
  .insight-message {
    font-size: 0.875rem;
  }
}
</style>

