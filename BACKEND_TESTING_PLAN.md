# 🧪 Backend Testing Plan - Complete Coverage Strategy

> **Objective**: Achieve comprehensive test coverage for all backend functionality with Unit and Functional tests for every component and API endpoint.

---

## 📊 Project Statistics

**Backend Components:**
- **Controllers**: 9 controllers
- **API Endpoints**: 43+ endpoints
- **Services**: 9 service classes
- **Repositories**: 6 repository classes
- **Entities**: 8 entity classes
- **Current Test Coverage**: ~15% (11 test files)
- **Target Coverage**: 80%+ for critical paths

**Current Tests:**
- Unit: 3 files (Service, Repository, Security)
- Integration: 2 files (Google Auth)
- Functional: 5 files (Auth flows)

---

## 🎯 Testing Strategy Overview

### Test Types

#### 1. **Unit Tests** (`tests/Unit/`)
**Purpose**: Test individual components in isolation
- Mock all dependencies
- Fast execution (<1ms per test)
- Focus on business logic
- No database, no HTTP

#### 2. **Functional Tests** (`tests/Functional/`)
**Purpose**: Test complete API workflows end-to-end
- Real HTTP requests via WebTestCase
- Real database with ResetDatabase trait
- Test full request/response cycle
- Authentication, authorization, validation

---

## 📋 Testing Plan Structure

### Phase 1: Core Services (Unit Tests) - **HIGH PRIORITY**
### Phase 2: Repositories (Unit Tests) - **HIGH PRIORITY**
### Phase 3: API Endpoints (Functional Tests) - **CRITICAL**
### Phase 4: Edge Cases & Error Handling - **MEDIUM PRIORITY**
### Phase 5: Integration & Performance Tests - **LOW PRIORITY**

---

## 🚀 PHASE 1: Core Services - Unit Tests

**Estimated Time**: 8-10 hours
**Priority**: HIGH
**Files to Create**: 9 test files

### 1.1 TaskService (`tests/Unit/Service/TaskServiceTest.php`)

**Class**: `App\Service\TaskService`
**Dependencies to Mock**:
- TaskRepository
- TagRepository
- EntityManagerInterface
- MediaObjectRepository
- RecurrenceService
- TranslationService

**Methods to Test** (20+ test cases):

#### Task CRUD Operations
```php
✅ testCreateTaskSuccessfully()
✅ testCreateTaskWithTags()
✅ testCreateTaskWithMediaObjects()
✅ testCreateTaskWithRecurrence()
✅ testCreateTaskAsSubtask()
✅ testUpdateTaskSuccessfully()
✅ testUpdateTaskTags()
✅ testUpdateTaskWithInvalidData()
✅ testDeleteTask()
✅ testDeleteTaskWithSubtasks()
```

#### Task State Management
```php
✅ testCompleteTask()
✅ testUncompleteTask()
✅ testToggleTaskCompletion()
✅ testArchiveTask()
✅ testUnarchiveTask()
✅ testCompleteParentTaskWithPendingSubtasks()
```

#### Task Queries
```php
✅ testGetUserTasks()
✅ testGetTasksByFilters()
✅ testGetOverdueTasks()
✅ testGetUnscheduledTasks()
✅ testGetTaskStatistics()
```

#### Edge Cases
```php
✅ testCreateTaskWithInvalidUser()
✅ testUpdateNonExistentTask()
✅ testAccessDeniedForOtherUserTask()
```

---

### 1.2 AnalyticsService (`tests/Unit/Service/AnalyticsServiceTest.php`)

**Class**: `App\Service\AnalyticsService`
**Dependencies to Mock**:
- TaskRepository
- TagRepository

**Methods to Test** (15+ test cases):

```php
✅ testGetOverview()
✅ testGetCompletionTimeline()
✅ testGetCompletionTimelineWithCustomPeriod()
✅ testGetStatusDistribution()
✅ testGetPriorityBreakdown()
✅ testGetProductivityHeatmap()
✅ testGetProductivityHeatmapForSpecificYear()
✅ testGetWeekdayProductivity()
✅ testGetTopTags()
✅ testGetTopTagsWithLimit()
✅ testGetInsights()
✅ testCalculateStreak()
✅ testGetDashboardData()
✅ testEmptyDataScenarios()
✅ testUserWithNoTasks()
```

