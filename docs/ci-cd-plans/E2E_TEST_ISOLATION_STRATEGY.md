# Стратегия изоляции E2E тестов для CI/CD

> **Статус**: 📋 Планирование
> **Приоритет**: 🔴 Критический
> **Ожидаемое время**: 2-3 дня
> **Создано**: 2025-11-12

---

## 📊 Текущая проблема

### Ситуация
E2E тесты в настоящее время используют **захардкоженного тестового пользователя** (`sollent98@gmail.com`) в **общей dev базе данных**:
- `apps/frontend/e2e/fixtures/auth.fixture.ts:64-66`
- Пользователь должен существовать в базе данных до запуска тестов
- Тесты НЕ изолированы - они делят одно и то же состояние базы данных
- **Блокирует CI/CD**: Невозможно надежно запускать параллельные тестовые наборы

### Последствия
1. ❌ Тесты падают, если пользователь не существует или пароль изменен
2. ❌ Тесты мешают друг другу (race conditions)
3. ❌ Невозможно запускать несколько CI пайплайнов параллельно
4. ❌ Нет чистого старта - тесты зависят от существующих данных
5. ❌ Невозможно воспроизвести сбои локально

---

## 🎯 Требования

### Функциональные требования
1. ✅ Каждый запуск E2E тестов должен использовать **изолированную тестовую базу данных**
2. ✅ Тестовые пользователи должны **создаваться автоматически** перед тестами
3. ✅ База данных должна **очищаться** после тестов (опционально)
4. ✅ Должно работать как **локально**, так и в **CI/CD**
5. ✅ Тесты должны быть **воспроизводимыми** и **детерминированными**

### Нефункциональные требования
1. ⚡ Быстрая настройка (< 30 секунд)
2. 🔒 Без влияния на dev/prod базы данных
3. 🔄 Поддержка параллельного выполнения тестов
4. 📦 Простота настройки и обслуживания

---

## 🏗️ Рекомендуемая архитектура

### Обзор
```
┌─────────────────────────────────────────────────────┐
│                  CI/CD Pipeline                     │
├─────────────────────────────────────────────────────┤
│  1. Запуск тестового окружения                      │
│     ├─ docker-compose.test.yml                      │
│     ├─ PostgreSQL (test-db)                         │
│     ├─ Backend API                                  │
│     └─ Frontend dev server                          │
│                                                      │
│  2. Глобальная настройка (Playwright)               │
│     ├─ Запуск миграций                              │
│     ├─ Создание тестовых пользователей              │
│     └─ Заполнение минимальными тестовыми данными    │
│                                                      │
│  3. Запуск E2E тестов                               │
│     └─ Все тесты используют одного тестового        │
│        пользователя                                 │
│                                                      │
│  4. Глобальная очистка (Опционально)                │
│     └─ Остановка контейнеров, очистка volumes       │
└─────────────────────────────────────────────────────┘
```

---

## 📝 План реализации

### Фаза 1: Настройка тестовой базы данных (День 1, утро)
**Цель**: Создать изолированный PostgreSQL для E2E тестов

#### 1.1 Создать `docker-compose.test.yml`
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
      - "15433:5432"  # Другой порт, чем у dev
    volumes:
      - test-db-data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-CHOWN", "pg_isready", "-U", "test_user"]
      interval: 5s
      timeout: 5s
      retries: 5

  test-backend:
    # То же что и dev backend, но использует test-db
    build:
      context: ../../apps/backend
      dockerfile: ../../infrastructure/docker/Dockerfile.php
    environment:
      DATABASE_URL: "postgresql://test_user:test_password@test-db:5432/backend_test?serverVersion=16&charset=utf8"
      APP_ENV: test
    ports:
      - "8090:80"  # Другой порт, чем у dev
    depends_on:
      test-db:
        condition: service_healthy

volumes:
  test-db-data:
