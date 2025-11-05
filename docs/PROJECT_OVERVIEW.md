# 🎯 Project Overview - Task Manager

> **TL;DR**: Modern SPA task management application with unlimited subtask nesting, calendar integration, analytics, and enterprise-grade caching system.

---

## Table of Contents

- [What is Task Manager?](#what-is-task-manager)
- [Core Features](#core-features)
- [Business Requirements](#business-requirements)
- [User Workflows](#user-workflows)
- [System Capabilities](#system-capabilities)
- [Performance Goals](#performance-goals)

---

## What is Task Manager?

**Task Manager** is a full-featured, production-ready Single Page Application (SPA) for personal and team productivity. It combines the simplicity of a to-do list with the power of project management tools.

### Vision

Create a lightning-fast task management system that:
- **Feels instant** (optimistic UI, sub-millisecond cache responses)
- **Scales infinitely** (unlimited subtask nesting)
- **Provides insights** (analytics, productivity tracking)
- **Works everywhere** (mobile-first responsive design)

### Target Users

- **Individual Users**: Personal task management, goal tracking
- **Small Teams**: Collaborative project planning
- **Power Users**: Complex workflows with nested tasks
- **Analytics Enthusiasts**: Data-driven productivity insights

---

## Core Features

### 1. Task Management

#### Create & Organize Tasks
```
Task Properties:
├── Basic Info
│   ├── Title (required)
│   ├── Description (optional, rich text)
│   ├── Status (Pending, In Progress, Completed, Cancelled)
│   └── Priority (Low, Medium, High, Urgent)
├── Timing
│   ├── Start Date (optional)
│   ├── Due Date (optional)
│   └── Completion Date (auto-set)
├── Organization
│   ├── Tags (multiple, custom colors)
│   ├── Parent Task (for nesting)
│   └── Sort Order (drag & drop)
└── Metadata
    ├── Created At
    ├── Updated At
    ├── Completion Progress (%)
    └── Overdue Status (boolean)
```

#### Unlimited Subtask Nesting
```
Project
├── Phase 1
│   ├── Task 1.1
│   │   ├── Subtask 1.1.1
│   │   │   └── Sub-subtask 1.1.1.1
│   │   └── Subtask 1.1.2
│   └── Task 1.2
└── Phase 2
    └── Task 2.1
        ├── Subtask 2.1.1
        └── Subtask 2.1.2
```

**Example Use Case:**
```
"Launch Website" (Parent Task)
├── "Design" (Subtask Level 1)
│   ├── "Create Wireframes" (Subtask Level 2)
│   ├── "Design Mockups" (Subtask Level 2)
│   └── "Get Client Approval" (Subtask Level 2)
├── "Development" (Subtask Level 1)
│   ├── "Frontend" (Subtask Level 2)
│   │   ├── "Vue.js Setup" (Subtask Level 3)
│   │   ├── "Components" (Subtask Level 3)
│   │   └── "Styling" (Subtask Level 3)
│   └── "Backend" (Subtask Level 2)
│       ├── "API Setup" (Subtask Level 3)
│       └── "Database" (Subtask Level 3)
└── "Deployment" (Subtask Level 1)
    ├── "Configure Server" (Subtask Level 2)
    └── "DNS Setup" (Subtask Level 2)
```

### 2. Calendar Integration

#### Views Available
- **Monthly View**: Full month grid with task indicators
- **Weekly View**: 7-day detailed view
- **Daily View**: Focused single-day task list

#### Calendar Features
```typescript
// Tasks are displayed on calendar:
- Due date → Task appears on that day
- Start date → Task highlighted from start
- Overdue → Red background on past dates
- Completed → Strikethrough text
- Multi-day → Spanning indicator
```

**Visual Indicators:**
```
[Jan 15] ● ● ●    (3 tasks due)
[Jan 16] ●● ●● ●  (5 tasks: 2 high priority)
[Jan 17] ✓ ✓      (2 completed)
[Jan 18] ⚠        (1 overdue)
```

### 3. Tag System

#### Custom Tags
```typescript
Tag {
  id: number
  name: string        // "Work", "Personal", "Urgent"
  color: string       // Hex color: "#3b82f6"
  usageCount: number  // Auto-calculated
  userId: number      // Owner
}
```

#### Tag Features
- **Unlimited tags per task**
- **Custom colors** (color picker)
- **Auto-suggestions** (based on usage frequency)
- **Quick filters** (click tag to filter)
- **Popular tags sidebar** (top 7 most used)

**Example Tags:**
```
🔴 Urgent      (#dc2626)
🔵 Work        (#3b82f6)
🟢 Personal    (#10b981)
🟡 Finance     (#f59e0b)
🟣 Health      (#8b5cf6)
📚 Study       (#6366f1)
```

### 4. Advanced Search & Filtering

#### Filter Options
```typescript
Filters {
  status?: TaskStatus            // Pending, In Progress, Completed
  priority?: TaskPriority        // Low, Medium, High, Urgent
  tags?: string[]                // ["Work", "Urgent"]
  search?: string                // Searches title + description
  dateRange?: {
    from: Date
    to: Date
  }
  isOverdue?: boolean            // Only overdue tasks
  hasSubtasks?: boolean          // Only parent tasks
}
```

#### Quick Filters (Sidebar)
```
📋 All Tasks       (total count)
📅 Today           (due today)
📈 Upcoming        (next 7 days)
⚠️ Overdue         (past due date)
📭 No Due Date     (without deadline)
```

### 5. Statistics & Analytics

#### Overview Statistics
```typescript
Statistics {
  total: number                  // Total tasks
  pending: number                // Awaiting completion
  inProgress: number             // Currently working on
  completed: number              // Finished tasks
  cancelled: number              // Cancelled tasks
  overdue: number                // Past due date
  completionRate: number         // % completed (0-100)
  averageCompletionTime: number  // In hours
}
```

#### Analytics Dashboard (9 Cached Endpoints)

**1. Overview Statistics**
- Total tasks by status
- Completion rate %
- Average completion time
- Tasks created vs completed

**2. Completion Timeline**
```
Graph showing tasks completed over time:
[Day 1]  ████████ 8 tasks
[Day 2]  ██████ 6 tasks
[Day 3]  ████████████ 12 tasks
[Day 4]  ██ 2 tasks
```

**3. Status Distribution**
```
Pie Chart:
- Pending: 35%
- In Progress: 25%
- Completed: 30%
- Cancelled: 10%
```

**4. Priority Breakdown**
```
Bar Chart:
[Low]     ████████ 40%
[Medium]  ████████████ 60%
[High]    ████████ 40%
[Urgent]  ████ 20%
```

**5. Productivity Heatmap**
```
Calendar heatmap showing completed tasks per day:
[Jan 1]  ■■■□□ (3 completed)
[Jan 2]  ■■■■■ (5 completed)
[Jan 3]  ■□□□□ (1 completed)
```

**6. Weekday Productivity**
```
Which days you complete most tasks:
Mon  ████████ 15%
Tue  ████████████ 22%
Wed  ██████ 10%
Thu  ████████████ 20%
Fri  ████████████████ 28%
Sat  ██ 3%
Sun  ██ 2%
```

**7. Top Tags**
```
Most frequently used tags:
1. Work        (245 tasks) ████████████████
2. Personal    (189 tasks) ████████████
3. Urgent      (156 tasks) ██████████
4. Finance     (98 tasks)  ██████
5. Study       (67 tasks)  ████
```

**8. Insights (AI-like)**
```
"You're most productive on Tuesdays and Fridays!"
"Your average completion time is 3.2 days"
"You have 15 overdue tasks - consider prioritizing them"
"Great job! You completed 85% of tasks this week"
```

**9. Streak Tracking**
```
Current Streak: 🔥 7 days
Longest Streak: 🏆 21 days
Total Completed This Month: 127 tasks
```

### 6. Multi-language Support

#### Supported Languages
- 🇷🇺 **Russian** (primary)
- 🇬🇧 **English** (secondary)

#### i18n Implementation
```typescript
// All UI text is translated:
$t('task.createButton')      // "Создать задачу" / "Create Task"
$t('task.status.pending')    // "В ожидании" / "Pending"
$t('analytics.overview')     // "Обзор" / "Overview"

// Date formatting respects locale
formatDate(date, locale)     // "15 января 2025" / "January 15, 2025"
```

### 7. Mobile-First Design

#### Responsive Breakpoints
```css
Mobile:  < 768px   (single column, touch-optimized)
Tablet:  768-1024px (2 columns, adapted UI)
Desktop: > 1024px  (3 columns, full features)
```

#### Touch Gestures
- **Swipe left** on task → Delete action
- **Swipe right** on task → Complete action
- **Long press** → Open context menu
- **Pinch zoom** → Calendar month/week toggle

### 8. Authentication & Security

#### Google OAuth2
```
User clicks "Continue with Google"
→ Google One Tap UI appears
→ User selects account
→ Google returns ID token (JWT)
→ Backend validates with Google API
→ Backend creates/finds user in database
→ Backend returns JWT + Refresh token
→ User is authenticated
```

#### JWT Tokens
```typescript
Access Token:
- Lifetime: 30 minutes
- Usage: API authentication
- Storage: localStorage

Refresh Token:
- Lifetime: 7 days
- Usage: Get new access token
- Storage: Database (secure)
```

---

## Business Requirements

### Functional Requirements

#### Must Have (Implemented ✅)
- [x] Create/Read/Update/Delete tasks
- [x] Unlimited subtask nesting
- [x] Tag system with custom colors
- [x] Calendar views (month/week/day)
- [x] Advanced filtering
- [x] Statistics dashboard
- [x] Google OAuth authentication
- [x] Multi-language support (RU/EN)
- [x] Mobile responsive design
- [x] Optimistic UI updates

#### Should Have (Implemented ✅)
- [x] Drag & drop task reordering
- [x] Task search
- [x] Productivity analytics (9 endpoints)
- [x] Completion streak tracking
- [x] Overdue task highlighting
- [x] Tag auto-suggestions
- [x] Toast notifications

#### Nice to Have (Future)
- [ ] Team collaboration (share tasks)
- [ ] Real-time sync (WebSockets)
- [ ] Task templates
- [ ] Recurring tasks
- [ ] File attachments
- [ ] Task comments
- [ ] Email notifications
- [ ] Dark mode

### Non-Functional Requirements

#### Performance
```
API Response Times (with cache):
- GET /api/tasks          → < 1ms (0.5ms average)
- GET /api/analytics/*    → < 1ms (0.19-0.54ms average)
- POST /api/tasks         → < 50ms
- PUT /api/tasks/{id}     → < 50ms

Frontend Performance:
- Initial Load: < 2s
- Route Navigation: < 100ms
- Task List Render: < 50ms (100 tasks)
- Optimistic Update: Instant (0ms perceived latency)

Cache Hit Rate:
- Target: > 90%
- Actual: ~95% (production measurements)
```

#### Scalability
```
Database:
- Support: 100,000+ tasks per user
- Indexing: All foreign keys + frequently queried fields
- Query optimization: Eager loading, selective fetching

Redis Cache:
- Memory: ~1-2 KB per task (optimized DTOs)
- Eviction: LRU (Least Recently Used)
- Pattern: Separate keys per user (no cross-contamination)

Frontend:
- Virtual scrolling: 10,000+ tasks in list
- Lazy loading: Routes code-split
- Debouncing: Search input (300ms)
```

#### Security
```
Authentication:
- JWT with RS256 algorithm
- Token expiration enforced
- Refresh token rotation
- Google OAuth2 verified

Authorization:
- User can only access own tasks
- Voters for complex permissions
- SQL injection prevention (parameterized queries)
- XSS prevention (sanitized inputs)

Data Protection:
- HTTPS only (production)
- CORS configured (only allowed origins)
- No sensitive data in localStorage
- Password hashing (bcrypt, cost 13)
```

---

## User Workflows

### Workflow 1: Creating a Project with Subtasks

```
User Story:
"As a user, I want to break down a large project into manageable subtasks"

Steps:
1. Click "Create Task" button
2. Enter title: "Build Website"
3. Set priority: High
4. Add tags: Work, Urgent
5. Set due date: 2 weeks from now
6. Click "Save"
   → Task appears in list
7. Click on task to open sidebar
8. Scroll to "Subtasks" section
9. Enter "Design Mockups" → Press Enter
   → Subtask created
10. Repeat for "Frontend Dev", "Backend API", "Testing"
11. Click on "Frontend Dev" subtask
12. Add nested subtasks: "Components", "Styling", "Routing"
    → Unlimited nesting supported
```

### Workflow 2: Daily Task Management

```
User Story:
"As a user, I want to see my tasks for today and mark them complete"

Steps:
1. Open app → Dashboard loads
2. Click "Today" filter in sidebar
   → Only today's tasks shown (cached, instant)
3. See task "Morning Workout"
4. Click checkbox to mark complete
   → Optimistic update (instant visual feedback)
   → API call in background
   → Success toast appears: "Task completed!"
   → Statistics update automatically
5. Task moves to "Completed" section
6. Completion streak updates: "🔥 7 days"
```

### Workflow 3: Viewing Analytics

```
User Story:
"As a user, I want to see my productivity trends"

Steps:
1. Click "Analytics" tab in navigation
   → Dashboard loads (cached, < 1ms)
2. See overview statistics:
   - Total tasks: 127
   - Completed: 85 (67%)
   - Average completion time: 3.2 days
3. Scroll to "Completion Timeline"
   → Graph shows tasks completed over last 30 days
4. Check "Weekday Productivity"
   → "You're most productive on Fridays!"
5. View "Top Tags"
   → "Work" used in 245 tasks
6. Read insights:
   → "Great job! You completed 85% of tasks this week"
```

### Workflow 4: Filtering & Search

```
User Story:
"As a user, I want to find all overdue work tasks"

Steps:
1. Click "Filters" button
2. Advanced filter modal opens
3. Select status: "All"
4. Select priority: "All"
5. Select tags: "Work"
6. Toggle "Only overdue" → ON
7. Click "Apply Filters"
   → List updates (filtered tasks shown)
   → URL updates: ?status=all&tags=Work&overdue=true
8. See 12 overdue work tasks
9. Decide to postpone or complete
```

### Workflow 5: Calendar Planning

```
User Story:
"As a user, I want to visualize my tasks on a calendar"

Steps:
1. Click "Calendar" tab
2. Monthly view loads
3. See today highlighted
4. January 15: 3 task indicators (●●●)
5. Click on January 15
   → Day view opens with task list
6. See tasks:
   - "Morning Workout" (completed ✓)
   - "Client Meeting" (2:00 PM)
   - "Review PRs" (pending)
7. Click "Client Meeting"
   → Sidebar opens with details
8. Mark as complete
9. Return to month view
   → January 15 now shows (✓✓●)
```

---

## System Capabilities

### What This System CAN Do

#### Task Management
✅ Create unlimited tasks
✅ Nest subtasks infinitely (no depth limit)
✅ Bulk operations (multi-select, mass update)
✅ Drag & drop reordering
✅ Quick actions (toggle complete, delete)
✅ Duplicate tasks
✅ Archive tasks (soft delete)

#### Organization
✅ Custom tags with colors
✅ Multiple tags per task
✅ Tag-based filtering
✅ Auto-tag suggestions
✅ Sort by: date, priority, status, title
✅ Group by: date, status, tag

#### Time Management
✅ Set start dates
✅ Set due dates
✅ Track completion time
✅ Identify overdue tasks
✅ Calendar integration
✅ Today/Upcoming quick filters

#### Analytics
✅ Real-time statistics
✅ Completion trends (timeline)
✅ Status distribution
✅ Priority breakdown
✅ Productivity heatmap
✅ Weekday patterns
✅ Top tags analysis
✅ Streak tracking
✅ Personalized insights

#### Performance
✅ Sub-millisecond API responses (cache)
✅ Optimistic UI updates (instant feedback)
✅ Virtual scrolling (handle 10,000+ tasks)
✅ Debounced search
✅ Code splitting (fast initial load)
✅ 90%+ cache hit rate

#### User Experience
✅ Mobile-first responsive
✅ Touch gestures support
✅ Keyboard shortcuts
✅ Toast notifications
✅ Loading states
✅ Error handling
✅ Offline detection
✅ Multi-language (RU/EN)

### What This System CANNOT Do (Yet)

#### Collaboration
❌ Share tasks with other users
❌ Assign tasks to team members
❌ Comments/discussions on tasks
❌ Real-time multi-user sync
❌ Task permissions (view/edit)

#### Advanced Features
❌ Recurring tasks (daily/weekly/monthly)
❌ Task templates (reusable)
❌ File attachments
❌ Task dependencies (A blocks B)
❌ Gantt chart view
❌ Kanban board view
❌ Time tracking (hours spent)

#### Notifications
❌ Email notifications
❌ Push notifications
❌ Reminders (before due date)
❌ Webhook integrations

#### Integrations
❌ Google Calendar sync
❌ Import from Trello/Asana/Jira
❌ Export to CSV/PDF
❌ API for third-party apps

---

## Performance Goals

### Current Performance (Achieved ✅)

#### API Endpoints
```
GET /api/tasks
  Without cache: ~100ms
  With cache:    ~0.5ms       ✅ 200x improvement

GET /api/analytics/overview
  Without cache: ~35ms
  With cache:    ~0.24ms      ✅ 150x improvement

GET /api/analytics/dashboard
  Without cache: ~134ms
  With cache:    ~0.19ms      ✅ 714x improvement
```

#### User Actions
```
Toggle task complete:
  Optimistic update: 0ms (instant)
  API call:          6-10ms
  Cache update:      15-35ms
  Total:             ~35ms    ✅ Imperceptible delay

Create new task:
  Form submit:       0ms (instant)
  API call:          15-25ms
  Cache update:      17-40ms
  Total:             ~40ms    ✅ Very fast

Update task:
  Optimistic update: 0ms (instant)
  API call:          20-35ms
  Cache update:      25-50ms
  Total:             ~50ms    ✅ Fast enough
```

#### Frontend Performance
```
Initial Load:
  First Contentful Paint: ~800ms
  Time to Interactive:    ~1.2s    ✅ Under 2s goal

Route Navigation:
  Vue Router:            ~50ms     ✅ Under 100ms goal
  Component render:      ~30ms     ✅ Fast

Task List Render (100 tasks):
  Initial render:        ~45ms     ✅ Under 50ms goal
  Re-render (filter):    ~20ms     ✅ Very fast
  Virtual scroll:        Handles 10,000+ ✅
```

### Future Performance Goals (Next Phase)

#### Backend
- [ ] API response time: < 0.1ms (cache warmup optimization)
- [ ] Database query time: < 5ms (query optimization, indexes)
- [ ] Concurrent users: 1000+ (horizontal scaling)

#### Frontend
- [ ] Initial load: < 1s (bundle optimization, lazy loading)
- [ ] Lighthouse score: > 95 (PWA, performance, accessibility)
- [ ] Memory usage: < 50MB (component lifecycle optimization)

#### Infrastructure
- [ ] Cache hit rate: > 98% (improved TTL, preemptive warming)
- [ ] Uptime: 99.9% (redundancy, health checks)
- [ ] Error rate: < 0.1% (robust error handling)

---

## Related Documents

### Must Read Next
- **[Tech Stack](TECH_STACK.md)** - Technologies powering this system
- **[Architecture](backend/ARCHITECTURE.md)** - How the system is designed

### For Development
- **[Coding Standards](CODING_STANDARDS.md)** - How to write code
- **[Cache System](backend/CACHE_SYSTEM.md)** - Understanding caching

### For Reference
- **[API Reference](backend/API_REFERENCE.md)** - All endpoints documented
- **[Troubleshooting](guides/TROUBLESHOOTING.md)** - Common issues

---

*This project is in active development. Features and capabilities are continuously evolving.*
