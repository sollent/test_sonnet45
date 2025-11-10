# 🎯 Backend Missing Test Coverage - Implementation Plan

> **Last Updated**: 2025-11-10
> **Status**: Ready for Implementation
> **Priority**: Critical → Medium → Low

---

## 📊 Executive Summary

This document provides a **step-by-step implementation plan** for writing missing backend tests. After deep analysis of the entire backend codebase, we identified **29 components** that need test coverage.

**Coverage Status:**
- ✅ **Already Tested**: 33 test files (Controllers: 8, Services: 9, Repositories: 6, Security: 1, Commands: 1)
- ⚠️ **Missing Tests**: 29 components identified below
- 🎯 **Target Coverage**: 95%+ (from current ~75-80%)

**Time Estimate**: 25-30 hours (5-6 days for solo dev + AI)

---

## 🗂️ Complete List of Missing Tests

### Summary by Priority

| Priority | Components | Estimated Time |
|----------|------------|----------------|
| 🔥 **Critical** | 7 components | 8-10 hours |
| ⚠️ **High** | 8 components | 10-12 hours |
| 📘 **Medium** | 9 components | 6-8 hours |
| 📙 **Low** | 5 components | 3-4 hours |
| **TOTAL** | **29 components** | **27-34 hours** |

---

## 🔥 PRIORITY 1 - CRITICAL (Must Have)

### 1.1. Security Voters - Unit Tests

**Priority**: 🔥 Critical
**Estimated Time**: 3-4 hours
**Test Type**: Unit Tests

#### Components:
- `src/Security/Voter/TaskVoter.php`
- `src/Security/Voter/TagVoter.php`

#### Why Critical:
- Authorization logic is the security backbone
- Currently tested only indirectly via Functional tests
- Direct unit tests ensure voter logic is bulletproof

#### Test File Locations:
```
tests/Unit/Security/Voter/TaskVoterTest.php
tests/Unit/Security/Voter/TagVoterTest.php
```

#### Test Cases for TaskVoter:

```php
// tests/Unit/Security/Voter/TaskVoterTest.php

class TaskVoterTest extends TestCase
{
    private TaskVoter $voter;

    /** @test */
    public function testOwnerCanViewTask(): void

    /** @test */
    public function testOwnerCanEditTask(): void

    /** @test */
    public function testOwnerCanDeleteTask(): void

    /** @test */
    public function testNonOwnerCannotViewTask(): void

    /** @test */
    public function testNonOwnerCannotEditTask(): void

    /** @test */
    public function testNonOwnerCannotDeleteTask(): void

    /** @test */
    public function testUnauthenticatedUserDenied(): void

    /** @test */
    public function testVoterSupportsOnlyTaskEntity(): void

    /** @test */
    public function testVoterSupportsCorrectAttributes(): void
}
```

**Total Tests**: 9 tests for TaskVoter + 7 tests for TagVoter = **16 tests**

---

### 1.2. API Controllers - Functional Tests

**Priority**: 🔥 Critical
**Estimated Time**: 3-4 hours
**Test Type**: Functional Tests

#### Components:
- `src/Controller/Api/EnumController.php`
- `src/Controller/Api/TranslationController.php`

#### Why Critical:
- These are public API endpoints used by frontend
- EnumController: serves priorities and statuses (critical for UI)
- TranslationController: serves i18n translations (critical for i18n)

#### Test File Locations:
```
tests/Functional/Api/EnumControllerTest.php
tests/Functional/Api/TranslationControllerTest.php
```

#### Test Cases for EnumController:

```php
// tests/Functional/Api/EnumControllerTest.php

class EnumControllerTest extends ApiTestCase
{
    /** @test */
    public function testGetPrioritiesReturnsAllPriorities(): void
    // Expected: 200, array with 4 priorities (low, medium, high, urgent)

    /** @test */
    public function testGetPrioritiesIncludesColorAndIcon(): void
    // Expected: Each priority has value, label, color, icon

    /** @test */
    public function testGetPrioritiesRespectsAcceptLanguageHeader(): void
    // Expected: Russian labels when Accept-Language: ru

    /** @test */
    public function testGetStatusesReturnsAllStatuses(): void
    // Expected: 200, array with 4 statuses

    /** @test */
    public function testGetStatusesIncludesColorAndIcon(): void

    /** @test */
    public function testGetStatusesRespectsAcceptLanguageHeader(): void

    /** @test */
    public function testUnauthorizedUserCannotAccessEnumEndpoints(): void
    // Expected: 401 when no JWT token
}
```