```

#### 1.2 Создать `.env.test` для backend
```bash
# apps/backend/.env.test
DATABASE_URL="postgresql://test_user:test_password@localhost:15433/backend_test?serverVersion=16&charset=utf8"
APP_ENV=test
APP_DEBUG=true
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-passphrase
```

**Файлы для создания:**
- `infrastructure/docker/docker-compose.test.yml`
- `apps/backend/.env.test`

---

### Фаза 2: Глобальная настройка Playwright (День 1, после обеда)
**Цель**: Автоматически подготовить тестовую базу данных и пользователя

#### 2.1 Создать скрипт глобальной настройки
```typescript
// apps/frontend/e2e/global-setup.ts
import { chromium, FullConfig } from '@playwright/test'
import { exec } from 'child_process'
import { promisify } from 'util'

const execAsync = promisify(exec)

async function globalSetup(config: FullConfig) {
  console.log('🚀 Запуск глобальной настройки E2E...')

  // 1. Запуск тестового окружения (если еще не запущено)
  if (process.env.CI) {
    console.log('📦 Запуск тестовых Docker контейнеров...')
    await execAsync('docker-compose -f infrastructure/docker/docker-compose.test.yml up -d')

    // Ожидание, пока сервисы станут healthy
    await execAsync('docker-compose -f infrastructure/docker/docker-compose.test.yml exec -T test-backend php bin/console doctrine:migrations:migrate --no-interaction')
  }

  // 2. Создание тестового пользователя через API
  const API_URL = process.env.PLAYWRIGHT_API_URL || 'http://localhost:8090'
  const TEST_USER_EMAIL = process.env.E2E_TEST_USER_EMAIL || 'e2e-test@example.com'
  const TEST_USER_PASSWORD = process.env.E2E_TEST_USER_PASSWORD || 'TestPassword123!'

  console.log(`👤 Создание тестового пользователя: ${TEST_USER_EMAIL}`)

  try {
    const browser = await chromium.launch()
    const context = await browser.newContext()
    const page = await context.newPage()

    // Попытка зарегистрировать тестового пользователя (упадет, если уже существует - это OK)
    const response = await page.request.post(`${API_URL}/api/users`, {
      data: {
        email: TEST_USER_EMAIL,
        password: TEST_USER_PASSWORD,
        confirmPassword: TEST_USER_PASSWORD
      }
    })

    if (response.ok()) {
      console.log('✅ Тестовый пользователь успешно создан')
    } else if (response.status() === 400) {
      const body = await response.json()
      if (body.message?.includes('already exists')) {
        console.log('ℹ️  Тестовый пользователь уже существует (OK)')
      } else {
        console.error('❌ Не удалось создать тестового пользователя:', body)
      }
    }

    await browser.close()
  } catch (error) {
    console.error('❌ Глобальная настройка не удалась:', error)
    throw error
  }

  console.log('✅ Глобальная настройка завершена\n')
}

export default globalSetup
```

#### 2.2 Создать скрипт глобальной очистки (опционально)
```typescript
// apps/frontend/e2e/global-teardown.ts
import { FullConfig } from '@playwright/test'
import { exec } from 'child_process'
import { promisify } from 'util'

const execAsync = promisify(exec)

async function globalTeardown(config: FullConfig) {
  console.log('\n🧹 Запуск глобальной очистки E2E...')

  if (process.env.CI) {
    console.log('🛑 Остановка тестовых Docker контейнеров...')
    await execAsync('docker-compose -f infrastructure/docker/docker-compose.test.yml down -v')
    console.log('✅ Тестовое окружение очищено')
  }
}

export default globalTeardown
```

#### 2.3 Обновить `playwright.config.ts`
```typescript
// apps/frontend/e2e/playwright.config.ts
import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
  testDir: './tests',

  // Добавить глобальную настройку/очистку
  globalSetup: require.resolve('./global-setup'),
  globalTeardown: require.resolve('./global-teardown'),

  // ... остальная конфигурация

  use: {
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:3000',
    // Использовать тестовый backend в CI
    ...(process.env.CI && {
      baseURL: 'http://localhost:3000',
    }),
  },

  // ... остальная конфигурация
})
```

**Файлы для создания:**
- `apps/frontend/e2e/global-setup.ts`
- `apps/frontend/e2e/global-teardown.ts`

**Файлы для изменения:**
- `apps/frontend/e2e/playwright.config.ts`

---

### Фаза 3: Обновление тестовых фикстур (День 2, утро)
**Цель**: Использовать переменные окружения для учетных данных тестового пользователя

#### 3.1 Обновить `auth.fixture.ts`
```typescript
// apps/frontend/e2e/fixtures/auth.fixture.ts

