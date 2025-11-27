# 🚀 Фаза 2.2: Руководство по Реализации Слоя Сервисов

> **Версия**: 2.0.0
> **Дата**: 2025-11-27
> **Для AI Ассистента**: Это пошаговое руководство для реализации слоя сервисов. Следуй каждому шагу по порядку. Адаптируй код к существующей структуре проекта.

---

## ⚠️ КРИТИЧЕСКИ ВАЖНО: Нативная Установка AI

**AI сервисы (Ollama и Whisper) работают НАТИВНО на хосте, не в Docker!**

| Сервис | Хост URL | Docker URL |
|--------|----------|------------|
| **Ollama** | `http://localhost:11434` | `http://host.docker.internal:11434` |
| **Whisper** | `http://localhost:9001` | `http://host.docker.internal:9001` |

**Модели для Production:**
- **LLM**: `qwen2.5:14b-instruct-q4_K_M` (10-12 GB VRAM)
- **STT**: Whisper `large-v3` (3-4 GB VRAM)

---

## 📋 Быстрая Навигация

- **Предыдущее**: [Доменная Модель](01_DOMAIN_MODEL.md) - Завершите это сначала!
- **Следующее**: [Обработчики Команд](03_COMMAND_HANDLERS.md) - После готовности сервисов
- **Корень Проекта**: `backend/` директория

---

## 🎯 Что Мы Создаём

Нам нужно 5 основных сервисов для Voice AI Assistant MVP:

1. **VoiceProcessingService** - Обрабатывает аудио/текстовые команды
2. **LLMService** - Взаимодействует с Ollama (нативно на хосте)
3. **CommandExecutorService** - Выполняет распознанные команды
4. **WebSocketPublisherService** - Отправляет обновления в реальном времени
5. **SmartSearchService** - Находит задачи по голосовому описанию

---

## 📝 Пошаговая Реализация

### Шаг 1: Создать Базовую Структуру Сервисов

**Действие AI Ассистента**: Сначала создайте эти директории:

```bash
backend/src/Service/VoiceAssistant/
backend/src/Service/VoiceAssistant/Command/
backend/src/Service/VoiceAssistant/Command/Handlers/
backend/src/Service/VoiceAssistant/Integration/
```

### Шаг 2: VoiceProcessingService

**AI Ассистент**: Создайте этот сервис для обработки входящих голосовых команд.

```php
<?php
// File: apps/backend/src/Service/VoiceAssistant/VoiceProcessingService.php

namespace App\Service\VoiceAssistant;

use App\Entity\VoiceCommand;
use App\Entity\User;
use App\Repository\VoiceCommandRepository;
use App\ValueObject\CommandType;
use App\Message\ProcessVoiceCommandMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Psr\Log\LoggerInterface;

class VoiceProcessingService
{
    public function __construct(
        private VoiceCommandRepository $repository,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
        private string $uploadsDir  // Inject from parameters
    ) {}

    /**
     * Process audio file from user
     */
    public function processAudioCommand(
        UploadedFile $audioFile,
        User $user,
        string $source = 'web'
    ): VoiceCommand {
        // 1. Validate audio file
        $this->validateAudioFile($audioFile);

        // 2. Create VoiceCommand entity
        $command = new VoiceCommand($user, CommandType::AUDIO, $source);

        // 3. Save audio file
        $filename = sprintf('voice_%s_%s.%s',
            $user->getId()->toRfc4122(),
            time(),
            $audioFile->getClientOriginalExtension()
        );

        $audioFile->move($this->uploadsDir, $filename);
        $command->setAudioFile($this->uploadsDir . '/' . $filename);

        // 4. Save to database
        $this->repository->save($command, true);

        // 5. Send to queue for async processing
        $this->messageBus->dispatch(new ProcessVoiceCommandMessage($command->getId()));

        $this->logger->info('Voice command queued', [
            'command_id' => $command->getId()->toRfc4122(),
            'user_id' => $user->getId()->toRfc4122()
        ]);

        return $command;
    }

    /**
     * Process text command (from Telegram, API, etc)
     */
    public function processTextCommand(
        string $text,
        User $user,
        string $source = 'text'
    ): VoiceCommand {
        // 1. Validate text
        if (empty($text) || strlen($text) > 1000) {
            throw new \InvalidArgumentException('Invalid command text');
        }

        // 2. Create command
        $command = new VoiceCommand($user, CommandType::TEXT, $source);
        $command->setRawText($text);

        // 3. Save
        $this->repository->save($command, true);

        // 4. Process immediately (no STT needed)
        $this->messageBus->dispatch(new ProcessVoiceCommandMessage($command->getId()));

        return $command;
    }

    private function validateAudioFile(UploadedFile $file): void
    {
        $allowedMimes = ['audio/wav', 'audio/mpeg', 'audio/webm', 'audio/ogg'];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \InvalidArgumentException('Invalid audio format');
        }

        if ($file->getSize() > 10 * 1024 * 1024) { // 10MB
            throw new \InvalidArgumentException('Audio file too large');
        }
    }
}
```

