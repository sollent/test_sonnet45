<template>
  <div class="recurrence-settings">
    <!-- Enable recurrence toggle -->
    <div class="recurrence-toggle">
      <ToggleButton 
        v-model="localEnabled"
        :on-label="t('tasks.repeat')"
        :off-label="t('tasks.no_repeat')"
        @change="handleToggle"
      />
    </div>

    <!-- Recurrence options (shown when enabled) -->
    <div v-if="localEnabled" class="recurrence-options">
      <!-- Recurrence type selector -->
      <div class="form-field">
        <label class="field-label">
          <i class="pi pi-refresh"></i>
          {{ t('tasks.recurrence_type') }}
        </label>
        <Dropdown
          v-model="localRule.recurrenceType"
          :options="recurrenceTypeOptions"
          option-label="label"
          option-value="value"
          :placeholder="t('tasks.select_recurrence_type')"
          class="w-full"
          @change="handleTypeChange"
        />
      </div>

      <!-- Daily settings (no additional options) -->
      <div v-if="localRule.recurrenceType === 'daily'" class="type-settings">
        <p class="type-description">{{ t('tasks.daily_description') }}</p>
      </div>

      <!-- Weekly settings -->
      <div v-else-if="localRule.recurrenceType === 'weekly'" class="type-settings">
        <label class="field-label">{{ t('tasks.days_of_week') }}</label>
        <div class="days-selector">
          <button
            v-for="day in weekDays"
            :key="day.value"
            :class="['day-btn', { active: localRule.daysOfWeek?.includes(day.value) }]"
            @click="toggleWeekDay(day.value)"
          >
            {{ day.label }}
          </button>
        </div>
      </div>

      <!-- Monthly settings -->
      <div v-else-if="localRule.recurrenceType === 'monthly'" class="type-settings">
        <div class="form-field">
          <label class="field-label">{{ t('tasks.day_of_month') }}</label>
          <InputNumber
            v-model="localRule.dayOfMonth"
            :min="1"
            :max="31"
            :placeholder="t('tasks.enter_day')"
            class="w-full"
          />
        </div>
      </div>

      <!-- Yearly settings -->
      <div v-else-if="localRule.recurrenceType === 'yearly'" class="type-settings">
        <div class="form-row">
          <div class="form-field">
            <label class="field-label">{{ t('tasks.month') }}</label>
            <Dropdown
              v-model="localRule.monthOfYear"
              :options="monthOptions"
              option-label="label"
              option-value="value"
              :placeholder="t('tasks.select_month')"
              class="w-full"
            />
          </div>
          <div class="form-field">
            <label class="field-label">{{ t('tasks.day') }}</label>
            <InputNumber
              v-model="localRule.dayOfMonth"
              :min="1"
              :max="31"
              :placeholder="t('tasks.enter_day')"
              class="w-full"
            />
          </div>
        </div>
      </div>

      <!-- Custom interval settings -->
      <div v-else-if="localRule.recurrenceType === 'custom'" class="type-settings">
        <div class="form-field">
          <label class="field-label">{{ t('tasks.every_n_days') }}</label>
          <div class="interval-input">
            <span>{{ t('tasks.every') }}</span>
            <InputNumber
              v-model="localRule.interval"
              :min="1"
              :max="365"
              :placeholder="1"
              class="interval-number"
            />
            <span>{{ t('tasks.days') }}</span>
          </div>
        </div>
      </div>

      <!-- Time of day -->
      <div class="form-field">
        <label class="field-label">
          <i class="pi pi-clock"></i>
          {{ t('tasks.time_of_day') }}
        </label>
        <Calendar
          v-model="timeOfDay"
          time-only
          hour-format="24"
          :step-minute="10"
          :placeholder="t('tasks.select_time')"
          class="w-full"
        />
      </div>

      <!-- Advanced settings (collapsible) -->
      <div class="advanced-settings">
        <button 
          class="advanced-toggle"
          @click="showAdvanced = !showAdvanced"
        >
          <i :class="['pi', showAdvanced ? 'pi-chevron-up' : 'pi-chevron-down']"></i>
          {{ t('tasks.advanced_settings') }}
        </button>

        <div v-if="showAdvanced" class="advanced-content">
          <!-- End date -->
          <div class="form-field">
            <label class="field-label">
              <i class="pi pi-calendar-times"></i>
              {{ t('tasks.end_date_optional') }}
            </label>
            <Calendar
              v-model="endDate"
              date-format="dd.mm.yy"
              :placeholder="t('tasks.select_end_date')"
              :min-date="new Date()"
              class="w-full"
            />
          </div>

          <!-- Max occurrences -->
          <div class="form-field">
            <label class="field-label">
              <i class="pi pi-hashtag"></i>
              {{ t('tasks.max_occurrences_optional') }}
            </label>
            <InputNumber
              v-model="localRule.maxOccurrences"
              :min="1"
              :max="999"
              :placeholder="t('tasks.no_limit')"
              class="w-full"
            />
          </div>
        </div>
      </div>

      <!-- Preview of next occurrences -->
      <div v-if="previewDates.length > 0" class="preview-section">
        <label class="field-label">
          <i class="pi pi-eye"></i>
          {{ t('tasks.preview_occurrences') }}
        </label>
        <div class="preview-dates">
          <div v-for="(date, index) in previewDates" :key="index" class="preview-date">
            <i class="pi pi-calendar"></i>
            {{ formatPreviewDate(date) }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import ToggleButton from 'primevue/togglebutton'
import Dropdown from 'primevue/dropdown'
import InputNumber from 'primevue/inputnumber'
import Calendar from 'primevue/calendar'
import type { RecurrenceRule, RecurrenceSettings } from '@/types/task.types'

// Props
const props = defineProps<{
  modelValue?: RecurrenceSettings
}>()

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: RecurrenceSettings]
}>()

