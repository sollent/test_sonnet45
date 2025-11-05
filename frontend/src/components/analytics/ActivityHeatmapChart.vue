<template>
  <div class="chart-container">
    <div class="chart-header">
      <h3 class="chart-title">{{ t('analytics.activity_heatmap') }}</h3>
      <div class="heatmap-legend">
        <span class="legend-label">{{ t('analytics.less') }}</span>
        <div class="legend-colors">
          <span class="legend-box" style="background: #ebedf0"></span>
          <span class="legend-box" style="background: #c6e48b"></span>
          <span class="legend-box" style="background: #7bc96f"></span>
          <span class="legend-box" style="background: #239a3b"></span>
          <span class="legend-box" style="background: #196127"></span>
        </div>
        <span class="legend-label">{{ t('analytics.more') }}</span>
      </div>
    </div>
    <div class="heatmap-body">
      <div class="heatmap-months">
        <span v-for="month in months" :key="month" class="month-label">{{ month }}</span>
      </div>
      <div class="heatmap-grid">
        <div class="weekday-labels">
          <span class="weekday-label">{{ t('analytics.days_short.mon') }}</span>
          <span class="weekday-label"></span>
          <span class="weekday-label">{{ t('analytics.days_short.wed') }}</span>
          <span class="weekday-label"></span>
          <span class="weekday-label">{{ t('analytics.days_short.fri') }}</span>
          <span class="weekday-label"></span>
        </div>
        <div class="heatmap-cells">
          <div
            v-for="(day, index) in days"
            :key="index"
            class="heatmap-cell"
            :class="getIntensityClass(day.count)"
            :title="`${day.date}: ${day.count} ${t('analytics.tasks_completed')}`"
            @click="handleDayClick(day.date)"
          >
          </div>
        </div>
      </div>
      <div v-if="bestMonth" class="heatmap-insight">
        <i class="pi pi-trophy"></i>
        {{ t('analytics.most_productive_month') }}: {{ bestMonth.month }} ({{ bestMonth.count }} {{ t('analytics.tasks_count') }})
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

const { t } = useI18n()
const router = useRouter()

const props = defineProps<{
  data: Record<string, number>
  year: number
}>()

// Generate all days of the year
const days = computed(() => {
  const result = []
  const startDate = new Date(props.year, 0, 1)
  const endDate = new Date(props.year, 11, 31)
  
  // Start from first Monday before Jan 1
  const firstDay = new Date(startDate)
  const dayOfWeek = firstDay.getDay()
  const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek
  firstDay.setDate(firstDay.getDate() + diff)
  
  const current = new Date(firstDay)
  
  while (current <= endDate || current.getDay() !== 1) {
    const dateStr = current.toISOString().split('T')[0]
    const count = props.data[dateStr] || 0
    
    result.push({
      date: dateStr,
      count: count,
      isCurrentYear: current.getFullYear() === props.year
    })
    
    current.setDate(current.getDate() + 1)
    
    // Stop after completing last week
    if (current > endDate && current.getDay() === 1) {
      break
    }
  }
  
  return result
})

const months = computed(() => {
  const monthNames = []
  for (let i = 0; i < 12; i++) {
    const date = new Date(props.year, i, 1)
    monthNames.push(date.toLocaleDateString(t('app.locale') === 'ru' ? 'ru-RU' : 'en-US', { month: 'short' }))
  }
  return monthNames
})

const bestMonth = computed(() => {
  const monthCounts: Record<string, number> = {}
  
  Object.entries(props.data).forEach(([date, count]) => {
    const d = new Date(date)
    if (d.getFullYear() === props.year) {
      const month = d.toLocaleDateString(t('app.locale') === 'ru' ? 'ru-RU' : 'en-US', { month: 'long', year: 'numeric' })
      monthCounts[month] = (monthCounts[month] || 0) + count
    }
  })
  
  if (Object.keys(monthCounts).length === 0) return null
  
  const sorted = Object.entries(monthCounts).sort((a, b) => b[1] - a[1])
  return {
    month: sorted[0][0],
    count: sorted[0][1]
  }
})

function getIntensityClass(count: number): string {
  if (count === 0) return 'intensity-0'
  if (count <= 2) return 'intensity-1'
  if (count <= 5) return 'intensity-2'
  if (count <= 8) return 'intensity-3'
  return 'intensity-4'
}

function handleDayClick(date: string) {
  // Navigate to calendar on this date
  router.push(`/calendar?date=${date}`)
}
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
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.heatmap-body {
  overflow-x: auto;
}

.heatmap-months {
  display: flex;
  gap: 0;
  margin-bottom: 0.5rem;
  margin-left: 30px;
}

.month-label {
  flex: 1;
  font-size: 0.75rem;
  color: #64748b;
  text-align: left;
}

.heatmap-grid {
  display: flex;
  gap: 0.5rem;
}

.weekday-labels {
  display: flex;
  flex-direction: column;
  gap: 3px;
  padding-top: 0;
}

.weekday-label {
  height: 12px;
  font-size: 0.625rem;
  color: #64748b;
  line-height: 12px;
}

.heatmap-cells {
  display: grid;
  grid-auto-flow: column;
  grid-template-rows: repeat(7, 12px);
  gap: 3px;
  flex: 1;
}

.heatmap-cell {
  width: 12px;
  height: 12px;
  border-radius: 2px;
  cursor: pointer;
  transition: all 0.2s;
}

.heatmap-cell:hover {
  transform: scale(1.2);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  z-index: 10;
  position: relative;
}

.intensity-0 {
  background: #ebedf0;
}

.intensity-1 {
  background: #c6e48b;
}

.intensity-2 {
  background: #7bc96f;
}

.intensity-3 {
  background: #239a3b;
}

.intensity-4 {
  background: #196127;
}

.heatmap-insight {
  margin-top: 1rem;
  padding: 0.75rem 1rem;
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border: 1px solid #fcd34d;
  border-radius: 8px;
  color: #92400e;
  font-size: 0.875rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.heatmap-insight i {
  color: #f59e0b;
  font-size: 1rem;
}

@media (max-width: 768px) {
  .heatmap-cells {
    grid-template-rows: repeat(7, 10px);
    gap: 2px;
  }
  
  .heatmap-cell {
    width: 10px;
    height: 10px;
  }
  
  .weekday-label {
    height: 10px;
    line-height: 10px;
  }
}
</style>