---

### 1.3 RecurrenceService (`tests/Unit/Service/RecurrenceServiceTest.php`)

**Class**: `App\Service\RecurrenceService`
**Dependencies to Mock**:
- RecurrenceRuleRepository
- TaskRepository

**Methods to Test** (25+ test cases):

#### Rule Creation
```php
✅ testCreateDailyRecurrenceRule()
✅ testCreateWeeklyRecurrenceRule()
✅ testCreateWeeklyWithMultipleDays()
✅ testCreateMonthlyRecurrenceRule()
✅ testCreateYearlyRecurrenceRule()
✅ testCreateCustomRecurrenceRule()
✅ testCreateRuleWithEndDate()
✅ testCreateRuleWithMaxOccurrences()
✅ testCreateRuleWithTimeOfDay()
```

#### Rule Processing
```php
✅ testProcessRecurrenceRules()
✅ testGenerateTaskFromRule()
✅ testGenerateTaskWithDuration()
✅ testGenerateTaskWithTags()
✅ testSkipExpiredRules()
✅ testDeactivateCompletedRules()
```

#### Occurrence Calculation
```php
✅ testCalculateNextOccurrenceDaily()
✅ testCalculateNextOccurrenceWeekly()
✅ testCalculateNextOccurrenceMonthly()
✅ testCalculateNextOccurrenceYearly()
✅ testGetPreviewDates()
```

#### Rule Updates
```php
✅ testUpdateRecurrenceRule()
✅ testPauseRecurrenceRule()
✅ testResumeRecurrenceRule()
✅ testDeleteRecurrenceRule()
```

---

### 1.4 UserRegistrationService (`tests/Unit/Service/UserRegistrationServiceTest.php`)

**Class**: `App\Service\UserRegistrationService`
**Status**: ✅ Already exists
**Action**: Verify completeness and add missing test cases

**Additional Test Cases Needed**:
```php
✅ testRegisterWithWeakPassword()
✅ testRegisterWithInvalidEmailFormat()
✅ testRegisterWithLongPassword()
✅ testPasswordHashingWorks()
```

---

### 1.5 UserProfileService (`tests/Unit/Service/UserProfileServiceTest.php`)

**Class**: `App\Service\UserProfileService`
**Dependencies to Mock**:
- UserRepository
- PasswordHasher

**Methods to Test** (12+ test cases):

```php
✅ testGetUserProfile()
✅ testUpdateProfile()
✅ testUpdateEmail()
✅ testUpdateEmailToExisting()
✅ testUpdatePassword()
✅ testUpdatePasswordWithWrongCurrent()
✅ testUpdatePasswordForGoogleUser()
✅ testUpdateNotificationSettings()
✅ testUpdateLocale()
✅ testUpdatePreferences()
✅ testDeleteAccount()
✅ testGetUserNotFound()
```

---

### 1.6 MediaObjectService (`tests/Unit/Service/MediaObjectServiceTest.php`)

**Class**: `App\Service\MediaObjectService`
**Dependencies to Mock**:
- MediaObjectRepository
- FileUploadService

**Methods to Test** (10+ test cases):

```php
✅ testUploadImage()
✅ testUploadDocument()
✅ testUploadWithInvalidType()
✅ testUploadWithOversizedFile()
✅ testGenerateThumbnail()
✅ testDeleteMedia()
✅ testDeleteMediaWithAssociatedTask()
✅ testGetMediaByUser()
✅ testGetMediaNotFound()
✅ testAccessDeniedForOtherUserMedia()
```

---

### 1.7 TranslationService (`tests/Unit/Service/TranslationServiceTest.php`)

**Class**: `App\Service\TranslationService`

**Methods to Test** (8+ test cases):

```php
✅ testTranslateTaskStatus()
✅ testTranslateTaskPriority()
✅ testTranslateWithEnLocale()
✅ testTranslateWithRuLocale()
✅ testTranslateWithUkLocale()
✅ testFallbackToEnglish()
✅ testGetAvailableLocales()
✅ testInvalidTranslationKey()
```

---

