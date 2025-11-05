# Troubleshooting Guide - Complete Solutions

## Table of Contents
1. [Solved Issues](#solved-issues)
2. [Docker Issues](#docker-issues)
3. [Database Issues](#database-issues)
4. [Frontend Issues](#frontend-issues)
5. [Backend Issues](#backend-issues)
6. [Performance Issues](#performance-issues)
7. [Security Issues](#security-issues)

---

## Solved Issues

These are critical issues that were encountered and solved during development. Solutions are battle-tested and production-ready.

---

### 1. CORS Errors

**Problem:**
```
Access to XMLHttpRequest at 'http://localhost:8089/api/tasks' from origin
'http://localhost:3000' has been blocked by CORS policy: No
'Access-Control-Allow-Origin' header is present on the requested resource.
```

**Symptoms:**
- API calls work in Postman but fail in browser
- Console shows CORS policy errors
- Preflight OPTIONS requests fail
- No response data visible in Network tab

**Root Cause:**

The CORS configuration was disabled with `paths: '^/': null` in `nelmio_cors.yaml`:

```yaml
# BROKEN CONFIGURATION
nelmio_cors:
    paths:
        '^/': null  # This completely DISABLES CORS!
```

**Solution:**

**File:** `/backend/config/packages/nelmio_cors.yaml`

```yaml
# WORKING CONFIGURATION
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['*']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
        expose_headers: ['Link']
        max_age: 3600
    paths:
        '^/api':
            allow_origin: ['*']
            allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
            allow_headers: ['Content-Type', 'Authorization']
            max_age: 3600
```

**Step-by-Step Fix:**

1. Edit `/backend/config/packages/nelmio_cors.yaml`
2. Replace entire content with working configuration above
3. Rebuild Docker containers:
   ```bash
   docker-compose down
   docker-compose up -d --build
   ```
4. Test in browser console:
   ```javascript
   fetch('http://localhost:8089/api/tasks', {
     headers: { 'Authorization': 'Bearer YOUR_TOKEN' }
   }).then(r => r.json()).then(console.log)
   ```

**Prevention:**
- Never use `paths: '^/': null` - it disables CORS entirely
- Always specify explicit paths like `'^/api'`
- Test API calls from frontend before deployment
- Use browser DevTools Network tab to inspect CORS headers

**Verification:**

Check that response includes these headers:
```http
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, OPTIONS, POST, PUT, PATCH, DELETE
Access-Control-Allow-Headers: Content-Type, Authorization
```

---

### 2. Date Shifting (Timezone Issue)

**Problem:**
```javascript
// User selects: 2025-01-15
// Backend receives: 2025-01-14T23:00:00Z
// Database stores: 2025-01-14
// Frontend displays: 2025-01-14 (wrong!)
```

**Symptoms:**
- Dates shift backward by 1 day
- Tasks appear on wrong day in calendar
- Due dates are one day earlier than selected
- Timezone-related errors in browser console

**Root Cause:**

JavaScript `Date.toISOString()` converts to UTC, but users work in local timezone:

```javascript
// BROKEN CODE
const date = new Date('2025-01-15'); // Local midnight
date.toISOString(); // "2025-01-14T23:00:00.000Z" (UTC, shifted!)
```

**Solution:**

Create utility function that preserves local timezone:

**File:** `/frontend/src/utils/dateUtils.ts`

```typescript
/**
 * Format date for API (preserves local timezone)
 *
 * PROBLEM: toISOString() converts to UTC, shifting dates
 * SOLUTION: Manually format using local timezone
 *
 * @param date - Date to format
 * @returns ISO 8601 string in local timezone (e.g., "2025-01-15T00:00:00+03:00")
 */
export function formatDateForApi(date: Date | null): string | null {
  if (!date) return null;

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  const seconds = String(date.getSeconds()).padStart(2, '0');

  // Get timezone offset in format "+03:00" or "-05:00"
  const tzOffset = -date.getTimezoneOffset();
  const tzHours = String(Math.floor(Math.abs(tzOffset) / 60)).padStart(2, '0');
  const tzMinutes = String(Math.abs(tzOffset) % 60).padStart(2, '0');
  const tzSign = tzOffset >= 0 ? '+' : '-';

  return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}${tzSign}${tzHours}:${tzMinutes}`;
}

/**
 * Format date as YYYY-MM-DD (for date pickers)
 */
export function formatDateOnly(date: Date | null): string | null {
  if (!date) return null;

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}
```

**Usage:**

```typescript
// BEFORE (BROKEN)
const task = {
  dueDate: new Date('2025-01-15').toISOString() // "2025-01-14T23:00:00.000Z" ❌
};

// AFTER (WORKING)
import { formatDateForApi } from '@/utils/dateUtils';

const task = {
  dueDate: formatDateForApi(new Date('2025-01-15')) // "2025-01-15T00:00:00+03:00" ✅
};
```

**Step-by-Step Fix:**

1. Create `formatDateForApi()` function in dateUtils.ts
2. Find all `.toISOString()` calls in codebase:
   ```bash
   grep -r "toISOString()" frontend/src/
   ```
3. Replace with `formatDateForApi()`:
   ```typescript
   // Task creation/update
   dueDate: formatDateForApi(formData.dueDate)

   // Date filters
   dateFrom: formatDateOnly(filters.dateFrom)
   ```
4. Test date selection:
   - Select 2025-01-15 in date picker
   - Check network request payload
   - Verify backend receives 2025-01-15, not 2025-01-14

**Prevention:**
- Never use `.toISOString()` for user-facing dates
- Always use timezone-aware formatting
- Test with different timezones (UTC, EST, PST, JST)
- Document timezone handling in API docs

**Verification:**

```typescript
// Test in browser console
import { formatDateForApi } from '@/utils/dateUtils';

const date = new Date('2025-01-15');
console.log('toISOString():', date.toISOString());
// "2025-01-14T23:00:00.000Z" ❌

console.log('formatDateForApi():', formatDateForApi(date));
// "2025-01-15T00:00:00+03:00" ✅
```

---

### 3. UI Blinking on Updates

**Problem:**

Every time a task is updated (title change, completion toggle), the entire task list reloads, causing:
- Visible flicker/blink
- Scroll position jumps to top
- Loading spinner appears briefly
- Poor user experience

**Symptoms:**
- UI feels sluggish
- Tasks disappear and reappear
- Animations restart
- State resets (expanded items collapse)

**Root Cause:**

After every task mutation, the app fetched the entire task list from the API:

```typescript
// BEFORE (causes UI blinking)
const updateTask = async (taskId: number, updates: Partial<Task>) => {
  await api.put(`/tasks/${taskId}`, updates);

  // This fetches ALL tasks again, replacing entire list
  await fetchTasks(); // ❌ Causes UI to reload
};
```

**Solution:**

Use **point updates** - update only the specific task in the local state without refetching:

**File:** `/frontend/src/stores/taskStore.ts`

```typescript
import { defineStore } from 'pinia';
import type { Task } from '@/types/task';
import api from '@/services/api';

export const useTaskStore = defineStore('tasks', {
  state: () => ({
    tasks: [] as Task[],
    loading: false,
  }),

  actions: {
    /**
     * Update task in local state (point update - no refetch)
     */
    updateTaskInState(taskId: number, updates: Partial<Task>) {
      const index = this.tasks.findIndex(t => t.id === taskId);
      if (index !== -1) {
        // Merge updates into existing task
        this.tasks[index] = { ...this.tasks[index], ...updates };
      }
    },

    /**
     * Update task on backend and in local state
     */
    async updateTask(taskId: number, updates: Partial<Task>) {
      try {
        // 1. Send update to backend
        const { data } = await api.put(`/tasks/${taskId}`, updates);

        // 2. Update local state with response (no refetch!)
        this.updateTaskInState(taskId, data);

        return data;
      } catch (error) {
        console.error('Failed to update task:', error);
        throw error;
      }
    },

    /**
     * Toggle task completion
     */
    async toggleTaskCompletion(taskId: number) {
      try {
        const { data } = await api.post(`/tasks/${taskId}/toggle`);

        // Update local state (no refetch!)
        this.updateTaskInState(taskId, data);

        return data;
      } catch (error) {
        console.error('Failed to toggle task:', error);
        throw error;
      }
    },

    /**
     * Fetch all tasks (only call on mount or explicit refresh)
     */
    async fetchTasks(filters?: TaskFilters) {
      this.loading = true;
      try {
        const { data } = await api.get('/tasks', { params: filters });
        this.tasks = data; // Replace entire list
      } finally {
        this.loading = false;
      }
    },
  },
});
```

**Usage in Components:**

```vue
<script setup lang="ts">
import { useTaskStore } from '@/stores/taskStore';

const taskStore = useTaskStore();

// BEFORE (causes blinking)
const handleComplete = async (taskId: number) => {
  await api.post(`/tasks/${taskId}/complete`);
  await taskStore.fetchTasks(); // ❌ Refetches all tasks
};

// AFTER (smooth update)
const handleComplete = async (taskId: number) => {
  await taskStore.toggleTaskCompletion(taskId); // ✅ Updates only this task
};
</script>
```

**Step-by-Step Fix:**

1. Add `updateTaskInState()` method to store
2. Replace `fetchTasks()` calls after mutations with point updates
3. Use backend response to update local state
4. Only call `fetchTasks()` on:
   - Component mount
   - Manual refresh button
   - Navigation between views

**Prevention:**
- Implement optimistic updates (update UI before API call)
- Use WebSocket for real-time updates
- Debounce rapid mutations
- Show subtle loading indicators (not full list reload)

**Verification:**

```typescript
// Test in browser console
const taskStore = useTaskStore();

// Watch for list reloads
let reloadCount = 0;
taskStore.$subscribe((mutation, state) => {
  console.log('Store updated:', ++reloadCount);
});

// Toggle task completion
await taskStore.toggleTaskCompletion(123);
// Should log only 1 update, not 2 (update + refetch)
```

---

### 4. Subtasks Not Updating

**Problem:**

When a subtask is completed, the parent task's `completionProgress` doesn't update in the UI. Refresh is required to see changes.

**Symptoms:**
- Progress bar shows outdated value
- `subtaskCount` and `completedSubtaskCount` don't change
- Parent task UI doesn't react to subtask changes

**Root Cause:**

Vue 3 reactivity doesn't track nested object mutations deeply:

```vue
<script setup lang="ts">
import { ref } from 'vue';

const task = ref({
  id: 1,
  subtasks: [
    { id: 2, isCompleted: false }
  ],
  completionProgress: 0
});

// This doesn't trigger reactivity!
task.value.subtasks[0].isCompleted = true; // ❌ Not reactive
task.value.completionProgress = 50; // ❌ Not reactive
</script>
```

**Solution:**

Create composable that handles subtask updates with proper reactivity:

**File:** `/frontend/src/composables/useTaskCompletion.ts`

```typescript
import { ref, computed } from 'vue';
import type { Task } from '@/types/task';
import api from '@/services/api';

export function useTaskCompletion(task: Ref<Task>) {
  const isUpdating = ref(false);

  /**
   * Toggle subtask completion with reactivity
   */
  const toggleSubtask = async (subtaskId: number) => {
    isUpdating.value = true;

    try {
      // 1. Call API
      const { data } = await api.post(`/tasks/${subtaskId}/toggle`);

      // 2. Find subtask in array
      const subtaskIndex = task.value.subtasks.findIndex(st => st.id === subtaskId);

      if (subtaskIndex !== -1) {
        // 3. Create NEW subtask object (triggers reactivity)
        task.value.subtasks[subtaskIndex] = { ...data };

        // 4. Recalculate parent task progress
        const completedCount = task.value.subtasks.filter(st => st.isCompleted).length;
        const totalCount = task.value.subtasks.length;

        // 5. Update parent task (create NEW object to trigger reactivity)
        task.value = {
          ...task.value,
          completedSubtaskCount: completedCount,
          completionProgress: totalCount > 0 ? (completedCount / totalCount) * 100 : 0
        };
      }

      return data;
    } catch (error) {
      console.error('Failed to toggle subtask:', error);
      throw error;
    } finally {
      isUpdating.value = false;
    }
  };

  /**
   * Computed progress percentage
   */
  const progressPercentage = computed(() => {
    return Math.round(task.value.completionProgress);
  });

  /**
   * Computed progress label
   */
  const progressLabel = computed(() => {
    const completed = task.value.completedSubtaskCount;
    const total = task.value.subtaskCount;
    return `${completed}/${total} completed`;
  });

  return {
    toggleSubtask,
    isUpdating,
    progressPercentage,
    progressLabel,
  };
}
```

**Usage:**

```vue
<template>
  <div class="task-card">
    <h3>{{ task.title }}</h3>

    <!-- Progress bar -->
    <div class="progress">
      <div
        class="progress-bar"
        :style="{ width: progressPercentage + '%' }"
      ></div>
      <span>{{ progressLabel }}</span>
    </div>

    <!-- Subtasks -->
    <div class="subtasks">
      <div
        v-for="subtask in task.subtasks"
        :key="subtask.id"
        class="subtask"
      >
        <input
          type="checkbox"
          :checked="subtask.isCompleted"
          :disabled="isUpdating"
          @change="toggleSubtask(subtask.id)"
        />
        <span>{{ subtask.title }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useTaskCompletion } from '@/composables/useTaskCompletion';
import type { Task } from '@/types/task';

const props = defineProps<{
  task: Task
}>();

// Make task reactive
const task = ref(props.task);

// Use composable
const { toggleSubtask, isUpdating, progressPercentage, progressLabel } = useTaskCompletion(task);
</script>
```

**Step-by-Step Fix:**

1. Create `useTaskCompletion` composable
2. Replace direct property mutations with composable methods
3. Always create NEW objects when updating (spread operator)
4. Use computed properties for derived values
5. Test reactivity in Vue DevTools

**Prevention:**
- Use composables for complex state updates
- Never mutate nested objects directly
- Always create new objects/arrays when updating
- Use Vue DevTools to verify reactivity

**Verification:**

```typescript
// Test in Vue DevTools
const task = ref({
  id: 1,
  subtasks: [{ id: 2, isCompleted: false }],
  completionProgress: 0
});

// Toggle subtask
await toggleSubtask(2);

// Check that these updated:
console.log(task.value.completionProgress); // Should be 100
console.log(task.value.completedSubtaskCount); // Should be 1
```

---

## Docker Issues

### Container Won't Start

**Error:**
```
ERROR: for ultra_backend  Cannot start service backend: driver failed programming external connectivity
Error starting userland proxy: listen tcp4 0.0.0.0:8089: bind: address already in use
```

**Diagnosis:**
```bash
# Find process using port 8089
lsof -i :8089
# or
netstat -tulpn | grep 8089

# Output shows:
php-fpm   12345  user    8u  IPv4  0x123456789  0t0  TCP *:8089 (LISTEN)
```

**Solution:**

```bash
# Option 1: Kill process
kill -9 12345

# Option 2: Change port in docker-compose.yml
services:
  backend:
    ports:
      - "8090:80"  # Changed from 8089 to 8090

# Restart containers
docker-compose down
docker-compose up -d
```

**Prevention:**
- Use unique ports for each project
- Document port usage in README
- Add port check to startup script

---

### Port Conflicts

**Common Ports:**
- 8089 - Backend (PHP-FPM)
- 3000 - Frontend (Vite)
- 5432 - PostgreSQL

**Check All Ports:**
```bash
# macOS/Linux
lsof -i :8089 -i :3000 -i :5432

# Windows
netstat -ano | findstr "8089 3000 5432"
```

**Fix Port Conflicts:**

Edit `docker-compose.yml`:
```yaml
services:
  backend:
    ports:
      - "8090:80"  # Changed from 8089

  frontend:
    ports:
      - "3001:3000"  # Changed from 3000

  postgres:
    ports:
      - "5433:5432"  # Changed from 5432
```

Don't forget to update `.env` files!

---

### Permission Errors

**Error:**
```
ERROR: for ultra_backend  Cannot start service backend:
OCI runtime create failed: container_linux.go:380: starting container process caused:
process_linux.go:545: container init caused: rootfs_linux.go:76: mounting "/var/www"
to rootfs at "/var/www" caused: mkdir /var/lib/docker/overlay2/abc123/merged/var/www:
permission denied
```

**Solution:**

```bash
# Fix ownership
sudo chown -R $USER:$USER backend/
sudo chown -R $USER:$USER frontend/

# Fix permissions
sudo chmod -R 755 backend/var/
sudo chmod -R 777 backend/var/log/

# Rebuild
docker-compose down
docker-compose up -d --build
```

---

### Volume Mounting Issues

**Error:**
```
ERROR: for ultra_backend  Cannot create container for service backend:
failed to mount local volume: mount /path/to/backend:/var/www:ro, flags: 0x1000:
no such file or directory
```

**Solution:**

```bash
# Create missing directories
mkdir -p backend/var/log
mkdir -p backend/public/uploads

# Fix docker-compose.yml paths
services:
  backend:
    volumes:
      - ./backend:/var/www  # Use relative paths
      - ./backend/var:/var/www/var  # Mount var directory

# Restart
docker-compose up -d
```

---

## Database Issues

### Migration Errors

**Error:**
```
An exception occurred while executing a query: SQLSTATE[42P01]:
Undefined table: 7 ERROR: relation "task" does not exist
```

**Solution:**

```bash
# Check migration status
docker exec -it ultra_backend php bin/console doctrine:migrations:status

# Run migrations
docker exec -it ultra_backend php bin/console doctrine:migrations:migrate

# If migrations fail, reset database
docker exec -it ultra_backend php bin/console doctrine:database:drop --force
docker exec -it ultra_backend php bin/console doctrine:database:create
docker exec -it ultra_backend php bin/console doctrine:migrations:migrate
```

---

### Connection Pool Exhausted

**Error:**
```
SQLSTATE[08006] [7] FATAL: sorry, too many clients already
Connection pool exhausted
```

**Diagnosis:**
```bash
# Connect to PostgreSQL
docker exec -it ultra_postgres psql -U postgres

# Check active connections
SELECT count(*) FROM pg_stat_activity;

# Show max connections
SHOW max_connections;
```

**Solution:**

Edit `docker-compose.yml`:
```yaml
services:
  postgres:
    image: postgres:15-alpine
    command: postgres -c max_connections=200
    #                    ↑ Increase from default 100
```

Edit `backend/config/packages/doctrine.yaml`:
```yaml
doctrine:
    dbal:
        connections:
            default:
                options:
                    # Limit connections per worker
                    max_connections: 10
```

---

### Slow Queries (N+1 Problem)

**Error:** API responses take 2-5 seconds

**Diagnosis:**

Enable query logging in `doctrine.yaml`:
```yaml
doctrine:
    dbal:
        logging: true
        profiling: true
```

Check logs:
```bash
docker exec -it ultra_backend tail -f var/log/dev.log | grep "SELECT"

# Output shows repeated queries:
SELECT * FROM task WHERE id = 1
SELECT * FROM task WHERE id = 2
SELECT * FROM task WHERE id = 3
# ... 500 queries! (N+1 problem)
```

**Solution:**

Use `JOIN` queries in repositories:

```php
// BEFORE (N+1 problem)
public function findActiveTasks(User $user): array
{
    $tasks = $this->createQueryBuilder('t')
        ->where('t.user = :user')
        ->setParameter('user', $user)
        ->getQuery()
        ->getResult();

    // Each task triggers separate query for tags!
    foreach ($tasks as $task) {
        $task->getTags(); // Extra query
    }

    return $tasks;
}

// AFTER (Single query with JOIN)
public function findActiveTasks(User $user): array
{
    return $this->createQueryBuilder('t')
        ->leftJoin('t.tags', 'tag')
        ->addSelect('tag')
        ->leftJoin('t.subtasks', 'subtask')
        ->addSelect('subtask')
        ->where('t.user = :user')
        ->andWhere('t.isArchived = false')
        ->setParameter('user', $user)
        ->getQuery()
        ->getResult();
}
```

---

### Deadlocks

**Error:**
```
SQLSTATE[40P01]: Deadlock detected: 7 ERROR: deadlock detected
DETAIL: Process 12345 waits for ShareLock on transaction 67890
```

**Solution:**

1. Use consistent lock order
2. Keep transactions short
3. Use optimistic locking

```php
// Add version field to entity
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Task
{
    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version;
}

// Doctrine will check version on update
try {
    $task->setTitle('Updated');
    $this->entityManager->flush();
} catch (OptimisticLockException $e) {
    // Handle concurrent modification
    throw new ConflictException('Task was modified by another user');
}
```

---

## Frontend Issues

### Type Errors in Strict Mode

**Error:**
```typescript
TS2345: Argument of type 'number | null' is not assignable to parameter of type 'number'.
  Type 'null' is not assignable to type 'number'.
```

**Solution:**

Use type guards:

```typescript
// BEFORE (type error)
const updateTask = (taskId: number | null) => {
  api.put(`/tasks/${taskId}`, data); // ❌ taskId might be null
};

// AFTER (type safe)
const updateTask = (taskId: number | null) => {
  if (!taskId) {
    console.error('Task ID is required');
    return;
  }

  api.put(`/tasks/${taskId}`, data); // ✅ taskId is number
};

// Or use non-null assertion (when you're sure it's not null)
const updateTask = (taskId: number | null) => {
  api.put(`/tasks/${taskId!}`, data); // ⚠️ Use with caution
};
```

---

### Pinia Store Not Reactive

**Problem:** Store updates don't trigger component re-renders

**Solution:**

```typescript
// WRONG - Direct mutation doesn't trigger reactivity
const taskStore = useTaskStore();
taskStore.tasks[0].title = 'Updated'; // ❌

// CORRECT - Use actions
const taskStore = useTaskStore();
taskStore.updateTask(taskId, { title: 'Updated' }); // ✅

// Or use $patch for bulk updates
taskStore.$patch({
  tasks: [...taskStore.tasks] // Create new array
});

// Or use $state (replaces entire state)
taskStore.$state = {
  ...taskStore.$state,
  tasks: updatedTasks
};
```

---

### API Calls Failing

**Error:**
```
TypeError: Cannot read property 'data' of undefined
Network Error
CORS Error
```

**Diagnosis:**

```typescript
// Enable Axios interceptor logging
api.interceptors.request.use(config => {
  console.log('Request:', config.method, config.url, config.data);
  return config;
});

api.interceptors.response.use(
  response => {
    console.log('Response:', response.status, response.data);
    return response;
  },
  error => {
    console.error('Error:', error.response?.status, error.message);
    return Promise.reject(error);
  }
);
```

**Common Fixes:**

1. **CORS** - Check backend CORS config (see [CORS Errors](#1-cors-errors))
2. **Auth** - Verify JWT token in localStorage
3. **Base URL** - Check `.env` file:
   ```env
   VITE_API_BASE_URL=http://localhost:8089/api
   ```
4. **Network** - Verify backend container is running:
   ```bash
   docker ps | grep backend
   curl http://localhost:8089/api/tasks
   ```

---

### Token Refresh Infinite Loop

**Problem:** App keeps refreshing token in loop

**Diagnosis:**

```javascript
// Check localStorage
localStorage.getItem('token');
localStorage.getItem('refreshToken');

// Check if tokens are expired
function isTokenExpired(token) {
  const decoded = JSON.parse(atob(token.split('.')[1]));
  return decoded.exp * 1000 < Date.now();
}

console.log('Access token expired:', isTokenExpired(accessToken));
console.log('Refresh token expired:', isTokenExpired(refreshToken));
```

**Solution:**

```typescript
// auth.ts
let isRefreshing = false;
let refreshSubscribers: ((token: string) => void)[] = [];

api.interceptors.response.use(
  response => response,
  async error => {
    const originalRequest = error.config;

    if (error.response?.status === 401 && !originalRequest._retry) {
      if (isRefreshing) {
        // Wait for refresh to complete
        return new Promise(resolve => {
          refreshSubscribers.push((token: string) => {
            originalRequest.headers.Authorization = `Bearer ${token}`;
            resolve(api(originalRequest));
          });
        });
      }

      originalRequest._retry = true;
      isRefreshing = true;

      try {
        const { data } = await api.post('/token/refresh', {
          refreshToken: localStorage.getItem('refreshToken')
        });

        localStorage.setItem('token', data.token);
        localStorage.setItem('refreshToken', data.refreshToken);

        // Notify subscribers
        refreshSubscribers.forEach(callback => callback(data.token));
        refreshSubscribers = [];

        isRefreshing = false;

        // Retry original request
        originalRequest.headers.Authorization = `Bearer ${data.token}`;
        return api(originalRequest);
      } catch (refreshError) {
        isRefreshing = false;
        // Logout user
        localStorage.removeItem('token');
        localStorage.removeItem('refreshToken');
        window.location.href = '/login';
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  }
);
```

---

## Performance Issues

### Slow Initial Load

**Problem:** App takes 5-10 seconds to load

**Diagnosis:**

```bash
# Check bundle size
npm run build
# Look for warnings about large chunks

# Analyze bundle
npm install -D rollup-plugin-visualizer
```

Add to `vite.config.ts`:
```typescript
import { visualizer } from 'rollup-plugin-visualizer';

export default defineConfig({
  plugins: [
    vue(),
    visualizer({
      filename: './dist/stats.html',
      open: true
    })
  ]
});
```

**Solution:**

1. **Code splitting:**
   ```typescript
   // Use dynamic imports
   const Dashboard = () => import('./views/Dashboard.vue');
   const Tasks = () => import('./views/Tasks.vue');

   const routes = [
     { path: '/dashboard', component: Dashboard },
     { path: '/tasks', component: Tasks }
   ];
   ```

2. **Lazy load heavy libraries:**
   ```typescript
   // BEFORE
   import Chart from 'chart.js';

   // AFTER
   const loadChart = async () => {
     const { Chart } = await import('chart.js');
     return Chart;
   };
   ```

3. **Tree shaking:**
   ```typescript
   // BEFORE - Imports entire lodash (70KB)
   import _ from 'lodash';

   // AFTER - Imports only what you need (5KB)
   import debounce from 'lodash/debounce';
   import throttle from 'lodash/throttle';
   ```

---

### Memory Leaks

**Problem:** Browser tab uses 500MB+ memory

**Diagnosis:**

Use Chrome DevTools:
1. Open DevTools → Performance → Memory
2. Take heap snapshot
3. Perform actions
4. Take another snapshot
5. Compare snapshots

**Common Causes:**

1. **Event listeners not removed:**
   ```typescript
   // WRONG
   onMounted(() => {
     window.addEventListener('resize', handleResize);
   });

   // CORRECT
   onMounted(() => {
     window.addEventListener('resize', handleResize);
   });

   onUnmounted(() => {
     window.removeEventListener('resize', handleResize);
   });
   ```

2. **Timers not cleared:**
   ```typescript
   // WRONG
   const interval = setInterval(() => {
     fetchData();
   }, 5000);

   // CORRECT
   let interval: number;

   onMounted(() => {
     interval = setInterval(() => {
       fetchData();
     }, 5000);
   });

   onUnmounted(() => {
     clearInterval(interval);
   });
   ```

3. **Large objects in closures:**
   ```typescript
   // WRONG - Keeps entire array in memory
   const largeArray = new Array(1000000);
   const getFirst = () => largeArray[0];

   // CORRECT - Only keeps what's needed
   const firstItem = largeArray[0];
   const getFirst = () => firstItem;
   ```

---

## Security Issues

### XSS Vulnerabilities

**Problem:** User input not sanitized

**Solution:**

```vue
<!-- WRONG - Renders raw HTML -->
<div v-html="task.description"></div>

<!-- CORRECT - Escapes HTML -->
<div>{{ task.description }}</div>

<!-- If you need HTML, sanitize it -->
<template>
  <div v-html="sanitizedDescription"></div>
</template>

<script setup>
import DOMPurify from 'dompurify';

const sanitizedDescription = computed(() => {
  return DOMPurify.sanitize(task.description);
});
</script>
```

---

### JWT Token in URL

**Problem:** Token exposed in browser history

**Solution:**

```typescript
// WRONG - Token in query string
router.push(`/dashboard?token=${token}`);

// CORRECT - Token in localStorage
localStorage.setItem('token', token);
router.push('/dashboard');
```

---

## Conclusion

This troubleshooting guide covers all major issues encountered during development. For issues not listed here:

1. Check Docker logs: `docker-compose logs -f`
2. Check browser console
3. Check backend logs: `backend/var/log/dev.log`
4. Enable debug mode in `.env`: `APP_DEBUG=true`
5. Use Xdebug for PHP debugging
6. Use Vue DevTools for frontend debugging

**Remember:**
- Always check logs first
- Search error messages on Google/Stack Overflow
- Test in isolation (clear data, verify configuration)
- Document new issues for future reference
