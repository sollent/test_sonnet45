# 🎯 Backend - План реализации недостающих тестов

> **Последнее обновление**: 2025-11-10
> **Статус**: Готово к реализации
> **Приоритет**: Критический → Средний → Низкий

---

## 📊 Краткое резюме

Этот документ предоставляет **пошаговый план реализации** для написания недостающих backend тестов. После глубокого анализа всей кодовой базы backend мы выявили **29 компонентов**, которые нуждаются в тестовом покрытии.

**Статус покрытия:**
- ✅ **Уже протестировано**: 33 тестовых файла (Контроллеры: 8, Сервисы: 9, Репозитории: 6, Безопасность: 1, Команды: 1)
- ⚠️ **Отсутствуют тесты**: 29 компонентов (указаны ниже)
- 🎯 **Целевое покрытие**: 95%+ (с текущих ~75-80%)

**Оценка времени**: 25-30 часов (5-6 дней для solo dev + AI)

---

## 🗂️ Полный список недостающих тестов

### Сводка по приоритетам

| Приоритет | Компонентов | Примерное время |
|----------|------------|----------------|
| 🔥 **Критический** | 7 компонентов | 8-10 часов |
| ⚠️ **Высокий** | 8 компонентов | 10-12 часов |
| 📘 **Средний** | 9 компонентов | 6-8 часов |
| 📙 **Низкий** | 5 компонентов | 3-4 часа |
| **ВСЕГО** | **29 компонентов** | **27-34 часа** |

---

## 🔥 ПРИОРИТЕТ 1 - КРИТИЧЕСКИЙ (Обязательно)

### 1.1. Security Voters - Unit тесты

**Приоритет**: 🔥 Критический
**Примерное время**: 3-4 часа
**Тип тестов**: Unit Tests

#### Компоненты:
- `src/Security/Voter/TaskVoter.php`
- `src/Security/Voter/TagVoter.php`

#### Почему критический:
- Логика авторизации - это основа безопасности
- Сейчас тестируется только косвенно через Functional тесты
- Прямые unit тесты гарантируют, что логика voter'ов пуленепробиваемая

#### Расположение тестовых файлов:
```
tests/Unit/Security/Voter/TaskVoterTest.php
tests/Unit/Security/Voter/TagVoterTest.php
```

#### Тест-кейсы для TaskVoter:

```php
// tests/Unit/Security/Voter/TaskVoterTest.php

class TaskVoterTest extends TestCase
{
    private TaskVoter $voter;

    /** @test */
    public function testOwnerCanViewTask(): void
    // Владелец может просматривать задачу

    /** @test */
    public function testOwnerCanEditTask(): void
    // Владелец может редактировать задачу

    /** @test */
    public function testOwnerCanDeleteTask(): void
    // Владелец может удалить задачу

    /** @test */
    public function testNonOwnerCannotViewTask(): void
    // Не-владелец не может просматривать задачу

    /** @test */
    public function testNonOwnerCannotEditTask(): void
    // Не-владелец не может редактировать задачу

    /** @test */
    public function testNonOwnerCannotDeleteTask(): void
    // Не-владелец не может удалить задачу

    /** @test */
    public function testUnauthenticatedUserDenied(): void
    // Неаутентифицированный пользователь получает отказ

    /** @test */
    public function testVoterSupportsOnlyTaskEntity(): void
    // Voter поддерживает только сущность Task

    /** @test */
    public function testVoterSupportsCorrectAttributes(): void
    // Voter поддерживает корректные атрибуты
}
```

**Всего тестов**: 9 тестов для TaskVoter + 7 тестов для TagVoter = **16 тестов**

---

### 1.2. API Controllers - Functional тесты

**Приоритет**: 🔥 Критический
**Примерное время**: 3-4 часа
**Тип тестов**: Functional Tests

#### Компоненты:
- `src/Controller/Api/EnumController.php`
- `src/Controller/Api/TranslationController.php`

#### Почему критический:
- Это публичные API эндпоинты, используемые frontend'ом
- EnumController: отдает приоритеты и статусы (критично для UI)
- TranslationController: отдает i18n переводы (критично для интернационализации)

#### Расположение тестовых файлов:
```
tests/Functional/Api/EnumControllerTest.php
tests/Functional/Api/TranslationControllerTest.php
```

