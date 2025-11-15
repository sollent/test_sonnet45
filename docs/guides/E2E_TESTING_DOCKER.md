# 🎭 E2E Тестирование в Docker с Playwright

> **Документация по запуску E2E тестов в Docker контейнере**
> **Версия**: 1.0
> **Дата**: 2025-11-16

---

## 📋 Содержание

1. [Обзор](#обзор)
2. [Архитектура](#архитектура)
3. [Быстрый Старт](#быстрый-старт)
4. [Команды Makefile](#команды-makefile)
5. [Конфигурация](#конфигурация)
6. [Troubleshooting](#troubleshooting)

---

## Обзор

### Что это?

E2E (End-to-End) тесты проверяют работу всего приложения от начала до конца, имитируя действия реального пользователя в браузере. Мы используем **Playwright** - современный инструмент для автоматизации браузеров.

### Зачем Docker?

✅ **Изоляция**: Браузеры и их зависимости изолированы в контейнере
✅ **Консистентность**: Одинаковое окружение для всех разработчиков
✅ **CI/CD Ready**: Легко интегрируется в пайплайны
✅ **Без установки**: Не нужно устанавливать браузеры на хост

---

## Архитектура

### Компоненты

```
┌─────────────────────────────────────┐
│  Frontend E2E Container             │
│  (Playwright + Браузеры)            │
│                                     │
│  - Chromium                         │
│  - Firefox                          │
│  - WebKit (Safari)                  │
│                                     │
│  Размер: ~2GB                       │
└──────────────┬──────────────────────┘
               │ host.docker.internal
               ├──────────────┬────────────────┐
               ↓              ↓                ↓
      ┌────────────┐  ┌──────────────┐  ┌─────────┐
      │  Backend   │  │  Frontend    │  │  Nginx  │
      │  :8089     │  │  :3000       │  │  :80    │
      └────────────┘  └──────────────┘  └─────────┘
```

### Файлы

- **`apps/frontend/Dockerfile.e2e`** - Docker образ с Playwright
- **`infrastructure/docker/docker-compose.e2e.yml`** - Конфигурация контейнера
- **`apps/frontend/e2e/`** - E2E тесты
- **`apps/frontend/playwright.config.ts`** - Конфигурация Playwright

---

## Быстрый Старт

### Шаг 1: Собрать E2E Docker образ

```bash
make test-e2e-build
```

**Важно**: Первая сборка займет ~10-15 минут (скачивается 764MB базовый образ Playwright). Последующие сборки будут быстрее благодаря кешированию.

### Шаг 2: Убедиться что backend и frontend запущены

```bash
# Проверить статус
docker ps

# Должны быть запущены:
# - backend-nginx
# - backend-php83
# - backend-frontend-dev (или frontend на :3000)
```

Если не запущены:

```bash
make up  # Запустит все сервисы
```

### Шаг 3: Запустить E2E тесты

```bash
make test-e2e
```

### Шаг 4: Просмотреть результаты

```bash
# Откроет HTML отчет Playwright
make test-e2e-report
```

---

## Команды Makefile

### `make test-e2e-build`

**Собрать E2E Docker образ**

```bash
make test-e2e-build
```

**Что делает:**
- Скачивает официальный Playwright образ (mcr.microsoft.com/playwright:v1.56.1-noble)
- Устанавливает npm зависимости frontend
- Копирует исходный код
- Готовит браузеры Chromium, Firefox, WebKit

**Когда использовать:**
- После первого клонирования проекта
- После обновления Playwright версии
- После изменения зависимостей npm

---

### `make test-e2e`

**Запустить E2E тесты (headless режим)**

```bash
make test-e2e
```

**Что делает:**
- Запускает Playwright тесты в headless режиме (без видимого браузера)
- Тесты выполняются в Docker контейнере
- Результаты сохраняются в `apps/frontend/test-results/`
- HTML отчет генерируется в `apps/frontend/playwright-report/`

**Предварительные условия:**
- ✅ E2E образ собран (`make test-e2e-build`)
- ✅ Backend запущен на порту 8089
- ✅ Frontend запущен на порту 3000

**Пример вывода:**

```
🎭 Running E2E tests in Docker...
⚠️  Backend (port 8089) and frontend (port 3000) must be running!

Running 15 tests using 3 workers
  15 passed (30.5s)
```

---

### `make test-e2e-ui`

**Запустить E2E тесты в UI режиме**

```bash
make test-e2e-ui
```

**Что делает:**
- Запускает Playwright UI для интерактивного тестирования
- Позволяет запускать тесты по одному
- Показывает пошаговое выполнение

**Ограничение**: Требует X11/VNC сервер (по умолчанию не поддерживается в Docker на macOS)

---

### `make test-e2e-debug`

**Запустить E2E тесты в debug режиме**

```bash
make test-e2e-debug
```

**Что делает:**
- Запускает тесты с Playwright Inspector
- Позволяет ставить breakpoints
- Пошаговое выполнение тестов

---

### `make test-e2e-headed`

**Запустить E2E тесты с видимым браузером**

```bash
make test-e2e-headed
```

**Что делает:**
- Запускает тесты в headed режиме (браузер виден)
- Полезно для отладки визуальных проблем

**Ограничение**: Требует display server (ограничено в Docker)

---

### `make test-e2e-report`

**Показать HTML отчет о тестах**

```bash
make test-e2e-report
```

**Что делает:**
- Открывает последний HTML отчет Playwright
- Показывает подробные результаты каждого теста
- Включает скриншоты и видео (если настроено)

**Предварительное условие**: Тесты должны быть запущены хотя бы раз

---

## Конфигурация

### Environment переменные

E2E контейнер использует следующие переменные окружения:

```yaml
# В docker-compose.e2e.yml
environment:
  # URL backend API (откуда брать данные)
  - VITE_API_BASE_URL=${E2E_API_URL:-http://host.docker.internal:8089}

  # URL frontend приложения (что тестировать)
  - PLAYWRIGHT_BASE_URL=${E2E_FRONTEND_URL:-http://host.docker.internal:3000}
```

**Переопределение**:

```bash
# В .env.docker
E2E_API_URL=http://localhost:8089
E2E_FRONTEND_URL=http://localhost:3000
```

### Playwright конфигурация

Файл: `apps/frontend/playwright.config.ts`

```typescript
import { defineConfig } from '@playwright/test';

export default defineConfig({
  // URL приложения для тестирования
  use: {
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:3000',
  },

  // Браузеры для тестирования
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
    { name: 'webkit', use: { ...devices['Desktop Safari'] } },
  ],

  // Настройки отчетов
  reporter: [
    ['html', { outputFolder: 'playwright-report' }],
    ['json', { outputFile: 'test-results/results.json' }],
  ],
});
```

---

## Troubleshooting

### Проблема: "Backend and frontend must be running!"

**Симптом**: Тесты падают с ошибкой подключения

**Решение**:

```bash
# Проверить что сервисы запущены
docker ps

# Запустить если не запущены
make up

# Проверить доступность
curl http://localhost:8089/api
curl http://localhost:3000
```

---

### Проблема: "No such file or directory: apps/frontend"

**Симптом**: Ошибка при сборке образа

**Решение**:

```bash
# Убедитесь что запускаете из корня проекта
cd /path/to/CLAUDE

# Запустите сборку
make test-e2e-build
```

---

### Проблема: "Network backend-network not found"

**Симптом**: Ошибка при запуске контейнера

**Решение**: Это исправлено в последней версии. Обновите код:

```bash
git pull origin feature/test-ci-setup
```

---

### Проблема: Тесты проходят локально, но падают в Docker

**Возможные причины:**
1. **Разные URL**: Проверьте `E2E_API_URL` и `E2E_FRONTEND_URL`
2. **Timing issues**: Увеличьте timeout в тестах
3. **Разрешение экрана**: Playwright использует 1280x720 по умолчанию

**Решение**:

```typescript
// В тесте
test.setTimeout(60000); // 60 секунд

// В playwright.config.ts
use: {
  viewport: { width: 1920, height: 1080 },
  timeout: 30000,
},
```

---

### Проблема: "UI mode requires display server"

**Симптом**: UI режим не работает в Docker

**Решение**: UI режим требует X11 сервер. Используйте headless режим:

```bash
make test-e2e  # Вместо make test-e2e-ui
```

Или запустите тесты локально (если npm установлен):

```bash
cd apps/frontend
npm run test:e2e:ui
```

---

## Дополнительные Ресурсы

- **Playwright документация**: https://playwright.dev/
- **Playwright Docker**: https://playwright.dev/docs/docker
- **Наши E2E тесты**: `apps/frontend/e2e/`
- **Основная документация**: [`docs/INDEX.md`](../INDEX.md)

---

**Последнее обновление**: 2025-11-16
**Версия документа**: 1.0
**Автор**: Claude Code AI

---

## 📝 Примеры использования

### Запуск конкретного теста

```bash
# В Docker (требует изменения command в docker-compose.e2e.yml)
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose.e2e.yml \
  run --rm frontend-e2e npm run test:e2e -- tests/login.spec.ts
```

### Запуск в CI/CD

```yaml
# .github/workflows/e2e-tests.yml
- name: Run E2E tests
  run: |
    make up
    make test-e2e-build
    make test-e2e

- name: Upload test results
  uses: actions/upload-artifact@v3
  with:
    name: playwright-report
    path: apps/frontend/playwright-report/
```

### Debug конкретного теста

```bash
# Локально с UI
cd apps/frontend
npx playwright test --ui tests/login.spec.ts

# Или с debug режимом
npx playwright test --debug tests/login.spec.ts
```
