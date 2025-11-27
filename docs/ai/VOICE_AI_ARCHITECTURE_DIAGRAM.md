# 🎨 Voice AI Assistant - Архитектурная Диаграмма

> **Версия**: 2.0.0
> **Дата**: 2025-11-27
> **Визуализация**: Полная архитектура системы с нативными AI сервисами

---

## ⚠️ КРИТИЧЕСКИ ВАЖНО: Нативные AI Сервисы

**AI сервисы (Ollama и Whisper) работают НАТИВНО на хосте, НЕ в Docker!**

| Сервис | Где работает | Порт | Доступ из Docker |
|--------|-------------|------|------------------|
| **Ollama** | Нативно на хосте | 11434 | `host.docker.internal:11434` |
| **Whisper** | Нативно на хосте | 9001 | `host.docker.internal:9001` |
| **Centrifugo** | Docker | 8000 | `backend-centrifugo:8000` |
| **RabbitMQ** | Docker | 5672 | `backend-rabbitmq:5672` |

---

## 🌐 Полная Архитектурная Диаграмма

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                                  👤 ПОЛЬЗОВАТЕЛЬ                                         │
│                                                                                          │
│  🗣️ Голосовая Команда: "Создай задачу купить молоко завтра в 10:00 с тегом 'Покупки'" │
└──────────────────────────────────┬──────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                              🖥️  FRONTEND (Vue.js 3.4)                                   │
│                                                                                          │
│  ┌──────────────────────┐   ┌──────────────────────┐   ┌──────────────────────┐       │
│  │   VoiceButton.vue    │   │ WebSocket Listener   │   │    Task List View    │       │
│  │   (PrimeVue)         │   │   (Centrifugo)       │   │    (Real-time)       │       │
│  │                      │   │                      │   │                      │       │
│  │  ┌──────────────┐    │   │  ┌──────────────┐   │   │  ┌──────────────┐   │       │
│  │  │ 🎤 Start Rec │◀───┼───┼──│ Listen Event │◀──┼───┼──│ Show Updates │   │       │
│  │  │ 🔴 Recording │    │   │  │ Update State │   │   │  │ Optimistic UI│   │       │
│  │  │ ⏹️  Stop Rec  │    │   │  └──────────────┘   │   │  └──────────────┘   │       │
│  │  │ 📤 Upload     │────┼───┼──────────────────────┼───┼────────────────────►│       │
│  │  └──────────────┘    │   │                      │   │                      │       │
│  │  MediaRecorder API   │   │  Centrifugo Client   │   │   Pinia TaskStore    │       │
│  └──────────────────────┘   └──────────────────────┘   └──────────────────────┘       │
│                                                                                          │
│  Composables:                                                                           │
│  • useVoiceRecording() - Запись и отправка аудио                                       │
│  • useWebSocket() - Real-time обновления                                               │
│  • useTaskStore() - Состояние задач                                                    │
└────────────────────────┬──────────────────────────┬──────────────────────────┬─────────┘
                         │ HTTP POST                │ WebSocket                │
                         │ /api/voice/command       │ ws://centrifugo:8000     │
                         ▼                          ▲                          ▲
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                           🔧 BACKEND (Symfony 7.1 + PHP 8.3) [Docker]                   │
│                                                                                          │
│  ┌──────────────────────────────────────────────────────────────────────────────────┐  │
│  │                          📡 API LAYER (Controllers)                               │  │
│  │                                                                                   │  │
│  │  VoiceCommandController::processCommand()                                         │  │
│  │  1️⃣  Validate audio file (format, size)                                          │  │
│  │  2️⃣  Create VoiceCommand entity (status: PENDING)                                │  │
│  │  3️⃣  Dispatch to RabbitMQ queue                                                  │  │
│  │  4️⃣  Return 202 Accepted                                                         │  │
│  └───────────────────────────────────┬─────────────────────────────────────────────┘  │
│                                      │                                                 │
│                                      ▼                                                 │
│  ┌──────────────────────────────────────────────────────────────────────────────────┐  │
│  │                      📨 MESSAGE QUEUE (RabbitMQ в Docker)                         │  │
│  │                                                                                   │  │
│  │  Message → Queue: "async" → ProcessVoiceCommandHandler                           │  │
│  └───────────────────────────────────┬─────────────────────────────────────────────┘  │
│                                      │                                                 │
│                                      ▼                                                 │
│  ┌──────────────────────────────────────────────────────────────────────────────────┐  │
│  │                        🧠 SERVICE LAYER (Business Logic)                          │  │
│  │                                                                                   │  │
│  │  VoiceProcessingService - Главный оркестратор                                    │  │
│  │  {                                                                                │  │
│  │      // Шаг 1: Транскрипция (НАТИВНЫЙ Whisper на хосте!)                        │  │
│  │      $text = $this->whisperService->transcribe($audioPath);                      │  │
│  │      // URL: http://host.docker.internal:9001                                    │  │
│  │                                                                                   │  │
│  │      // Шаг 2: Парсинг через LLM (НАТИВНЫЙ Ollama на хосте!)                    │  │
│  │      $parsed = $this->llmService->parseCommand($text);                           │  │
│  │      // URL: http://host.docker.internal:11434                                   │  │
│  │                                                                                   │  │
│  │      // Шаг 3: Выполнение команды                                               │  │
│  │      $result = $this->commandExecutor->execute($parsed, $user);                  │  │
│  │                                                                                   │  │
│  │      // Шаг 4: Уведомление через WebSocket                                      │  │
│  │      $this->wsPublisher->publish($user, $result);                               │  │
│  │  }                                                                                │  │
│  └──────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                          │
│  ┌──────────────────────────────────────────────────────────────────────────────────┐  │
│  │                         💾 DATABASE (PostgreSQL 16 в Docker)                     │  │
│  │                                                                                   │  │
│  │  Tables: voice_commands, task, user, tag, task_tags                             │  │
│  └──────────────────────────────────────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────────────────────────────────────┘
                         │                                │
                         │ HTTP                           │ HTTP
                         │ host.docker.internal           │ host.docker.internal
                         ▼                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                    🤖 AI SERVICES (НАТИВНО НА ХОСТЕ, НЕ Docker!)                        │
