# E2E Test Isolation Strategy for CI/CD

> **Status**: 📋 Planning
> **Priority**: 🔴 Critical
> **Estimated Time**: 2-3 days
> **Created**: 2025-11-12

---

## 📊 Current Problem

### Situation
E2E tests currently use a **hardcoded test user** (`sollent98@gmail.com`) in the **shared dev database**:
- `apps/frontend/e2e/fixtures/auth.fixture.ts:64-66`
- User must exist in database before tests run
- Tests are NOT isolated - they share the same database state
- **Blocks CI/CD**: Can't run parallel test suites reliably

### Consequences
1. ❌ Tests fail if user doesn't exist or password changes
2. ❌ Tests interfere with each other (race conditions)
3. ❌ Can't run multiple CI pipelines in parallel
4. ❌ No clean slate - tests depend on existing data
5. ❌ Impossible to reproduce failures locally

---

## 🎯 Requirements

### Functional Requirements
1. ✅ Each E2E test run should use **isolated test database**
2. ✅ Test user(s) should be **created automatically** before tests
3. ✅ Database should be **cleaned up** after tests (optional)
4. ✅ Should work both **locally** and in **CI/CD**
5. ✅ Tests should be **reproducible** and **deterministic**

### Non-Functional Requirements
1. ⚡ Fast setup time (< 30 seconds)
2. 🔒 No impact on dev/prod databases
3. 🔄 Support parallel test execution
4. 📦 Easy to configure and maintain

---

## 🏗️ Recommended Architecture

### Overview
```
┌─────────────────────────────────────────────────────┐
│                  CI/CD Pipeline                     │
├─────────────────────────────────────────────────────┤
│  1. Start Test Environment                          │
│     ├─ docker-compose.test.yml                      │
│     ├─ PostgreSQL (test-db)                         │
│     ├─ Backend API                                  │
│     └─ Frontend dev server                          │
│                                                      │
│  2. Global Setup (Playwright)                       │
│     ├─ Run migrations                               │
│     ├─ Create test user(s)                          │
│     └─ Seed minimal test data                       │
│                                                      │
│  3. Run E2E Tests                                   │
│     └─ All tests use the same test user             │
│                                                      │
│  4. Global Teardown (Optional)                      │
│     └─ Stop containers, clean volumes               │
└─────────────────────────────────────────────────────┘
```

---

## 📝 Implementation Plan

### Phase 1: Test Database Setup (Day 1, Morning)
**Goal**: Create isolated PostgreSQL for E2E tests

#### 1.1 Create `docker-compose.test.yml`
```yaml
# infrastructure/docker/docker-compose.test.yml
version: '3.8'

services:
  test-db:
    image: postgres:16.0-alpine
    environment:
      POSTGRES_DB: backend_test
      POSTGRES_USER: test_user
      POSTGRES_PASSWORD: test_password
    ports:
      - "15433:5432"  # Different port than dev
    volumes:
      - test-db-data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-CHOWN", "pg_isready", "-U", "test_user"]
      interval: 5s
      timeout: 5s
      retries: 5

  test-backend:
    # Same as dev backend but uses test-db
    build:
      context: ../../apps/backend
      dockerfile: ../../infrastructure/docker/Dockerfile.php
    environment:
      DATABASE_URL: "postgresql://test_user:test_password@test-db:5432/backend_test?serverVersion=16&charset=utf8"
      APP_ENV: test
    ports:
      - "8090:80"  # Different port than dev
    depends_on:
      test-db:
        condition: service_healthy

volumes:
  test-db-data:
```

#### 1.2 Create `.env.test` for backend
```bash
# apps/backend/.env.test
DATABASE_URL="postgresql://test_user:test_password@localhost:15433/backend_test?serverVersion=16&charset=utf8"
APP_ENV=test
APP_DEBUG=true
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-passphrase
```

**Files to create:**
- `infrastructure/docker/docker-compose.test.yml`
- `apps/backend/.env.test`

---

### Phase 2: Playwright Global Setup (Day 1, Afternoon)
**Goal**: Automatically prepare test database and user