### Шаг 3: LLMService для Интеграции с Ollama (Нативный)

**AI Ассистент**: Этот сервис взаимодействует с Ollama. **Ollama работает нативно на хосте!**

```php
<?php
// File: apps/backend/src/Service/VoiceAssistant/LLMService.php

namespace App\Service\VoiceAssistant;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class LLMService
{
    /**
     * ⚠️ ВАЖНО: Ollama работает НАТИВНО на хосте!
     * - Из Docker контейнера: http://host.docker.internal:11434
     * - Локально на хосте: http://localhost:11434
     */
    private string $ollamaUrl;
    private string $model = 'qwen2.5:14b-instruct-q4_K_M';  // Production модель

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $ollamaUrl = 'http://host.docker.internal:11434'  // Default для Docker
    ) {
        $this->ollamaUrl = $ollamaUrl;
    }

    /**
     * Parse voice command using LLM
     */
    public function parseCommand(string $text, array $context = []): array
    {
        $prompt = $this->buildPrompt($text, $context);

        try {
            $response = $this->httpClient->request('POST', $this->ollamaUrl . '/api/generate', [
                'json' => [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'format' => 'json',
                    'options' => [
                        'temperature' => 0.3,
                        'top_p' => 0.9,
                        'num_predict' => 500
                    ]
                ],
                'timeout' => 60  // LLM может думать долго
            ]);

            $result = $response->toArray();
            $llmResponse = $result['response'] ?? '{}';

            // Parse JSON from LLM
            $parsed = json_decode($llmResponse, true);

            if (!$parsed) {
                throw new \RuntimeException('Invalid JSON from LLM');
            }

            return $this->validateParsedCommand($parsed);

        } catch (\Exception $e) {
            $this->logger->error('LLM parsing failed', [
                'error' => $e->getMessage(),
                'text' => $text,
                'ollama_url' => $this->ollamaUrl
            ]);

            // Fallback to simple parsing
            return $this->fallbackParse($text);
        }
    }

    /**
     * Check if Ollama is available
     */
    public function healthCheck(): bool
    {
        try {
            $response = $this->httpClient->request('GET', $this->ollamaUrl . '/api/tags', [
                'timeout' => 5
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            $this->logger->warning('Ollama health check failed', [
                'error' => $e->getMessage(),
                'url' => $this->ollamaUrl
            ]);
            return false;
        }
    }

    private function buildPrompt(string $text, array $context): string
    {
        $date = $context['date'] ?? date('Y-m-d');
        $timezone = $context['timezone'] ?? 'UTC';

        $systemPrompt = <<<PROMPT
You are a task management assistant. Convert user commands to JSON.

Available actions:
- create_task: Create new task
- update_task: Update existing task
- complete_task: Mark task as done
- filter_tasks: Search/filter tasks
- create_subtask: Add subtask

Return ONLY valid JSON:
{
  "action": "action_name",
  "parameters": {},
  "confidence": 0.0-1.0
}

User command: "$text"

Context:
Current date: $date
User timezone: $timezone

JSON:
PROMPT;

        return $systemPrompt;
    }

    private function validateParsedCommand(array $parsed): array
    {
        // Ensure required fields
        $parsed['action'] = $parsed['action'] ?? 'unknown';
        $parsed['parameters'] = $parsed['parameters'] ?? [];
        $parsed['confidence'] = (float)($parsed['confidence'] ?? 0.5);

        // Validate action
        $validActions = ['create_task', 'update_task', 'complete_task', 'filter_tasks', 'create_subtask'];

        if (!in_array($parsed['action'], $validActions)) {
            $parsed['action'] = 'unknown';
            $parsed['confidence'] = 0.1;
        }

        return $parsed;
    }

    private function fallbackParse(string $text): array
    {
        // Simple keyword-based parsing as fallback
        $lower = mb_strtolower($text);

        if (str_contains($lower, 'создай') || str_contains($lower, 'добавь')) {
            return [
                'action' => 'create_task',
                'parameters' => ['title' => $text],
                'confidence' => 0.4
            ];
        }

        if (str_contains($lower, 'заверши') || str_contains($lower, 'готово')) {
            return [
                'action' => 'complete_task',
                'parameters' => ['search' => $text],
                'confidence' => 0.4
            ];
        }

        return [
            'action' => 'unknown',
            'parameters' => ['raw_text' => $text],
            'confidence' => 0.1
        ];
    }
}
```

