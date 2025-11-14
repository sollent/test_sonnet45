# Руководство по устранению неполадок - Полные решения

## Содержание
1. [Решенные проблемы](#решенные-проблемы)
2. [Проблемы с Docker](#проблемы-с-docker)
3. [Проблемы с базой данных](#проблемы-с-базой-данных)
4. [Проблемы Frontend](#проблемы-frontend)
5. [Проблемы Backend](#проблемы-backend)
6. [Проблемы производительности](#проблемы-производительности)
7. [Проблемы безопасности](#проблемы-безопасности)

---

## Решенные проблемы

Это критические проблемы, с которыми столкнулись во время разработки и которые были успешно решены. Решения проверены в боевых условиях и готовы к использованию в продакшене.

---

### 1. Ошибки CORS

**Проблема:**
```
Access to XMLHttpRequest at 'http://localhost:8089/api/tasks' from origin
'http://localhost:3000' has been blocked by CORS policy: No
'Access-Control-Allow-Origin' header is present on the requested resource.
```

**Симптомы:**
- API-запросы работают в Postman, но не работают в браузере
- В консоли отображаются ошибки политики CORS
- Preflight OPTIONS запросы завершаются неудачей
- Данные ответа не видны во вкладке Network

**Корневая причина:**

Конфигурация CORS была отключена с помощью `paths: '^/': null` в `nelmio_cors.yaml`:

```yaml
# НЕРАБОТАЮЩАЯ КОНФИГУРАЦИЯ
nelmio_cors:
    paths:
        '^/': null  # Это ПОЛНОСТЬЮ ОТКЛЮЧАЕТ CORS!
```

**Решение:**

**Файл:** `/backend/config/packages/nelmio_cors.yaml`

```yaml
# РАБОЧАЯ КОНФИГУРАЦИЯ
nelmio_cors:
    defaults:
        origin_regex: true
        # Production: Только разрешенные домены (НЕ *)
        # Development: localhost для локальной разработки
        # Production: task.nesty.by для боевого сервера
        allow_origin:
            - '^https?://localhost(:[0-9]+)?$'  # Local dev
            - '^https://task\.nesty\.by$'        # Production frontend
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization', 'X-Requested-With', 'Cache-Control']
        expose_headers: ['Link', 'Content-Length', 'Content-Range']
        allow_credentials: true
        max_age: 3600
    paths:
        '^/api':
            allow_origin:
                - '^https?://localhost(:[0-9]+)?$'
                - '^https://task\.nesty\.by$'
            allow_headers: ['*']
            allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
            allow_headers: ['Content-Type', 'Authorization']
            max_age: 3600
```

**Пошаговое исправление:**

1. Отредактируйте `/backend/config/packages/nelmio_cors.yaml`
2. Замените всё содержимое на рабочую конфигурацию выше
3. Пересоберите Docker-контейнеры:
   ```bash
   docker-compose down
   docker-compose up -d --build
   ```
4. Проверьте в консоли браузера:
   ```javascript
   fetch('http://localhost:8089/api/tasks', {
     headers: { 'Authorization': 'Bearer YOUR_TOKEN' }
   }).then(r => r.json()).then(console.log)
   ```

**Предотвращение:**
- Никогда не используйте `paths: '^/': null` - это полностью отключает CORS
- Всегда указывайте явные пути типа `'^/api'`
- Тестируйте API-вызовы из frontend перед деплоем
- Используйте вкладку Network в DevTools браузера для проверки CORS-заголовков

**Проверка:**

Убедитесь, что ответ включает эти заголовки:
```http
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, OPTIONS, POST, PUT, PATCH, DELETE
Access-Control-Allow-Headers: Content-Type, Authorization
```

---

### 2. Сдвиг дат (проблема с временными зонами)

**Проблема:**
```javascript
// Пользователь выбирает: 2025-01-15
// Backend получает: 2025-01-14T23:00:00Z
// База данных хранит: 2025-01-14
// Frontend отображает: 2025-01-14 (неправильно!)
```

**Симптомы:**
- Даты сдвигаются назад на 1 день
- Задачи появляются на неправильный день в календаре
- Сроки выполнения на день раньше, чем были выбраны
- Ошибки, связанные с временными зонами, в консоли браузера

**Корневая причина:**

JavaScript `Date.toISOString()` конвертирует в UTC, но пользователи работают в локальной временной зоне:

```javascript
// НЕРАБОТАЮЩИЙ КОД
const date = new Date('2025-01-15'); // Локальная полночь
date.toISOString(); // "2025-01-14T23:00:00.000Z" (UTC, сдвинуто!)
```

**Решение:**

Создайте утилитарную функцию, которая сохраняет локальную временную зону:

**Файл:** `/frontend/src/utils/dateUtils.ts`

```typescript
/**
 * Форматирование даты для API (сохраняет локальную временную зону)
 *
 * ПРОБЛЕМА: toISOString() конвертирует в UTC, сдвигая даты
 * РЕШЕНИЕ: Форматируем вручную с использованием локальной временной зоны
 *
 * @param date - Дата для форматирования
 * @returns ISO 8601 строка в локальной временной зоне (например, "2025-01-15T00:00:00+03:00")
 */
export function formatDateForApi(date: Date | null): string | null {
  if (!date) return null;

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  const seconds = String(date.getSeconds()).padStart(2, '0');

  // Получаем смещение временной зоны в формате "+03:00" или "-05:00"
  const tzOffset = -date.getTimezoneOffset();
  const tzHours = String(Math.floor(Math.abs(tzOffset) / 60)).padStart(2, '0');
  const tzMinutes = String(Math.abs(tzOffset) % 60).padStart(2, '0');
  const tzSign = tzOffset >= 0 ? '+' : '-';

  return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}${tzSign}${tzHours}:${tzMinutes}`;
}

/**
 * Форматирование даты как YYYY-MM-DD (для date pickers)
 */
export function formatDateOnly(date: Date | null): string | null {
  if (!date) return null;

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}
```

**Использование:**

```typescript
// ДО (НЕРАБОТАЕТ)
const task = {
  dueDate: new Date('2025-01-15').toISOString() // "2025-01-14T23:00:00.000Z" ❌
};

// ПОСЛЕ (РАБОТАЕТ)
import { formatDateForApi } from '@/utils/dateUtils';

const task = {
  dueDate: formatDateForApi(new Date('2025-01-15')) // "2025-01-15T00:00:00+03:00" ✅
};
```

**Пошаговое исправление:**

1. Создайте функцию `formatDateForApi()` в dateUtils.ts
2. Найдите все вызовы `.toISOString()` в кодовой базе:
   ```bash
   grep -r "toISOString()" frontend/src/
   ```
3. Замените на `formatDateForApi()`:
   ```typescript
   // Создание/обновление задачи
   dueDate: formatDateForApi(formData.dueDate)

   // Фильтры по дате
   dateFrom: formatDateOnly(filters.dateFrom)
   ```
4. Протестируйте выбор даты:
   - Выберите 2025-01-15 в date picker
   - Проверьте payload сетевого запроса
   - Убедитесь, что backend получает 2025-01-15, а не 2025-01-14

**Предотвращение:**
- Никогда не используйте `.toISOString()` для пользовательских дат
- Всегда используйте форматирование с учетом временной зоны
- Тестируйте с разными временными зонами (UTC, EST, PST, JST)
- Документируйте обработку временных зон в документации API

**Проверка:**

```typescript
// Тест в консоли браузера
import { formatDateForApi } from '@/utils/dateUtils';

const date = new Date('2025-01-15');
console.log('toISOString():', date.toISOString());
// "2025-01-14T23:00:00.000Z" ❌

console.log('formatDateForApi():', formatDateForApi(date));
// "2025-01-15T00:00:00+03:00" ✅
```

---

### 3. Мигание UI при обновлениях

**Проблема:**

Каждый раз, когда задача обновляется (изменение заголовка, переключение завершения), весь список задач перезагружается, вызывая:
- Видимое мигание/вспышка
- Прыжок позиции прокрутки наверх
- Кратковременное появление спиннера загрузки
- Плохой пользовательский опыт

**Симптомы:**
- UI ощущается медленным
- Задачи исчезают и появляются снова
- Анимации перезапускаются
- Состояние сбрасывается (развернутые элементы сворачиваются)

**Корневая причина:**

После каждой мутации задачи приложение получало весь список задач с API:

```typescript
// ДО (вызывает мигание UI)
const updateTask = async (taskId: number, updates: Partial<Task>) => {
  await api.put(`/tasks/${taskId}`, updates);

  // Это получает ВСЕ задачи снова, заменяя весь список
  await fetchTasks(); // ❌ Вызывает перезагрузку UI
};
```

**Решение:**

Используйте **точечные обновления** - обновляйте только конкретную задачу в локальном состоянии без повторного получения:

**Файл:** `/frontend/src/stores/taskStore.ts`

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
     * Обновление задачи в локальном состоянии (точечное обновление - без повторного получения)
     */
    updateTaskInState(taskId: number, updates: Partial<Task>) {
      const index = this.tasks.findIndex(t => t.id === taskId);
      if (index !== -1) {
        // Объединяем обновления с существующей задачей
        this.tasks[index] = { ...this.tasks[index], ...updates };
      }
    },

    /**
     * Обновление задачи на backend и в локальном состоянии
     */
    async updateTask(taskId: number, updates: Partial<Task>) {
      try {
        // 1. Отправляем обновление на backend
        const { data } = await api.put(`/tasks/${taskId}`, updates);

        // 2. Обновляем локальное состояние ответом (без повторного получения!)
        this.updateTaskInState(taskId, data);

        return data;
      } catch (error) {
        console.error('Не удалось обновить задачу:', error);
        throw error;
      }
    },

    /**
     * Переключение завершения задачи
     */
    async toggleTaskCompletion(taskId: number) {
      try {
        const { data } = await api.post(`/tasks/${taskId}/toggle`);

        // Обновляем локальное состояние (без повторного получения!)
        this.updateTaskInState(taskId, data);

        return data;
      } catch (error) {
        console.error('Не удалось переключить задачу:', error);
        throw error;
      }
    },

    /**
     * Получение всех задач (вызывать только при монтировании или явном обновлении)
     */
    async fetchTasks(filters?: TaskFilters) {
      this.loading = true;
      try {
        const { data } = await api.get('/tasks', { params: filters });
        this.tasks = data; // Заменяем весь список
      } finally {
        this.loading = false;
      }
    },
  },
});
```

**Использование в компонентах:**

```vue
<script setup lang="ts">
import { useTaskStore } from '@/stores/taskStore';

const taskStore = useTaskStore();

// ДО (вызывает мигание)
const handleComplete = async (taskId: number) => {
  await api.post(`/tasks/${taskId}/complete`);
  await taskStore.fetchTasks(); // ❌ Получает все задачи снова
};

// ПОСЛЕ (плавное обновление)
const handleComplete = async (taskId: number) => {
  await taskStore.toggleTaskCompletion(taskId); // ✅ Обновляет только эту задачу
};
</script>
```

**Пошаговое исправление:**

1. Добавьте метод `updateTaskInState()` в store
2. Замените вызовы `fetchTasks()` после мутаций на точечные обновления
3. Используйте ответ backend для обновления локального состояния
4. Вызывайте `fetchTasks()` только при:
   - Монтировании компонента
   - Нажатии кнопки ручного обновления
   - Навигации между представлениями

**Предотвращение:**
- Реализуйте оптимистичные обновления (обновляйте UI перед API-вызовом)
- Используйте WebSocket для обновлений в реальном времени
- Применяйте debounce для быстрых мутаций
- Показывайте тонкие индикаторы загрузки (не полную перезагрузку списка)

**Проверка:**

```typescript
// Тест в консоли браузера
const taskStore = useTaskStore();

// Отслеживаем перезагрузки списка
let reloadCount = 0;
taskStore.$subscribe((mutation, state) => {
  console.log('Store обновлен:', ++reloadCount);
});

// Переключаем завершение задачи
await taskStore.toggleTaskCompletion(123);
// Должно залогировать только 1 обновление, а не 2 (обновление + повторное получение)
```

---

### 4. Подзадачи не обновляются

**Проблема:**

Когда подзадача завершается, `completionProgress` родительской задачи не обновляется в UI. Требуется обновление для просмотра изменений.

**Симптомы:**
- Прогресс-бар показывает устаревшее значение
- `subtaskCount` и `completedSubtaskCount` не меняются
- UI родительской задачи не реагирует на изменения подзадач

**Корневая причина:**

Реактивность Vue 3 не отслеживает глубокие мутации вложенных объектов:

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

// Это не вызывает реактивность!
task.value.subtasks[0].isCompleted = true; // ❌ Не реактивно
task.value.completionProgress = 50; // ❌ Не реактивно
</script>
```

**Решение:**

Создайте composable, который обрабатывает обновления подзадач с правильной реактивностью:

**Файл:** `/frontend/src/composables/useTaskCompletion.ts`

```typescript
import { ref, computed } from 'vue';
import type { Task } from '@/types/task';
import api from '@/services/api';

export function useTaskCompletion(task: Ref<Task>) {
  const isUpdating = ref(false);

  /**
   * Переключение завершения подзадачи с реактивностью
   */
  const toggleSubtask = async (subtaskId: number) => {
    isUpdating.value = true;

    try {
      // 1. Вызов API
      const { data } = await api.post(`/tasks/${subtaskId}/toggle`);

      // 2. Находим подзадачу в массиве
      const subtaskIndex = task.value.subtasks.findIndex(st => st.id === subtaskId);

      if (subtaskIndex !== -1) {
        // 3. Создаем НОВЫЙ объект подзадачи (вызывает реактивность)
        task.value.subtasks[subtaskIndex] = { ...data };

        // 4. Пересчитываем прогресс родительской задачи
        const completedCount = task.value.subtasks.filter(st => st.isCompleted).length;
        const totalCount = task.value.subtasks.length;

        // 5. Обновляем родительскую задачу (создаем НОВЫЙ объект для вызова реактивности)
        task.value = {
          ...task.value,
          completedSubtaskCount: completedCount,
          completionProgress: totalCount > 0 ? (completedCount / totalCount) * 100 : 0
        };
      }

      return data;
    } catch (error) {
      console.error('Не удалось переключить подзадачу:', error);
      throw error;
    } finally {
      isUpdating.value = false;
    }
  };

  /**
   * Вычисляемый процент прогресса
   */
  const progressPercentage = computed(() => {
    return Math.round(task.value.completionProgress);
  });

  /**
   * Вычисляемая метка прогресса
   */
  const progressLabel = computed(() => {
    const completed = task.value.completedSubtaskCount;
    const total = task.value.subtaskCount;
    return `${completed}/${total} выполнено`;
  });

  return {
    toggleSubtask,
    isUpdating,
    progressPercentage,
    progressLabel,
  };
}
```

**Использование:**

```vue
<template>
  <div class="task-card">
    <h3>{{ task.title }}</h3>

    <!-- Прогресс-бар -->
    <div class="progress">
      <div
        class="progress-bar"
        :style="{ width: progressPercentage + '%' }"
      ></div>
      <span>{{ progressLabel }}</span>
    </div>

    <!-- Подзадачи -->
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

// Делаем задачу реактивной
const task = ref(props.task);

// Используем composable
const { toggleSubtask, isUpdating, progressPercentage, progressLabel } = useTaskCompletion(task);
</script>
```

**Пошаговое исправление:**

1. Создайте composable `useTaskCompletion`
2. Замените прямые мутации свойств на методы composable
3. Всегда создавайте НОВЫЕ объекты при обновлении (оператор spread)
4. Используйте computed свойства для производных значений
5. Тестируйте реактивность в Vue DevTools

**Предотвращение:**
- Используйте composables для сложных обновлений состояния
- Никогда не изменяйте вложенные объекты напрямую
- Всегда создавайте новые объекты/массивы при обновлении
- Используйте Vue DevTools для проверки реактивности

**Проверка:**

```typescript
// Тест в Vue DevTools
const task = ref({
  id: 1,
  subtasks: [{ id: 2, isCompleted: false }],
  completionProgress: 0
});

// Переключаем подзадачу
await toggleSubtask(2);

// Проверяем, что эти значения обновились:
console.log(task.value.completionProgress); // Должно быть 100
console.log(task.value.completedSubtaskCount); // Должно быть 1
```

---

## Проблемы с Docker

### Контейнер не запускается

**Ошибка:**
```
ERROR: for ultra_backend  Cannot start service backend: driver failed programming external connectivity
Error starting userland proxy: listen tcp4 0.0.0.0:8089: bind: address already in use
```

**Диагностика:**
```bash
# Найти процесс, использующий порт 8089
lsof -i :8089
# или
netstat -tulpn | grep 8089

# Вывод показывает:
php-fpm   12345  user    8u  IPv4  0x123456789  0t0  TCP *:8089 (LISTEN)
```

**Решение:**

```bash
# Вариант 1: Убить процесс
kill -9 12345

# Вариант 2: Изменить порт в docker-compose.yml
services:
  backend:
    ports:
      - "8090:80"  # Изменено с 8089 на 8090

# Перезапустить контейнеры
docker-compose down
docker-compose up -d
```

**Предотвращение:**
- Используйте уникальные порты для каждого проекта
- Документируйте использование портов в README
- Добавьте проверку портов в скрипт запуска

---

### Конфликты портов

**Распространенные порты:**
- 8089 - Backend Nginx
- 15432 - PostgreSQL
- 3000 - Frontend Dev (Vite)
- 8080 - Frontend Prod (Nginx)
- 9009 - PHP-FPM
- 5672 - RabbitMQ
- 15672 - RabbitMQ Management

**Проверка всех портов:**
```bash
# macOS/Linux
lsof -i :8089 -i :3000 -i :8080 -i :15432

# Windows
netstat -ano | findstr "8089 3000 8080 15432"
```

**Исправление конфликтов портов:**

Отредактируйте `.env.docker`:
```bash
# Backend
NGINX_PORT=8090           # Изменено с 8089
POSTGRES_PORT=15433       # Изменено с 15432

# Frontend
FRONTEND_DEV_PORT=3001    # Изменено с 3000
FRONTEND_PROD_PORT=8081   # Изменено с 8080
```

Перезапустите контейнеры:
```bash
docker-compose down
docker-compose up -d
```

Не забудьте обновить `VITE_API_BASE_URL` если изменили `NGINX_PORT`!

---

### Ошибки прав доступа

**Ошибка:**
```
ERROR: for ultra_backend  Cannot start service backend:
OCI runtime create failed: container_linux.go:380: starting container process caused:
process_linux.go:545: container init caused: rootfs_linux.go:76: mounting "/var/www"
to rootfs at "/var/www" caused: mkdir /var/lib/docker/overlay2/abc123/merged/var/www:
permission denied
```

**Решение:**

```bash
# Исправить владельца
sudo chown -R $USER:$USER backend/
sudo chown -R $USER:$USER frontend/

# Исправить права
sudo chmod -R 755 backend/var/
sudo chmod -R 777 backend/var/log/

# Пересобрать
docker-compose down
docker-compose up -d --build
```

---

### Проблемы монтирования томов

**Ошибка:**
```
ERROR: for ultra_backend  Cannot create container for service backend:
failed to mount local volume: mount /path/to/backend:/var/www:ro, flags: 0x1000:
no such file or directory
```

**Решение:**

```bash
# Создать отсутствующие директории
mkdir -p backend/var/log
mkdir -p backend/public/uploads

# Исправить пути в docker-compose.yml
services:
  backend:
    volumes:
      - ./backend:/var/www  # Используйте относительные пути
      - ./backend/var:/var/www/var  # Смонтировать директорию var

# Перезапустить
docker-compose up -d
```

---

### Frontend контейнер не запускается

**Ошибка:**
```
ERROR: for frontend-dev  Cannot start service frontend: driver failed programming external connectivity
Error starting userland proxy: listen tcp4 0.0.0.0:3000: bind: address already in use
```

**Диагностика:**
```bash
# Проверить занятые порты
lsof -i :3000
lsof -i :8080

# Проверить логи контейнера
docker logs frontend-dev
# или
docker logs frontend-prod
```

**Решение:**

```bash
# Вариант 1: Остановить процесс на порту
lsof -ti :3000 | xargs kill -9

# Вариант 2: Изменить порт в .env.docker
FRONTEND_DEV_PORT=3001
FRONTEND_PROD_PORT=8081

# Перезапустить
docker-compose down
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose.frontend-dev.yml up -d
```

---

### HMR (Hot Module Replacement) не работает в Docker

**Проблема:** Изменения в коде не отражаются в браузере, требуется ручное обновление

**Симптомы:**
- Изменения файлов не вызывают перезагрузку браузера
- В консоли нет сообщений HMR
- Vite не отслеживает изменения файлов

**Корневая причина:**

Docker volume mount имеет проблемы с file watching на некоторых системах (особенно macOS)

**Решение:**

**1. Проверьте конфигурацию Vite (`apps/frontend/vite.config.ts`):**

```typescript
export default defineConfig({
  server: {
    host: '0.0.0.0',  // ✅ Обязательно для Docker!
    port: 3000,
    watch: {
      usePolling: true,  // ✅ Включить polling для Docker
      interval: 1000,    // Проверка каждую секунду
    },
    hmr: {
      host: 'localhost',  // ✅ Для доступа с хоста
    }
  }
});
```

**2. Проверьте volume mount в docker-compose:**

```yaml
# infrastructure/docker/docker-compose.frontend-dev.yml
services:
  frontend:
    volumes:
      - ../../apps/frontend:/app       # ✅ Монтирует весь код
      - /app/node_modules              # ✅ Anonymous volume!
```

**Важно:** Anonymous volume `/app/node_modules` предотвращает перезапись контейнерных node_modules хостовыми файлами!

**3. Перезапустите с чистой сборкой:**

```bash
docker-compose down
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose.frontend-dev.yml up -d --build
```

**Проверка:**

```bash
# Откройте браузер на http://localhost:3000
# Измените файл apps/frontend/src/App.vue
# Должно произойти автоматическое обновление через 1-2 секунды
```

---

### node_modules конфликт между хостом и контейнером

**Проблема:** После запуска Docker контейнера локальный `npm run dev` не работает

**Ошибка:**
```bash
# На хосте (macOS/Windows)
cd apps/frontend
npm run dev

# Ошибка:
Error: Cannot find module '@rollup/rollup-darwin-arm64'
# или
Error: The module was compiled against a different Node.js version
```

**Корневая причина:**

Docker контейнер (Linux) создает node_modules для Linux, которые несовместимы с хостовой системой (macOS/Windows)

**Решение:**

```bash
# Удалить node_modules на хосте
rm -rf apps/frontend/node_modules

# Переустановить для вашей платформы
cd apps/frontend
npm install

# Теперь локальный dev будет работать
npm run dev

# Docker dev будет использовать свои node_modules (anonymous volume!)
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose.frontend-dev.yml up -d
```

**Предотвращение:**

Добавьте `node_modules` в `.dockerignore`:

```bash
# apps/frontend/.dockerignore
node_modules
dist
.git
.env.local
```

Это предотвращает копирование хостовых node_modules в контейнер!

---

### Production build fails: Cannot find module '@rollup/rollup-linux-x64-musl'

**Ошибка:**
```
ERROR [builder 5/6] RUN npm run build
#0 12.45 Error: Cannot find module '@rollup/rollup-linux-x64-musl'
#0 12.45 Require stack:
#0 12.45 - /app/node_modules/rollup/dist/native.js
```

**Корневая причина:**

Использование `npm ci --only=production` в Dockerfile.prod, что исключает devDependencies. Но `vite`, `rollup`, `typescript` и другие инструменты сборки находятся в devDependencies!

**Решение:**

Исправьте `apps/frontend/Dockerfile.prod`:

```dockerfile
# ❌ НЕПРАВИЛЬНО
RUN npm ci --only=production --prefer-offline

# ✅ ПРАВИЛЬНО
RUN npm ci --prefer-offline

# В multi-stage build это безопасно:
# - Builder stage нужны ВСЕ зависимости для сборки
# - Production stage (nginx) вообще не содержит node_modules
# - Финальный образ содержит только dist/ + nginx (~57MB)
```

**Полный Dockerfile.prod:**

```dockerfile
FROM node:20-alpine AS builder
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --prefer-offline  # ✅ Устанавливает ВСЕ зависимости
COPY . .
RUN npm run build
RUN test -d dist || (echo "ERROR: dist/ not created!" && exit 1)

FROM nginx:alpine
COPY --from=builder /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
```

**Проверка:**

```bash
# Собрать production образ
docker build -f apps/frontend/Dockerfile.prod -t frontend-prod ./apps/frontend

# Должно завершиться успешно
# Проверить размер
docker images | grep frontend-prod
# frontend-prod    latest    abc123    2 minutes ago    56.9MB ✅
```

---

### Nginx 404 для всех маршрутов кроме /

**Проблема:** Production frontend работает на `/`, но `/tasks`, `/calendar` возвращают 404

**Симптомы:**
- Главная страница загружается
- Навигация по ссылкам работает
- Прямой доступ к URL (F5) возвращает 404
- Nginx возвращает стандартную страницу ошибки 404

**Корневая причина:**

Vue Router использует HTML5 History mode, который требует серверной конфигурации для fallback на `index.html`

**Решение:**

Проверьте `apps/frontend/nginx.conf`:

```nginx
server {
    listen 80;
    server_name localhost;
    root /usr/share/nginx/html;
    index index.html;

    # ✅ КРИТИЧЕСКИ ВАЖНО для SPA!
    location / {
        try_files $uri $uri/ /index.html;
        add_header Cache-Control "no-cache, no-store, must-revalidate";
    }

    # Proxy API к backend
    location /api {
        proxy_pass http://backend-nginx:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

**Ключевой момент:** `try_files $uri $uri/ /index.html;`

Это означает:
1. Попытаться найти файл (например `/tasks`)
2. Попытаться найти директорию
3. Если не найдено - вернуть `/index.html` (где Vue Router обработает маршрут)

**Пересоберите production контейнер:**

```bash
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml down

docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml up -d --build
```

**Проверка:**

```bash
# Откройте http://localhost:8080/tasks
# Должна загрузиться страница задач (не 404!)

# Проверьте логи nginx
docker logs frontend-prod

# Должны видеть:
# "GET /tasks HTTP/1.1" 200  (не 404!)
```

---

## Проблемы с базой данных

### Ошибки миграций

**Ошибка:**
```
An exception occurred while executing a query: SQLSTATE[42P01]:
Undefined table: 7 ERROR: relation "task" does not exist
```

**Решение:**

```bash
# Проверить статус миграций
docker exec -it ultra_backend php bin/console doctrine:migrations:status

# Запустить миграции
docker exec -it ultra_backend php bin/console doctrine:migrations:migrate

# Если миграции не удаются, сбросить базу данных
docker exec -it ultra_backend php bin/console doctrine:database:drop --force
docker exec -it ultra_backend php bin/console doctrine:database:create
docker exec -it ultra_backend php bin/console doctrine:migrations:migrate
```

---

### Пул соединений исчерпан

**Ошибка:**
```
SQLSTATE[08006] [7] FATAL: sorry, too many clients already
Connection pool exhausted
```

**Диагностика:**
```bash
# Подключиться к PostgreSQL
docker exec -it ultra_postgres psql -U postgres

# Проверить активные соединения
SELECT count(*) FROM pg_stat_activity;

# Показать максимум соединений
SHOW max_connections;
```

**Решение:**

Отредактируйте `docker-compose.yml`:
```yaml
services:
  postgres:
    image: postgres:15-alpine
    command: postgres -c max_connections=200
    #                    ↑ Увеличено с дефолтных 100
```

Отредактируйте `backend/config/packages/doctrine.yaml`:
```yaml
doctrine:
    dbal:
        connections:
            default:
                options:
                    # Ограничить соединения на worker
                    max_connections: 10
```

---

### Медленные запросы (проблема N+1)

**Ошибка:** API-ответы занимают 2-5 секунд

**Диагностика:**

Включите логирование запросов в `doctrine.yaml`:
```yaml
doctrine:
    dbal:
        logging: true
        profiling: true
```

Проверьте логи:
```bash
docker exec -it ultra_backend tail -f var/log/dev.log | grep "SELECT"

# Вывод показывает повторяющиеся запросы:
SELECT * FROM task WHERE id = 1
SELECT * FROM task WHERE id = 2
SELECT * FROM task WHERE id = 3
# ... 500 запросов! (проблема N+1)
```

**Решение:**

Используйте `JOIN` запросы в репозиториях:

```php
// ДО (проблема N+1)
public function findActiveTasks(User $user): array
{
    $tasks = $this->createQueryBuilder('t')
        ->where('t.user = :user')
        ->setParameter('user', $user)
        ->getQuery()
        ->getResult();

    // Каждая задача вызывает отдельный запрос для тегов!
    foreach ($tasks as $task) {
        $task->getTags(); // Дополнительный запрос
    }

    return $tasks;
}

// ПОСЛЕ (Один запрос с JOIN)
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

### Взаимоблокировки (Deadlocks)

**Ошибка:**
```
SQLSTATE[40P01]: Deadlock detected: 7 ERROR: deadlock detected
DETAIL: Process 12345 waits for ShareLock on transaction 67890
```

**Решение:**

1. Используйте последовательный порядок блокировок
2. Держите транзакции короткими
3. Используйте оптимистичную блокировку

```php
// Добавьте поле version в entity
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Task
{
    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version;
}

// Doctrine будет проверять версию при обновлении
try {
    $task->setTitle('Updated');
    $this->entityManager->flush();
} catch (OptimisticLockException $e) {
    // Обработка одновременной модификации
    throw new ConflictException('Задача была изменена другим пользователем');
}
```

---

## Проблемы Frontend

### Ошибки типов в строгом режиме

**Ошибка:**
```typescript
TS2345: Argument of type 'number | null' is not assignable to parameter of type 'number'.
  Type 'null' is not assignable to type 'number'.
```

**Решение:**

Используйте type guards:

```typescript
// ДО (ошибка типов)
const updateTask = (taskId: number | null) => {
  api.put(`/tasks/${taskId}`, data); // ❌ taskId может быть null
};

// ПОСЛЕ (type safe)
const updateTask = (taskId: number | null) => {
  if (!taskId) {
    console.error('Task ID обязателен');
    return;
  }

  api.put(`/tasks/${taskId}`, data); // ✅ taskId это number
};

// Или используйте non-null assertion (когда уверены, что не null)
const updateTask = (taskId: number | null) => {
  api.put(`/tasks/${taskId!}`, data); // ⚠️ Используйте с осторожностью
};
```

---

### Pinia Store не реактивен

**Проблема:** Обновления store не вызывают повторный рендеринг компонентов

**Решение:**

```typescript
// НЕПРАВИЛЬНО - Прямая мутация не вызывает реактивность
const taskStore = useTaskStore();
taskStore.tasks[0].title = 'Updated'; // ❌

// ПРАВИЛЬНО - Используйте actions
const taskStore = useTaskStore();
taskStore.updateTask(taskId, { title: 'Updated' }); // ✅

// Или используйте $patch для массовых обновлений
taskStore.$patch({
  tasks: [...taskStore.tasks] // Создать новый массив
});

// Или используйте $state (заменяет всё состояние)
taskStore.$state = {
  ...taskStore.$state,
  tasks: updatedTasks
};
```

---

### API-вызовы не работают

**Ошибка:**
```
TypeError: Cannot read property 'data' of undefined
Network Error
CORS Error
```

**Диагностика:**

```typescript
// Включить логирование Axios interceptor
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

**Распространенные исправления:**

1. **CORS** - Проверьте конфигурацию CORS на backend (см. [Ошибки CORS](#1-ошибки-cors))
2. **Auth** - Проверьте JWT токен в localStorage
3. **Base URL** - Проверьте файл `.env`:
   ```env
   VITE_API_BASE_URL=http://localhost:8089/api
   ```
4. **Network** - Убедитесь, что backend контейнер запущен:
   ```bash
   docker ps | grep backend
   curl http://localhost:8089/api/tasks
   ```

---

### Бесконечный цикл обновления токена

**Проблема:** Приложение продолжает обновлять токен в цикле

**Диагностика:**

```javascript
// Проверить localStorage
localStorage.getItem('token');
localStorage.getItem('refreshToken');

// Проверить, истекли ли токены
function isTokenExpired(token) {
  const decoded = JSON.parse(atob(token.split('.')[1]));
  return decoded.exp * 1000 < Date.now();
}

console.log('Access token истек:', isTokenExpired(accessToken));
console.log('Refresh token истек:', isTokenExpired(refreshToken));
```

**Решение:**

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
        // Ждать завершения обновления
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

        // Уведомить подписчиков
        refreshSubscribers.forEach(callback => callback(data.token));
        refreshSubscribers = [];

        isRefreshing = false;

        // Повторить исходный запрос
        originalRequest.headers.Authorization = `Bearer ${data.token}`;
        return api(originalRequest);
      } catch (refreshError) {
        isRefreshing = false;
        // Разлогинить пользователя
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

## Проблемы производительности

### Медленная начальная загрузка

**Проблема:** Приложение загружается 5-10 секунд

**Диагностика:**

```bash
# Проверить размер бандла
npm run build
# Искать предупреждения о больших чанках

# Анализировать бандл
npm install -D rollup-plugin-visualizer
```

Добавьте в `vite.config.ts`:
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

**Решение:**

1. **Code splitting:**
   ```typescript
   // Используйте динамические импорты
   const Dashboard = () => import('./views/Dashboard.vue');
   const Tasks = () => import('./views/Tasks.vue');

   const routes = [
     { path: '/dashboard', component: Dashboard },
     { path: '/tasks', component: Tasks }
   ];
   ```

2. **Ленивая загрузка тяжелых библиотек:**
   ```typescript
   // ДО
   import Chart from 'chart.js';

   // ПОСЛЕ
   const loadChart = async () => {
     const { Chart } = await import('chart.js');
     return Chart;
   };
   ```

3. **Tree shaking:**
   ```typescript
   // ДО - Импортирует весь lodash (70KB)
   import _ from 'lodash';

   // ПОСЛЕ - Импортирует только необходимое (5KB)
   import debounce from 'lodash/debounce';
   import throttle from 'lodash/throttle';
   ```

---

### Утечки памяти

**Проблема:** Вкладка браузера использует 500MB+ памяти

**Диагностика:**

Используйте Chrome DevTools:
1. Откройте DevTools → Performance → Memory
2. Сделайте снимок кучи
3. Выполните действия
4. Сделайте еще один снимок
5. Сравните снимки

**Распространенные причины:**

1. **Event listeners не удалены:**
   ```typescript
   // НЕПРАВИЛЬНО
   onMounted(() => {
     window.addEventListener('resize', handleResize);
   });

   // ПРАВИЛЬНО
   onMounted(() => {
     window.addEventListener('resize', handleResize);
   });

   onUnmounted(() => {
     window.removeEventListener('resize', handleResize);
   });
   ```

2. **Таймеры не очищены:**
   ```typescript
   // НЕПРАВИЛЬНО
   const interval = setInterval(() => {
     fetchData();
   }, 5000);

   // ПРАВИЛЬНО
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

3. **Большие объекты в замыканиях:**
   ```typescript
   // НЕПРАВИЛЬНО - Держит весь массив в памяти
   const largeArray = new Array(1000000);
   const getFirst = () => largeArray[0];

   // ПРАВИЛЬНО - Держит только необходимое
   const firstItem = largeArray[0];
   const getFirst = () => firstItem;
   ```

---

## Проблемы безопасности

### XSS уязвимости

**Проблема:** Пользовательский ввод не санитизирован

**Решение:**

```vue
<!-- НЕПРАВИЛЬНО - Рендерит сырой HTML -->
<div v-html="task.description"></div>

<!-- ПРАВИЛЬНО - Экранирует HTML -->
<div>{{ task.description }}</div>

<!-- Если нужен HTML, санитизируйте его -->
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

### JWT токен в URL

**Проблема:** Токен раскрывается в истории браузера

**Решение:**

```typescript
// НЕПРАВИЛЬНО - Токен в query string
router.push(`/dashboard?token=${token}`);

// ПРАВИЛЬНО - Токен в localStorage
localStorage.setItem('token', token);
router.push('/dashboard');
```

---

## Заключение

Это руководство по устранению неполадок охватывает все основные проблемы, с которыми столкнулись во время разработки. Для проблем, не перечисленных здесь:

1. Проверьте логи Docker: `docker-compose logs -f`
2. Проверьте консоль браузера
3. Проверьте логи backend: `backend/var/log/dev.log`
4. Включите режим отладки в `.env`: `APP_DEBUG=true`
5. Используйте Xdebug для отладки PHP
6. Используйте Vue DevTools для отладки frontend

**Помните:**
- Всегда сначала проверяйте логи
- Ищите сообщения об ошибках в Google/Stack Overflow
- Тестируйте изолированно (очистите данные, проверьте конфигурацию)
- Документируйте новые проблемы для будущего
