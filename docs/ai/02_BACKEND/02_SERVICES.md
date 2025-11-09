# 🚀 Phase 2.2: Service Layer Implementation Guide

> **For AI Assistant**: This is a step-by-step guide to implement the service layer. Follow each step in order. Adapt code to existing project structure.

## 📋 Quick Navigation

- **Previous**: [Domain Model](01_DOMAIN_MODEL.md) - Complete this first!
- **Next**: [Command Handlers](03_COMMAND_HANDLERS.md) - After services are ready
- **Project Root**: `backend/` directory

---

## 🎯 What We're Building

We need 5 core services for Voice AI Assistant MVP:

1. **VoiceProcessingService** - Handles audio/text commands
2. **LLMService** - Communicates with Ollama
3. **CommandExecutorService** - Executes parsed commands
4. **WebSocketPublisherService** - Sends real-time updates
5. **SmartSearchService** - Finds tasks by voice description

---

## 📝 Step-by-Step Implementation

### Step 1: Create Base Service Structure

**AI Assistant Action**: Create these directories first:

```bash
backend/src/Service/VoiceAssistant/
backend/src/Service/VoiceAssistant/Command/
backend/src/Service/VoiceAssistant/Command/Handlers/
backend/src/Service/VoiceAssistant/Integration/
```

### Step 2: VoiceProcessingService

**AI Assistant**: Create this service to handle incoming voice commands.

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

### Step 3: LLMService for Ollama Integration

**AI Assistant**: This service talks to Ollama. Adapt the URL based on your Docker setup.

```php
<?php
// File: apps/backend/src/Service/VoiceAssistant/LLMService.php

namespace App\Service\VoiceAssistant;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class LLMService
{
    private string $ollamaUrl = 'http://localhost:11434';  // Change if needed
    private string $model = 'llama3.2:3b';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

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
                ]
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
                'text' => $text
            ]);

            // Fallback to simple parsing
            return $this->fallbackParse($text);
        }
    }

    private function buildPrompt(string $text, array $context): string
    {
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
Current date: {$context['date']}
User timezone: {$context['timezone']}

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

### Step 4: CommandExecutorService

**AI Assistant**: This service executes parsed commands. It uses existing TaskService.

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

### Step 5: WebSocketPublisherService

**AI Assistant**: Sends real-time updates via Centrifugo.

```php
<?php
// File: apps/backend/src/Service/VoiceAssistant/WebSocketPublisherService.php

namespace App\Service\VoiceAssistant;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class WebSocketPublisherService
{
    private string $centrifugoUrl = 'http://localhost:8000';
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

### Step 6: SmartSearchService

**AI Assistant**: Finds tasks using fuzzy matching. Uses existing Task entity.

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

## 🔧 Service Configuration

### Step 7: Register Services in Symfony

**AI Assistant**: Add this to `backend/config/services.yaml`:

```yaml
services:
    # Voice Assistant Services
    App\Service\VoiceAssistant\VoiceProcessingService:
        arguments:
            $uploadsDir: '%kernel.project_dir%/var/uploads/voice'

    App\Service\VoiceAssistant\LLMService:
        arguments:
            $httpClient: '@http_client'

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

### Step 8: Add Environment Variables

**AI Assistant**: Add to `backend/.env`:

```env
# Voice AI Configuration
OLLAMA_URL=http://localhost:11434
WHISPER_URL=http://localhost:8090
CENTRIFUGO_URL=http://localhost:8000
CENTRIFUGO_API_KEY=your-api-key-here
```

---

## ✅ Testing Your Services

### Quick Test Script

**AI Assistant**: Create `backend/tests/VoiceServiceTest.php`:

```php
<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\VoiceAssistant\LLMService;
use App\Service\VoiceAssistant\SmartSearchService;

class VoiceServiceTest extends KernelTestCase
{
    public function testLLMService(): void
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

    public function testSmartSearch(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $searchService = $container->get(SmartSearchService::class);

        // This will work after you have test data
        // $task = $searchService->findTaskByDescription('купить молоко', $user);

        $this->assertTrue(true); // Placeholder
    }
}
```

Run test:
```bash
docker exec backend-php83 php bin/phpunit tests/VoiceServiceTest.php
```

---

## 🚨 Common Issues & Solutions

### Issue: Ollama connection refused
```php
// Change URL in LLMService if Docker networking differs
private string $ollamaUrl = 'http://host.docker.internal:11434';  // For Mac/Windows
// OR
private string $ollamaUrl = 'http://172.17.0.1:11434';  // For Linux
```

### Issue: Task not found by voice
```php
// In SmartSearchService, adjust similarity threshold:
'OR similarity(t.title, :description) > 0.2'  // Lower = more matches
```

### Issue: WebSocket not updating
```bash
# Check Centrifugo is running:
docker ps | grep centrifugo
# Check API key matches in .env
```

---

## 📊 Service Architecture Diagram

```
User Voice Input
      ↓
VoiceProcessingService
      ↓
   Queue
      ↓
LLMService (Ollama)
      ↓
CommandExecutorService
      ├── SmartSearchService (find tasks)
      └── TaskService (execute)
           ↓
WebSocketPublisherService → Frontend
```

---

## ✅ Checklist for AI Assistant

- [ ] Created all service directories
- [ ] Implemented VoiceProcessingService
- [ ] Implemented LLMService with Ollama
- [ ] Implemented CommandExecutorService
- [ ] Implemented WebSocketPublisherService
- [ ] Implemented SmartSearchService
- [ ] Added service configuration to services.yaml
- [ ] Added environment variables
- [ ] Tested at least one service

---

## 🎯 Next Steps

**Services are ready!** Now proceed to:

1. → [Command Handlers](03_COMMAND_HANDLERS.md) - Implement specific command logic
2. → [API Endpoints](04_API_ENDPOINTS.md) - Create REST endpoints
3. → [Queue Processing](05_QUEUE_PROCESSING.md) - Set up async processing

---

**Remember for AI**:
- Adapt code to existing project structure
- Use existing TaskService and Task entity
- Check Docker networking for service URLs
- Don't overthink - this is MVP, make it work first

---

**Document Status**: Simplified for AI Implementation
**Complexity**: Medium
**Time to Implement**: 2-3 hours