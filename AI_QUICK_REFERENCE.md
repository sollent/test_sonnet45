# 🤖 AI Quick Reference - Быстрая шпаргалка для нейронок

> **Цель**: Максимально быстро ввести AI-модель в контекст проекта

---

## ⚡ TL;DR - Что это за проект?

**Task Manager** - SPA для управления задачами с подзадачами, календарем и тегами.

**Stack**: 
- Backend: Symfony 6.4 + PostgreSQL (в Docker)
- Frontend: Vue 3 + TypeScript + PrimeVue

**Главная фича**: Неограниченная вложенность подзадач + оптимистичные обновления UI

---

## 🎯 Ключевые принципы проекта

### 1. **ВСЕГДА используй оптимистичные обновления**
```typescript
// ✅ Правильно
function handleUpdate() {
  // 1. Обнови UI сразу
  localState.value = newValue
  
  // 2. Запрос в фоне
  try {
    await api.update()
  } catch {
    // 3. Откат при ошибке
    localState.value = oldValue
  }
}

// ❌ Неправильно
async function handleUpdate() {
  await api.update()  // Ждем ответ
  fetchData()         // Перезагружаем все
}
```

### 2. **НЕ перезагружай списки после обновления**
```typescript
// ✅ Правильно - точечное обновление
function updateTask(updatedTask: Task) {
  const idx = tasks.value.findIndex(t => t.id === updatedTask.id)
  if (idx !== -1) {
    tasks.value[idx] = updatedTask
  }
}

// ❌ Неправильно - полная перезагрузка
async function updateTask() {
  await api.update()
  await fetchAllTasks()  // Моргание UI!
}
```

### 3. **Mobile-first подход**
- Всегда проверяй адаптивность
- Border-radius: умеренные (8-12px)
- Большие кликабельные области
- Плавные анимации

### 4. **Backend всегда в Docker**
```bash
# ✅ Правильно
docker-compose exec php bin/console ...

# ❌ Неправильно
php bin/console ...  # Локально не работает!
```

---

## 📁 Где что находится

### Часто используемые файлы

#### Frontend
```
frontend/src/
├── views/
│   ├── TasksDashboardView.vue    # Главная страница
│   └── CalendarView.vue          # Календарь
├── components/tasks/
│   ├── TaskCard.vue              # Карточка задачи
│   ├── TaskDetailsSidebar.vue    # Детали задачи
│   └── TaskFilters.vue           # Фильтры
├── stores/
│   ├── task.store.ts             # State задач
│   └── auth.store.ts             # Аутентификация
├── composables/
│   └── useTaskCompletion.ts      # Логика завершения задач
└── assets/styles/
    └── main.css                  # Глобальные стили
```

#### Backend
```
backend/src/
├── Controller/
│   ├── TaskController.php        # API задач
│   └── AuthController.php        # Аутентификация
├── Service/
│   └── TaskService.php           # Бизнес-логика
├── Entity/
│   ├── Task.php                  # Entity задачи
│   └── User.php                  # Entity пользователя
└── Repository/
    └── TaskRepository.php        # Queries
```

---

## 🔥 Частые задачи и решения

### Задача: Добавить новое поле в Task

**Frontend:**
```typescript
// 1. Обнови тип
// frontend/src/types/task.types.ts
export interface Task {
  // ... existing fields
  newField: string  // Добавь новое поле
}

// 2. Обнови форму
// frontend/src/components/forms/CreateTaskDialog.vue
const formData = reactive({
  // ... existing fields
  newField: ''
})
```

**Backend:**
```php
// 1. Добавь поле в Entity
// backend/src/Entity/Task.php
#[ORM\Column(type: 'string', nullable: true)]
private ?string $newField = null;

// 2. Создай миграцию
docker-compose exec php bin/console make:migration

// 3. Примени миграцию
docker-compose exec php bin/console doctrine:migrations:migrate

// 4. Обнови DTO
// backend/src/DTO/CreateTaskDTO.php
public ?string $newField = null;
```

---

### Задача: Исправить проблему с датами

**Проблема**: Даты сдвигаются на день

**Решение**:
```typescript
// ✅ Используй formatDateForApi
function formatDateForApi(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// ❌ НЕ используй toISOString()
date.toISOString().split('T')[0]  // Сдвиг из-за UTC!
```

---

### Задача: Добавить новый фильтр задач

**Frontend:**
```typescript
// 1. Добавь в TaskFilters.vue
const filters = reactive({
  status: 'all',
  priority: 'all',
  newFilter: 'all'  // Новый фильтр
})

// 2. Обнови computed в TasksDashboardView.vue
const filteredTasks = computed(() => {
  return tasks.value.filter(task => {
    // ... existing filters
    if (filters.newFilter !== 'all') {
      return task.newField === filters.newFilter
    }
    return true
  })
})
```

