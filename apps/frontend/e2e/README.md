# E2E Tests Setup

## Prerequisites

Before running E2E tests, ensure the test user exists in the database.

## Test User Setup

The E2E tests use the following test user credentials (defined in `fixtures/auth.fixture.ts`):
- **Email**: `sollent98@gmail.com`
- **Password**: `Pahan1998`

### Creating the Test User

Run the following commands from the project root:

```bash
# 1. Hash the password
docker exec backend-php83 php bin/console security:hash-password Pahan1998

# 2. Insert the test user (use the hashed password from step 1)
docker exec backend-psql16 psql -U user -d backend-app -c "INSERT INTO users (email, password, roles, name, theme, language, timezone, notification_settings, created_at, updated_at) VALUES ('sollent98@gmail.com', '\$2y\$13\$SpCdOvrscA2drfiRzPIqtemD87M0//x3HneYuV2anX0lutm6Olzcm', '[\"ROLE_USER\"]', 'Test User', 'light', 'ru', 'Europe/Moscow', '{\"email\": true, \"push\": true, \"taskReminders\": true, \"taskAssignments\": true, \"taskCompletion\": true, \"weeklyDigest\": false}', NOW(), NOW());"
```

### Verifying the Test User

To verify the test user exists:

```bash
docker exec backend-psql16 psql -U user -d backend-app -c "SELECT id, email, name FROM users WHERE email = 'sollent98@gmail.com';"
```

### Testing Login

To verify login works:

```bash
curl -X POST http://localhost:8089/api/auth \
  -H "Content-Type: application/json" \
  -d '{"email":"sollent98@gmail.com","password":"Pahan1998"}'
```

Expected response: JWT token and refresh token.

## Running E2E Tests

```bash
# From frontend directory
cd apps/frontend

# Run all E2E tests
npm run test:e2e

# Run specific test file
npx playwright test e2e/tests/auth/login.spec.ts

# Run with UI
npx playwright test --ui

# View test report
npx playwright show-report
```

## Troubleshooting

### All tests failing with timeout

**Symptom**: Tests reach dashboard but timeout on UI interactions.

**Cause**: Test user doesn't exist in database.

**Solution**: Follow "Creating the Test User" steps above.

### Authentication errors

**Symptom**: Tests fail at login with 401/400 errors.

**Possible causes**:
1. Test user password is incorrect
2. API endpoint changed
3. Backend not running

**Solution**:
- Verify API endpoint in `src/config/constants.ts` (should be `/api/auth`)
- Check backend is running: `docker ps | grep backend`
- Verify password with curl command above