### Шаг 4: WhisperService для Интеграции с faster-whisper-server

**AI Ассистент**: Этот сервис взаимодействует с Whisper. **Whisper работает нативно на хосте!**

```php
<?php
// File: apps/backend/src/Service/VoiceAssistant/WhisperService.php

namespace App\Service\VoiceAssistant;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class WhisperService
{
    /**
     * ⚠️ ВАЖНО: faster-whisper-server работает НАТИВНО на хосте!
     * - Из Docker контейнера: http://host.docker.internal:9001
     * - Локально на хосте: http://localhost:9001
     */
    private string $whisperUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $whisperUrl = 'http://host.docker.internal:9001'  // Default для Docker
    ) {
        $this->whisperUrl = $whisperUrl;
    }

    /**
     * Transcribe audio file to text
     */
    public function transcribe(string $audioFilePath): array
    {
        try {
            // OpenAI-совместимый API (faster-whisper-server)
            $response = $this->httpClient->request('POST', $this->whisperUrl . '/v1/audio/transcriptions', [
                'headers' => [
                    'Content-Type' => 'multipart/form-data',
                ],
                'body' => [
                    'file' => fopen($audioFilePath, 'r'),
                    'model' => 'large-v3',
                    'language' => 'ru',
                    'response_format' => 'json'
                ],
                'timeout' => 120  // STT может занять время
            ]);

            $result = $response->toArray();

            return [
                'text' => $result['text'] ?? '',
                'language' => $result['language'] ?? 'ru',
                'duration' => $result['duration'] ?? null
            ];

        } catch (\Exception $e) {
            $this->logger->error('Whisper transcription failed', [
                'error' => $e->getMessage(),
                'file' => $audioFilePath,
                'whisper_url' => $this->whisperUrl
            ]);

            throw new \RuntimeException('Speech-to-text failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if Whisper is available
     */
    public function healthCheck(): bool
    {
        try {
            $response = $this->httpClient->request('GET', $this->whisperUrl . '/health', [
                'timeout' => 5
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            $this->logger->warning('Whisper health check failed', [
                'error' => $e->getMessage(),
                'url' => $this->whisperUrl
            ]);
            return false;
        }
    }
}
```

### Шаг 5: CommandExecutorService

**AI Ассистент**: Этот сервис выполняет распознанные команды. Использует существующий TaskService.

```php
<?php
// File: apps/backend/src/Service/VoiceAssistant/CommandExecutorService.php

namespace App\Service\VoiceAssistant;

use App\Entity\VoiceCommand;
use App\Service\TaskService;  // Existing service
use App\Service\VoiceAssistant\Command\Handlers\CommandHandlerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Psr\Log\LoggerInterface;

class CommandExecutorService
{
    private array $handlers = [];

    public function __construct(
        private TaskService $taskService,
        private SmartSearchService $searchService,
        private LoggerInterface $logger,
        private ServiceLocator $handlerLocator  // For lazy loading
    ) {
        $this->registerHandlers();
    }

    private function registerHandlers(): void
    {
        // Map actions to handler service IDs
        $this->handlers = [
            'create_task' => 'voice.handler.create_task',
            'update_task' => 'voice.handler.update_task',
            'complete_task' => 'voice.handler.complete_task',
            'filter_tasks' => 'voice.handler.filter_tasks',
            'create_subtask' => 'voice.handler.create_subtask',
        ];
    }

    public function execute(VoiceCommand $command): array
    {
        $parsed = $command->getParsedCommand();

        if (!$parsed) {
            throw new \RuntimeException('No parsed command available');
        }

        $action = $parsed->getAction();
        $parameters = $parsed->getParameters();

        // Get appropriate handler
        $handler = $this->getHandler($action);

        if (!$handler) {
            // Use simple execution for MVP
            return $this->executeSimple($action, $parameters, $command->getUser());
        }

        // Execute via handler
        return $handler->handle($command);
    }

    private function executeSimple(string $action, array $params, $user): array
    {
        try {
            switch ($action) {
                case 'create_task':
                    $task = $this->taskService->createTask(
                        $user,
                        $params['title'] ?? 'New Task',
                        $params['description'] ?? null,
                        $params['due_date'] ?? null
                    );

                    return [
                        'success' => true,
                        'task_id' => $task->getId()->toRfc4122(),
                        'message' => 'Task created'
                    ];

                case 'complete_task':
                    // Find task by description
                    $task = $this->searchService->findTaskByDescription(
                        $params['search'] ?? $params['task_name'] ?? '',
                        $user
                    );

                    if ($task) {
                        $this->taskService->completeTask($task);
                        return [
                            'success' => true,
                            'task_id' => $task->getId()->toRfc4122(),
                            'message' => 'Task completed'
                        ];
                    }

                    return [
                        'success' => false,
                        'message' => 'Task not found'
                    ];

                default:
                    return [
                        'success' => false,
                        'message' => 'Unknown action'
                    ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Command execution failed', [
                'action' => $action,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Execution failed',
                'error' => $e->getMessage()
            ];
        }
    }

    private function getHandler(string $action): ?CommandHandlerInterface
    {
        if (!isset($this->handlers[$action])) {
            return null;
        }

        if ($this->handlerLocator->has($this->handlers[$action])) {
            return $this->handlerLocator->get($this->handlers[$action]);
        }

        return null;
    }
}
```

