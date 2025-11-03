<template>
  <div class="analytics-view">
    <!-- Header -->
    <header class="analytics-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="header-title">{{ t('analytics.title') }}</h1>
        </div>
        <div class="header-nav">
          <Button
            :label="!isMobile ? t('tasks.my_tasks') : ''"
            icon="pi pi-list"
            severity="secondary"
            text
            @click="router.push('/dashboard')"
          />
          <Button
            :label="!isMobile ? t('calendar.title') : ''"
            icon="pi pi-calendar"
            severity="secondary"
            text
            @click="router.push('/calendar')"
          />
          <Button
            :label="!isMobile ? t('analytics.title') : ''"
            icon="pi pi-chart-bar"
            severity="primary"
            text
          />
        </div>
        <div class="header-actions">
          <button v-if="!isMobile" @click="router.push('/profile')" class="profile-button">
            <i class="pi pi-user"></i>
            <span>{{ user?.email }}</span>
          </button>
          <Button 
            icon="pi pi-sign-out"
            severity="secondary"
            text
            rounded
            @click="handleLogout"
            :aria-label="t('common.logout')"
          />
        </div>
      </div>
    </header>

    <!-- Period Selector - Sticky -->
    <div class="period-selector-sticky" :class="{ 'is-sticky': isSticky }">
      <div class="period-selector-content">
        <div class="period-label">
          <i class="pi pi-filter"></i>
          <span>{{ t('analytics.period') }}:</span>
        </div>
        <div class="period-buttons">
          <Button
            v-for="period in periods"
            :key="period.value"
            :label="period.label"
            :severity="selectedPeriod === period.value && !customRange ? 'primary' : 'secondary'"
            :outlined="selectedPeriod !== period.value || customRange"
            @click="selectPeriod(period.value)"
            size="small"
            rounded
          />
          <Button
            :label="customRange ? formatCustomRange() : t('analytics.custom_range')"
            :severity="customRange ? 'primary' : 'secondary'"
            :outlined="!customRange"
            icon="pi pi-calendar"
            @click="showDatePicker = true"
            size="small"
            rounded
          />
        </div>
        <div v-if="isRecalculating" class="recalculating-badge">
          <i class="pi pi-spin pi-spinner"></i>
          {{ t('analytics.recalculating') }}
        </div>
        <div v-else class="period-info">
          <i class="pi pi-info-circle"></i>
          {{ t('analytics.period_info') }}
        </div>
      </div>
    </div>

    <!-- Custom Date Range Dialog -->
    <Dialog
      v-model:visible="showDatePicker"
      :header="t('analytics.select_custom_range')"
      :modal="true"
      :style="{ width: '600px' }"
      :breakpoints="{ '960px': '75vw', '640px': '95vw' }"
    >
      <div class="custom-date-picker">
        <Calendar
          v-model="customDateRange"
          selectionMode="range"
          :placeholder="t('analytics.select_dates')"
          dateFormat="dd.mm.yy"
          :showIcon="true"
          :numberOfMonths="2"
          :showButtonBar="true"
          :inline="true"
        />
      </div>
      <template #footer>
        <Button
          :label="t('common.cancel')"
          severity="secondary"
          @click="showDatePicker = false"
        />
        <Button
          :label="t('common.apply')"
          @click="applyCustomRange"
          :disabled="!customDateRange || !customDateRange[0] || !customDateRange[1]"
        />
      </template>
    </Dialog>

    <!-- Loading State -->
    <div v-if="isLoading" class="analytics-loading">
      <Skeleton v-for="i in 6" :key="i" height="150px" class="skeleton-card" />
    </div>

    <!-- Analytics Content -->
    <div v-else class="analytics-content">
      <!-- Overview Cards -->
      <div class="overview-grid">
        <div class="metric-card">
          <div class="metric-icon total">
            <i class="pi pi-list"></i>
          </div>
          <div class="metric-content">
            <div class="metric-value">{{ overview?.totalTasks || 0 }}</div>
            <div class="metric-label">{{ t('analytics.total_tasks') }}</div>
            <div v-if="overview?.weeklyChange" class="metric-change" :class="overview.weeklyChange > 0 ? 'positive' : 'negative'">
              <i :class="overview.weeklyChange > 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down'"></i>
              {{ Math.abs(overview.weeklyChange) }} ({{ Math.abs(overview.weeklyChangePercent) }}%)
            </div>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon completed">
            <i class="pi pi-check-circle"></i>
          </div>
          <div class="metric-content">
            <div class="metric-value">{{ overview?.completedThisWeek || 0 }}</div>
            <div class="metric-label">{{ t('analytics.completed_this_week') }}</div>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon time">
            <i class="pi pi-clock"></i>
          </div>
          <div class="metric-content">
            <div class="metric-value">{{ overview?.averageCompletionTime || 0 }} {{ t('analytics.days_label') }}</div>
            <div class="metric-label">{{ t('analytics.avg_completion_time') }}</div>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon streak">
            <i class="pi pi-bolt"></i>
          </div>
          <div class="metric-content">
            <div class="metric-value">{{ overview?.currentStreak || 0 }} 🔥</div>
            <div class="metric-label">{{ t('analytics.current_streak') }}</div>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon target">
            <i class="pi pi-target"></i>
          </div>
          <div class="metric-content">
            <div class="metric-value">{{ overview?.onTimeCompletionRate || 0 }}%</div>
            <div class="metric-label">{{ t('analytics.on_time_rate') }}</div>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon productive">
            <i class="pi pi-calendar-plus"></i>
          </div>
          <div class="metric-content">
            <div class="metric-value">{{ t(`analytics.days.${overview?.mostProductiveDay?.toLowerCase() || 'monday'}`) }}</div>
            <div class="metric-label">{{ t('analytics.most_productive_day') }}</div>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon overdue">
            <i class="pi pi-exclamation-circle"></i>
          </div>
          <div class="metric-content">
            <div class="metric-value">{{ overview?.overdue || 0 }}</div>
            <div class="metric-label">{{ t('analytics.overdue_tasks') }}</div>
          </div>
        </div>
      </div>

      <!-- Completion Timeline Chart -->
      <CompletionTimelineChart 
        v-if="timelineData"
        :data="timelineData"
        class="chart-full-width"
      />

      <!-- Charts Grid -->
      <div class="charts-grid">
        <!-- Status Distribution -->
        <StatusDistributionChart 
          v-if="statusDistribution"
          :data="statusDistribution"
        />

        <!-- Priority Breakdown -->
        <PriorityBreakdownChart 
          v-if="priorityBreakdown"
          :data="priorityBreakdown"
        />
      </div>

      <!-- Second Row -->
      <div class="charts-grid">
        <!-- Weekday Productivity -->
        <WeekdayProductivityChart 
          v-if="weekdayData"
          :data="weekdayData"
        />

        <!-- Top Tags -->
        <TopTagsChart 
          v-if="topTags && topTags.length > 0"
          :tags="topTags"
        />
      </div>

      <!-- Activity Heatmap -->
      <ActivityHeatmapChart 
        v-if="heatmapData"
        :data="heatmapData"
        :year="currentYear"
        class="chart-full-width"
      />

      <!-- Goals Progress -->
      <GoalsProgressChart 
        v-if="overview"
        :weeklyCompleted="overview.completedThisWeek"
        :weeklyGoal="60"
        :monthlyCompleted="overview.completed"
        :monthlyGoal="240"
        :onTimeRate="overview.onTimeCompletionRate"
      />

      <!-- Insights -->
      <InsightsPanel 
        v-if="insights && insights.length > 0"
        :insights="insights"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useToast } from '@/composables/useToast'