#### Тест-кейсы для EnumController:

```php
// tests/Functional/Api/EnumControllerTest.php

class EnumControllerTest extends ApiTestCase
{
    /** @test */
    public function testGetPrioritiesReturnsAllPriorities(): void
    // Ожидается: 200, массив с 4 приоритетами (low, medium, high, urgent)

    /** @test */
    public function testGetPrioritiesIncludesColorAndIcon(): void
    // Ожидается: Каждый приоритет имеет value, label, color, icon

    /** @test */
    public function testGetPrioritiesRespectsAcceptLanguageHeader(): void
    // Ожидается: Русские labels когда Accept-Language: ru

    /** @test */
    public function testGetStatusesReturnsAllStatuses(): void
    // Ожидается: 200, массив с 4 статусами

    /** @test */
    public function testGetStatusesIncludesColorAndIcon(): void
    // Каждый статус содержит color и icon

    /** @test */
    public function testGetStatusesRespectsAcceptLanguageHeader(): void
    // Учитывает заголовок Accept-Language

    /** @test */
    public function testUnauthorizedUserCannotAccessEnumEndpoints(): void
    // Ожидается: 401 когда нет JWT токена
}
```

**Всего тестов**: 7 тестов для EnumController + 8 тестов для TranslationController = **15 тестов**

---

### 1.3. Admin Controllers - Functional тесты

**Приоритет**: 🔥 Критический
**Примерное время**: 4-5 часов
**Тип тестов**: Functional Tests

#### Компоненты:
- `src/Controller/Admin/DashboardController.php`
- `src/Controller/Admin/SecurityController.php`
- `src/Controller/Admin/UserCrudController.php`

#### Почему критический:
- Админ-панель имеет доступ к чувствительным операциям
- Авторизация ROLE_ADMIN должна быть протестирована
- Управление пользователями - критический функционал

#### Расположение тестовых файлов:
```
tests/Functional/Admin/AdminDashboardTest.php
tests/Functional/Admin/AdminSecurityTest.php
tests/Functional/Admin/UserCrudTest.php
```

#### Тест-кейсы для Admin Controllers:

```php
// tests/Functional/Admin/AdminAccessTest.php (объединенный тест)

class AdminAccessTest extends WebTestCase
{
    /** @test */
    public function testNonAdminCannotAccessDashboard(): void
    // Ожидается: 403 когда обычный пользователь пытается попасть на /admin

    /** @test */
    public function testAdminCanAccessDashboard(): void
    // Ожидается: 200 когда пользователь с ROLE_ADMIN заходит на /admin

    /** @test */
    public function testUnauthenticatedUserRedirectedToLogin(): void
    // Ожидается: 302 редирект на страницу логина

    /** @test */
    public function testAdminCanViewUsersList(): void
    // Админ может просмотреть список пользователей

    /** @test */
    public function testAdminCanCreateUser(): void
    // Админ может создать пользователя

    /** @test */
    public function testAdminCanEditUser(): void
    // Админ может редактировать пользователя

    /** @test */
    public function testAdminCanDeleteUser(): void
    // Админ может удалить пользователя

    /** @test */
    public function testAdminCannotDeleteSelf(): void
    // Админ не может удалить самого себя

    /** @test */
    public function testAdminCanGrantRoleAdmin(): void
    // Админ может выдать роль ROLE_ADMIN

    /** @test */
    public function testNonAdminCannotAccessUserCrud(): void
    // Не-админ не может получить доступ к CRUD пользователей
}
```

**Всего тестов**: ~12-15 тестов для Admin контроллеров

---

## ⚠️ ПРИОРИТЕТ 2 - ВЫСОКИЙ (Должно быть)

### 2.1. Recurrence Strategies - Unit тесты

**Приоритет**: ⚠️ Высокий
**Примерное время**: 6-8 часов
**Тип тестов**: Unit Tests

#### Компоненты:
- `src/Service/Recurrence/Strategy/DailyRecurrenceStrategy.php`
- `src/Service/Recurrence/Strategy/WeeklyRecurrenceStrategy.php`
- `src/Service/Recurrence/Strategy/MonthlyRecurrenceStrategy.php`
- `src/Service/Recurrence/Strategy/YearlyRecurrenceStrategy.php`
- `src/Service/Recurrence/Strategy/CustomRecurrenceStrategy.php`