### Шаг 6: WebSocketPublisherService

**AI Ассистент**: Отправляет обновления в реальном времени через Centrifugo.

```php
<?php
// File: apps/backend/src/Service/VoiceAssistant/WebSocketPublisherService.php

namespace App\Service\VoiceAssistant;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class WebSocketPublisherService
{
    private string $centrifugoUrl = 'http://backend-centrifugo:8000';
    private string $apiKey;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $centrifugoApiKey  // From parameters
    ) {
        $this->apiKey = $centrifugoApiKey;
    }

    public function publishVoiceEvent(string $userId, string $event, array $data): void
    {
        $channel = "voice:user#$userId";

        try {
            $this->httpClient->request('POST', $this->centrifugoUrl . '/api/publish', [
                'headers' => [
                    'Authorization' => 'apikey ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'channel' => $channel,
                    'data' => [
                        'event' => $event,
                        'timestamp' => time(),
                        'data' => $data
                    ]
                ]
            ]);

            $this->logger->info('WebSocket event published', [
                'channel' => $channel,
                'event' => $event
            ]);

        } catch (\Exception $e) {
            // Don't fail the main process if WebSocket fails
            $this->logger->error('WebSocket publish failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sendCommandUpdate(
        string $userId,
        string $commandId,
        string $status,
        ?array $result = null
    ): void {
        $this->publishVoiceEvent($userId, "command.$status", [
            'command_id' => $commandId,
            'status' => $status,
            'result' => $result
        ]);
    }
}
```

### Шаг 7: SmartSearchService

**AI Ассистент**: Находит задачи используя нечеткое сопоставление. Использует существующую сущность Task.

```php
<?php
// File: apps/backend/src/Service/VoiceAssistant/SmartSearchService.php

namespace App\Service\VoiceAssistant;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;

class SmartSearchService
{
    public function __construct(
        private TaskRepository $taskRepository,
        private EntityManagerInterface $em
    ) {}

    /**
     * Find task by voice description
     */
    public function findTaskByDescription(string $description, User $user): ?Task
    {
        // 1. Try exact match
        $task = $this->taskRepository->findOneBy([
            'user' => $user,
            'title' => $description
        ]);

        if ($task) {
            return $task;
        }

        // 2. Try fuzzy search (PostgreSQL specific)
        $sql = "
            SELECT t.id
            FROM tasks t
            WHERE t.user_id = :user_id
            AND t.deleted_at IS NULL
            AND (
                LOWER(t.title) LIKE :search
                OR similarity(t.title, :description) > 0.3
            )
            ORDER BY similarity(t.title, :description) DESC
            LIMIT 1
        ";

        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addScalarResult('id', 'id');

        $query = $this->em->createNativeQuery($sql, $rsm);
        $query->setParameter('user_id', $user->getId(), 'uuid');
        $query->setParameter('description', $description);
        $query->setParameter('search', '%' . strtolower($description) . '%');

        $result = $query->getOneOrNullResult();

        if ($result) {
            return $this->taskRepository->find($result['id']);
        }

        return null;
    }

    /**
     * Find multiple similar tasks
     */
    public function findSimilarTasks(string $query, User $user, int $limit = 5): array
    {
        // Simple LIKE search for MVP
        return $this->taskRepository->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.title LIKE :query OR t.description LIKE :query')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
```