import { 
  analyticsService, 
  type AnalyticsOverview,
  type TimelineData,
  type StatusDistribution,
  type PriorityBreakdown,
  type TopTag,
  type Insight
} from '@/services/analytics.service'
import Button from 'primevue/button'
import Skeleton from 'primevue/skeleton'
import Dialog from 'primevue/dialog'
import Calendar from 'primevue/calendar'
import CompletionTimelineChart from '@/components/analytics/CompletionTimelineChart.vue'
import StatusDistributionChart from '@/components/analytics/StatusDistributionChart.vue'
import PriorityBreakdownChart from '@/components/analytics/PriorityBreakdownChart.vue'
import WeekdayProductivityChart from '@/components/analytics/WeekdayProductivityChart.vue'
import TopTagsChart from '@/components/analytics/TopTagsChart.vue'
import InsightsPanel from '@/components/analytics/InsightsPanel.vue'
import ActivityHeatmapChart from '@/components/analytics/ActivityHeatmapChart.vue'
import GoalsProgressChart from '@/components/analytics/GoalsProgressChart.vue'

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()
const { showError } = useToast()

const user = computed(() => authStore.user)
const isMobile = ref(window.innerWidth < 768)
const isSticky = ref(false)

const isLoading = ref(true)
const isRecalculating = ref(false)
const showDatePicker = ref(false)
const customDateRange = ref<Date[] | null>(null)
const customRange = ref(false)