### 1.8 EnumTranslatorService (`tests/Unit/Service/EnumTranslatorServiceTest.php`)

**Class**: `App\Service\EnumTranslatorService`

**Methods to Test** (6+ test cases):

```php
✅ testTranslateEnum()
✅ testTranslateAllEnumValues()
✅ testGetEnumTranslations()
✅ testInvalidEnum()
✅ testLocaleChange()
✅ testCaching()
```

---

### 1.9 FileUploadService (`tests/Unit/Service/FileUploadServiceTest.php`)

**Class**: `App\Service\FileUploadService`

**Methods to Test** (12+ test cases):

```php
✅ testUploadFile()
✅ testGenerateUniqueFileName()
✅ testValidateFileType()
✅ testValidateFileSize()
✅ testUploadToCorrectDirectory()
✅ testDeleteFile()
✅ testGetFilePath()
✅ testGetFileUrl()
✅ testUploadFailsWithInvalidType()
✅ testUploadFailsWithOversizedFile()
✅ testHandleDuplicateFileNames()
✅ testCleanupOrphanedFiles()
```

---

## 🗄️ PHASE 2: Repositories - Unit Tests

**Estimated Time**: 6-8 hours
**Priority**: HIGH
**Files to Create**: 5 test files (1 exists)

### 2.1 TaskRepository (`tests/Unit/Repository/TaskRepositoryTest.php`)

**Class**: `App\Repository\Database\TaskRepository`

**Methods to Test** (20+ test cases):

```php
✅ testFindByUser()
✅ testFindByUserWithFilters()
✅ testFindOverdueTasks()
✅ testFindUnscheduledTasks()
✅ testFindByDateRange()
✅ testFindByTags()
✅ testFindByPriority()
✅ testFindByStatus()
✅ testFindCompletedTasks()
✅ testSearchByTitle()
✅ testGetUserTaskStatistics()
✅ testGetAverageCompletionTime()
✅ testGetOnTimeCompletionRate()
✅ testGetMostProductiveDay()
✅ testFindTasksCreatedBetween()
✅ testFindTasksCompletedBetween()
✅ testCountByStatus()
✅ testCountByPriority()
✅ testFindRecurringTasks()
✅ testFindArchivedTasks()
```

---

### 2.2 TagRepository (`tests/Unit/Repository/TagRepositoryTest.php`)

**Class**: `App\Repository\Database\TagRepository`

**Methods to Test** (10+ test cases):

```php
✅ testFindByUser()
✅ testFindByName()
✅ testSearchByName()
✅ testFindMostUsed()
✅ testGetTagStatistics()
✅ testIncrementUsageCount()
✅ testDecrementUsageCount()
✅ testDeleteUnusedTags()
✅ testFindOrCreate()
✅ testGetUserTagCount()
```

---

### 2.3 UserRepository (`tests/Unit/Repository/UserRepositoryTest.php`)

**Status**: ✅ Already exists
**Action**: Verify and add missing cases

**Additional Test Cases**:
```php
✅ testFindByEmail()
✅ testFindByGoogleId()
✅ testCountTotalUsers()
✅ testFindActiveUsers()
✅ testFindUsersWithTasks()
```

---

### 2.4 MediaObjectRepository (`tests/Unit/Repository/MediaObjectRepositoryTest.php`)

**Class**: `App\Repository\Database\MediaObjectRepository`

**Methods to Test** (8+ test cases):

```php
✅ testFindByUser()
✅ testFindByTask()
✅ testFindOrphanedMedia()
✅ testGetTotalSizeByUser()
✅ testDeleteOrphanedMedia()
✅ testFindByFileType()
✅ testCountByUser()
✅ testGetRecentUploads()
```

---

### 2.5 RecurrenceRuleRepository (`tests/Unit/Repository/RecurrenceRuleRepositoryTest.php`)

**Class**: `App\Repository\Database\RecurrenceRuleRepository`

**Methods to Test** (10+ test cases):

```php
✅ testFindActiveRulesToProcess()
✅ testFindByUser()
✅ testFindByTask()
✅ testDeactivateExpiredRules()
✅ testFindExpiredRules()
✅ testFindByRecurrenceType()
✅ testCountActiveRules()
✅ testFindRulesNeedingProcessing()
✅ testUpdateNextOccurrence()
✅ testIncrementOccurrences()
```

