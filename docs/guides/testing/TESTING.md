# 🧪 Руководство по тестированию - Комплексная стратегия тестирования

> **Кратко**: Backend использует PHPUnit 9.6 с Unit/Integration/Functional тестами. Frontend использует Vitest с 7 тестовыми файлами, покрывающими composables, stores, components, services и views. Все тесты следуют строгим принципам изоляции и мокирования.

---

## 📖 Содержание

1. [Тестирование Backend (PHPUnit)](#тестирование-backend-phpunit)
2. [Тестирование Frontend (Vitest)](#тестирование-frontend-vitest)
3. [Рекомендации по написанию тестов](#рекомендации-по-написанию-тестов)
4. [Интеграция с CI/CD](#интеграция-с-cicd)
5. [Решение проблем](#решение-проблем)

---

## Тестирование Backend (PHPUnit)

### Конфигурация

**Расположение**: `backend/phpunit.xml.dist`

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

**Ключевые расширения:**
- **DAMA DoctrineTestBundle** - Управление транзакциями базы данных для тестов
- **Zenstruck Foundry** - Паттерн Factory для тестовых данных

### Организация тестов

```
backend/tests/
├── Unit/                      # Изолированные юнит-тесты (без БД, без HTTP)
│   ├── Service/
│   │   └── UserRegistrationServiceTest.php
│   ├── Repository/
│   │   └── UserRepositoryTest.php
│   └── Security/
│       └── GoogleAuthenticatorTest.php
│
├── Integration/               # Интеграционные тесты (с моками внешних сервисов)
│   └── Api/
│       ├── GoogleAuthIntegrationTest.php
│       └── GoogleAuthWithHttpMockTest.php
│
├── Functional/                # Полные E2E тесты (реальные HTTP запросы)
│   └── Api/
│       ├── AuthenticationTest.php
│       ├── UserRegistrationTest.php
│       ├── TokenRefreshTest.php
│       ├── UserProfileTest.php
│       └── GoogleAuthTest.php
│
└── bootstrap.php              # Настройка тестового окружения
```

### Запуск тестов

```bash
# Запустить все тесты
docker exec backend-php83 vendor/bin/phpunit

# Запустить конкретный набор тестов
docker exec backend-php83 vendor/bin/phpunit tests/Unit
docker exec backend-php83 vendor/bin/phpunit tests/Integration
docker exec backend-php83 vendor/bin/phpunit tests/Functional

# Запустить конкретный тестовый файл
docker exec backend-php83 vendor/bin/phpunit tests/Unit/Service/UserRegistrationServiceTest.php

# Запустить конкретный тестовый метод
docker exec backend-php83 vendor/bin/phpunit --filter testRegisterSuccessfully

# С покрытием кода
docker exec backend-php83 vendor/bin/phpunit --coverage-html coverage
docker exec backend-php83 vendor/bin/phpunit --coverage-text
```

### Типы тестов - подробное описание

#### 1. Юнит-тесты (Unit Tests)

**Цель**: Тестирование изолированных компонентов (сервисов, сущностей) без зависимостей.

**Характеристики:**
- ✅ Использует моки для всех зависимостей
- ✅ Без доступа к базе данных
- ✅ Без HTTP запросов
- ✅ Быстрое выполнение (<1мс на тест)
- ✅ Тестирование чистой логики

**Пример**: `tests/Unit/Service/UserRegistrationServiceTest.php`

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
        // Мокируем все зависимости
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->service = new UserRegistrationService($this->userRepository);
    }

    public function testRegisterSuccessfully(): void
    {
        // Arrange - Подготовка
        $dto = new UserRegistrationRequestDto(
            email: 'test@example.com',
            password: 'SecurePassword123'
        );

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        // Act - Действие
        $user = $this->service->register($dto);

        // Assert - Проверка
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test@example.com', $user->getEmail());
    }
}
```

#### 2. Интеграционные тесты (Integration Tests)

**Цель**: Тестирование интеграции компонентов с моками внешних сервисов (Google API и др.).

**Характеристики:**
- ✅ Использует реальную базу данных (с транзакциями)
- ✅ Мокирует внешние HTTP вызовы
- ✅ Тестирует логику интеграции с API
- ✅ Средняя скорость выполнения (~100мс на тест)
- ✅ Zenstruck Foundry для фабрик

**Пример**: `tests/Integration/Api/GoogleAuthIntegrationTest.php`

```php
<?php

namespace App\Tests\Integration\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class GoogleAuthIntegrationTest extends WebTestCase
{
    use ResetDatabase; // Очищает БД после каждого теста

    public function testGoogleAuthWithMockedGoogleAPI(): void
    {
        // Мокируем ответы Google API
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        // ... настройка моков

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

#### 3. Функциональные тесты (Functional Tests)

**Цель**: Тестирование полных API workflow с реальными HTTP запросами.

**Характеристики:**
- ✅ Использует WebTestCase (Symfony)
- ✅ Реальный HTTP клиент
- ✅ Реальная база данных (с ResetDatabase)
- ✅ Тестирует полный цикл запрос/ответ
- ✅ Более медленное выполнение (~200мс на тест)
- ✅ Использует Foundry фабрики (UserFactory)

**Пример**: `tests/Functional/Api/AuthenticationTest.php`

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
        // Arrange: Создаем пользователя с известными учетными данными
        UserFactory::createOne([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $client = static::createClient();

        // Act: Выполняем логин
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

        // Assert: Проверяем ответ
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

### Ключевые инструменты тестирования

#### Zenstruck Foundry

Паттерн Factory для создания тестовых данных:

```php
use App\TestsUtilities\Factory\UserFactory;
use App\TestsUtilities\Factory\TaskFactory;

// Создать одну сущность
$user = UserFactory::createOne(['email' => 'test@example.com']);

// Создать несколько сущностей
$users = UserFactory::createMany(5);

// Создать с связями
$task = TaskFactory::createOne([
    'title' => 'Test Task',
    'user' => $user,
]);
```

#### ResetDatabase Trait

Автоматически сбрасывает базу данных после каждого теста:

```php
use Zenstruck\Foundry\Test\ResetDatabase;

class MyTest extends WebTestCase
{
    use ResetDatabase; // База данных очищается для каждого теста
}
```

---

## Тестирование Frontend (Vitest)

### Конфигурация

**Расположение**: `frontend/vite.config.ts`

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

**Ключевые настройки:**
- **Environment**: happy-dom (быстрее чем jsdom)
- **Globals**: `describe`, `it`, `expect` доступны без импортов
- **Coverage**: v8 провайдер с text/json/html отчетами

### Организация тестов

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

**Всего**: 7 тестовых файлов

### Запуск тестов

```bash
# Перейти в frontend
cd frontend

# Запустить все тесты один раз
npm run test:run

# Режим наблюдения (перезапускает при изменениях)
npm run test

# UI режим (интерактивный браузерный интерфейс)
npm run test:ui

# Сгенерировать отчет о покрытии
npm run test:coverage
```

### Примеры тестов

#### 1. Тест Composable

**Файл**: `src/composables/__tests__/useToast.spec.ts`

```typescript
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useToast } from '../useToast'

// Мокируем useToast из PrimeVue
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

  it('должен показать toast успеха с кастомным сообщением', () => {
    toast.showSuccess('Operation completed')

    expect(mockAdd).toHaveBeenCalledWith({
      severity: 'success',
      summary: 'Success',
      detail: 'Operation completed',
      life: 3000,
    })
  })

  it('должен показать toast ошибки с более длительным временем жизни', () => {
    toast.showError('Error message')

    expect(mockAdd).toHaveBeenCalledWith(
      expect.objectContaining({
        severity: 'error',
        life: 5000, // Ошибки показываются дольше
      })
    )
  })
})
```

#### 2. Тест Store

**Файл**: `src/stores/__tests__/auth.store.spec.ts`

```typescript
import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../auth.store'

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('должен инициализироваться в состоянии "выход выполнен"', () => {
    const authStore = useAuthStore()

    expect(authStore.isAuthenticated).toBe(false)
    expect(authStore.user).toBeNull()
  })

  it('должен установить пользователя при логине', () => {
    const authStore = useAuthStore()
    const user = { id: 1, email: 'test@example.com' }

    authStore.setUser(user)

    expect(authStore.isAuthenticated).toBe(true)
    expect(authStore.user).toEqual(user)
  })

  it('должен очистить пользователя при выходе', () => {
    const authStore = useAuthStore()
    authStore.setUser({ id: 1, email: 'test@example.com' })

    authStore.logout()

    expect(authStore.isAuthenticated).toBe(false)
    expect(authStore.user).toBeNull()
  })
})
```

#### 3. Тест компонента

**Файл**: `src/components/ui/__tests__/BaseButton.spec.ts`

```typescript
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseButton from '../BaseButton.vue'

describe('BaseButton', () => {
  it('рендерит кнопку с текстом', () => {
    const wrapper = mount(BaseButton, {
      props: {
        label: 'Click me'
      }
    })

    expect(wrapper.text()).toContain('Click me')
  })

  it('генерирует событие click при клике', async () => {
    const wrapper = mount(BaseButton, {
      props: {
        label: 'Click me'
      }
    })

    await wrapper.trigger('click')

    expect(wrapper.emitted('click')).toHaveLength(1)
  })

  it('применяет класс disabled когда prop disabled равен true', () => {
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

#### 4. Тест Service

**Файл**: `src/services/__tests__/auth.service.spec.ts`

```typescript
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { authService } from '../auth.service'
import axios from 'axios'

vi.mock('axios')

describe('Auth Service', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('должен вызвать endpoint логина с учетными данными', async () => {
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

  it('должен выбросить ошибку при неудачном логине', async () => {
    vi.mocked(axios.post).mockRejectedValue(new Error('Invalid credentials'))

    await expect(
      authService.login('test@example.com', 'wrong')
    ).rejects.toThrow('Invalid credentials')
  })
})
```

---

## Рекомендации по написанию тестов

### Общие принципы

#### 1. **Паттерн Arrange-Act-Assert (AAA)**

```typescript
it('должен что-то сделать', () => {
  // Arrange: Подготовка тестовых данных и моков
  const input = { value: 10 }
  const expected = 20

  // Act: Выполнение тестируемой функции
  const result = doubleValue(input.value)

  // Assert: Проверка результата
  expect(result).toBe(expected)
})
```

#### 2. **Изоляция тестов**

✅ **ДЕЛАЙ**: Каждый тест должен быть независимым
```typescript
describe('Calculator', () => {
  beforeEach(() => {
    // Сбросить состояние перед каждым тестом
    calculator = new Calculator()
  })

  it('складывает числа', () => {
    expect(calculator.add(2, 3)).toBe(5)
  })

  it('вычитает числа', () => {
    expect(calculator.subtract(5, 3)).toBe(2)
  })
})
```

❌ **НЕ ДЕЛАЙ**: Не используй общее состояние между тестами
```typescript
let sharedValue = 0 // ❌ Плохо: общее состояние

it('тест 1', () => {
  sharedValue = 5 // Влияет на тест 2
})

it('тест 2', () => {
  expect(sharedValue).toBe(0) // ❌ Падает из-за теста 1
})
```

#### 3. **Мокируй внешние зависимости**

✅ **ДЕЛАЙ**: Мокируй HTTP, базу данных, внешние сервисы
```typescript
vi.mock('axios', () => ({
  default: {
    get: vi.fn(() => Promise.resolve({ data: mockData }))
  }
}))
```

❌ **НЕ ДЕЛАЙ**: Не делай реальные HTTP вызовы в тестах
```typescript
// ❌ Плохо: делает реальный HTTP вызов
await axios.get('https://api.example.com/data')
```

#### 4. **Тестируй поведение, а не реализацию**

✅ **ДЕЛАЙ**: Тестируй что делает код
```typescript
it('отображает сообщение об ошибке когда логин не удался', () => {
  // Тестируем наблюдаемое поведение
  expect(wrapper.text()).toContain('Invalid credentials')
})
```

❌ **НЕ ДЕЛАЙ**: Не тестируй внутреннюю реализацию
```typescript
it('вызывает внутренний метод', () => {
  // ❌ Плохо: тестирование приватной реализации
  expect(component.internalMethod).toHaveBeenCalled()
})
```

### Рекомендации для Backend тестов

✅ **ДЕЛАЙ**:
- Используй подходящий тип теста (Unit/Integration/Functional)
- Мокируй все внешние сервисы в Unit тестах
- Используй ResetDatabase trait в Integration/Functional тестах
- Используй Foundry фабрики для тестовых данных
- Тестируй как happy path ТАК И случаи с ошибками
- Тестируй граничные случаи (null, пустые значения, граничные значения)

❌ **НЕ ДЕЛАЙ**:
- Не тестируй код фреймворка
- Не тестируй геттеры/сеттеры без логики
- Не пропускай очистку базы данных
- Не делай реальные HTTP вызовы к внешним API
- Не используй общие фикстуры между тестами

### Рекомендации для Frontend тестов

✅ **ДЕЛАЙ**:
- Мокируй внешние зависимости (axios, stores, composables)
- Используй `@vue/test-utils` для монтирования компонентов
- Тестируй взаимодействие пользователя (click, input, submit)
- Тестируй computed свойства и watchers
- Тестируй генерируемые события
- Используй `beforeEach` для настройки

❌ **НЕ ДЕЛАЙ**:
- Не тестируй код библиотек (Vue, PrimeVue)
- Не тестируй CSS/стилизацию
- Не тестируй детали реализации (внутренние методы)
- Не делай реальные API вызовы
- Не пропускай очистку в `beforeEach`/`afterEach`

---

## Интеграция с CI/CD

### Backend CI

```bash
# Запуск в CI pipeline
docker exec backend-php83 vendor/bin/phpunit --coverage-text --colors=never
```

### Frontend CI

```bash
# Запуск в CI pipeline
cd frontend && npm run test:run -- --reporter=junit --coverage
```

---

## Решение проблем

### Проблемы Backend

**Проблема**: Тесты падают с ошибками подключения к базе данных
```
Решение: Убедись что APP_ENV=test и база данных создана
docker exec backend-php83 php bin/console doctrine:database:create --env=test
```

**Проблема**: Тесты оставляют данные в базе
```
Решение: Используй ResetDatabase trait
use Zenstruck\Foundry\Test\ResetDatabase;
```

**Проблема**: Медленные интеграционные тесты
```
Решение: Используй DAMA DoctrineTestBundle (уже настроен)
```

### Проблемы Frontend

**Проблема**: Тесты падают с ошибкой "Cannot find module"
```
Решение: Проверь конфигурацию alias в vite.config.ts
resolve: {
  alias: {
    '@': fileURLToPath(new URL('./src', import.meta.url))
  }
}
```

**Проблема**: Тесты падают с "ReferenceError: document is not defined"
```
Решение: Убедись что environment установлен в 'happy-dom'
test: {
  environment: 'happy-dom'
}
```

**Проблема**: Тесты Pinia store падают
```
Решение: Создай новый Pinia instance в beforeEach
beforeEach(() => {
  setActivePinia(createPinia())
})
```

---

## Цели по покрытию

### Целевые показатели покрытия Backend
- **Unit Tests**: >80%
- **Integration Tests**: >70%
- **Functional Tests**: >60%

### Целевые показатели покрытия Frontend
- **Components**: >70%
- **Stores**: >80%
- **Services**: >80%
- **Composables**: >80%

---

## Связанные документы

- **[Development Workflow](DEVELOPMENT_WORKFLOW.md)** - Запуск тестов в разработке
- **[Backend Architecture](../backend/ARCHITECTURE.md)** - Понимание структуры кода
- **[Frontend Architecture](../frontend/ARCHITECTURE.md)** - Паттерны компонентов
- **[Troubleshooting](TROUBLESHOOTING.md)** - Распространенные проблемы и решения

---

*Последнее обновление: 2025-11-06 от Claude Code AI*
*Версия документации: 2.0*
