# Стратегия изоляции E2E тестов для CI/CD

> **Статус**: 🔄 В разработке (обновлено с полным seeding)
> **Приоритет**: 🔴 Критический
> **Ожидаемое время**: 3-4 дня
> **Создано**: 2025-11-12
> **Обновлено**: 2025-11-12 (добавлена стратегия полного seeding тестовых данных)

---

## 📊 Текущая проблема

### Ситуация
E2E тесты в настоящее время используют **захардкоженного тестового пользователя** (`sollent98@gmail.com`) в **общей dev базе данных**:
- `apps/frontend/e2e/fixtures/auth.fixture.ts:64-66`
- Пользователь должен существовать в базе данных до запуска тестов
- Тесты НЕ изолированы - они делят одно и то же состояние базы данных
- **Отсутствуют предсказуемые тестовые данные** - нет гарантии наличия задач, тегов, recurrence rules
- **Блокирует CI/CD**: Невозможно надежно запускать параллельные тестовые наборы

### Последствия
1. ❌ Тесты падают, если пользователь не существует или пароль изменен
2. ❌ Тесты мешают друг другу (race conditions)
3. ❌ Невозможно запускать несколько CI пайплайнов параллельно
4. ❌ Нет чистого старта - тесты зависят от существующих данных
5. ❌ Невозможно воспроизвести сбои локально
6. ❌ Тесты фильтрации/календаря падают без предсказуемых задач
7. ❌ Тесты recurrence rules требуют ручного создания повторяющихся задач

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

### Фаза 2: Глобальная настройка Playwright (День 1-2)
**Цель**: Автоматически подготовить тестовую базу данных с полным набором тестовых данных

**Что создаётся:**
- ✅ Тестовый пользователь (e2e-test@example.com)
- ✅ 10 задач с различными статусами, приоритетами, датами
- ✅ 4 повторяющиеся задачи (daily, weekly, monthly, yearly)
- ✅ 5 тегов с разными цветами
- ✅ Связи задача-тег
- ✅ 1 родительская задача + 1 подзадача (тестирование вложенности)

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
    console.log('⏳ Ожидание готовности сервисов...')
    await execAsync('sleep 10')

    console.log('🗄️  Запуск миграций...')
    await execAsync('docker-compose -f infrastructure/docker/docker-compose.test.yml exec -T test-backend php bin/console doctrine:migrations:migrate --no-interaction')
  }

  // 2. Заполнение базы данных тестовыми данными через Symfony команду
  console.log('🌱 Заполнение базы данных тестовыми данными...')

  try {
    if (process.env.CI) {
      // В CI используем Docker
      await execAsync('docker-compose -f infrastructure/docker/docker-compose.test.yml exec -T test-backend php bin/console app:e2e:seed')
    } else {
      // Локально используем dev backend
      await execAsync('docker exec backend-php83 php bin/console app:e2e:seed')
    }
    console.log('✅ Тестовые данные успешно заполнены')
  } catch (error) {
    console.error('❌ Не удалось заполнить тестовые данные:', error)
    throw error
  }

  console.log('✅ Глобальная настройка завершена\n')
  console.log('📊 Созданные тестовые данные:')
  console.log('   👤 1 тестовый пользователь (e2e-test@example.com)')
  console.log('   📝 10 задач (с различными статусами, приоритетами, датами)')
  console.log('   🔁 4 повторяющиеся задачи (daily, weekly, monthly, yearly)')
  console.log('   🏷️  5 тегов')
  console.log('   🌲 1 родительская задача + 1 подзадача')
}