---

## 🔧 Конфигурация Сервисов

### Шаг 8: Зарегистрировать Сервисы в Symfony

**AI Ассистент**: Добавьте это в `backend/config/services.yaml`:

```yaml
services:
    # Voice Assistant Services
    App\Service\VoiceAssistant\VoiceProcessingService:
        arguments:
            $uploadsDir: '%kernel.project_dir%/var/uploads/voice'

    App\Service\VoiceAssistant\LLMService:
        arguments:
            $httpClient: '@http_client'
            # ⚠️ ВАЖНО: Ollama работает НАТИВНО на хосте!
            $ollamaUrl: '%env(OLLAMA_URL)%'

    App\Service\VoiceAssistant\WhisperService:
        arguments:
            $httpClient: '@http_client'
            # ⚠️ ВАЖНО: Whisper работает НАТИВНО на хосте!
            $whisperUrl: '%env(WHISPER_URL)%'

    App\Service\VoiceAssistant\CommandExecutorService:
        arguments:
            $handlerLocator: !service_locator
                voice.handler.create_task: '@App\Service\VoiceAssistant\Command\Handlers\CreateTaskHandler'
                voice.handler.complete_task: '@App\Service\VoiceAssistant\Command\Handlers\CompleteTaskHandler'

    App\Service\VoiceAssistant\WebSocketPublisherService:
        arguments:
            $centrifugoApiKey: '%env(CENTRIFUGO_API_KEY)%'

    App\Service\VoiceAssistant\SmartSearchService: ~
```

### Шаг 9: Добавить Переменные Окружения

**AI Ассистент**: Добавьте в `backend/.env`:

```env
###> Voice AI Configuration ###
# ⚠️ ВАЖНО: AI сервисы работают НАТИВНО на хосте!
# Из Docker контейнера используем host.docker.internal
OLLAMA_URL=http://host.docker.internal:11434
WHISPER_URL=http://host.docker.internal:9001

# Centrifugo (в Docker)
CENTRIFUGO_URL=http://backend-centrifugo:8000
CENTRIFUGO_API_KEY=your-api-key-here
###< Voice AI Configuration ###
```

**Для Linux хостов** (если host.docker.internal не работает):

```yaml
# В docker-compose.yml добавить для PHP контейнера:
services:
  php83-fpm:
    extra_hosts:
      - "host.docker.internal:host-gateway"
```

---

## ✅ Тестирование Ваших Сервисов

### Скрипт Проверки AI Сервисов

**AI Ассистент**: Создайте `backend/bin/check-ai-services.php`:

```php
#!/usr/bin/env php
<?php
/**
 * Проверка доступности AI сервисов (нативных на хосте)
 */

echo "=== AI Services Check ===\n\n";

// 1. Проверка Ollama
$ollamaUrl = getenv('OLLAMA_URL') ?: 'http://host.docker.internal:11434';
echo "Ollama URL: $ollamaUrl\n";
echo "Ollama: ";

$ch = curl_init($ollamaUrl . '/api/tags');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ OK\n";
    $data = json_decode($response, true);
    $models = array_column($data['models'] ?? [], 'name');
    echo "   Models: " . implode(', ', $models) . "\n";
} else {
    echo "❌ FAIL (HTTP $httpCode)\n";
}

// 2. Проверка Whisper
$whisperUrl = getenv('WHISPER_URL') ?: 'http://host.docker.internal:9001';
echo "\nWhisper URL: $whisperUrl\n";
echo "Whisper: ";

$ch = curl_init($whisperUrl . '/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ OK\n";
} else {
    echo "❌ FAIL (HTTP $httpCode)\n";
}

echo "\n=== Check Complete ===\n";
```

Запустите из Docker контейнера:
```bash
docker exec backend-php83 php bin/check-ai-services.php
```

### Unit Тест

**AI Ассистент**: Создайте `backend/tests/VoiceServiceTest.php`:

```php
<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\VoiceAssistant\LLMService;
use App\Service\VoiceAssistant\SmartSearchService;

class VoiceServiceTest extends KernelTestCase
{
    public function testLLMServiceHealthCheck(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $llmService = $container->get(LLMService::class);

        // Проверяем доступность Ollama
        $isAvailable = $llmService->healthCheck();

        $this->assertTrue($isAvailable, 'Ollama should be available on host');
    }

    public function testLLMServiceParseCommand(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $llmService = $container->get(LLMService::class);

        $result = $llmService->parseCommand('Создай задачу купить молоко', [
            'date' => date('Y-m-d'),
            'timezone' => 'UTC'
        ]);

        $this->assertArrayHasKey('action', $result);
        $this->assertArrayHasKey('parameters', $result);
        $this->assertArrayHasKey('confidence', $result);
    }
}
```

