# 📊 Отчет о покрытии тестами Backend

> **Последнее обновление**: 2025-11-10
> **Всего тестовых файлов**: 33
> **Фреймворк тестирования**: PHPUnit 9.6
> **Организация тестов**: Unit, Integration, Functional
> **📋 План реализации**: См. [MISSING_TEST_COVERAGE_PLAN.md](MISSING_TEST_COVERAGE_PLAN.md) для пошагового руководства по написанию тестов

---

## 🎯 Резюме

Наш backend имеет **комплексное покрытие тестами** по всем критическим слоям с **33 тестовыми файлами**, охватывающими:

- ✅ **Контроллеры** (API endpoints) - Functional тесты
- ✅ **Сервисы** (Бизнес-логика) - Unit тесты
- ✅ **Репозитории** (Доступ к данным) - Unit тесты
- ✅ **Аутентификация и безопасность** - Integration тесты
- ✅ **Команды** (CLI) - Integration тесты
- ⚠️ **Voters** (Авторизация) - Частично покрыты
- ⚠️ **DTO** - Нет выделенных тестов (валидируются неявно)
- ⚠️ **Entities** - Нет выделенных тестов (валидируются через интеграцию)

**Общая оценка покрытия**: ~75-80% (по критическому функционалу)

---

## 📋 Покрытие по слоям

### 1. Контроллеры (API Endpoints) - **73% покрытия**

Большинство API контроллеров имеют комплексные функциональные тесты, но некоторые отсутствуют.

#### ✅ Протестированные API контроллеры:

| Контроллер | Тестовый файл | Количество тестов | Статус покрытия |
|-----------|-----------|------------|-----------------|
| **TaskController** | `Functional/Api/TaskControllerTest.php` | 50+ тестов | ✅ Полное |
| **TagController** | `Functional/Api/TagControllerTest.php` | 15+ тестов | ✅ Полное |
| **AnalyticsController** | `Functional/Api/AnalyticsControllerTest.php` | 10+ тестов | ✅ Полное |
| **RecurrenceController** | `Functional/Api/RecurrenceControllerTest.php` | 12+ тестов | ✅ Полное |
| **AttachmentController** | `Functional/Api/AttachmentControllerTest.php` | 8+ тестов | ✅ Полное |
| **MediaObjectController** | `Functional/Api/MediaObjectControllerTest.php` | 10+ тестов | ✅ Полное |
| **GoogleAuthController** | `Functional/Api/GoogleAuthTest.php` | 5+ тестов | ✅ Полное |
| **UserProfileController** | `Functional/Api/UserProfileTest.php` | 8+ тестов | ✅ Полное |

#### ⚠️ Отсутствующие API контроллеры:

| Контроллер | Причина | Приоритет |
|-----------|--------|----------|
| **EnumController** | Используется frontend для приоритетов/статусов | 🔥 Критический |
| **TranslationController** | Используется для i18n переводов | 🔥 Критический |

#### ❌ Отсутствующие Admin контроллеры:

| Контроллер | Причина | Приоритет |
|-----------|--------|----------|
| **DashboardController** | Доступ к админ-панели | 🔥 Критический |
| **SecurityController** | Аутентификация админа | 🔥 Критический |
| **UserCrudController** | Операции управления пользователями | 🔥 Критический |

**Итого**: 8/14 контроллеров протестировано (57%) - **См. [MISSING_TEST_COVERAGE_PLAN.md](MISSING_TEST_COVERAGE_PLAN.md) для плана реализации**

#### Функциональные тесты покрывают:
- ✅ Все CRUD операции (Create, Read, Update, Delete)
- ✅ Аутентификацию (JWT токены)
- ✅ Авторизацию (проверки владения пользователем)
- ✅ Ошибки валидации (422 ответы)
- ✅ Ошибки "не найдено" (404 ответы)
- ✅ Ошибки "доступ запрещен" (403 ответы)
- ✅ Query параметры и фильтры
- ✅ Пагинация
- ✅ Функциональность поиска
- ✅ Сложные операции (toggle, archive, complete)

---

### 2. Сервисы (Бизнес-логика) - **64% покрытия**

