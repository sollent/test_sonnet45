# 🔐 Фаза 1.4: Настройка Безопасности и Сети

> **Версия документа**: 1.0.0
> **Последнее обновление**: 2025-11-08
> **Оценочное время**: 1 день
> **Сложность**: ВЫСОКАЯ
> **Пререквизиты**: Развернута инфраструктура, Работают сервисы

## 📋 Содержание

1. [Обзор Архитектуры Безопасности](#обзор-архитектуры-безопасности)
2. [Настройка Сетевой Безопасности](#настройка-сетевой-безопасности)
3. [Аутентификация и Авторизация](#аутентификация-и-авторизация)
4. [Шифрование Данных](#шифрование-данных)
5. [Безопасность API](#безопасность-api)
6. [Безопасность Контейнеров](#безопасность-контейнеров)
7. [Мониторинг и Аудит](#мониторинг-и-аудит)
8. [Реагирование на Инциденты](#реагирование-на-инциденты)

---

## 🏛️ Обзор Архитектуры Безопасности

### Стратегия Эшелонированной Защиты

```yaml
Уровни Безопасности:
  1. Сетевой Уровень:
     - Правила файрвола
     - Сегментация сети
     - Защита от DDoS

  2. Уровень Приложения:
     - JWT аутентификация
     - Ограничение частоты запросов
     - Валидация ввода

  3. Уровень Контейнеров:
     - Контейнеры без root
     - Файловые системы только для чтения
     - Лимиты ресурсов

  4. Уровень Данных:
     - Шифрование в покое
     - Шифрование в транзите
     - Управление ключами

  5. Уровень Мониторинга:
     - Логирование событий безопасности
     - Обнаружение аномалий
     - Аудиторские следы
```

### Модель Угроз

```yaml
Выявленные Угрозы:
  - Несанкционированный доступ к API
  - Инъекции в голосовые команды
  - Инъекции промптов в LLM
  - Эксфильтрация данных
  - Побег из контейнера
  - Исчерпание ресурсов
  - Атаки Man-in-the-middle
  - Повторные атаки

Стратегии Митигации:
  - Строгая аутентификация
  - Санитизация ввода
  - Валидация промптов
  - Изоляция сети
  - Усиление контейнеров
  - Ограничение частоты запросов
  - TLS везде
  - Подписывание запросов
```

---

## 🌐 Настройка Сетевой Безопасности

### Настройка Правил Файрвола

```bash
#!/bin/bash
# Файл: infrastructure/ai-services/scripts/setup_firewall.sh

set -e

echo "🔥 Настройка Правил Файрвола..."

# Включение UFW
sudo ufw --force enable

# Политики по умолчанию
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw default deny routed

# Разрешить SSH (с ограничением частоты)
sudo ufw limit 22/tcp comment 'SSH rate limited'

# Разрешить HTTP/HTTPS
sudo ufw allow 80/tcp comment 'HTTP'
sudo ufw allow 443/tcp comment 'HTTPS'

# Разрешить Voice AI сервисы (ограничено конкретными IP в production)
sudo ufw allow 8089/tcp comment 'Backend API'
sudo ufw allow 8000/tcp comment 'Centrifugo WebSocket'

# Внутренние сервисы (должны быть заблокированы от внешнего доступа в production)
# sudo ufw deny 11434/tcp comment 'Ollama - internal only'
# sudo ufw deny 8090/tcp comment 'Whisper - internal only'
# sudo ufw deny 6379/tcp comment 'Redis - internal only'

# Docker специфичные правила
sudo ufw allow in on docker0
sudo ufw allow from 172.17.0.0/16 to any

# Применить правила
sudo ufw reload

echo "✅ Файрвол настроен успешно"
```

### Сегментация Сети

```yaml
# Файл: infrastructure/ai-services/docker-compose.security.yml

version: '3.8'

networks:
  # DMZ сеть для публичных сервисов
  dmz_network:
    driver: bridge
    ipam:
      config:
        - subnet: 172.30.0.0/24
    driver_opts:
      com.docker.network.bridge.name: br-dmz
      com.docker.network.bridge.enable_ip_masquerade: "true"

  # Внутренняя сеть для AI сервисов
  ai_internal:
    driver: bridge
    internal: true  # Без внешнего доступа
    ipam:
      config:
        - subnet: 172.31.0.0/24
    driver_opts:
      com.docker.network.bridge.name: br-ai-internal

  # Сеть данных для доступа к БД
  data_network:
    driver: bridge
    internal: true
    ipam:
      config:
        - subnet: 172.32.0.0/24
    driver_opts:
      com.docker.network.bridge.name: br-data

services:
  # API Gateway (DMZ)
  nginx-gateway:
    image: nginx:alpine
    networks:
      - dmz_network
      - ai_internal
    cap_drop:
      - ALL
    cap_add:
      - NET_BIND_SERVICE
    read_only: true
    tmpfs:
      - /var/cache/nginx
      - /var/run

  # Ollama (Только внутренняя)
  ollama:
    image: ollama/ollama:latest
    networks:
      - ai_internal
    cap_drop:
      - ALL
    security_opt:
      - no-new-privileges:true

  # Whisper (Только внутренняя)
  whisper:
    image: voice-ai/whisper:secure
    networks:
      - ai_internal
    cap_drop:
      - ALL
    security_opt:
      - no-new-privileges:true
      - seccomp:unconfined
```

### Расширенные Правила IPTables

```bash
#!/bin/bash
# Файл: infrastructure/ai-services/scripts/advanced_iptables.sh

# Ограничение частоты для API endpoints
iptables -N RATE_LIMIT
iptables -A RATE_LIMIT -m recent --name api_rate --set
iptables -A RATE_LIMIT -m recent --name api_rate --update --seconds 1 --hitcount 100 -j DROP

# Применить ограничение к API порту
iptables -A INPUT -p tcp --dport 8089 -j RATE_LIMIT

# Предотвращение сканирования портов
iptables -N PORT_SCAN
iptables -A PORT_SCAN -p tcp --tcp-flags SYN,ACK,FIN,RST RST -m limit --limit 1/s -j RETURN
iptables -A PORT_SCAN -j DROP

# Блокировка невалидных пакетов
iptables -A INPUT -m state --state INVALID -j DROP
iptables -A INPUT -p tcp --tcp-flags ALL NONE -j DROP
iptables -A INPUT -p tcp --tcp-flags ALL ALL -j DROP

# Разрешить установленные соединения
iptables -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT

# Логирование отброшенных пакетов
iptables -N LOGGING
iptables -A INPUT -j LOGGING
iptables -A LOGGING -m limit --limit 2/min -j LOG --log-prefix "IPTables-Dropped: "
iptables -A LOGGING -j DROP

# Сохранить правила
iptables-save > /etc/iptables/rules.v4
```

---

## 🔑 Аутентификация и Авторизация

### Реализация JWT с RSA

```php
<?php
// Файл: apps/backend/src/Security/JWTManager.php

namespace App\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class JWTManager
{
    private string $privateKey;
    private string $publicKey;
    private string $algorithm = 'RS256';
    private int $ttl = 900; // 15 минут
    private int $refreshTtl = 604800; // 7 дней

    public function __construct(ParameterBagInterface $params)
    {
        $this->privateKey = file_get_contents($params->get('jwt.private_key_path'));
        $this->publicKey = file_get_contents($params->get('jwt.public_key_path'));
    }

    public function createToken(array $payload): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->ttl;

        $token = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'nbf' => $issuedAt,
            'jti' => bin2hex(random_bytes(16)),
            'iss' => 'voice-ai-assistant',
            'aud' => 'voice-ai-client',
            ...$payload
        ];

        return JWT::encode($token, $this->privateKey, $this->algorithm);
    }

    public function createRefreshToken(string $userId): string
    {
        $token = [
            'sub' => $userId,
            'type' => 'refresh',
            'iat' => time(),
            'exp' => time() + $this->refreshTtl,
            'jti' => bin2hex(random_bytes(32))
        ];

        return JWT::encode($token, $this->privateKey, $this->algorithm);
    }

    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->publicKey, $this->algorithm));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function generateKeyPair(): void
    {
        $config = [
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $keyPair = openssl_pkey_new($config);

        openssl_pkey_export($keyPair, $privateKey);
        $publicKey = openssl_pkey_get_details($keyPair)['key'];

        file_put_contents('/keys/jwt_private.pem', $privateKey);
        file_put_contents('/keys/jwt_public.pem', $publicKey);

        chmod('/keys/jwt_private.pem', 0600);
        chmod('/keys/jwt_public.pem', 0644);
    }
}
```

### Управление API Ключами

```php
<?php
// Файл: apps/backend/src/Security/ApiKeyAuthenticator.php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use App\Repository\ApiKeyRepository;

class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private ApiKeyRepository $apiKeyRepository,
        private RateLimiterFactory $apiLimiter
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-API-Key');
    }

    public function authenticate(Request $request): Passport
    {
        $apiKey = $request->headers->get('X-API-Key');

        if (!$apiKey) {
            throw new AuthenticationException('No API key provided');
        }

        // Ограничение частоты для каждого API ключа
        $limiter = $this->apiLimiter->create($apiKey);
        $limit = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            throw new AuthenticationException('Rate limit exceeded');
        }

        // Валидация формата API ключа
        if (!preg_match('/^[a-f0-9]{64}$/i', $apiKey)) {
            throw new AuthenticationException('Invalid API key format');
        }

        // Хешируем API ключ для сравнения с хранилищем
        $hashedKey = hash('sha256', $apiKey);

        $apiKeyEntity = $this->apiKeyRepository->findOneBy([
            'keyHash' => $hashedKey,
            'isActive' => true
        ]);

        if (!$apiKeyEntity) {
            throw new AuthenticationException('Invalid API key');
        }

        // Проверка истечения срока
        if ($apiKeyEntity->getExpiresAt() && $apiKeyEntity->getExpiresAt() < new \DateTime()) {
            throw new AuthenticationException('API key expired');
        }

        // Обновление времени последнего использования
        $apiKeyEntity->setLastUsedAt(new \DateTime());
        $this->apiKeyRepository->save($apiKeyEntity, true);

        return new SelfValidatingPassport(
            new UserBadge($apiKeyEntity->getUser()->getId())
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response('Authentication Failed: ' . $exception->getMessage(), 401);
    }
}
```

---

## 🔒 Шифрование Данных

### Сервис Шифрования

```php
<?php
// Файл: apps/backend/src/Security/EncryptionService.php

namespace App\Security;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\File;

class EncryptionService
{
    private Key $encryptionKey;
    private string $kmsEndpoint;

    public function __construct(ParameterBagInterface $params)
    {
        // Загрузка или генерация ключа шифрования
        $keyPath = $params->get('encryption.key_path');

        if (file_exists($keyPath)) {
            $this->encryptionKey = Key::loadFromAsciiSafeString(
                file_get_contents($keyPath)
            );
        } else {
            $this->encryptionKey = Key::createNewRandomKey();
            file_put_contents(
                $keyPath,
                $this->encryptionKey->saveToAsciiSafeString()
            );
            chmod($keyPath, 0600);
        }

        $this->kmsEndpoint = $params->get('kms.endpoint');
    }

    /**
     * Шифрование чувствительных данных
     */
    public function encrypt(string $data): string
    {
        return Crypto::encrypt($data, $this->encryptionKey);
    }

    /**
     * Расшифровка чувствительных данных
     */
    public function decrypt(string $encryptedData): string
    {
        return Crypto::decrypt($encryptedData, $this->encryptionKey);
    }

    /**
     * Шифрование файла
     */
    public function encryptFile(string $inputPath, string $outputPath): void
    {
        File::encryptFile($inputPath, $outputPath, $this->encryptionKey);
    }

    /**
     * Расшифровка файла
     */
    public function decryptFile(string $inputPath, string $outputPath): void
    {
        File::decryptFile($inputPath, $outputPath, $this->encryptionKey);
    }

    /**
     * Шифрование голосовой записи
     */
    public function encryptVoiceRecording(string $audioPath): string
    {
        $encryptedPath = $audioPath . '.encrypted';
        $this->encryptFile($audioPath, $encryptedPath);

        // Удаление оригинала
        unlink($audioPath);

        return $encryptedPath;
    }

    /**
     * Генерация безопасного случайного токена
     */
    public function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Хеширование пароля с Argon2id
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }

    /**
     * Проверка пароля
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Ротация ключей шифрования
     */
    public function rotateKeys(): void
    {
        $newKey = Key::createNewRandomKey();

        // Перешифровать все чувствительные данные новым ключом
        // В production это делается пакетами

        $this->encryptionKey = $newKey;

        // Безопасное хранение нового ключа
        // В production используйте AWS KMS, HashiCorp Vault, и т.д.
    }
}
```

### Шифрование Базы Данных

```yaml
# Файл: apps/backend/config/packages/doctrine.yaml

doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'

        # Прозрачное шифрование
        options:
            sslmode: require
            sslcert: '%kernel.project_dir%/config/ssl/client-cert.pem'
            sslkey: '%kernel.project_dir%/config/ssl/client-key.pem'
            sslrootcert: '%kernel.project_dir%/config/ssl/ca-cert.pem'

    orm:
        # Слушатели сущностей для шифрования
        entity_listeners:
            App\Entity\VoiceCommand:
                - App\EventListener\EncryptionListener
            App\Entity\User:
                - App\EventListener\PersonalDataEncryptionListener
```

---

## 🛡️ Безопасность API

### Настройка Ограничения Частоты Запросов

```yaml
# Файл: apps/backend/config/packages/rate_limiter.yaml

framework:
    rate_limiter:
        # Глобальное ограничение API
        api:
            policy: sliding_window
            limit: 1000
            interval: '1 hour'

        # Ограничение для голосовых команд
        voice_command:
            policy: token_bucket
            limit: 100
            rate: { interval: '1 minute', amount: 10 }

        # Попытки аутентификации
        login:
            policy: fixed_window
            limit: 5
            interval: '5 minutes'

        # LLM запросы (дорогие)
        llm_request:
            policy: token_bucket
            limit: 50
            rate: { interval: '1 minute', amount: 2 }
```

### Валидация и Санитизация Ввода

```php
<?php
// Файл: apps/backend/src/Security/InputSanitizer.php

namespace App\Security;

use Symfony\Component\Validator\Validator\ValidatorInterface;

class InputSanitizer
{
    private array $dangerousPatterns = [
        '/(<script[\s\S]*?<\/script>)/i',
        '/(javascript:[\s\S]*?)/i',
        '/(on\w+\s*=)/i',
        '/(<iframe[\s\S]*?<\/iframe>)/i',
        '/(eval\s*\()/i',
        '/(exec\s*\()/i',
        '/(\$\{.*?\})/i',  // Инъекция шаблонов
        '/({{.*?}})/i',     // Инъекция шаблонов
    ];

    private array $sqlInjectionPatterns = [
        '/(\bUNION\b[\s\S]*?\bSELECT\b)/i',
        '/(\bDROP\b[\s\S]*?\bTABLE\b)/i',
        '/(\bINSERT\b[\s\S]*?\bINTO\b)/i',
        '/(\bDELETE\b[\s\S]*?\bFROM\b)/i',
        '/(\bUPDATE\b[\s\S]*?\bSET\b)/i',
        '/(--|\#|\/\*)/i',  // SQL комментарии
    ];

    private array $commandInjectionPatterns = [
        '/(\||&|;|`|\$\(|\))/i',
        '/(>|<|>>|<<)/i',
        '/(\bsudo\b|\bsu\b|\bchmod\b|\bchown\b)/i',
    ];

    public function __construct(private ValidatorInterface $validator) {}

    /**
     * Санитизация пользовательского ввода
     */
    public function sanitize(mixed $input, string $type = 'general'): mixed
    {
        if (is_array($input)) {
            return array_map(fn($item) => $this->sanitize($item, $type), $input);
        }

        if (!is_string($input)) {
            return $input;
        }

        return match($type) {
            'html' => $this->sanitizeHtml($input),
            'sql' => $this->sanitizeSql($input),
            'command' => $this->sanitizeCommand($input),
            'filename' => $this->sanitizeFilename($input),
            'voice_command' => $this->sanitizeVoiceCommand($input),
            default => $this->sanitizeGeneral($input)
        };
    }

    private function sanitizeGeneral(string $input): string
    {
        // Удаление нулевых байтов
        $input = str_replace(chr(0), '', $input);

        // Удаление тегов
        $input = strip_tags($input);

        // Проверка опасных паттернов
        foreach ($this->dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                throw new \InvalidArgumentException('Potentially dangerous input detected');
            }
        }

        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function sanitizeHtml(string $input): string
    {
        // Использование HTMLPurifier для надежной санитизации HTML
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,b,i,u,a[href],ul,ol,li');
        $config->set('URI.DisableExternalResources', true);

        $purifier = new \HTMLPurifier($config);
        return $purifier->purify($input);
    }

    private function sanitizeSql(string $input): string
    {
        foreach ($this->sqlInjectionPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                throw new \InvalidArgumentException('SQL injection attempt detected');
            }
        }

        // Экранирование специальных символов
        return addslashes($input);
    }

    private function sanitizeCommand(string $input): string
    {
        foreach ($this->commandInjectionPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                throw new \InvalidArgumentException('Command injection attempt detected');
            }
        }

        return escapeshellarg($input);
    }

    private function sanitizeFilename(string $input): string
    {
        // Удаление попыток обхода каталогов
        $input = str_replace(['..', '/', '\\'], '', $input);

        // Разрешены только безопасные символы
        $input = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $input);

        // Ограничение длины
        return substr($input, 0, 255);
    }

    private function sanitizeVoiceCommand(string $input): string
    {
        // Специфическая санитизация для голосовых команд

        // Удаление потенциальных попыток инъекции промптов
        $promptInjectionPatterns = [
            '/ignore previous instructions/i',
            '/disregard all prior commands/i',
            '/system:/i',
            '/\\n\\nHuman:/i',
            '/\\n\\nAssistant:/i',
        ];

        foreach ($promptInjectionPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                throw new \InvalidArgumentException('Prompt injection attempt detected');
            }
        }

        // Ограничение длины для предотвращения переполнения контекста
        $input = mb_substr($input, 0, 1000);

        // Базовая санитизация
        return $this->sanitizeGeneral($input);
    }

    /**
     * Валидация и санитизация голосовой команды для LLM
     */
    public function validateVoiceCommand(string $command): array
    {
        $sanitized = $this->sanitizeVoiceCommand($command);

        // Дополнительная валидация
        $errors = [];

        if (strlen($sanitized) < 3) {
            $errors[] = 'Command too short';
        }

        if (strlen($sanitized) > 500) {
            $errors[] = 'Command too long';
        }

        // Проверка подозрительных паттернов
        if (preg_match('/\b(hack|exploit|injection|bypass)\b/i', $sanitized)) {
            $errors[] = 'Suspicious content detected';
        }

        return [
            'valid' => empty($errors),
            'sanitized' => $sanitized,
            'errors' => $errors
        ];
    }
}
```

### Настройка CORS

```php
<?php
// Файл: apps/backend/src/EventListener/CorsListener.php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpFoundation\Response;

class CorsListener
{
    private array $allowedOrigins = [
        'http://localhost:3000',
        'https://app.yourdomain.com'
    ];

    private array $allowedMethods = [
        'GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'
    ];

    private array $allowedHeaders = [
        'Content-Type',
        'Authorization',
        'X-API-Key',
        'X-Request-ID'
    ];

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Обработка preflight запросов
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response();
            $this->addCorsHeaders($response, $request->headers->get('Origin'));
            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $origin = $request->headers->get('Origin');

        if ($origin && in_array($origin, $this->allowedOrigins, true)) {
            $this->addCorsHeaders($response, $origin);
        }
    }

    private function addCorsHeaders(Response $response, ?string $origin): void
    {
        if ($origin && in_array($origin, $this->allowedOrigins, true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }

        $response->headers->set('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
        $response->headers->set('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '3600');
    }
}
```

---

## 🐳 Безопасность Контейнеров

### Лучшие Практики Безопасности Dockerfile

```dockerfile
# Файл: infrastructure/ai-services/configs/secure/Dockerfile.secure

# Использовать конкретную версию, не latest
FROM python:3.11.6-slim-bookworm

# Установить метки безопасности
LABEL security.scan="enabled" \
      security.updates="auto" \
      maintainer="security@voiceai.com"

# Создать пользователя без root
RUN groupadd -r voiceai && \
    useradd -r -g voiceai -u 1000 \
    -d /home/voiceai \
    -s /sbin/nologin \
    -c "Voice AI User" voiceai

# Установить только обновления безопасности
RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        dumb-init && \
    rm -rf /var/lib/apt/lists/* && \
    rm -rf /tmp/*

# Копировать и установить владельца
WORKDIR /app
COPY --chown=voiceai:voiceai requirements.txt .

# Установить Python пакеты от пользователя
USER voiceai
RUN pip install --user --no-cache-dir \
    --no-compile \
    --disable-pip-version-check \
    -r requirements.txt

# Копировать файлы приложения
COPY --chown=voiceai:voiceai . .

# Удалить ненужные файлы
RUN find . -name "*.pyc" -delete && \
    find . -name "__pycache__" -delete && \
    find . -name ".git" -delete

# Усиление безопасности
USER root
RUN chmod -R 755 /app && \
    chown -R voiceai:voiceai /app

# Использовать dumb-init для правильной обработки сигналов
ENTRYPOINT ["/usr/bin/dumb-init", "--"]

# Запуск от имени пользователя без root
USER voiceai

# Проверка здоровья
HEALTHCHECK --interval=30s --timeout=3s --start-period=60s --retries=3 \
    CMD python -c "import requests; requests.get('http://localhost:8090/health')"

# Запуск приложения
CMD ["python", "-m", "uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8090"]
```

### Безопасность Времени Выполнения Контейнера

```yaml
# Файл: infrastructure/ai-services/docker-compose.secure.yml

version: '3.8'

services:
  secure-whisper:
    image: voice-ai/whisper:secure
    container_name: secure-whisper

    # Опции безопасности
    security_opt:
      - no-new-privileges:true
      - apparmor:docker-default
      - seccomp:default.json

    # Возможности
    cap_drop:
      - ALL
    cap_add:
      - NET_BIND_SERVICE

    # Корневая файловая система только для чтения
    read_only: true

    # Временные файловые системы для записываемых областей
    tmpfs:
      - /tmp:noexec,nosuid,size=100M
      - /var/run:noexec,nosuid,size=10M

    # Пользователь
    user: "1000:1000"

    # Лимиты ресурсов
    deploy:
      resources:
        limits:
          cpus: '1'
          memory: 1G
          pids: 100
        reservations:
          cpus: '0.5'
          memory: 512M

    # Окружение
    environment:
      - PYTHONDONTWRITEBYTECODE=1
      - PYTHONUNBUFFERED=1

    # Тома (минимальные, только для чтения где возможно)
    volumes:
      - whisper-models:/models:ro
      - type: tmpfs
        target: /uploads
        tmpfs:
          size: 100M
```

### Сканирование Безопасности

```bash
#!/bin/bash
# Файл: infrastructure/ai-services/scripts/security_scan.sh

set -e

echo "🔍 Запуск Сканирования Безопасности..."

# Сканирование Docker образов на уязвимости
echo "1. Сканирование Docker образов с Trivy..."
for image in $(docker-compose images -q); do
    echo "Сканирование $image..."
    docker run --rm -v /var/run/docker.sock:/var/run/docker.sock \
        aquasec/trivy image --severity HIGH,CRITICAL $image
done

# Проверка секретов в коде
echo ""
echo "2. Проверка секретов с GitLeaks..."
docker run --rm -v $(pwd):/path ghcr.io/gitleaks/gitleaks:latest \
    detect --source="/path" --verbose

# SAST сканирование
echo ""
echo "3. Статический анализ с Semgrep..."
docker run --rm -v $(pwd):/src \
    returntocorp/semgrep:latest \
    --config=auto /src

# Проверка зависимостей
echo ""
echo "4. Проверка зависимостей..."
pip-audit --fix --desc

# Безопасность времени выполнения контейнера
echo ""
echo "5. Проверка безопасности времени выполнения с Falco..."
sudo falco -r /etc/falco/rules.d

echo "✅ Сканирование безопасности завершено"
```

---

## 📊 Мониторинг и Аудит

### Логирование Событий Безопасности

```php
<?php
// Файл: apps/backend/src/Security/SecurityAuditLogger.php

namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;

class SecurityAuditLogger
{
    private LoggerInterface $logger;
    private string $auditLogPath;

    public function __construct(
        LoggerInterface $securityLogger,
        string $auditLogPath = '/var/log/voice-ai/audit.log'
    ) {
        $this->logger = $securityLogger;
        $this->auditLogPath = $auditLogPath;
    }

    public function logSecurityEvent(
        string $eventType,
        ?UserInterface $user,
        Request $request,
        array $context = []
    ): void {
        $auditEntry = [
            'timestamp' => (new \DateTime())->format('c'),
            'event_type' => $eventType,
            'user_id' => $user?->getUserIdentifier(),
            'ip_address' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'request_id' => $request->headers->get('X-Request-ID'),
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'context' => $context
        ];

        // Логирование в канал безопасности
        $this->logger->info('Security event', $auditEntry);

        // Также запись в файл аудита
        $this->writeAuditLog($auditEntry);

        // Отправка алертов для критических событий
        if ($this->isCriticalEvent($eventType)) {
            $this->sendSecurityAlert($auditEntry);
        }
    }

    private function writeAuditLog(array $entry): void
    {
        $line = json_encode($entry) . PHP_EOL;
        file_put_contents($this->auditLogPath, $line, FILE_APPEND | LOCK_EX);
    }

    private function isCriticalEvent(string $eventType): bool
    {
        $criticalEvents = [
            'authentication_failure_repeated',
            'sql_injection_attempt',
            'xss_attempt',
            'unauthorized_access',
            'privilege_escalation',
            'suspicious_activity'
        ];

        return in_array($eventType, $criticalEvents, true);
    }

    private function sendSecurityAlert(array $auditEntry): void
    {
        // Отправка в систему мониторинга
        // Реализация зависит от стека мониторинга (Datadog, NewRelic, и т.д.)
    }

    public function logAuthenticationSuccess(UserInterface $user, Request $request): void
    {
        $this->logSecurityEvent('authentication_success', $user, $request);
    }

    public function logAuthenticationFailure(string $username, Request $request, string $reason): void
    {
        $this->logSecurityEvent('authentication_failure', null, $request, [
            'username' => $username,
            'reason' => $reason
        ]);
    }

    public function logVoiceCommandExecution(
        UserInterface $user,
        Request $request,
        string $command,
        bool $success
    ): void {
        $this->logSecurityEvent('voice_command_execution', $user, $request, [
            'command' => substr($command, 0, 100), // Логируем только первые 100 символов
            'success' => $success
        ]);
    }

    public function logSuspiciousActivity(
        ?UserInterface $user,
        Request $request,
        string $description
    ): void {
        $this->logSecurityEvent('suspicious_activity', $user, $request, [
            'description' => $description
        ]);
    }
}
```

### Настройка Дашборда Мониторинга

```yaml
# Файл: infrastructure/ai-services/configs/monitoring/security-dashboard.yml

dashboard:
  title: "Voice AI Security Dashboard"
  refresh: 30s

  panels:
    - title: "Метрики Аутентификации"
      type: graph
      metrics:
        - auth_success_rate
        - auth_failure_count
        - jwt_token_issued
        - refresh_token_used

    - title: "Безопасность API"
      type: graph
      metrics:
        - api_rate_limit_exceeded
        - api_invalid_requests
        - api_response_time_p99

    - title: "Обнаружение Угроз"
      type: table
      metrics:
        - sql_injection_attempts
        - xss_attempts
        - prompt_injection_attempts
        - suspicious_patterns

    - title: "Безопасность Контейнеров"
      type: graph
      metrics:
        - container_privilege_escalation
        - container_resource_violation
        - container_network_anomaly

  alerts:
    - name: "Высокий Уровень Ошибок Аутентификации"
      condition: "auth_failure_rate > 0.1"
      severity: warning

    - name: "Возможная DDoS Атака"
      condition: "request_rate > 10000"
      severity: critical

    - name: "Обнаружена SQL Инъекция"
      condition: "sql_injection_attempts > 0"
      severity: critical
```

---

## 🚨 Реагирование на Инциденты

### План Реагирования на Инциденты

```yaml
# Файл: infrastructure/ai-services/INCIDENT_RESPONSE.yml

incident_response_plan:

  detection:
    sources:
      - Дашборды мониторинга безопасности
      - Анализ логов
      - Сообщения пользователей
      - Автоматические алерты

  classification:
    severity_levels:
      - P1: Критический (утечка данных, компрометация системы)
      - P2: Высокий (нарушение работы сервиса, попытка взлома)
      - P3: Средний (нарушение политики, подозрительная активность)
      - P4: Низкий (незначительное событие безопасности)

  response_team:
    roles:
      - Командир инцидента
      - Лидер безопасности
      - DevOps инженер
      - Лидер коммуникаций

  response_phases:
    1_identification:
      - Верификация инцидента
      - Определение масштаба
      - Классификация серьезности

    2_containment:
      - Изоляция затронутых систем
      - Сохранение доказательств
      - Предотвращение эскалации

    3_eradication:
      - Удаление угрозы
      - Закрытие уязвимостей
      - Обновление мер контроля

    4_recovery:
      - Восстановление сервисов
      - Мониторинг на повторение
      - Валидация безопасности

    5_lessons_learned:
      - Документирование инцидента
      - Обновление процедур
      - Реализация улучшений

  communication:
    internal:
      - Команда инцидента: Немедленно
      - Менеджмент: В течение 1 часа
      - Весь персонал: По необходимости

    external:
      - Пользователи: В течение 24 часов если затронуты
      - Власти: Как требуется по закону
      - Медиа: Только через PR команду
```

### Автоматизированные Скрипты Реагирования

```bash
#!/bin/bash
# Файл: infrastructure/ai-services/scripts/incident_response.sh

set -e

INCIDENT_TYPE=$1
SEVERITY=$2

case $INCIDENT_TYPE in
    "breach")
        echo "🚨 ОБНАРУЖЕНА УТЕЧКА БЕЗОПАСНОСТИ"

        # 1. Изолировать затронутые контейнеры
        docker-compose stop

        # 2. Сделать бекап текущего состояния для криминалистики
        docker commit $(docker ps -aq) breach-snapshot-$(date +%s)

        # 3. Заблокировать весь внешний трафик
        sudo iptables -I INPUT -j DROP
        sudo iptables -I OUTPUT -j DROP

        # 4. Сохранить логи
        tar czf /secure/incident-logs-$(date +%s).tar.gz /var/log/

        # 5. Уведомить команду
        curl -X POST https://hooks.slack.com/services/YOUR/WEBHOOK/URL \
            -H 'Content-Type: application/json' \
            -d '{"text":"🚨 КРИТИЧНО: Обнаружена утечка безопасности. Активировано реагирование на инцидент."}'
        ;;

    "ddos")
        echo "🔥 ОБНАРУЖЕНА DDoS АТАКА"

        # Включить защиту от DDoS
        sudo iptables -N DDOS_PROTECT
        sudo iptables -A DDOS_PROTECT -m limit --limit 25/minute --limit-burst 100 -j ACCEPT
        sudo iptables -A DDOS_PROTECT -j DROP

        # Усилить ограничение частоты запросов
        docker exec backend-php83 php bin/console app:rate-limit:emergency

        # Включить защиту CloudFlare (если настроено)
        curl -X PATCH "https://api.cloudflare.com/client/v4/zones/YOUR_ZONE/settings/security_level" \
            -H "Authorization: Bearer YOUR_TOKEN" \
            -H "Content-Type: application/json" \
            --data '{"value":"under_attack"}'
        ;;

    "injection")
        echo "⚠️ ОБНАРУЖЕНА ПОПЫТКА ИНЪЕКЦИИ"

        # Заблокировать атакующий IP
        ATTACKER_IP=$(grep "injection_attempt" /var/log/voice-ai/security.log | tail -1 | grep -oP '\d+\.\d+\.\d+\.\d+')
        sudo iptables -A INPUT -s $ATTACKER_IP -j DROP

        # Увеличить уровень безопасности
        docker exec backend-php83 php bin/console app:security:level high

        # Проверить и залатать
        ./scripts/security_scan.sh
        ;;

    *)
        echo "Неизвестный тип инцидента: $INCIDENT_TYPE"
        exit 1
        ;;
esac

echo "✅ Реагирование на инцидент завершено для $INCIDENT_TYPE"
```

---

## ✅ Чеклист Безопасности

### Перед Развертыванием

- [ ] Все секреты в переменных окружения или менеджере секретов
- [ ] SSL/TLS сертификаты установлены и валидны
- [ ] Правила файрвола настроены и протестированы
- [ ] Образы контейнеров просканированы на уязвимости
- [ ] Зависимости обновлены до последних безопасных версий
- [ ] Настроены заголовки безопасности (CSP, HSTS, и т.д.)
- [ ] Настроено и протестировано ограничение частоты запросов
- [ ] Реализована валидация ввода на всех endpoints
- [ ] Сгенерированы и защищены ключи шифрования
- [ ] Включено логирование аудита

### После Развертывания

- [ ] Активен дашборд мониторинга безопасности
- [ ] Настроены и протестированы алерты
- [ ] Доведен план реагирования на инциденты
- [ ] Протестированы бекап и восстановление
- [ ] Завершено тестирование на проникновение
- [ ] Проведено обучение команды по безопасности
- [ ] Обновлена документация
- [ ] Соблюдены требования комплаенса

---

## 📚 Следующие Шаги

1. ✅ Настройка безопасности завершена
2. → Перейти к [Доменной Модели Бэкенда](../02_BACKEND/01_DOMAIN_MODEL.md)
3. → Реализовать [Слой Сервисов](../02_BACKEND/02_SERVICES.md)
4. → Настроить [API Endpoints](../02_BACKEND/04_API_ENDPOINTS.md)

---

**Статус Документа**: Завершен
**Проверка Безопасности**: Требуется перед production
**Последний Аудит**: 2025-11-08
**Автор**: Команда Безопасности
