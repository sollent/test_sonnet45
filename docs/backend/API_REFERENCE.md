# API Reference - Complete Documentation

## Table of Contents
1. [Authentication](#authentication)
2. [Tasks](#tasks)
3. [Tags](#tags)
4. [Analytics](#analytics)
5. [Error Handling](#error-handling)
6. [Rate Limiting](#rate-limiting)

---

## Base URL

```
Development: http://localhost:8089/api
Production:  https://your-domain.com/api
```

## Authentication

All API endpoints (except registration and login) require JWT authentication.

**Header Format**:
```http
Authorization: Bearer <jwt_token>
```

---

### POST /api/register

Register a new user account.

**Authentication:** Not required

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "securePassword123",
  "name": "John Doe"
}
```

**Response 201 (Created):**
```json
{
  "id": 42,
  "email": "user@example.com",
  "name": "John Doe",
  "createdAt": "2025-01-15T10:00:00+00:00"
}
```

**Error 400 (Validation Failed):**
```json
{
  "errors": [
    "Email is already registered",
    "Password must be at least 8 characters"
  ]
}
```

**Error 422 (Invalid Input):**
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

Authenticate user and receive JWT token.

**Authentication:** Not required

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "securePassword123"
}
```

**Response 200 (Success):**
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

**Error 401 (Invalid Credentials):**
```json
{
  "message": "Invalid credentials"
}
```

---

### POST /api/token/refresh

Refresh JWT token using refresh token.

**Authentication:** Not required (uses refresh token)

**Request Body:**
```json
{
  "refreshToken": "def50200a1b2c3d4e5f6..."
}
```

**Response 200 (Success):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refreshToken": "def50200a1b2c3d4e5f6..."
}
```

**Error 401 (Invalid Refresh Token):**
```json
{
  "message": "Invalid refresh token"
}
```

---

### POST /api/logout

Logout user (invalidate refresh token).

**Authentication:** Required (JWT)

**Request Body:**
```json
{
  "refreshToken": "def50200a1b2c3d4e5f6..."
}
```

**Response 204 (No Content)**

---

### GET /api/users/me

Get current authenticated user profile.

**Authentication:** Required (JWT)

**Response 200 (Success):**
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

**Error 401 (Unauthorized):**
```json
{
  "message": "JWT Token not found"
}
```

---

## Tasks

### GET /api/tasks

Get list of tasks with optional filters.

**Authentication:** Required (JWT)

**Query Parameters:**
- `view` (optional): Filter by view type
  - Values: `today`, `overdue`, `upcoming`, `all`, `unscheduled`
  - Default: `all`
- `search` (optional): Search in title/description
  - Type: `string`
  - Example: `search=meeting`
- `tags` (optional): Filter by tag IDs
  - Type: `integer[]`
  - Example: `tags[]=1&tags[]=2`
- `completed` (optional): Filter by completion status
  - Type: `boolean`
  - Example: `completed=true`
- `dateFrom` (optional): Filter tasks starting from date
  - Type: `string` (format: `YYYY-MM-DD`)
  - Example: `dateFrom=2025-01-01`
- `dateTo` (optional): Filter tasks ending at date
  - Type: `string` (format: `YYYY-MM-DD`)
  - Example: `dateTo=2025-12-31`
- `priorities` (optional): Filter by priority levels
  - Type: `string[]`
  - Values: `LOW`, `MEDIUM`, `HIGH`, `URGENT`
  - Example: `priorities[]=HIGH&priorities[]=URGENT`
- `statuses` (optional): Filter by task statuses
  - Type: `string[]`
  - Values: `PENDING`, `IN_PROGRESS`, `COMPLETED`, `CANCELLED`
  - Example: `statuses[]=PENDING&statuses[]=IN_PROGRESS`

**Response 200 (Success):**
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

**Error 401 (Unauthorized):**
```json
{
  "message": "JWT Token not found"
}
```

**Error 400 (Invalid Parameters):**
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

Get single task with full details including subtasks.

**Authentication:** Required (JWT)

**Path Parameters:**
- `id` (required): Task ID
  - Type: `integer`

**Response 200 (Success):**
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

**Error 404 (Not Found):**
```json
{
  "message": "Task not found"
}
```

**Error 403 (Forbidden):**
```json
{
  "message": "Access denied. You do not own this task."
}
```

---

### POST /api/tasks

Create a new task.

**Authentication:** Required (JWT)

**Request Body:**
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

**Required Fields:**
- `title` (string, max 255 characters)

**Optional Fields:**
- `description` (string, nullable)
- `status` (enum: `PENDING`, `IN_PROGRESS`, `COMPLETED`, `CANCELLED`, default: `PENDING`)
- `priority` (enum: `LOW`, `MEDIUM`, `HIGH`, `URGENT`, default: `MEDIUM`)
- `startDate` (ISO 8601 datetime, nullable)
- `dueDate` (ISO 8601 datetime, nullable)
- `parentTaskId` (integer, nullable)
- `tagIds` (integer array, nullable)
- `sortOrder` (integer, default: 0)

**Response 201 (Created):**
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

**Error 400 (Validation Failed):**
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

**Error 404 (Parent Task Not Found):**
```json
{
  "message": "Parent task with ID 999 not found"
}
```

---

### PUT /api/tasks/{id}

Update existing task (full update).

**Authentication:** Required (JWT)

**Path Parameters:**
- `id` (required): Task ID

**Request Body:**
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

**Response 200 (Success):**
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

**Error 403 (Forbidden):**
```json
{
  "message": "Access denied. You do not own this task."
}
```

**Error 404 (Not Found):**
```json
{
  "message": "Task not found"
}
```

---

### DELETE /api/tasks/{id}

Delete task and all its subtasks.

**Authentication:** Required (JWT)

**Path Parameters:**
- `id` (required): Task ID

**Response 204 (No Content)**

**Error 403 (Forbidden):**
```json
{
  "message": "Access denied. You do not own this task."
}
```

**Error 404 (Not Found):**
```json
{
  "message": "Task not found"
}
```

---

### POST /api/tasks/{id}/toggle

Toggle task completion status (complete ↔ incomplete).

**Authentication:** Required (JWT)

**Path Parameters:**
- `id` (required): Task ID

**Response 200 (Success):**
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

**Behavior:**
- If task is `COMPLETED` → changes to previous status (default `IN_PROGRESS`)
- If task is not `COMPLETED` → changes to `COMPLETED`
- Sets/clears `completedAt` timestamp

---

### POST /api/tasks/{id}/complete

Mark task as completed.

**Authentication:** Required (JWT)

**Path Parameters:**
- `id` (required): Task ID

**Response 200 (Success):**
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

Archive task (hide from active lists).

**Authentication:** Required (JWT)

**Path Parameters:**
- `id` (required): Task ID

**Response 200 (Success):**
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

Unarchive task (restore to active lists).

**Authentication:** Required (JWT)

**Path Parameters:**
- `id` (required): Task ID

**Response 200 (Success):**
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

Get task statistics for current user.

**Authentication:** Required (JWT)

**Response 200 (Success):**
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

**Field Descriptions:**
- `total`: Total number of tasks (excluding archived)
- `pending`: Tasks with status `PENDING`
- `in_progress`: Tasks with status `IN_PROGRESS`
- `completed`: Tasks with status `COMPLETED`
- `cancelled`: Tasks with status `CANCELLED`
- `overdue`: Tasks past due date and not completed
- `dueToday`: Tasks due today
- `dueThisWeek`: Tasks due within 7 days
- `completionRate`: Percentage of completed tasks
- `avgCompletionTime`: Average days to complete tasks

---

### GET /api/tasks/overdue

Get paginated list of overdue tasks.

**Authentication:** Required (JWT)

**Query Parameters:**
- `page` (optional): Page number
  - Type: `integer`
  - Default: `1`
- `limit` (optional): Items per page
  - Type: `integer`
  - Default: `20`
  - Max: `100`

**Response 200 (Success):**
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

Get tasks for calendar week view.

**Authentication:** Required (JWT)

**Query Parameters:**
- `weekStart` (required): Week start date
  - Type: `string` (format: `YYYY-MM-DD`)
  - Example: `weekStart=2025-01-13` (Monday)
- `includeCompleted` (optional): Include completed tasks
  - Type: `boolean`
  - Default: `true`

**Response 200 (Success):**
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

Get tasks for calendar month view.

**Authentication:** Required (JWT)

**Query Parameters:**
- `year` (required): Year
  - Type: `integer`
  - Example: `year=2025`
- `month` (required): Month (1-12)
  - Type: `integer`
  - Example: `month=1`
- `includeCompleted` (optional): Include completed tasks
  - Type: `boolean`
  - Default: `true`

**Response 200 (Success):**
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

Get tasks for specific day.

**Authentication:** Required (JWT)

**Query Parameters:**
- `date` (required): Date
  - Type: `string` (format: `YYYY-MM-DD`)
  - Example: `date=2025-01-15`
- `includeCompleted` (optional): Include completed tasks
  - Type: `boolean`
  - Default: `true`

**Response 200 (Success):**
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

Reorder tasks by providing new sort order.

**Authentication:** Required (JWT)

**Request Body:**
```json
{
  "taskIds": [125, 123, 124, 126]
}
```

**Response 204 (No Content)**

**Behavior:**
- Updates `sortOrder` field for each task based on array position
- Task at index 0 gets `sortOrder = 0`, index 1 gets `sortOrder = 1`, etc.

---

## Tags

### GET /api/tags

Get list of user's tags.

**Authentication:** Required (JWT)

**Query Parameters:**
- `search` (optional): Search tags by name
  - Type: `string`
  - Example: `search=work`
- `limit` (optional): Limit number of results
  - Type: `integer`
  - Example: `limit=10`

**Response 200 (Success):**
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

Get single tag details.

**Authentication:** Required (JWT)

**Path Parameters:**
- `id` (required): Tag ID

**Response 200 (Success):**
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

**Error 404 (Not Found):**
```json
{
  "message": "Tag not found"
}
```

---

### POST /api/tags

Create new tag.

**Authentication:** Required (JWT)

**Request Body:**
```json
{
  "name": "Urgent",
  "color": "#ef4444",
  "icon": "alert-circle"
}
```

**Required Fields:**
- `name` (string, max 50 characters)

**Optional Fields:**
- `color` (string, hex color, default: `#3B82F6`)
- `icon` (string, nullable)

**Response 201 (Created):**
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

**Error 409 (Conflict):**
```json
{
  "message": "Tag with this name already exists"
}
```

**Error 400 (Validation Failed):**
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

Update existing tag.

**Authentication:** Required (JWT)

**Path Parameters:**
- `id` (required): Tag ID

**Request Body:**
```json
{
  "name": "Super Urgent",
  "color": "#dc2626",
  "icon": "alert-triangle"
}
```

**Response 200 (Success):**
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

**Error 409 (Conflict):**
```json
{
  "message": "Tag with this name already exists"
}
```

---

### DELETE /api/tags/{id}

Delete tag (removes tag from all tasks).

**Authentication:** Required (JWT)

**Path Parameters:**
- `id` (required): Tag ID

**Response 204 (No Content)**

**Error 403 (Forbidden):**
```json
{
  "message": "Access denied. You do not own this tag."
}
```

---

### GET /api/tags/most-used

Get most frequently used tags.

**Authentication:** Required (JWT)

**Query Parameters:**
- `limit` (optional): Number of tags to return
  - Type: `integer`
  - Default: `5`
  - Max: `20`

**Response 200 (Success):**
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

## Analytics

All analytics endpoints are cached with INVALIDATE strategy. Cache is cleared when tasks are created/updated/deleted.

### GET /api/analytics/overview

Get analytics overview with key metrics.

**Authentication:** Required (JWT)

**Response 200 (Success):**
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

Get complete analytics dashboard data (combines multiple endpoints).

**Authentication:** Required (JWT)

**Query Parameters:**
- `period` (optional): Timeline period in days
  - Type: `integer`
  - Default: `30`
  - Example: `period=90`
- `dateFrom` (optional): Timeline start date
  - Type: `string` (format: `YYYY-MM-DD`)
  - Example: `dateFrom=2025-01-01`
- `dateTo` (optional): Timeline end date
  - Type: `string` (format: `YYYY-MM-DD`)
  - Example: `dateTo=2025-01-31`
- `year` (optional): Heatmap year
  - Type: `integer`
  - Default: current year
  - Example: `year=2025`

**Response 200 (Success):**
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

Get task completion timeline data.

**Authentication:** Required (JWT)

**Query Parameters:**
- `period` (optional): Number of days
  - Type: `integer`
  - Default: `30`
- `dateFrom` (optional): Start date (overrides period)
  - Type: `string` (format: `YYYY-MM-DD`)
- `dateTo` (optional): End date (required with dateFrom)
  - Type: `string` (format: `YYYY-MM-DD`)

**Response 200 (Success):**
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

Get task count distribution by status.

**Authentication:** Required (JWT)

**Response 200 (Success):**
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

Get task statistics grouped by priority.

**Authentication:** Required (JWT)

**Response 200 (Success):**
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

Get GitHub-style productivity heatmap (daily task completion counts).

**Authentication:** Required (JWT)

**Query Parameters:**
- `year` (optional): Year for heatmap
  - Type: `integer`
  - Default: current year
  - Example: `year=2025`

**Response 200 (Success):**
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

**Notes:**
- Returns all 365 days of the year
- Days with no completions have value `0`
- Most expensive analytics query (30 second TTL)

---

### GET /api/analytics/weekday-productivity

Get task completion statistics by day of week.

**Authentication:** Required (JWT)

**Response 200 (Success):**
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

Get most used tags with completion statistics.

**Authentication:** Required (JWT)

**Query Parameters:**
- `limit` (optional): Number of tags to return
  - Type: `integer`
  - Default: `5`
  - Max: `20`

**Response 200 (Success):**
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

Get AI-like insights and recommendations based on user data.

**Authentication:** Required (JWT)

**Response 200 (Success):**
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

Get current and longest task completion streak.

**Authentication:** Required (JWT)

**Response 200 (Success):**
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

**Field Descriptions:**
- `currentStreak`: Number of consecutive days with completed tasks
- `longestStreak`: Historical longest streak
- `streakStartDate`: When current streak started
- `streakEndDate`: When streak ended (null if active)
- `isActive`: Whether streak is currently active
- `daysUntilBreak`: Days since last completion (0 if active)

---

## Error Handling

### Standard Error Response Format

All errors follow this structure:

```json
{
  "message": "Human-readable error message",
  "code": "ERROR_CODE",
  "errors": [],
  "timestamp": "2025-01-18T16:00:00+00:00"
}
```

### HTTP Status Codes

| Code | Name | Description |
|------|------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 204 | No Content | Request successful, no content returned |
| 400 | Bad Request | Invalid request parameters or body |
| 401 | Unauthorized | Missing or invalid JWT token |
| 403 | Forbidden | Authenticated but not authorized |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Resource conflict (e.g., duplicate name) |
| 422 | Unprocessable Entity | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

### Common Error Responses

**401 Unauthorized (Missing Token):**
```json
{
  "message": "JWT Token not found",
  "code": "JWT_MISSING"
}
```

**401 Unauthorized (Invalid Token):**
```json
{
  "message": "Invalid JWT Token",
  "code": "JWT_INVALID"
}
```

**401 Unauthorized (Expired Token):**
```json
{
  "message": "Expired JWT Token",
  "code": "JWT_EXPIRED"
}
```

**403 Forbidden:**
```json
{
  "message": "Access denied. You do not own this resource.",
  "code": "ACCESS_DENIED"
}
```

**404 Not Found:**
```json
{
  "message": "Resource not found",
  "code": "NOT_FOUND"
}
```

**422 Validation Error:**
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

**429 Rate Limit:**
```json
{
  "message": "Rate limit exceeded. Please try again later.",
  "code": "RATE_LIMIT_EXCEEDED",
  "retryAfter": 60
}
```

**500 Internal Server Error:**
```json
{
  "message": "An unexpected error occurred. Please try again later.",
  "code": "INTERNAL_ERROR",
  "timestamp": "2025-01-18T16:00:00+00:00"
}
```

---

## Rate Limiting

**Current Limits:**
- Unauthenticated requests: 60 requests per hour
- Authenticated requests: 1000 requests per hour
- Analytics endpoints: 100 requests per hour

**Headers:**
```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 995
X-RateLimit-Reset: 1642521600
```

**When Rate Limited:**
```http
HTTP/1.1 429 Too Many Requests
Retry-After: 3600

{
  "message": "Rate limit exceeded",
  "retryAfter": 3600
}
```

---

## Pagination

Endpoints that return lists support pagination:

**Query Parameters:**
- `page`: Page number (default: 1)
- `limit`: Items per page (default: 20, max: 100)

**Response Format:**
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

## Date/Time Format

All dates use ISO 8601 format with timezone:

```
2025-01-18T16:45:00+00:00
```

**Timezone:** All timestamps in UTC

**Parsing:** Use `new Date()` in JavaScript or `DateTimeImmutable` in PHP

---

## Filtering & Searching

**Query String Format:**
```
GET /api/tasks?status=PENDING&priority=HIGH&tags[]=1&tags[]=2&search=meeting
```

**Array Parameters:**
```
tags[]=1&tags[]=2&tags[]=3
priorities[]=HIGH&priorities[]=URGENT
```

**Date Ranges:**
```
dateFrom=2025-01-01&dateTo=2025-01-31
```