---

### 2.6 TaskAttachmentRepository (`tests/Unit/Repository/TaskAttachmentRepositoryTest.php`)

**Class**: `App\Repository\Database\TaskAttachmentRepository`

**Methods to Test** (6+ test cases):

```php
✅ testFindByTask()
✅ testFindByUser()
✅ testGetTotalSizeByTask()
✅ testDeleteByTask()
✅ testCountByTask()
✅ testFindRecentAttachments()
```

---

## 🌐 PHASE 3: API Endpoints - Functional Tests

**Estimated Time**: 20-25 hours
**Priority**: CRITICAL
**Files to Create**: 8 comprehensive test files

### 3.1 TaskController (`tests/Functional/Api/TaskControllerTest.php`)

**Endpoints**: 16 endpoints
**Test Cases**: 60+ tests

#### GET /api/tasks (List Tasks)
```php
✅ testListTasksAuthenticated()
✅ testListTasksUnauthenticated()
✅ testListTasksWithFilters()
✅ testFilterByView()
✅ testFilterBySearch()
✅ testFilterByTags()
✅ testFilterByCompleted()
✅ testFilterByDateRange()
✅ testFilterByPriorities()
✅ testFilterByStatuses()
✅ testPaginationWithLimitAndOffset()
✅ testEmptyResults()
```

#### GET /api/tasks/{id}
```php
✅ testGetTaskById()
✅ testGetTaskNotFound()
✅ testGetTaskAccessDenied()
✅ testGetTaskWithSubtasks()
✅ testGetTaskWithTags()
✅ testGetTaskWithRecurrence()
```

#### POST /api/tasks (Create Task)
```php
✅ testCreateTaskSuccessfully()
✅ testCreateTaskWithMinimalData()
✅ testCreateTaskWithAllFields()
✅ testCreateTaskWithTags()
✅ testCreateTaskWithMediaObjects()
✅ testCreateTaskWithRecurrence()
✅ testCreateSubtask()
✅ testCreateTaskWithInvalidData()
✅ testCreateTaskMissingRequiredFields()
✅ testCreateTaskUnauthenticated()
```

#### PUT /api/tasks/{id} (Update Task)
```php
✅ testUpdateTaskSuccessfully()
✅ testUpdateTaskTitle()
✅ testUpdateTaskStatus()
✅ testUpdateTaskPriority()
✅ testUpdateTaskDates()
✅ testUpdateTaskTags()
✅ testUpdateTaskNotFound()
✅ testUpdateTaskAccessDenied()
✅ testUpdateWithInvalidData()
```

#### DELETE /api/tasks/{id}
```php
✅ testDeleteTask()
✅ testDeleteTaskWithSubtasks()
✅ testDeleteTaskNotFound()
✅ testDeleteTaskAccessDenied()
```

#### POST /api/tasks/{id}/complete
```php
✅ testCompleteTask()
✅ testCompleteAlreadyCompletedTask()
✅ testCompleteTaskNotFound()
✅ testCompleteTaskAccessDenied()
```

#### POST /api/tasks/{id}/toggle
```php
✅ testToggleTaskCompletion()
✅ testToggleFromPendingToCompleted()
✅ testToggleFromCompletedToPending()
```

#### POST /api/tasks/{id}/archive
```php
✅ testArchiveTask()
✅ testArchiveAlreadyArchivedTask()
```

#### POST /api/tasks/{id}/unarchive
```php
✅ testUnarchiveTask()
✅ testUnarchiveNonArchivedTask()
```

#### GET /api/tasks/overdue
```php
✅ testGetOverdueTasks()
✅ testGetOverdueTasksEmpty()
✅ testGetOverdueTasksPagination()
```

#### GET /api/tasks/unscheduled
```php
✅ testGetUnscheduledTasks()
✅ testGetUnscheduledTasksEmpty()
```

#### GET /api/tasks/statistics
```php
✅ testGetTaskStatistics()
✅ testGetStatisticsWithNoTasks()
```

#### POST /api/tasks/reorder
```php
✅ testReorderTasks()
✅ testReorderWithInvalidIds()
✅ testReorderAccessDenied()
```

