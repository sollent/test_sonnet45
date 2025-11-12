<template>
  <div class="chart-container">
    <div class="chart-header">
      <h3 class="chart-title">{{ t('analytics.weekday_productivity') }}</h3>
      <div v-if="bestDay" class="best-day-badge">
        🏆 {{ t(`analytics.days.${bestDay.toLowerCase()}`) }}
      </div>
    </div>
    <div class="chart-body">
      <v-chart class="chart" :option="chartOption" autoresize />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { BarChart } from 'echarts/charts'
import {
  TitleComponent,
  TooltipComponent,
  GridComponent
} from 'echarts/components'
import VChart from 'vue-echarts'
import { useI18n } from 'vue-i18n'

use([
  CanvasRenderer,
  BarChart,
  TitleComponent,
  TooltipComponent,
  GridComponent
])

const { t } = useI18n()

const props = defineProps<{
  data: Record<string, number>
}>()

// Безопасные fallback значения для предотвращения ошибок при undefined данных
const safeData = computed(() => props.data ?? {})

const bestDay = computed(() => {
  const entries = Object.entries(safeData.value)
  if (entries.length === 0) return null
  const sorted = entries.sort((a, b) => b[1] - a[1])
  return sorted[0][0]
})

const chartOption = computed(() => {
  const dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']
  const days = dayOrder.map(day => t(`analytics.days.${day.toLowerCase()}`))
  const values = dayOrder.map(day => safeData.value[day] || 0)
  
  // Generate colors based on value (gradient from low to high)
  const maxValue = Math.max(...values)
  const colors = values.map(value => {
    const ratio = maxValue > 0 ? value / maxValue : 0
    if (ratio > 0.8) return '#10b981'
    if (ratio > 0.6) return '#3b82f6'
    if (ratio > 0.4) return '#f59e0b'
    return '#94a3b8'
  })
  
  return {
    tooltip: {
      trigger: 'axis',
      axisPointer: {
        type: 'shadow'
      },
      backgroundColor: 'rgba(255, 255, 255, 0.95)',
      borderColor: '#e2e8f0',
      borderWidth: 1,
      textStyle: {
        color: '#1e293b'
      },
      formatter: (params: any) => {
        const param = params[0]
        return `${param.name}<br/>${t('analytics.tasks')}: <strong>${param.value}</strong>`
      }
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '3%',
      top: '3%',
      containLabel: true
    },
    xAxis: {
      type: 'category',
      data: days,
      axisLabel: {
        color: '#64748b',
        fontSize: 12
      },
      axisLine: {
        lineStyle: {
          color: '#e2e8f0'
        }
      }
    },
    yAxis: {
      type: 'value',
      axisLabel: {
        color: '#64748b'
      },
      splitLine: {
        lineStyle: {
          color: '#f1f5f9',
          type: 'dashed'
        }
      }
    },
    series: [
      {
        data: values.map((value, index) => ({
          value,
          itemStyle: { 
            color: colors[index],
            borderRadius: [8, 8, 0, 0]
          }
        })),
        type: 'bar',
        barWidth: '60%',
        emphasis: {
          itemStyle: {
            shadowBlur: 10,
            shadowColor: 'rgba(0, 0, 0, 0.3)'
          }
        }
      }
    ]
  }
})
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

.best-day-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border: 1px solid #fcd34d;
  border-radius: 8px;
  color: #92400e;
  font-size: 0.8125rem;
  font-weight: 600;
}

.chart-body {
  position: relative;
}

.chart {
  width: 100%;
  height: 300px;
}

@media (max-width: 768px) {
  .chart {
    height: 250px;
  }
}
</style>