#### ✅ Протестированные сервисы:

| Сервис | Тестовый файл | Количество тестов | Статус покрытия |
|---------|-----------|------------|-----------------|
| **TaskService** | `Unit/Service/TaskServiceTest.php` | 25+ тестов | ✅ Полное |
| **RecurrenceService** | `Unit/Service/RecurrenceServiceTest.php` | 20+ тестов | ✅ Полное |
| **AnalyticsService** | `Unit/Service/AnalyticsServiceTest.php` | 15+ тестов | ✅ Полное |
| **UserRegistrationService** | `Unit/Service/UserRegistrationServiceTest.php` | 8+ тестов | ✅ Полное |
| **UserProfileService** | `Unit/Service/UserProfileServiceTest.php` | 10+ тестов | ✅ Полное |
| **MediaObjectService** | `Unit/Service/MediaObjectServiceTest.php` | 8+ тестов | ✅ Полное |
| **FileUploadService** | `Unit/Service/FileUploadServiceTest.php` | 6+ тестов | ✅ Полное |
| **EnumTranslatorService** | `Unit/Service/EnumTranslatorServiceTest.php` | 5+ тестов | ✅ Полное |
| **TranslationService** | `Unit/Service/TranslationServiceTest.php` | 6+ тестов | ✅ Полное |

#### ⚠️ Отсутствующие тесты сервисов и стратегий:

| Компонент | Причина | Приоритет |
|-----------|--------|----------|
| **TagService** | Не существует (логика в TaskService) | ⛔ N/A |
| **DailyRecurrenceStrategy** | Тестируется косвенно через RecurrenceService | ⚠️ Высокий |
| **WeeklyRecurrenceStrategy** | Тестируется косвенно через RecurrenceService | ⚠️ Высокий |
| **MonthlyRecurrenceStrategy** | Тестируется косвенно через RecurrenceService | ⚠️ Высокий |
| **YearlyRecurrenceStrategy** | Тестируется косвенно через RecurrenceService | ⚠️ Высокий |
| **CustomRecurrenceStrategy** | Тестируется косвенно через RecurrenceService | ⚠️ Высокий |

**Итого**: 9/14 сервисов протестировано (64%) - **См. [MISSING_TEST_COVERAGE_PLAN.md](MISSING_TEST_COVERAGE_PLAN.md) для тестов Recurrence Strategy**

**Примечание**: TagService не существует. Стратегии рекуррентности тестируются косвенно, но нуждаются в прямых unit тестах.

#### Тесты сервисов покрывают:
- ✅ Все публичные методы
- ✅ Бизнес-правила и валидацию
- ✅ Проверки контроля доступа
- ✅ Граничные случаи (null значения, пустые массивы)
- ✅ Обработку ошибок (исключения)
- ✅ Мокированные зависимости (чистые unit тесты)
- ✅ Сложные алгоритмы (стратегии рекуррентности)
- ✅ Преобразования данных

---

### 3. Репозитории (Доступ к данным) - **100% покрытия**

| Репозиторий | Тестовый файл | Количество тестов | Статус покрытия |
|-----------|-----------|------------|-----------------|
| **TaskRepository** | `Unit/Repository/TaskRepositoryTest.php` | 15+ тестов | ✅ Полное |
| **TagRepository** | `Unit/Repository/TagRepositoryTest.php` | 8+ тестов | ✅ Полное |
| **UserRepository** | `Unit/Repository/UserRepositoryTest.php` | 6+ тестов | ✅ Полное |
| **MediaObjectRepository** | `Unit/Repository/MediaObjectRepositoryTest.php` | 5+ тестов | ✅ Полное |
| **TaskAttachmentRepository** | `Unit/Repository/TaskAttachmentRepositoryTest.php` | 4+ тестов | ✅ Полное |
| **RecurrenceRuleRepository** | `Unit/Repository/RecurrenceRuleRepositoryTest.php` | 6+ тестов | ✅ Полное |

**Итого**: 6/6 репозиториев протестировано (100%)