#### Calendar Endpoints
```php
✅ testGetCalendarMonth()
✅ testGetCalendarWeek()
✅ testGetCalendarDay()
✅ testCalendarWithIncludeCompleted()
✅ testCalendarWithInvalidDate()
```

---

### 3.2 TagController (`tests/Functional/Api/TagControllerTest.php`)

**Endpoints**: 6 endpoints
**Test Cases**: 25+ tests

```php
// GET /api/tags
✅ testListTags()
✅ testListTagsWithSearch()
✅ testListTagsWithLimit()
✅ testListTagsEmpty()
✅ testListTagsUnauthenticated()

// GET /api/tags/most-used
✅ testGetMostUsedTags()
✅ testGetMostUsedWithLimit()
✅ testGetMostUsedEmpty()

// GET /api/tags/{id}
✅ testGetTagById()
✅ testGetTagNotFound()
✅ testGetTagAccessDenied()

// POST /api/tags
✅ testCreateTag()
✅ testCreateTagWithMinimalData()
✅ testCreateTagWithAllFields()
✅ testCreateTagWithInvalidColor()
✅ testCreateTagMissingName()
✅ testCreateDuplicateTag()
✅ testCreateTagUnauthenticated()

// PUT /api/tags/{id}
✅ testUpdateTag()
✅ testUpdateTagColor()
✅ testUpdateTagNotFound()
✅ testUpdateTagAccessDenied()

// DELETE /api/tags/{id}
✅ testDeleteTag()
✅ testDeleteTagWithTasks()
✅ testDeleteTagNotFound()
```

---

### 3.3 AnalyticsController (`tests/Functional/Api/AnalyticsControllerTest.php`)

**Endpoints**: 9 endpoints
**Test Cases**: 30+ tests

```php
// GET /api/analytics/overview
✅ testGetOverview()
✅ testGetOverviewWithNoData()
✅ testGetOverviewUnauthenticated()

// GET /api/analytics/completion-timeline
✅ testGetCompletionTimeline()
✅ testGetCompletionTimelineWithPeriod()
✅ testGetCompletionTimelineWithDateRange()
✅ testGetTimelineEmpty()

// GET /api/analytics/status-distribution
✅ testGetStatusDistribution()
✅ testGetStatusDistributionEmpty()

// GET /api/analytics/priority-breakdown
✅ testGetPriorityBreakdown()
✅ testGetPriorityBreakdownEmpty()

// GET /api/analytics/productivity-heatmap
✅ testGetProductivityHeatmap()
✅ testGetHeatmapForSpecificYear()
✅ testGetHeatmapEmpty()

// GET /api/analytics/weekday-productivity
✅ testGetWeekdayProductivity()
✅ testGetWeekdayProductivityEmpty()

// GET /api/analytics/top-tags
✅ testGetTopTags()
✅ testGetTopTagsWithLimit()
✅ testGetTopTagsEmpty()

// GET /api/analytics/insights
✅ testGetInsights()
✅ testGetInsightsEmpty()

// GET /api/analytics/dashboard
✅ testGetDashboard()
✅ testGetDashboardWithPeriod()
✅ testGetDashboardWithDateRange()
✅ testGetDashboardWithYear()
✅ testGetDashboardUnauthenticated()
```

---

### 3.4 UserProfileController (`tests/Functional/Api/UserProfileControllerTest.php`)

**Status**: ✅ Already exists
**Action**: Verify and add missing test cases

**Additional Test Cases**:
```php
✅ testUpdateProfileEmail()
✅ testUpdateProfileLocale()
✅ testUpdateProfilePreferences()
✅ testUpdatePasswordInvalidCurrent()
✅ testUpdateNotificationsPartial()
```

---

### 3.5 RecurrenceController (`tests/Functional/Api/RecurrenceControllerTest.php`)

**Endpoints**: 8 endpoints
**Test Cases**: 40+ tests