// Composables
const { t, locale } = useI18n()

// Local state
const localEnabled = ref(props.modelValue?.enabled || false)
const localRule = ref<RecurrenceRule>({
  recurrenceType: props.modelValue?.rule?.recurrenceType || 'daily',
  interval: props.modelValue?.rule?.interval || 1,
  daysOfWeek: props.modelValue?.rule?.daysOfWeek || [1], // Default to Monday
  dayOfMonth: props.modelValue?.rule?.dayOfMonth || 1,
  monthOfYear: props.modelValue?.rule?.monthOfYear || 1,
  endDate: props.modelValue?.rule?.endDate || null,
  maxOccurrences: props.modelValue?.rule?.maxOccurrences || null,
  timeOfDay: props.modelValue?.rule?.timeOfDay || null,
})

const timeOfDay = ref<Date | null>(null)
const endDate = ref<Date | null>(null)
const showAdvanced = ref(false)
const previewDates = ref<string[]>([])

// Initialize time and date from props
if (localRule.value.timeOfDay) {
  const [hours, minutes] = localRule.value.timeOfDay.split(':')
  const date = new Date()
  date.setHours(parseInt(hours), parseInt(minutes))
  timeOfDay.value = date
}

if (localRule.value.endDate) {
  endDate.value = new Date(localRule.value.endDate)
}

// Computed
const recurrenceTypeOptions = computed(() => [
  { value: 'daily', label: t('tasks.daily') },
  { value: 'weekly', label: t('tasks.weekly') },
  { value: 'monthly', label: t('tasks.monthly') },
  { value: 'yearly', label: t('tasks.yearly') },
  { value: 'custom', label: t('tasks.custom') },
])

const weekDays = computed(() => [
  { value: 1, label: t('days.mon_short') },
  { value: 2, label: t('days.tue_short') },
  { value: 3, label: t('days.wed_short') },
  { value: 4, label: t('days.thu_short') },
  { value: 5, label: t('days.fri_short') },
  { value: 6, label: t('days.sat_short') },
  { value: 7, label: t('days.sun_short') },
])

