# 🔐 Аутентификация и Авторизация

> **TL;DR**: Аутентификация на основе JWT с Google OAuth2 One Tap. Access-токены (30 мин) + Refresh-токены (30 дней). Security voters для авторизации. Асимметричное шифрование RS256 для максимальной безопасности.

---

## Содержание

- [Обзор](#обзор)
- [Поток JWT-токенов](#поток-jwt-токенов)
- [Интеграция Google OAuth2](#интеграция-google-oauth2)
- [Механизм обновления токенов](#механизм-обновления-токенов)
- [Security Voters](#security-voters)
- [Примеры кода](#примеры-кода)

---

## Обзор

### Стратегия аутентификации

```
┌─────────────────────────────────────────────────────────────┐
│                  ПОТОК АУТЕНТИФИКАЦИИ                       │
└─────────────────────────────────────────────────────────────┘

Пользователь нажимает "Войти через Google"
           │
           ▼
Появляется Google One Tap UI
           │
           ▼
Пользователь выбирает Google аккаунт
           │
           ▼
Google возвращает ID Token (JWT)
           │
           ▼
Frontend отправляет токен на /api/auth/google
           │
           ▼
Backend проверяет токен с помощью публичных ключей Google
           │
           ▼
Backend находит/создает User в базе данных
           │
           ▼
Backend генерирует Access Token (30 мин)
           │
           ▼
Backend генерирует Refresh Token (30 дней)
           │
           ▼
Frontend сохраняет токены в localStorage
           │
           ▼
Пользователь аутентифицирован!
```

---

## Поток JWT-токенов

### Типы токенов

#### 1. Access Token (30 минут)

```json
{
  "iat": 1641024000,
  "exp": 1641025800,
  "roles": ["ROLE_USER"],
  "username": "user@example.com"
}
```

**Назначение:** Аутентификация API-запросов
**Время жизни:** 30 минут
**Хранение:** `localStorage` (frontend)
**Использование:** Отправляется в заголовке `Authorization: Bearer <token>`

#### 2. Refresh Token (30 дней)

```json
{
  "token": "550e8400-e29b-41d4-a716-446655440000",
  "username": "user@example.com",
  "valid": "2025-02-05T00:00:00+00:00"
}
```

**Назначение:** Получение нового access-токена
**Время жизни:** 30 дней
**Хранение:** таблица `refresh_tokens` (база данных)
**Использование:** POST на `/api/token/refresh`

---

### Конфигурация токенов

```yaml
# config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 1800  # 30 минут
    user_identity_field: email
```

### Генерация ключей (RS256)

```bash
# Генерация приватного ключа
openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096

# Генерация публичного ключа
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

# Установка passphrase в .env
JWT_PASSPHRASE=your-secret-passphrase
```

---

## Интеграция Google OAuth2

### Конфигурация

```yaml
# config/packages/knpu_oauth2_client.yaml
knpu_oauth2_client:
    clients:
        google:
            type: google
            client_id: '%env(GOOGLE_CLIENT_ID)%'
            client_secret: '%env(GOOGLE_CLIENT_SECRET)%'
            redirect_route: connect_google_check
            redirect_params: {}
```

### Frontend (Google One Tap)

```typescript
// Frontend: Загрузка библиотеки Google Sign-In
<script src="https://accounts.google.com/gsi/client" async defer></script>

// Инициализация One Tap
google.accounts.id.initialize({
  client_id: 'YOUR_GOOGLE_CLIENT_ID',
  callback: handleCredentialResponse
})

// Обработка ответа
async function handleCredentialResponse(response: CredentialResponse) {
  const credential = response.credential  // JWT от Google

  // Отправка на backend
  const result = await axios.post('/api/auth/google', {
    credential
  })

  // Сохранение токенов
  localStorage.setItem('access_token', result.data.token)
  localStorage.setItem('refresh_token', result.data.refreshToken)
}
```

### Backend (валидация токена)

```php
<?php
// src/Controller/Auth/GoogleAuthController.php

#[Route('/api/auth/google', methods: ['POST'])]
public function google(
    Request $request,
    GoogleAuthenticator $googleAuthenticator,
    JWTTokenManagerInterface $jwtManager,
    RefreshTokenManagerInterface $refreshTokenManager
): JsonResponse {
    $data = json_decode($request->getContent(), true);
    $credential = $data['credential'] ?? null;

    if (!$credential) {
        return $this->json(['error' => 'Missing credential'], 400);
    }

    // ✅ Получение публичных ключей Google
    $googleJwks = json_decode(
        file_get_contents('https://www.googleapis.com/oauth2/v3/certs'),
        true
    );

    // ✅ Декодирование и проверка JWT с помощью ключей Google
    $decoded = JWT::decode($credential, JWK::parseKeySet($googleJwks));

    $email = $decoded->email ?? null;

    if (!$email) {
        return $this->json(['error' => 'Invalid token'], 400);
    }

    // ✅ Поиск или создание пользователя
    $user = $googleAuthenticator->loadUserFromDecodedJwt($decoded);

    // ✅ Генерация access-токена
    $token = $jwtManager->create($user);

    // ✅ Генерация refresh-токена
    $refreshToken = $refreshTokenManager->create();
    $refreshToken->setRefreshToken(Uuid::v4()->toRfc4122());
    $refreshToken->setUsername($user->getUserIdentifier());
    $refreshToken->setValid((new \DateTime())->modify('+30 days'));

    $refreshTokenManager->save($refreshToken);

    return $this->json([
        'token' => $token,
        'refreshToken' => $refreshToken->getRefreshToken(),
        'refreshTokenExpiration' => $refreshToken->getValid()?->getTimestamp(),
    ]);
}
```

### Google Authenticator

```php
<?php
// src/Security/GoogleAuthenticator.php

class GoogleAuthenticator extends OAuthUserProvider
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    public function loadUserFromDecodedJwt(\stdClass $jwt): User
    {
        $email = $jwt->email ?? null;

        if (!$email) {
            throw new \RuntimeException('Email not found in Google token');
        }

        // ✅ Поиск существующего пользователя
        $user = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        // ✅ Создание нового пользователя, если не существует
        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setGoogleId($jwt->sub ?? null);
            $user->setGoogleUserName($jwt->name ?? 'Google User');

            $this->em->persist($user);
            $this->em->flush();
        }

        return $user;
    }
}
```

---

## Механизм обновления токенов

### Зачем нужны refresh-токены?

**Проблема:** Access-токены истекают через 30 минут
**Решение:** Использование refresh-токена для получения нового access-токена без повторной авторизации

### Реализация на Frontend

```typescript
// src/services/api.service.ts

class ApiService {
  private isRefreshing = false
  private failedQueue: Array<any> = []

  setupInterceptors() {
    // Перехватчик ответов
    this.axiosInstance.interceptors.response.use(
      (response) => response,
      async (error: AxiosError) => {
        const originalRequest = error.config as any

        // Если 401 и еще не повторяли
        if (error.response?.status === 401 && !originalRequest._retry) {
          const refreshToken = localStorage.getItem('refresh_token')

          if (!refreshToken) {
            // Нет refresh-токена → редирект на логин
            window.location.href = '/login'
            return Promise.reject(error)
          }

          // Если уже обновляем, добавить запрос в очередь
          if (this.isRefreshing) {
            return new Promise((resolve, reject) => {
              this.failedQueue.push({ resolve, reject })
            })
              .then(token => {
                originalRequest.headers.Authorization = `Bearer ${token}`
                return this.axiosInstance.request(originalRequest)
              })
          }

          originalRequest._retry = true
          this.isRefreshing = true

          try {
            // ✅ Вызов endpoint обновления
            const { data } = await this.axiosInstance.post('/api/token/refresh', {
              refreshToken
            })

            // ✅ Сохранение новых токенов
            localStorage.setItem('access_token', data.token)
            localStorage.setItem('refresh_token', data.refreshToken)

            // ✅ Обработка запросов в очереди
            this.processQueue(null, data.token)

            // ✅ Повтор оригинального запроса
            originalRequest.headers.Authorization = `Bearer ${data.token}`
            return this.axiosInstance.request(originalRequest)
          } catch (refreshError) {
            // ✅ Обновление не удалось → редирект на логин
            this.processQueue(refreshError, null)
            this.clearAuth()
            window.location.href = '/login'
            return Promise.reject(refreshError)
          } finally {
            this.isRefreshing = false
          }
        }

        return Promise.reject(error)
      }
    )
  }

  private processQueue(error: any = null, token: string | null = null) {
    this.failedQueue.forEach(prom => {
      if (error) {
        prom.reject(error)
      } else {
        prom.resolve(token)
      }
    })
    this.failedQueue = []
  }
}
```

### Backend endpoint обновления

```php
<?php
// Обрабатывается gesdinet/jwt-refresh-token-bundle

#[Route('/api/token/refresh', methods: ['POST'])]
public function refresh(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $refreshToken = $data['refreshToken'] ?? null;

    // ✅ Валидация refresh-токена
    $token = $this->refreshTokenManager->get($refreshToken);

    if (!$token || !$token->isValid()) {
        return $this->json(['error' => 'Invalid refresh token'], 401);
    }

    // ✅ Загрузка пользователя
    $user = $this->userProvider->loadUserByIdentifier($token->getUsername());

    // ✅ Генерация нового access-токена
    $jwt = $this->jwtManager->create($user);

    // ✅ Генерация нового refresh-токена (ротация)
    $newRefreshToken = $this->refreshTokenManager->create();
    $newRefreshToken->setRefreshToken(Uuid::v4()->toRfc4122());
    $newRefreshToken->setUsername($user->getUserIdentifier());
    $newRefreshToken->setValid((new \DateTime())->modify('+30 days'));

    $this->refreshTokenManager->save($newRefreshToken);

    // ✅ Инвалидация старого refresh-токена
    $this->refreshTokenManager->delete($token);

    return $this->json([
        'token' => $jwt,
        'refreshToken' => $newRefreshToken->getRefreshToken(),
    ]);
}
```

---

## Security Voters

### Что такое Voters?

**Назначение:** Сложная логика авторизации (помимо простых ролей)

**Пример:** "Может ли пользователь редактировать эту задачу?"
- Пользователь должен быть аутентифицирован ✓
- Пользователь должен быть владельцем задачи ✓
- Задача не должна быть архивирована ✓

### Реализация TaskVoter

```php
<?php
// src/Security/Voter/TaskVoter.php

class TaskVoter extends Voter
{
    const EDIT = 'TASK_EDIT';
    const DELETE = 'TASK_DELETE';
    const VIEW = 'TASK_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Голосуем только за сущности Task
        if (!in_array($attribute, [self::EDIT, self::DELETE, self::VIEW])) {
            return false;
        }

        if (!$subject instanceof Task) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool {
        $user = $token->getUser();

        // Пользователь должен быть аутентифицирован
        if (!$user instanceof User) {
            return false;
        }

        /** @var Task $task */
        $task = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($task, $user),
            self::EDIT => $this->canEdit($task, $user),
            self::DELETE => $this->canDelete($task, $user),
            default => throw new \LogicException('Invalid attribute'),
        };
    }

    private function canView(Task $task, User $user): bool
    {
        // ✅ Пользователь должен быть владельцем задачи
        return $task->getUser() === $user;
    }

    private function canEdit(Task $task, User $user): bool
    {
        // ✅ Пользователь должен быть владельцем задачи
        if ($task->getUser() !== $user) {
            return false;
        }

        // ✅ Задача не должна быть архивирована
        if ($task->isArchived()) {
            return false;
        }

        return true;
    }

    private function canDelete(Task $task, User $user): bool
    {
        // ✅ Пользователь должен быть владельцем задачи
        return $task->getUser() === $user;
    }
}
```

### Использование Voters в контроллерах

```php
<?php

#[Route('/api/tasks/{id}', methods: ['PUT'])]
public function update(
    int $id,
    #[MapRequestPayload] UpdateTaskDto $dto,
    #[CurrentUser] User $user
): JsonResponse {
    $task = $this->taskRepository->find($id);

    if (!$task) {
        throw new NotFoundHttpException();
    }

    // ✅ Проверка авторизации через voter
    $this->denyAccessUnlessGranted('TASK_EDIT', $task);

    // Если мы здесь, пользователь МОЖЕТ редактировать задачу
    $updatedTask = $this->taskService->updateTask($task, $dto);

    return $this->json($updatedTask);
}
```

### Использование Voters в сервисах

```php
<?php
use Symfony\Bundle\SecurityBundle\Security;

class TaskService
{
    public function __construct(
        private readonly Security $security
    ) {}

    public function deleteTask(Task $task): void
    {
        // ✅ Проверка авторизации
        if (!$this->security->isGranted('TASK_DELETE', $task)) {
            throw new AccessDeniedException('You cannot delete this task');
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();
    }
}
```

---

## Примеры кода

### Полный поток аутентификации

```php
<?php
// 1. Пользователь входит через Google
POST /api/auth/google
Body: { "credential": "eyJhbGc..." }

Response:
{
  "token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refreshToken": "550e8400-e29b-41d4-a716-446655440000",
  "refreshTokenExpiration": 1738790400
}

// 2. Frontend сохраняет токены
localStorage.setItem('access_token', response.token)
localStorage.setItem('refresh_token', response.refreshToken)

// 3. Frontend делает аутентифицированный запрос
GET /api/tasks
Headers: {
  Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...
}

// 4. Access-токен истекает (через 30 мин)
GET /api/tasks
Response: 401 Unauthorized

// 5. Frontend автоматически обновляет токен
POST /api/token/refresh
Body: { "refreshToken": "550e8400-e29b-41d4-a716-446655440000" }

Response:
{
  "token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",  // Новый access-токен
  "refreshToken": "660e8400-e29b-41d4-a716-446655440000"  // Новый refresh-токен
}

// 6. Frontend повторяет оригинальный запрос с новым токеном
GET /api/tasks
Headers: {
  Authorization: Bearer <new_token>
}
Response: 200 OK
```

---

## Лучшие практики безопасности

### ДЕЛАТЬ ✅

✅ **Использовать RS256 (асимметричный)** - Безопаснее чем HS256
✅ **Короткое время жизни access-токена** - Максимум 30 минут
✅ **Длинное время жизни refresh-токена** - 7-30 дней
✅ **Ротация refresh-токенов** - Генерировать новый при каждом обновлении
✅ **Хранить refresh-токены в базе данных** - Можно отозвать
✅ **Валидировать Google JWT ключами Google** - Не доверять клиенту
✅ **Использовать только HTTPS** - Никогда не отправлять токены по HTTP
✅ **Использовать voters для сложной авторизации** - Не размещать логику в контроллерах

### НЕ ДЕЛАТЬ ❌

❌ **Хранить access-токены в cookies** - Уязвимость XSS
❌ **Использовать один токен для access и refresh** - Риск безопасности
❌ **Пропускать валидацию токена** - Всегда проверять подпись
❌ **Хардкодить секреты** - Использовать переменные окружения
❌ **Доверять клиентской валидации** - Всегда проверять на сервере
❌ **Раскрывать JWT secret** - Держать приватный ключ в секрете
❌ **Разрешать бесконечное обновление** - Реализовать максимальное количество обновлений

---

## Связанные документы

### Обязательно прочитать

- **[Архитектура](ARCHITECTURE.md)** - Как аутентификация интегрируется с сервисами
- **[API Reference](API_REFERENCE.md)** - Endpoints аутентификации

### Для справки

- **[Frontend API Integration](../frontend/API_INTEGRATION.md)** - Обработка токенов на frontend

---

*Последнее обновление: 2025-01-05*
*Версия аутентификации: 1.0*