#### 2.1 Create Global Setup Script
```typescript
// apps/frontend/e2e/global-setup.ts
import { chromium, FullConfig } from '@playwright/test'
import { exec } from 'child_process'
import { promisify } from 'util'

const execAsync = promisify(exec)

async function globalSetup(config: FullConfig) {
  console.log('🚀 Starting E2E Global Setup...')

  // 1. Start test environment (if not already running)
  if (process.env.CI) {
    console.log('📦 Starting test Docker containers...')
    await execAsync('docker-compose -f infrastructure/docker/docker-compose.test.yml up -d')

    // Wait for services to be healthy
    await execAsync('docker-compose -f infrastructure/docker/docker-compose.test.yml exec -T test-backend php bin/console doctrine:migrations:migrate --no-interaction')
  }

  // 2. Create test user via API
  const API_URL = process.env.PLAYWRIGHT_API_URL || 'http://localhost:8090'
  const TEST_USER_EMAIL = process.env.E2E_TEST_USER_EMAIL || 'e2e-test@example.com'
  const TEST_USER_PASSWORD = process.env.E2E_TEST_USER_PASSWORD || 'TestPassword123!'

  console.log(`👤 Creating test user: ${TEST_USER_EMAIL}`)

  try {
    const browser = await chromium.launch()
    const context = await browser.newContext()
    const page = await context.newPage()

    // Try to register test user (will fail if already exists - that's OK)
    const response = await page.request.post(`${API_URL}/api/users`, {
      data: {
        email: TEST_USER_EMAIL,
        password: TEST_USER_PASSWORD,
        confirmPassword: TEST_USER_PASSWORD
      }
    })

    if (response.ok()) {
      console.log('✅ Test user created successfully')
    } else if (response.status() === 400) {
      const body = await response.json()
      if (body.message?.includes('already exists')) {
        console.log('ℹ️  Test user already exists (OK)')
      } else {
        console.error('❌ Failed to create test user:', body)
      }
    }

    await browser.close()
  } catch (error) {
    console.error('❌ Global setup failed:', error)
    throw error
  }

  console.log('✅ Global Setup Complete\n')
}

export default globalSetup
```

#### 2.2 Create Global Teardown Script (Optional)
```typescript
// apps/frontend/e2e/global-teardown.ts
import { FullConfig } from '@playwright/test'
import { exec } from 'child_process'
import { promisify } from 'util'

const execAsync = promisify(exec)

async function globalTeardown(config: FullConfig) {
  console.log('\n🧹 Starting E2E Global Teardown...')

  if (process.env.CI) {
    console.log('🛑 Stopping test Docker containers...')
    await execAsync('docker-compose -f infrastructure/docker/docker-compose.test.yml down -v')
    console.log('✅ Test environment cleaned up')
  }
}

export default globalTeardown
```

#### 2.3 Update `playwright.config.ts`
```typescript
// apps/frontend/e2e/playwright.config.ts
import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
  testDir: './tests',

  // Add global setup/teardown
  globalSetup: require.resolve('./global-setup'),
  globalTeardown: require.resolve('./global-teardown'),

  // ... rest of config

  use: {
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:3000',
    // Use test backend in CI
    ...(process.env.CI && {
      baseURL: 'http://localhost:3000',
    }),
  },

  // ... rest of config
})
```

**Files to create:**
- `apps/frontend/e2e/global-setup.ts`
- `apps/frontend/e2e/global-teardown.ts`

**Files to modify:**
- `apps/frontend/e2e/playwright.config.ts`

---

### Phase 3: Update Test Fixtures (Day 2, Morning)
**Goal**: Use environment variables for test user credentials

#### 3.1 Update `auth.fixture.ts`
```typescript
// apps/frontend/e2e/fixtures/auth.fixture.ts

/**
 * Test user credentials for login tests
 * Uses environment variables in CI/CD, falls back to defaults locally
 */
export const testLoginUsers = {
  valid: {
    email: process.env.E2E_TEST_USER_EMAIL || 'e2e-test@example.com',
    password: process.env.E2E_TEST_USER_PASSWORD || 'TestPassword123!'
  },
  invalidCredentials: {
    email: 'nonexistent@example.com',
    password: 'WrongPassword123!'
  },
  wrongPassword: {
    email: process.env.E2E_TEST_USER_EMAIL || 'e2e-test@example.com',
    password: 'WrongPassword123!'
  }
}
```

**Files to modify:**
- `apps/frontend/e2e/fixtures/auth.fixture.ts` (lines 62-75)

---

### Phase 4: Local Development Setup (Day 2, Afternoon)
**Goal**: Make it easy for developers to run E2E tests locally

