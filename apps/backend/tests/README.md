# Backend Tests Documentation

Полная документация по тестированию backend Symfony приложения.

## 📊 Общая статистика

**Всего: 78 тестов, 187 assertions**

- ✅ **17 Unit тестов** (44 assertions) - изолированное тестирование бизнес-логики
- ✅ **52 Functional API тестов** (120 assertions) - полное тестирование всех endpoints
- ✅ **9 Integration тестов** (23 assertions) - тестирование интеграций с внешними сервисами (Google OAuth)

## 🚀 Запуск тестов

Все тесты запускаются внутри Docker контейнера `backend-php83`.

### Запустить ВСЕ тесты

```bash
docker exec backend-php83 bash -c "cd /var/www/backend-app && php vendor/bin/phpunit --testdox"
```

### Запустить только Unit тесты

```bash
docker exec backend-php83 bash -c "cd /var/www/backend-app && php vendor/bin/phpunit tests/Unit --testdox"
```

### Запустить только Functional тесты

```bash
docker exec backend-php83 bash -c "cd /var/www/backend-app && php vendor/bin/phpunit tests/Functional --testdox"
```

### Запустить только Integration тесты

```bash
docker exec backend-php83 bash -c "cd /var/www/backend-app && php vendor/bin/phpunit tests/Integration --testdox"
```

### Запустить конкретный тест

```bash
docker exec backend-php83 bash -c "cd /var/www/backend-app && php vendor/bin/phpunit tests/Functional/Api/UserRegistrationTest.php --testdox"
```

## 📂 Структура тестов

```
tests/
├── bootstrap.php                    # Bootstrap для PHPUnit
├── README.md                        # Этот файл
├── Unit/                           # Unit тесты (17 тестов)
│   ├── Repository/
│   │   └── UserRepositoryTest.php  # 7 тестов
│   ├── Security/
│   │   └── GoogleAuthenticatorTest.php  # 5 тестов
│   └── Service/
│       └── UserRegistrationServiceTest.php  # 5 тестов
├── Functional/                     # Functional API тесты (52 теста)
│   └── Api/
│       ├── AuthenticationTest.php       # 12 тестов
│       ├── GoogleAuthTest.php           # 8 тестов
│       ├── TokenRefreshTest.php         # 11 тестов
│       ├── UserProfileTest.php          # 7 тестов
│       └── UserRegistrationTest.php     # 14 тестов
└── Integration/                    # Integration тесты (9 тестов)
    ├── README.md                   # Детальная документация
    └── Api/
        ├── GoogleAuthIntegrationTest.php      # 4 теста
        └── GoogleAuthWithHttpMockTest.php     # 5 тестов
```

## 🧪 Unit Tests (17 тестов, 44 assertions)

Unit тесты изолированно проверяют бизнес-логику с использованием моков для всех зависимостей.

### UserRepository (7 тестов)

**Файл:** `tests/Unit/Repository/UserRepositoryTest.php`

Тестирует методы репозитория для работы с пользователями:
- `save()` с flush=true/false
- `remove()` с flush=true/false
- `upgradePassword()` успешно и с исключением для неподдерживаемого пользователя
- `getEntityClass()`

**Используемые моки:** `EntityManagerInterface`

### UserRegistrationService (5 тестов)

**Файл:** `tests/Unit/Service/UserRegistrationServiceTest.php`

Тестирует сервис регистрации пользователей:
- Успешная регистрация
- Исключение при существующем пользователе
- Корректное хеширование пароля
- Вызов `eraseCredentials()`
- Сохранение с flush=true

**Используемые моки:** `UserRepository`, `UserPasswordHasherInterface`, `TranslatorInterface`

### GoogleAuthenticator (5 тестов)

**Файл:** `tests/Unit/Security/GoogleAuthenticatorTest.php`

Тестирует аутентификатор Google OAuth2:
- Загрузка существующего пользователя из JWT
- Создание нового пользователя из JWT
- Обработка отсутствующего имени
- Исключение при отсутствующем email
- Обработка отсутствующего Google ID

