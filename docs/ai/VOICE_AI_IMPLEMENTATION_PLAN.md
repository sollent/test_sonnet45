# 🎤 Voice AI Assistant - Implementation Plan

> **Quick Start Guide**: Simplified step-by-step plan based on complete MVP documentation analysis

---

## 📋 What We're Building

**Voice AI Assistant MVP** for Task Manager:
- User speaks Russian command → Whisper transcribes → Llama parses → Task created/updated
- Real-time WebSocket updates (Centrifugo)
- Web interface with voice recording button
- Async processing with RabbitMQ queue

**Tech Stack (Optimized for 4GB RAM VPS):**
- LLM: Llama 3.2 3B via Ollama (fits in 2-3GB memory)
- STT: Whisper base model (lightweight, accurate for Russian)
- WebSocket: Centrifugo + Redis
- Queue: RabbitMQ (already in project)
- Backend: Symfony 7.1 + PHP 8.3
- Frontend: Vue.js 3 + Composition API

---

## 🚀 Implementation Steps (5-Day MVP Plan)

### Step 0: Read Documentation First! ⭐

**CRITICAL - Start here (15 min):**
1. [`docs/ai/START_HERE.md`](START_HERE.md) - Quick overview + checklist
2. [`docs/ai/REFERENCE/PROMPTS_LIBRARY.md`](REFERENCE/PROMPTS_LIBRARY.md) - **⭐ MOST CRITICAL** - Copy exact prompts for Llama!
3. [`docs/ai/INDEX.md`](INDEX.md) - Complete navigation map

**Why PROMPTS_LIBRARY is critical:**
- Contains exact system prompts for Llama 3.2 3B
- Defines JSON response structure (action, parameters, confidence)
- Includes 7 tested command patterns in Russian
- Without these prompts, LLM will return garbage!

---

### Day 1: Backend Domain Layer (Morning, 2-3h)

**Goal:** Create database schema for voice commands

**Read first:**
- 📖 [`docs/ai/02_BACKEND/01_DOMAIN_MODEL.md`](02_BACKEND/01_DOMAIN_MODEL.md) - Complete entity specs

**Implementation order:**

**1. Value Objects (30 min)** - Simple enums and data structures:
```
backend/src/ValueObject/
├── CommandType.php           # Enum: VOICE_AUDIO, VOICE_TEXT
├── CommandStatus.php         # Enum: PENDING, PROCESSING, COMPLETED, FAILED
├── TranscriptionResult.php   # Data: text, language, confidence
└── ParsedCommand.php         # Data: action, parameters, confidence
```

**2. Main Entity (1h)** - VoiceCommand with state transitions:
```
backend/src/Entity/VoiceCommand.php
```

**Key points:**
- Use UUIDs (already in project)
- ManyToOne relation with User entity
- Status transitions: `pending → processing → executing → completed|failed`
- Store: rawText, transcription, parsedCommand, result, error

**3. Migration (30 min)**:
```bash
# Create migration
docker exec backend-php83 php bin/console make:migration

# Review SQL
docker exec backend-php83 cat backend/migrations/VersionXXX.php

# Apply
docker exec backend-php83 php bin/console doctrine:migrations:migrate --no-interaction
```

**Verification:**
```bash
# Check table created
docker exec backend-psql16 psql -U user -d backend-app -c "\d voice_commands"
```

---

### Day 1: Backend Services (Afternoon, 3-4h)

**Goal:** Implement 5 core services (SOLID architecture)

**Read first:**
- 📖 [`docs/ai/02_BACKEND/02_SERVICES.md`](02_BACKEND/02_SERVICES.md) - Service layer patterns
- ⭐ [`docs/ai/REFERENCE/PROMPTS_LIBRARY.md`](REFERENCE/PROMPTS_LIBRARY.md) - **Copy exact prompts!**

**Implementation order:**

