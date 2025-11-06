# 🧪 Testing Guide - Comprehensive Testing Strategy

> **TL;DR**: Backend uses PHPUnit 9.6 with Unit/Integration/Functional tests. Frontend uses Vitest with 7 test files covering composables, stores, components, services, and views. All tests follow strict isolation and mocking principles.

---

## 📖 Table of Contents

1. [Backend Testing (PHPUnit)](#backend-testing-phpunit)
2. [Frontend Testing (Vitest)](#frontend-testing-vitest)
3. [Test Writing Guidelines](#test-writing-guidelines)
4. [CI/CD Integration](#cicd-integration)
5. [Troubleshooting](#troubleshooting)

---

## Backend Testing (PHPUnit)

### Configuration

**Location**: `backend/phpunit.xml.dist`

```xml
<phpunit>
    <php>
        <server name="APP_ENV" value="test" force="true" />
        <server name="SYMFONY_PHPUNIT_VERSION" value="9.6" />
    </php>
    <extensions>
        <extension class="DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension"/>
    </extensions>
</phpunit>
```

**Key Extensions:**
- **DAMA DoctrineTestBundle** - Database transaction management for tests
- **Zenstruck Foundry** - Factory pattern for test data

### Test Organization

```
backend/tests/
├── Unit/                      # Isolated unit tests (no DB, no HTTP)
│   ├── Service/
│   │   └── UserRegistrationServiceTest.php
│   ├── Repository/
│   │   └── UserRepositoryTest.php
│   └── Security/
│       └── GoogleAuthenticatorTest.php
│
├── Integration/               # Integration tests (mocked external services)
│   └── Api/
│       ├── GoogleAuthIntegrationTest.php
│       └── GoogleAuthWithHttpMockTest.php
│
├── Functional/                # Full E2E tests (real HTTP requests)
│   └── Api/
│       ├── AuthenticationTest.php
│       ├── UserRegistrationTest.php
│       ├── TokenRefreshTest.php
│       ├── UserProfileTest.php
│       └── GoogleAuthTest.php
│
└── bootstrap.php              # Test environment setup
```

### Running Tests

```bash
# Run all tests
docker exec backend-php83 vendor/bin/phpunit

# Run specific test suite
docker exec backend-php83 vendor/bin/phpunit tests/Unit
docker exec backend-php83 vendor/bin/phpunit tests/Integration
docker exec backend-php83 vendor/bin/phpunit tests/Functional

# Run specific test file
docker exec backend-php83 vendor/bin/phpunit tests/Unit/Service/UserRegistrationServiceTest.php

# Run specific test method
docker exec backend-php83 vendor/bin/phpunit --filter testRegisterSuccessfully

# With coverage
docker exec backend-php83 vendor/bin/phpunit --coverage-html coverage
docker exec backend-php83 vendor/bin/phpunit --coverage-text
```

### Test Types Explained

#### 1. Unit Tests

**Purpose**: Test isolated components (services, entities) without dependencies.

**Characteristics:**
- ✅ Uses mocks for all dependencies
- ✅ No database access
- ✅ No HTTP requests
- ✅ Fast execution (<1ms per test)
- ✅ Pure logic testing

**Example**: `tests/Unit/Service/UserRegistrationServiceTest.php`

```php
<?php

namespace App\Tests\Unit\Service;

use App\Service\UserRegistrationService;
use App\Repository\Database\UserRepository;
use PHPUnit\Framework\TestCase;

class UserRegistrationServiceTest extends TestCase
{
    private UserRepository $userRepository;
    private UserRegistrationService $service;

    protected function setUp(): void
    {
        // Mock all dependencies
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->service = new UserRegistrationService($this->userRepository);
    }

    public function testRegisterSuccessfully(): void
    {
        // Arrange
        $dto = new UserRegistrationRequestDto(
            email: 'test@example.com',
            password: 'SecurePassword123'
        );

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        // Act
        $user = $this->service->register($dto);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test@example.com', $user->getEmail());
    }
}
```

#### 2. Integration Tests

**Purpose**: Test component integration with mocked external services (Google API, etc).

**Characteristics:**
- ✅ Uses real database (with transactions)
- ✅ Mocks external HTTP calls
- ✅ Tests API integration logic
- ✅ Medium execution speed (~100ms per test)
- ✅ Zenstruck Foundry for factories

**Example**: `tests/Integration/Api/GoogleAuthIntegrationTest.php`

```php
<?php

namespace App\Tests\Integration\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class GoogleAuthIntegrationTest extends WebTestCase
{
    use ResetDatabase; // Clears DB after each test

    public function testGoogleAuthWithMockedGoogleAPI(): void
    {
        // Mock Google API responses
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        // ... setup mocks

        $client = static::createClient();
        $client->request('POST', '/api/auth/google', [
            'code' => 'mock-auth-code'
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
    }
}
```

#### 3. Functional Tests

**Purpose**: Test complete API workflows with real HTTP requests.

**Characteristics:**
- ✅ Uses WebTestCase (Symfony)
- ✅ Real HTTP client
- ✅ Real database (with ResetDatabase)
- ✅ Tests full request/response cycle
- ✅ Slower execution (~200ms per test)
- ✅ Uses Foundry factories (UserFactory)

**Example**: `tests/Functional/Api/AuthenticationTest.php`

```php
<?php

namespace App\Tests\Functional\Api;

use App\TestsUtilities\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Test\Factories;

class AuthenticationTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testSuccessfulLogin(): void
    {
        // Arrange: Create user with known credentials
        UserFactory::createOne([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $client = static::createClient();

        // Act: Perform login
        $client->request(
            'POST',
            '/api/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test@example.com',
                'password' => 'password123',
            ])
        );

        // Assert: Check response
        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('refreshToken', $data);
        $this->assertNotEmpty($data['token']);
    }

    public function testLoginWithWrongPassword(): void
    {
        UserFactory::createOne([
            'email' => 'test@example.com',
            'password' => 'correctpassword',
        ]);

        $client = static::createClient();
        $client->request(
            'POST',
            '/api/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
```

### Key Testing Tools

#### Zenstruck Foundry

Factory pattern for test data creation:

```php
use App\TestsUtilities\Factory\UserFactory;
use App\TestsUtilities\Factory\TaskFactory;

// Create single entity
$user = UserFactory::createOne(['email' => 'test@example.com']);

// Create multiple entities
$users = UserFactory::createMany(5);

// Create with relationships
$task = TaskFactory::createOne([
    'title' => 'Test Task',
    'user' => $user,
]);
```

#### ResetDatabase Trait

Automatically resets database after each test:

```php
use Zenstruck\Foundry\Test\ResetDatabase;

class MyTest extends WebTestCase
{
    use ResetDatabase; // Database is clean for each test
}
```

---

## Frontend Testing (Vitest)

### Configuration

**Location**: `frontend/vite.config.ts`

```typescript
export default defineConfig({
  test: {
    globals: true,
    environment: 'happy-dom',
    setupFiles: ['./src/tests/setup.ts'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      exclude: [
        'node_modules/',
        'src/tests/',
        '**/*.spec.ts',
        '**/*.test.ts',
        '**/types/**',
        '**/*.d.ts'
      ]
    }
  }
})
```

**Key Configuration:**
- **Environment**: happy-dom (faster than jsdom)
- **Globals**: `describe`, `it`, `expect` available without imports
- **Coverage**: v8 provider with text/json/html reports

### Test Organization

```
frontend/src/
├── composables/
│   └── __tests__/
│       ├── useToast.spec.ts
│       └── useFormValidation.spec.ts
│
├── stores/
│   └── __tests__/
│       └── auth.store.spec.ts
│
├── components/
│   ├── ui/
│   │   └── __tests__/
│   │       └── BaseButton.spec.ts
│   └── forms/
│       └── __tests__/
│           └── LoginForm.spec.ts
│
├── views/
│   └── __tests__/
│       └── HomeView.spec.ts
│
└── services/
    └── __tests__/
        └── auth.service.spec.ts
```

**Total**: 7 test files

### Running Tests

```bash
# Navigate to frontend
cd frontend

# Run all tests once
npm run test:run

# Watch mode (reruns on changes)
npm run test

# UI mode (interactive browser interface)
npm run test:ui

# Generate coverage report
npm run test:coverage
```

### Test Examples

#### 1. Composable Test

**File**: `src/composables/__tests__/useToast.spec.ts`

```typescript
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useToast } from '../useToast'

// Mock PrimeVue's useToast
vi.mock('primevue/usetoast', () => ({
  useToast: vi.fn(() => ({
    add: vi.fn(),
  })),
}))

describe('useToast', () => {
  let mockAdd: ReturnType<typeof vi.fn>
  let toast: ReturnType<typeof useToast>

  beforeEach(() => {
    mockAdd = vi.fn()
    toast = useToast()
  })

  it('should show success toast with custom message', () => {
    toast.showSuccess('Operation completed')

    expect(mockAdd).toHaveBeenCalledWith({
      severity: 'success',
      summary: 'Success',
      detail: 'Operation completed',
      life: 3000,
    })
  })

  it('should show error toast with longer life', () => {
    toast.showError('Error message')

    expect(mockAdd).toHaveBeenCalledWith(
      expect.objectContaining({
        severity: 'error',
        life: 5000, // Errors show longer
      })
    )
  })
})
```

#### 2. Store Test

**File**: `src/stores/__tests__/auth.store.spec.ts`

```typescript
import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../auth.store'

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('should initialize with logged out state', () => {
    const authStore = useAuthStore()

    expect(authStore.isAuthenticated).toBe(false)
    expect(authStore.user).toBeNull()
  })

  it('should set user on login', () => {
    const authStore = useAuthStore()
    const user = { id: 1, email: 'test@example.com' }

    authStore.setUser(user)

    expect(authStore.isAuthenticated).toBe(true)
    expect(authStore.user).toEqual(user)
  })

  it('should clear user on logout', () => {
    const authStore = useAuthStore()
    authStore.setUser({ id: 1, email: 'test@example.com' })

    authStore.logout()

    expect(authStore.isAuthenticated).toBe(false)
    expect(authStore.user).toBeNull()
  })
})
```

#### 3. Component Test

**File**: `src/components/ui/__tests__/BaseButton.spec.ts`

```typescript
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseButton from '../BaseButton.vue'

describe('BaseButton', () => {
  it('renders button with text', () => {
    const wrapper = mount(BaseButton, {
      props: {
        label: 'Click me'
      }
    })

    expect(wrapper.text()).toContain('Click me')
  })

  it('emits click event when clicked', async () => {
    const wrapper = mount(BaseButton, {
      props: {
        label: 'Click me'
      }
    })

    await wrapper.trigger('click')

    expect(wrapper.emitted('click')).toHaveLength(1)
  })

  it('applies disabled class when disabled prop is true', () => {
    const wrapper = mount(BaseButton, {
      props: {
        label: 'Click me',
        disabled: true
      }
    })

    expect(wrapper.classes()).toContain('disabled')
  })
})
```

#### 4. Service Test

**File**: `src/services/__tests__/auth.service.spec.ts`

```typescript
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { authService } from '../auth.service'
import axios from 'axios'

vi.mock('axios')

describe('Auth Service', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should call login endpoint with credentials', async () => {
    const mockResponse = {
      data: {
        token: 'jwt-token',
        refreshToken: 'refresh-token'
      }
    }

    vi.mocked(axios.post).mockResolvedValue(mockResponse)

    const result = await authService.login('test@example.com', 'password')

    expect(axios.post).toHaveBeenCalledWith('/api/auth', {
      email: 'test@example.com',
      password: 'password'
    })
    expect(result).toEqual(mockResponse.data)
  })

  it('should throw error on failed login', async () => {
    vi.mocked(axios.post).mockRejectedValue(new Error('Invalid credentials'))

    await expect(
      authService.login('test@example.com', 'wrong')
    ).rejects.toThrow('Invalid credentials')
  })
})
```

---

## Test Writing Guidelines

### General Principles

#### 1. **Arrange-Act-Assert (AAA) Pattern**

```typescript
it('should do something', () => {
  // Arrange: Setup test data and mocks
  const input = { value: 10 }
  const expected = 20

  // Act: Execute the function under test
  const result = doubleValue(input.value)

  // Assert: Verify the result
  expect(result).toBe(expected)
})
```

#### 2. **Test Isolation**

✅ **DO**: Each test should be independent
```typescript
describe('Calculator', () => {
  beforeEach(() => {
    // Reset state before each test
    calculator = new Calculator()
  })

  it('adds numbers', () => {
    expect(calculator.add(2, 3)).toBe(5)
  })

  it('subtracts numbers', () => {
    expect(calculator.subtract(5, 3)).toBe(2)
  })
})
```

❌ **DON'T**: Share state between tests
```typescript
let sharedValue = 0 // ❌ Bad: shared state

it('test 1', () => {
  sharedValue = 5 // Affects test 2
})

it('test 2', () => {
  expect(sharedValue).toBe(0) // ❌ Fails due to test 1
})
```

#### 3. **Mock External Dependencies**

✅ **DO**: Mock HTTP, database, external services
```typescript
vi.mock('axios', () => ({
  default: {
    get: vi.fn(() => Promise.resolve({ data: mockData }))
  }
}))
```

❌ **DON'T**: Make real HTTP calls in tests
```typescript
// ❌ Bad: makes real HTTP call
await axios.get('https://api.example.com/data')
```

#### 4. **Test Behavior, Not Implementation**

✅ **DO**: Test what the code does
```typescript
it('displays error message when login fails', () => {
  // Test the observable behavior
  expect(wrapper.text()).toContain('Invalid credentials')
})
```

❌ **DON'T**: Test internal implementation
```typescript
it('calls internal method', () => {
  // ❌ Bad: testing private implementation
  expect(component.internalMethod).toHaveBeenCalled()
})
```

### Backend Test Guidelines

✅ **DO's**:
- Use appropriate test type (Unit/Integration/Functional)
- Mock all external services in Unit tests
- Use ResetDatabase trait in Integration/Functional tests
- Use Foundry factories for test data
- Test happy path AND error cases
- Test edge cases (null, empty, boundary values)

❌ **DON'Ts**:
- Don't test framework code
- Don't test getters/setters without logic
- Don't skip database cleanup
- Don't make real HTTP calls to external APIs
- Don't share fixtures between tests

### Frontend Test Guidelines

✅ **DO's**:
- Mock external dependencies (axios, stores, composables)
- Use `@vue/test-utils` for component mounting
- Test user interactions (click, input, submit)
- Test computed properties and watchers
- Test emitted events
- Use `beforeEach` for setup

❌ **DON'Ts**:
- Don't test library code (Vue, PrimeVue)
- Don't test CSS/styling
- Don't test implementation details (internal methods)
- Don't make real API calls
- Don't skip cleanup in `beforeEach`/`afterEach`

---

## CI/CD Integration

### Backend CI

```bash
# Run in CI pipeline
docker exec backend-php83 vendor/bin/phpunit --coverage-text --colors=never
```

### Frontend CI

```bash
# Run in CI pipeline
cd frontend && npm run test:run -- --reporter=junit --coverage
```

---

## Troubleshooting

### Backend Issues

**Problem**: Tests fail with database connection errors
```
Solution: Ensure APP_ENV=test and database is created
docker exec backend-php83 php bin/console doctrine:database:create --env=test
```

**Problem**: Tests leave data in database
```
Solution: Use ResetDatabase trait
use Zenstruck\Foundry\Test\ResetDatabase;
```

**Problem**: Slow integration tests
```
Solution: Use DAMA DoctrineTestBundle (already configured)
```

### Frontend Issues

**Problem**: Tests fail with "Cannot find module"
```
Solution: Check vite.config.ts alias configuration
resolve: {
  alias: {
    '@': fileURLToPath(new URL('./src', import.meta.url))
  }
}
```

**Problem**: Tests fail with "ReferenceError: document is not defined"
```
Solution: Ensure environment is set to 'happy-dom'
test: {
  environment: 'happy-dom'
}
```

**Problem**: Pinia store tests fail
```
Solution: Create new Pinia instance in beforeEach
beforeEach(() => {
  setActivePinia(createPinia())
})
```

---

## Coverage Goals

### Backend Coverage Targets
- **Unit Tests**: >80%
- **Integration Tests**: >70%
- **Functional Tests**: >60%

### Frontend Coverage Targets
- **Components**: >70%
- **Stores**: >80%
- **Services**: >80%
- **Composables**: >80%

---

## Related Documents

- **[Development Workflow](DEVELOPMENT_WORKFLOW.md)** - Running tests in development
- **[Backend Architecture](../backend/ARCHITECTURE.md)** - Understanding code structure
- **[Frontend Architecture](../frontend/ARCHITECTURE.md)** - Component patterns
- **[Troubleshooting](TROUBLESHOOTING.md)** - Common issues and fixes

---

*Last updated: 2025-11-06 by Claude Code AI*
*Documentation version: 2.0*