#### Почему высокий приоритет:
- Сложная бизнес-логика для расчета дат
- Сейчас тестируется только косвенно через RecurrenceServiceTest
- Прямые unit тесты улучшают читаемость и поддерживаемость
- Критично для функционала повторяющихся задач

#### Расположение тестовых файлов:
```
tests/Unit/Service/Recurrence/Strategy/DailyRecurrenceStrategyTest.php
tests/Unit/Service/Recurrence/Strategy/WeeklyRecurrenceStrategyTest.php
tests/Unit/Service/Recurrence/Strategy/MonthlyRecurrenceStrategyTest.php
tests/Unit/Service/Recurrence/Strategy/YearlyRecurrenceStrategyTest.php
tests/Unit/Service/Recurrence/Strategy/CustomRecurrenceStrategyTest.php
```

#### Тест-кейсы для DailyRecurrenceStrategy:

```php
// tests/Unit/Service/Recurrence/Strategy/DailyRecurrenceStrategyTest.php

class DailyRecurrenceStrategyTest extends TestCase
{
    private DailyRecurrenceStrategy $strategy;

    /** @test */
    public function testCalculateNextOccurrenceAddsOneDay(): void
    // Дано: задача со сроком 2025-01-10
    // Когда: вызывается calculateNextOccurrence()
    // Тогда: возвращается 2025-01-11

    /** @test */
    public function testAppliesTimeOfDayIfSet(): void
    // Дано: правило с timeOfDay = 14:30
    // Когда: calculateNextOccurrence()
    // Тогда: следующая дата имеет время 14:30:00

    /** @test */
    public function testRespectsEndDate(): void
    // Дано: правило с endDate = 2025-01-15
    // Когда: calculateNextOccurrence(2025-01-15)
    // Тогда: возвращается null (больше нет повторений)

    /** @test */
    public function testRespectsMaxOccurrences(): void
    // Дано: правило с maxOccurrences = 5, currentOccurrences = 5
    // Когда: calculateNextOccurrence()
    // Тогда: возвращается null

    /** @test */
    public function testSupportsOnlyDailyType(): void
    // Дано: экземпляр стратегии
    // Когда: вызывается supports('daily')
    // Тогда: возвращается true
    // Когда: вызывается supports('weekly')
    // Тогда: возвращается false

    /** @test */
    public function testGetPreviewDatesReturnsCorrectCount(): void
    // Дано: startDate = 2025-01-10, count = 5
    // Когда: вызывается getPreviewDates()
    // Тогда: возвращается массив с 5 датами

    /** @test */
    public function testGetPreviewDatesStopsAtEndDate(): void
    // Дано: endDate = 2025-01-12, count = 10
    // Когда: вызывается getPreviewDates()
    // Тогда: возвращается только 2 даты (до endDate)
}
```

**Аналогичная структура для других стратегий (Weekly, Monthly, Yearly, Custom)**

**Всего тестов**: ~7 тестов × 5 стратегий = **35 тестов**

---

### 2.2. Commands - Integration тесты

**Приоритет**: ⚠️ Высокий
**Примерное время**: 2-3 часа
**Тип тестов**: Integration Tests

#### Компоненты:
- `src/Command/MakeAdminCommand.php` (КРИТИЧНО - production команда)

#### Почему высокий приоритет:
- Используется в production для выдачи админских привилегий
- Должны убедиться, что корректно работает с реальной БД

#### Расположение тестового файла:
```
tests/Integration/Command/MakeAdminCommandTest.php
```

#### Тест-кейсы:

```php
// tests/Integration/Command/MakeAdminCommandTest.php

class MakeAdminCommandTest extends KernelTestCase
{
    use ResetDatabase;

    /** @test */
    public function testMakeUserAdmin(): void
    // Дано: существует обычный пользователь
    // Когда: команда выполнена с email пользователя
    // Тогда: пользователь имеет роль ROLE_ADMIN

    /** @test */
    public function testCannotMakeNonExistentUserAdmin(): void
    // Дано: пользователь не существует
    // Когда: команда выполнена
    // Тогда: команда завершается с ошибкой

    /** @test */
    public function testUserAlreadyAdmin(): void
    // Дано: пользователь уже имеет ROLE_ADMIN
    // Когда: команда выполнена
    // Тогда: команда показывает сообщение "уже админ"

    /** @test */
    public function testCommandRequiresEmailArgument(): void
    // Команда требует аргумент email

    /** @test */
    public function testCommandPersistsChangesToDatabase(): void
    // Убеждаемся, что изменения сохраняются в БД
}
```

