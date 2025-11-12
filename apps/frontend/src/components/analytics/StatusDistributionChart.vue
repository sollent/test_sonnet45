<template>
  <div class="chart-container">
    <div class="chart-header">
      <h3 class="chart-title">{{ t('analytics.status_distribution') }}</h3>
    </div>
    <div class="chart-body">
      <v-chart class="chart" :option="chartOption" autoresize />
      <div class="chart-center-text">
        <div class="center-value">{{ total }}</div>
        <div class="center-label">{{ t('analytics.total') }}</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import VChart from 'vue-echarts'
import { useI18n } from 'vue-i18n'
import '@/utils/echarts' // Configure ECharts with tree-shaking

const { t } = useI18n()

const props = defineProps<{
  data: {
    pending: number
    in_progress: number
    completed: number
    cancelled: number
    total: number
  }
}>()

// Безопасные fallback значения для предотвращения ошибок при undefined данных
const safeData = computed(() => ({
  pending: props.data?.pending ?? 0,
  in_progress: props.data?.in_progress ?? 0,
  completed: props.data?.completed ?? 0,
  cancelled: props.data?.cancelled ?? 0,
  total: props.data?.total ?? 0
}))

const total = computed(() => safeData.value.total)

const chartOption = computed(() => ({
  tooltip: {
    trigger: 'item',
    formatter: '{b}: {c} ({d}%)',
    backgroundColor: 'rgba(255, 255, 255, 0.95)',
    borderColor: '#e2e8f0',
    borderWidth: 1,
    textStyle: {
      color: '#1e293b'
    }
  },
  legend: {
    orient: 'vertical',
    right: 20,
    top: 'center',
    textStyle: {
      color: '#64748b',
      fontSize: 14
    }
  },
  series: [
    {
      name: t('analytics.tasks'),
      type: 'pie',
      radius: ['50%', '70%'],
      center: ['35%', '50%'],
      avoidLabelOverlap: false,
      label: {
        show: false
      },
      emphasis: {
        label: {
          show: false
        },
        scale: true,
        scaleSize: 10
      },
      labelLine: {
        show: false
      },
      data: [
        {
          value: safeData.value.completed,
          name: t('tasks.status_completed'),
          itemStyle: { color: '#10b981' }
        },
        {
          value: safeData.value.in_progress,
          name: t('tasks.status_in_progress'),
          itemStyle: { color: '#3b82f6' }
        },
        {
          value: safeData.value.pending,
          name: t('tasks.status_pending'),
          itemStyle: { color: '#94a3b8' }
        },
        {
          value: safeData.value.cancelled,
          name: t('tasks.status_cancelled'),
          itemStyle: { color: '#ef4444' }
        }
      ]
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
  position: relative;
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

.chart-center-text {
  position: absolute;
  top: 50%;
  left: 35%;
  transform: translate(-50%, -50%);
  text-align: center;
  pointer-events: none;
}

.center-value {
  font-size: 2.5rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
}

.center-label {
  font-size: 0.875rem;
  color: #64748b;
  margin-top: 0.25rem;
}

@media (max-width: 768px) {
  .chart {
    height: 300px;
  }
  
  .center-value {
    font-size: 2rem;
  }
}
</style>