#### Тесты репозиториев покрывают:
- ✅ Кастомные методы запросов
- ✅ Фильтры и поиск
- ✅ Пагинация
- ✅ Сортировка
- ✅ Агрегации и статистика
- ✅ Сложные соединения (joins)
- ✅ Запросы на основе дат

---

### 4. Аутентификация и безопасность - **90% покрытия**

| Компонент | Тестовый файл | Количество тестов | Статус покрытия |
|-----------|-----------|------------|-----------------|
| **JWT аутентификация** | `Functional/Api/AuthenticationTest.php` | 8+ тестов | ✅ Полное |
| **Регистрация пользователя** | `Functional/Api/UserRegistrationTest.php` | 6+ тестов | ✅ Полное |
| **Обновление токена** | `Functional/Api/TokenRefreshTest.php` | 5+ тестов | ✅ Полное |
| **Google OAuth** | `Functional/Api/GoogleAuthTest.php` | 5+ тестов | ✅ Полное |
| **GoogleAuthenticator** | `Unit/Security/GoogleAuthenticatorTest.php` | 8+ тестов | ✅ Полное |
| **Google OAuth интеграция** | `Integration/Api/GoogleAuthIntegrationTest.php` | 6+ тестов | ✅ Полное |
| **Тесты авторизации** | `Functional/Api/AuthorizationTest.php` | 10+ тестов | ✅ Полное |
| **TaskVoter** | - | - | ⚠️ **Отсутствует** |
| **TagVoter** | - | - | ⚠️ **Отсутствует** |

**Итого**: 7/9 компонентов протестировано (78%)

#### Тесты безопасности покрывают:
- ✅ Вход с валидными учетными данными
- ✅ Вход с невалидными учетными данными
- ✅ Генерация токена
- ✅ Поток обновления токена
- ✅ Истечение срока токена
- ✅ Поток Google OAuth
- ✅ Валидация регистрации пользователя
- ✅ Хеширование пароля
- ✅ Проверки владения ресурсом (через контроллеры)

**Отсутствует**: Прямые unit тесты для Symfony Voters (TaskVoter, TagVoter) - хотя они тестируются косвенно через функциональные тесты.

---

### 5. Команды (CLI) - **17% покрытия**

| Команда | Тестовый файл | Количество тестов | Статус покрытия |
|---------|-----------|------------|-----------------|
| **ProcessRecurrenceRulesCommand** | `Integration/Command/ProcessRecurrenceRulesCommandTest.php` | 6+ тестов | ✅ Полное |
| **MakeAdminCommand** | - | - | ❌ Не протестирована |
| **SeedTasksCommand** | - | - | ❌ Не протестирована |
| **GenerateUserJourneyCommand** | - | - | ❌ Не протестирована |
| **GenerateTestDataCommand** | - | - | ❌ Не протестирована |
| **GenerateTestDataFastCommand** | - | - | ❌ Не протестирована |

**Итого**: 1/6 команд протестировано (17%)

**Причина**: Большинство команд - инструменты для разработки/заполнения данных. Только `ProcessRecurrenceRulesCommand` критична для продакшена.

---

### 6. Интеграционные тесты - **100% критических потоков**

| Интеграционный тест | Файл | Назначение |
|-----------------|------|---------|
| **TaskService интеграция** | `Integration/Service/TaskServiceIntegrationTest.php` | Операции с реальной БД |
| **Google Auth с HTTP Mock** | `Integration/Api/GoogleAuthWithHttpMockTest.php` | OAuth с мокированным Google API |
| **Google Auth интеграция** | `Integration/Api/GoogleAuthIntegrationTest.php` | Полный OAuth поток |
| **Recurrence команда** | `Integration/Command/ProcessRecurrenceRulesCommandTest.php` | Симуляция cron задачи |

---

### 7. Кросс-функциональные тесты

| Тип теста | Файл | Покрытие |
|-----------|------|----------|
| **Валидация** | `Functional/Api/ValidationTest.php` | ✅ Полное |
| **Авторизация** | `Functional/Api/AuthorizationTest.php` | ✅ Полное |

---

## 📊 Статистика покрытия

### По типу тестов