│                                                                                          │
│  ┌────────────────────────────────┐   ┌────────────────────────────────┐               │
│  │  🦙 Ollama (LLM Runtime)        │   │  🎙️ faster-whisper-server (STT) │               │
│  │  Port: 11434 (localhost)        │   │  Port: 9001 (localhost)         │               │
│  │                                 │   │                                 │               │
│  │  Model: qwen2.5:14b-instruct   │   │  Model: large-v3                │               │
│  │  Quant: q4_K_M                  │   │  Size: ~3-4 GB VRAM             │               │
│  │  Size: ~10-12 GB VRAM           │   │  Accuracy: 98%+ (русский)       │               │
│  │  Context: 8192 tokens           │   │  Real-time factor: 0.1-0.3      │               │
│  │  Response: 5-15s                │   │  Max audio: 30 seconds          │               │
│  │                                 │   │                                 │               │
│  │  API:                           │   │  API (OpenAI-совместимый):      │               │
│  │  POST /api/generate             │   │  POST /v1/audio/transcriptions  │               │
│  │  {                              │   │  {                              │               │
│  │    "model": "qwen2.5:14b...",  │   │    "file": audio_blob,          │               │
│  │    "prompt": "...",             │   │    "model": "large-v3",         │               │
│  │    "format": "json",            │   │    "language": "ru"             │               │
│  │    "stream": false              │   │  }                              │               │
│  │  }                              │   │  Response: { "text": "..." }    │               │
│  └────────────────────────────────┘   └────────────────────────────────┘               │
│                                                                                          │
│  ⚠️ Почему НАТИВНО, а не Docker?                                                        │
│  ┌──────────────────────────────────────────────────────────────────────────────────┐  │
│  │  • GPU доступ: Прямой доступ к CUDA/Metal без виртуализации                      │  │
│  │  • Производительность: 5-10x быстрее чем Docker                                  │  │
│  │  • Память: Нет оверхеда контейнера                                               │  │
│  │  • Whisper Docker: 30-45s → Native: 3-5s                                         │  │
│  │  • LLM Docker: 60-90s → Native: 5-15s                                            │  │
│  └──────────────────────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 📊 Детальный Поток Данных (Data Flow)

### 🎯 Сценарий: Создание задачи голосом

