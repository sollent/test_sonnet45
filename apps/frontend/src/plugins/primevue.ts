import type { App } from 'vue'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'
import Ripple from 'primevue/ripple'

// Import only used PrimeVue components for tree-shaking
import AutoComplete from 'primevue/autocomplete'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Calendar from 'primevue/calendar'
import Card from 'primevue/card'
import Checkbox from 'primevue/checkbox'
import Chip from 'primevue/chip'
import Chips from 'primevue/chips'
import ConfirmDialog from 'primevue/confirmdialog'
import Dialog from 'primevue/dialog'
import Divider from 'primevue/divider'
import Dropdown from 'primevue/dropdown'
import FileUpload from 'primevue/fileupload'
import Image from 'primevue/image'
import InputNumber from 'primevue/inputnumber'
import InputSwitch from 'primevue/inputswitch'
import InputText from 'primevue/inputtext'
import Menu from 'primevue/menu'
import Message from 'primevue/message'
import MultiSelect from 'primevue/multiselect'
import OrganizationChart from 'primevue/organizationchart'
import Paginator from 'primevue/paginator'
import Password from 'primevue/password'
import ProgressBar from 'primevue/progressbar'
import Sidebar from 'primevue/sidebar'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'
import ToggleButton from 'primevue/togglebutton'

/**
 * Setup PrimeVue with tree-shaking
 * Only imports components that are actually used in the application
 *
 * Components registered globally (used in multiple places):
 * - Button, InputText, Dropdown, Dialog, etc.
 *
 * This approach reduces bundle size by ~200-300 KB compared to importing entire PrimeVue
 */
export function setupPrimeVue(app: App, locale: Record<string, unknown>): void {
  // Configure PrimeVue
  app.use(PrimeVue, {
    ripple: true,
    locale
  })

  // Services
  app.use(ToastService)
  app.use(ConfirmationService)

  // Directives
  app.directive('ripple', Ripple)

  // Register components globally
  // These are used in multiple places across the app
  app.component('AutoComplete', AutoComplete)
  app.component('Badge', Badge)
  app.component('Button', Button)
  app.component('Calendar', Calendar)
  app.component('Card', Card)
  app.component('Checkbox', Checkbox)
  app.component('Chip', Chip)
  app.component('Chips', Chips)
  app.component('ConfirmDialog', ConfirmDialog)
  app.component('Dialog', Dialog)
  app.component('Divider', Divider)
  app.component('Dropdown', Dropdown)
  app.component('FileUpload', FileUpload)
  app.component('Image', Image)
  app.component('InputNumber', InputNumber)
  app.component('InputSwitch', InputSwitch)
  app.component('InputText', InputText)
  app.component('Menu', Menu)
  app.component('Message', Message)
  app.component('MultiSelect', MultiSelect)
  app.component('OrganizationChart', OrganizationChart)
  app.component('Paginator', Paginator)
  app.component('Password', Password)
  app.component('ProgressBar', ProgressBar)
  app.component('Sidebar', Sidebar)
  app.component('Skeleton', Skeleton)
  app.component('Tag', Tag)
  app.component('Textarea', Textarea)
  app.component('Toast', Toast)
  app.component('ToggleButton', ToggleButton)
}