```php
// GET /api/recurrence
✅ testListRecurrenceRules()
✅ testListRulesEmpty()
✅ testListRulesUnauthenticated()

// GET /api/recurrence/{id}
✅ testGetRecurrenceRule()
✅ testGetRuleWithPreviewDates()
✅ testGetRuleNotFound()
✅ testGetRuleAccessDenied()

// POST /api/recurrence/task/{taskId}
✅ testCreateDailyRecurrence()
✅ testCreateWeeklyRecurrence()
✅ testCreateMonthlyRecurrence()
✅ testCreateYearlyRecurrence()
✅ testCreateCustomRecurrence()
✅ testCreateRecurrenceWithEndDate()
✅ testCreateRecurrenceWithMaxOccurrences()
✅ testCreateRecurrenceWithTimeOfDay()
✅ testCreateRecurrenceTaskNotFound()
✅ testCreateRecurrenceInvalidType()
✅ testCreateRecurrenceAccessDenied()

// PUT /api/recurrence/{id}
✅ testUpdateRecurrenceRule()
✅ testUpdateRecurrenceType()
✅ testUpdateRecurrenceOptions()
✅ testUpdateRecurrenceNotFound()

// DELETE /api/recurrence/{id}
✅ testDeleteRecurrenceRule()
✅ testDeleteRecurrenceNotFound()

// GET /api/recurrence/{id}/preview
✅ testPreviewOccurrences()
✅ testPreviewWithCustomCount()
✅ testPreviewWithMaxCount()
✅ testPreviewNotFound()

// POST /api/recurrence/{id}/pause
✅ testPauseRecurrenceRule()
✅ testPauseAlreadyPausedRule()

// POST /api/recurrence/{id}/resume
✅ testResumeRecurrenceRule()
✅ testResumeActiveRule()
```

---

### 3.6 AttachmentController (`tests/Functional/Api/AttachmentControllerTest.php`)

**Endpoints**: 3 endpoints
**Test Cases**: 20+ tests

```php
// GET /api/tasks/{taskId}/attachments
✅ testListAttachments()
✅ testListAttachmentsEmpty()
✅ testListAttachmentsTaskNotFound()
✅ testListAttachmentsAccessDenied()

// POST /api/tasks/{taskId}/attachments
✅ testUploadAttachment()
✅ testUploadImageAttachment()
✅ testUploadDocumentAttachment()
✅ testUploadPdfAttachment()
✅ testUploadMultipleAttachments()
✅ testUploadWithInvalidFile()
✅ testUploadWithOversizedFile()
✅ testUploadWithInvalidType()
✅ testUploadTaskNotFound()
✅ testUploadAccessDenied()

// DELETE /api/tasks/{taskId}/attachments/{id}
✅ testDeleteAttachment()
✅ testDeleteAttachmentNotFound()
✅ testDeleteAttachmentAccessDenied()
✅ testDeleteAttachmentTaskMismatch()
```

---

### 3.7 MediaObjectController (`tests/Functional/Api/MediaObjectControllerTest.php`)

**Endpoints**: 2 endpoints
**Test Cases**: 15+ tests

```php
// POST /api/media
✅ testUploadMedia()
✅ testUploadImage()
✅ testUploadDocument()
✅ testUploadWithThumbnail()
✅ testUploadInvalidFile()
✅ testUploadOversizedFile()
✅ testUploadInvalidType()
✅ testUploadUnauthenticated()

// DELETE /api/media/{id}
✅ testDeleteMedia()
✅ testDeleteMediaInUse()
✅ testDeleteMediaNotFound()
✅ testDeleteMediaAccessDenied()
```

---

### 3.8 AuthenticationController (`tests/Functional/Api/AuthenticationControllerTest.php`)

**Status**: Partially exists
**Action**: Complete with all scenarios

**Additional Test Cases**:
```php
// POST /api/register
✅ testRegisterWithAllFields()
✅ testRegisterWithMinimalFields()
✅ testRegisterDuplicateEmail()
✅ testRegisterInvalidEmail()
✅ testRegisterWeakPassword()
✅ testRegisterMissingFields()

// POST /api/login
✅ testLoginSuccess() // exists
✅ testLoginWrongPassword() // exists
✅ testLoginNonExistentUser()
✅ testLoginWithGoogleAccount()
✅ testLoginMissingFields()

// POST /api/token/refresh
✅ testRefreshTokenSuccess()
✅ testRefreshTokenExpired()
✅ testRefreshTokenInvalid()
✅ testRefreshTokenMissing()

// POST /api/auth/google
✅ testGoogleAuthNewUser()
✅ testGoogleAuthExistingUser()
✅ testGoogleAuthInvalidToken()
✅ testGoogleAuthExpiredToken()
```