/**
 * Учетные данные тестового пользователя для тестов авторизации
 * Использует переменные окружения в CI/CD, откатывается к значениям по умолчанию локально
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

**Файлы для изменения:**
- `apps/frontend/e2e/fixtures/auth.fixture.ts` (строки 62-75)

---

### Фаза 4: Настройка для локальной разработки (День 2, после обеда)
**Цель**: Упростить разработчикам запуск E2E тестов локально

#### 4.1 Создать NPM скрипты
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

#### 4.2 Создать скрипт локальной настройки
```javascript
// apps/frontend/e2e/scripts/local-setup.js
const { execSync } = require('child_process')

console.log('🔧 Настройка локального окружения для E2E тестов...\n')

// 1. Проверить, запущен ли Docker
try {
  execSync('docker info', { stdio: 'ignore' })
} catch {
  console.error('❌ Docker не запущен. Пожалуйста, сначала запустите Docker.')
  process.exit(1)
}

// 2. Проверить, существует ли тестовый пользователь, создать, если нет
const API_URL = 'http://localhost:8089'  // Dev backend
const TEST_USER = {
  email: 'e2e-test@example.com',
  password: 'TestPassword123!'
}

console.log(`👤 Проверка тестового пользователя: ${TEST_USER.email}`)

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
        console.log('✅ Тестовый пользователь создан')
      } else if (res.statusCode === 400) {
        console.log('ℹ️  Тестовый пользователь уже существует (OK)')
      } else {
        console.warn(`⚠️  Неожиданный статус: ${res.statusCode}`)
      }
      resolve()
    })

    req.on('error', (error) => {
      console.error('❌ Не удалось создать тестового пользователя:', error.message)
      console.log('⚠️  Убедитесь, что backend запущен: docker-compose up -d')
      process.exit(1)
    })

    req.write(data)
    req.end()
  })
}

registerUser().then(() => {
  console.log('\n✅ Локальное E2E окружение готово!')
  console.log('\nТеперь вы можете запустить: npm run test:e2e')
})
```

**Файлы для создания:**
- `apps/frontend/e2e/scripts/local-setup.js`

**Файлы для изменения:**
- `apps/frontend/package.json`

---

### Фаза 5: Интеграция CI/CD (День 3)
**Цель**: Настроить GitHub Actions (или другой CI) для запуска E2E тестов

#### 5.1 Создать workflow для GitHub Actions
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
          # Ожидание, пока сервисы станут healthy
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

**Файлы для создания:**
- `.github/workflows/e2e-tests.yml`

---

## 🎯 Альтернативные решения (Не рекомендуются)

### Вариант B: Изоляция на основе транзакций
**Плюсы:**
- Не требуется отдельная база данных
- Быстрая настройка

**Минусы:**
- ❌ Сложно реализовать для E2E (пересекает backend/frontend)
- ❌ Невозможно откатить состояние браузера
- ❌ Не работает с реальными HTTP запросами
- ❌ Race conditions в параллельных тестах

**Вердикт**: ❌ Не подходит для E2E тестов

---

### Вариант C: Заполнение базы данных перед каждым тестом
**Плюсы:**
- Простая реализация
- Использует существующий GenerateTestDataFastCommand

**Минусы:**
- ❌ Медленно (30+ секунд на запуск теста)
- ❌ Все еще использует общую базу данных
- ❌ Race conditions при параллельном выполнении

**Вердикт**: ❌ Подходит только для локальной разработки

---

## 📊 Сравнительная матрица

| Аспект | Текущее состояние | Рекомендуется (изолированная БД) | Вариант B (Транзакции) | Вариант C (Заполнение) |
|--------|------------------|----------------------------------|------------------------|------------------------|
| **Изоляция** | ❌ Нет | ✅ Полная | ⚠️ Частичная | ❌ Нет |
| **Параллельные тесты** | ❌ Нет | ✅ Да | ❌ Нет | ❌ Нет |
| **Готовность CI/CD** | ❌ Нет | ✅ Да | ⚠️ Частично | ❌ Нет |
| **Время настройки** | Быстро | 30с | Быстро | 30-60с |
| **Поддерживаемость** | ⚠️ Сложно | ✅ Легко | ❌ Сложно | ⚠️ Средне |
| **Воспроизводимость** | ❌ Нет | ✅ Да | ⚠️ Частично | ⚠️ Частично |

---

## ✅ Критерии успеха

### Фаза 1-2 (Базовая изоляция)
- [ ] Тестовая база данных работает в Docker
- [ ] Глобальная настройка автоматически создает тестового пользователя
- [ ] E2E тесты проходят с использованием изолированной базы данных

### Фаза 3-4 (Developer Experience)
- [ ] Разработчики могут запускать E2E тесты одной командой
- [ ] Учетные данные тестового пользователя настраиваются через env vars
- [ ] Документация обновлена

### Фаза 5 (CI/CD)
- [ ] GitHub Actions workflow проходит успешно
- [ ] Тесты выполняются менее чем за 5 минут
- [ ] Отчеты о тестах загружаются как артефакты

---

## 📚 Дополнительные рекомендации

### 1. Стратегия очистки базы данных
```bash
# Вариант A: Полная очистка после тестов (медленнее, но чище)
docker-compose -f infrastructure/docker/docker-compose.test.yml down -v

# Вариант B: Сохранять БД между запусками (быстрее, но может накапливать данные)
docker-compose -f infrastructure/docker/docker-compose.test.yml down
# Только сброс пароля пользователя при необходимости
```

### 2. Управление тестовыми данными
Создать отдельную команду для данных E2E тестов:
```bash
php bin/console app:e2e:seed
```
Она должна создавать:
- 1 тестового пользователя (e2e-test@example.com)
- 5-10 примеров задач с разными состояниями
- 2-3 примера тегов
- Без лишних данных (минимум для скорости)

### 3. Переменные окружения
Добавить в документацию:
```bash
# Локальная разработка
E2E_TEST_USER_EMAIL=e2e-test@example.com
E2E_TEST_USER_PASSWORD=TestPassword123!

# CI/CD (использовать другой email во избежание конфликтов)
E2E_TEST_USER_EMAIL=e2e-ci-test@example.com
E2E_TEST_USER_PASSWORD=<secure-generated-password>
```

---

## 🚀 Быстрый старт после реализации

### Для разработчиков (локально)
```bash
# Однократная настройка
npm run test:e2e:setup

# Запуск тестов
npm run test:e2e

# Отладка тестов
npm run test:e2e:ui
```

### Для CI/CD
```bash
# Автоматически через GitHub Actions
git push origin main
# Проверить: https://github.com/<org>/<repo>/actions
```

---

## 📝 Примечания и соображения

1. **Версия Docker Compose**: Тестовое окружение требует Docker Compose v2.0+
2. **Конфликты портов**: Тестовые сервисы используют другие порты (8090, 15433), чтобы избежать конфликтов с dev
3. **Постоянство базы данных**: Данные тестовой БД эфемерны (volumes очищаются после teardown)
4. **JWT ключи**: Тестовый backend нуждается в JWT ключах - скопировать из dev или сгенерировать новые
5. **Миграции**: Всегда запускать миграции в global setup, чтобы убедиться, что схема актуальна

---

## 🔗 Связанные документы
- [План E2E тестирования](../guides/e2e/E2E_TESTING_PLAN.md)
- [Git workflow для E2E](../guides/e2e/E2E_GIT_WORKFLOW.md)
- [Рабочий процесс разработки](../guides/DEVELOPMENT_WORKFLOW.md)
- [Руководство по тестированию](../guides/testing/TESTING.md)

---

**Последнее обновление**: 2025-11-12
**Следующий обзор**: После завершения Фазы 2