**1. LLMService (1.5h)** - ⚠️ MOST IMPORTANT:
```php
backend/src/Service/VoiceAssistant/LLMService.php

Critical:
- Copy system prompt EXACTLY from PROMPTS_LIBRARY.md
- Use Ollama HTTP client (Symfony\Contracts\HttpClient\HttpClientInterface)
- Request: {"model": "llama3.2:3b", "prompt": "...", "format": "json", "options": {"temperature": 0.3}}
- Parse JSON response
- Validate structure: {action, parameters, confidence}
- Fallback parsing if JSON invalid
```

**2. SmartSearchService (45 min)** - Find tasks by fuzzy text:
```php
backend/src/Service/VoiceAssistant/SmartSearchService.php

Uses PostgreSQL trigram similarity:
- SELECT * FROM tasks WHERE similarity(title, ?) > 0.3
- Order by similarity DESC
- Requires pg_trgm extension (check if installed!)
```

**3. WebSocketPublisherService (30 min)** - Centrifugo integration:
```php
backend/src/Service/VoiceAssistant/WebSocketPublisherService.php

Publishes events to:
- Channel: "voice:user#{userId}"
- Events: command.received, transcribed, parsed, executing, completed, failed
```

**4. CommandExecutorService (45 min)** - Orchestrates handlers:
```php
backend/src/Service/VoiceAssistant/CommandExecutorService.php

Delegates to CommandHandlers (will create next)
```

**5. VoiceProcessingService (30 min)** - Entry point:
```php
backend/src/Service/VoiceAssistant/VoiceProcessingService.php

Simple coordinator, dispatches to queue
```

**Test Ollama first!**
```bash
# Verify Ollama running (should be installed separately - see Infrastructure docs)
curl http://localhost:11434/api/tags

# Test with Russian command
curl -X POST http://localhost:11434/api/generate -d '{
  "model": "llama3.2:3b",
  "prompt": "You are a task assistant. Convert this Russian command to JSON: Создай задачу купить молоко завтра",
  "format": "json",
  "stream": false
}'
```

---

### Day 2: Command Handlers (Morning, 2h)

**Goal:** Implement 3 core command handlers (MVP scope)

**Read first:**
- 📖 [`docs/ai/02_BACKEND/03_COMMAND_HANDLERS.md`](02_BACKEND/03_COMMAND_HANDLERS.md) - Handler pattern

**Implementation order:**

**1. Interface (15 min)**:
```php
backend/src/Service/VoiceAssistant/Command/CommandHandlerInterface.php

interface CommandHandlerInterface {
    public function supports(string $action): bool;
    public function handle(VoiceCommand $command): array;
}
```

**2. CreateTaskHandler (1h)** - Most common:
```php
backend/src/Service/VoiceAssistant/Command/Handlers/CreateTaskHandler.php

⚠️ CRITICAL: Use existing TaskService!
- supports(): return $action === 'create_task';
- handle(): Extract params, call taskService->createTask()
- Parameters: title (required), description, dueDate, priority, tags
- Returns: ['success' => true, 'task_id' => $id, 'message' => '...']
```

**3. CompleteTaskHandler (30 min)** - With smart search:
```php
backend/src/Service/VoiceAssistant/Command/Handlers/CompleteTaskHandler.php

Uses SmartSearchService to find task by description:
- Parse: task_id OR task_description
- If task_id: direct lookup
- If description: use SmartSearchService->findTask()
- Call taskService->completeTask($taskId)
```

**4. FilterTasksHandler (30 min)** - Query builder:
```php
backend/src/Service/VoiceAssistant/Command/Handlers/FilterTasksHandler.php

Build filter from parameters:
- status, priority, tags, date_from, date_to
- Use TaskRepository->findByFilters()
- Returns list of tasks (max 20)
```

**⚠️ DO NOT:**
```php
// ❌ Don't create new repository methods
$this->taskRepository->createTaskFromVoice($data);

// ✅ Use existing TaskService
$this->taskService->createTask($user, $title, $description);
```

---