```
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  STEP 1: Запись Голоса                                                   [~2s]      │
├──────────────────────────────────────────────────────────────────────────────────────┤
│  Frontend (VoiceButton.vue)                                                         │
│  1. User clicks 🎤 button                                                           │
│  2. MediaRecorder starts → navigator.mediaDevices.getUserMedia()                    │
│  3. User speaks: "Создай задачу купить молоко завтра в 10:00"                      │
│  4. User clicks ⏹️ stop                                                             │
│  5. Blob created (audio/webm format)                                                │
│  Output: Blob { type: 'audio/webm', size: 45KB }                                   │
└──────────────────────────────────────────────────────────────────────────────────────┘
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  STEP 2: Отправка на Backend                                             [~100ms]  │
├──────────────────────────────────────────────────────────────────────────────────────┤
│  Frontend → HTTP POST /api/voice/command                                            │
│  Headers: Authorization: Bearer JWT_TOKEN                                           │
│  Body: multipart/form-data { audio: <binary blob> }                                │
│  Output: HTTP 202 Accepted { "id": 42, "status": "PENDING" }                       │
└──────────────────────────────────────────────────────────────────────────────────────┘
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  STEP 3: Backend Controller + Queue                                      [~60ms]   │
├──────────────────────────────────────────────────────────────────────────────────────┤
│  VoiceCommandController → VoiceCommand Entity → RabbitMQ Queue                     │
│  Output: Message dispatched to async queue                                          │
└──────────────────────────────────────────────────────────────────────────────────────┘
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  STEP 4: Speech-to-Text (НАТИВНЫЙ Whisper на хосте!)                     [~3-5s]  │
├──────────────────────────────────────────────────────────────────────────────────────┤
│  WhisperService → http://host.docker.internal:9001/v1/audio/transcriptions         │
│                                                                                      │
│  ⚠️ faster-whisper-server работает НАТИВНО на хосте, не в Docker!                   │
│  Model: large-v3 (98%+ точность для русского)                                       │
│                                                                                      │
│  Output: { "text": "Создай задачу купить молоко завтра в 10:00" }                  │
└──────────────────────────────────────────────────────────────────────────────────────┘
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  STEP 5: LLM Parsing (НАТИВНЫЙ Ollama на хосте!)                         [~5-15s] │
├──────────────────────────────────────────────────────────────────────────────────────┤
│  LLMService → http://host.docker.internal:11434/api/generate                        │
│                                                                                      │
│  ⚠️ Ollama работает НАТИВНО на хосте, не в Docker!                                  │
│  Model: qwen2.5:14b-instruct-q4_K_M (отличное понимание русского)                  │
│                                                                                      │
│  Output: ParsedCommand {                                                            │
│    commandType: "CREATE_TASK",                                                      │
│    parameters: { "title": "Купить молоко", "dueDate": "2025-11-28T10:00:00Z" }    │
│  }                                                                                   │
└──────────────────────────────────────────────────────────────────────────────────────┘
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  STEP 6: Command Execution                                               [~100ms] │
├──────────────────────────────────────────────────────────────────────────────────────┤
│  CommandExecutorService → CreateTaskHandler → TaskService                          │
│  Output: ExecutionResult { success: true, taskId: 123 }                            │
└──────────────────────────────────────────────────────────────────────────────────────┘
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  STEP 7: WebSocket Notification                                          [~50ms]  │
├──────────────────────────────────────────────────────────────────────────────────────┤
│  WebSocketPublisherService → Centrifugo → Frontend                                 │
│  Output: UI updated, user sees new task immediately                                 │
└──────────────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────────────────┐
│  ✅ ИТОГО: Время выполнения ~8-20 секунд (с нативными AI сервисами)                 │
│                                                                                      │
│  • Recording: 2s (user action)                                                      │
│  • Upload: 100ms                                                                    │
│  • Backend + Queue: 60ms                                                            │
│  • Whisper STT (NATIVE): 3-5s  (было 30-45s в Docker!)                             │
│  • LLM parsing (NATIVE): 5-15s (было 60-90s в Docker!)                             │
│  • Command execution: 100ms                                                         │
│  • WebSocket: 50ms                                                                  │
│  ═══════════════════════════════                                                    │
│  TOTAL: ~8-20 seconds ✅ (5-7x быстрее чем Docker!)                                 │
└──────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 🔗 Интеграция с Существующим Кодом

```
┌──────────────────────────────────────────────────────────────────────────┐
│  Существующий Код (НЕ ТРОГАЕМ!)                                          │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  TaskService.php                  ✅ ИСПОЛЬЗУЕМ                          │
│  • createTask(CreateTaskDto)                                             │
│  • updateTask(UpdateTaskDto)                                             │
│  • toggleCompletion(Task)                                                │
│  • deleteTask(Task)                                                      │
│                                                                           │
│  TaskRepository.php               ✅ ИСПОЛЬЗУЕМ                          │
│  • findUserTasks(User)                                                   │
│  • findTodayTasks(User)                                                  │
│  • findUpcomingTasks(User)                                               │
│  • searchTasks(User, string)      ← Smart Search здесь!                 │
│                                                                           │
│  Task Entity                      ✅ ИСПОЛЬЗУЕМ                          │
│  • All properties                                                        │
│  • All relations (tags, subtasks, media)                                │
│                                                                           │
└──────────────────────────────────────────────────────────────────────────┘
                                     ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  Новый Код (СОЗДАЕМ!)                                                    │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  VoiceCommand Entity               🆕 NEW                                │