**Используемые моки:** `EntityManagerInterface`, `UserRepository`

## 🌐 Functional API Tests (52 теста, 120 assertions)

Functional тесты проверяют полный workflow API endpoints с реальной тестовой базой данных, используя **Zenstruck Foundry** для создания фикстур.

### Общие особенности Functional тестов

- ✅ Используют тестовую базу данных (автоматически пересоздается перед тестами)
- ✅ Используют **Zenstruck Foundry** для создания тестовых данных
- ✅ Тестируют реальные HTTP запросы через `KernelBrowser`
- ✅ Проверяют статус коды, структуру ответов, валидацию
- ✅ Используют **Data Providers** для параметризованных тестов

### User Registration (`POST /api/users`) - 14 тестов

**Файл:** `tests/Functional/Api/UserRegistrationTest.php`

Полное покрытие endpoint регистрации пользователей:

**Успешные сценарии (4 теста с Data Provider):**
- Стандартный email
- Email с поддоменом
- Email с плюсом (`user+test@example.com`)
- Длинный пароль

**Сценарии ошибок:**
- Регистрация с существующим email (400)
- Отсутствующие/пустые поля (422)
- Невалидный формат email (422)
- Слишком короткий пароль (422)
- Пустое тело запроса (422)
- Невалидный JSON (400)
- Отсутствующий Content-Type

### Authentication (`POST /api/auth`) - 12 тестов

**Файл:** `tests/Functional/Api/AuthenticationTest.php`

Тестирование JWT аутентификации:

**Успешные сценарии:**
- Успешный логин с получением JWT и refresh token
- Использование полученного токена для доступа к защищенным endpoints
- Множественные логины одного пользователя
- Проверка структуры JWT (3 сегмента)

**Сценарии ошибок:**
- Неправильный пароль (401)
- Неправильный email (401)
- Несуществующий пользователь (401)
- Пустые credentials (400)
- Отсутствующие поля (400)
- Невалидный JSON (400)

### Token Refresh (`POST /api/token/refresh`) - 11 тестов

**Файл:** `tests/Functional/Api/TokenRefreshTest.php`

Тестирование обновления JWT токенов:

**Успешные сценарии:**
- Успешное обновление токена
- Использование нового токена для доступа к API
- Множественное обновление токенов (3 раза подряд)
- Проверка что expiration в будущем
- Проверка поведения старого refresh token после обновления

**Сценарии ошибок:**
- Невалидный refresh token (401)
- Отсутствующий refresh token (401)
- Пустой refresh token (401)
- Null refresh token (401)
- Пустое тело запроса (401)
- Невалидный JSON (401)

### User Profile (`GET /api/users/me`) - 7 тестов

**Файл:** `tests/Functional/Api/UserProfileTest.php`

Тестирование получения профиля пользователя:

**Успешные сценарии:**
- Получение профиля с валидным JWT
- Получение профиля Google пользователя
- Проверка структуры ответа (id, email, roles, createdAt, updatedAt)
- Проверка что Google пользователь имеет поле `name`

**Сценарии ошибок:**
- Запрос без токена (401)
- Невалидный токен (401)
- Истекший/поддельный токен (401)
- Неправильный формат Authorization header (401)

### Google Auth (`POST /api/auth/google`) - 8 тестов

**Файл:** `tests/Functional/Api/GoogleAuthTest.php`

Тестирование Google OAuth2 аутентификации:

**Сценарии валидации:**
- Отсутствующий credential (400)
- Пустой credential (400)
- Null credential (400)
- Невалидный JWT (500/422)
- Неправильный формат JWT (500/422)
- Пустое тело запроса (400)
- Невалидный JSON (400)
- Проверка существования endpoint

> **Примечание:** Успешная Google аутентификация требует реального Google JWT токена и мокирования внешних HTTP запросов, поэтому полное тестирование этого flow выполняется на уровне integration/e2e тестов.

## 🔗 Integration Tests (9 тестов, 23 assertions)