#### 4.1 Create NPM Scripts
```json
// apps/frontend/package.json
{
  "scripts": {
    "test:e2e": "playwright test --config=e2e/playwright.config.ts",
    "test:e2e:ui": "playwright test --config=e2e/playwright.config.ts --ui",
    "test:e2e:setup": "node e2e/scripts/local-setup.js",
    "test:e2e:full": "npm run test:e2e:setup && npm run test:e2e"
  }
}
```

#### 4.2 Create Local Setup Script
```javascript
// apps/frontend/e2e/scripts/local-setup.js
const { execSync } = require('child_process')

console.log('🔧 Setting up local E2E test environment...\n')

// 1. Check if Docker is running
try {
  execSync('docker info', { stdio: 'ignore' })
} catch {
  console.error('❌ Docker is not running. Please start Docker first.')
  process.exit(1)
}

// 2. Check if test user exists, create if not
const API_URL = 'http://localhost:8089'  // Dev backend
const TEST_USER = {
  email: 'e2e-test@example.com',
  password: 'TestPassword123!'
}

console.log(`👤 Checking test user: ${TEST_USER.email}`)

const https = require('http')
const registerUser = () => {
  return new Promise((resolve) => {
    const data = JSON.stringify({
      email: TEST_USER.email,
      password: TEST_USER.password,
      confirmPassword: TEST_USER.password
    })

    const req = https.request({
      hostname: 'localhost',
      port: 8089,
      path: '/api/users',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': data.length
      }
    }, (res) => {
      if (res.statusCode === 201) {
        console.log('✅ Test user created')
      } else if (res.statusCode === 400) {
        console.log('ℹ️  Test user already exists (OK)')
      } else {
        console.warn(`⚠️  Unexpected status: ${res.statusCode}`)
      }
      resolve()
    })

    req.on('error', (error) => {
      console.error('❌ Failed to create test user:', error.message)
      console.log('⚠️  Make sure backend is running: docker-compose up -d')
      process.exit(1)
    })

    req.write(data)
    req.end()
  })
}

registerUser().then(() => {
  console.log('\n✅ Local E2E environment ready!')
  console.log('\nYou can now run: npm run test:e2e')
})
```

**Files to create:**
- `apps/frontend/e2e/scripts/local-setup.js`

**Files to modify:**
- `apps/frontend/package.json`

---

### Phase 5: CI/CD Integration (Day 3)
**Goal**: Configure GitHub Actions (or other CI) to run E2E tests

#### 5.1 Create GitHub Actions Workflow
```yaml
# .github/workflows/e2e-tests.yml
name: E2E Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  e2e-tests:
    runs-on: ubuntu-latest
    timeout-minutes: 20

    env:
      E2E_TEST_USER_EMAIL: e2e-ci-test@example.com
      E2E_TEST_USER_PASSWORD: TestPassword123!
      PLAYWRIGHT_BASE_URL: http://localhost:3000
      PLAYWRIGHT_API_URL: http://localhost:8090

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: apps/frontend/package-lock.json

      - name: Install frontend dependencies
        working-directory: apps/frontend
        run: npm ci

      - name: Install Playwright browsers
        working-directory: apps/frontend
        run: npx playwright install --with-deps chromium

      - name: Start test environment
        run: |
          docker-compose -f infrastructure/docker/docker-compose.test.yml up -d
          # Wait for services to be healthy
          timeout 60 bash -c 'until docker-compose -f infrastructure/docker/docker-compose.test.yml exec -T test-backend php bin/console doctrine:database:create --if-not-exists; do sleep 2; done'

      - name: Run database migrations
        run: |
          docker-compose -f infrastructure/docker/docker-compose.test.yml exec -T test-backend php bin/console doctrine:migrations:migrate --no-interaction

      - name: Run E2E tests
        working-directory: apps/frontend
        env:
          CI: true
        run: npm run test:e2e

      - name: Upload test results
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: playwright-report
          path: apps/frontend/playwright-report/
          retention-days: 7

      - name: Upload test videos
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: playwright-videos
          path: apps/frontend/test-results/
          retention-days: 7

      - name: Stop test environment
        if: always()
        run: docker-compose -f infrastructure/docker/docker-compose.test.yml down -v
```

**Files to create:**
- `.github/workflows/e2e-tests.yml`

---

## 🎯 Alternative Solutions (Not Recommended)

### Option B: Transaction-Based Isolation
**Pros:**
- No separate database needed
- Fast setup