**Всего тестов**: 5 тестов

---

### 2.3. Event Listeners - Unit тесты

**Приоритет**: ⚠️ Высокий
**Примерное время**: 2-3 часа
**Тип тестов**: Unit Tests

#### Компоненты:
- `src/EventListener/LocaleListener.php`
- `src/EventSubscriber/LocaleSubscriber.php`

#### Почему высокий приоритет:
- Обработка локали влияет на все API ответы
- i18n - ключевая фича

#### Расположение тестовых файлов:
```
tests/Unit/EventListener/LocaleListenerTest.php
tests/Unit/EventSubscriber/LocaleSubscriberTest.php
```

#### Тест-кейсы для LocaleListener:

```php
// tests/Unit/EventListener/LocaleListenerTest.php

class LocaleListenerTest extends TestCase
{
    /** @test */
    public function testSetsLocaleFromAcceptLanguageHeader(): void
    // Устанавливает локаль из заголовка Accept-Language

    /** @test */
    public function testFallsBackToDefaultLocale(): void
    // Возвращается к дефолтной локали

    /** @test */
    public function testSupportsOnlyConfiguredLocales(): void
    // Дано: настроенные локали [en, ru, uk]
    // Когда: Accept-Language: fr
    // Тогда: возвращается к 'en'
}
```

**Всего тестов**: 6 тестов (3 + 3)

---

## 📘 ПРИОРИТЕТ 3 - СРЕДНИЙ (Желательно)

### 3.1. Serializer Normalizer - Unit тесты

**Приоритет**: 📘 Средний
**Примерное время**: 1-2 часа
**Тип тестов**: Unit Tests

#### Компонент:
- `src/Serializer/Normalizer/TaskEnumNormalizer.php`

#### Почему средний приоритет:
- Кастомная логика сериализации для enum'ов
- Используется в API ответах
- Сейчас тестируется косвенно через Functional тесты

#### Расположение тестового файла:
```
tests/Unit/Serializer/Normalizer/TaskEnumNormalizerTest.php
```

#### Тест-кейсы:

```php
// tests/Unit/Serializer/Normalizer/TaskEnumNormalizerTest.php

class TaskEnumNormalizerTest extends TestCase
{
    /** @test */
    public function testNormalizesTaskPriority(): void
    // Нормализует приоритет задачи

    /** @test */
    public function testNormalizesTaskStatus(): void
    // Нормализует статус задачи

    /** @test */
    public function testSupportsNormalizationForEnums(): void
    // Поддерживает нормализацию для enum'ов

    /** @test */
    public function testDoesNotSupportOtherTypes(): void
    // Не поддерживает другие типы
}
```

**Всего тестов**: 4 теста

---

### 3.2. DTO Validation - Unit тесты

**Приоритет**: 📘 Средний
**Примерное время**: 4-5 часов
**Тип тестов**: Unit Tests

#### Компоненты:
- `src/Dto/Request/Task/CreateTaskDto.php`
- `src/Dto/Request/Task/UpdateTaskDto.php`
- `src/Dto/Request/User/UserRegistrationRequestDto.php`
- `src/Dto/Request/User/UpdateProfileDto.php`
- `src/Dto/Request/User/UpdatePasswordDto.php`
- `src/Dto/Request/User/UpdateThemeDto.php`
- `src/Dto/Request/User/UpdateNotificationsDto.php`
- `src/Dto/Request/Recurrence/CreateRecurrenceDto.php`

#### Почему средний приоритет:
- Валидация сейчас тестируется только через API эндпоинты
- Прямые тесты DTO изолируют логику валидации
- Хорошо для целей документации

#### Расположение тестовых файлов:
```
tests/Unit/Dto/Request/Task/CreateTaskDtoTest.php
tests/Unit/Dto/Request/Task/UpdateTaskDtoTest.php
tests/Unit/Dto/Request/User/UserRegistrationRequestDtoTest.php
... (и т.д. для других DTO)
```

