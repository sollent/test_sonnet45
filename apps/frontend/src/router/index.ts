import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { ROUTES } from '@/config/constants'

const routes: RouteRecordRaw[] = [
  // Landing pages
  {
    path: '/',
    name: 'Landing',
    component: () => import('@/views/landing/HomePage.vue'),
    meta: { requiresAuth: false }
  },

  // Feature pages
  {
    path: '/features/voice-control',
    name: 'VoiceControl',
    component: () => import('@/views/landing/features/VoiceControlPage.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/features/telegram',
    name: 'Telegram',
    component: () => import('@/views/landing/features/TelegramPage.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/features/ai-assistant',
    name: 'AIAssistant',
    component: () => import('@/views/landing/features/AIAssistantPage.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/features/file-processing',
    name: 'FileProcessing',
    component: () => import('@/views/landing/features/FileProcessingPage.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/features/smart-reminders',
    name: 'SmartReminders',
    component: () => import('@/views/landing/features/SmartRemindersPage.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/features/web-interface',
    name: 'WebInterface',
    component: () => import('@/views/landing/features/WebInterfacePage.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/features/web-search',
    name: 'WebSearch',
    component: () => import('@/views/landing/features/WebSearchPage.vue'),
    meta: { requiresAuth: false }
  },

  // Comparison pages
  {
    path: '/compare/todoist',
    name: 'CompareTodoist',
    component: () => import('@/views/landing/compare/TodoistPage.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/compare/ticktick',
    name: 'CompareTickTick',
    component: () => import('@/views/landing/compare/TickTickPage.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/compare/anydo',
    name: 'CompareAnydo',
    component: () => import('@/views/landing/compare/AnydoPage.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/compare/google-keep',
    name: 'CompareGoogleKeep',
    component: () => import('@/views/landing/compare/GoogleKeepPage.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/compare/things3',
    name: 'CompareThings3',
    component: () => import('@/views/landing/compare/Things3Page.vue'),
    meta: { requiresAuth: false }
  },

  // Alternatives pages
  {
    path: '/alternatives/todoist',
    name: 'AlternativesTodoist',
    component: () => import('@/views/landing/alternatives/TodoistAlternativesPage.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/alternatives/google-keep',
    name: 'AlternativesGoogleKeep',
    component: () => import('@/views/landing/alternatives/GoogleKeepAlternativesPage.vue'),
    meta: { requiresAuth: false }
  },

  // About page
  {
    path: '/about',
    name: 'About',
    component: () => import('@/views/landing/AboutPage.vue'),
    meta: { requiresAuth: false }
  },

  // App pages
  {
    path: ROUTES.HOME,
    name: 'Home',
    component: () => import('@/views/HomeView.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: ROUTES.LOGIN,
    name: 'Login',
    component: () => import('@/views/LoginView.vue'),
    meta: { requiresAuth: false, guestOnly: true }
  },
  {
    path: ROUTES.REGISTER,
    name: 'Register',
    component: () => import('@/views/RegisterView.vue'),
    meta: { requiresAuth: false, guestOnly: true }
  },
  {
    path: ROUTES.DASHBOARD,
    name: 'Dashboard',
    component: () => import('@/views/TasksDashboardView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/calendar',
    name: 'Calendar',
    component: () => import('@/views/CalendarView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('@/views/ProfileView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/analytics',
    name: 'Analytics',
    component: () => import('@/views/AnalyticsView.vue'),
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, _from, savedPosition) {
    // Если есть сохранённая позиция (кнопка назад/вперёд) - возвращаемся к ней
    if (savedPosition) {
      return savedPosition
    }
    // Если есть hash (якорь) - скроллим к нему
    if (to.hash) {
      return { el: to.hash, behavior: 'smooth' }
    }
    // Иначе скроллим наверх
    return { top: 0, behavior: 'smooth' }
  }
})

// Navigation guards
router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore()
  
  // Инициализируем auth при первой загрузке (SSR-safe)
  if (typeof localStorage !== 'undefined' && !authStore.accessToken && localStorage.getItem('access_token')) {
    await authStore.initializeAuth()
  }

  const requiresAuth = to.meta.requiresAuth as boolean
  const guestOnly = to.meta.guestOnly as boolean
  const isAuthenticated = authStore.isAuthenticated

  if (requiresAuth && !isAuthenticated) {
    // Требуется авторизация, но пользователь не залогинен
    next({ name: 'Login', query: { redirect: to.fullPath } })
  } else if (guestOnly && isAuthenticated) {
    // Страница только для гостей, но пользователь залогинен
    next({ name: 'Dashboard' })
  } else {
    next()
  }
})

export default router