│  VoiceCommandRepository            🆕 NEW                                │
│  VoiceProcessingService            🆕 NEW                                │
│  LLMService (→ host.docker.internal:11434)   🆕 NEW                      │
│  WhisperService (→ host.docker.internal:9001) 🆕 NEW                     │
│  CommandExecutorService            🆕 NEW                                │
│  SmartSearchService                🆕 NEW                                │
│  WebSocketPublisherService         🆕 NEW                                │
│  Command Handlers (6 classes)     🆕 NEW                                │
│  VoiceCommandController            🆕 NEW                                │
│  ProcessVoiceCommandMessage        🆕 NEW                                │
│  ProcessVoiceCommandHandler        🆕 NEW                                │
│                                                                           │
│  Frontend:                                                                │
│  VoiceButton.vue                   🆕 NEW                                │
│  useVoiceRecording.ts              🆕 NEW                                │
│  useWebSocket.ts                   🆕 NEW                                │
│  voiceCommand.service.ts           🆕 NEW                                │
│                                                                           │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 🎯 Ключевые Паттерны Проектирования

```
┌──────────────────────────────────────────────────────────────────────────┐
│  Применяемые SOLID/GRASP/GoF Паттерны                                    │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  1. Strategy Pattern (Command Handlers)                                  │
│     CommandExecutorService → CreateTaskHandler / CompleteTaskHandler     │
│                                                                           │
│  2. Service Layer Pattern                                                │
│     Controllers → Services → Repositories → Entities                    │
│     (Thin)       (Fat)      (Data Access) (Domain)                      │
│                                                                           │
│  3. Repository Pattern                                                   │
│     TaskRepository - Encapsulates data access logic                     │
│                                                                           │
│  4. DTO Pattern                                                          │
│     CreateTaskDto, UpdateTaskDto - Data transfer between layers         │
│                                                                           │
│  5. Message Queue Pattern                                                │
│     Async processing with RabbitMQ                                       │
│                                                                           │
│  6. Pub/Sub Pattern                                                      │
│     WebSocket updates via Centrifugo                                     │
│                                                                           │
│  7. Dependency Injection                                                 │
│     All services injected through constructor                            │
│                                                                           │
│  8. Single Responsibility Principle                                     │
│     Each service/handler has ONE clear responsibility                    │
│                                                                           │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 💻 Production GPU Requirements (RTX 4090)

```
┌──────────────────────────────────────────────────────────────────────────┐
│  GPU Server для Production                                               │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  GPU: NVIDIA RTX 4090 24GB                                               │
│  VRAM: 24 GB GDDR6X                                                      │
│  Bandwidth: 1008 GB/s                                                    │
│  CUDA Cores: 16,384                                                      │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │  VRAM Distribution:                                              │    │
│  │  • Ollama (Qwen 2.5 14B q4_K_M): ~10-12 GB                      │    │
│  │  • Whisper (large-v3): ~3-4 GB                                   │    │
│  │  • System/Buffer: ~8-10 GB                                       │    │
│  │  ═══════════════════════════════════                             │    │
│  │  Total Used: ~21-26 GB / 24 GB ✅                                │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                                                           │
│  RAM: 32-64 GB DDR5                                                      │
│  Storage: 200 GB NVMe SSD                                                │
│  OS: Ubuntu 22.04 LTS                                                    │
│                                                                           │
│  Стоимость: ~29,000₽/мес (выделенный сервер)                            │
│                                                                           │
│  ⚠️ Оба сервиса (Ollama + Whisper) могут работать одновременно!         │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 📦 Файловая Структура Проекта