#### Тест-кейсы для CreateTaskDto:

```php
// tests/Unit/Dto/Request/Task/CreateTaskDtoTest.php

class CreateTaskDtoTest extends TestCase
{
    private ValidatorInterface $validator;

    /** @test */
    public function testValidDtoPassesValidation(): void
    // Валидный DTO проходит валидацию

    /** @test */
    public function testTitleIsRequired(): void
    // Ожидается: ошибка валидации когда title null/пустой

    /** @test */
    public function testTitleMinLength(): void
    // Ожидается: ошибка когда title < 3 символов

    /** @test */
    public function testTitleMaxLength(): void
    // Ожидается: ошибка когда title > 255 символов

    /** @test */
    public function testInvalidStatus(): void
    // Ожидается: ошибка когда status не является валидным значением enum

    /** @test */
    public function testInvalidPriority(): void
    // Ожидается: ошибка для невалидного приоритета

    /** @test */
    public function testDueDateMustBeFutureDate(): void
    // dueDate должна быть будущей датой

    /** @test */
    public function testDescriptionIsOptional(): void
    // description необязательное поле

    /** @test */
    public function testParentIdMustBeInteger(): void
    // parentId должно быть целым числом
}
```

**Всего тестов**: ~50-60 тестов (все DTO вместе)

---

## 📙 ПРИОРИТЕТ 4 - НИЗКИЙ (Опционально)

### 4.1. Development Commands - Integration тесты

**Приоритет**: 📙 Низкий
**Примерное время**: 2-3 часа
**Тип тестов**: Integration Tests

#### Компоненты:
- `src/Command/SeedTasksCommand.php`
- `src/Command/GenerateUserJourneyCommand.php`
- `src/Command/GenerateTestDataFastCommand.php`

#### Почему низкий приоритет:
- Только инструменты для разработки/заполнения данных
- Не используются в production
- Достаточно ручного QA

#### Расположение тестовых файлов:
```
tests/Integration/Command/SeedTasksCommandTest.php
tests/Integration/Command/GenerateUserJourneyCommandTest.php
tests/Integration/Command/GenerateTestDataFastCommandTest.php
```

#### Тест-кейсы (Пример):

```php
// tests/Integration/Command/SeedTasksCommandTest.php

class SeedTasksCommandTest extends KernelTestCase
{
    use ResetDatabase;

    /** @test */
    public function testCommandCreatesTasksInDatabase(): void
    // Команда создает задачи в базе данных

    /** @test */
    public function testCommandCreatesCorrectNumberOfTasks(): void
    // Команда создает корректное количество задач
}
```

**Всего тестов**: ~9 тестов (3 на команду)

---

### 4.2. Entity Business Logic - Unit тесты

**Приоритет**: 📙 Низкий
**Примерное время**: 2-3 часа
**Тип тестов**: Unit Tests

#### Компоненты:
- `src/Entity/Task.php` (методы: `isCompleted()`, `isOverdue()`, и т.д.)
- `src/Entity/RecurrenceRule.php` (методы валидации)

#### Почему низкий приоритет:
- Большинство методов сущностей - простые getters/setters
- Уже тестируются через Integration тесты
- Только методы с бизнес-логикой требуют прямых тестов

#### Расположение тестовых файлов:
```
tests/Unit/Entity/TaskTest.php
tests/Unit/Entity/RecurrenceRuleTest.php
```

#### Тест-кейсы для Task Entity:

```php
// tests/Unit/Entity/TaskTest.php

class TaskTest extends TestCase
{
    /** @test */
    public function testIsOverdueReturnsTrueForPastDueDate(): void
    // isOverdue() возвращает true для прошедшей даты

    /** @test */
    public function testIsOverdueReturnsFalseForFutureDueDate(): void
    // isOverdue() возвращает false для будущей даты

    /** @test */
    public function testIsOverdueReturnsFalseWhenNoDueDate(): void
    // isOverdue() возвращает false когда нет dueDate

    /** @test */
    public function testIsCompletedReturnsTrueWhenStatusCompleted(): void
    // isCompleted() возвращает true когда статус completed

    /** @test */
    public function testIsCompletedReturnsFalseForOtherStatuses(): void
    // isCompleted() возвращает false для других статусов

    /** @test */
    public function testAddSubtaskRelationship(): void
    // Добавление подзадачи

    /** @test */
    public function testRemoveSubtaskRelationship(): void
    // Удаление подзадачи

    /** @test */
    public function testAddTagRelationship(): void
    // Добавление тега
}
```