export default globalSetup
```

**Ключевые изменения по сравнению с оригинальным планом:**
- ❌ **Удалена** регистрация через API (ненадёжно, может конфликтовать)
- ✅ **Добавлен** вызов Symfony команды `app:e2e:seed`
- ✅ Команда создаёт **все тестовые данные** за один вызов:
  - Тестовый пользователь
  - 10 задач с различными статусами/приоритетами/датами
  - 4 повторяющиеся задачи (daily, weekly, monthly, yearly)
  - 5 тегов
  - Связи задача-тег
  - 1 родительская задача + 1 подзадача
- ✅ Работает как в **CI** (через test-backend), так и **локально** (через backend-php83)
- ✅ Идемпотентная операция - можно запускать многократно без ошибок

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
- `apps/backend/src/Command/E2ESeedCommand.php` ⭐ **Новая Symfony команда для seeding**
- `apps/frontend/e2e/global-setup.ts`
- `apps/frontend/e2e/global-teardown.ts`

**Файлы для изменения:**
- `apps/frontend/e2e/playwright.config.ts`

**Преимущества подхода с Symfony командой:**
- ✅ **Надёжность**: Нативная работа с Doctrine ORM, никаких API запросов
- ✅ **Скорость**: Одна команда создаёт все данные (~5 секунд)
- ✅ **Предсказуемость**: Всегда одинаковый набор данных
- ✅ **Простота отладки**: Можно запустить команду вручную для проверки
- ✅ **Идемпотентность**: Безопасно запускать многократно

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

### Фаза 1-2 (Базовая изоляция + Полный Seeding)
- [ ] Тестовая база данных работает в Docker
- [ ] Symfony команда `app:e2e:seed` создана и работает
- [ ] Команда создаёт полный набор тестовых данных:
  - [ ] 1 тестовый пользователь
  - [ ] 10 задач (различные статусы, приоритеты, даты)
  - [ ] 4 повторяющиеся задачи (daily, weekly, monthly, yearly)
  - [ ] 5 тегов
  - [ ] Связи задача-тег
  - [ ] 1 родительская задача + 1 подзадача
- [ ] Глобальная настройка Playwright вызывает `app:e2e:seed`
- [ ] E2E тесты проходят с использованием предсказуемых тестовых данных
- [ ] Seeding работает как локально, так и в CI

### Фаза 3-4 (Developer Experience)
- [ ] Разработчики могут запускать E2E тесты одной командой
- [ ] Учетные данные тестового пользователя настраиваются через env vars
- [ ] Можно вручную запустить `docker exec backend-php83 php bin/console app:e2e:seed`
- [ ] Документация обновлена с примерами тестовых данных

### Фаза 5 (CI/CD)
- [ ] GitHub Actions workflow проходит успешно
- [ ] Тесты выполняются менее чем за 5 минут (включая seeding)
- [ ] Отчеты о тестах загружаются как артефакты
- [ ] Seeding выполняется автоматически перед тестами

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

#### 2.1 Структура тестовых данных

Создать Symfony команду `app:e2e:seed` для заполнения тестовой БД предсказуемыми данными:

**Тестовый пользователь:**
- Email: `e2e-test@example.com`
- Password: `TestPassword123!`
- Создаётся ОДИН пользователь для всех E2E тестов

**Задачи (Tasks):**
- **10 задач** с разными состояниями, приоритетами и датами
- Покрывают все основные сценарии фильтрации и календаря

| ID | Title | Status | Priority | Due Date | Parent | Recurrence |
|----|-------|--------|----------|----------|--------|------------|
| 1 | Task Today 1 | pending | medium | today | - | - |
| 2 | Task Today 2 | pending | high | today | - | - |
| 3 | Task Tomorrow | pending | low | tomorrow | - | - |
| 4 | Task Overdue | pending | urgent | yesterday | - | - |
| 5 | Task Next Week | pending | medium | +7 days | - | - |
| 6 | Task Completed | completed | medium | today | - | - |
| 7 | Task In Progress | in_progress | high | today | - | - |
| 8 | Task No Date | pending | low | null | - | - |
| 9 | Parent Task | pending | medium | today | - | - |
| 10 | Subtask 1 | pending | low | today | Task 9 | - |

**Повторяющиеся задачи (Recurrence Rules):**
- **4 recurring tasks** покрывающих все типы повторений

| Task Title | Recurrence Type | Interval | Details |
|------------|-----------------|----------|---------|
| Daily Recurring Task | daily | 1 | Every day |
| Weekly Recurring Task | weekly | 1 | Every Monday |
| Monthly Recurring Task | monthly | 1 | Day 15 of month |
| Yearly Recurring Task | yearly | 1 | January 1st |

**Теги (Tags):**
- **5 тегов** с разными цветами для тестов фильтрации

| Tag Name | Color | Task Count |
|----------|-------|------------|
| Work | #FF5733 | 3 tasks |
| Personal | #33FF57 | 2 tasks |
| Urgent | #FF3333 | 1 task |
| Project | #3357FF | 2 tasks |
| Home | #F3FF33 | 1 task |

**Связи задачи-теги:**
- Task 1 → Work, Urgent
- Task 2 → Work, Project
- Task 3 → Personal
- Task 4 → Urgent
- Task 5 → Project
- Task 6 → Personal
- Task 7 → Work
- Task 8 → Home

#### 2.2 Symfony команда для seeding

Создать команду `apps/backend/src/Command/E2ESeedCommand.php`:

```php
<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Task;
use App\Entity\Tag;
use App\Entity\User;
use App\Entity\RecurrenceRule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:e2e:seed',
    description: 'Seeds the database with test data for E2E tests'
)]
final class E2ESeedCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🌱 Seeding E2E Test Data');

        // 1. Create test user
        $user = $this->createTestUser();
        $io->success('✅ Test user created: ' . $user->getEmail());

        // 2. Create tags
        $tags = $this->createTags($user);
        $io->success('✅ Created ' . count($tags) . ' tags');

        // 3. Create tasks (including subtasks)
        $tasks = $this->createTasks($user, $tags);
        $io->success('✅ Created ' . count($tasks) . ' tasks');

        // 4. Create recurrence rules
        $recurrences = $this->createRecurrenceRules($user);
        $io->success('✅ Created ' . count($recurrences) . ' recurrence rules');

        $this->entityManager->flush();

        $io->success('🎉 E2E test data seeded successfully!');

        return Command::SUCCESS;
    }

    private function createTestUser(): User
    {
        // Check if user already exists
        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'e2e-test@example.com']);

        if ($existingUser) {
            return $existingUser;
        }

        $user = new User();
        $user->setEmail('e2e-test@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'TestPassword123!'));

        $this->entityManager->persist($user);

        return $user;
    }

    private function createTags(User $user): array
    {
        $tagData = [
            ['name' => 'Work', 'color' => '#FF5733'],
            ['name' => 'Personal', 'color' => '#33FF57'],
            ['name' => 'Urgent', 'color' => '#FF3333'],
            ['name' => 'Project', 'color' => '#3357FF'],
            ['name' => 'Home', 'color' => '#F3FF33'],
        ];

        $tags = [];
        foreach ($tagData as $data) {
            $tag = new Tag();
            $tag->setName($data['name']);
            $tag->setColor($data['color']);
            $tag->setUser($user);

            $this->entityManager->persist($tag);
            $tags[$data['name']] = $tag;
        }

        return $tags;
    }

    private function createTasks(User $user, array $tags): array
    {
        $now = new \DateTimeImmutable();

        $tasksData = [
            ['title' => 'Task Today 1', 'status' => 'pending', 'priority' => 'medium', 'dueDate' => $now, 'tags' => ['Work', 'Urgent']],
            ['title' => 'Task Today 2', 'status' => 'pending', 'priority' => 'high', 'dueDate' => $now, 'tags' => ['Work', 'Project']],
            ['title' => 'Task Tomorrow', 'status' => 'pending', 'priority' => 'low', 'dueDate' => $now->modify('+1 day'), 'tags' => ['Personal']],
            ['title' => 'Task Overdue', 'status' => 'pending', 'priority' => 'urgent', 'dueDate' => $now->modify('-1 day'), 'tags' => ['Urgent']],
            ['title' => 'Task Next Week', 'status' => 'pending', 'priority' => 'medium', 'dueDate' => $now->modify('+7 days'), 'tags' => ['Project']],
            ['title' => 'Task Completed', 'status' => 'completed', 'priority' => 'medium', 'dueDate' => $now, 'tags' => ['Personal']],
            ['title' => 'Task In Progress', 'status' => 'in_progress', 'priority' => 'high', 'dueDate' => $now, 'tags' => ['Work']],
            ['title' => 'Task No Date', 'status' => 'pending', 'priority' => 'low', 'dueDate' => null, 'tags' => ['Home']],
        ];

        $tasks = [];
        foreach ($tasksData as $data) {
            $task = new Task();
            $task->setTitle($data['title']);
            $task->setStatus($data['status']);
            $task->setPriority($data['priority']);
            $task->setDueDate($data['dueDate']);
            $task->setUser($user);

            // Add tags
            foreach ($data['tags'] as $tagName) {
                if (isset($tags[$tagName])) {
                    $task->addTag($tags[$tagName]);
                }
            }

            $this->entityManager->persist($task);
            $tasks[] = $task;
        }

        // Create parent task with subtask
        $parentTask = new Task();
        $parentTask->setTitle('Parent Task');
        $parentTask->setStatus('pending');
        $parentTask->setPriority('medium');
        $parentTask->setDueDate($now);
        $parentTask->setUser($user);
        $this->entityManager->persist($parentTask);
        $tasks[] = $parentTask;

        $subtask = new Task();
        $subtask->setTitle('Subtask 1');
        $subtask->setStatus('pending');
        $subtask->setPriority('low');
        $subtask->setDueDate($now);
        $subtask->setUser($user);
        $subtask->setParent($parentTask);
        $this->entityManager->persist($subtask);
        $tasks[] = $subtask;

        return $tasks;
    }

    private function createRecurrenceRules(User $user): array
    {
        $now = new \DateTimeImmutable();

        $recurrenceData = [
            ['title' => 'Daily Recurring Task', 'type' => 'daily', 'interval' => 1],
            ['title' => 'Weekly Recurring Task', 'type' => 'weekly', 'interval' => 1, 'daysOfWeek' => [1]], // Monday
            ['title' => 'Monthly Recurring Task', 'type' => 'monthly', 'interval' => 1, 'dayOfMonth' => 15],
            ['title' => 'Yearly Recurring Task', 'type' => 'yearly', 'interval' => 1, 'monthOfYear' => 1, 'dayOfMonth' => 1],
        ];

        $rules = [];
        foreach ($recurrenceData as $data) {
            $task = new Task();
            $task->setTitle($data['title']);
            $task->setStatus('pending');
            $task->setPriority('medium');
            $task->setDueDate($now);
            $task->setUser($user);
            $this->entityManager->persist($task);

            $rule = new RecurrenceRule();
            $rule->setTask($task);
            $rule->setRecurrenceType($data['type']);
            $rule->setIntervalValue($data['interval']);
            $rule->setStartDate($now);

            if (isset($data['daysOfWeek'])) {
                $rule->setDaysOfWeek($data['daysOfWeek']);
            }
            if (isset($data['dayOfMonth'])) {
                $rule->setDayOfMonth($data['dayOfMonth']);
            }
            if (isset($data['monthOfYear'])) {
                $rule->setMonthOfYear($data['monthOfYear']);
            }

            $this->entityManager->persist($rule);
            $rules[] = $rule;
        }

        return $rules;
    }
}
```

**Команда создаёт минимальный, но полный набор данных для покрытия всех E2E тестов:**
- ✅ Фильтры по статусу, приоритету, дате
- ✅ Календарные представления
- ✅ Тесты вложенных задач
- ✅ Тесты тегов
- ✅ Тесты повторяющихся задач
- ✅ Быстрое выполнение (< 5 секунд)

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
# 1. Убедиться, что backend запущен
docker ps | grep backend-php83

# 2. Заполнить БД тестовыми данными (вручную, если нужно)
docker exec backend-php83 php bin/console app:e2e:seed

# 3. Запуск тестов (seeding выполнится автоматически в global-setup)
cd apps/frontend
npm run test:e2e

# 4. Отладка тестов с UI
npm run test:e2e:ui

# 5. Проверить созданные данные в БД
docker exec -it backend-psql16 psql -U user -d backend-app -c "SELECT COUNT(*) FROM tasks WHERE user_id = (SELECT id FROM users WHERE email = 'e2e-test@example.com');"
```