### Day 2: API Endpoints (Afternoon, 1.5h)

**Goal:** Create 3 REST endpoints for voice commands

**Read first:**
- 📖 [`docs/ai/02_BACKEND/04_API_ENDPOINTS.md`](02_BACKEND/04_API_ENDPOINTS.md) - API specs

**Implementation:**

**1. Controller (1h)**:
```php
backend/src/Controller/VoiceCommandController.php

#[Route('/api/voice', name: 'api_voice_')]
class VoiceCommandController extends AbstractController
{
    // POST /api/voice/command - Submit audio or text
    #[Route('/command', name: 'submit', methods: ['POST'])]
    public function submitCommand(Request $request): JsonResponse
    {
        // Accept: audio file OR text
        // Create VoiceCommand entity
        // Dispatch to queue (returns 202 Accepted)
        // Return: {command_id, status: 'queued'}
    }

    // GET /api/voice/command/{id} - Get status
    #[Route('/command/{id}', name: 'status', methods: ['GET'])]
    public function getStatus(string $id): JsonResponse
    {
        // Fetch VoiceCommand by UUID
        // Return: {id, status, result, created_at}
    }

    // GET /api/voice/history - Get recent commands
    #[Route('/history', name: 'history', methods: ['GET'])]
    public function getHistory(Request $request): JsonResponse
    {
        // Query: ?days=7&limit=20
        // Return user's command history
    }
}
```

**2. Route Configuration (auto-discovered by Symfony)**

**3. Test with curl (30 min)**:
```bash
# Get JWT token first
TOKEN=$(curl -X POST http://localhost:8089/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"test123"}' \
  | jq -r '.token')

# Test text command
curl -X POST http://localhost:8089/api/voice/command \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"text":"Создай задачу купить молоко","source":"web"}' | jq

# Check status
COMMAND_ID="..." # from previous response
curl -X GET "http://localhost:8089/api/voice/command/$COMMAND_ID" \
  -H "Authorization: Bearer $TOKEN" | jq

# Get history
curl -X GET "http://localhost:8089/api/voice/history?days=7" \
  -H "Authorization: Bearer $TOKEN" | jq
```

---

### Day 3: Queue Processing (Morning, 1.5h)

**Goal:** Async voice command processing with RabbitMQ

**Read first:**
- 📖 [`docs/ai/02_BACKEND/05_QUEUE_PROCESSING.md`](02_BACKEND/05_QUEUE_PROCESSING.md) - Queue setup

**Implementation:**

**1. Message class (10 min)**:
```php
backend/src/Message/ProcessVoiceCommandMessage.php

Simple DTO:
class ProcessVoiceCommandMessage {
    public function __construct(private string $commandId) {}
    public function getCommandId(): string { return $this->commandId; }
}
```

**2. Message Handler (1h)** - Main processing logic:
```php
backend/src/MessageHandler/ProcessVoiceCommandHandler.php

#[AsMessageHandler]
class ProcessVoiceCommandHandler {
    public function __invoke(ProcessVoiceCommandMessage $message): void {
        // 1. Fetch VoiceCommand by ID
        // 2. Start processing
        // 3. Transcribe if audio (call Whisper HTTP API)
        // 4. Parse with LLM (call LLMService)
        // 5. Execute command (call CommandExecutorService)
        // 6. Mark complete
        // 7. Send WebSocket updates at each step
        // 8. Handle errors gracefully
    }
}
```

**3. Configure Messenger (20 min)**:
```yaml
# backend/config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    exchange: {name: messages, type: direct}
                    queues: {messages: ~}
            failed: 'doctrine://default?queue_name=failed'
        routing:
            'App\Message\ProcessVoiceCommandMessage': async

# .env
MESSENGER_TRANSPORT_DSN=amqp://user:password@localhost:5672/%2f/messages
WHISPER_URL=http://localhost:8090
OLLAMA_URL=http://localhost:11434
```