---

## ⚠️ PHASE 4: Edge Cases & Error Handling

**Estimated Time**: 8-10 hours
**Priority**: MEDIUM

### 4.1 Validation Tests

**File**: `tests/Functional/Api/ValidationTest.php`

```php
✅ testInvalidJsonPayload()
✅ testMalformedJsonPayload()
✅ testMissingContentTypeHeader()
✅ testInvalidContentType()
✅ testEmptyRequestBody()
✅ testExtraFieldsInPayload()
✅ testInvalidFieldTypes()
✅ testFieldMaxLengthViolation()
✅ testFieldMinLengthViolation()
✅ testInvalidEnumValue()
✅ testInvalidDateFormat()
✅ testInvalidEmailFormat()
```

### 4.2 Authorization Tests

**File**: `tests/Functional/Api/AuthorizationTest.php`

```php
✅ testAccessWithoutToken()
✅ testAccessWithExpiredToken()
✅ testAccessWithInvalidToken()
✅ testAccessWithMalformedToken()
✅ testAccessOtherUserResource()
✅ testAdminAccessToUserResource()
✅ testGuestAccessToProtectedRoute()
```

### 4.3 Rate Limiting Tests

**File**: `tests/Functional/Api/RateLimitingTest.php`

```php
✅ testRateLimitExceeded()
✅ testRateLimitHeaders()
✅ testRateLimitReset()
```

### 4.4 Database Constraint Tests

**File**: `tests/Functional/Api/DatabaseConstraintTest.php`

```php
✅ testUniqueConstraintViolation()
✅ testForeignKeyConstraintViolation()
✅ testNotNullConstraintViolation()
✅ testCascadeDelete()
```

---

## 🔄 PHASE 5: Integration Tests

**Estimated Time**: 6-8 hours
**Priority**: LOW

### 5.1 Service Integration Tests

**File**: `tests/Integration/Service/TaskServiceIntegrationTest.php`

```php
✅ testTaskCreationWithRecurrence()
✅ testTaskCompletionWithSubtasks()
✅ testTaskDeletionWithCascade()
✅ testBulkTaskOperations()
```

### 5.2 Command Tests

**File**: `tests/Integration/Command/ProcessRecurrenceRulesCommandTest.php`

```php
✅ testProcessRecurrenceCommand()
✅ testProcessRecurrenceWithDryRun()
✅ testProcessRecurrenceWithLimit()
```

---

## 📊 Coverage Goals

### By Component

| Component | Target Coverage | Current | Priority |
|-----------|----------------|---------|----------|
| Services | 90% | 10% | HIGH |
| Repositories | 85% | 15% | HIGH |
| Controllers | 80% | 20% | CRITICAL |
| Entities | 70% | 0% | MEDIUM |
| DTOs | 60% | 0% | LOW |

### By Test Type

| Test Type | Target | Current | Files Needed |
|-----------|--------|---------|--------------|
| Unit | 120+ tests | 11 | +9 files |
| Functional | 250+ tests | 15 | +8 files |
| Integration | 20+ tests | 2 | +3 files |

---

## 🛠️ Implementation Order

### Week 1: High-Value Services
1. TaskService (Unit)
2. TaskRepository (Unit)
3. TaskController (Functional) - Priority endpoints

### Week 2: Core Functionality
4. AnalyticsService (Unit)
5. TagController (Functional)
6. RecurrenceService (Unit)
7. RecurrenceController (Functional)

### Week 3: User & Media
8. UserProfileService (Unit)
9. MediaObjectService (Unit)
10. AttachmentController (Functional)
11. MediaObjectController (Functional)

### Week 4: Repositories & Edge Cases
12. Remaining Repositories (Unit)
13. Validation & Authorization tests
14. Error handling tests

### Week 5: Polish & Coverage
15. Integration tests
16. Performance tests
17. Coverage report analysis
18. Missing edge cases

---

## 📝 Test Writing Guidelines

### AAA Pattern