### Что делает seeding команда?
```bash
docker exec backend-php83 php bin/console app:e2e:seed
```
**Результат:**
- ✅ Создаёт/обновляет пользователя `e2e-test@example.com`
- ✅ Создаёт 10 задач с разными статусами/приоритетами/датами
- ✅ Создаёт 4 повторяющиеся задачи (daily, weekly, monthly, yearly)
- ✅ Создаёт 5 тегов и связывает их с задачами
- ✅ Создаёт родительскую задачу с подзадачей
- ⚡ Выполняется за ~5 секунд
- 🔁 Идемпотентная - можно запускать многократно

### Для CI/CD
```bash
# Автоматически через GitHub Actions
git push origin main
# Проверить: https://github.com/<org>/<repo>/actions

# В CI workflow автоматически выполняется:
# 1. docker-compose up (test environment)
# 2. php bin/console app:e2e:seed
# 3. npm run test:e2e
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

**Последнее обновление**: 2025-11-12 (добавлена стратегия полного seeding тестовых данных)
**Следующий обзор**: После завершения Фазы 2

**Changelog v2.0:**
- ✅ Добавлена Symfony команда `app:e2e:seed` для полного заполнения БД
- ✅ Определена структура тестовых данных (10 задач, 4 recurrence, 5 тегов)
- ✅ Обновлён global-setup.ts для вызова команды seeding
- ✅ Добавлены преимущества подхода с Symfony командой
- ✅ Расширены критерии успеха
- ✅ Обновлён "Быстрый старт" с примерами использования