**Всего тестов**: ~12 тестов

---

## 📋 Дорожная карта реализации

### Фаза 1: Критическая безопасность и контроллеры (Неделя 1)
**Цель**: Покрыть все критические компоненты безопасности
**Время**: 8-10 часов

- [ ] TaskVoter unit тесты (2 часа)
- [ ] TagVoter unit тесты (1 час)
- [ ] Admin Controllers functional тесты (4 часа)
- [ ] EnumController functional тесты (1 час)
- [ ] TranslationController functional тесты (1 час)

**Результат**: Безопасность 100%, API контроллеры 100%

---

### Фаза 2: Recurrence стратегии (Неделя 2)
**Цель**: Покрыть сложную логику расчета дат
**Время**: 6-8 часов

- [ ] DailyRecurrenceStrategy unit тесты (1.5 часа)
- [ ] WeeklyRecurrenceStrategy unit тесты (1.5 часа)
- [ ] MonthlyRecurrenceStrategy unit тесты (2 часа)
- [ ] YearlyRecurrenceStrategy unit тесты (2 часа)
- [ ] CustomRecurrenceStrategy unit тесты (1 час)

**Результат**: Логика повторений 100% покрыта

---

### Фаза 3: Команды и Event Listeners (Неделя 3)
**Цель**: Покрыть инфраструктурные компоненты
**Время**: 4-6 часов

- [ ] MakeAdminCommand integration тесты (2 часа)
- [ ] LocaleListener unit тесты (1.5 часа)
- [ ] LocaleSubscriber unit тесты (1.5 часа)
- [ ] TaskEnumNormalizer unit тесты (1 час)

**Результат**: Команды 33%, Обработка событий 100%

---

### Фаза 4: DTO валидация (Неделя 4)
**Цель**: Изолировать логику валидации
**Время**: 4-5 часов

- [ ] CreateTaskDto validation тесты (1 час)
- [ ] UpdateTaskDto validation тесты (1 час)
- [ ] User DTOs validation тесты (2 часа)
- [ ] Recurrence DTO validation тесты (1 час)

**Результат**: DTO 100% покрыты

---

### Фаза 5: Опционально - Сущности и Dev команды (Неделя 5)
**Цель**: Достижение 100% покрытия
**Время**: 4-6 часов

- [ ] Task entity бизнес-логика тесты (1 час)
- [ ] RecurrenceRule entity тесты (1 час)
- [ ] GenerateTestDataFastCommand тесты (1 час)

**Результат**: Общее покрытие 95%+

---

## 📊 Ожидаемое покрытие после каждой фазы

| Фаза | Контроллеры | Сервисы | Репозитории | Безопасность | Команды | DTO | Сущности | Общее |
|-------|-------------|----------|--------------|----------|----------|------|----------|---------|
| **Текущее** | 73% (8/11) | 90% (9/10) | 100% | 78% (7/9) | 17% (1/6) | 0% | 0% | ~75% |
| **Фаза 1** | 100% | 90% | 100% | 100% | 17% | 0% | 0% | ~85% |
| **Фаза 2** | 100% | 100% | 100% | 100% | 17% | 0% | 0% | ~88% |
| **Фаза 3** | 100% | 100% | 100% | 100% | 33% | 0% | 0% | ~90% |
| **Фаза 4** | 100% | 100% | 100% | 100% | 33% | 100% | 0% | ~93% |
| **Фаза 5** | 100% | 100% | 100% | 100% | 67% | 100% | 50% | ~95% |

---

## 🔧 Инструменты и команды для тестирования

### Запуск тестов