| Тип теста | Файлов | Примерное кол-во тестов | Покрытие |
|-----------|-------|---------------|----------|
| **Unit тесты** | 15 | ~150 | Контроллеры: 0%, Сервисы: 90%, Репозитории: 100% |
| **Integration тесты** | 4 | ~25 | Критические потоки: 100% |
| **Functional тесты** | 14 | ~200 | API endpoints: 100% |
| **Всего** | **33** | **~375** | **В целом: ~65-70%** (29 компонентов отсутствует) |

### По слоям

| Слой | Компонентов | Протестировано | Не протестировано | Покрытие % |
|-------|-----------|---------|----------|------------|
| **API контроллеры** | 10 | 8 | 2 | 80% |
| **Admin контроллеры** | 3 | 0 | 3 | 0% |
| **Сервисы** | 9 | 9 | 0 | 100% |
| **Стратегии рекуррентности** | 5 | 0 | 5 | 0%* |
| **Репозитории** | 6 | 6 | 0 | 100% |
| **Безопасность (Auth)** | 7 | 7 | 0 | 100% |
| **Voters** | 2 | 0 | 2 | 0%** |
| **Команды** | 5 | 1 | 4 | 20% |
| **Event Listeners** | 2 | 0 | 2 | 0% |
| **Normalizers** | 1 | 0 | 1 | 0% |
| **Entities** | 8 | 0 | 8 | 0%* |
| **DTOs** | 16 | 0 | 16 | 0%* |

\* *Тестируются неявно через интеграционные и функциональные тесты*
\*\* *Voters тестируются косвенно через функциональные тесты авторизации*

---

## 🎨 Основные моменты качества тестов

### ✅ Отличные практики

1. **AAA паттерн** - Все тесты следуют Arrange, Act, Assert
2. **Изоляция** - Unit тесты используют моки, без реальной БД
3. **ResetDatabase** - Functional тесты имеют чистое состояние
4. **Фабрики** - Zenstruck Foundry для тестовых данных
5. **Описательные имена** - Понятные имена тестовых методов
6. **Граничные случаи** - Комплексное покрытие сценариев ошибок
7. **Аутентификация** - Все защищенные endpoints протестированы
8. **Авторизация** - Проверка владения пользователем

### 📝 Организация тестов

```
backend/tests/
├── Unit/                          # Чистые unit тесты (мокированные зависимости)
│   ├── Service/                  # 9 тестов сервисов ✅
│   ├── Repository/               # 6 тестов репозиториев ✅
│   └── Security/                 # 1 тест безопасности ✅
├── Integration/                   # Интеграционные тесты (реальная БД)
│   ├── Service/                  # 1 интеграционный тест сервиса ✅
│   ├── Api/                      # 2 интеграционных теста OAuth ✅
│   └── Command/                  # 1 тест команды ✅
├── Functional/                    # Тесты API endpoints
│   └── Api/                      # 14 тестов контроллеров ✅
└── TestsUtilities/
    └── Factory/                  # Foundry фабрики для тестовых данных
```

---

## 🔍 Что НЕ покрыто (пока)

### Критические пробелы 🔥

| Компонент | Приоритет | Причина отсутствия тестов | Влияние |
|-----------|----------|-------------------|--------|
| **TaskVoter** | 🔥 Критический | Тестируется только косвенно через функциональные тесты | Высокое - компонент безопасности |
| **TagVoter** | 🔥 Критический | Тестируется только косвенно через функциональные тесты | Высокое - компонент безопасности |
| **Admin контроллеры (3)** | 🔥 Критический | Функции только для админа, чувствительные операции | Высокое - безопасность ROLE_ADMIN |
| **EnumController** | 🔥 Критический | Используется frontend для приоритетов/статусов | Среднее - API контракт |
| **TranslationController** | 🔥 Критический | Используется для i18n переводов | Среднее - API контракт |

### Пробелы высокого приоритета ⚠️