**Cons:**
- ❌ Complex to implement for E2E (crosses backend/frontend)
- ❌ Can't rollback browser state
- ❌ Doesn't work with real HTTP requests
- ❌ Race conditions in parallel tests

**Verdict**: ❌ Not suitable for E2E tests

---

### Option C: Database Seeding Before Each Test
**Pros:**
- Simple implementation
- Uses existing GenerateTestDataFastCommand

**Cons:**
- ❌ Slow (30+ seconds per test run)
- ❌ Still uses shared database
- ❌ Race conditions in parallel execution

**Verdict**: ❌ Only suitable for local development

---

## 📊 Comparison Matrix

| Aspect | Current State | Recommended (Isolated DB) | Option B (Transactions) | Option C (Seeding) |
|--------|--------------|---------------------------|-------------------------|-------------------|
| **Isolation** | ❌ None | ✅ Full | ⚠️ Partial | ❌ None |
| **Parallel Tests** | ❌ No | ✅ Yes | ❌ No | ❌ No |
| **CI/CD Ready** | ❌ No | ✅ Yes | ⚠️ Partial | ❌ No |
| **Setup Time** | Fast | 30s | Fast | 30-60s |
| **Maintainability** | ⚠️ Hard | ✅ Easy | ❌ Hard | ⚠️ Medium |
| **Reproducibility** | ❌ No | ✅ Yes | ⚠️ Partial | ⚠️ Partial |

---

## ✅ Success Criteria

### Phase 1-2 (Basic Isolation)
- [ ] Test database runs in Docker
- [ ] Global setup creates test user automatically
- [ ] E2E tests pass using isolated database

### Phase 3-4 (Developer Experience)
- [ ] Developers can run E2E tests with single command
- [ ] Test user credentials configurable via env vars
- [ ] Documentation updated

### Phase 5 (CI/CD)
- [ ] GitHub Actions workflow passes
- [ ] Tests run in < 5 minutes
- [ ] Test reports uploaded as artifacts

---

## 📚 Additional Recommendations

### 1. Database Cleanup Strategy
```bash
# Option A: Full cleanup after tests (slower but cleaner)
docker-compose -f infrastructure/docker/docker-compose.test.yml down -v

# Option B: Keep DB between runs (faster, but may accumulate data)
docker-compose -f infrastructure/docker/docker-compose.test.yml down
# Only reset user password if needed
```

### 2. Test Data Management
Create a dedicated command for E2E test data:
```bash
php bin/console app:e2e:seed
```
This should create:
- 1 test user (e2e-test@example.com)
- 5-10 sample tasks with different states
- 2-3 sample tags
- No unnecessary data (keep it minimal for speed)

### 3. Environment Variables
Add to documentation:
```bash
# Local development
E2E_TEST_USER_EMAIL=e2e-test@example.com
E2E_TEST_USER_PASSWORD=TestPassword123!

# CI/CD (use different email to avoid conflicts)
E2E_TEST_USER_EMAIL=e2e-ci-test@example.com
E2E_TEST_USER_PASSWORD=<secure-generated-password>
```

---

## 🚀 Quick Start After Implementation

### For Developers (Local)
```bash
# One-time setup
npm run test:e2e:setup

# Run tests
npm run test:e2e

# Debug tests
npm run test:e2e:ui
```

### For CI/CD
```bash
# Automatic via GitHub Actions
git push origin main
# Check: https://github.com/<org>/<repo>/actions
```

---

## 📝 Notes & Considerations

1. **Docker Compose Version**: Test environment requires Docker Compose v2.0+
2. **Port Conflicts**: Test services use different ports (8090, 15433) to avoid dev conflicts
3. **Database Persistence**: Test DB data is ephemeral (volumes cleaned after teardown)
4. **JWT Keys**: Test backend needs JWT keys - copy from dev or generate new ones
5. **Migrations**: Always run migrations in global setup to ensure schema is up-to-date

---

## 🔗 Related Documents
- [E2E Testing Plan](../guides/e2e/E2E_TESTING_PLAN.md)
- [E2E Git Workflow](../guides/e2e/E2E_GIT_WORKFLOW.md)
- [Development Workflow](../guides/DEVELOPMENT_WORKFLOW.md)
- [Testing Guide](../guides/testing/TESTING.md)

---

**Last Updated**: 2025-11-12
**Next Review**: After Phase 2 completion