**4. Start worker:**
```bash
# Start consumer
docker exec backend-php83 php bin/console messenger:consume async -vv

# Check queue health
docker exec backend-rabbitmq rabbitmqctl list_queues

# Check logs
docker logs -f backend-php83 | grep "ProcessVoiceCommandHandler"
```

---

### Day 3-4: Frontend Voice UI (Afternoon + Morning, 3h)

**Goal:** Voice recording button with real-time updates

**Read first:**
- 📖 [`docs/ai/03_FRONTEND/01_VOICE_RECORDING.md`](03_FRONTEND/01_VOICE_RECORDING.md) - Complete implementation

**Implementation order:**

**1. Voice Command Service (30 min)**:
```typescript
frontend/src/services/voiceCommand.service.ts

class VoiceCommandService {
    async submitAudioCommand(audioBlob: Blob): Promise<string> {
        // FormData with audio file
        // POST /api/voice/command
        // Returns command_id
    }

    async submitTextCommand(text: string): Promise<string> {
        // JSON with text
        // POST /api/voice/command
    }

    async getCommandStatus(commandId: string) {
        // GET /api/voice/command/{id}
    }
}
```

**2. Voice Recording Composable (45 min)**:
```typescript
frontend/src/composables/useVoiceRecording.ts

export function useVoiceRecording() {
    // Uses MediaRecorder API
    // Recording states: idle, recording, processing
    // startRecording(), stopRecording(), sendTextCommand()
    // Returns: {isRecording, isProcessing, error, ...}
}
```

**3. WebSocket Composable (45 min)**:
```typescript
frontend/src/composables/useWebSocket.ts

export function useWebSocket() {
    // Uses Centrifuge client library
    // Subscribe to: "voice:user#{userId}"
    // Listen for events: transcribed, parsed, completed, failed
    // emit() events to component
}
```

**4. Voice Button Component (45 min)**:
```vue
frontend/src/components/VoiceAssistant/VoiceButton.vue

<template>
  <button @click="toggleRecording" :class="btnClass">
    <i v-if="!isRecording && !isProcessing" class="pi pi-microphone"></i>
    <i v-if="isRecording" class="pi pi-stop-circle"></i>
    <i v-if="isProcessing" class="pi pi-spin pi-spinner"></i>
  </button>
  <div v-if="statusText">{{ statusText }}</div>
  <div v-if="error" class="error">{{ error }}</div>
</template>

<script setup lang="ts">
import { useVoiceRecording } from '@/composables/useVoiceRecording'
import { useWebSocket } from '@/composables/useWebSocket'

const { isRecording, isProcessing, error, startRecording, stopRecording } = useVoiceRecording()
const { onVoiceEvent } = useWebSocket()

onVoiceEvent('completed', (data) => {
    // Show success message
})
</script>
```

**5. Add to main view (15 min)**:
```vue
<!-- In your tasks view -->
<VoiceButton />
```

**Test in browser (Chrome/Firefox):**
1. Open dev console (F12)
2. Click microphone → Allow permission
3. Say: "Создай задачу купить молоко"
4. Click stop
5. Watch console for WebSocket events
6. Check task list for new task

---

### Day 4-5: Infrastructure Setup (Afternoon + Full Day, 4h)

**Goal:** Install and configure AI services (Ollama, Whisper, Centrifugo)

**Read first:**
- 📖 [`docs/ai/01_INFRASTRUCTURE/01_SETUP.md`](01_INFRASTRUCTURE/01_SETUP.md) - System requirements
- 📖 [`docs/ai/01_INFRASTRUCTURE/03_AI_SERVICES.md`](01_INFRASTRUCTURE/03_AI_SERVICES.md) - ⚠️ CRITICAL - Complete setup guide

**Setup Order:**

