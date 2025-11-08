# 🎯 START HERE - Voice AI Assistant Implementation

> **For AI (Opus 4.1, Sonnet 4.5)**: This is your starting point. Read this first!

## 📍 Current Location

You are in: `test_sonnet45/` project
Documentation: `docs/ai/` directory

## 🎯 Mission

Implement Voice AI Assistant for Task Manager:
- User speaks → AI understands → Task created/updated
- Works in web app + Telegram
- Uses local LLM (Llama 3.2) + Whisper STT

## 🚦 Implementation Checklist

### ✅ Before You Start

- [ ] Read [INDEX.md](INDEX.md) - Full documentation map
- [ ] Read [PROMPTS_LIBRARY.md](REFERENCE/PROMPTS_LIBRARY.md) - ⭐ MOST IMPORTANT
- [ ] Check existing code in `backend/src/` and `frontend/src/`

### 📝 Step-by-Step Implementation

#### Phase 1: Backend (2-3 days)

**Day 1: Domain & Services**
- [ ] 1.1: [Domain Model](02_BACKEND/01_DOMAIN_MODEL.md) - Create VoiceCommand entity
- [ ] 1.2: Run migration to create tables
- [ ] 1.3: [Services](02_BACKEND/02_SERVICES.md) - Implement 5 core services
- [ ] 1.4: Test LLMService with Ollama (use prompts from library!)

**Day 2: Handlers & API**
- [ ] 2.1: [Command Handlers](02_BACKEND/03_COMMAND_HANDLERS.md) - Start with CreateTaskHandler
- [ ] 2.2: [API Endpoints](02_BACKEND/04_API_ENDPOINTS.md) - Create 3 endpoints
- [ ] 2.3: Test with curl/Postman

**Day 3: Queue & Processing**
- [ ] 3.1: [Queue Processing](02_BACKEND/05_QUEUE_PROCESSING.md) - Setup async worker
- [ ] 3.2: Test end-to-end: API → Queue → WebSocket

#### Phase 2: Frontend (1-2 days)

**Day 4: Voice UI**
- [ ] 4.1: [Voice Recording](03_FRONTEND/01_VOICE_RECORDING.md) - Create VoiceButton component
- [ ] 4.2: Implement WebSocket listener
- [ ] 4.3: Test recording → backend → result display

#### Phase 3: Infrastructure (1 day)

**Day 5: AI Services**
- [ ] 5.1: [AI Services](01_INFRASTRUCTURE/03_AI_SERVICES.md) - Install Ollama + Whisper
- [ ] 5.2: Load Llama 3.2 3B model
- [ ] 5.3: Test Whisper transcription
- [ ] 5.4: Integration test full flow

## 🎯 MVP Scope (What to Build)

### Must Have (Core)
1. ✅ **Create task** - "Создай задачу купить молоко завтра"
2. ✅ **Complete task** - "Отметь задачу купить молоко как выполненную"
3. ✅ **Filter tasks** - "Покажи все задачи на завтра"

### Nice to Have (Add if time)
4. ⚪ **Create subtask** - "Добавь подзадачу к проекту"
5. ⚪ **Bulk operations** - "Заверши три задачи"

### Skip for Now
- ❌ Telegram integration (implement later)
- ❌ Complex analytics
- ❌ Multi-language support

## 📋 Code Structure Overview

```
backend/src/
├── Entity/
│   └── VoiceCommand.php               [CREATE THIS]
├── ValueObject/
│   ├── CommandType.php                [CREATE THIS]
│   ├── CommandStatus.php              [CREATE THIS]
│   ├── TranscriptionResult.php        [CREATE THIS]
│   └── ParsedCommand.php              [CREATE THIS]
├── Service/
│   └── VoiceAssistant/
│       ├── VoiceProcessingService.php [CREATE THIS]
│       ├── LLMService.php             [CREATE THIS - USE PROMPTS!]
│       ├── CommandExecutorService.php [CREATE THIS]
│       ├── WebSocketPublisherService.php
│       ├── SmartSearchService.php
│       └── Command/
│           └── Handlers/
│               ├── CreateTaskHandler.php
│               └── CompleteTaskHandler.php
├── Controller/
│   └── VoiceCommandController.php     [CREATE THIS]
├── Message/
│   └── ProcessVoiceCommandMessage.php [CREATE THIS]
└── MessageHandler/
    └── ProcessVoiceCommandHandler.php [CREATE THIS]

frontend/src/
├── composables/
│   ├── useVoiceRecording.ts           [CREATE THIS]
│   └── useWebSocket.ts                [CREATE THIS]
├── services/
│   └── voiceCommand.service.ts        [CREATE THIS]
└── components/
    └── VoiceAssistant/
        └── VoiceButton.vue             [CREATE THIS]
```