```
test_sonnet45/
├── apps/
│   ├── backend/
│   │   └── src/
│   │       ├── Entity/
│   │       │   ├── VoiceCommand.php                     [NEW] 🆕
│   │       │   ├── Task.php                             [EXISTS] ✅
│   │       │   └── User.php                             [EXISTS] ✅
│   │       │
│   │       ├── ValueObject/
│   │       │   ├── CommandType.php                      [NEW] 🆕
│   │       │   ├── CommandStatus.php                    [NEW] 🆕
│   │       │   └── ParsedCommand.php                    [NEW] 🆕
│   │       │
│   │       ├── Service/
│   │       │   ├── VoiceAssistant/
│   │       │   │   ├── VoiceProcessingService.php       [NEW] 🆕
│   │       │   │   ├── LLMService.php                   [NEW] 🆕 → host.docker.internal:11434
│   │       │   │   ├── WhisperService.php               [NEW] 🆕 → host.docker.internal:9001
│   │       │   │   ├── CommandExecutorService.php       [NEW] 🆕
│   │       │   │   ├── SmartSearchService.php           [NEW] 🆕
│   │       │   │   ├── WebSocketPublisherService.php    [NEW] 🆕
│   │       │   │   └── Command/Handlers/                [NEW] 🆕
│   │       │   │
│   │       │   └── TaskService.php                      [EXISTS] ✅
│   │       │
│   │       ├── Controller/Api/
│   │       │   ├── VoiceCommandController.php           [NEW] 🆕
│   │       │   └── TaskController.php                   [EXISTS] ✅
│   │       │
│   │       ├── Message/
│   │       │   └── ProcessVoiceCommandMessage.php       [NEW] 🆕
│   │       │
│   │       └── MessageHandler/
│   │           └── ProcessVoiceCommandHandler.php       [NEW] 🆕
│   │
│   └── frontend/
│       └── src/
│           ├── components/VoiceAssistant/
│           │   ├── VoiceButton.vue                      [NEW] 🆕
│           │   └── VoiceStatus.vue                      [NEW] 🆕
│           │
│           ├── composables/
│           │   ├── useVoiceRecording.ts                 [NEW] 🆕
│           │   └── useWebSocket.ts                      [NEW] 🆕
│           │
│           └── services/
│               └── voiceCommand.service.ts              [NEW] 🆕
│
├── infrastructure/
│   └── docker/                      # Только app-сервисы!
│       ├── docker-compose.app.yml   # PHP, PostgreSQL, RabbitMQ, Centrifugo
│       └── docker-compose.dev.yml   # Dev overrides
│
│   ⚠️ AI-сервисы НЕ в Docker! Установлены нативно на хосте:
│   • Ollama: brew install ollama / curl installer
│   • Whisper: pip install faster-whisper-server
│
└── docs/ai/
    ├── VOICE_AI_ARCHITECTURE_DIAGRAM.md     [THIS FILE] 📄
    ├── NATIVE_INSTALLATION.md               🆕 Инструкции по установке
    ├── START_HERE.md
    └── INDEX.md
```

---

## ✅ Чеклист Готовности к Production

```
Backend:
  ☐ VoiceCommand Entity + migrations
  ☐ VoiceProcessingService
  ☐ LLMService (→ host.docker.internal:11434)
  ☐ WhisperService (→ host.docker.internal:9001)
  ☐ CommandExecutorService
  ☐ 6 Command Handlers
  ☐ VoiceCommandController
  ☐ ProcessVoiceCommandHandler
  ☐ SmartSearchService
  ☐ WebSocketPublisherService
  ☐ Unit tests (coverage > 80%)

Frontend:
  ☐ VoiceButton.vue
  ☐ useVoiceRecording composable
  ☐ useWebSocket composable
  ☐ voiceCommand.service.ts
  ☐ Pinia store integration
  ☐ Error handling UI
  ☐ Loading states

AI Services (НАТИВНО на хосте!):
  ☐ Ollama установлен нативно
  ☐ qwen2.5:14b-instruct-q4_K_M модель загружена
  ☐ faster-whisper-server установлен нативно
  ☐ large-v3 модель загружена
  ☐ systemd services настроены (Linux)
  ☐ Health checks работают

Infrastructure (Docker):
  ☐ Centrifugo configured
  ☐ RabbitMQ routing
  ☐ extra_hosts для Linux

Testing:
  ☐ E2E test: Create task via voice
  ☐ E2E test: Complete task via voice
  ☐ E2E test: List tasks via voice
  ☐ Performance test: Response < 20s (native AI)
  ☐ Stress test: 100 concurrent users
```

---

## 🔗 Связанные Документы

- [NATIVE_INSTALLATION.md](NATIVE_INSTALLATION.md) - Установка Ollama и Whisper нативно
- [01_SETUP.md](01_INFRASTRUCTURE/01_SETUP.md) - Настройка инфраструктуры
- [02_SERVICES.md](02_BACKEND/02_SERVICES.md) - Реализация сервисов
- [INDEX.md](INDEX.md) - Главный индекс документации

---

**📅 Последнее обновление**: 2025-11-27
**👨‍💻 Автор**: Claude Code AI
**📝 Версия**: 2.0.0
**✅ Статус**: Updated for Native AI Services