**1. Ollama + Llama 3.2 (1h)**:
```bash
# Install Ollama
curl -fsSL https://ollama.com/install.sh | sh

# Pull Llama 3.2 3B model (~ 2GB download)
ollama pull llama3.2:3b

# Start Ollama server
ollama serve

# Test
curl http://localhost:11434/api/tags
# Should show: {"models": [{"name": "llama3.2:3b", ...}]}

# Test Russian command
curl -X POST http://localhost:11434/api/generate -d '{
  "model": "llama3.2:3b",
  "prompt": "Convert to JSON: Создай задачу купить молоко",
  "format": "json",
  "stream": false
}'
```

**2. Whisper STT Service (2h)**:

**Option A - Using Docker (Recommended)**:
```bash
# Build Whisper container
cd ~/voice-ai-services
docker build -f configs/whisper/Dockerfile -t voice-ai/whisper:latest .

# Run Whisper service
docker run -d \
  --name voice-ai-whisper \
  -p 8090:8090 \
  -e MODEL_SIZE=base \
  -e LANGUAGE=ru \
  voice-ai/whisper:latest

# Test
curl http://localhost:8090/health
```

**Option B - Direct Python**:
```bash
# Install dependencies
pip install openai-whisper fastapi uvicorn python-multipart

# Copy whisper_api.py from docs/ai/01_INFRASTRUCTURE/03_AI_SERVICES.md

# Run
uvicorn whisper_api:app --host 0.0.0.0 --port 8090
```

**3. Centrifugo WebSocket (1h)**:
```bash
# Generate secrets
CENTRIFUGO_SECRET=$(openssl rand -hex 32)
CENTRIFUGO_API_KEY=$(openssl rand -hex 32)

# Create config
cat > centrifugo.json <<EOF
{
  "token_hmac_secret_key": "$CENTRIFUGO_SECRET",
  "api_key": "$CENTRIFUGO_API_KEY",
  "allowed_origins": ["http://localhost:3000", "http://localhost:8089"],
  "namespaces": [{
    "name": "voice",
    "history_size": 50,
    "history_ttl": "300s"
  }]
}
EOF

# Run Centrifugo
docker run -d \
  --name voice-ai-centrifugo \
  -p 8000:8000 \
  -v $(pwd)/centrifugo.json:/centrifugo/config.json \
  centrifugo/centrifugo:v5 \
  centrifugo -c /centrifugo/config.json

# Test
curl http://localhost:8000/health
```

**4. Integration Test (30 min)**:
```bash
# Test full flow
python3 scripts/integration_test.py

# Should show:
# ✅ Ollama is running
# ✅ Whisper is healthy
# ✅ Centrifugo is healthy
```

**Add to .env:**
```env
OLLAMA_URL=http://localhost:11434
WHISPER_URL=http://localhost:8090
CENTRIFUGO_URL=http://localhost:8000
CENTRIFUGO_API_KEY=<your-api-key>
CENTRIFUGO_SECRET=<your-secret>
```

---

## 🎯 MVP Success Criteria

**Your MVP is DONE when all these work:**

1. ✅ User clicks voice button → recording starts
2. ✅ User speaks Russian command → stops recording
3. ✅ Audio sent to backend → queued (202 response)
4. ✅ Whisper transcribes audio to text
5. ✅ Llama parses text to JSON (using prompts from library!)
6. ✅ Command executed (task created/completed/filtered)
7. ✅ WebSocket sends real-time updates
8. ✅ User sees result in UI (< 5 seconds total)
9. ✅ Works reliably for 3 core commands:
   - "Создай задачу купить молоко завтра"
   - "Отметь задачу купить молоко как выполненную"
   - "Покажи все задачи на завтра"

---

## ⚠️ Critical Things to Remember

### 1. ⭐ ALWAYS Use Exact Prompts from Library
**Location:** [`docs/ai/REFERENCE/PROMPTS_LIBRARY.md`](REFERENCE/PROMPTS_LIBRARY.md)

**Why critical:** Without exact prompts, Llama will return garbage or incorrect JSON!