**Backend:**
```php
// Добавь в TaskRepository.php
public function findByNewFilter(User $user, string $value): array
{
    return $this->createQueryBuilder('t')
        ->where('t.user = :user')
        ->andWhere('t.newField = :value')
        ->setParameter('user', $user)
        ->setParameter('value', $value)
        ->getQuery()
        ->getResult();
}
```

---

### Задача: Добавить новую страницу

```typescript
// 1. Создай компонент
// frontend/src/views/NewPageView.vue
<script setup lang="ts">
// ...
</script>

// 2. Добавь роут
// frontend/src/router/index.ts
{
  path: '/new-page',
  name: 'NewPage',
  component: () => import('@/views/NewPageView.vue'),
  meta: { requiresAuth: true }
}

// 3. Добавь переводы
// frontend/src/i18n/locales/ru.ts
export default {
  // ...
  newPage: {
    title: 'Новая страница'
  }
}
```

---

## 🐛 Частые ошибки и как их избежать

### ❌ Ошибка 1: "Задачи моргают при обновлении"
**Причина**: Перезагрузка всего списка
**Решение**: Используй точечное обновление массива

### ❌ Ошибка 2: "Двойные borders на фокусе"
**Причина**: Конфликт стилей
**Решение**: Уже исправлено в `main.css`, не добавляй новые `outline`

### ❌ Ошибка 3: "Подзадачи не обновляются"
**Причина**: Props не реактивны для вложенных объектов
**Решение**: Используй `useTaskCompletion` composable

### ❌ Ошибка 4: "401 Unauthorized"
**Причина**: Токен истек или не передан
**Решение**: Проверь `authStore.token` и Axios interceptors

### ❌ Ошибка 5: "CORS error"
**Причина**: Backend не разрешает запросы с frontend
**Решение**: Проверь `nelmio_cors.yaml` в backend

---

## 🎨 Дизайн-система (быстрая справка)

### Цвета
```css
Primary: #6366f1
Success: #10b981
Warning: #f59e0b
Danger: #ef4444
```

### Размеры
```css
Border-radius: 8-12px (не больше!)
Spacing: 8px, 16px, 24px, 32px
Font-size: 14px (base), 16px (large), 12px (small)
```

### Компоненты PrimeVue
```vue
<!-- Используй эти компоненты -->
<Button />
<InputText />
<Textarea />
<Dropdown />
<Calendar />
<Checkbox />
<Chip />
<Dialog />
<Sidebar />
<Toast />
```

---

## 🔐 Аутентификация (быстрая справка)

### Проверка авторизации
```typescript
// В компоненте
import { useAuthStore } from '@/stores/auth.store'
const authStore = useAuthStore()

if (!authStore.isAuthenticated) {
  router.push('/login')
}
```

### Защищенные роуты
```typescript
// В router/index.ts
{
  path: '/dashboard',
  meta: { requiresAuth: true }  // Требует авторизации
}
```

### API запросы с токеном
```typescript
// Автоматически добавляется в Axios interceptor
// Ничего делать не нужно!
```

---

## 📊 Структура Task (самое важное)

```typescript
interface Task {
  id: number
  title: string                    // Обязательное
  description?: string
  status: 'pending' | 'in_progress' | 'completed'
  priority: 'low' | 'medium' | 'high' | 'urgent'
  isCompleted: boolean
  completionProgress: number       // 0-100, автоматически
  startDate?: Date
  dueDate?: Date
  parent?: Task                    // Родительская задача
  subtasks?: Task[]                // Подзадачи (рекурсивно!)
  tags?: Tag[]
  user: User
  createdAt: Date
  updatedAt: Date
}
```

**Важно**: 
- `subtasks` - массив Task (неограниченная вложенность)
- `completionProgress` - рассчитывается автоматически на backend
- `parent` - ссылка на родительскую задачу (может быть null)

---

## 🚀 Быстрый старт для новой фичи

### 1. Backend (Symfony)
```bash
# Создай новый endpoint
docker-compose exec php bin/console make:controller

# Создай сервис
# backend/src/Service/NewFeatureService.php

# Создай DTO
# backend/src/DTO/NewFeatureDTO.php

# Добавь валидацию
use Symfony\Component\Validator\Constraints as Assert;

# Документируй API
use OpenApi\Attributes as OA;
```

### 2. Frontend (Vue)
```bash
# Создай компонент
touch frontend/src/components/NewFeature.vue

# Создай сервис
touch frontend/src/services/newFeature.service.ts

# Добавь в store (если нужно)
touch frontend/src/stores/newFeature.store.ts

# Добавь переводы
# frontend/src/i18n/locales/ru.ts
# frontend/src/i18n/locales/en.ts
```