| Компонент | Приоритет | Причина |
|-----------|----------|--------|
| **Стратегии рекуррентности (5)** | ⚠️ Высокий | Сложная логика дат, тестируется косвенно | Среднее - нужна лучшая изоляция тестов |
| **MakeAdminCommand** | ⚠️ Высокий | Продакшн-команда для админских привилегий | Высокое - критическая операция |
| **LocaleListener** | ⚠️ Высокий | Обработка i18n локали | Среднее - влияет на все API ответы |
| **LocaleSubscriber** | ⚠️ Высокий | Обработка i18n локали | Среднее - влияет на все API ответы |

### Пробелы среднего приоритета 📘

| Компонент | Приоритет | Причина |
|-----------|----------|--------|
| **DTOs (8)** | 📘 Средний | Валидируются через Symfony Validator в функциональных тестах | Низкое - но хорошо для изоляции |
| **TaskEnumNormalizer** | 📘 Средний | Кастомная логика сериализации | Низкое - тестируется через API |

### Пробелы низкого приоритета 📙

| Компонент | Приоритет | Причина |
|-----------|----------|--------|
| **Entities (2)** | 📙 Низкий | Простые классы данных, валидируются через Doctrine |
| **Команды заполнения данных (3)** | 📙 Низкий | Только инструменты для разработки |
| **EasyAdmin контроллеры** | 📙 Низкий | UI стороннего фреймворка |

**📋 Полный план реализации**: [MISSING_TEST_COVERAGE_PLAN.md](MISSING_TEST_COVERAGE_PLAN.md)

---

## 🚀 Что осталось покрыть тестами

### 🔥 Высокий приоритет (должны быть покрыты)

#### 1. **Security Voters** - Unit тесты

**Компоненты**:
- `src/Security/Voter/TaskVoter.php`
- `src/Security/Voter/TagVoter.php`

**Почему важно**:
- Voters отвечают за критическую логику авторизации
- Хотя они тестируются косвенно через Functional тесты, нужны прямые Unit тесты

**Какие тесты нужны**:

```php
// tests/Unit/Security/Voter/TaskVoterTest.php
class TaskVoterTest extends TestCase
{
    /** @test */
    public function testOwnerCanViewTask()
    /** @test */
    public function testOwnerCanEditTask()
    /** @test */
    public function testOwnerCanDeleteTask()
    /** @test */
    public function testNonOwnerCannotViewTask()
    /** @test */
    public function testNonOwnerCannotEditTask()
    /** @test */
    public function testNonOwnerCannotDeleteTask()
    /** @test */
    public function testUnauthenticatedUserDenied()
}

// tests/Unit/Security/Voter/TagVoterTest.php
class TagVoterTest extends TestCase
{
    /** @test */
    public function testOwnerCanViewTag()
    /** @test */
    public function testOwnerCanDeleteTag()
    /** @test */
    public function testNonOwnerCannotDeleteTag()
}
```

**Оценка**: 2-3 часа работы
**Покрытие после**: +2 компонента (Security: 100%)

---

#### 2. **Admin Controllers** - Functional тесты

**Компоненты**:
- `src/Controller/Admin/DashboardController.php`
- `src/Controller/Admin/SecurityController.php`
- `src/Controller/Admin/UserCrudController.php`

**Почему важно**:
- Админ-панель имеет доступ к критическим операциям
- Нужно проверить ROLE_ADMIN authorization
- Проверка CRUD операций над пользователями

**Какие тесты нужны**:

```php
// tests/Functional/Admin/AdminAccessTest.php
class AdminAccessTest extends WebTestCase
{
    /** @test */
    public function testNonAdminCannotAccessDashboard()
    /** @test */
    public function testAdminCanAccessDashboard()
    /** @test */
    public function testAdminCanViewUsers()
    /** @test */
    public function testAdminCanEditUser()
    /** @test */
    public function testAdminCanDeleteUser()
    /** @test */
    public function testAdminCannotDeleteSelf()
}
```

**Оценка**: 3-4 часа работы
**Покрытие после**: +3 контроллера (Controllers: 100%)

---

### ⚠️ Средний приоритет (желательно покрыть)

#### 3. **Command Tests** - Integration тесты для продакшн-команд

**Компоненты**:
- `src/Command/MakeAdminCommand.php` - критическая команда