**Total Tests**: 7 tests for EnumController + 8 tests for TranslationController = **15 tests**

---

### 1.3. Admin Controllers - Functional Tests

**Priority**: 🔥 Critical
**Estimated Time**: 4-5 hours
**Test Type**: Functional Tests

#### Components:
- `src/Controller/Admin/DashboardController.php`
- `src/Controller/Admin/SecurityController.php`
- `src/Controller/Admin/UserCrudController.php`

#### Why Critical:
- Admin panel has access to sensitive operations
- ROLE_ADMIN authorization must be tested
- User management is critical functionality

#### Test File Locations:
```
tests/Functional/Admin/AdminDashboardTest.php
tests/Functional/Admin/AdminSecurityTest.php
tests/Functional/Admin/UserCrudTest.php
```

#### Test Cases for Admin Controllers:

```php
// tests/Functional/Admin/AdminAccessTest.php (combined test)

class AdminAccessTest extends WebTestCase
{
    /** @test */
    public function testNonAdminCannotAccessDashboard(): void
    // Expected: 403 when regular user tries to access /admin

    /** @test */
    public function testAdminCanAccessDashboard(): void
    // Expected: 200 when ROLE_ADMIN user accesses /admin

    /** @test */
    public function testUnauthenticatedUserRedirectedToLogin(): void
    // Expected: 302 redirect to login page

    /** @test */
    public function testAdminCanViewUsersList(): void

    /** @test */
    public function testAdminCanCreateUser(): void

    /** @test */
    public function testAdminCanEditUser(): void

    /** @test */
    public function testAdminCanDeleteUser(): void

    /** @test */
    public function testAdminCannotDeleteSelf(): void

    /** @test */
    public function testAdminCanGrantRoleAdmin(): void

    /** @test */
    public function testNonAdminCannotAccessUserCrud(): void
}
```

**Total Tests**: ~12-15 tests for Admin controllers

---

## ⚠️ PRIORITY 2 - HIGH (Should Have)

### 2.1. Recurrence Strategies - Unit Tests

**Priority**: ⚠️ High
**Estimated Time**: 6-8 hours
**Test Type**: Unit Tests

#### Components:
- `src/Service/Recurrence/Strategy/DailyRecurrenceStrategy.php`
- `src/Service/Recurrence/Strategy/WeeklyRecurrenceStrategy.php`
- `src/Service/Recurrence/Strategy/MonthlyRecurrenceStrategy.php`
- `src/Service/Recurrence/Strategy/YearlyRecurrenceStrategy.php`
- `src/Service/Recurrence/Strategy/CustomRecurrenceStrategy.php`

#### Why High Priority:
- Complex business logic for date calculations
- Currently tested only indirectly through RecurrenceServiceTest
- Direct unit tests improve readability and maintainability
- Critical for recurring tasks feature

#### Test File Locations:
```
tests/Unit/Service/Recurrence/Strategy/DailyRecurrenceStrategyTest.php
tests/Unit/Service/Recurrence/Strategy/WeeklyRecurrenceStrategyTest.php
tests/Unit/Service/Recurrence/Strategy/MonthlyRecurrenceStrategyTest.php
tests/Unit/Service/Recurrence/Strategy/YearlyRecurrenceStrategyTest.php
tests/Unit/Service/Recurrence/Strategy/CustomRecurrenceStrategyTest.php
```

#### Test Cases for DailyRecurrenceStrategy:

```php
// tests/Unit/Service/Recurrence/Strategy/DailyRecurrenceStrategyTest.php

class DailyRecurrenceStrategyTest extends TestCase
{
    private DailyRecurrenceStrategy $strategy;

    /** @test */
    public function testCalculateNextOccurrenceAddsOneDay(): void
    // Given: task due on 2025-01-10
    // When: calculateNextOccurrence() called
    // Then: returns 2025-01-11

    /** @test */
    public function testAppliesTimeOfDayIfSet(): void
    // Given: rule with timeOfDay = 14:30
    // When: calculateNextOccurrence()
    // Then: next date has time 14:30:00

    /** @test */
    public function testRespectsEndDate(): void
    // Given: rule with endDate = 2025-01-15
    // When: calculateNextOccurrence(2025-01-15)
    // Then: returns null (no more occurrences)

    /** @test */
    public function testRespectsMaxOccurrences(): void
    // Given: rule with maxOccurrences = 5, currentOccurrences = 5
    // When: calculateNextOccurrence()
    // Then: returns null

    /** @test */
    public function testSupportsOnlyDailyType(): void
    // Given: strategy instance
    // When: supports('daily') called
    // Then: returns true
    // When: supports('weekly') called
    // Then: returns false

    /** @test */
    public function testGetPreviewDatesReturnsCorrectCount(): void
    // Given: startDate = 2025-01-10, count = 5
    // When: getPreviewDates() called
    // Then: returns array with 5 dates

    /** @test */
    public function testGetPreviewDatesStopsAtEndDate(): void
    // Given: endDate = 2025-01-12, count = 10
    // When: getPreviewDates() called
    // Then: returns only 2 dates (until endDate)
}
```

**Similar structure for other strategies (Weekly, Monthly, Yearly, Custom)**

**Total Tests**: ~7 tests × 5 strategies = **35 tests**

---

### 2.2. Commands - Integration Tests

**Priority**: ⚠️ High
**Estimated Time**: 2-3 hours
**Test Type**: Integration Tests

#### Components:
- `src/Command/MakeAdminCommand.php` (CRITICAL - production command)

#### Why High Priority:
- Used in production to grant admin privileges
- Must ensure it works correctly with real database

#### Test File Location:
```
tests/Integration/Command/MakeAdminCommandTest.php
```

#### Test Cases:

```php
// tests/Integration/Command/MakeAdminCommandTest.php

class MakeAdminCommandTest extends KernelTestCase
{
    use ResetDatabase;

    /** @test */
    public function testMakeUserAdmin(): void
    // Given: regular user exists
    // When: command executed with user email
    // Then: user has ROLE_ADMIN role

    /** @test */
    public function testCannotMakeNonExistentUserAdmin(): void
    // Given: user does not exist
    // When: command executed
    // Then: command fails with error message

    /** @test */
    public function testUserAlreadyAdmin(): void
    // Given: user already has ROLE_ADMIN
    // When: command executed
    // Then: command shows "already admin" message

    /** @test */
    public function testCommandRequiresEmailArgument(): void

    /** @test */
    public function testCommandPersistsChangesToDatabase(): void
    // Ensure changes are flushed to DB
}
```

**Total Tests**: 5 tests

---

### 2.3. Event Listeners - Unit Tests

**Priority**: ⚠️ High
**Estimated Time**: 2-3 hours
**Test Type**: Unit Tests

#### Components:
- `src/EventListener/LocaleListener.php`
- `src/EventSubscriber/LocaleSubscriber.php`

#### Why High Priority:
- Locale handling affects all API responses
- i18n is a key feature

#### Test File Locations:
```
tests/Unit/EventListener/LocaleListenerTest.php
tests/Unit/EventSubscriber/LocaleSubscriberTest.php
```

#### Test Cases for LocaleListener:

```php
// tests/Unit/EventListener/LocaleListenerTest.php

class LocaleListenerTest extends TestCase
{
    /** @test */
    public function testSetsLocaleFromAcceptLanguageHeader(): void

    /** @test */
    public function testFallsBackToDefaultLocale(): void

    /** @test */
    public function testSupportsOnlyConfiguredLocales(): void
    // Given: configured locales are [en, ru, uk]
    // When: Accept-Language: fr
    // Then: falls back to 'en'
}
```

**Total Tests**: 6 tests (3 + 3)

---

## 📘 PRIORITY 3 - MEDIUM (Nice to Have)

### 3.1. Serializer Normalizer - Unit Tests

**Priority**: 📘 Medium
**Estimated Time**: 1-2 hours
**Test Type**: Unit Tests

#### Component:
- `src/Serializer/Normalizer/TaskEnumNormalizer.php`

#### Why Medium Priority:
- Custom serialization logic for enums
- Used in API responses
- Currently tested indirectly via Functional tests