const overview = ref<AnalyticsOverview | null>(null)
const timelineData = ref<TimelineData | null>(null)
const statusDistribution = ref<StatusDistribution | null>(null)
const priorityBreakdown = ref<PriorityBreakdown | null>(null)
const weekdayData = ref<Record<string, number> | null>(null)
const heatmapData = ref<Record<string, number> | null>(null)
const topTags = ref<TopTag[]>([])
const insights = ref<Insight[]>([])
const selectedPeriod = ref(30)
const currentYear = new Date().getFullYear()

const periods = computed(() => [
  { label: t('analytics.7_days'), value: 7 },
  { label: t('analytics.30_days'), value: 30 },
  { label: t('analytics.90_days'), value: 90 },
  { label: t('analytics.all_time'), value: 365 }
])

// DEPRECATED: Replaced with reloadAllData using single dashboard endpoint
async function loadAnalytics() {
  await reloadAllData()
}

function selectPeriod(period: number) {
  customRange.value = false
  customDateRange.value = null
  selectedPeriod.value = period
  reloadAllData()
}

function formatCustomRange(): string {
  if (!customDateRange.value || !customDateRange.value[0]) return t('analytics.custom_range')
  
  const start = customDateRange.value[0].toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' })
  const end = customDateRange.value[1] 
    ? customDateRange.value[1].toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' })
    : start
  
  return `${start} - ${end}`
}

async function applyCustomRange() {
  if (!customDateRange.value || !customDateRange.value[0] || !customDateRange.value[1]) return
  
  customRange.value = true
  showDatePicker.value = false
  await reloadAllData()
}

async function reloadAllData() {
  // Show loading spinner on first load, recalculating badge on subsequent
  if (isLoading.value) {
    // First load - keep isLoading
  } else {
    isRecalculating.value = true
  }

  try {
    // Single optimized dashboard request with all data
    let dashboardParams: any = { year: currentYear }

    // Timeline parameters
    if (customRange.value && customDateRange.value) {
      const dateFrom = customDateRange.value[0].toISOString().split('T')[0]
      const dateTo = customDateRange.value[1]?.toISOString().split('T')[0] || dateFrom
      dashboardParams.dateFrom = dateFrom
      dashboardParams.dateTo = dateTo
    } else {
      dashboardParams.period = selectedPeriod.value
    }

    const dashboardData = await analyticsService.getDashboard(dashboardParams)

    // Assign all data from single response
    overview.value = dashboardData.overview
    timelineData.value = dashboardData.timeline
    statusDistribution.value = dashboardData.statusDistribution
    priorityBreakdown.value = dashboardData.priorityBreakdown
    weekdayData.value = dashboardData.weekdayProductivity
    heatmapData.value = dashboardData.productivityHeatmap
    topTags.value = dashboardData.topTags
    insights.value = dashboardData.insights

  } catch (error: any) {
    showError(error.message || t('errors.fetch_failed'))
  } finally {
    isLoading.value = false
    isRecalculating.value = false
  }
}

// Reload data when period changes
watch(selectedPeriod, () => {
  if (!customRange.value) {
    reloadAllData()
  }
})

async function handleLogout() {
  try {
    await authStore.logout()
    router.push('/login')
  } catch (error) {
    showError(t('errors.logout_failed'))
  }
}

function handleResize() {
  isMobile.value = window.innerWidth < 768
}

