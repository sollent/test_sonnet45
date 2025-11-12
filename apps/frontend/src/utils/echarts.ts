/**
 * ECharts configuration with tree-shaking
 *
 * This file imports and registers only the ECharts components that are actually used
 * in the application, reducing bundle size by ~150-200 KB compared to importing entire ECharts.
 *
 * Components registered:
 * - Charts: LineChart, BarChart, PieChart
 * - Components: Grid, Tooltip, Legend, Title
 * - Renderer: Canvas (lighter than SVG for our use case)
 *
 * Usage in components:
 * ```typescript
 * import VChart from 'vue-echarts'
 * import { setupECharts } from '@/utils/echarts'
 *
 * // Ensure ECharts is configured (safe to call multiple times)
 * setupECharts()
 * ```
 */

import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart, BarChart, PieChart } from 'echarts/charts'
import {
  GridComponent,
  TooltipComponent,
  LegendComponent,
  TitleComponent
} from 'echarts/components'

// Track if ECharts has been configured to avoid duplicate registrations
let isConfigured = false

/**
 * Setup ECharts with only the components we need
 * Safe to call multiple times - will only register components once
 */
export function setupECharts(): void {
  if (isConfigured) {
    return
  }

  use([
    CanvasRenderer,
    LineChart,
    BarChart,
    PieChart,
    GridComponent,
    TooltipComponent,
    LegendComponent,
    TitleComponent
  ])

  isConfigured = true
}

// Auto-configure on import for convenience
setupECharts()

/**
 * Re-export use function for advanced usage if needed
 */
export { use }