**Почему важно**:
- MakeAdminCommand используется в продакшене для назначения админов
- Нужно убедиться, что она корректно работает

**Какие тесты нужны**:

```php
// tests/Integration/Command/MakeAdminCommandTest.php
class MakeAdminCommandTest extends TestCase
{
    /** @test */
    public function testMakeUserAdmin()
    /** @test */
    public function testCannotMakeNonExistentUserAdmin()
    /** @test */
    public function testAlreadyAdminUser()
}
```

**Оценка**: 1-2 часа работы
**Покрытие после**: +1 команда (Commands: 33%)

---

#### 4. **DTO Validation Tests** - Unit тесты

**Компоненты**:
- `src/Dto/Request/Task/CreateTaskDto.php`
- `src/Dto/Request/Task/UpdateTaskDto.php`
- `src/Dto/Request/User/UserRegistrationRequestDto.php`

**Почему нужно**:
- Проверить что Symfony Validator constraints работают корректно
- Изолированно протестировать валидацию (сейчас тестируется только через API)

**Какие тесты нужны**:

```php
// tests/Unit/Dto/CreateTaskDtoTest.php
class CreateTaskDtoTest extends TestCase
{
    /** @test */
    public function testValidDto()
    /** @test */
    public function testTitleIsRequired()
    /** @test */
    public function testTitleMinLength()
    /** @test */
    public function testInvalidStatus()
    /** @test */
    public function testInvalidPriority()
    /** @test */
    public function testDueDateValidation()
}
```

**Оценка**: 4-5 часов работы (для всех DTO)
**Покрытие после**: +16 DTOs (DTOs: 100%)

---

### 📘 Низкий приоритет (опционально)

#### 5. **Entity Tests** - Unit тесты для бизнес-логики

**Компоненты**:
- `src/Entity/Task.php` - методы `isCompleted()`, `isOverdue()`, etc.
- `src/Entity/RecurrenceRule.php` - валидация recurrence options

**Почему низкий приоритет**:
- Entities тестируются через Integration тесты
- Большинство методов - простые getters/setters

**Какие тесты могут быть полезны**:

```php
// tests/Unit/Entity/TaskTest.php
class TaskTest extends TestCase
{
    /** @test */
    public function testIsOverdue()
    /** @test */
    public function testIsCompleted()
    /** @test */
    public function testSubtaskRelationship()
    /** @test */
    public function testTagRelationship()
}
```

**Оценка**: 3-4 часа работы
**Покрытие после**: +8 entities (Entities: 100%)

---

#### 6. **Recurrence Strategy Tests** - Unit тесты

**Компоненты**:
- `src/Service/Recurrence/Strategy/DailyRecurrenceStrategy.php`
- `src/Service/Recurrence/Strategy/WeeklyRecurrenceStrategy.php`
- `src/Service/Recurrence/Strategy/MonthlyRecurrenceStrategy.php`
- `src/Service/Recurrence/Strategy/YearlyRecurrenceStrategy.php`
- `src/Service/Recurrence/Strategy/CustomRecurrenceStrategy.php`

**Почему низкий приоритет**:
- Strategies тестируются через RecurrenceServiceTest
- Но изолированные тесты улучшили бы читаемость

**Какие тесты нужны**:

```php
// tests/Unit/Service/Recurrence/Strategy/DailyRecurrenceStrategyTest.php
class DailyRecurrenceStrategyTest extends TestCase
{
    /** @test */
    public function testCalculateNextOccurrence()
    /** @test */
    public function testEveryNDays()
    /** @test */
    public function testEndDate()
}
```

**Оценка**: 4-5 часов работы (для всех стратегий)

---

## 📈 План по улучшению покрытия

### Phase 1: Критическая безопасность (Приоритет 1) - 1 неделя

**Цель**: Покрыть критические компоненты безопасности

- [ ] TaskVoter unit тесты (2-3 часа)
- [ ] TagVoter unit тесты (1-2 часа)
- [ ] Admin Controllers functional тесты (3-4 часа)

**Результат**: Security 100%, Controllers 100%

---

### Phase 2: Валидация и команды (Приоритет 2) - 1 неделя