#### Test File Location:
```
tests/Unit/Serializer/Normalizer/TaskEnumNormalizerTest.php
```

#### Test Cases:

```php
// tests/Unit/Serializer/Normalizer/TaskEnumNormalizerTest.php

class TaskEnumNormalizerTest extends TestCase
{
    /** @test */
    public function testNormalizesTaskPriority(): void

    /** @test */
    public function testNormalizesTaskStatus(): void

    /** @test */
    public function testSupportsNormalizationForEnums(): void

    /** @test */
    public function testDoesNotSupportOtherTypes(): void
}
```

**Total Tests**: 4 tests

---

### 3.2. DTO Validation - Unit Tests

**Priority**: 📘 Medium
**Estimated Time**: 4-5 hours
**Test Type**: Unit Tests

#### Components:
- `src/Dto/Request/Task/CreateTaskDto.php`
- `src/Dto/Request/Task/UpdateTaskDto.php`
- `src/Dto/Request/User/UserRegistrationRequestDto.php`
- `src/Dto/Request/User/UpdateProfileDto.php`
- `src/Dto/Request/User/UpdatePasswordDto.php`
- `src/Dto/Request/User/UpdateThemeDto.php`
- `src/Dto/Request/User/UpdateNotificationsDto.php`
- `src/Dto/Request/Recurrence/CreateRecurrenceDto.php`

#### Why Medium Priority:
- Validation is currently tested only through API endpoints
- Direct DTO tests isolate validation logic
- Good for documentation purposes

#### Test File Locations:
```
tests/Unit/Dto/Request/Task/CreateTaskDtoTest.php
tests/Unit/Dto/Request/Task/UpdateTaskDtoTest.php
tests/Unit/Dto/Request/User/UserRegistrationRequestDtoTest.php
... (etc for other DTOs)
```

#### Test Cases for CreateTaskDto:

```php
// tests/Unit/Dto/Request/Task/CreateTaskDtoTest.php

class CreateTaskDtoTest extends TestCase
{
    private ValidatorInterface $validator;

    /** @test */
    public function testValidDtoPassesValidation(): void

    /** @test */
    public function testTitleIsRequired(): void
    // Expected: validation error when title is null/empty

    /** @test */
    public function testTitleMinLength(): void
    // Expected: error when title < 3 characters

    /** @test */
    public function testTitleMaxLength(): void
    // Expected: error when title > 255 characters

    /** @test */
    public function testInvalidStatus(): void
    // Expected: error when status is not valid enum value

    /** @test */
    public function testInvalidPriority(): void

    /** @test */
    public function testDueDateMustBeFutureDate(): void

    /** @test */
    public function testDescriptionIsOptional(): void

    /** @test */
    public function testParentIdMustBeInteger(): void
}
```

**Total Tests**: ~50-60 tests (all DTOs combined)

---

## 📙 PRIORITY 4 - LOW (Optional)

### 4.1. Development Commands - Integration Tests

**Priority**: 📙 Low
**Estimated Time**: 2-3 hours
**Test Type**: Integration Tests

#### Components:
- `src/Command/SeedTasksCommand.php`
- `src/Command/GenerateUserJourneyCommand.php`
- `src/Command/GenerateTestDataFastCommand.php`

#### Why Low Priority:
- Development/seeding tools only
- Not used in production
- Manual QA sufficient

#### Test File Locations:
```
tests/Integration/Command/SeedTasksCommandTest.php
tests/Integration/Command/GenerateUserJourneyCommandTest.php
tests/Integration/Command/GenerateTestDataFastCommandTest.php
```

#### Test Cases (Example):

```php
// tests/Integration/Command/SeedTasksCommandTest.php

class SeedTasksCommandTest extends KernelTestCase
{
    use ResetDatabase;

    /** @test */
    public function testCommandCreatesTasksInDatabase(): void

    /** @test */
    public function testCommandCreatesCorrectNumberOfTasks(): void
}
```

**Total Tests**: ~9 tests (3 per command)

---

### 4.2. Entity Business Logic - Unit Tests

**Priority**: 📙 Low
**Estimated Time**: 2-3 hours
**Test Type**: Unit Tests

#### Components:
- `src/Entity/Task.php` (methods: `isCompleted()`, `isOverdue()`, etc.)
- `src/Entity/RecurrenceRule.php` (validation methods)

