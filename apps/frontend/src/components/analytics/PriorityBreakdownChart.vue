<template>
  <div class="chart-container">
    <div class="chart-header">
      <h3 class="chart-title">{{ t('analytics.priority_breakdown') }}</h3>
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
  LegendComponent,
  GridComponent
} from 'echarts/components'
import VChart from 'vue-echarts'
import { useI18n } from 'vue-i18n'

use([
  CanvasRenderer,
  BarChart,
  TitleComponent,
  TooltipComponent,
  LegendComponent,
  GridComponent
])

const { t } = useI18n()

const props = defineProps<{
  data: {
    low: { total: number; completed: number; inProgress: number; pending: number }
    medium: { total: number; completed: number; inProgress: number; pending: number }
    high: { total: number; completed: number; inProgress: number; pending: number }
    urgent: { total: number; completed: number; inProgress: number; pending: number }
  }
}>()

const chartOption = computed(() => {
  // Безопасные fallback значения для предотвращения ошибок при undefined данных
  const defaultPriorityData = { completed: 0, inProgress: 0, pending: 0, total: 0 }
  const safeData = {
    urgent: props.data?.urgent ?? defaultPriorityData,
    high: props.data?.high ?? defaultPriorityData,
    medium: props.data?.medium ?? defaultPriorityData,
    low: props.data?.low ?? defaultPriorityData
  }

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
      }
    },
    legend: {
      data: [t('tasks.status_completed'), t('tasks.status_in_progress'), t('tasks.status_pending')],
      textStyle: {
        color: '#64748b'
      },
      bottom: 0
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '15%',
      top: '3%',
      containLabel: true
    },
    xAxis: {
      type: 'value',
      axisLabel: {
        color: '#64748b'
      },
      splitLine: {
        lineStyle: {
          color: '#f1f5f9'
        }
      }
    },
    yAxis: {
      type: 'category',
      data: [
        t('tasks.priority_urgent'),
        t('tasks.priority_high'),
        t('tasks.priority_medium'),
        t('tasks.priority_low')
      ],
      axisLabel: {
        color: '#64748b',
        fontSize: 13
      },
      axisLine: {
        lineStyle: {
          color: '#e2e8f0'
        }
      }
    },
    series: [
      {
        name: t('tasks.status_completed'),
        type: 'bar',
        stack: 'total',
        data: [
          safeData.urgent.completed,
          safeData.high.completed,
          safeData.medium.completed,
          safeData.low.completed
        ],
        itemStyle: {
          color: '#10b981'
        }
      },
      {
        name: t('tasks.status_in_progress'),
        type: 'bar',
        stack: 'total',
        data: [
          safeData.urgent.inProgress,
          safeData.high.inProgress,
          safeData.medium.inProgress,
          safeData.low.inProgress
        ],
        itemStyle: {
          color: '#3b82f6'
        }
      },
      {
        name: t('tasks.status_pending'),
        type: 'bar',
        stack: 'total',
        data: [
          safeData.urgent.pending,
          safeData.high.pending,
          safeData.medium.pending,
          safeData.low.pending
        ],
        itemStyle: {
          color: '#94a3b8'
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
  margin-bottom: 1.5rem;
}

.chart-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.chart-body {
  position: relative;
}

.chart {
  width: 100%;
  height: 350px;
}

@media (max-width: 768px) {
  .chart {
    height: 300px;
  }
}
</style>




