# Frontend - Vue.js 3 + TypeScript + PrimeVue

Modern, secure, and beautiful authentication frontend built with Vue.js 3, TypeScript, and PrimeVue.

## 🚀 Features

- ✅ **Vue.js 3** with Composition API (script setup)
- ✅ **TypeScript** in strict mode
- ✅ **PrimeVue** UI component library
- ✅ **Pinia** for state management
- ✅ **Vue Router** with navigation guards
- ✅ **Axios** for API communication
- ✅ **JWT Authentication** with automatic token refresh
- ✅ **Beautiful UI/UX** with smooth animations
- ✅ **Fully Responsive** design
- ✅ **Form Validation** with custom composables
- ✅ **Toast Notifications**

## 📦 Tech Stack

```json
{
  "framework": "Vue.js 3.4+",
  "language": "TypeScript 5.4+",
  "build": "Vite 5.1+",
  "ui": "PrimeVue 3.50+",
  "state": "Pinia 2.1+",
  "router": "Vue Router 4.3+",
  "http": "Axios 1.6+",
  "utils": "@vueuse/core 10.9+"
}
```

## 🏗️ Project Structure

```
frontend/
├── public/               # Static assets
├── src/
│   ├── assets/          # Styles, images
│   │   └── styles/
│   │       └── main.css # Global styles & CSS variables
│   ├── components/      # Reusable components
│   │   ├── forms/       # Form components
│   │   │   ├── LoginForm.vue
│   │   │   └── RegisterForm.vue
│   │   ├── layout/      # Layout components
│   │   │   └── AuthLayout.vue
│   │   └── ui/          # Base UI components
│   │       └── BaseButton.vue
│   ├── composables/     # Reusable composition functions
│   │   ├── useAuth.ts
│   │   ├── useToast.ts
│   │   └── useFormValidation.ts
│   ├── config/          # Configuration
│   │   └── constants.ts # App constants & API endpoints
│   ├── router/          # Vue Router configuration
│   │   └── index.ts
│   ├── services/        # API services
│   │   ├── api.service.ts    # Axios instance & interceptors
│   │   └── auth.service.ts   # Auth API calls
│   ├── stores/          # Pinia stores
│   │   └── auth.store.ts     # Authentication store
│   ├── types/           # TypeScript types
│   │   ├── auth.types.ts
│   │   └── api.types.ts
│   ├── views/           # Page components
│   │   ├── HomeView.vue
│   │   ├── LoginView.vue
│   │   ├── RegisterView.vue
│   │   ├── DashboardView.vue
│   │   └── ProfileView.vue
│   ├── App.vue          # Root component
│   └── main.ts          # Application entry point
├── index.html           # HTML template
├── vite.config.ts       # Vite configuration
├── tsconfig.json        # TypeScript configuration
└── package.json         # Dependencies
```

## 🎯 Architecture & Best Practices

### 1. **Composition API (script setup)**
All components use `<script setup lang="ts">` syntax for better TypeScript integration and cleaner code.

### 2. **TypeScript Strict Mode**
- Full type safety with strict mode enabled
- No `any` types - everything is properly typed
- Type guards for runtime checks

### 3. **Smart/Dumb Component Pattern**
- **Smart Components** (Views): Handle business logic, state management
- **Dumb Components** (UI): Receive props, emit events, purely presentational

### 4. **Composables for Reusability**
- `useAuth()` - Authentication logic
- `useToast()` - Toast notifications
- `useFormValidation()` - Form validation rules

### 5. **Service Layer**
- `api.service.ts` - Axios instance with interceptors
- `auth.service.ts` - Authentication API calls
- Separation of concerns: API logic separate from components

### 6. **State Management with Pinia**
- Composition API style stores
- Type-safe state, getters, and actions
- Persisted authentication state

### 7. **Route Protection**
- Navigation guards in router
- Automatic redirect for unauthenticated users
- Guest-only routes (login/register)

## 🚀 Getting Started

### Prerequisites

- Node.js 18+ 
- npm or yarn

### Installation

```bash
# Navigate to frontend directory
cd frontend

# Install dependencies
npm install

# Start development server
npm run dev
```

The app will be available at `http://localhost:3000`

### Available Scripts

```bash
npm run dev          # Start development server
npm run build        # Build for production
npm run preview      # Preview production build
npm run type-check   # Run TypeScript type checking
```

## 🔐 Authentication Flow

### Login
1. User enters email and password
2. Form validation with real-time feedback
3. API call to `/api/auth`
4. Store JWT token and refresh token
5. Fetch user data
6. Redirect to dashboard

### Registration
1. User enters email, password, and password confirmation
2. Client-side validation
3. API call to `/api/users`
4. Auto-login after successful registration
5. Redirect to dashboard

