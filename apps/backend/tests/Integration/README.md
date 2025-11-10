# Integration Tests Documentation

Интеграционные тесты для backend приложения, которые проверяют взаимодействие между компонентами и внешними сервисами.

## 📋 Обзор

Интеграционные тесты отличаются от Unit и Functional тестов:

- **Unit тесты** - изолированное тестирование отдельных компонентов с мокированием всех зависимостей
- **Functional тесты** - тестирование API endpoints с реальной базой данных
- **Integration тесты** - тестирование интеграций с внешними сервисами (Google OAuth, Payment providers, External APIs)

## 🎯 Google OAuth Integration Tests

### GoogleAuthIntegrationTest

**Файл:** `tests/Integration/Api/GoogleAuthIntegrationTest.php`

Базовые интеграционные тесты Google OAuth2 аутентификации:

**Тестируемые сценарии:**
- ✅ Создание нового пользователя через Google
- ✅ Логин существующего пользователя через Google
- ✅ Отклонение истекших токенов
- ✅ Валидация email_verified флага

**Особенности:**
- Создает реальные RSA ключи для подписи JWT
- Генерирует валидные Google ID Tokens
- Тестирует структуру токенов

### GoogleAuthWithHttpMockTest

**Файл:** `tests/Integration/Api/GoogleAuthWithHttpMockTest.php`

Расширенные интеграционные тесты с полным мокированием HTTP запросов:

**Тестируемые сценарии:**
- ✅ Полный flow аутентификации с новым пользователем
- ✅ Полный flow аутентификации с существующим пользователем
- ✅ Использование полученного токена для доступа к защищенным endpoints
- ✅ Сохранение ролей пользователя при повторном логине
- ✅ Множественные Google пользователи в системе

**Особенности:**
- Использует `MockHttpClient` для мокирования Google API
- Генерирует валидные JWKs (JSON Web Key Sets)
- Создает полноценные Google ID Tokens с подписью
- Тестирует все аспекты Google OAuth flow

## 🚀 Запуск тестов

### Все интеграционные тесты

```bash
docker exec backend-php83 bash -c "cd /var/www/backend-app && php vendor/bin/phpunit tests/Integration --testdox"
```

### Только Google Auth тесты

```bash
docker exec backend-php83 bash -c "cd /var/www/backend-app && php vendor/bin/phpunit tests/Integration/Api/GoogleAuthIntegrationTest.php --testdox"
```

### С подробным выводом

```bash
docker exec backend-php83 bash -c "cd /var/www/backend-app && php vendor/bin/phpunit tests/Integration --testdox --colors=always -v"
```

### По группам

```bash
# Только Google Auth интеграционные тесты
docker exec backend-php83 bash -c "cd /var/www/backend-app && php vendor/bin/phpunit --group google-auth --testdox"

# Все интеграционные тесты
docker exec backend-php83 bash -c "cd /var/www/backend-app && php vendor/bin/phpunit --group integration --testdox"
```

## 🛠️ Технические детали

### Генерация тестовых RSA ключей

Интеграционные тесты создают реальные RSA ключи для подписи JWT:

```php
$privateKey = openssl_pkey_new([
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
]);
```

### Создание валидного Google ID Token

```php
$header = [
    'alg' => 'RS256',
    'typ' => 'JWT',
    'kid' => 'test-key-id'
];

$payload = [
    'iss' => 'https://accounts.google.com',
    'sub' => 'google-user-id',
    'email' => 'user@gmail.com',
    'email_verified' => true,
    'name' => 'Test User',
    'iat' => time(),
    'exp' => time() + 3600,
];
```

### Мокирование Google JWKs Endpoint

Google использует JWKs (JSON Web Key Sets) для публикации публичных ключей:

```php
$jwks = [
    'keys' => [
        [
            'kty' => 'RSA',
            'alg' => 'RS256',
            'use' => 'sig',
            'kid' => 'test-key-id',
            'n' => base64_url_encode($modulus),
            'e' => base64_url_encode($exponent),
        ]
    ]
];
```

## 📝 Текущее состояние

### ⚠️ Ограничения текущей реализации

**Проблема:** `GoogleAuthController` использует `file_get_contents()` для запроса к Google API:

```php
$googleJwks = json_decode(file_get_contents('https://www.googleapis.com/oauth2/v3/certs'), true);
```

**Решение:** Для полного мокирования нужно:

1. Заменить `file_get_contents()` на `HttpClientInterface`
2. Мокировать HTTP клиент в тестах:

```php
$mockResponse = new MockResponse(json_encode($jwks));
$mockHttpClient = new MockHttpClient($mockResponse);
static::getContainer()->set('http_client', $mockHttpClient);
```

3. Добавить в `services_test.yaml`:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: true

    http_client:
        class: Symfony\Component\HttpClient\MockHttpClient
```

### ✅ Что уже работает

- Создание валидных Google ID Tokens
- Генерация JWKs
- Тестирование логики работы с пользователями
- Проверка защищенных endpoints
- Тестирование сохранения ролей

### 🎯 Что можно улучшить

1. **Рефакторинг GoogleAuthController**
   - Заменить `file_get_contents()` на `HttpClientInterface`
   - Добавить кеширование Google JWKs
   - Вынести логику декодирования в отдельный сервис

2. **Расширение тестов**
   - Тестирование различных Google audiences
   - Проверка нескольких ключей в JWKs
   - Ротация ключей
   - Тестирование неправильных подписей

3. **Дополнительные сценарии**
   - Google аккаунт без имени
   - Google аккаунт без фото
   - Смена email в Google аккаунте
   - Linking существующего email пользователя с Google

## 🔍 Примеры использования

### Создание пользователя через Google OAuth

```php
public function testGoogleAuthCreatesUser(): void
{
    $email = 'newuser@gmail.com';
    $googleId = 'google-123';
    $name = 'Test User';

    $idToken = $this->createGoogleIdToken([
        'sub' => $googleId,
        'email' => $email,
        'email_verified' => true,
        'name' => $name,
    ]);

    $this->client->request(
        'POST',
        '/api/auth/google',
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        json_encode(['credential' => $idToken])
    );

    $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    
    // Проверяем что пользователь создан
    $user = $userRepository->findOneBy(['email' => $email]);
    $this->assertNotNull($user);
}
```

### Проверка токена для защищенных endpoints

```php
public function testGoogleUserCanAccessProtectedEndpoints(): void
{
    // Создаем Google пользователя
    $user = $this->createGoogleUser('test@gmail.com');
    
    // Получаем JWT токен
    $token = $jwtManager->create($user);
    
    // Делаем запрос к защищенному endpoint
    $this->client->request(
        'GET',
        '/api/users/me',
        [],
        [],
        ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
    );

    $this->assertResponseStatusCodeSame(Response::HTTP_OK);
}
```

## 📚 Дополнительные ресурсы

- [Google Identity - Verify ID Tokens](https://developers.google.com/identity/gsi/web/guides/verify-google-id-token)
- [JSON Web Keys (JWK)](https://datatracker.ietf.org/doc/html/rfc7517)
- [Symfony HttpClient](https://symfony.com/doc/current/http_client.html)
- [PHPUnit Test Doubles](https://phpunit.de/manual/current/en/test-doubles.html)

## 🐛 Troubleshooting

### "Wrong number of segments" ошибка

Это означает что JWT токен имеет неправильный формат. JWT должен содержать 3 части:
```
header.payload.signature
```

### "Invalid signature" ошибка

Подпись JWT не совпадает с публичным ключом. Убедитесь что:
- Используется правильный приватный ключ для подписи
- Публичный ключ в JWKs соответствует приватному
- `kid` (Key ID) в header совпадает с `kid` в JWKs

### Тесты падают с timeout

Google API может быть недоступен или медленно отвечать. Используйте моки для изоляции от внешних сервисов.

## 🎓 Best Practices

1. **Изоляция от внешних сервисов** - всегда мокировать HTTP запросы
2. **Использование реальных криптографических операций** - тестировать с настоящими ключами
3. **Проверка всех аспектов** - не только успешные сценарии, но и ошибки
4. **Читаемость тестов** - каждый тест должен быть понятен без документации
5. **Группировка тестов** - использовать `@group` для удобного запуска

---

**Версия:** 1.0  
**Дата:** 26 октября 2025  
**Статус:** В разработке (требуется рефакторинг GoogleAuthController для полного мокирования)

