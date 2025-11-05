# 🧪 Testing Guide - PHPUnit & Vitest

> **TL;DR**: PHPUnit for backend (unit + integration tests). Vitest for frontend (115 tests, 100% passing). Test organization by feature. Code examples for common scenarios.

---

## Backend Testing (PHPUnit)

### Test Organization

```
backend/tests/
├── Unit/               # Unit tests (isolated)
│   ├── Service/
│   │   └── TaskServiceTest.php
│   └── Entity/
│       └── TaskTest.php
└── Integration/        # Integration tests (with DB)
    └── Repository/
        └── TaskRepositoryTest.php
```

### Running Tests

```bash
# Run all tests
docker exec backend-php83 php bin/phpunit

# Run specific test file
docker exec backend-php83 php bin/phpunit tests/Unit/Service/TaskServiceTest.php

# Run with coverage
docker exec backend-php83 php bin/phpunit --coverage-html coverage
```

### Example Test

```php
<?php
// tests/Unit/Service/TaskServiceTest.php

namespace App\Tests\Unit\Service;

use App\Service\TaskService;
use App\Repository\Database\TaskRepository;
use PHPUnit\Framework\TestCase;

class TaskServiceTest extends TestCase
{
    public function testCreateTask(): void
    {
        $repository = $this->createMock(TaskRepository::class);
        $service = new TaskService($repository);

        $task = $service->createTask($dto, $user);

        $this->assertNotNull($task->getId());
        $this->assertEquals('Test Task', $task->getTitle());
    }
}
```

---

## Frontend Testing (Vitest)

### Test Organization

```
frontend/src/
├── components/
│   └── __tests__/
│       └── TaskCard.spec.ts
├── composables/
│   └── __tests__/
│       └── useTaskCompletion.spec.ts
└── stores/
    └── __tests__/
        └── task.store.spec.ts
```

### Running Tests

```bash
# Run all tests
npm run test:run

# Watch mode
npm run test

# With UI
npm run test:ui

# Coverage
npm run test:coverage
```

### Example Test

```typescript
// src/composables/__tests__/useTaskCompletion.spec.ts

import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useTaskCompletion } from '../useTaskCompletion'
import { useTaskStore } from '@/stores/task.store'

describe('useTaskCompletion', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('counts uncompleted subtasks correctly', () => {
    const { countUncompletedSubtasks } = useTaskCompletion()

    const task = {
      id: 1,
      title: 'Parent',
      subtasks: [
        { id: 2, isCompleted: false },
        { id: 3, isCompleted: true }
      ]
    }

    expect(countUncompletedSubtasks(task)).toBe(1)
  })
})
```

---

## Writing Tests

### DO's ✅

✅ Test business logic
✅ Test error cases
✅ Use mocks for dependencies
✅ Keep tests isolated
✅ Test edge cases

### DON'Ts ❌

❌ Test implementation details
❌ Test framework code
❌ Skip error cases
❌ Share state between tests

---

## Related Documents

- **[Development Workflow](DEVELOPMENT_WORKFLOW.md)** - Running tests
- **[Architecture](../backend/ARCHITECTURE.md)** - Code structure

---

*Last updated: 2025-01-05*