#### Why Low Priority:
- Most entity methods are simple getters/setters
- Already tested via Integration tests
- Only methods with business logic need direct tests

#### Test File Locations:
```
tests/Unit/Entity/TaskTest.php
tests/Unit/Entity/RecurrenceRuleTest.php
```

#### Test Cases for Task Entity:

```php
// tests/Unit/Entity/TaskTest.php

class TaskTest extends TestCase
{
    /** @test */
    public function testIsOverdueReturnsTrueForPastDueDate(): void

    /** @test */
    public function testIsOverdueReturnsFalseForFutureDueDate(): void

    /** @test */
    public function testIsOverdueReturnsFalseWhenNoDueDate(): void

    /** @test */
    public function testIsCompletedReturnsTrueWhenStatusCompleted(): void

    /** @test */
    public function testIsCompletedReturnsFalseForOtherStatuses(): void

    /** @test */
    public function testAddSubtaskRelationship(): void

    /** @test */
    public function testRemoveSubtaskRelationship(): void

    /** @test */
    public function testAddTagRelationship(): void
}
```

**Total Tests**: ~12 tests

---

## 📋 Implementation Roadmap

### Phase 1: Critical Security & Controllers (Week 1)
**Goal**: Cover all security-critical components
**Time**: 8-10 hours

- [ ] TaskVoter unit tests (2 hours)
- [ ] TagVoter unit tests (1 hour)
- [ ] Admin Controllers functional tests (4 hours)
- [ ] EnumController functional tests (1 hour)
- [ ] TranslationController functional tests (1 hour)

**Result**: Security 100%, API Controllers 100%

---

### Phase 2: Recurrence Strategies (Week 2)
**Goal**: Cover complex date calculation logic
**Time**: 6-8 hours

- [ ] DailyRecurrenceStrategy unit tests (1.5 hours)
- [ ] WeeklyRecurrenceStrategy unit tests (1.5 hours)
- [ ] MonthlyRecurrenceStrategy unit tests (2 hours)
- [ ] YearlyRecurrenceStrategy unit tests (2 hours)
- [ ] CustomRecurrenceStrategy unit tests (1 hour)

**Result**: Recurrence logic 100% covered

---

### Phase 3: Commands & Event Listeners (Week 3)
**Goal**: Cover infrastructure components
**Time**: 4-6 hours

- [ ] MakeAdminCommand integration tests (2 hours)
- [ ] LocaleListener unit tests (1.5 hours)
- [ ] LocaleSubscriber unit tests (1.5 hours)
- [ ] TaskEnumNormalizer unit tests (1 hour)

**Result**: Commands 33%, Event handling 100%

---

### Phase 4: DTO Validation (Week 4)
**Goal**: Isolate validation logic
**Time**: 4-5 hours

- [ ] CreateTaskDto validation tests (1 hour)
- [ ] UpdateTaskDto validation tests (1 hour)
- [ ] User DTOs validation tests (2 hours)
- [ ] Recurrence DTO validation tests (1 hour)

**Result**: DTOs 100% covered

---

### Phase 5: Optional - Entities & Dev Commands (Week 5)
**Goal**: Complete 100% coverage
**Time**: 4-6 hours

- [ ] Task entity business logic tests (1 hour)
- [ ] RecurrenceRule entity tests (1 hour)
- [ ] SeedTasksCommand tests (1 hour)
- [ ] GenerateUserJourneyCommand tests (1 hour)
- [ ] GenerateTestDataFastCommand tests (1 hour)

**Result**: Overall coverage 95%+

---

## 📊 Expected Coverage After Each Phase

| Phase | Controllers | Services | Repositories | Security | Commands | DTOs | Entities | Overall |
|-------|-------------|----------|--------------|----------|----------|------|----------|---------|
| **Current** | 73% (8/11) | 90% (9/10) | 100% | 78% (7/9) | 17% (1/6) | 0% | 0% | ~75% |
| **Phase 1** | 100% | 90% | 100% | 100% | 17% | 0% | 0% | ~85% |
| **Phase 2** | 100% | 100% | 100% | 100% | 17% | 0% | 0% | ~88% |
| **Phase 3** | 100% | 100% | 100% | 100% | 33% | 0% | 0% | ~90% |
| **Phase 4** | 100% | 100% | 100% | 100% | 33% | 100% | 0% | ~93% |
| **Phase 5** | 100% | 100% | 100% | 100% | 67% | 100% | 50% | ~95% |