function handleScroll() {
  isSticky.value = window.scrollY > 100
}

onMounted(async () => {
  // Initial load with default period
  await reloadAllData()
  
  window.addEventListener('resize', handleResize)
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  window.removeEventListener('scroll', handleScroll)
})
</script>

<style scoped>
.analytics-view {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.analytics-header {
  background: rgba(255, 255, 255, 0.98);
  border-bottom: 1px solid #e2e8f0;
  padding: 1.5rem 0;
  position: sticky;
  top: 0;
  z-index: 100;
  backdrop-filter: blur(10px);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.header-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left {
  display: flex;
  align-items: center;
}

.header-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.header-nav {
  display: flex;
  gap: 0.5rem;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.profile-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  color: #64748b;
}

.profile-button:hover {
  background: #f8f9fa;
  border-color: #cbd5e0;
  color: #1e293b;
}

.profile-button i {
  font-size: 0.875rem;
  color: #6366f1;
}

.period-selector-sticky {
  position: sticky;
  top: 88px;
  z-index: 90;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid transparent;
  transition: all 0.3s;
  padding: 1rem 0;
  margin-bottom: 1.5rem;
}

.period-selector-sticky.is-sticky {
  border-bottom-color: #e2e8f0;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.period-selector-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.period-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #64748b;
  font-size: 0.9375rem;
}

.period-label i {
  color: #6366f1;
  font-size: 1rem;
}

.period-buttons {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.period-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  color: #64748b;
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  margin-left: auto;
}

.period-info i {
  color: #3b82f6;
  font-size: 0.875rem;
}

.recalculating-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #6366f1;
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
  border: 1px solid #c7d2fe;
  border-radius: 8px;
  margin-left: auto;
}

.recalculating-badge i {
  font-size: 0.875rem;
}

.custom-date-picker {
  padding: 1rem 0;
}

.analytics-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem 3rem 2rem;
}

.analytics-loading {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-top: 1.5rem;
}

.skeleton-card {
  border-radius: 12px;
}

.overview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.metric-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(226, 232, 240, 0.5);
  display: flex;
  gap: 1rem;
  transition: all 0.3s;
}

.metric-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 30px rgba(99, 102, 241, 0.15);
}

.metric-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.metric-icon i {
  font-size: 1.5rem;
  color: white;
}

.metric-icon.total {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

.metric-icon.completed {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.metric-icon.time {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.metric-icon.streak {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.metric-icon.target {
  background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
}

.metric-icon.productive {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.metric-icon.overdue {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.metric-content {
  flex: 1;
}

.metric-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.25rem;
}

.metric-label {
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 0.5rem;
}

.metric-change {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.8125rem;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
}

.metric-change.positive {
  color: #059669;
  background: rgba(16, 185, 129, 0.1);
}

.metric-change.negative {
  color: #dc2626;
  background: rgba(239, 68, 68, 0.1);
}

.metric-change i {
  font-size: 0.75rem;
}

.chart-full-width {
  margin-bottom: 2rem;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

@media (max-width: 768px) {
  .charts-grid {
    grid-template-columns: 1fr;
  }
  
  .header-content {
    padding: 0 1rem;
  }

  .header-title {
    font-size: 1.25rem;
  }

  .period-selector-sticky {
    top: 66px;
    padding: 0.75rem 0;
    margin-bottom: 1rem;
  }

  .period-selector-content {
    padding: 0 1rem;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }

  .period-label {
    font-size: 0.8125rem;
  }

  .period-buttons {
    width: 100%;
    justify-content: flex-start;
  }

  .period-info,
  .recalculating-badge {
    width: 100%;
    margin-left: 0;
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
  }

  .analytics-content {
    padding: 0 1rem 2rem 1rem;
  }
  
  .analytics-loading {
    padding: 0 1rem;
    margin-top: 1rem;
  }

  .overview-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .metric-card {
    padding: 1rem;
  }

  .metric-icon {
    width: 48px;
    height: 48px;
  }

  .metric-icon i {
    font-size: 1.25rem;
  }

  .metric-value {
    font-size: 1.5rem;
  }

  .profile-button span {
    display: none;
  }
}
</style>