```php
// ❌ DON'T create your own prompts
private function buildPrompt(string $text): string {
    return "Parse this command: " . $text; // WILL NOT WORK!
}

// ✅ DO copy EXACT prompt from PROMPTS_LIBRARY.md
private function buildPrompt(string $text, array $context): string {
    $date = $context['date'] ?? date('Y-m-d');
    $time = $context['time'] ?? date('H:i');

    return <<<PROMPT
Ты - ассистент для управления задачами для русскоязычных пользователей.
Твоя задача: конвертировать голосовые команды в валидный JSON.

ВАЖНЫЕ ПРАВИЛА:
1. ВСЕГДА возвращай ТОЛЬКО валидный JSON (никакого дополнительного текста!)
2. Понимай команды на русском языке
3. Извлекай: действие (action), параметры (parameters), уверенность (confidence)
4. Текущая дата: {$date}, время: {$time}

[... full prompt from library - 50+ lines ...]
PROMPT;
}
```

### 2. Keep It Simple - Use Existing Services
```php
// ❌ DON'T create new repository methods
$this->taskRepository->findByVoiceDescription($text); // NO!
$this->taskRepository->createFromVoiceCommand($data); // NO!

// ✅ DO use existing services
$task = $this->smartSearchService->findTask($text, $user);
$task = $this->taskService->createTask($user, $title, $description);
```

### 3. Don't Bypass TaskService
```php
// ❌ DON'T bypass TaskService
$task = new Task();
$task->setTitle($params['title']);
$this->entityManager->persist($task); // NO!

// ✅ DO use TaskService (already has all logic!)
$task = $this->taskService->createTask(
    $user,
    $params['title'],
    $params['description'] ?? null,
    $params['dueDate'] ?? null,
    $params['priority'] ?? null
);
```

### 4. Test Each Phase Before Moving On
```bash
# After Phase 1 (Domain)
docker exec backend-psql16 psql -U user -d backend-app -c "\d voice_commands"

# After Phase 2 (Services)
curl -X POST http://localhost:11434/api/generate -d '{"model":"llama3.2:3b","prompt":"test"}'

# After Phase 3 (API)
curl -X POST http://localhost:8089/api/voice/command -H "Authorization: Bearer TOKEN" -d '{"text":"test"}'

# After Phase 4 (Queue)
docker exec backend-php83 php bin/console messenger:consume async --limit=1

# After Phase 5 (Frontend)
# Open browser, click button, check console

# After Phase 6 (Infrastructure)
curl http://localhost:11434/api/tags
curl http://localhost:8090/health
curl http://localhost:8000/health
```

---

## 🚨 Troubleshooting

### Problem: LLM returns invalid JSON
→ Check [`PROMPTS_LIBRARY.md`](REFERENCE/PROMPTS_LIBRARY.md)
→ Verify `format: 'json'` in Ollama request
→ Check `temperature: 0.3` (not too high!)

### Problem: Task not found by voice
→ Check `SmartSearchService` in [`02_SERVICES.md`](02_BACKEND/02_SERVICES.md)
→ Adjust similarity threshold (default: 0.3)
→ Check PostgreSQL trigram extension installed

### Problem: WebSocket not updating
→ Check Centrifugo running: `docker ps | grep centrifugo`
→ Verify API key in `.env`
→ Check browser console for WebSocket errors

### Problem: Queue not processing
→ Check worker running: `docker exec backend-php83 php bin/console messenger:consume async`
→ Check RabbitMQ: `docker ps | grep rabbitmq`
→ View logs: `docker logs -f backend-php83`

---

## 📊 5-Day Implementation Timeline

