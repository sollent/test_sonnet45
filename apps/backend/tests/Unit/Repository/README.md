# Repository Unit Tests - Important Notice

## Summary

**All repository tests in this directory are intentionally skipped.**

Doctrine's `ServiceEntityRepository` cannot be effectively unit tested due to deep framework coupling with ClassMetadata and EntityManager infrastructure.

## The Problem

When attempting to unit test repositories by mocking `ManagerRegistry`:

```php
$this->entityManager = $this->createMock(EntityManagerInterface::class);
$this->registry = $this->createMock(ManagerRegistry::class);
$this->registry->method('getManagerForClass')->willReturn($this->entityManager);
$this->repository = new TaskRepository($this->registry);
```

**Error occurs:**
```
Typed property Doctrine\ORM\Mapping\ClassMetadata::$name must not be accessed before initialization
```

### Why This Happens

1. `ServiceEntityRepository` constructor calls `parent::__construct($registry, EntityClass::class)`
2. Parent constructor requires fully configured Doctrine infrastructure:
   - EntityManager with ClassMetadata
   - Metadata cache factory
   - Proper ORM configuration
3. Simple mocks cannot provide this complex infrastructure

## The Solution

**Use Integration Tests instead of Unit Tests**

Repositories should be tested using:
- Symfony's `KernelTestCase` or `WebTestCase`
- Real test database (SQLite in-memory or PostgreSQL test instance)
- Doctrine fixtures for test data
- Transaction rollback after each test

## Integration Test Location

Integration tests will be created in:
```
backend/tests/Integration/Repository/
├── TaskRepositoryTest.php
├── TagRepositoryTest.php
├── UserRepositoryTest.php
├── MediaObjectRepositoryTest.php
├── TaskAttachmentRepositoryTest.php
└── RecurrenceRuleRepositoryTest.php
```

## Repository Methods Requiring Integration Tests

### TaskRepository (40+ methods)
- CRUD: `save()`, `remove()`
- Queries: `findUserTasks()`, `findTodayTasks()`, `findOverdueTasks()`, etc.
- Analytics: `getUserTaskStatistics()`, `getCompletionRate()`, etc.
- Calendar: `getTasksForCalendar()`, `getTasksByDateRange()`
- Search: `searchTasks()`, `findActiveTasks()`, `countActiveTasks()`

### TagRepository (8 methods)
- CRUD: `save()`, `remove()`
- Queries: `findUserTags()`, `findByNameAndUser()`, `searchTags()`
- Complex: `findOrCreateByNames()`, `getMostUsedTags()`, `updateUsageCounts()`

### UserRepository (5 methods)
- CRUD: `save()`, `remove()`
- Auth: `upgradePassword()` (PasswordUpgraderInterface)
- Queries: `findByEmail()`, `findOneByGoogleId()`

### MediaObjectRepository (2 methods)
- CRUD: `save()`, `remove()`

### TaskAttachmentRepository (2 methods)
- CRUD: `save()`, `remove()`

### RecurrenceRuleRepository (4 methods)
- CRUD: `save()`, `remove()`
- Queries: `findActiveRulesToProcess()`, `deactivateExpiredRules()`

## References

- **Symfony Testing Docs**: https://symfony.com/doc/current/testing.html#integration-tests
- **Doctrine Testing**: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/testing.html
- **DAMA DoctrineTestBundle**: https://github.com/dmaicher/doctrine-test-bundle (for transaction rollback)

## Test Statistics

- **Total Repository Tests**: 6 (all skipped)
- **Reason**: Require integration testing with real database
- **Next Phase**: Controller Functional Tests (Phase 3)

## Created

- **Date**: 2025-11-07
- **Phase**: Backend Testing Plan - Phase 2
- **Status**: Documented and ready for integration testing implementation
