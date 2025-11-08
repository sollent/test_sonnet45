# 🔐 Phase 1.4: Security & Networking Configuration

> **Document Version**: 1.0.0
> **Last Updated**: 2025-11-08
> **Estimated Time**: 1 day
> **Complexity**: HIGH
> **Prerequisites**: Infrastructure deployed, Services running

## 📋 Table of Contents

1. [Security Architecture Overview](#security-architecture-overview)
2. [Network Security Configuration](#network-security-configuration)
3. [Authentication & Authorization](#authentication--authorization)
4. [Data Encryption](#data-encryption)
5. [API Security](#api-security)
6. [Container Security](#container-security)
7. [Monitoring & Auditing](#monitoring--auditing)
8. [Incident Response](#incident-response)

---

## 🏛️ Security Architecture Overview

### Defense in Depth Strategy

```yaml
Security Layers:
  1. Network Layer:
     - Firewall rules
     - Network segmentation
     - DDoS protection

  2. Application Layer:
     - JWT authentication
     - Rate limiting
     - Input validation

  3. Container Layer:
     - Rootless containers
     - Read-only filesystems
     - Resource limits

  4. Data Layer:
     - Encryption at rest
     - Encryption in transit
     - Key management

  5. Monitoring Layer:
     - Security events logging
     - Anomaly detection
     - Audit trails
```

### Threat Model

```yaml
Identified Threats:
  - Unauthorized API access
  - Voice command injection
  - LLM prompt injection
  - Data exfiltration
  - Container escape
  - Resource exhaustion
  - Man-in-the-middle attacks
  - Replay attacks

Mitigation Strategies:
  - Strong authentication
  - Input sanitization
  - Prompt validation
  - Network isolation
  - Container hardening
  - Rate limiting
  - TLS everywhere
  - Request signing
```

---

## 🌐 Network Security Configuration

### Firewall Rules Setup

```bash
#!/bin/bash
# File: ~/voice-ai-services/scripts/setup_firewall.sh

set -e

echo "🔥 Configuring Firewall Rules..."

# Enable UFW
sudo ufw --force enable

# Default policies
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw default deny routed

# Allow SSH (rate limited)
sudo ufw limit 22/tcp comment 'SSH rate limited'

# Allow HTTP/HTTPS
sudo ufw allow 80/tcp comment 'HTTP'
sudo ufw allow 443/tcp comment 'HTTPS'

# Allow Voice AI services (restricted to specific IPs in production)
sudo ufw allow 8089/tcp comment 'Backend API'
sudo ufw allow 8000/tcp comment 'Centrifugo WebSocket'

# Internal services (should be blocked from external access in production)
# sudo ufw deny 11434/tcp comment 'Ollama - internal only'
# sudo ufw deny 8090/tcp comment 'Whisper - internal only'
# sudo ufw deny 6379/tcp comment 'Redis - internal only'

# Docker specific rules
sudo ufw allow in on docker0
sudo ufw allow from 172.17.0.0/16 to any

# Apply rules
sudo ufw reload

echo "✅ Firewall configured successfully"
```

### Network Segmentation

```yaml
# File: ~/voice-ai-services/docker-compose.security.yml

version: '3.8'

networks:
  # DMZ network for external-facing services
  dmz_network:
    driver: bridge
    ipam:
      config:
        - subnet: 172.30.0.0/24
    driver_opts:
      com.docker.network.bridge.name: br-dmz
      com.docker.network.bridge.enable_ip_masquerade: "true"

  # Internal network for AI services
  ai_internal:
    driver: bridge
    internal: true  # No external access
    ipam:
      config:
        - subnet: 172.31.0.0/24
    driver_opts:
      com.docker.network.bridge.name: br-ai-internal

  # Data network for database access
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

  # Ollama (Internal only)
  ollama:
    image: ollama/ollama:latest
    networks:
      - ai_internal
    cap_drop:
      - ALL
    security_opt:
      - no-new-privileges:true

  # Whisper (Internal only)
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

### IPTables Advanced Rules

```bash
#!/bin/bash
# File: ~/voice-ai-services/scripts/advanced_iptables.sh

# Rate limiting for API endpoints
iptables -N RATE_LIMIT
iptables -A RATE_LIMIT -m recent --name api_rate --set
iptables -A RATE_LIMIT -m recent --name api_rate --update --seconds 1 --hitcount 100 -j DROP

# Apply rate limiting to API port
iptables -A INPUT -p tcp --dport 8089 -j RATE_LIMIT

# Prevent port scanning
iptables -N PORT_SCAN
iptables -A PORT_SCAN -p tcp --tcp-flags SYN,ACK,FIN,RST RST -m limit --limit 1/s -j RETURN
iptables -A PORT_SCAN -j DROP

# Block invalid packets
iptables -A INPUT -m state --state INVALID -j DROP
iptables -A INPUT -p tcp --tcp-flags ALL NONE -j DROP
iptables -A INPUT -p tcp --tcp-flags ALL ALL -j DROP

# Allow established connections
iptables -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT

# Log dropped packets
iptables -N LOGGING
iptables -A INPUT -j LOGGING
iptables -A LOGGING -m limit --limit 2/min -j LOG --log-prefix "IPTables-Dropped: "
iptables -A LOGGING -j DROP

# Save rules
iptables-save > /etc/iptables/rules.v4
```

---

## 🔑 Authentication & Authorization

### JWT Implementation with RSA

```php
<?php
// File: backend/src/Security/JWTManager.php

namespace App\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class JWTManager
{
    private string $privateKey;
    private string $publicKey;
    private string $algorithm = 'RS256';
    private int $ttl = 900; // 15 minutes
    private int $refreshTtl = 604800; // 7 days

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

### API Key Management

```php
<?php
// File: backend/src/Security/ApiKeyAuthenticator.php

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

        // Rate limiting per API key
        $limiter = $this->apiLimiter->create($apiKey);
        $limit = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            throw new AuthenticationException('Rate limit exceeded');
        }

        // Validate API key format
        if (!preg_match('/^[a-f0-9]{64}$/i', $apiKey)) {
            throw new AuthenticationException('Invalid API key format');
        }

        // Hash the API key for storage comparison
        $hashedKey = hash('sha256', $apiKey);

        $apiKeyEntity = $this->apiKeyRepository->findOneBy([
            'keyHash' => $hashedKey,
            'isActive' => true
        ]);

        if (!$apiKeyEntity) {
            throw new AuthenticationException('Invalid API key');
        }

        // Check expiration
        if ($apiKeyEntity->getExpiresAt() && $apiKeyEntity->getExpiresAt() < new \DateTime()) {
            throw new AuthenticationException('API key expired');
        }

        // Update last used
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

## 🔒 Data Encryption

### Encryption Service

```php
<?php
// File: backend/src/Security/EncryptionService.php

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
        // Load or generate encryption key
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
     * Encrypt sensitive data
     */
    public function encrypt(string $data): string
    {
        return Crypto::encrypt($data, $this->encryptionKey);
    }

    /**
     * Decrypt sensitive data
     */
    public function decrypt(string $encryptedData): string
    {
        return Crypto::decrypt($encryptedData, $this->encryptionKey);
    }

    /**
     * Encrypt file
     */
    public function encryptFile(string $inputPath, string $outputPath): void
    {
        File::encryptFile($inputPath, $outputPath, $this->encryptionKey);
    }

    /**
     * Decrypt file
     */
    public function decryptFile(string $inputPath, string $outputPath): void
    {
        File::decryptFile($inputPath, $outputPath, $this->encryptionKey);
    }

    /**
     * Encrypt voice recording
     */
    public function encryptVoiceRecording(string $audioPath): string
    {
        $encryptedPath = $audioPath . '.encrypted';
        $this->encryptFile($audioPath, $encryptedPath);

        // Delete original
        unlink($audioPath);

        return $encryptedPath;
    }

    /**
     * Generate secure random token
     */
    public function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash password with Argon2id
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
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Rotate encryption keys
     */
    public function rotateKeys(): void
    {
        $newKey = Key::createNewRandomKey();

        // Re-encrypt all sensitive data with new key
        // This would be done in batches in production

        $this->encryptionKey = $newKey;

        // Store new key securely
        // In production, use AWS KMS, HashiCorp Vault, etc.
    }
}
```

### Database Encryption

```yaml
# File: backend/config/packages/doctrine.yaml

doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'

        # Transparent encryption
        options:
            sslmode: require
            sslcert: '%kernel.project_dir%/config/ssl/client-cert.pem'
            sslkey: '%kernel.project_dir%/config/ssl/client-key.pem'
            sslrootcert: '%kernel.project_dir%/config/ssl/ca-cert.pem'

    orm:
        # Entity listeners for encryption
        entity_listeners:
            App\Entity\VoiceCommand:
                - App\EventListener\EncryptionListener
            App\Entity\User:
                - App\EventListener\PersonalDataEncryptionListener
```

---

## 🛡️ API Security

### Rate Limiting Configuration

```yaml
# File: backend/config/packages/rate_limiter.yaml

framework:
    rate_limiter:
        # Global API rate limit
        api:
            policy: sliding_window
            limit: 1000
            interval: '1 hour'

        # Voice command rate limit
        voice_command:
            policy: token_bucket
            limit: 100
            rate: { interval: '1 minute', amount: 10 }

        # Authentication attempts
        login:
            policy: fixed_window
            limit: 5
            interval: '5 minutes'

        # LLM requests (expensive)
        llm_request:
            policy: token_bucket
            limit: 50
            rate: { interval: '1 minute', amount: 2 }
```

### Input Validation & Sanitization

```php
<?php
// File: backend/src/Security/InputSanitizer.php

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
        '/(\$\{.*?\})/i',  // Template injection
        '/({{.*?}})/i',     // Template injection
    ];

    private array $sqlInjectionPatterns = [
        '/(\bUNION\b[\s\S]*?\bSELECT\b)/i',
        '/(\bDROP\b[\s\S]*?\bTABLE\b)/i',
        '/(\bINSERT\b[\s\S]*?\bINTO\b)/i',
        '/(\bDELETE\b[\s\S]*?\bFROM\b)/i',
        '/(\bUPDATE\b[\s\S]*?\bSET\b)/i',
        '/(--|\#|\/\*)/i',  // SQL comments
    ];

    private array $commandInjectionPatterns = [
        '/(\||&|;|`|\$\(|\))/i',
        '/(>|<|>>|<<)/i',
        '/(\bsudo\b|\bsu\b|\bchmod\b|\bchown\b)/i',
    ];

    public function __construct(private ValidatorInterface $validator) {}

    /**
     * Sanitize user input
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
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);

        // Strip tags
        $input = strip_tags($input);

        // Check dangerous patterns
        foreach ($this->dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                throw new \InvalidArgumentException('Potentially dangerous input detected');
            }
        }

        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function sanitizeHtml(string $input): string
    {
        // Use HTMLPurifier for robust HTML sanitization
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

        // Escape special characters
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
        // Remove directory traversal attempts
        $input = str_replace(['..', '/', '\\'], '', $input);

        // Allow only safe characters
        $input = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $input);

        // Limit length
        return substr($input, 0, 255);
    }

    private function sanitizeVoiceCommand(string $input): string
    {
        // Specific sanitization for voice commands

        // Remove potential prompt injection attempts
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

        // Limit length to prevent context overflow
        $input = mb_substr($input, 0, 1000);

        // Basic sanitization
        return $this->sanitizeGeneral($input);
    }

    /**
     * Validate and sanitize voice command for LLM
     */
    public function validateVoiceCommand(string $command): array
    {
        $sanitized = $this->sanitizeVoiceCommand($command);

        // Additional validation
        $errors = [];

        if (strlen($sanitized) < 3) {
            $errors[] = 'Command too short';
        }

        if (strlen($sanitized) > 500) {
            $errors[] = 'Command too long';
        }

        // Check for suspicious patterns
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

### CORS Configuration

```php
<?php
// File: backend/src/EventListener/CorsListener.php

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

        // Handle preflight requests
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

## 🐳 Container Security

### Dockerfile Security Best Practices

```dockerfile
# File: ~/voice-ai-services/configs/secure/Dockerfile.secure

# Use specific version, not latest
FROM python:3.11.6-slim-bookworm

# Set security labels
LABEL security.scan="enabled" \
      security.updates="auto" \
      maintainer="security@voiceai.com"

# Create non-root user
RUN groupadd -r voiceai && \
    useradd -r -g voiceai -u 1000 \
    -d /home/voiceai \
    -s /sbin/nologin \
    -c "Voice AI User" voiceai

# Install security updates only
RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        dumb-init && \
    rm -rf /var/lib/apt/lists/* && \
    rm -rf /tmp/*

# Copy and set ownership
WORKDIR /app
COPY --chown=voiceai:voiceai requirements.txt .

# Install Python packages as user
USER voiceai
RUN pip install --user --no-cache-dir \
    --no-compile \
    --disable-pip-version-check \
    -r requirements.txt

# Copy application files
COPY --chown=voiceai:voiceai . .

# Remove unnecessary files
RUN find . -name "*.pyc" -delete && \
    find . -name "__pycache__" -delete && \
    find . -name ".git" -delete

# Security hardening
USER root
RUN chmod -R 755 /app && \
    chown -R voiceai:voiceai /app

# Use dumb-init to handle signals properly
ENTRYPOINT ["/usr/bin/dumb-init", "--"]

# Run as non-root user
USER voiceai

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=60s --retries=3 \
    CMD python -c "import requests; requests.get('http://localhost:8090/health')"

# Run application
CMD ["python", "-m", "uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8090"]
```

### Container Runtime Security

```yaml
# File: ~/voice-ai-services/docker-compose.secure.yml

version: '3.8'

services:
  secure-whisper:
    image: voice-ai/whisper:secure
    container_name: secure-whisper

    # Security options
    security_opt:
      - no-new-privileges:true
      - apparmor:docker-default
      - seccomp:default.json

    # Capabilities
    cap_drop:
      - ALL
    cap_add:
      - NET_BIND_SERVICE

    # Read-only root filesystem
    read_only: true

    # Temporary filesystems for writable areas
    tmpfs:
      - /tmp:noexec,nosuid,size=100M
      - /var/run:noexec,nosuid,size=10M

    # User
    user: "1000:1000"

    # Resource limits
    deploy:
      resources:
        limits:
          cpus: '1'
          memory: 1G
          pids: 100
        reservations:
          cpus: '0.5'
          memory: 512M

    # Environment
    environment:
      - PYTHONDONTWRITEBYTECODE=1
      - PYTHONUNBUFFERED=1

    # Volumes (minimal, read-only where possible)
    volumes:
      - whisper-models:/models:ro
      - type: tmpfs
        target: /uploads
        tmpfs:
          size: 100M
```

### Security Scanning

```bash
#!/bin/bash
# File: ~/voice-ai-services/scripts/security_scan.sh

set -e

echo "🔍 Running Security Scans..."

# Scan Docker images for vulnerabilities
echo "1. Scanning Docker images with Trivy..."
for image in $(docker-compose images -q); do
    echo "Scanning $image..."
    docker run --rm -v /var/run/docker.sock:/var/run/docker.sock \
        aquasec/trivy image --severity HIGH,CRITICAL $image
done

# Check for secrets in code
echo ""
echo "2. Checking for secrets with GitLeaks..."
docker run --rm -v $(pwd):/path ghcr.io/gitleaks/gitleaks:latest \
    detect --source="/path" --verbose

# SAST scanning
echo ""
echo "3. Static analysis with Semgrep..."
docker run --rm -v $(pwd):/src \
    returntocorp/semgrep:latest \
    --config=auto /src

# Dependency check
echo ""
echo "4. Checking dependencies..."
pip-audit --fix --desc

# Container runtime security
echo ""
echo "5. Runtime security check with Falco..."
sudo falco -r /etc/falco/rules.d

echo "✅ Security scans completed"
```

---

## 📊 Monitoring & Auditing

### Security Event Logging

```php
<?php
// File: backend/src/Security/SecurityAuditLogger.php

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

        // Log to security channel
        $this->logger->info('Security event', $auditEntry);

        // Also write to audit file
        $this->writeAuditLog($auditEntry);

        // Send alerts for critical events
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
        // Send to monitoring system
        // Implementation depends on monitoring stack (Datadog, NewRelic, etc.)
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
            'command' => substr($command, 0, 100), // Log first 100 chars only
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

### Monitoring Dashboard Configuration

```yaml
# File: ~/voice-ai-services/configs/monitoring/security-dashboard.yml

dashboard:
  title: "Voice AI Security Dashboard"
  refresh: 30s

  panels:
    - title: "Authentication Metrics"
      type: graph
      metrics:
        - auth_success_rate
        - auth_failure_count
        - jwt_token_issued
        - refresh_token_used

    - title: "API Security"
      type: graph
      metrics:
        - api_rate_limit_exceeded
        - api_invalid_requests
        - api_response_time_p99

    - title: "Threat Detection"
      type: table
      metrics:
        - sql_injection_attempts
        - xss_attempts
        - prompt_injection_attempts
        - suspicious_patterns

    - title: "Container Security"
      type: graph
      metrics:
        - container_privilege_escalation
        - container_resource_violation
        - container_network_anomaly

  alerts:
    - name: "High Authentication Failure Rate"
      condition: "auth_failure_rate > 0.1"
      severity: warning

    - name: "Potential DDoS Attack"
      condition: "request_rate > 10000"
      severity: critical

    - name: "SQL Injection Detected"
      condition: "sql_injection_attempts > 0"
      severity: critical
```

---

## 🚨 Incident Response

### Incident Response Plan

```yaml
# File: ~/voice-ai-services/INCIDENT_RESPONSE.yml

incident_response_plan:

  detection:
    sources:
      - Security monitoring dashboards
      - Log analysis
      - User reports
      - Automated alerts

  classification:
    severity_levels:
      - P1: Critical (data breach, system compromise)
      - P2: High (service disruption, attempted breach)
      - P3: Medium (policy violation, suspicious activity)
      - P4: Low (minor security event)

  response_team:
    roles:
      - Incident Commander
      - Security Lead
      - DevOps Engineer
      - Communications Lead

  response_phases:
    1_identification:
      - Verify the incident
      - Determine scope
      - Classify severity

    2_containment:
      - Isolate affected systems
      - Preserve evidence
      - Prevent escalation

    3_eradication:
      - Remove threat
      - Patch vulnerabilities
      - Update security controls

    4_recovery:
      - Restore services
      - Monitor for recurrence
      - Validate security

    5_lessons_learned:
      - Document incident
      - Update procedures
      - Implement improvements

  communication:
    internal:
      - Incident team: Immediately
      - Management: Within 1 hour
      - All staff: As needed

    external:
      - Users: Within 24 hours if affected
      - Authorities: As required by law
      - Media: Through PR team only
```

### Automated Response Scripts

```bash
#!/bin/bash
# File: ~/voice-ai-services/scripts/incident_response.sh

set -e

INCIDENT_TYPE=$1
SEVERITY=$2

case $INCIDENT_TYPE in
    "breach")
        echo "🚨 SECURITY BREACH DETECTED"

        # 1. Isolate affected containers
        docker-compose stop

        # 2. Backup current state for forensics
        docker commit $(docker ps -aq) breach-snapshot-$(date +%s)

        # 3. Block all external traffic
        sudo iptables -I INPUT -j DROP
        sudo iptables -I OUTPUT -j DROP

        # 4. Preserve logs
        tar czf /secure/incident-logs-$(date +%s).tar.gz /var/log/

        # 5. Notify team
        curl -X POST https://hooks.slack.com/services/YOUR/WEBHOOK/URL \
            -H 'Content-Type: application/json' \
            -d '{"text":"🚨 CRITICAL: Security breach detected. Incident response activated."}'
        ;;

    "ddos")
        echo "🔥 DDoS ATTACK DETECTED"

        # Enable DDoS protection
        sudo iptables -N DDOS_PROTECT
        sudo iptables -A DDOS_PROTECT -m limit --limit 25/minute --limit-burst 100 -j ACCEPT
        sudo iptables -A DDOS_PROTECT -j DROP

        # Scale up rate limiting
        docker exec backend-php83 php bin/console app:rate-limit:emergency

        # Enable CloudFlare protection (if configured)
        curl -X PATCH "https://api.cloudflare.com/client/v4/zones/YOUR_ZONE/settings/security_level" \
            -H "Authorization: Bearer YOUR_TOKEN" \
            -H "Content-Type: application/json" \
            --data '{"value":"under_attack"}'
        ;;

    "injection")
        echo "⚠️ INJECTION ATTEMPT DETECTED"

        # Block offending IP
        ATTACKER_IP=$(grep "injection_attempt" /var/log/voice-ai/security.log | tail -1 | grep -oP '\d+\.\d+\.\d+\.\d+')
        sudo iptables -A INPUT -s $ATTACKER_IP -j DROP

        # Increase security level
        docker exec backend-php83 php bin/console app:security:level high

        # Review and patch
        ./scripts/security_scan.sh
        ;;

    *)
        echo "Unknown incident type: $INCIDENT_TYPE"
        exit 1
        ;;
esac

echo "✅ Incident response completed for $INCIDENT_TYPE"
```

---

## ✅ Security Checklist

### Pre-Deployment

- [ ] All secrets in environment variables or secret manager
- [ ] SSL/TLS certificates installed and valid
- [ ] Firewall rules configured and tested
- [ ] Container images scanned for vulnerabilities
- [ ] Dependencies updated to latest secure versions
- [ ] Security headers configured (CSP, HSTS, etc.)
- [ ] Rate limiting configured and tested
- [ ] Input validation implemented on all endpoints
- [ ] Encryption keys generated and secured
- [ ] Audit logging enabled

### Post-Deployment

- [ ] Security monitoring dashboard active
- [ ] Alerts configured and tested
- [ ] Incident response plan communicated
- [ ] Backup and recovery tested
- [ ] Penetration testing completed
- [ ] Security training provided to team
- [ ] Documentation updated
- [ ] Compliance requirements met

---

## 📚 Next Steps

1. ✅ Security configuration complete
2. → Proceed to [Backend Domain Model](../02_BACKEND/01_DOMAIN_MODEL.md)
3. → Implement [Service Layer](../02_BACKEND/02_SERVICES.md)
4. → Configure [API Endpoints](../02_BACKEND/04_API_ENDPOINTS.md)

---

**Document Status**: Complete
**Security Review**: Required before production
**Last Audit**: 2025-11-08
**Author**: Security Team