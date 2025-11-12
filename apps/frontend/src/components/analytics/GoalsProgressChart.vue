<template>
  <div class="chart-container">
    <div class="chart-header">
      <h3 class="chart-title">{{ t('analytics.goals_progress') }}</h3>
    </div>
    <div class="goals-grid">
      <!-- Weekly Goal -->
      <div class="goal-card">
        <div class="goal-ring">
          <svg viewBox="0 0 100 100" class="progress-ring">
            <circle class="progress-ring-bg" cx="50" cy="50" r="40"></circle>
            <circle 
              class="progress-ring-fill weekly"
              cx="50" 
              cy="50" 
              r="40"
              :style="{ strokeDashoffset: weeklyOffset }"
            ></circle>
          </svg>
          <div class="goal-center">
            <div class="goal-value">{{ weeklyCompleted }}/{{ weeklyGoal }}</div>
            <div class="goal-percent">{{ weeklyPercent }}%</div>
          </div>
        </div>
        <div class="goal-label">
          <i class="pi pi-calendar"></i>
          {{ t('analytics.weekly_goal') }}
        </div>
      </div>

      <!-- Monthly Goal -->
      <div class="goal-card">
        <div class="goal-ring">
          <svg viewBox="0 0 100 100" class="progress-ring">
            <circle class="progress-ring-bg" cx="50" cy="50" r="40"></circle>
            <circle 
              class="progress-ring-fill monthly"
              cx="50" 
              cy="50" 
              r="40"
              :style="{ strokeDashoffset: monthlyOffset }"
            ></circle>
          </svg>
          <div class="goal-center">
            <div class="goal-value">{{ monthlyCompleted }}/{{ monthlyGoal }}</div>
            <div class="goal-percent">{{ monthlyPercent }}%</div>
          </div>
        </div>
        <div class="goal-label">
          <i class="pi pi-calendar-plus"></i>
          {{ t('analytics.monthly_goal') }}
        </div>
      </div>

      <!-- On-Time Rate -->
      <div class="goal-card">
        <div class="goal-ring">
          <svg viewBox="0 0 100 100" class="progress-ring">
            <circle class="progress-ring-bg" cx="50" cy="50" r="40"></circle>
            <circle 
              class="progress-ring-fill ontime"
              cx="50" 
              cy="50" 
              r="40"
              :style="{ strokeDashoffset: onTimeOffset }"
            ></circle>
          </svg>
          <div class="goal-center">
            <div class="goal-percent">{{ onTimeRate }}%</div>
            <div class="goal-status">{{ onTimeRate >= 80 ? '✓' : '○' }}</div>
          </div>
        </div>
        <div class="goal-label">
          <i class="pi pi-target"></i>
          {{ t('analytics.on_time_delivery') }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps<{
  weeklyCompleted: number
  weeklyGoal: number
  monthlyCompleted: number
  monthlyGoal: number
  onTimeRate: number
}>()

const circumference = 2 * Math.PI * 40

// Безопасное вычисление процентов с защитой от деления на ноль
const weeklyPercent = computed(() => {
  if (!props.weeklyGoal || props.weeklyGoal === 0) return 0
  return Math.round((props.weeklyCompleted / props.weeklyGoal) * 100)
})

const monthlyPercent = computed(() => {
  if (!props.monthlyGoal || props.monthlyGoal === 0) return 0
  return Math.round((props.monthlyCompleted / props.monthlyGoal) * 100)
})

const weeklyOffset = computed(() => 
  circumference - (weeklyPercent.value / 100) * circumference
)

const monthlyOffset = computed(() => 
  circumference - (monthlyPercent.value / 100) * circumference
)

const onTimeOffset = computed(() => 
  circumference - (props.onTimeRate / 100) * circumference
)
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
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.chart-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.heatmap-legend {
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.legend-label {
  font-size: 0.75rem;
  color: #64748b;
}

.legend-colors {
  display: flex;
  gap: 0.25rem;
}

.legend-box {
  width: 12px;
  height: 12px;
  border-radius: 2px;
}

.goals-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1.5rem;
}

.goal-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
  background: #f8f9fa;
  border-radius: 12px;
  transition: all 0.3s;
}

.goal-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.goal-ring {
  position: relative;
  width: 120px;
  height: 120px;
}

.progress-ring {
  width: 100%;
  height: 100%;
  transform: rotate(-90deg);
}

.progress-ring-bg {
  fill: none;
  stroke: #e2e8f0;
  stroke-width: 8;
}

.progress-ring-fill {
  fill: none;
  stroke-width: 8;
  stroke-linecap: round;
  stroke-dasharray: 251.2;
  transition: stroke-dashoffset 1s ease;
}

.progress-ring-fill.weekly {
  stroke: #3b82f6;
}

.progress-ring-fill.monthly {
  stroke: #8b5cf6;
}

.progress-ring-fill.ontime {
  stroke: #10b981;
}

.goal-center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
}

.goal-value {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
}

.goal-percent {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
}

.goal-status {
  font-size: 1.25rem;
  color: #10b981;
  margin-top: 0.25rem;
}

.goal-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #64748b;
  text-align: center;
}

.goal-label i {
  color: #6366f1;
  font-size: 1rem;
}

@media (max-width: 768px) {
  .goals-grid {
    grid-template-columns: 1fr;
  }
  
  .goal-ring {
    width: 100px;
    height: 100px;
  }
  
  .goal-value {
    font-size: 1rem;
  }
  
  .goal-percent {
    font-size: 1.25rem;
  }
}
</style>