---

## 🔧 Testing Tools & Commands

### Running Tests

```bash
# All tests
docker exec backend-php83 vendor/bin/phpunit

# Specific test file
docker exec backend-php83 vendor/bin/phpunit tests/Unit/Security/Voter/TaskVoterTest.php

# Specific test method
docker exec backend-php83 vendor/bin/phpunit --filter testOwnerCanViewTask

# With coverage (requires Xdebug)
docker exec backend-php83 php -dxdebug.mode=coverage vendor/bin/phpunit --coverage-html var/coverage
```

### Code Coverage Report

```bash
# Install Xdebug
docker exec backend-php83 pecl install xdebug

# Generate HTML coverage report
docker exec backend-php83 php -dxdebug.mode=coverage vendor/bin/phpunit --coverage-html var/coverage

# Open report
open var/coverage/index.html
```

---

## 📝 Test Writing Guidelines

### AAA Pattern (Arrange, Act, Assert)

```php
/** @test */
public function testOwnerCanViewTask(): void
{
    // Arrange: Setup test data
    $user = new User();
    $user->setEmail('owner@test.com');

    $task = new Task();
    $task->setUser($user);

    $token = $this->createMock(TokenInterface::class);
    $token->method('getUser')->willReturn($user);

    $voter = new TaskVoter();

    // Act: Execute the code under test
    $result = $voter->vote($token, $task, ['TASK_VIEW']);

    // Assert: Verify the result
    $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
}
```

### Mocking Dependencies

```php
// Mock repository
$taskRepository = $this->createMock(TaskRepository::class);
$taskRepository
    ->expects($this->once())
    ->method('find')
    ->with(123)
    ->willReturn($task);

// Mock service
$service = new TaskService($taskRepository, $entityManager);
```

### Testing Exceptions

```php
/** @test */
public function testThrowsExceptionWhenTaskNotFound(): void
{
    $this->expectException(TaskNotFoundException::class);
    $this->expectExceptionMessage('Task with ID 999 not found');

    $service->getTask(999);
}
```

---

## 🎯 Success Criteria

### Definition of Done for Each Test:

- [ ] Test file created in correct location
- [ ] All methods in component are tested
- [ ] Happy path covered
- [ ] Error cases covered
- [ ] Edge cases covered
- [ ] Mocks used correctly (for Unit tests)
- [ ] ResetDatabase trait used (for Integration/Functional tests)
- [ ] Test names are descriptive
- [ ] AAA pattern followed
- [ ] All tests pass green ✅

### Coverage Goals:

- **Critical Priority**: Must reach 100%
- **High Priority**: Should reach 100%
- **Medium Priority**: Should reach 80%+
- **Low Priority**: Optional, but nice to have

---

## 📚 Related Documents

- **[TEST_COVERAGE.md](TEST_COVERAGE.md)** - Current test coverage report
- **[TESTING.md](../guides/testing/TESTING.md)** - Testing guidelines and best practices
- **[CODING_STANDARDS.md](../CODING_STANDARDS.md)** - Code quality standards
- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Backend architecture overview

---

## 📌 Notes for AI Implementation

### When Writing Tests:

1. **Always read existing similar tests first** - Follow established patterns
2. **Use factories for test data** - TaskFactory, UserFactory, TagFactory
3. **Mock external dependencies** - Don't test third-party code
4. **Test one thing at a time** - Single responsibility per test
5. **Use descriptive test names** - `testOwnerCanViewTask` not `testVoter`
6. **Add comments for complex setup** - Help future developers understand

### Common Pitfalls to Avoid:

- ❌ Don't test getters/setters (they're trivial)
- ❌ Don't test framework code (Symfony is already tested)
- ❌ Don't test third-party libraries
- ❌ Don't use real database for Unit tests (use mocks)
- ❌ Don't forget to clean up (ResetDatabase trait)
- ❌ Don't write tests that depend on other tests

---

**Version**: 1.0.0
**Last Updated**: 2025-11-10
**Ready for Implementation**: ✅ Yes
