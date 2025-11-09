# 🧪 E2E Testing Plan - Task Manager Application

> **Comprehensive End-to-End Testing Strategy**  
> Complete plan for browser-based E2E tests covering all critical user flows

---

## 📋 Table of Contents

1. [Technology Stack](#technology-stack)
2. [Test Architecture](#test-architecture)
3. [Test Scenarios by Feature](#test-scenarios-by-feature)
4. [Implementation Phases](#implementation-phases)
5. [CI/CD Integration](#cicd-integration)

---

## 🛠️ Technology Stack

### Recommended: **Playwright** (Primary Choice)

**Why Playwright?**
- ✅ **Multi-browser support**: Chromium, Firefox, WebKit
- ✅ **Auto-waiting**: Built-in smart waiting for elements
- ✅ **Network interception**: Mock API responses, test offline scenarios
- ✅ **Screenshots & videos**: Automatic on failure
- ✅ **TypeScript support**: Native TypeScript support
- ✅ **Fast execution**: Parallel test execution
- ✅ **Great debugging**: Trace viewer, step-by-step debugging
- ✅ **Mobile emulation**: Test mobile views easily
- ✅ **Active development**: Microsoft-backed, actively maintained

**Alternative: Cypress**
- Good for Vue.js apps
- Real browser testing
- Time-travel debugging
- But: Single browser (Chromium), slower, less flexible

**Decision: Playwright** ✅

### Test Framework Structure

```
frontend/
├── e2e/
│   ├── tests/
│   │   ├── auth/
│   │   │   ├── login.spec.ts
│   │   │   ├── register.spec.ts
│   │   │   └── logout.spec.ts
│   │   ├── dashboard/
│   │   │   ├── task-list.spec.ts
│   │   │   ├── task-creation.spec.ts
│   │   │   ├── task-editing.spec.ts
│   │   │   ├── task-completion.spec.ts
│   │   │   └── filters.spec.ts
│   │   ├── calendar/
│   │   │   ├── calendar-view.spec.ts
│   │   │   └── calendar-navigation.spec.ts
│   │   ├── analytics/
│   │   │   └── analytics-charts.spec.ts
│   │   └── profile/
│   │       ├── profile-edit.spec.ts
│   │       └── profile-settings.spec.ts
│   ├── fixtures/
│   │   ├── auth.fixture.ts
│   │   ├── tasks.fixture.ts
│   │   └── test-data.fixture.ts
│   ├── page-objects/
│   │   ├── LoginPage.ts
│   │   ├── DashboardPage.ts
│   │   ├── TaskDialog.ts
│   │   ├── TaskDetailsSidebar.ts
│   │   ├── FiltersModal.ts
│   │   ├── CalendarPage.ts
│   │   ├── AnalyticsPage.ts
│   │   └── ProfilePage.ts
│   ├── utils/
│   │   ├── helpers.ts
│   │   ├── api-helpers.ts
│   │   └── test-data.ts
│   ├── playwright.config.ts
│   └── package.json
```

---

## 🏗️ Test Architecture

### Page Object Model (POM) Pattern

**Benefits:**
- Reusable page interactions
- Easy maintenance when UI changes
- Clear separation of concerns
- Type-safe selectors

**Example Structure:**
```typescript
// page-objects/DashboardPage.ts
export class DashboardPage {
  constructor(private page: Page) {}
  
  async clickCreateTask() { ... }
  async openFilters() { ... }
  async selectView(view: 'all' | 'today' | 'upcoming' | 'overdue' | 'unscheduled') { ... }
  async getTaskCount() { ... }
  async waitForTasksLoaded() { ... }
}
```

### Test Fixtures

**Purpose:** Reusable test setup (authentication, test data)

```typescript
// fixtures/auth.fixture.ts
export const testUser = {
  email: 'e2e-test@example.com',
  password: 'TestPassword123!'
}
```

---

## 📝 Test Scenarios by Feature

### 1. Authentication Flow

#### 1.1 Registration
**Priority: HIGH**

**Test Cases:**
1. **TC-AUTH-001**: Successful registration with valid data
   - Navigate to `/register`
   - Fill email, password, confirm password
   - Submit form
   - Verify redirect to `/dashboard`
   - Verify success toast message
   - Verify user is authenticated

2. **TC-AUTH-002**: Registration validation - empty fields
   - Try to submit empty form
   - Verify error messages for all required fields
   - Verify form is not submitted

3. **TC-AUTH-003**: Registration validation - invalid email
   - Enter invalid email formats (no @, no domain, etc.)
   - Verify email validation error
   - Verify form is not submitted

4. **TC-AUTH-004**: Registration validation - password mismatch
   - Enter different passwords in password and confirm password
   - Verify password mismatch error
   - Verify form is not submitted

5. **TC-AUTH-005**: Registration validation - weak password
   - Enter password < 6 characters
   - Verify password length validation
   - Verify form is not submitted

6. **TC-AUTH-006**: Registration - duplicate email
   - Try to register with existing email
   - Verify error message from backend
   - Verify form is not submitted

7. **TC-AUTH-007**: Registration - Google OAuth button presence
   - Verify Google login button is visible
   - Verify button is clickable (actual OAuth flow can be mocked)

#### 1.2 Login
**Priority: HIGH**

**Test Cases:**
1. **TC-AUTH-008**: Successful login with valid credentials
   - Navigate to `/login`
   - Fill email and password
   - Submit form
   - Verify redirect to `/dashboard`
   - Verify success toast message
   - Verify user is authenticated

2. **TC-AUTH-009**: Login validation - empty fields
   - Try to submit empty form
   - Verify error messages for email and password
   - Verify form is not submitted

3. **TC-AUTH-010**: Login validation - invalid email format
   - Enter invalid email
   - Verify email validation error
   - Verify form is not submitted

4. **TC-AUTH-011**: Login - incorrect credentials
   - Enter wrong email or password
   - Verify error message from backend
   - Verify form is not submitted
   - Verify user is not authenticated

5. **TC-AUTH-012**: Login - Google OAuth button
   - Verify Google login button is visible and clickable

#### 1.3 Logout
**Priority: MEDIUM**

**Test Cases:**
1. **TC-AUTH-013**: Successful logout
   - Login as test user
   - Click logout button
   - Verify redirect to landing page
   - Verify user is not authenticated
   - Verify tokens are cleared

---

### 2. Dashboard - Task List

#### 2.1 Initial Load
**Priority: HIGH**

**Test Cases:**
1. **TC-DASH-001**: Dashboard loads successfully
   - Login and navigate to `/dashboard`
   - Verify page title is correct
   - Verify no console errors
   - Verify no network errors (401, 500, etc.)

2. **TC-DASH-002**: Default view "Все задачи" loads tasks
   - Verify "Все задачи" is selected by default
   - Verify tasks are loaded and displayed
   - Verify task count matches API response
   - Verify no loading skeletons after load
   - Verify no error messages

3. **TC-DASH-003**: Tasks are grouped by date
   - Verify tasks are grouped correctly
   - Verify date headers are displayed
   - Verify progress bars are shown for each day

4. **TC-DASH-004**: Empty state when no tasks
   - Use test account with no tasks
   - Verify empty state message is displayed
   - Verify "Create task" button is visible

#### 2.2 View Navigation
**Priority: HIGH**

**Test Cases:**
1. **TC-DASH-005**: Navigate to "Сегодня" view
   - Click "Сегодня" in sidebar
   - Verify only today's tasks are displayed
   - Verify correct API request is made
   - Verify no errors

2. **TC-DASH-006**: Navigate to "Предстоящие" view
   - Click "Предстоящие"
   - Verify only upcoming tasks are displayed
   - Verify correct API request
   - Verify no errors

3. **TC-DASH-007**: Navigate to "Просроченные" view
   - Click "Просроченные"
   - Verify only overdue tasks are displayed
   - Verify correct API request
   - Verify no errors

4. **TC-DASH-008**: Navigate to "Без срока" view
   - Click "Без срока"
   - Verify only tasks without dates are displayed
   - Verify correct API request
   - Verify no errors

5. **TC-DASH-009**: Switch between views multiple times
   - Switch between all views multiple times
   - Verify no errors accumulate
   - Verify correct data is displayed each time

#### 2.3 Display Modes
**Priority: MEDIUM**

**Test Cases:**
1. **TC-DASH-010**: Switch to cards view
   - Click cards view button
   - Verify tasks are displayed as cards
   - Verify all task information is visible

2. **TC-DASH-011**: Switch to list view
   - Click list view button
   - Verify tasks are displayed as list items
   - Verify all task information is visible

3. **TC-DASH-012**: View mode persists
   - Switch to list view
   - Refresh page
   - Verify list view is still selected (if persisted)

---

### 3. Task Creation

#### 3.1 Basic Task Creation
**Priority: CRITICAL**

**Test Cases:**
1. **TC-CREATE-001**: Create task with minimal data (title only)
   - Click "Создать задачу" button
   - Enter task title
   - Click "Сохранить"
   - Verify task appears in list
   - Verify success toast
   - Verify task has default values (status: pending, priority: medium)

2. **TC-CREATE-002**: Create task with all fields
   - Open create dialog
   - Fill title, description
   - Select status (In Progress)
   - Select priority (High)
   - Select tags (multiple)
   - Set start date and due date
   - Click "Сохранить"
   - Verify task appears with all data
   - Verify all fields are displayed correctly

3. **TC-CREATE-003**: Create task validation - empty title
   - Try to submit without title
   - Verify validation error
   - Verify form is not submitted
   - Verify dialog stays open

4. **TC-CREATE-004**: Create task validation - title too long
   - Enter title > 255 characters
   - Verify validation error
   - Verify form is not submitted

5. **TC-CREATE-005**: Create task - cancel button
   - Fill form partially
   - Click "Отмена"
   - Verify dialog closes
   - Verify task is not created
   - Verify form is reset on next open

#### 3.2 Quick Date Selection
**Priority: HIGH**

**Test Cases:**
1. **TC-CREATE-006**: Create task with "Сегодня" quick date
   - Open create dialog
   - Click "Сегодня" button
   - Fill title and save
   - Verify task has today's date
   - Verify task appears in "Сегодня" view

2. **TC-CREATE-007**: Create task with "Завтра" quick date
   - Click "Завтра" button
   - Fill title and save
   - Verify task has tomorrow's date
   - Verify task appears in "Предстоящие" view

3. **TC-CREATE-008**: Create task with "Послезавтра" quick date
   - Click "Послезавтра" button
   - Fill title and save
   - Verify task has day after tomorrow's date

#### 3.3 Advanced Date Selection
**Priority: HIGH**

**Test Cases:**
1. **TC-CREATE-009**: Create task with custom date range
   - Click "Показать расширенные настройки"
   - Select start date and due date from calendar
   - Fill title and save
   - Verify task has correct dates
   - Verify dates are displayed correctly in task card

2. **TC-CREATE-010**: Create task - date validation (due before start)
   - Set due date before start date
   - Try to save
   - Verify validation error
   - Verify form is not submitted

#### 3.4 Tag Management
**Priority: HIGH**

**Test Cases:**
1. **TC-CREATE-011**: Add tags from popular tags
   - Open create dialog
   - Click on popular tags (Work, Personal, etc.)
   - Verify tags are added to form
   - Save task
   - Verify tags are displayed on task card

2. **TC-CREATE-012**: Add tags by typing
   - Type tag name in tag input
   - Press Enter
   - Verify tag is added
   - Save task
   - Verify tag is created and displayed

3. **TC-CREATE-013**: Add multiple tags
   - Add 3-4 tags (mix of popular and new)
   - Save task
   - Verify all tags are displayed
   - Verify tags are clickable

4. **TC-CREATE-014**: Remove tag before saving
   - Add tag
   - Click X on tag chip
   - Verify tag is removed
   - Save task
   - Verify removed tag is not in task

5. **TC-CREATE-015**: Search tags functionality
   - Type in tag search field
   - Verify search results appear
   - Click on search result
   - Verify tag is added

#### 3.5 Recurring Tasks
**Priority: MEDIUM**

**Test Cases:**
1. **TC-CREATE-016**: Create daily recurring task
   - Enable "Повторяющаяся задача" switch
   - Select "Ежедневно"
   - Fill title and save
   - Verify task is created
   - Verify recurrence rule is set

2. **TC-CREATE-017**: Create weekly recurring task
   - Enable recurrence
   - Select "Еженедельно"
   - Select days of week
   - Fill title and save
   - Verify recurrence rule is set correctly

3. **TC-CREATE-018**: Create monthly recurring task
   - Enable recurrence
   - Select "Ежемесячно"
   - Fill title and save
   - Verify recurrence rule is set

4. **TC-CREATE-019**: Create yearly recurring task
   - Enable recurrence
   - Select "Ежегодно"
   - Fill title and save
   - Verify recurrence rule is set

5. **TC-CREATE-020**: Create custom recurring task
   - Enable recurrence
   - Select "Произвольный"
   - Set custom interval
   - Fill title and save
   - Verify custom recurrence rule is set

#### 3.6 File Attachments
**Priority: MEDIUM**

**Test Cases:**
1. **TC-CREATE-021**: Upload single file
   - Open create dialog
   - Upload file via drag & drop
   - Verify file appears in attachments list
   - Save task
   - Verify file is attached to task

2. **TC-CREATE-022**: Upload multiple files
   - Upload 2-3 files
   - Verify all files appear
   - Save task
   - Verify all files are attached

3. **TC-CREATE-023**: File upload validation - file too large
   - Try to upload file > 10MB
   - Verify error message
   - Verify file is not uploaded

4. **TC-CREATE-024**: Remove file before saving
   - Upload file
   - Remove file
   - Save task
   - Verify file is not attached

---

### 4. Task Editing

#### 4.1 Open Task Details
**Priority: HIGH**

**Test Cases:**
1. **TC-EDIT-001**: Open task details sidebar
   - Click on task card in list
   - Verify sidebar opens from right
   - Verify task data is loaded correctly
   - Verify all fields are displayed

2. **TC-EDIT-002**: Close task details sidebar
   - Open task details
   - Click close button (X)
   - Verify sidebar closes
   - Verify no errors

3. **TC-EDIT-003**: Close sidebar by clicking outside
   - Open task details
   - Click outside sidebar
   - Verify sidebar closes

#### 4.2 Edit Task Fields
**Priority: CRITICAL**

**Test Cases:**
1. **TC-EDIT-004**: Edit task title
   - Open task details
   - Click edit button
   - Change title
   - Save changes
   - Verify title is updated in list
   - Verify title is updated in sidebar
   - Verify optimistic update (immediate UI change)

2. **TC-EDIT-005**: Edit task description
   - Edit description
   - Save changes
   - Verify description is updated
   - Verify description is displayed correctly

3. **TC-EDIT-006**: Change task status
   - Change status from "В ожидании" to "В процессе"
   - Save changes
   - Verify status is updated
   - Verify task appears in correct filter view

4. **TC-EDIT-007**: Change task priority
   - Change priority from "Средний" to "Срочный"
   - Save changes
   - Verify priority is updated
   - Verify priority indicator is updated in task card

5. **TC-EDIT-008**: Change task dates
   - Change start date and due date
   - Save changes
   - Verify dates are updated
   - Verify task appears in correct date group

6. **TC-EDIT-009**: Add tags to existing task
   - Open task details
   - Add new tags
   - Save changes
   - Verify tags are added
   - Verify tags appear on task card

7. **TC-EDIT-010**: Remove tags from task
   - Remove existing tags
   - Save changes
   - Verify tags are removed
   - Verify tags are removed from task card

8. **TC-EDIT-011**: Edit task - cancel changes
   - Make changes to task
   - Click cancel
   - Verify changes are not saved
   - Verify original data is still displayed

#### 4.3 Subtasks Management
**Priority: HIGH**

**Test Cases:**
1. **TC-EDIT-012**: Add subtask
   - Open task details
   - Enter subtask title
   - Press Enter or click add button
   - Verify subtask is added
   - Verify subtask count is updated
   - Verify parent task progress is recalculated

2. **TC-EDIT-013**: Complete subtask
   - Add subtask
   - Check subtask checkbox
   - Verify subtask is marked as completed
   - Verify parent task progress increases
   - Verify completed subtask count updates

3. **TC-EDIT-014**: Uncomplete subtask
   - Complete subtask
   - Uncheck subtask checkbox
   - Verify subtask is uncompleted
   - Verify parent task progress decreases

4. **TC-EDIT-015**: Edit subtask title
   - Add subtask
   - Click edit on subtask
   - Change title
   - Save
   - Verify subtask title is updated

5. **TC-EDIT-016**: Delete subtask
   - Add subtask
   - Delete subtask
   - Verify subtask is removed
   - Verify subtask count decreases
   - Verify parent progress is recalculated

6. **TC-EDIT-017**: Add nested subtask (subtask of subtask)
   - Add subtask
   - Open subtask details
   - Add subtask to subtask
   - Verify nested structure is created
   - Verify counts are correct

#### 4.4 Task Attachments in Edit Mode
**Priority: MEDIUM**

**Test Cases:**
1. **TC-EDIT-018**: Add file to existing task
   - Open task details
   - Upload file
   - Save changes
   - Verify file is attached
   - Verify file is downloadable

2. **TC-EDIT-019**: Remove file from task
   - Remove attached file
   - Save changes
   - Verify file is removed

3. **TC-EDIT-020**: Download attached file
   - Click on attached file
   - Verify file download starts
   - Verify file is correct

#### 4.5 Delete Task
**Priority: HIGH**

**Test Cases:**
1. **TC-EDIT-021**: Delete task with confirmation
   - Open task details
   - Click delete button
   - Confirm deletion in dialog
   - Verify task is removed from list
   - Verify sidebar closes
   - Verify success message

2. **TC-EDIT-022**: Cancel task deletion
   - Click delete button
   - Cancel in confirmation dialog
   - Verify task is not deleted
   - Verify sidebar stays open

3. **TC-EDIT-023**: Delete task with subtasks
   - Create task with subtasks
   - Delete parent task
   - Verify task and all subtasks are deleted

---

### 5. Task Completion

#### 5.1 Complete Task via Checkbox
**Priority: CRITICAL**

**Test Cases:**
1. **TC-COMPLETE-001**: Complete task from task card checkbox
   - Check checkbox on task card
   - Verify task is marked as completed immediately (optimistic update)
   - Verify task moves to completed section
   - Verify progress bar updates
   - Verify API call succeeds

2. **TC-COMPLETE-002**: Uncomplete task from checkbox
   - Uncheck completed task checkbox
   - Verify task is uncompleted immediately
   - Verify task moves back to active section
   - Verify progress bar updates

3. **TC-COMPLETE-003**: Complete task with subtasks
   - Complete parent task
   - Verify all subtasks are also completed
   - Verify progress shows 100%

#### 5.2 Complete Task via Sidebar
**Priority: HIGH**

**Test Cases:**
1. **TC-COMPLETE-004**: Complete task from sidebar button
   - Open task details
   - Click "Отметить как завершенную" button
   - Verify task is completed
   - Verify sidebar updates
   - Verify task card updates

2. **TC-COMPLETE-005**: Uncomplete task from sidebar
   - Open completed task
   - Click "Вернуть в невыполненные"
   - Verify task is uncompleted
   - Verify updates in UI

---

### 6. Filters

#### 6.1 Quick Filters
**Priority: HIGH**

**Test Cases:**
1. **TC-FILTER-001**: Apply "На сегодня" quick filter
   - Click "На сегодня" button
   - Verify only today's tasks are displayed
   - Verify button is highlighted
   - Verify correct API request

2. **TC-FILTER-002**: Apply "Срочные" quick filter
   - Click "Срочные" button
   - Verify only high/urgent priority tasks are displayed
   - Verify button is highlighted

3. **TC-FILTER-003**: Apply "Просроченные" quick filter
   - Click "Просроченные" button
   - Verify only overdue tasks are displayed

4. **TC-FILTER-004**: Apply "В процессе" quick filter
   - Click "В процессе" button
   - Verify only in-progress tasks are displayed

5. **TC-FILTER-005**: Apply multiple quick filters
   - Click multiple quick filter buttons
   - Verify combined filter is applied
   - Verify correct tasks are displayed

6. **TC-FILTER-006**: Clear quick filters
   - Apply filters
   - Click "Очистить фильтры" button
   - Verify all filters are cleared
   - Verify all tasks are displayed

#### 6.2 Advanced Filters Modal
**Priority: HIGH**

**Test Cases:**
1. **TC-FILTER-007**: Open filters modal
   - Click "Фильтры" button
   - Verify modal opens
   - Verify all filter sections are visible

2. **TC-FILTER-008**: Filter by task type - "Активные"
   - Select "Активные" in task type
   - Apply filters
   - Verify only active tasks are displayed
   - Verify completed tasks are hidden

3. **TC-FILTER-009**: Filter by task type - "Завершенные"
   - Select "Завершенные" in task type
   - Apply filters
   - Verify only completed tasks are displayed

4. **TC-FILTER-010**: Filter by priority - single
   - Select "Высокий" priority
   - Apply filters
   - Verify only high priority tasks are displayed

5. **TC-FILTER-011**: Filter by priority - multiple
   - Select multiple priorities (High, Urgent)
   - Apply filters
   - Verify tasks with selected priorities are displayed

6. **TC-FILTER-012**: Filter by status - single
   - Select "В процессе" status
   - Apply filters
   - Verify only in-progress tasks are displayed

7. **TC-FILTER-013**: Filter by status - multiple
   - Select multiple statuses
   - Apply filters
   - Verify tasks with selected statuses are displayed

8. **TC-FILTER-014**: Filter by tags - single
   - Select tag "Work"
   - Apply filters
   - Verify only tasks with "Work" tag are displayed

9. **TC-FILTER-015**: Filter by tags - multiple
   - Select multiple tags
   - Apply filters
   - Verify tasks with any of selected tags are displayed

10. **TC-FILTER-016**: Filter by date range
    - Select date range (dateFrom, dateTo)
    - Apply filters
    - Verify only tasks within date range are displayed
    - Verify date range filtering works correctly (boundaries)

11. **TC-FILTER-017**: Combined filters (priority + status + tags)
    - Select priority, status, and tags
    - Apply filters
    - Verify tasks match all criteria
    - Verify correct API request with all parameters

12. **TC-FILTER-018**: Clear all filters
    - Apply multiple filters
    - Click "Очистить" button
    - Verify all filters are cleared
    - Verify modal closes
    - Verify all tasks are displayed

#### 6.3 Filter Presets
**Priority: HIGH**

**Test Cases:**
1. **TC-FILTER-019**: Apply "Все задачи" preset
   - Click "Все задачи" preset
   - Verify all filters are cleared
   - Verify all tasks are displayed

2. **TC-FILTER-020**: Apply "Важные" preset
   - Click "Важные" preset
   - Verify "Высокий" and "Срочный" priorities are selected
   - Verify correct tasks are displayed
   - Verify preset button is highlighted

3. **TC-FILTER-021**: Apply "На этой неделе" preset
   - Click "На этой неделе" preset
   - Verify date range is set to current week
   - Verify correct tasks are displayed
   - Verify preset button is highlighted

4. **TC-FILTER-022**: Preset synchronization - "Важные"
   - Apply "Важные" preset
   - Manually select "Низкий" priority
   - Verify "Важные" preset is deselected
   - Verify filters are updated

5. **TC-FILTER-023**: Preset synchronization - "На этой неделе"
   - Apply "На этой неделе" preset
   - Manually change date range
   - Verify "На этой неделе" preset is deselected

6. **TC-FILTER-024**: Preset synchronization - "Все задачи"
   - Apply "Все задачи" preset
   - Manually select any filter
   - Verify "Все задачи" preset is deselected

#### 6.4 Tag Filtering from Sidebar
**Priority: MEDIUM**

**Test Cases:**
1. **TC-FILTER-025**: Filter by tag from sidebar
   - Click tag in "Часто используемые" section
   - Verify tasks are filtered by tag
   - Verify tag is highlighted

2. **TC-FILTER-026**: Multiple tag filters from sidebar
   - Click multiple tags
   - Verify combined filter is applied

---

### 7. Search

**Priority: HIGH**

**Test Cases:**
1. **TC-SEARCH-001**: Search tasks by title
   - Enter search query in search box
   - Verify tasks are filtered by title
   - Verify search is case-insensitive

2. **TC-SEARCH-002**: Search tasks by description
   - Enter text that matches task description
   - Verify matching tasks are displayed

3. **TC-SEARCH-003**: Search tasks by tag name
   - Enter tag name in search
   - Verify tasks with that tag are displayed

4. **TC-SEARCH-004**: Clear search
   - Enter search query
   - Clear search
   - Verify all tasks are displayed again

5. **TC-SEARCH-005**: Search with no results
   - Enter query that matches no tasks
   - Verify "No results" message is displayed

---

### 8. Calendar View

#### 8.1 Calendar Navigation
**Priority: HIGH**

**Test Cases:**
1. **TC-CAL-001**: Navigate to calendar page
   - Click "Календарь" in navigation
   - Verify calendar page loads
   - Verify no errors

2. **TC-CAL-002**: Switch to month view
   - Click "Месяц" button
   - Verify month calendar is displayed
   - Verify tasks are displayed on correct dates

3. **TC-CAL-003**: Switch to week view
   - Click "Неделя" button
   - Verify week calendar is displayed
   - Verify tasks are displayed correctly

4. **TC-CAL-004**: Navigate to previous month/week
   - Click previous button
   - Verify calendar navigates correctly
   - Verify tasks for that period are loaded

5. **TC-CAL-005**: Navigate to next month/week
   - Click next button
   - Verify calendar navigates correctly

6. **TC-CAL-006**: Navigate to today
   - Navigate to different period
   - Click "Сегодня" button
   - Verify calendar returns to current date

#### 8.2 Calendar Task Display
**Priority: HIGH**

**Test Cases:**
1. **TC-CAL-007**: Tasks are displayed on correct dates
   - Verify tasks appear on their due dates
   - Verify tasks with date ranges span correctly

2. **TC-CAL-008**: Click task on calendar
   - Click task in calendar
   - Verify task details sidebar opens
   - Verify correct task data is displayed

3. **TC-CAL-009**: Create task from calendar date
   - Click on date in calendar
   - Verify create dialog opens with pre-filled date
   - Create task
   - Verify task appears on that date

---

### 9. Analytics View

#### 9.1 Analytics Page Load
**Priority: MEDIUM**

**Test Cases:**
1. **TC-ANAL-001**: Navigate to analytics page
   - Click "Графики" in navigation
   - Verify analytics page loads
   - Verify no errors

2. **TC-ANAL-002**: Analytics charts load successfully
   - Verify all charts are rendered
   - Verify no chart errors
   - Verify data is displayed correctly

#### 9.2 Period Selection
**Priority: MEDIUM**

**Test Cases:**
1. **TC-ANAL-003**: Select "Последние 7 дней"
   - Select period
   - Verify charts update
   - Verify correct data is displayed

2. **TC-ANAL-004**: Select "Последние 30 дней"
   - Select period
   - Verify charts update

3. **TC-ANAL-005**: Select custom date range
   - Click custom range button
   - Select date range
   - Verify charts update with correct data

#### 9.3 Chart Interactions
**Priority: LOW**

**Test Cases:**
1. **TC-ANAL-006**: Chart tooltips work
   - Hover over chart elements
   - Verify tooltips are displayed
   - Verify tooltip data is correct

2. **TC-ANAL-007**: Chart legends are clickable
   - Click on chart legend items
   - Verify chart updates (if interactive)

---

### 10. Profile View

#### 10.1 Profile Navigation
**Priority: MEDIUM**

**Test Cases:**
1. **TC-PROF-001**: Navigate to profile page
   - Click profile button/email
   - Verify profile page loads
   - Verify user data is displayed

2. **TC-PROF-002**: Switch between profile tabs
   - Click "Общие" tab
   - Verify general settings are displayed
   - Click "Безопасность" tab
   - Verify security settings are displayed
   - Click "Уведомления" tab
   - Verify notification settings are displayed

#### 10.2 Edit Profile
**Priority: MEDIUM**

**Test Cases:**
1. **TC-PROF-003**: Update profile name
   - Edit name field
   - Save changes
   - Verify name is updated
   - Verify success message

2. **TC-PROF-004**: Update email (if allowed)
   - Edit email field
   - Save changes
   - Verify email is updated (if feature exists)

3. **TC-PROF-005**: Cancel profile changes
   - Make changes
   - Click cancel
   - Verify changes are not saved

#### 10.3 Change Password
**Priority: MEDIUM**

**Test Cases:**
1. **TC-PROF-006**: Change password successfully
   - Enter current password
   - Enter new password
   - Confirm new password
   - Save changes
   - Verify success message
   - Verify can login with new password

2. **TC-PROF-007**: Change password - validation
   - Try to change with wrong current password
   - Verify error message
   - Try with mismatched new passwords
   - Verify validation error

#### 10.4 Notification Settings
**Priority: LOW**

**Test Cases:**
1. **TC-PROF-008**: Toggle notification settings
   - Toggle various notification switches
   - Save changes
   - Verify settings are saved
   - Verify settings persist after reload

---

### 11. Error Handling

**Priority: HIGH**

**Test Cases:**
1. **TC-ERROR-001**: Handle 401 Unauthorized
   - Expire token or logout
   - Try to perform action
   - Verify redirect to login
   - Verify error message

2. **TC-ERROR-002**: Handle 500 Server Error
   - Mock server error
   - Perform action
   - Verify error message is displayed
   - Verify app doesn't crash

3. **TC-ERROR-003**: Handle network timeout
   - Simulate slow network
   - Perform action
   - Verify timeout handling
   - Verify retry mechanism (if exists)

4. **TC-ERROR-004**: Handle validation errors
   - Submit invalid data
   - Verify validation errors are displayed
   - Verify form is not submitted

---

### 12. Mobile Responsiveness

**Priority: MEDIUM**

**Test Cases:**
1. **TC-MOBILE-001**: Dashboard on mobile
   - Resize to mobile viewport
   - Verify layout adapts
   - Verify all features are accessible

2. **TC-MOBILE-002**: Filters modal on mobile
   - Open filters on mobile
   - Verify modal is fullscreen
   - Verify all controls are accessible

3. **TC-MOBILE-003**: Task creation on mobile
   - Create task on mobile
   - Verify form is usable
   - Verify date picker works (touch UI)

---

## 🚀 Implementation Phases

### Phase 1: Foundation (Week 1)
**Goal:** Set up testing infrastructure and basic auth tests

**Tasks:**
1. Install Playwright
2. Configure Playwright (browsers, base URL, timeouts)
3. Set up Page Object Model structure
4. Create test fixtures (auth helpers)
5. Implement authentication tests (login, register, logout)
6. Set up CI/CD basic integration

**Deliverables:**
- ✅ Playwright configured
- ✅ 7 authentication test cases passing
- ✅ Basic CI/CD pipeline

### Phase 2: Core Task Management (Week 2)
**Goal:** Test task CRUD operations

**Tasks:**
1. Create DashboardPage page object
2. Create TaskDialog page object
3. Create TaskDetailsSidebar page object
4. Implement task creation tests (all scenarios)
5. Implement task editing tests
6. Implement task completion tests
7. Implement task deletion tests

**Deliverables:**
- ✅ 25+ task management test cases passing
- ✅ Page objects for task components

### Phase 3: Filters & Search (Week 3)
**Goal:** Test all filtering and search functionality

**Tasks:**
1. Create FiltersModal page object
2. Implement quick filter tests
3. Implement advanced filter tests
4. Implement filter preset tests
5. Implement search tests

**Deliverables:**
- ✅ 25+ filter/search test cases passing

### Phase 4: Views & Navigation (Week 4)
**Goal:** Test all views and navigation

**Tasks:**
1. Implement dashboard view navigation tests
2. Create CalendarPage page object
3. Implement calendar tests
4. Create AnalyticsPage page object
5. Implement analytics tests
6. Create ProfilePage page object
7. Implement profile tests

**Deliverables:**
- ✅ 20+ view/navigation test cases passing

### Phase 5: Edge Cases & Polish (Week 5)
**Goal:** Test edge cases and error handling

**Tasks:**
1. Implement error handling tests
2. Implement mobile responsiveness tests
3. Add test data cleanup
4. Optimize test execution time
5. Add test reporting

**Deliverables:**
- ✅ All edge case tests passing
- ✅ Test suite runs in < 10 minutes
- ✅ Comprehensive test reports

---

## 🔧 CI/CD Integration

### GitHub Actions Example

```yaml
name: E2E Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  e2e-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Install dependencies
        run: |
          cd frontend
          npm ci
      
      - name: Install Playwright
        run: npx playwright install --with-deps
      
      - name: Start backend
        run: |
          cd docker
          docker-compose up -d
      
      - name: Start frontend
        run: |
          cd frontend
          npm run dev &
        env:
          VITE_API_URL: http://localhost:8089
      
      - name: Wait for services
        run: |
          npx wait-on http://localhost:3000 http://localhost:8089/api/health
      
      - name: Run E2E tests
        run: |
          cd frontend
          npm run test:e2e
      
      - name: Upload test results
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: playwright-report
          path: frontend/e2e/playwright-report/
```

---

## 📊 Test Coverage Goals

### Critical Paths (Must Have - 100%)
- ✅ Authentication (login, register)
- ✅ Task creation (basic + with all fields)
- ✅ Task editing (all fields)
- ✅ Task completion
- ✅ Task deletion
- ✅ Filtering (all types)
- ✅ View navigation

### Important Features (Should Have - 80%)
- ✅ Recurring tasks
- ✅ File attachments
- ✅ Subtasks management
- ✅ Calendar view
- ✅ Analytics view
- ✅ Profile editing

### Nice to Have (Could Have - 50%)
- ✅ Mobile responsiveness
- ✅ Error handling edge cases
- ✅ Performance testing
- ✅ Accessibility testing

---

## 📈 Success Metrics

1. **Test Coverage**: 80%+ of critical user flows
2. **Execution Time**: < 10 minutes for full suite
3. **Reliability**: 95%+ pass rate on CI/CD
4. **Maintenance**: Tests updated within 1 day of UI changes

---

## 🎯 Next Steps

1. **Install Playwright**
   ```bash
   cd frontend
   npm install -D @playwright/test
   npx playwright install
   ```

2. **Create initial test structure**
   - Set up `e2e/` directory
   - Create `playwright.config.ts`
   - Create first page objects

3. **Start with Phase 1**
   - Implement authentication tests
   - Set up CI/CD basic pipeline

4. **Iterate and expand**
   - Add tests incrementally
   - Refine page objects
   - Optimize execution time

---

**Total Test Cases: ~100+**  
**Estimated Implementation Time: 4-5 weeks**  
**Priority: HIGH - Critical for production readiness**

