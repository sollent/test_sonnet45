<template>
  <div class="chart-container">
    <div class="chart-header">
      <h3 class="chart-title">{{ t('analytics.completion_timeline') }}</h3>
      <div class="chart-legend">
        <span class="legend-item"><span class="legend-dot created"></span> {{ t('analytics.created') }}</span>
        <span class="legend-item"><span class="legend-dot completed"></span> {{ t('analytics.completed') }}</span>
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
import { LineChart } from 'echarts/charts'
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
  LineChart,
  TitleComponent,
  TooltipComponent,
  LegendComponent,
  GridComponent
])

const { t } = useI18n()

const props = defineProps<{
  data: {
    dates: string[]
    created: number[]
    completed: number[]
    overdue: number[]
  }
}>()

// Безопасные fallback значения для предотвращения ошибок при undefined данных
const safeData = computed(() => ({
  dates: props.data?.dates ?? [],
  created: props.data?.created ?? [],
  completed: props.data?.completed ?? [],
  overdue: props.data?.overdue ?? []
}))

const chartOption = computed(() => ({
  tooltip: {
    trigger: 'axis',
    backgroundColor: 'rgba(255, 255, 255, 0.95)',
    borderColor: '#e2e8f0',
    borderWidth: 1,
    textStyle: {
      color: '#1e293b'
    },
    axisPointer: {
      type: 'cross',
      label: {
        backgroundColor: '#6366f1'
      }
    }
  },
  grid: {
    left: '3%',
    right: '4%',
    bottom: '3%',
    containLabel: true
  },
  xAxis: {
    type: 'category',
    boundaryGap: false,
    data: safeData.value.dates.map(date => {
      const d = new Date(date)
      return d.toLocaleDateString(t('app.locale') === 'ru' ? 'ru-RU' : 'en-US', {
        month: 'short',
        day: 'numeric'
      })
    }),
    axisLine: {
      lineStyle: {
        color: '#e2e8f0'
      }
    },
    axisLabel: {
      color: '#64748b',
      fontSize: 12
    }
  },
  yAxis: {
    type: 'value',
    axisLine: {
      lineStyle: {
        color: '#e2e8f0'
      }
    },
    axisLabel: {
      color: '#64748b',
      fontSize: 12
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
      name: t('analytics.created'),
      type: 'line',
      smooth: true,
      data: safeData.value.created,
      itemStyle: {
        color: '#3b82f6'
      },
      lineStyle: {
        width: 3
      },
      areaStyle: {
        color: {
          type: 'linear',
          x: 0,
          y: 0,
          x2: 0,
          y2: 1,
          colorStops: [
            { offset: 0, color: 'rgba(59, 130, 246, 0.2)' },
            { offset: 1, color: 'rgba(59, 130, 246, 0.05)' }
          ]
        }
      }
    },
    {
      name: t('analytics.completed'),
      type: 'line',
      smooth: true,
      data: safeData.value.completed,
      itemStyle: {
        color: '#10b981'
      },
      lineStyle: {
        width: 3
      },
      areaStyle: {
        color: {
          type: 'linear',
          x: 0,
          y: 0,
          x2: 0,
          y2: 1,
          colorStops: [
            { offset: 0, color: 'rgba(16, 185, 129, 0.2)' },
            { offset: 1, color: 'rgba(16, 185, 129, 0.05)' }
          ]
        }
      }
    }
  ]
}))
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

.chart-legend {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #64748b;
}

.legend-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
}

.legend-dot.created {
  background: #3b82f6;
}

.legend-dot.completed {
  background: #10b981;
}

.chart-body {
  position: relative;
}

.chart {
  width: 100%;
  height: 400px;
}

@media (max-width: 768px) {
  .chart {
    height: 300px;
  }
  
  .chart-header {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>

