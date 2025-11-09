import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { ROUTES } from '@/config/constants'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'Landing',
    component: () => import('@/views/LandingPage.vue'),
    meta: { requiresAuth: false }
  },
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
  routes
})

// Navigation guards
router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore()
  
  // Инициализируем auth при первой загрузке
  if (!authStore.accessToken && localStorage.getItem('access_token')) {
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

