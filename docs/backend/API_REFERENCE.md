# API Справочник - Полная документация

## Содержание
1. [Аутентификация](#authentication)
2. [Задачи](#tasks)
3. [Теги](#tags)
4. [Аналитика](#analytics)
5. [Обработка ошибок](#error-handling)
6. [Ограничение частоты запросов](#rate-limiting)

---

## Базовый URL

```
Development: http://localhost:8089/api
Production:  https://your-domain.com/api
```

## Аутентификация

Все API эндпоинты (кроме регистрации и входа) требуют JWT аутентификацию.

**Формат заголовка**:
```http
Authorization: Bearer <jwt_token>
```

---

### POST /api/register

Регистрация нового пользователя.

**Аутентификация:** Не требуется

**Тело запроса:**
```json
{
  "email": "user@example.com",
  "password": "securePassword123",
  "name": "John Doe"
}
```

**Ответ 201 (Создано):**
```json
{
  "id": 42,
  "email": "user@example.com",
  "name": "John Doe",
  "createdAt": "2025-01-15T10:00:00+00:00"
}
```

**Ошибка 400 (Ошибка валидации):**
```json
{
  "errors": [
    "Email is already registered",
    "Password must be at least 8 characters"
  ]
}
```

**Ошибка 422 (Некорректный ввод):**
```json
{
  "message": "Validation failed",
  "violations": [
    {
      "propertyPath": "email",
      "message": "This value is not a valid email address."
    }
  ]
}
```

---

### POST /api/login

Аутентификация пользователя и получение JWT токена.

**Аутентификация:** Не требуется

**Тело запроса:**
```json
{
  "email": "user@example.com",
  "password": "securePassword123"
}
```

**Ответ 200 (Успех):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refreshToken": "def50200a1b2c3d4e5f6...",
  "user": {
    "id": 42,
    "email": "user@example.com",
    "name": "John Doe",
    "roles": ["ROLE_USER"]
  }
}
```

**Ошибка 401 (Неверные учетные данные):**
```json
{
  "message": "Invalid credentials"
}
```

---

### POST /api/token/refresh

Обновление JWT токена с использованием refresh токена.

**Аутентификация:** Не требуется (использует refresh токен)

**Тело запроса:**
```json
{
  "refreshToken": "def50200a1b2c3d4e5f6..."
}
```

**Ответ 200 (Успех):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refreshToken": "def50200a1b2c3d4e5f6..."
}
```

**Ошибка 401 (Неверный Refresh Token):**
```json
{
  "message": "Invalid refresh token"
}
```

---

### POST /api/logout

Выход пользователя (инвалидация refresh токена).

**Аутентификация:** Требуется (JWT)

**Тело запроса:**
```json
{
  "refreshToken": "def50200a1b2c3d4e5f6..."
}
```

**Ответ 204 (Нет содержимого)**

---

### GET /api/users/me

Получение профиля текущего аутентифицированного пользователя.

**Аутентификация:** Требуется (JWT)

**Ответ 200 (Успех):**
```json
{
  "id": 42,
  "email": "user@example.com",
  "name": "John Doe",
  "roles": ["ROLE_USER"],
  "createdAt": "2025-01-01T00:00:00+00:00",
  "updatedAt": "2025-01-15T10:00:00+00:00"
}
```

**Ошибка 401 (Не авторизован):**
```json
{
  "message": "JWT Token not found"
}
```

---

## Задачи

### GET /api/tasks

Получение списка задач с опциональными фильтрами.

**Аутентификация:** Требуется (JWT)

**Параметры запроса:**
- `view` (опционально): Фильтр по типу представления
  - Значения: `today`, `overdue`, `upcoming`, `all`, `unscheduled`
  - По умолчанию: `all`
- `search` (опционально): Поиск в заголовке/описании
  - Тип: `string`
  - Пример: `search=meeting`
- `tags` (опционально): Фильтр по ID тегов
  - Тип: `integer[]`
  - Пример: `tags[]=1&tags[]=2`
- `completed` (опционально): Фильтр по статусу завершения
  - Тип: `boolean`
  - Пример: `completed=true`
- `dateFrom` (опционально): Фильтр задач начиная с даты
  - Тип: `string` (формат: `YYYY-MM-DD`)
  - Пример: `dateFrom=2025-01-01`
- `dateTo` (опционально): Фильтр задач заканчивая датой
  - Тип: `string` (формат: `YYYY-MM-DD`)
  - Пример: `dateTo=2025-12-31`
- `priorities` (опционально): Фильтр по уровням приоритета
  - Тип: `string[]`
  - Значения: `LOW`, `MEDIUM`, `HIGH`, `URGENT`
  - Пример: `priorities[]=HIGH&priorities[]=URGENT`
- `statuses` (опционально): Фильтр по статусам задач
  - Тип: `string[]`
  - Значения: `PENDING`, `IN_PROGRESS`, `COMPLETED`, `CANCELLED`
  - Пример: `statuses[]=PENDING&statuses[]=IN_PROGRESS`

**Ответ 200 (Успех):**
```json
[
  {
    "id": 123,
    "title": "Complete documentation",
    "description": "Write comprehensive API docs",
    "status": "IN_PROGRESS",
    "priority": "HIGH",
    "startDate": "2025-01-15T10:00:00+00:00",
    "dueDate": "2025-01-20T18:00:00+00:00",
    "completedAt": null,
    "parentTaskId": null,
    "tags": [
      {
        "id": 1,
        "name": "Work",
        "color": "#3b82f6"
      },
      {
        "id": 5,
        "name": "Documentation",
        "color": "#10b981"
      }
    ],
    "subtasks": [],
    "subtaskCount": 3,
    "completedSubtaskCount": 1,
    "hasNestedSubtasks": true,
    "attachments": [],
    "isRecurringTemplate": false,
    "recurrenceRule": null,
    "sortOrder": 0,
    "isArchived": false,
    "isCompleted": false,
    "isOverdue": false,
    "completionProgress": 33.33,
    "createdAt": "2025-01-15T09:00:00+00:00",
    "updatedAt": "2025-01-15T11:00:00+00:00",
    "priorityLabel": "High",
    "statusLabel": "In Progress"
  }
]
```

**Ошибка 401 (Не авторизован):**
```json
{
  "message": "JWT Token not found"
}
```

**Ошибка 400 (Некорректные параметры):**
```json
{
  "message": "Invalid filter parameters",
  "errors": {
    "priority": "Invalid priority value. Allowed: LOW, MEDIUM, HIGH, URGENT"
  }
}
```

---

### GET /api/tasks/{id}

Получение одной задачи с полными деталями, включая подзадачи.

**Аутентификация:** Требуется (JWT)

**Параметры пути:**
- `id` (обязательно): ID задачи
  - Тип: `integer`

**Ответ 200 (Успех):**
```json
{
  "id": 123,
  "title": "Complete documentation",
  "description": "Write comprehensive API docs",
  "status": "IN_PROGRESS",
  "priority": "HIGH",
  "startDate": "2025-01-15T10:00:00+00:00",
  "dueDate": "2025-01-20T18:00:00+00:00",
  "completedAt": null,
  "parentTaskId": null,
  "tags": [
    {
      "id": 1,
      "name": "Work",
      "color": "#3b82f6"
    }
  ],
  "subtasks": [
    {
      "id": 124,
      "title": "Write authentication section",
      "description": null,
      "status": "COMPLETED",
      "priority": "MEDIUM",
      "startDate": null,
      "dueDate": "2025-01-16T18:00:00+00:00",
      "completedAt": "2025-01-16T15:30:00+00:00",
      "parentTaskId": 123,
      "tags": [],
      "subtasks": [],
      "subtaskCount": 0,
      "completedSubtaskCount": 0,
      "hasNestedSubtasks": false,
      "sortOrder": 0,
      "isArchived": false,
      "isCompleted": true,
      "isOverdue": false,
      "completionProgress": 100.0,
      "createdAt": "2025-01-15T09:30:00+00:00",
      "updatedAt": "2025-01-16T15:30:00+00:00",
      "priorityLabel": "Medium",
      "statusLabel": "Completed"
    }
  ],
  "subtaskCount": 3,
  "completedSubtaskCount": 1,
  "hasNestedSubtasks": true,
  "attachments": [
    {
      "id": 10,
      "fileName": "api_diagram_abc123.png",
      "originalName": "api_diagram.png",
      "mimeType": "image/png",
      "fileSize": 245760,
      "fileSizeHuman": "240 KB",
      "fileType": "image",
      "filePath": "/uploads/tasks/api_diagram_abc123.png",
      "thumbnailPath": "/uploads/tasks/thumbnails/api_diagram_abc123.png",
      "createdAt": "2025-01-15 10:30:00"
    }
  ],
  "isRecurringTemplate": false,
  "recurrenceRule": null,
  "sortOrder": 0,
  "isArchived": false,
  "isCompleted": false,
  "isOverdue": false,
  "completionProgress": 33.33,
  "createdAt": "2025-01-15T09:00:00+00:00",
  "updatedAt": "2025-01-15T11:00:00+00:00",
  "priorityLabel": "High",
  "statusLabel": "In Progress"
}
```

**Ошибка 404 (Не найдено):**
```json
{
  "message": "Task not found"
}
```

**Ошибка 403 (Запрещено):**
```json
{
  "message": "Access denied. You do not own this task."
}
```

---

### POST /api/tasks

Создание новой задачи.

**Аутентификация:** Требуется (JWT)

**Тело запроса:**
```json
{
  "title": "Complete documentation",
  "description": "Write comprehensive API docs",
  "status": "IN_PROGRESS",
  "priority": "HIGH",
  "startDate": "2025-01-15T10:00:00+00:00",
  "dueDate": "2025-01-20T18:00:00+00:00",
  "parentTaskId": null,
  "tagIds": [1, 5],
  "sortOrder": 0
}
```

**Обязательные поля:**
- `title` (string, макс 255 символов)

**Опциональные поля:**
- `description` (string, nullable)
- `status` (enum: `PENDING`, `IN_PROGRESS`, `COMPLETED`, `CANCELLED`, по умолчанию: `PENDING`)
- `priority` (enum: `LOW`, `MEDIUM`, `HIGH`, `URGENT`, по умолчанию: `MEDIUM`)
- `startDate` (ISO 8601 datetime, nullable)
- `dueDate` (ISO 8601 datetime, nullable)
- `parentTaskId` (integer, nullable)
- `tagIds` (integer array, nullable)
- `sortOrder` (integer, по умолчанию: 0)

**Ответ 201 (Создано):**
```json
{
  "id": 125,
  "title": "Complete documentation",
  "description": "Write comprehensive API docs",
  "status": "IN_PROGRESS",
  "priority": "HIGH",
  "startDate": "2025-01-15T10:00:00+00:00",
  "dueDate": "2025-01-20T18:00:00+00:00",
  "completedAt": null,
  "parentTaskId": null,
  "tags": [
    {
      "id": 1,
      "name": "Work",
      "color": "#3b82f6"
    }
  ],
  "subtasks": [],
  "subtaskCount": 0,
  "completedSubtaskCount": 0,
  "hasNestedSubtasks": false,
  "sortOrder": 0,
  "isArchived": false,
  "isCompleted": false,
  "isOverdue": false,
  "completionProgress": 0.0,
  "createdAt": "2025-01-15T12:00:00+00:00",
  "updatedAt": "2025-01-15T12:00:00+00:00",
  "priorityLabel": "High",
  "statusLabel": "In Progress"
}
```

**Ошибка 400 (Ошибка валидации):**
```json
{
  "message": "Validation failed",
  "violations": [
    {
      "propertyPath": "title",
      "message": "This value should not be blank."
    },
    {
      "propertyPath": "dueDate",
      "message": "Due date must be after start date."
    }
  ]
}
```

**Ошибка 404 (Родительская задача не найдена):**
```json
{
  "message": "Parent task with ID 999 not found"
}
```

---

### PUT /api/tasks/{id}

Обновление существующей задачи (полное обновление).

**Аутентификация:** Требуется (JWT)

**Параметры пути:**
- `id` (обязательно): ID задачи

**Тело запроса:**
```json
{
  "title": "Complete documentation (updated)",
  "description": "Write comprehensive API docs with examples",
  "status": "COMPLETED",
  "priority": "HIGH",
  "startDate": "2025-01-15T10:00:00+00:00",
  "dueDate": "2025-01-20T18:00:00+00:00",
  "tagIds": [1, 5, 7]
}
```

**Ответ 200 (Успех):**
```json
{
  "id": 123,
  "title": "Complete documentation (updated)",
  "description": "Write comprehensive API docs with examples",
  "status": "COMPLETED",
  "priority": "HIGH",
  "completedAt": "2025-01-18T16:45:00+00:00",
  "tags": [
    {
      "id": 1,
      "name": "Work",
      "color": "#3b82f6"
    },
    {
      "id": 5,
      "name": "Documentation",
      "color": "#10b981"
    },
    {
      "id": 7,
      "name": "Backend",
      "color": "#f59e0b"
    }
  ],
  "updatedAt": "2025-01-18T16:45:00+00:00"
}
```

**Ошибка 403 (Запрещено):**
```json
{
  "message": "Access denied. You do not own this task."
}
```

**Ошибка 404 (Не найдено):**
```json
{
  "message": "Task not found"
}
```

---

### DELETE /api/tasks/{id}

Удаление задачи и всех её подзадач.

**Аутентификация:** Требуется (JWT)

**Параметры пути:**
- `id` (обязательно): ID задачи

**Ответ 204 (Нет содержимого)**

**Ошибка 403 (Запрещено):**
```json
{
  "message": "Access denied. You do not own this task."
}
```

**Ошибка 404 (Не найдено):**
```json
{
  "message": "Task not found"
}
```

---

### POST /api/tasks/{id}/toggle

Переключение статуса завершения задачи (завершить ↔ не завершена).

**Аутентификация:** Требуется (JWT)

**Параметры пути:**
- `id` (обязательно): ID задачи

**Ответ 200 (Успех):**
```json
{
  "id": 123,
  "title": "Complete documentation",
  "status": "COMPLETED",
  "completedAt": "2025-01-18T16:45:00+00:00",
  "isCompleted": true,
  "completionProgress": 100.0,
  "updatedAt": "2025-01-18T16:45:00+00:00"
}
```

**Поведение:**
- Если задача `COMPLETED` → меняется на предыдущий статус (по умолчанию `IN_PROGRESS`)
- Если задача не `COMPLETED` → меняется на `COMPLETED`
- Устанавливает/очищает временную метку `completedAt`

---

### POST /api/tasks/{id}/complete

Отметить задачу как завершенную.

**Аутентификация:** Требуется (JWT)

**Параметры пути:**
- `id` (обязательно): ID задачи

**Ответ 200 (Успех):**
```json
{
  "id": 123,
  "title": "Complete documentation",
  "status": "COMPLETED",
  "completedAt": "2025-01-18T16:45:00+00:00",
  "isCompleted": true,
  "completionProgress": 100.0,
  "updatedAt": "2025-01-18T16:45:00+00:00"
}
```

---

### POST /api/tasks/{id}/archive

Архивировать задачу (скрыть из активных списков).

**Аутентификация:** Требуется (JWT)

**Параметры пути:**
- `id` (обязательно): ID задачи

**Ответ 200 (Успех):**
```json
{
  "id": 123,
  "title": "Complete documentation",
  "isArchived": true,
  "updatedAt": "2025-01-18T17:00:00+00:00"
}
```

---

### POST /api/tasks/{id}/unarchive

Разархивировать задачу (вернуть в активные списки).

**Аутентификация:** Требуется (JWT)

**Параметры пути:**
- `id` (обязательно): ID задачи

**Ответ 200 (Успех):**
```json
{
  "id": 123,
  "title": "Complete documentation",
  "isArchived": false,
  "updatedAt": "2025-01-18T17:05:00+00:00"
}
```

---

### GET /api/tasks/statistics

Получение статистики задач для текущего пользователя.

**Аутентификация:** Требуется (JWT)

**Ответ 200 (Успех):**
```json
{
  "total": 150,
  "pending": 45,
  "in_progress": 32,
  "completed": 68,
  "cancelled": 5,
  "overdue": 12,
  "dueToday": 8,
  "dueThisWeek": 23,
  "completionRate": 45.33,
  "avgCompletionTime": 4.5
}
```

**Описание полей:**
- `total`: Общее количество задач (исключая архивные)
- `pending`: Задачи со статусом `PENDING`
- `in_progress`: Задачи со статусом `IN_PROGRESS`
- `completed`: Задачи со статусом `COMPLETED`
- `cancelled`: Задачи со статусом `CANCELLED`
- `overdue`: Задачи с истекшим сроком и не завершенные
- `dueToday`: Задачи с дедлайном сегодня
- `dueThisWeek`: Задачи с дедлайном в течение 7 дней
- `completionRate`: Процент завершенных задач
- `avgCompletionTime`: Среднее количество дней для завершения задач

---

### GET /api/tasks/overdue

Получение постраничного списка просроченных задач.

**Аутентификация:** Требуется (JWT)

**Параметры запроса:**
- `page` (опционально): Номер страницы
  - Тип: `integer`
  - По умолчанию: `1`
- `limit` (опционально): Элементов на странице
  - Тип: `integer`
  - По умолчанию: `20`
  - Макс: `100`

**Ответ 200 (Успех):**
```json
{
  "tasks": [
    {
      "id": 100,
      "title": "Fix critical bug",
      "dueDate": "2025-01-10T18:00:00+00:00",
      "priority": "URGENT",
      "isOverdue": true,
      "daysOverdue": 8
    }
  ],
  "total": 12,
  "page": 1,
  "limit": 20,
  "pages": 1
}
```

---

### GET /api/tasks/calendar/week

Получение задач для недельного календарного вида.

**Аутентификация:** Требуется (JWT)

**Параметры запроса:**
- `weekStart` (обязательно): Дата начала недели
  - Тип: `string` (формат: `YYYY-MM-DD`)
  - Пример: `weekStart=2025-01-13` (Понедельник)
- `includeCompleted` (опционально): Включить завершенные задачи
  - Тип: `boolean`
  - По умолчанию: `true`

**Ответ 200 (Успех):**
```json
[
  {
    "id": 123,
    "title": "Team meeting",
    "dueDate": "2025-01-15T14:00:00+00:00",
    "status": "PENDING",
    "priority": "MEDIUM"
  }
]
```

---

### GET /api/tasks/calendar/month

Получение задач для месячного календарного вида.

**Аутентификация:** Требуется (JWT)

**Параметры запроса:**
- `year` (обязательно): Год
  - Тип: `integer`
  - Пример: `year=2025`
- `month` (обязательно): Месяц (1-12)
  - Тип: `integer`
  - Пример: `month=1`
- `includeCompleted` (опционально): Включить завершенные задачи
  - Тип: `boolean`
  - По умолчанию: `true`

**Ответ 200 (Успех):**
```json
[
  {
    "id": 123,
    "title": "Monthly review",
    "dueDate": "2025-01-31T18:00:00+00:00",
    "status": "IN_PROGRESS",
    "priority": "HIGH"
  }
]
```

---

### GET /api/tasks/calendar/day

Получение задач для определенного дня.

**Аутентификация:** Требуется (JWT)

**Параметры запроса:**
- `date` (обязательно): Дата
  - Тип: `string` (формат: `YYYY-MM-DD`)
  - Пример: `date=2025-01-15`
- `includeCompleted` (опционально): Включить завершенные задачи
  - Тип: `boolean`
  - По умолчанию: `true`

**Ответ 200 (Успех):**
```json
[
  {
    "id": 123,
    "title": "Complete documentation",
    "dueDate": "2025-01-15T18:00:00+00:00",
    "status": "IN_PROGRESS",
    "priority": "HIGH"
  }
]
```

---

### POST /api/tasks/reorder

Изменение порядка задач путем указания нового порядка сортировки.

**Аутентификация:** Требуется (JWT)

**Тело запроса:**
```json
{
  "taskIds": [125, 123, 124, 126]
}
```

**Ответ 204 (Нет содержимого)**

**Поведение:**
- Обновляет поле `sortOrder` для каждой задачи на основе позиции в массиве
- Задача с индексом 0 получает `sortOrder = 0`, индекс 1 получает `sortOrder = 1`, и т.д.

---

## Теги

### GET /api/tags

Получение списка тегов пользователя.

**Аутентификация:** Требуется (JWT)

**Параметры запроса:**
- `search` (опционально): Поиск тегов по имени
  - Тип: `string`
  - Пример: `search=work`
- `limit` (опционально): Ограничение количества результатов
  - Тип: `integer`
  - Пример: `limit=10`

**Ответ 200 (Успех):**
```json
[
  {
    "id": 1,
    "name": "Work",
    "color": "#3b82f6",
    "icon": "briefcase",
    "taskCount": 45,
    "createdAt": "2025-01-01T00:00:00+00:00"
  },
  {
    "id": 5,
    "name": "Documentation",
    "color": "#10b981",
    "icon": "book",
    "taskCount": 12,
    "createdAt": "2025-01-05T10:00:00+00:00"
  }
]
```

---

### GET /api/tags/{id}

Получение деталей одного тега.

**Аутентификация:** Требуется (JWT)

**Параметры пути:**
- `id` (обязательно): ID тега

**Ответ 200 (Успех):**
```json
{
  "id": 1,
  "name": "Work",
  "color": "#3b82f6",
  "icon": "briefcase",
  "taskCount": 45,
  "createdAt": "2025-01-01T00:00:00+00:00",
  "updatedAt": "2025-01-10T15:30:00+00:00"
}
```

**Ошибка 404 (Не найдено):**
```json
{
  "message": "Tag not found"
}
```

---

### POST /api/tags

Создание нового тега.

**Аутентификация:** Требуется (JWT)

**Тело запроса:**
```json
{
  "name": "Urgent",
  "color": "#ef4444",
  "icon": "alert-circle"
}
```

**Обязательные поля:**
- `name` (string, макс 50 символов)

**Опциональные поля:**
- `color` (string, hex цвет, по умолчанию: `#3B82F6`)
- `icon` (string, nullable)

**Ответ 201 (Создано):**
```json
{
  "id": 10,
  "name": "Urgent",
  "color": "#ef4444",
  "icon": "alert-circle",
  "taskCount": 0,
  "createdAt": "2025-01-18T12:00:00+00:00"
}
```

**Ошибка 409 (Конфликт):**
```json
{
  "message": "Tag with this name already exists"
}
```

**Ошибка 400 (Ошибка валидации):**
```json
{
  "errors": [
    "Name must not be empty",
    "Color must be a valid hex color"
  ]
}
```

---

### PUT /api/tags/{id}

Обновление существующего тега.

**Аутентификация:** Требуется (JWT)

**Параметры пути:**
- `id` (обязательно): ID тега

**Тело запроса:**
```json
{
  "name": "Super Urgent",
  "color": "#dc2626",
  "icon": "alert-triangle"
}
```

**Ответ 200 (Успех):**
```json
{
  "id": 10,
  "name": "Super Urgent",
  "color": "#dc2626",
  "icon": "alert-triangle",
  "taskCount": 3,
  "updatedAt": "2025-01-18T13:00:00+00:00"
}
```

**Ошибка 409 (Конфликт):**
```json
{
  "message": "Tag with this name already exists"
}
```

---

### DELETE /api/tags/{id}

Удаление тега (удаляет тег из всех задач).

**Аутентификация:** Требуется (JWT)

**Параметры пути:**
- `id` (обязательно): ID тега

**Ответ 204 (Нет содержимого)**

**Ошибка 403 (Запрещено):**
```json
{
  "message": "Access denied. You do not own this tag."
}
```

---

### GET /api/tags/most-used

Получение наиболее часто используемых тегов.

**Аутентификация:** Требуется (JWT)

**Параметры запроса:**
- `limit` (опционально): Количество тегов для возврата
  - Тип: `integer`
  - По умолчанию: `5`
  - Макс: `20`

**Ответ 200 (Успех):**
```json
[
  {
    "id": 1,
    "name": "Work",
    "color": "#3b82f6",
    "icon": "briefcase",
    "taskCount": 45,
    "usageCount": 45
  },
  {
    "id": 5,
    "name": "Documentation",
    "color": "#10b981",
    "icon": "book",
    "taskCount": 32,
    "usageCount": 32
  }
]
```

---

## Аналитика

Все эндпоинты аналитики кэшируются со стратегией INVALIDATE. Кэш очищается при создании/обновлении/удалении задач.

### GET /api/analytics/overview

Получение обзора аналитики с ключевыми метриками.

**Аутентификация:** Требуется (JWT)

**Ответ 200 (Успех):**
```json
{
  "totalTasks": 150,
  "completedTasks": 68,
  "pendingTasks": 45,
  "inProgressTasks": 32,
  "overdueTasks": 12,
  "completionRate": 45.33,
  "avgCompletionTime": 4.5,
  "tasksCompletedToday": 5,
  "tasksCompletedThisWeek": 18,
  "tasksCompletedThisMonth": 42,
  "currentStreak": 7,
  "longestStreak": 15
}
```

---

### GET /api/analytics/dashboard

Получение полных данных панели аналитики (объединяет несколько эндпоинтов).

**Аутентификация:** Требуется (JWT)

**Параметры запроса:**
- `period` (опционально): Период временной линии в днях
  - Тип: `integer`
  - По умолчанию: `30`
  - Пример: `period=90`
- `dateFrom` (опционально): Дата начала временной линии
  - Тип: `string` (формат: `YYYY-MM-DD`)
  - Пример: `dateFrom=2025-01-01`
- `dateTo` (опционально): Дата окончания временной линии
  - Тип: `string` (формат: `YYYY-MM-DD`)
  - Пример: `dateTo=2025-01-31`
- `year` (опционально): Год для тепловой карты
  - Тип: `integer`
  - По умолчанию: текущий год
  - Пример: `year=2025`

**Ответ 200 (Успех):**
```json
{
  "overview": {
    "totalTasks": 150,
    "completedTasks": 68,
    "completionRate": 45.33
  },
  "timeline": [
    {
      "date": "2025-01-01",
      "completed": 3,
      "created": 5
    },
    {
      "date": "2025-01-02",
      "completed": 2,
      "created": 4
    }
  ],
  "statusDistribution": {
    "PENDING": 45,
    "IN_PROGRESS": 32,
    "COMPLETED": 68,
    "CANCELLED": 5
  },
  "priorityBreakdown": {
    "LOW": 25,
    "MEDIUM": 60,
    "HIGH": 45,
    "URGENT": 20
  },
  "heatmap": {
    "2025-01-01": 3,
    "2025-01-02": 2,
    "2025-01-03": 5
  },
  "weekdayProductivity": {
    "Monday": 12,
    "Tuesday": 15,
    "Wednesday": 10,
    "Thursday": 14,
    "Friday": 18,
    "Saturday": 5,
    "Sunday": 3
  },
  "topTags": [
    {
      "id": 1,
      "name": "Work",
      "taskCount": 45,
      "completedCount": 20,
      "completionRate": 44.44
    }
  ],
  "insights": [
    "You complete most tasks on Fridays",
    "Your completion rate improved by 15% this month",
    "You have 12 overdue tasks that need attention"
  ]
}
```

---

### GET /api/analytics/completion-timeline

Получение данных временной линии завершения задач.

**Аутентификация:** Требуется (JWT)

**Параметры запроса:**
- `period` (опционально): Количество дней
  - Тип: `integer`
  - По умолчанию: `30`
- `dateFrom` (опционально): Дата начала (переопределяет period)
  - Тип: `string` (формат: `YYYY-MM-DD`)
- `dateTo` (опционально): Дата окончания (требуется с dateFrom)
  - Тип: `string` (формат: `YYYY-MM-DD`)

**Ответ 200 (Успех):**
```json
[
  {
    "date": "2025-01-01",
    "completed": 3,
    "created": 5,
    "deleted": 0,
    "pending": 2,
    "inProgress": 1
  },
  {
    "date": "2025-01-02",
    "completed": 2,
    "created": 4,
    "deleted": 1,
    "pending": 3,
    "inProgress": 2
  }
]
```

---

### GET /api/analytics/status-distribution

Получение распределения количества задач по статусам.

**Аутентификация:** Требуется (JWT)

**Ответ 200 (Успех):**
```json
{
  "PENDING": {
    "count": 45,
    "percentage": 30.0
  },
  "IN_PROGRESS": {
    "count": 32,
    "percentage": 21.33
  },
  "COMPLETED": {
    "count": 68,
    "percentage": 45.33
  },
  "CANCELLED": {
    "count": 5,
    "percentage": 3.33
  }
}
```

---

### GET /api/analytics/priority-breakdown

Получение статистики задач, сгруппированных по приоритету.

**Аутентификация:** Требуется (JWT)

**Ответ 200 (Успех):**
```json
{
  "LOW": {
    "total": 25,
    "completed": 15,
    "pending": 8,
    "inProgress": 2,
    "completionRate": 60.0
  },
  "MEDIUM": {
    "total": 60,
    "completed": 28,
    "pending": 20,
    "inProgress": 12,
    "completionRate": 46.67
  },
  "HIGH": {
    "total": 45,
    "completed": 20,
    "pending": 15,
    "inProgress": 10,
    "completionRate": 44.44
  },
  "URGENT": {
    "total": 20,
    "completed": 5,
    "pending": 2,
    "inProgress": 8,
    "overdue": 5,
    "completionRate": 25.0
  }
}
```

---

### GET /api/analytics/productivity-heatmap

Получение тепловой карты продуктивности в стиле GitHub (количество завершенных задач по дням).

**Аутентификация:** Требуется (JWT)

**Параметры запроса:**
- `year` (опционально): Год для тепловой карты
  - Тип: `integer`
  - По умолчанию: текущий год
  - Пример: `year=2025`

**Ответ 200 (Успех):**
```json
{
  "2025-01-01": 3,
  "2025-01-02": 2,
  "2025-01-03": 5,
  "2025-01-04": 0,
  "2025-01-05": 4,
  "2025-01-06": 7,
  "2025-01-07": 1
}
```

**Примечания:**
- Возвращает все 365 дней года
- Дни без завершений имеют значение `0`
- Самый дорогой запрос аналитики (TTL 30 секунд)

---

### GET /api/analytics/weekday-productivity

Получение статистики завершения задач по дням недели.

**Аутентификация:** Требуется (JWT)

**Ответ 200 (Успех):**
```json
{
  "Monday": {
    "completed": 12,
    "created": 15,
    "averageCompletionTime": 4.2
  },
  "Tuesday": {
    "completed": 15,
    "created": 18,
    "averageCompletionTime": 3.8
  },
  "Wednesday": {
    "completed": 10,
    "created": 12,
    "averageCompletionTime": 5.1
  },
  "Thursday": {
    "completed": 14,
    "created": 16,
    "averageCompletionTime": 4.5
  },
  "Friday": {
    "completed": 18,
    "created": 20,
    "averageCompletionTime": 3.2
  },
  "Saturday": {
    "completed": 5,
    "created": 6,
    "averageCompletionTime": 6.5
  },
  "Sunday": {
    "completed": 3,
    "created": 4,
    "averageCompletionTime": 7.0
  }
}
```

---

### GET /api/analytics/top-tags

Получение наиболее используемых тегов со статистикой завершения.

**Аутентификация:** Требуется (JWT)

**Параметры запроса:**
- `limit` (опционально): Количество тегов для возврата
  - Тип: `integer`
  - По умолчанию: `5`
  - Макс: `20`

**Ответ 200 (Успех):**
```json
[
  {
    "id": 1,
    "name": "Work",
    "color": "#3b82f6",
    "taskCount": 45,
    "completedCount": 20,
    "pendingCount": 15,
    "inProgressCount": 10,
    "completionRate": 44.44,
    "avgCompletionTime": 4.5
  },
  {
    "id": 5,
    "name": "Documentation",
    "color": "#10b981",
    "taskCount": 32,
    "completedCount": 18,
    "pendingCount": 10,
    "inProgressCount": 4,
    "completionRate": 56.25,
    "avgCompletionTime": 3.2
  }
]
```

---

### GET /api/analytics/insights

Получение AI-подобных инсайтов и рекомендаций на основе данных пользователя.

**Аутентификация:** Требуется (JWT)

**Ответ 200 (Успех):**
```json
{
  "insights": [
    {
      "type": "productivity",
      "title": "Peak Productivity Day",
      "message": "You complete most tasks on Fridays (18 tasks on average)",
      "severity": "info",
      "actionable": false
    },
    {
      "type": "improvement",
      "title": "Completion Rate Improved",
      "message": "Your completion rate improved by 15% this month compared to last month",
      "severity": "success",
      "actionable": false
    },
    {
      "type": "warning",
      "title": "Overdue Tasks Need Attention",
      "message": "You have 12 overdue tasks. Consider reviewing and updating due dates.",
      "severity": "warning",
      "actionable": true,
      "action": {
        "label": "View Overdue Tasks",
        "url": "/tasks?view=overdue"
      }
    },
    {
      "type": "streak",
      "title": "7-Day Streak",
      "message": "You've completed tasks for 7 days in a row! Keep it up!",
      "severity": "success",
      "actionable": false
    },
    {
      "type": "recommendation",
      "title": "Focus on High Priority",
      "message": "You have 15 high priority pending tasks. These should be prioritized.",
      "severity": "info",
      "actionable": true,
      "action": {
        "label": "View High Priority",
        "url": "/tasks?priorities[]=HIGH&statuses[]=PENDING"
      }
    }
  ],
  "generatedAt": "2025-01-18T16:00:00+00:00"
}
```

---

### GET /api/analytics/streak

Получение текущей и самой длинной серии завершения задач.

**Аутентификация:** Требуется (JWT)

**Ответ 200 (Успех):**
```json
{
  "currentStreak": 7,
  "longestStreak": 15,
  "streakStartDate": "2025-01-12",
  "streakEndDate": null,
  "isActive": true,
  "daysUntilBreak": 0
}
```

**Описание полей:**
- `currentStreak`: Количество последовательных дней с завершенными задачами
- `longestStreak`: Историческая самая длинная серия
- `streakStartDate`: Когда началась текущая серия
- `streakEndDate`: Когда серия закончилась (null если активна)
- `isActive`: Активна ли серия в данный момент
- `daysUntilBreak`: Дней с последнего завершения (0 если активна)

---

## Обработка ошибок

### Стандартный формат ответа при ошибке

Все ошибки следуют этой структуре:

```json
{
  "message": "Читаемое человеком сообщение об ошибке",
  "code": "ERROR_CODE",
  "errors": [],
  "timestamp": "2025-01-18T16:00:00+00:00"
}
```

### HTTP коды статуса

| Код | Название | Описание |
|------|------|-------------|
| 200 | OK | Запрос успешен |
| 201 | Created | Ресурс успешно создан |
| 204 | No Content | Запрос успешен, содержимое не возвращено |
| 400 | Bad Request | Некорректные параметры запроса или тело |
| 401 | Unauthorized | Отсутствует или невалидный JWT токен |
| 403 | Forbidden | Аутентифицирован, но не авторизован |
| 404 | Not Found | Ресурс не найден |
| 409 | Conflict | Конфликт ресурсов (например, дублирование имени) |
| 422 | Unprocessable Entity | Ошибка валидации |
| 429 | Too Many Requests | Превышен лимит частоты запросов |
| 500 | Internal Server Error | Ошибка сервера |

### Частые ответы с ошибками

**401 Не авторизован (Отсутствует токен):**
```json
{
  "message": "JWT Token not found",
  "code": "JWT_MISSING"
}
```

**401 Не авторизован (Невалидный токен):**
```json
{
  "message": "Invalid JWT Token",
  "code": "JWT_INVALID"
}
```

**401 Не авторизован (Истекший токен):**
```json
{
  "message": "Expired JWT Token",
  "code": "JWT_EXPIRED"
}
```

**403 Запрещено:**
```json
{
  "message": "Access denied. You do not own this resource.",
  "code": "ACCESS_DENIED"
}
```

**404 Не найдено:**
```json
{
  "message": "Resource not found",
  "code": "NOT_FOUND"
}
```

**422 Ошибка валидации:**
```json
{
  "message": "Validation failed",
  "code": "VALIDATION_ERROR",
  "violations": [
    {
      "propertyPath": "title",
      "message": "This value should not be blank.",
      "invalidValue": ""
    },
    {
      "propertyPath": "dueDate",
      "message": "Due date must be after start date.",
      "invalidValue": "2025-01-01"
    }
  ]
}
```

**429 Лимит частоты запросов:**
```json
{
  "message": "Rate limit exceeded. Please try again later.",
  "code": "RATE_LIMIT_EXCEEDED",
  "retryAfter": 60
}
```

**500 Внутренняя ошибка сервера:**
```json
{
  "message": "An unexpected error occurred. Please try again later.",
  "code": "INTERNAL_ERROR",
  "timestamp": "2025-01-18T16:00:00+00:00"
}
```

---

## Ограничение частоты запросов

**Текущие лимиты:**
- Неаутентифицированные запросы: 60 запросов в час
- Аутентифицированные запросы: 1000 запросов в час
- Эндпоинты аналитики: 100 запросов в час

**Заголовки:**
```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 995
X-RateLimit-Reset: 1642521600
```

**При ограничении частоты:**
```http
HTTP/1.1 429 Too Many Requests
Retry-After: 3600

{
  "message": "Rate limit exceeded",
  "retryAfter": 3600
}
```

---

## Пагинация

Эндпоинты, возвращающие списки, поддерживают пагинацию:

**Параметры запроса:**
- `page`: Номер страницы (по умолчанию: 1)
- `limit`: Элементов на странице (по умолчанию: 20, макс: 100)

**Формат ответа:**
```json
{
  "data": [...],
  "total": 150,
  "page": 1,
  "limit": 20,
  "pages": 8
}
```

---

## Формат даты/времени

Все даты используют формат ISO 8601 с временной зоной:

```
2025-01-18T16:45:00+00:00
```

**Временная зона:** Все временные метки в UTC

**Парсинг:** Используйте `new Date()` в JavaScript или `DateTimeImmutable` в PHP

---

## Фильтрация и поиск

**Формат строки запроса:**
```
GET /api/tasks?status=PENDING&priority=HIGH&tags[]=1&tags[]=2&search=meeting
```

**Параметры массивов:**
```
tags[]=1&tags[]=2&tags[]=3
priorities[]=HIGH&priorities[]=URGENT
```

**Диапазоны дат:**
```
dateFrom=2025-01-01&dateTo=2025-01-31
```