**Цель**: Покрыть валидацию и продакшн-команды

- [ ] MakeAdminCommand integration тест (1-2 часа)
- [ ] DTO validation unit тесты (4-5 часов)

**Результат**: Commands 33%, DTOs 100%

---

### Phase 3: Entities (Приоритет 3) - опционально

**Цель**: Добавить прямые Entity тесты для улучшения документации кода

- [ ] Task entity тесты (2 часа)
- [ ] RecurrenceRule entity тесты (1-2 часа)
- [ ] Другие entities (1-2 часа)

**Результат**: Entities 100%

---

### Phase 4: Стратегии рекуррентности (Приоритет 4) - опционально

**Цель**: Улучшить читаемость тестов для сложной логики recurrence

- [ ] DailyRecurrenceStrategy тесты
- [ ] WeeklyRecurrenceStrategy тесты
- [ ] MonthlyRecurrenceStrategy тесты
- [ ] YearlyRecurrenceStrategy тесты
- [ ] CustomRecurrenceStrategy тесты

**Результат**: Лучшая документация recurrence логики

---

## 🎯 Целевые показатели покрытия

| Метрика | Текущее | Phase 1 | Phase 2 | Phase 3 | Phase 4 |
|---------|---------|---------|---------|---------|---------|
| **Контроллеры** | 73% (8/11) | 100% | 100% | 100% | 100% |
| **Сервисы** | 90% (9/10) | 90% | 90% | 90% | 100% |
| **Репозитории** | 100% | 100% | 100% | 100% | 100% |
| **Безопасность** | 78% (7/9) | 100% | 100% | 100% | 100% |
| **Команды** | 17% (1/6) | 17% | 33% | 33% | 33% |
| **Entities** | 0% | 0% | 0% | 100% | 100% |
| **DTOs** | 0% | 0% | 100% | 100% | 100% |
| **Voters** | 0% | 100% | 100% | 100% | 100% |
| **В целом** | ~75% | ~85% | ~90% | ~95% | ~98% |

---

## 🔧 Инструменты для измерения покрытия

Для точного измерения code coverage можно использовать:

```bash
# Установить Xdebug (если еще не установлен)
docker exec backend-php83 pecl install xdebug

# Запустить тесты с coverage
docker exec backend-php83 php -dxdebug.mode=coverage vendor/bin/phpunit --coverage-html var/coverage

# Открыть отчет
open var/coverage/index.html
```

**Альтернатива**: PHPUnit coverage-text

```bash
docker exec backend-php83 vendor/bin/phpunit --coverage-text
```

---

## 📚 Связанные документы

- **[MISSING_TEST_COVERAGE_PLAN.md](MISSING_TEST_COVERAGE_PLAN.md)** ⭐ **ГЛАВНЫЙ ДОКУМЕНТ** - Пошаговый план написания тестов
- **[Руководство по тестированию](../guides/testing/TESTING.md)** - Как писать и запускать тесты
- **[Стандарты кодирования](../CODING_STANDARDS.md)** - Стандарты качества кода
- **[Архитектура Backend](ARCHITECTURE.md)** - Архитектура backend

---

## 🎯 Следующие шаги

**Для AI реализации:**

1. **Прочитать**: [MISSING_TEST_COVERAGE_PLAN.md](MISSING_TEST_COVERAGE_PLAN.md) - Полное пошаговое руководство
2. **Начать с**: Phase 1 (Критическая безопасность) - TaskVoter, TagVoter, Admin Controllers
3. **Следовать**: AAA паттерну, использовать фабрики, мокировать зависимости
4. **Цель**: 95%+ покрытия после всех фаз

**Текущий статус**: ~65-70% покрытия | **Цель**: 95%+ покрытия

---

**Вывод**: Наш backend имеет хорошее покрытие тестами (~65-70%) для основного функционала. Обнаружено **29 непокрытых компонентов**, включая критические (Voters, Admin Controllers, API Controllers). Детальный план реализации в **[MISSING_TEST_COVERAGE_PLAN.md](MISSING_TEST_COVERAGE_PLAN.md)** - начать с Priority 1 (Critical).