Integration тесты проверяют взаимодействие с внешними сервисами, в частности Google OAuth2.

### GoogleAuthIntegrationTest (4 теста)

**Файл:** `tests/Integration/Api/GoogleAuthIntegrationTest.php`

Базовые интеграционные тесты Google OAuth:

**Тестируемые сценарии:**
- ✅ Создание нового пользователя через Google
- ✅ Логин существующего пользователя через Google
- ✅ Отклонение истекших токенов
- ✅ Валидация email_verified флага

**Особенности:**
- Генерирует реальные RSA ключи для подписи JWT
- Создает валидные Google ID Tokens
- Тестирует полный flow аутентификации

### GoogleAuthWithHttpMockTest (5 тестов)

**Файл:** `tests/Integration/Api/GoogleAuthWithHttpMockTest.php`

Расширенные интеграционные тесты с HTTP мокированием:

**Тестируемые сценарии:**
- ✅ Полный flow с новым пользователем
- ✅ Полный flow с существующим пользователем
- ✅ Использование токена для доступа к защищенным endpoints
- ✅ Сохранение ролей при повторном логине
- ✅ Множественные Google пользователи в системе

**Особенности:**
- Использует MockHttpClient для мокирования Google API
- Генерирует валидные JWKs (JSON Web Key Sets)
- Создает полноценные Google ID Tokens с подписью
- Тестирует все аспекты OAuth flow

**Детальная документация:** [`tests/Integration/README.md`](backend/tests/Integration/README.md)

## 🛠️ Используемые инструменты

### PHPUnit 9.6

Основной фреймворк для тестирования:
- Моки и стабы для изоляции зависимостей
- Data Providers для параметризованных тестов
- Assertions для проверки результатов
- TestDox для читаемых отчетов

### Zenstruck Foundry 2.0

Фабрики для создания тестовых данных:
- `UserFactory` - создание пользователей с автоматическим хешированием паролей
- `DefaultUserStory` - предустановленные наборы данных
- Автоматическая очистка базы после каждого теста (`ResetDatabase` trait)

### Symfony Test Tools

- `WebTestCase` - базовый класс для functional тестов
- `KernelBrowser` - симуляция HTTP запросов
- Тестовая база данных (отдельная от dev/prod)

## 📝 Best Practices

### Unit Tests

1. **Изоляция**: Каждый unit тест фокусируется на одной единице кода (метод/класс)
2. **Моки**: Все зависимости мокируются через `createMock()`
3. **Именование**: `test{MethodName}{Scenario}` (например, `testSaveWithFlushTrue`)
4. **Assertions**: Четкие и специфичные проверки ожидаемого поведения

### Functional Tests

1. **Реалистичность**: Тесты максимально приближены к реальному использованию API
2. **Изоляция данных**: Каждый тест создает свои данные через Foundry
3. **Уникальность**: Уникальные email'ы для избежания конфликтов
4. **Data Providers**: Используются для тестирования множества сценариев
5. **Проверка структуры**: Не только статус коды, но и структура ответов

## 🐛 Troubleshooting

### Ошибка "Database does not exist"

```bash
docker exec backend-php83 bash -c "cd /var/www/backend-app && APP_ENV=test php bin/console doctrine:database:create"
docker exec backend-php83 bash -c "cd /var/www/backend-app && APP_ENV=test php bin/console doctrine:schema:create"
```

### Ошибка "Cannot mock final class"

Убедитесь что тестируемые классы не имеют модификатор `final`.

### Дубликаты в БД при functional тестах

Используйте уникальные email'ы для каждого теста или проверьте что `ResetDatabase` trait подключен.

### JWT токены идентичны

Это нормально если токены создаются в одну секунду. Проверяйте не равенство токенов, а их работоспособность.

## 📚 Дополнительная информация

- [Symfony Testing](https://symfony.com/doc/current/testing.html)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Zenstruck Foundry](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html)
- [LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle/blob/2.x/Resources/doc/index.rst)