```bash
# Все тесты
docker exec backend-php83 vendor/bin/phpunit

# Конкретный тестовый файл
docker exec backend-php83 vendor/bin/phpunit tests/Unit/Security/Voter/TaskVoterTest.php

# Конкретный тестовый метод
docker exec backend-php83 vendor/bin/phpunit --filter testOwnerCanViewTask

# С покрытием (требуется Xdebug)
docker exec backend-php83 php -dxdebug.mode=coverage vendor/bin/phpunit --coverage-html var/coverage
```

### Отчет о покрытии кода

```bash
# Установка Xdebug
docker exec backend-php83 pecl install xdebug

# Генерация HTML отчета о покрытии
docker exec backend-php83 php -dxdebug.mode=coverage vendor/bin/phpunit --coverage-html var/coverage

# Открыть отчет
open var/coverage/index.html
```

---

## 📝 Руководство по написанию тестов

### AAA паттерн (Arrange, Act, Assert)

```php
/** @test */
public function testOwnerCanViewTask(): void
{
    // Arrange: Подготовка тестовых данных
    $user = new User();
    $user->setEmail('owner@test.com');

    $task = new Task();
    $task->setUser($user);

    $token = $this->createMock(TokenInterface::class);
    $token->method('getUser')->willReturn($user);

    $voter = new TaskVoter();

    // Act: Выполнение тестируемого кода
    $result = $voter->vote($token, $task, ['TASK_VIEW']);

    // Assert: Проверка результата
    $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
}
```

### Мокирование зависимостей

```php
// Мок репозитория
$taskRepository = $this->createMock(TaskRepository::class);
$taskRepository
    ->expects($this->once())
    ->method('find')
    ->with(123)
    ->willReturn($task);

// Мок сервиса
$service = new TaskService($taskRepository, $entityManager);
```

### Тестирование исключений

```php
/** @test */
public function testThrowsExceptionWhenTaskNotFound(): void
{
    $this->expectException(TaskNotFoundException::class);
    $this->expectExceptionMessage('Task with ID 999 not found');

    $service->getTask(999);
}
```

---

## 🎯 Критерии успеха

### Definition of Done для каждого теста:

- [ ] Тестовый файл создан в правильном месте
- [ ] Все методы компонента протестированы
- [ ] Happy path покрыт
- [ ] Сценарии с ошибками покрыты
- [ ] Граничные случаи покрыты
- [ ] Моки используются корректно (для Unit тестов)
- [ ] Используется трейт ResetDatabase (для Integration/Functional тестов)
- [ ] Названия тестов описательные
- [ ] Следование AAA паттерну
- [ ] Все тесты проходят зеленым ✅

### Цели покрытия:

- **Критический приоритет**: Должен достигнуть 100%
- **Высокий приоритет**: Должен достигнуть 100%
- **Средний приоритет**: Должен достигнуть 80%+
- **Низкий приоритет**: Опционально, но желательно

---

## 📚 Связанные документы

- **[TEST_COVERAGE.md](TEST_COVERAGE.md)** - Текущий отчет о тестовом покрытии
- **[TESTING.md](../guides/testing/TESTING.md)** - Руководство и best practices по тестированию
- **[CODING_STANDARDS.md](../CODING_STANDARDS.md)** - Стандарты качества кода
- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Обзор backend архитектуры

---

## 📌 Заметки для AI реализации

### При написании тестов:

1. **Всегда сначала читайте существующие похожие тесты** - Следуйте установленным паттернам
2. **Используйте фабрики для тестовых данных** - TaskFactory, UserFactory, TagFactory
3. **Мокируйте внешние зависимости** - Не тестируйте сторонний код
4. **Тестируйте одну вещь за раз** - Единая ответственность на тест
5. **Используйте описательные имена тестов** - `testOwnerCanViewTask`, а не `testVoter`
6. **Добавляйте комментарии для сложной настройки** - Помогите будущим разработчикам понять

### Распространенные ошибки, которых следует избегать:

- ❌ Не тестируйте getters/setters (они тривиальны)
- ❌ Не тестируйте код фреймворка (Symfony уже протестирован)
- ❌ Не тестируйте сторонние библиотеки
- ❌ Не используйте реальную БД для Unit тестов (используйте моки)
- ❌ Не забывайте очищать (трейт ResetDatabase)
- ❌ Не пишите тесты, зависящие от других тестов

---

**Версия**: 1.0.0
**Последнее обновление**: 2025-11-10
**Готово к реализации**: ✅ Да