### 3. Типы TypeScript
```typescript
// frontend/src/types/newFeature.types.ts
export interface NewFeature {
  id: number
  // ... fields
}

export enum NewFeatureStatus {
  ACTIVE = 'active',
  INACTIVE = 'inactive'
}
```

---

## 🧪 Тестирование

### Тестовые аккаунты
```
Email: sollent98@gmail.com
Password: Pahan1998

Email: vladislikedev@gmail.com
Password: Pahan1998
```

### Быстрая проверка
```bash
# Frontend
cd frontend && npm run dev
# Открой http://localhost:3000

# Backend API
# Открой http://localhost:8000/api/doc

# Проверь логи
docker-compose logs -f php
```

---

## 💡 Полезные команды (копируй и вставляй)

### Backend
```bash
# Создать миграцию
docker-compose exec php bin/console make:migration

# Применить миграции
docker-compose exec php bin/console doctrine:migrations:migrate

# Очистить кэш
docker-compose exec php bin/console cache:clear

# Посмотреть роуты
docker-compose exec php bin/console debug:router

# Посмотреть сервисы
docker-compose exec php bin/console debug:container
```

### Frontend
```bash
# Запустить dev
npm run dev

# Собрать production
npm run build

# Проверить типы
npm run type-check

# Открыть в браузере
open http://localhost:3000
```

### Docker
```bash
# Перезапустить все
docker-compose restart

# Посмотреть логи
docker-compose logs -f

# Зайти в контейнер
docker-compose exec php bash

# Остановить все
docker-compose down
```

---

## 🎯 Чек-лист перед коммитом

- [ ] Код работает локально
- [ ] Нет ошибок TypeScript (`npm run type-check`)
- [ ] Нет console.log (кроме debug)
- [ ] Добавлены переводы (ru + en)
- [ ] Проверена адаптивность (mobile)
- [ ] Используются оптимистичные обновления
- [ ] Не перезагружаются списки без необходимости
- [ ] Добавлена обработка ошибок
- [ ] Показываются toast уведомления
- [ ] Соблюдены naming conventions

---

## 🆘 Когда что-то сломалось

### 1. Frontend не запускается
```bash
cd frontend
rm -rf node_modules
npm install
npm run dev
```

### 2. Backend не работает
```bash
cd backend
docker-compose down
docker-compose up -d
docker-compose exec php composer install
docker-compose exec php bin/console cache:clear
```

### 3. База данных сломалась
```bash
docker-compose down -v  # Удалит все данные!
docker-compose up -d
docker-compose exec php bin/console doctrine:migrations:migrate
docker-compose exec php bin/console doctrine:fixtures:load
```

### 4. JWT токены не работают
```bash
docker-compose exec php bin/console lexik:jwt:generate-keypair --overwrite
```

---

## 📚 Где искать примеры кода

### Оптимистичные обновления
- `frontend/src/stores/task.store.ts` - метод `toggleTaskCompletion`
- `frontend/src/components/tasks/TaskCard.vue` - метод `handleToggleComplete`

### Работа с подзадачами
- `frontend/src/composables/useTaskCompletion.ts`
- `backend/src/Service/TaskService.php` - метод `completeTaskRecursively`

### Календарь
- `frontend/src/views/CalendarView.vue` - вся логика
- `backend/src/Repository/TaskRepository.php` - метод `findTasksByDateRange`

### Формы и валидация
- `frontend/src/components/forms/CreateTaskDialog.vue`
- `backend/src/DTO/CreateTaskDTO.php`

---

## 🎓 Ключевые концепции

### 1. Оптимистичные обновления
**Зачем**: Мгновенный отклик UI, лучший UX
**Как**: Обнови UI → запрос в фоне → откат при ошибке

### 2. Композиция над наследованием
**Зачем**: Переиспользуемая логика
**Как**: Используй composables (`useTaskCompletion`, `useToast`)

### 3. Типизация везде
**Зачем**: Меньше багов, лучший DX
**Как**: TypeScript strict mode, типизируй все

### 4. Разделение ответственности
**Зачем**: Легче поддерживать и тестировать
**Как**: Контроллеры → Сервисы → Репозитории (backend)
         Компоненты → Composables → Stores (frontend)

---

## 🔗 Полезные ссылки

- [Vue 3 Docs](https://vuejs.org/)
- [PrimeVue Components](https://primevue.org/)
- [Symfony Docs](https://symfony.com/doc/current/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)

---

**Помни**: Если не уверен - спроси или посмотри в `PROJECT_DOCUMENTATION.md`! 🚀