```
Day 1 (6h):
 Morning [3h]  → Domain Layer (Entity + Value Objects + Migration)
 Afternoon [3h] → Services (LLM, SmartSearch, WebSocket, Executor, Processing)

Day 2 (6h):
 Morning [2h]  → Command Handlers (Interface + 3 handlers)
 Afternoon [2h] → API Endpoints (Controller + 3 endpoints + testing)
 Evening [2h]  → Queue (Message + Handler + Config)

Day 3 (6h):
 Morning [2h]  → Queue testing + debugging
 Afternoon [3h] → Frontend (Services + Composables)
 Evening [1h]  → Frontend VoiceButton component

Day 4 (6h):
 Morning [2h]  → Frontend integration + testing
 Afternoon [4h] → Infrastructure (Ollama + Whisper)

Day 5 (4h):
 Morning [2h]  → Infrastructure (Centrifugo + Redis)
 Afternoon [2h] → End-to-end testing + bug fixes

TOTAL: ~28 hours of focused work
```

**Realistic MVP:** 3-5 days for solo developer with AI assistance

---

## 📚 Documentation Navigation

### Start Here
- 📄 [`docs/ai/START_HERE.md`](START_HERE.md) - Quick overview + full checklist
- 📄 [`docs/ai/INDEX.md`](INDEX.md) - Complete navigation map

### Backend (5 docs)
1. [`docs/ai/02_BACKEND/01_DOMAIN_MODEL.md`](02_BACKEND/01_DOMAIN_MODEL.md) - VoiceCommand entity
2. [`docs/ai/02_BACKEND/02_SERVICES.md`](02_BACKEND/02_SERVICES.md) - 5 core services
3. [`docs/ai/02_BACKEND/03_COMMAND_HANDLERS.md`](02_BACKEND/03_COMMAND_HANDLERS.md) - Command pattern
4. [`docs/ai/02_BACKEND/04_API_ENDPOINTS.md`](02_BACKEND/04_API_ENDPOINTS.md) - REST API
5. [`docs/ai/02_BACKEND/05_QUEUE_PROCESSING.md`](02_BACKEND/05_QUEUE_PROCESSING.md) - Async queue

### Frontend (1 doc)
- [`docs/ai/03_FRONTEND/01_VOICE_RECORDING.md`](03_FRONTEND/01_VOICE_RECORDING.md) - Complete UI implementation

### Infrastructure (4 docs)
1. [`docs/ai/01_INFRASTRUCTURE/01_SETUP.md`](01_INFRASTRUCTURE/01_SETUP.md) - System requirements
2. [`docs/ai/01_INFRASTRUCTURE/02_DOCKER.md`](01_INFRASTRUCTURE/02_DOCKER.md) - Docker configs (optional)
3. [`docs/ai/01_INFRASTRUCTURE/03_AI_SERVICES.md`](01_INFRASTRUCTURE/03_AI_SERVICES.md) - ⚠️ Ollama + Whisper + Centrifugo
4. [`docs/ai/01_INFRASTRUCTURE/04_SECURITY.md`](01_INFRASTRUCTURE/04_SECURITY.md) - Security (optional for MVP)

### Reference (Critical!)
- ⭐⭐⭐ [`docs/ai/REFERENCE/PROMPTS_LIBRARY.md`](REFERENCE/PROMPTS_LIBRARY.md) - **MOST CRITICAL - LLM prompts**
- [`docs/ai/REFERENCE/TESTING_STRATEGY.md`](docs/ai/REFERENCE/TESTING_STRATEGY.md) - Testing guide (optional)

---

## ✅ Final Checklist

Before marking as complete:

- [ ] All backend services implemented
- [ ] All command handlers working
- [ ] API endpoints tested with curl
- [ ] Queue worker running
- [ ] Frontend voice button works
- [ ] WebSocket updates received
- [ ] Ollama + Llama 3.2 installed
- [ ] Whisper transcription works
- [ ] End-to-end test passes (voice → task created)
- [ ] Russian language works
- [ ] Code follows SOLID principles

---

**Good luck! Start with [`docs/ai/START_HERE.md`](START_HERE.md) for detailed instructions.**

---

**Created:** 2025-01-08
**For:** Voice AI Assistant MVP Implementation
**Tech Stack:** Symfony + Vue.js + Ollama + Whisper + Centrifugo