const monthOptions = computed(() => [
  { value: 1, label: t('months.january') },
  { value: 2, label: t('months.february') },
  { value: 3, label: t('months.march') },
  { value: 4, label: t('months.april') },
  { value: 5, label: t('months.may') },
  { value: 6, label: t('months.june') },
  { value: 7, label: t('months.july') },
  { value: 8, label: t('months.august') },
  { value: 9, label: t('months.september') },
  { value: 10, label: t('months.october') },
  { value: 11, label: t('months.november') },
  { value: 12, label: t('months.december') },
])

// Methods
function handleToggle() {
  emitUpdate()
  if (localEnabled.value) {
    generatePreview()
  }
}

function handleTypeChange() {
  // Reset type-specific settings
  switch (localRule.value.recurrenceType) {
    case 'weekly':
      localRule.value.daysOfWeek = [1] // Default to Monday
      break
    case 'monthly':
      localRule.value.dayOfMonth = 1
      break
    case 'yearly':
      localRule.value.monthOfYear = 1
      localRule.value.dayOfMonth = 1
      break
    case 'custom':
      localRule.value.interval = 1
      break
  }
  emitUpdate()
  generatePreview()
}

function toggleWeekDay(day: number) {
  if (!localRule.value.daysOfWeek) {
    localRule.value.daysOfWeek = []
  }
  
  const index = localRule.value.daysOfWeek.indexOf(day)
  if (index > -1) {
    // Don't allow removing all days
    if (localRule.value.daysOfWeek.length > 1) {
      localRule.value.daysOfWeek.splice(index, 1)
    }
  } else {
    localRule.value.daysOfWeek.push(day)
    localRule.value.daysOfWeek.sort()
  }
  
  emitUpdate()
  generatePreview()
}

function emitUpdate() {
  // Convert time and date to strings
  if (timeOfDay.value) {
    const hours = timeOfDay.value.getHours().toString().padStart(2, '0')
    const minutes = timeOfDay.value.getMinutes().toString().padStart(2, '0')
    localRule.value.timeOfDay = `${hours}:${minutes}`
  } else {
    localRule.value.timeOfDay = null
  }
  
  if (endDate.value) {
    localRule.value.endDate = endDate.value.toISOString().split('T')[0]
  } else {
    localRule.value.endDate = null
  }
  
  emit('update:modelValue', {
    enabled: localEnabled.value,
    rule: localEnabled.value ? { ...localRule.value } : undefined
  })
}

function generatePreview() {
  // TODO: Call API to get preview dates
  // For now, generate simple preview client-side
  const dates: Date[] = []
  const startDate = new Date()
  
  if (!localEnabled.value) {
    previewDates.value = []
    return
  }
  
  for (let i = 0; i < 5; i++) {
    let nextDate: Date | null = null
    
    switch (localRule.value.recurrenceType) {
      case 'daily':
        nextDate = new Date(startDate)
        nextDate.setDate(startDate.getDate() + (i + 1))
        break
        
      case 'weekly':
        // Simplified - just show next 5 weeks on first selected day
        const firstDay = localRule.value.daysOfWeek?.[0] || 1
        nextDate = new Date(startDate)
        nextDate.setDate(startDate.getDate() + (i * 7) + firstDay)
        break
        
      case 'monthly':
        nextDate = new Date(startDate)
        nextDate.setMonth(startDate.getMonth() + (i + 1))
        nextDate.setDate(localRule.value.dayOfMonth || 1)
        break
        
      case 'yearly':
        nextDate = new Date(startDate)
        nextDate.setFullYear(startDate.getFullYear() + (i + 1))
        nextDate.setMonth((localRule.value.monthOfYear || 1) - 1)
        nextDate.setDate(localRule.value.dayOfMonth || 1)
        break
        
      case 'custom':
        const interval = localRule.value.interval || 1
        nextDate = new Date(startDate)
        nextDate.setDate(startDate.getDate() + ((i + 1) * interval))
        break
    }
    
    if (nextDate) {
      // Apply time if set
      if (localRule.value.timeOfDay) {
        const [hours, minutes] = localRule.value.timeOfDay.split(':')
        nextDate.setHours(parseInt(hours), parseInt(minutes))
      }
      
      // Check end conditions
      if (localRule.value.endDate && nextDate > new Date(localRule.value.endDate)) {
        break
      }
      
      if (localRule.value.maxOccurrences && i >= localRule.value.maxOccurrences) {
        break
      }
      
      dates.push(nextDate)
    }
  }
  
  previewDates.value = dates.map(d => d.toISOString())
}

