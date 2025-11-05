# 🔐 Authentication & Authorization

> **TL;DR**: JWT-based authentication with Google OAuth2 One Tap sign-in. Access tokens (30 min) + Refresh tokens (30 days). Security voters for authorization. RS256 asymmetric encryption for maximum security.

---

## Table of Contents

- [Overview](#overview)
- [JWT Token Flow](#jwt-token-flow)
- [Google OAuth2 Integration](#google-oauth2-integration)
- [Token Refresh Mechanism](#token-refresh-mechanism)
- [Security Voters](#security-voters)
- [Code Examples](#code-examples)

---

## Overview

### Authentication Strategy

```
┌─────────────────────────────────────────────────────────────┐
│                  AUTHENTICATION FLOW                        │
└─────────────────────────────────────────────────────────────┘

User clicks "Sign in with Google"
           │
           ▼
Google One Tap UI appears
           │
           ▼
User selects Google account
           │
           ▼
Google returns ID Token (JWT)
           │
           ▼
Frontend sends token to /api/auth/google
           │
           ▼
Backend validates with Google public keys
           │
           ▼
Backend finds/creates User in database
           │
           ▼
Backend generates Access Token (30 min)
           │
           ▼
Backend generates Refresh Token (30 days)
           │
           ▼
Frontend stores tokens in localStorage
           │
           ▼
User is authenticated!
```

---

## JWT Token Flow

### Token Types

#### 1. Access Token (30 minutes)

```json
{
  "iat": 1641024000,
  "exp": 1641025800,
  "roles": ["ROLE_USER"],
  "username": "user@example.com"
}
```

**Purpose:** Authenticate API requests
**Lifetime:** 30 minutes
**Storage:** `localStorage` (frontend)
**Usage:** Sent in `Authorization: Bearer <token>` header

#### 2. Refresh Token (30 days)

```json
{
  "token": "550e8400-e29b-41d4-a716-446655440000",
  "username": "user@example.com",
  "valid": "2025-02-05T00:00:00+00:00"
}
```

**Purpose:** Get new access token
**Lifetime:** 30 days
**Storage:** `refresh_tokens` table (database)
**Usage:** POST to `/api/token/refresh`

---

### Token Configuration

```yaml
# config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 1800  # 30 minutes
    user_identity_field: email
```

### Generate Keys (RS256)

```bash
# Generate private key
openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096

# Generate public key
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

# Set passphrase in .env
JWT_PASSPHRASE=your-secret-passphrase
```

---

## Google OAuth2 Integration

### Configuration

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
// Frontend: Load Google Sign-In library
<script src="https://accounts.google.com/gsi/client" async defer></script>

// Initialize One Tap
google.accounts.id.initialize({
  client_id: 'YOUR_GOOGLE_CLIENT_ID',
  callback: handleCredentialResponse
})

// Handle response
async function handleCredentialResponse(response: CredentialResponse) {
  const credential = response.credential  // JWT from Google

  // Send to backend
  const result = await axios.post('/api/auth/google', {
    credential
  })

  // Store tokens
  localStorage.setItem('access_token', result.data.token)
  localStorage.setItem('refresh_token', result.data.refreshToken)
}
```

### Backend (Token Validation)

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

    // ✅ Fetch Google's public keys
    $googleJwks = json_decode(
        file_get_contents('https://www.googleapis.com/oauth2/v3/certs'),
        true
    );

    // ✅ Decode and verify JWT with Google's keys
    $decoded = JWT::decode($credential, JWK::parseKeySet($googleJwks));

    $email = $decoded->email ?? null;

    if (!$email) {
        return $this->json(['error' => 'Invalid token'], 400);
    }

    // ✅ Find or create user
    $user = $googleAuthenticator->loadUserFromDecodedJwt($decoded);

    // ✅ Generate access token
    $token = $jwtManager->create($user);

    // ✅ Generate refresh token
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

        // ✅ Find existing user
        $user = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        // ✅ Create new user if not exists
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

## Token Refresh Mechanism

### Why Refresh Tokens?

**Problem:** Access tokens expire in 30 minutes
**Solution:** Use refresh token to get new access token without re-login

### Frontend Implementation

```typescript
// src/services/api.service.ts

class ApiService {
  private isRefreshing = false
  private failedQueue: Array<any> = []

  setupInterceptors() {
    // Response interceptor
    this.axiosInstance.interceptors.response.use(
      (response) => response,
      async (error: AxiosError) => {
        const originalRequest = error.config as any

        // If 401 and not already retried
        if (error.response?.status === 401 && !originalRequest._retry) {
          const refreshToken = localStorage.getItem('refresh_token')

          if (!refreshToken) {
            // No refresh token → redirect to login
            window.location.href = '/login'
            return Promise.reject(error)
          }

          // If already refreshing, queue request
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
            // ✅ Call refresh endpoint
            const { data } = await this.axiosInstance.post('/api/token/refresh', {
              refreshToken
            })

            // ✅ Save new tokens
            localStorage.setItem('access_token', data.token)
            localStorage.setItem('refresh_token', data.refreshToken)

            // ✅ Process queued requests
            this.processQueue(null, data.token)

            // ✅ Retry original request
            originalRequest.headers.Authorization = `Bearer ${data.token}`
            return this.axiosInstance.request(originalRequest)
          } catch (refreshError) {
            // ✅ Refresh failed → redirect to login
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

### Backend Refresh Endpoint

```php
<?php
// Handled by gesdinet/jwt-refresh-token-bundle

#[Route('/api/token/refresh', methods: ['POST'])]
public function refresh(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $refreshToken = $data['refreshToken'] ?? null;

    // ✅ Validate refresh token
    $token = $this->refreshTokenManager->get($refreshToken);

    if (!$token || !$token->isValid()) {
        return $this->json(['error' => 'Invalid refresh token'], 401);
    }

    // ✅ Load user
    $user = $this->userProvider->loadUserByIdentifier($token->getUsername());

    // ✅ Generate new access token
    $jwt = $this->jwtManager->create($user);

    // ✅ Generate new refresh token (rotation)
    $newRefreshToken = $this->refreshTokenManager->create();
    $newRefreshToken->setRefreshToken(Uuid::v4()->toRfc4122());
    $newRefreshToken->setUsername($user->getUserIdentifier());
    $newRefreshToken->setValid((new \DateTime())->modify('+30 days'));

    $this->refreshTokenManager->save($newRefreshToken);

    // ✅ Invalidate old refresh token
    $this->refreshTokenManager->delete($token);

    return $this->json([
        'token' => $jwt,
        'refreshToken' => $newRefreshToken->getRefreshToken(),
    ]);
}
```

---

## Security Voters

### What are Voters?

**Purpose:** Complex authorization logic (beyond simple roles)

**Example:** "Can user edit this task?"
- User must be authenticated ✓
- User must own the task ✓
- Task must not be archived ✓

### TaskVoter Implementation

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
        // Only vote on Task entities
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

        // User must be authenticated
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
        // ✅ User must own the task
        return $task->getUser() === $user;
    }

    private function canEdit(Task $task, User $user): bool
    {
        // ✅ User must own the task
        if ($task->getUser() !== $user) {
            return false;
        }

        // ✅ Task must not be archived
        if ($task->isArchived()) {
            return false;
        }

        return true;
    }

    private function canDelete(Task $task, User $user): bool
    {
        // ✅ User must own the task
        return $task->getUser() === $user;
    }
}
```

### Using Voters in Controllers

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

    // ✅ Check authorization with voter
    $this->denyAccessUnlessGranted('TASK_EDIT', $task);

    // If we reach here, user CAN edit task
    $updatedTask = $this->taskService->updateTask($task, $dto);

    return $this->json($updatedTask);
}
```

### Using Voters in Services

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
        // ✅ Check authorization
        if (!$this->security->isGranted('TASK_DELETE', $task)) {
            throw new AccessDeniedException('You cannot delete this task');
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();
    }
}
```

---

## Code Examples

### Complete Authentication Flow

```php
<?php
// 1. User signs in with Google
POST /api/auth/google
Body: { "credential": "eyJhbGc..." }

Response:
{
  "token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refreshToken": "550e8400-e29b-41d4-a716-446655440000",
  "refreshTokenExpiration": 1738790400
}

// 2. Frontend stores tokens
localStorage.setItem('access_token', response.token)
localStorage.setItem('refresh_token', response.refreshToken)

// 3. Frontend makes authenticated request
GET /api/tasks
Headers: {
  Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...
}

// 4. Access token expires (30 min later)
GET /api/tasks
Response: 401 Unauthorized

// 5. Frontend automatically refreshes token
POST /api/token/refresh
Body: { "refreshToken": "550e8400-e29b-41d4-a716-446655440000" }

Response:
{
  "token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",  // New access token
  "refreshToken": "660e8400-e29b-41d4-a716-446655440000"  // New refresh token
}

// 6. Frontend retries original request with new token
GET /api/tasks
Headers: {
  Authorization: Bearer <new_token>
}
Response: 200 OK
```

---

## Security Best Practices

### DO's ✅

✅ **Use RS256 (asymmetric)** - More secure than HS256
✅ **Short access token lifetime** - 30 minutes max
✅ **Long refresh token lifetime** - 7-30 days
✅ **Rotate refresh tokens** - Generate new on each refresh
✅ **Store refresh tokens in database** - Can be revoked
✅ **Validate Google JWT with Google keys** - Don't trust client
✅ **Use HTTPS only** - Never send tokens over HTTP
✅ **Implement voters for complex auth** - Don't put logic in controllers

### DON'Ts ❌

❌ **Store access tokens in cookies** - XSS vulnerability
❌ **Use same token for access and refresh** - Security risk
❌ **Skip token validation** - Always verify signature
❌ **Hardcode secrets** - Use environment variables
❌ **Trust client-side validation** - Always validate server-side
❌ **Expose JWT secret** - Keep private key private
❌ **Allow infinite refresh** - Implement max refresh count

---

## Related Documents

### Must Read Next
- **[Architecture](ARCHITECTURE.md)** - How auth integrates with services
- **[API Reference](API_REFERENCE.md)** - Auth endpoints

### For Reference
- **[Frontend API Integration](../frontend/API_INTEGRATION.md)** - Token handling in frontend

---

*Last updated: 2025-01-05*
*Authentication version: 1.0*