### Token Refresh
- Automatic token refresh on 401 responses
- Uses refresh token stored in localStorage
- Seamless user experience

### Logout
- Clears all stored tokens and user data
- Redirects to login page

## 🎨 Design System

### Color Palette

```css
/* Primary Colors (Blue) */
--primary-500: #0ea5e9
--primary-600: #0284c7
--primary-700: #0369a1

/* Secondary Colors (Purple) */
--secondary-600: #a855f7
--secondary-700: #9333ea

/* Neutral Colors */
--gray-50 to --gray-900

/* Semantic Colors */
--success: #10b981
--warning: #f59e0b
--error: #ef4444
```

### Typography

```css
/* Font Family */
font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', ...

/* Font Sizes */
Base: 16px (1rem)
Small: 0.875rem
Large: 1.125rem
Headings: 1.5rem - 3.5rem
```

### Spacing & Layout

```css
/* Spacing Scale */
0.25rem, 0.5rem, 0.75rem, 1rem, 1.5rem, 2rem, 3rem, 4rem

/* Border Radius */
Small: 8px
Medium: 12px
Large: 16px
XLarge: 20-24px
```

### Animations

```css
/* Transition Speeds */
--transition-fast: 150ms
--transition-base: 300ms
--transition-slow: 500ms

/* Keyframe Animations */
fadeIn, slideUp, slideDown, scaleIn
```

## 📱 Responsive Design

### Breakpoints

```css
Mobile: < 768px
Tablet: 768px - 1024px
Desktop: > 1024px
```

### Mobile-First Approach
- Base styles for mobile
- Progressive enhancement for larger screens
- Touch-friendly UI elements
- Optimized font sizes and spacing

## 🔧 API Integration

### Base URL Configuration

```typescript
// .env
VITE_API_BASE_URL=http://localhost:8089
```

### API Endpoints

```typescript
{
  AUTH: {
    LOGIN: '/api/auth',
    REGISTER: '/api/users',
    REFRESH: '/api/token/refresh',
    GOOGLE: '/api/auth/google'
  },
  USER: {
    ME: '/api/users/me'
  }
}
```

### Axios Interceptors

**Request Interceptor:**
- Automatically adds JWT token to Authorization header

**Response Interceptor:**
- Handles 401 errors
- Automatic token refresh
- Retry failed requests after refresh

## 🧪 Type Safety

### Strict TypeScript Configuration

```json
{
  "strict": true,
  "noUnusedLocals": true,
  "noUnusedParameters": true,
  "noFallthroughCasesInSwitch": true,
  "noUncheckedIndexedAccess": true
}
```

### Type Definitions

All API responses, request payloads, and component props are fully typed:

```typescript
interface LoginCredentials {
  email: string
  password: string
}

interface User {
  id: number
  email: string
  name?: string | null
  roles: string[]
  createdAt: string
  updatedAt: string
}
```

## 🎯 Performance Optimizations

- ✅ Code splitting with dynamic imports
- ✅ Lazy loading of route components
- ✅ Optimized bundle size
- ✅ Tree-shaking unused code
- ✅ CSS optimizations
- ✅ Image optimization ready

## 🌐 Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## 📝 Environment Variables

Create a `.env` file in the frontend directory:

```env
VITE_API_BASE_URL=http://localhost:8089
VITE_APP_TITLE=Auth App
```

## 🚢 Production Build

```bash
# Build for production
npm run build

# Preview production build locally
npm run preview
```

Build artifacts will be in the `dist/` directory.

## 🤝 Integration with Backend

This frontend is designed to work with the Symfony backend (see `/backend` directory).

### Backend must be running on:
- `http://localhost:8089`

### Available API endpoints:
- POST `/api/auth` - Login
- POST `/api/users` - Register
- POST `/api/token/refresh` - Refresh token
- GET `/api/users/me` - Get current user

## 📚 PrimeVue Components Used

- **InputText** - Text input
- **Password** - Password input with toggle
- **Button** - Styled via BaseButton wrapper
- **Card** - Content cards
- **Toast** - Notifications
- **Message** - Inline messages
- **Avatar** - User avatar
- **Divider** - Section divider

## 🎓 Learning Resources

- [Vue.js Documentation](https://vuejs.org/)
- [PrimeVue Documentation](https://primevue.org/)
- [Pinia Documentation](https://pinia.vuejs.org/)
- [Vue Router Documentation](https://router.vuejs.org/)
- [TypeScript Documentation](https://www.typescriptlang.org/)

## 📄 License

This is a template project for educational purposes.

---

**Built with ❤️ using Vue.js 3, TypeScript, and PrimeVue**