```php
public function testCreateTask(): void
{
    // Arrange
    $user = UserFactory::createOne();
    $dto = new CreateTaskDto(title: 'Test Task');

    // Act
    $task = $this->taskService->createTask($dto, $user->object());

    // Assert
    $this->assertNotNull($task->getId());
    $this->assertEquals('Test Task', $task->getTitle());
}
```

### Naming Convention

```
test[MethodName][Scenario][ExpectedResult]

Examples:
- testCreateTaskSuccessfully()
- testCreateTaskWithInvalidDataThrowsException()
- testGetTaskNotFoundReturns404()
```

### Mock Setup

```php
protected function setUp(): void
{
    $this->taskRepository = $this->createMock(TaskRepository::class);
    $this->entityManager = $this->createMock(EntityManagerInterface::class);

    $this->taskService = new TaskService(
        $this->taskRepository,
        $this->entityManager
    );
}
```

### Functional Test Setup

```php
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Test\Factories;

class TaskControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }
}
```

---

## 🎯 Success Criteria

### Minimum Viable Coverage
- ✅ All critical endpoints (CRUD) have functional tests
- ✅ All services have unit tests for public methods
- ✅ All repositories have unit tests for custom queries
- ✅ 80%+ code coverage for Services
- ✅ 70%+ code coverage for Controllers
- ✅ All positive and negative scenarios covered

### Quality Metrics
- ✅ All tests pass consistently
- ✅ No flaky tests
- ✅ Test execution time < 2 minutes
- ✅ Zero test warnings/deprecations
- ✅ PHPUnit 9.6 compatibility

---

## 🚀 Quick Start Commands

```bash
# Run all tests
docker exec backend-php83 vendor/bin/phpunit

# Run Unit tests only
docker exec backend-php83 vendor/bin/phpunit tests/Unit

# Run Functional tests only
docker exec backend-php83 vendor/bin/phpunit tests/Functional

# Run specific test file
docker exec backend-php83 vendor/bin/phpunit tests/Unit/Service/TaskServiceTest.php

# Run with coverage
docker exec backend-php83 vendor/bin/phpunit --coverage-html coverage

# Run specific test method
docker exec backend-php83 vendor/bin/phpunit --filter testCreateTask
```

---

## 📚 Resources

- **PHPUnit Documentation**: https://phpunit.de/documentation.html
- **Symfony Testing**: https://symfony.com/doc/current/testing.html
- **Zenstruck Foundry**: https://github.com/zenstruck/foundry
- **Project Testing Guide**: `docs/guides/TESTING.md`

---

## ✅ Progress Tracking

**Use this checklist to track implementation progress:**

### Phase 1: Services (Unit)
- [ ] TaskService
- [ ] AnalyticsService
- [ ] RecurrenceService
- [ ] UserRegistrationService (verify)
- [ ] UserProfileService
- [ ] MediaObjectService
- [ ] TranslationService
- [ ] EnumTranslatorService
- [ ] FileUploadService

### Phase 2: Repositories (Unit)
- [ ] TaskRepository
- [ ] TagRepository
- [ ] UserRepository (verify)
- [ ] MediaObjectRepository
- [ ] RecurrenceRuleRepository
- [ ] TaskAttachmentRepository

### Phase 3: Controllers (Functional)
- [ ] TaskController (16 endpoints)
- [ ] TagController (6 endpoints)
- [ ] AnalyticsController (9 endpoints)
- [ ] UserProfileController (verify + add)
- [ ] RecurrenceController (8 endpoints)
- [ ] AttachmentController (3 endpoints)
- [ ] MediaObjectController (2 endpoints)
- [ ] AuthenticationController (complete)

### Phase 4: Edge Cases
- [ ] Validation Tests
- [ ] Authorization Tests
- [ ] Rate Limiting Tests
- [ ] Database Constraints

### Phase 5: Integration
- [ ] Service Integration Tests
- [ ] Command Tests
- [ ] Performance Tests

---

**Total Estimated Time**: 50-60 hours
**Target Completion**: 5 weeks (10-12 hours/week)
**Final Coverage Goal**: 80%+

---

*Created: 2025-11-06*
*Last Updated: 2025-11-06*
*Author: Claude Code AI*