function formatPreviewDate(dateStr: string): string {
  const date = new Date(dateStr)
  const options: Intl.DateTimeFormatOptions = {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: localRule.value.timeOfDay ? '2-digit' : undefined,
    minute: localRule.value.timeOfDay ? '2-digit' : undefined,
  }
  return date.toLocaleDateString(locale.value, options)
}

// Watchers
watch(timeOfDay, () => {
  emitUpdate()
  generatePreview()
})

watch(endDate, () => {
  emitUpdate()
  generatePreview()
})

watch(() => localRule.value, () => {
  emitUpdate()
  generatePreview()
}, { deep: true })

// Initial preview
if (localEnabled.value) {
  generatePreview()
}
</script>

<style scoped>
.recurrence-settings {
  padding: 1rem 0;
}

.recurrence-toggle {
  margin-bottom: 1.5rem;
}

.recurrence-options {
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.form-field {
  margin-bottom: 1.25rem;
}

.field-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-color-secondary);
  margin-bottom: 0.5rem;
}

.field-label i {
  font-size: 1rem;
  color: var(--primary-color);
}

.type-settings {
  padding: 1rem;
  background: var(--surface-50);
  border-radius: 8px;
  margin-bottom: 1.25rem;
}

.type-description {
  color: var(--text-color-secondary);
  font-size: 0.875rem;
  margin: 0;
}

.days-selector {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.day-btn {
  width: 3rem;
  height: 3rem;
  border-radius: 8px;
  border: 2px solid var(--surface-border);
  background: white;
  color: var(--text-color);
  font-weight: 500;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
}

.day-btn:hover {
  border-color: var(--primary-color);
  background: var(--primary-50);
}

.day-btn.active {
  background: var(--primary-color);
  border-color: var(--primary-color);
  color: white;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.interval-input {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.interval-input span {
  color: var(--text-color-secondary);
  font-size: 0.875rem;
}

.interval-number {
  width: 80px;
}

.advanced-settings {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--surface-border);
}

.advanced-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: none;
  border: none;
  color: var(--primary-color);
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  padding: 0.5rem 0;
  transition: opacity 0.2s;
}

.advanced-toggle:hover {
  opacity: 0.8;
}

.advanced-content {
  margin-top: 1rem;
  animation: slideDown 0.3s ease-out;
}

.preview-section {
  margin-top: 1.5rem;
  padding: 1rem;
  background: var(--surface-50);
  border-radius: 8px;
}

.preview-dates {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.preview-date {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
  background: white;
  border-radius: 6px;
  font-size: 0.875rem;
  color: var(--text-color-secondary);
}

.preview-date i {
  color: var(--primary-color);
  font-size: 0.875rem;
}

/* Mobile styles */
@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .days-selector {
    justify-content: space-between;
  }
  
  .day-btn {
    flex: 1;
    min-width: 2.5rem;
    max-width: 3rem;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .type-settings {
    background: var(--surface-800);
  }
  
  .day-btn {
    background: var(--surface-800);
    border-color: var(--surface-600);
  }
  
  .day-btn:hover {
    background: var(--surface-700);
  }
  
  .preview-section {
    background: var(--surface-800);
  }
  
  .preview-date {
    background: var(--surface-700);
  }
}
</style>