## 🚨 Critical Files to Read

1. **[PROMPTS_LIBRARY.md](REFERENCE/PROMPTS_LIBRARY.md)** ⭐⭐⭐
   - System prompt for LLM
   - JSON structure
   - Test cases

2. **[Services Guide](02_BACKEND/02_SERVICES.md)** ⭐⭐
   - LLMService implementation
   - Ollama integration
   - Error handling

3. **[Domain Model](02_BACKEND/01_DOMAIN_MODEL.md)** ⭐
   - Entity structure
   - Value objects
   - Database schema

## 💡 Quick Tips for AI

### When Implementing Services

```php
// ALWAYS use prompts from PROMPTS_LIBRARY.md
private function buildPrompt(string $text): string
{
    return <<<PROMPT
You are a task management assistant.
[... copy EXACT prompt from library ...]
PROMPT;
}
```

### When Creating Handlers

```php
// Keep it simple - use existing TaskService
public function handle(VoiceCommand $command): array
{
    $params = $command->getParsedCommand()->getParameters();

    // Use EXISTING TaskService - don't create new methods!
    $task = $this->taskService->createTask(
        $command->getUser(),
        $params['title'],
        $params['description'] ?? null
    );

    return ['success' => true, 'task_id' => $task->getId()];
}
```

### When Testing

```bash
# Test LLM
curl -X POST http://localhost:11434/api/generate \
  -d '{"model":"llama3.2:3b","prompt":"Создай задачу купить молоко"}'

# Test API
curl -X POST http://localhost:8089/api/voice/command \
  -H "Authorization: Bearer TOKEN" \
  -d '{"text":"Создай задачу тест"}'
```

## 🚨 Common Mistakes to Avoid

### ❌ Don't Do This

```php
// Don't create new repository methods if not needed
$this->taskRepository->findByVoiceDescription($text); // NO!

// Don't create complex parsing logic
$this->parseComplexCommand($text); // NO!

// Don't add unnecessary services
class VoiceCommandAnalyticsService {} // NO! (not for MVP)
```

### ✅ Do This Instead

```php
// Use existing methods
$task = $this->searchService->findTaskByDescription($text, $user);

// Keep parsing in LLM
$parsed = $this->llmService->parseCommand($text);

// Only create what's needed for MVP
```

## 📊 Success Criteria

Your implementation is complete when:

1. ✅ User can click button and record voice
2. ✅ Voice is transcribed to text (Whisper)
3. ✅ Text is parsed to JSON (LLM with prompts from library)
4. ✅ Command is executed (task created/completed)
5. ✅ User sees result via WebSocket
6. ✅ Works for Russian language
7. ✅ Response time < 5 seconds

## 🆘 If You Get Stuck

### Problem: LLM returns invalid JSON
→ Check [PROMPTS_LIBRARY.md](REFERENCE/PROMPTS_LIBRARY.md)
→ Verify `format: 'json'` in Ollama request

### Problem: Task not found by voice
→ Check SmartSearchService in [Services](02_BACKEND/02_SERVICES.md)
→ Adjust similarity threshold

### Problem: WebSocket not updating
→ Check Centrifugo is running: `docker ps | grep centrifugo`
→ Verify API key in .env

### Problem: Queue not processing
→ Check worker is running: `docker exec backend-php83 php bin/console messenger:consume async`
→ Check RabbitMQ: `docker ps | grep rabbitmq`

## 📚 Documentation Index

Full map: [INDEX.md](INDEX.md)

Quick links:
- Backend: [02_BACKEND/](02_BACKEND/)
- Frontend: [03_FRONTEND/](03_FRONTEND/)
- Infrastructure: [01_INFRASTRUCTURE/](01_INFRASTRUCTURE/)
- Reference: [REFERENCE/](REFERENCE/)

## ✅ Final Checklist

Before marking as complete:

- [ ] All Phase 1 (Backend) implemented
- [ ] All Phase 2 (Frontend) implemented
- [ ] AI services (Ollama, Whisper) installed
- [ ] End-to-end test passes
- [ ] Can create task via voice
- [ ] Can complete task via voice
- [ ] WebSocket updates work
- [ ] Russian language works
- [ ] Code follows SOLID principles (check docs)

## 🎯 Remember

**For MVP:**
- ✅ Simple and working > Complex and broken
- ✅ Use existing services (TaskService)
- ✅ Follow prompts library exactly
- ✅ Test each phase before moving on

**Priority:**
1. Make it work
2. Make it correct (SOLID)
3. Make it fast (later)

---

**Good luck! Start with [INDEX.md](INDEX.md) for full context.**