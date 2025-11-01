# 🔧 Troubleshooting Guide - Решение проблем

> Полное руководство по диагностике и решению типичных проблем в проекте

---

## 📋 Содержание

1. [Frontend проблемы](#frontend-проблемы)
2. [Backend проблемы](#backend-проблемы)
3. [Docker проблемы](#docker-проблемы)
4. [База данных](#база-данных)
5. [Аутентификация](#аутентификация)
6. [API проблемы](#api-проблемы)
7. [UI/UX проблемы](#uiux-проблемы)
8. [Производительность](#производительность)

---

## Frontend проблемы

### ❌ Проблема: "npm run dev" не запускается

**Симптомы:**
```bash
Error: Cannot find module 'vite'
```

**Решение:**
```bash
cd frontend
rm -rf node_modules package-lock.json
npm install
npm run dev
```

**Причина:** Поврежденные или устаревшие зависимости

---

### ❌ Проблема: TypeScript ошибки компиляции

**Симптомы:**
```
Property 'xxx' does not exist on type 'yyy'
```

**Диагностика:**
```bash
npm run type-check
```

**Решение:**
1. Проверь типы в `types/*.types.ts`
2. Убедись что все props типизированы
3. Проверь импорты

**Пример исправления:**
```typescript
// ❌ Плохо
const props = defineProps({
  task: Object
})

// ✅ Хорошо
interface Props {
  task: Task
}
const props = defineProps<Props>()
```

---

### ❌ Проблема: "Задачи не отображаются"

**Диагностика:**
1. Открой DevTools → Network
2. Проверь запрос к `/api/tasks`
3. Проверь ответ сервера

**Возможные причины:**

#### 1. Токен не передается
```typescript
// Проверь authStore
console.log(authStore.token)  // Должен быть не null
```

**Решение:**
```typescript
// Перелогинься
authStore.logout()
router.push('/login')
```

#### 2. Фильтры слишком строгие
```typescript
// Проверь активные фильтры
console.log(filters.value)
```

**Решение:**
```typescript
// Сбрось фильтры
filters.value = {
  status: 'all',
  priority: 'all',
  tag: null
}
```

#### 3. Пустой ответ от API
```json
{
  "tasks": []
}
```

**Решение:**
- Создай тестовую задачу
- Проверь что задачи принадлежат текущему пользователю

---

### ❌ Проблема: "Задачи моргают при обновлении"

**Симптомы:**
- При клике на чекбокс список перезагружается
- Теряется позиция скролла
- Видно мигание

**Причина:** Полная перезагрузка списка вместо точечного обновления

**Решение:**
```typescript
// ❌ Плохо
async function handleUpdate() {
  await taskStore.updateTask(id, data)
  await taskStore.fetchTasks()  // Перезагрузка!
}

// ✅ Хорошо
function handleTaskUpdated(updatedTask: Task) {
  const idx = tasks.value.findIndex(t => t.id === updatedTask.id)
  if (idx !== -1) {
    tasks.value[idx] = updatedTask
  }
}
```

---

### ❌ Проблема: "Даты сдвигаются на день"

**Симптомы:**
- Задача на 31 октября отображается как 1 ноября
- В календаре задачи на неправильных датах

**Причина:** Конвертация в UTC через `toISOString()`

**Решение:**
```typescript
// ❌ Плохо
const dateStr = date.toISOString().split('T')[0]

// ✅ Хорошо
function formatDateForApi(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}
```

---

### ❌ Проблема: "Двойные borders на фокусе"

**Симптомы:**
- При фокусе на input/button видно 2 рамки
- Выглядит некрасиво

**Причина:** Конфликт стилей PrimeVue и кастомных стилей

**Решение:**
Уже исправлено в `frontend/src/assets/styles/main.css`:
```css
*:focus-visible {
  outline: none;
  box-shadow: 0 0 0 0.2rem var(--focus-ring-color);
}
```

Если проблема осталась:
```css
/* Добавь в компонент */
.your-element:focus {
  outline: none !important;
  box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.15) !important;
}
```

---

### ❌ Проблема: "Подзадачи не обновляются в реальном времени"

**Симптомы:**
- Клик на чекбокс подзадачи
- Визуально меняется, но потом возвращается

**Причина:** Не используется `useTaskCompletion` composable

**Решение:**
```typescript
// В TaskDetailsSidebar.vue
import { useTaskCompletion } from '@/composables/useTaskCompletion'

const { toggleTaskCompletion } = useTaskCompletion()

async function handleToggleSubtask(subtaskId: number) {
  const subtask = await taskStore.fetchTask(subtaskId)
  await toggleTaskCompletion(subtask, async () => {
    if (currentTask.value) {
      await taskStore.fetchTask(currentTask.value.id)
    }
    emit('task-updated')
  })
}
```

---

### ❌ Проблема: "Переводы не работают"

**Симптомы:**
```
{{ t('tasks.title') }} // Отображается как есть
```

**Диагностика:**
```typescript
import { useI18n } from 'vue-i18n'
const { t, locale } = useI18n()

console.log(locale.value)  // Проверь текущий язык
console.log(t('tasks.title'))  // Проверь перевод
```

**Решение:**
1. Проверь что ключ существует в `i18n/locales/ru.ts` и `en.ts`
2. Проверь импорт `useI18n`
3. Проверь что компонент внутри `<i18n-t>` провайдера

---

## Backend проблемы

### ❌ Проблема: "500 Internal Server Error"

**Диагностика:**
```bash
# Смотри логи
docker-compose logs -f php

# Или зайди в контейнер
docker-compose exec php bash
tail -f var/log/dev.log
```

**Частые причины:**

#### 1. Ошибка в коде PHP
```
PHP Fatal error: Uncaught Error: Class 'App\Service\TaskService' not found
```

**Решение:**
```bash
docker-compose exec php composer dump-autoload
docker-compose exec php bin/console cache:clear
```

#### 2. Ошибка базы данных
```
SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "task" does not exist
```

**Решение:**
```bash
docker-compose exec php bin/console doctrine:migrations:migrate
```

#### 3. Неправильная конфигурация
```
The service "App\Service\TaskService" has a dependency on a non-existent service
```

**Решение:**
- Проверь `services.yaml`
- Проверь что сервис помечен как `#[AsService]` или настроен autowiring

---

### ❌ Проблема: "JWT токен не генерируется"

**Симптомы:**
```json
{
  "code": 401,
  "message": "Invalid JWT Token"
}
```

**Диагностика:**
```bash
# Проверь наличие ключей
docker-compose exec php ls -la config/jwt/

# Должны быть:
# private.pem
# public.pem
```

**Решение:**
```bash
docker-compose exec php bin/console lexik:jwt:generate-keypair --overwrite
```

**Проверка:**
```bash
# Попробуй залогиниться
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password"}'
```

---

### ❌ Проблема: "CORS ошибка"

**Симптомы:**
```
Access to XMLHttpRequest at 'http://localhost:8000/api/tasks' from origin 'http://localhost:3000' 
has been blocked by CORS policy
```

**Решение:**
```yaml
# backend/config/packages/nelmio_cors.yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['^http://localhost:[0-9]+$']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
        expose_headers: ['Link']
        max_age: 3600
```

После изменения:
```bash
docker-compose exec php bin/console cache:clear
docker-compose restart
```

---

### ❌ Проблема: "Миграции не применяются"

**Симптомы:**
```
There are 3 new migrations to execute
```

**Решение:**
```bash
# Применить все миграции
docker-compose exec php bin/console doctrine:migrations:migrate

# Применить конкретную миграцию
docker-compose exec php bin/console doctrine:migrations:execute --up 'DoctrineMigrations\Version20251101000000'

# Откатить последнюю миграцию
docker-compose exec php bin/console doctrine:migrations:execute --down 'DoctrineMigrations\Version20251101000000'
```

**Если миграция сломана:**
```bash
# Отметь как выполненную без применения
docker-compose exec php bin/console doctrine:migrations:version --add 'DoctrineMigrations\Version20251101000000'
```

---

### ❌ Проблема: "Задачи не фильтруются по дате"

**Симптомы:**
- В календаре задачи отображаются на неправильных датах
- Задачи дублируются

**Причина:** Несоответствие логики фильтрации на frontend и backend

**Решение:**

**Backend:**
```php
// TaskRepository.php
public function findTasksByDateRange(User $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
{
    $qb = $this->createQueryBuilder('t')
        ->where('t.user = :user')
        ->andWhere('
            (t.startDate IS NOT NULL AND t.startDate BETWEEN :start AND :end) OR
            (t.dueDate IS NOT NULL AND t.dueDate BETWEEN :start AND :end) OR
            (t.startDate IS NOT NULL AND t.dueDate IS NOT NULL AND t.startDate <= :end AND t.dueDate >= :start)
        ')
        ->setParameter('user', $user)
        ->setParameter('start', $startDate)
        ->setParameter('end', $endDate);
    
    return $qb->getQuery()->getResult();
}
```

**Frontend:**
```typescript
// CalendarView.vue
function isTaskOnDate(task: Task, date: Date): boolean {
  const targetDate = normalizeDateValue(date)
  
  if (task.startDate) {
    const start = normalizeDateValue(task.startDate)
    if (isSameDay(start, targetDate)) return true
    
    if (task.dueDate) {
      const due = normalizeDateValue(task.dueDate)
      return targetDate >= start && targetDate <= due
    }
  }
  
  if (task.dueDate) {
    const due = normalizeDateValue(task.dueDate)
    if (isSameDay(due, targetDate)) return true
  }
  
  return false
}
```

---

## Docker проблемы

### ❌ Проблема: "docker-compose up не работает"

**Симптомы:**
```
ERROR: Couldn't connect to Docker daemon
```

**Решение:**
1. Убедись что Docker Desktop запущен
2. Проверь права:
```bash
sudo chmod 666 /var/run/docker.sock
```

---

### ❌ Проблема: "Контейнеры постоянно перезапускаются"

**Диагностика:**
```bash
docker-compose ps
docker-compose logs php
```

**Частые причины:**

#### 1. Ошибка в PHP коде
**Решение:** Исправь ошибку, контейнер перезапустится автоматически

#### 2. База данных не готова
**Решение:** Добавь `depends_on` в `docker-compose.yml`:
```yaml
php:
  depends_on:
    - db
```

#### 3. Нехватка памяти
**Решение:** Увеличь лимиты в Docker Desktop Settings

---

### ❌ Проблема: "Порт уже занят"

**Симптомы:**
```
ERROR: for php  Cannot start service php: Ports are not available: 
listen tcp 0.0.0.0:8000: bind: address already in use
```

**Диагностика:**
```bash
# Найди процесс на порту 8000
lsof -i :8000

# Или на Mac
sudo lsof -i :8000
```

**Решение:**
```bash
# Убей процесс
kill -9 <PID>

# Или измени порт в docker-compose.yml
ports:
  - "8001:8000"
```

---

## База данных

### ❌ Проблема: "База данных не подключается"

**Симптомы:**
```
SQLSTATE[08006] [7] could not connect to server: Connection refused
```

**Диагностика:**
```bash
# Проверь статус контейнера
docker-compose ps db

# Проверь логи
docker-compose logs db
```

**Решение:**
```bash
# Перезапусти контейнер БД
docker-compose restart db

# Или пересоздай
docker-compose down
docker-compose up -d
```

---

### ❌ Проблема: "Нужно сбросить базу данных"

**⚠️ ВНИМАНИЕ: Удалит все данные!**

```bash
# Остановить контейнеры
docker-compose down

# Удалить volumes (БД)
docker-compose down -v

# Запустить заново
docker-compose up -d

# Применить миграции
docker-compose exec php bin/console doctrine:migrations:migrate

# Загрузить фикстуры (опционально)
docker-compose exec php bin/console doctrine:fixtures:load
```

---

### ❌ Проблема: "Дублирующиеся задачи в календаре"

**Симптомы:**
- При переключении месяцев задачи дублируются
- Показывается больше точек чем задач

**Причина:** Задачи загружаются для предыдущего, текущего и следующего месяца и дублируются

**Решение:**
```typescript
// CalendarView.vue
async function fetchTasks() {
  // ...
  const [prevMonthTasks, currentMonthTasks, nextMonthTasks] = await Promise.all([...])
  
  // Дедупликация через Map
  const mergedTasks = new Map<number, Task>()
  ;[...prevMonthTasks, ...currentMonthTasks, ...nextMonthTasks].forEach(task => {
    mergedTasks.set(task.id, task)
  })
  monthTasks.value = Array.from(mergedTasks.values())
}
```

---

## Аутентификация

### ❌ Проблема: "401 Unauthorized на всех запросах"

**Диагностика:**
```typescript
// В DevTools Console
console.log(localStorage.getItem('token'))
```

**Возможные причины:**

#### 1. Токен истек
**Решение:**
```typescript
// Перелогинься
authStore.logout()
router.push('/login')
```

#### 2. Токен не передается в заголовках
**Проверь:**
```typescript
// frontend/src/services/api.client.ts
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})
```

#### 3. Неправильный формат токена
**Решение:**
```typescript
// Должен быть: "Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
// Проверь что нет лишних пробелов или кавычек
```

---

### ❌ Проблема: "Google OAuth не работает"

**Симптомы:**
```
Error: Invalid client ID
```

**Решение:**
1. Проверь `VITE_GOOGLE_CLIENT_ID` в `.env`
2. Убедись что домен добавлен в Google Console
3. Проверь что credentials не истекли

---

## API проблемы

### ❌ Проблема: "API возвращает 404"

**Диагностика:**
```bash
# Проверь доступные роуты
docker-compose exec php bin/console debug:router | grep task
```

**Решение:**
- Убедись что роут существует в `TaskController.php`
- Проверь что используется правильный HTTP метод (GET/POST/PUT/DELETE)
- Очисти кэш: `docker-compose exec php bin/console cache:clear`

---

### ❌ Проблема: "API возвращает 422 Validation Error"

**Симптомы:**
```json
{
  "code": 422,
  "message": "Validation Failed",
  "errors": {
    "title": "This value should not be blank."
  }
}
```

**Решение:**
1. Проверь что все обязательные поля заполнены
2. Проверь валидацию в DTO:
```php
#[Assert\NotBlank]
#[Assert\Length(min: 3, max: 255)]
private string $title;
```

---

## UI/UX проблемы

### ❌ Проблема: "Компонент не отображается"

**Диагностика:**
```vue
<script setup lang="ts">
// Добавь console.log
console.log('Component mounted', props)
</script>
```

**Частые причины:**

#### 1. Условный рендеринг
```vue
<!-- Проверь v-if -->
<TaskCard v-if="task" :task="task" />
```

#### 2. CSS скрывает элемент
```css
/* Проверь стили */
.hidden {
  display: none; /* Возможно случайно применился */
}
```

#### 3. Ошибка в шаблоне
```vue
<!-- Проверь DevTools Console на ошибки -->
```

---

### ❌ Проблема: "Анимации тормозят"

**Решение:**
```css
/* Используй transform вместо top/left */
/* ❌ Плохо */
.element {
  transition: top 0.3s;
  top: 100px;
}

/* ✅ Хорошо */
.element {
  transition: transform 0.3s;
  transform: translateY(100px);
}
```

---

### ❌ Проблема: "Скролл не работает на мобильных"

**Решение:**
```css
.scrollable {
  overflow-y: auto;
  -webkit-overflow-scrolling: touch; /* Для iOS */
}
```

---

## Производительность

### ❌ Проблема: "Приложение тормозит"

**Диагностика:**
```typescript
// Используй Vue DevTools Performance tab
// Или Chrome DevTools Performance
```

**Частые причины:**

#### 1. Слишком много рендеров
**Решение:**
```vue
<script setup lang="ts">
// Используй computed вместо methods в template
const filteredTasks = computed(() => 
  tasks.value.filter(t => t.isCompleted)
)
</script>
```

#### 2. Нет debounce для поиска
**Решение:**
```typescript
import { debounce } from 'lodash-es'

const debouncedSearch = debounce((query: string) => {
  searchTasks(query)
}, 300)
```

#### 3. Большие списки без виртуализации
**Решение:**
- Используй pagination
- Или virtual scrolling (vue-virtual-scroller)

---

### ❌ Проблема: "Много запросов к API"

**Диагностика:**
```
DevTools → Network → Фильтр XHR
```

**Решение:**
```typescript
// Кэшируй данные в store
const cachedTasks = ref<Task[]>([])
const lastFetch = ref<number>(0)

async function fetchTasks() {
  const now = Date.now()
  if (now - lastFetch.value < 60000) { // 1 минута
    return cachedTasks.value
  }
  
  const tasks = await api.getTasks()
  cachedTasks.value = tasks
  lastFetch.value = now
  return tasks
}
```

---

## 🆘 Последняя надежда

### Если ничего не помогает:

```bash
# 1. Полная перезагрузка backend
cd backend
docker-compose down -v
docker-compose up -d
docker-compose exec php composer install
docker-compose exec php bin/console doctrine:migrations:migrate
docker-compose exec php bin/console lexik:jwt:generate-keypair

# 2. Полная перезагрузка frontend
cd frontend
rm -rf node_modules package-lock.json dist
npm install
npm run dev

# 3. Очистка кэша браузера
# DevTools → Application → Clear storage → Clear site data

# 4. Проверка логов
docker-compose logs -f
```

---

## 📞 Получить помощь

### Чек-лист перед обращением за помощью:

- [ ] Проверил логи (backend и frontend)
- [ ] Проверил DevTools Console
- [ ] Проверил DevTools Network
- [ ] Попробовал перезапустить контейнеры
- [ ] Попробовал очистить кэш
- [ ] Прочитал этот troubleshooting guide
- [ ] Воспроизвел проблему на чистом аккаунте

### Информация для отчета о баге:

```markdown
**Описание проблемы:**
[Что происходит]

**Ожидаемое поведение:**
[Что должно происходить]

**Шаги для воспроизведения:**
1. 
2. 
3. 

**Окружение:**
- OS: [macOS/Windows/Linux]
- Browser: [Chrome/Firefox/Safari]
- Node version: [18.x]
- Docker version: [20.x]

**Логи:**
```
[Вставь логи]
```

**Скриншоты:**
[Приложи скриншоты если нужно]
```

---

**Последнее обновление**: 01.11.2025