Запустите тест:
```bash
docker exec backend-php83 php bin/phpunit tests/VoiceServiceTest.php
```

---

## 🚨 Частые Проблемы и Решения

### Проблема: Отказ соединения с Ollama из Docker

```php
// ❌ Не работает из Docker контейнера
private string $ollamaUrl = 'http://localhost:11434';

// ✅ Правильно для Docker
private string $ollamaUrl = 'http://host.docker.internal:11434';

// ✅ Для Linux (добавить в docker-compose.yml)
extra_hosts:
  - "host.docker.internal:host-gateway"
```

### Проблема: Ollama не отвечает

```bash
# Проверить что Ollama запущен на хосте (не в Docker!)
# macOS:
brew services list | grep ollama
# или
ps aux | grep ollama

# Linux:
sudo systemctl status ollama

# Проверить порт
curl http://localhost:11434/api/tags
```

### Проблема: Whisper не отвечает

```bash
# Проверить что faster-whisper-server запущен на хосте
ps aux | grep faster-whisper

# Linux:
sudo systemctl status whisper-server

# Проверить порт
curl http://localhost:9001/health
```

### Проблема: Задача не найдена по голосу

```php
// В SmartSearchService, настройте порог схожести:
'OR similarity(t.title, :description) > 0.2'  // Ниже = больше совпадений
```

### Проблема: WebSocket не обновляется

```bash
# Проверьте что Centrifugo запущен (он в Docker):
docker ps | grep centrifugo
# Проверьте что API key совпадает в .env
```

---

## 📊 Диаграмма Архитектуры Сервисов

```
Голосовой Ввод Пользователя
      ↓
VoiceProcessingService
      ↓
   Очередь (RabbitMQ в Docker)
      ↓
WhisperService ─────────────────→ faster-whisper-server (НАТИВНО на хосте:9001)
      ↓
LLMService ─────────────────────→ Ollama (НАТИВНО на хосте:11434)
      ↓
CommandExecutorService
      ├── SmartSearchService (поиск задач в PostgreSQL)
      └── TaskService (выполнение)
           ↓
WebSocketPublisherService ──────→ Centrifugo (Docker) → Frontend
```

---

## ✅ Чеклист для AI Ассистента

- [ ] Созданы все директории сервисов
- [ ] Реализован VoiceProcessingService
- [ ] Реализован LLMService с подключением к **нативному** Ollama
- [ ] Реализован WhisperService с подключением к **нативному** faster-whisper-server
- [ ] Реализован CommandExecutorService
- [ ] Реализован WebSocketPublisherService
- [ ] Реализован SmartSearchService
- [ ] Добавлена конфигурация сервисов в services.yaml
- [ ] Добавлены переменные окружения с `host.docker.internal`
- [ ] Протестирован health check AI сервисов

---

## 🎯 Следующие Шаги

**Сервисы готовы!** Теперь переходите к:

1. → [Обработчики Команд](03_COMMAND_HANDLERS.md) - Реализовать специфическую логику команд
2. → [API Эндпоинты](04_API_ENDPOINTS.md) - Создать REST эндпоинты
3. → [Обработка Очереди](05_QUEUE_PROCESSING.md) - Настроить асинхронную обработку

---

## 🔗 Связанные Документы

- [NATIVE_INSTALLATION.md](../NATIVE_INSTALLATION.md) - Установка Ollama и Whisper нативно
- [01_SETUP.md](../01_INFRASTRUCTURE/01_SETUP.md) - Настройка инфраструктуры
- [03_AI_SERVICES.md](../01_INFRASTRUCTURE/03_AI_SERVICES.md) - Детали AI сервисов

---

**Помните для AI**:
- **AI сервисы (Ollama, Whisper) работают НАТИВНО на хосте!**
- Из Docker используйте `host.docker.internal` для доступа к AI
- Адаптируйте код к существующей структуре проекта
- Используйте существующие TaskService и сущность Task
- Не усложняйте - это MVP, сначала сделайте рабочим

---

**Статус Документа**: Обновлен для нативной установки AI
**Версия**: 2.0.0
**Сложность**: Средняя
**Время Реализации**: 2-3 часа
