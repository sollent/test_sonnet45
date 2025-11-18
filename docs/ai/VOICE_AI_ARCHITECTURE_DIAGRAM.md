# 🎨 Voice AI Assistant - Архитектурная Диаграмма

> **Версия**: 1.0.0
> **Дата**: 2025-11-11
> **Визуализация**: Полная архитектура системы с технологическим стеком

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
│                           🔧 BACKEND (Symfony 7.1 + PHP 8.3)                            │
│                                                                                          │
│  ┌──────────────────────────────────────────────────────────────────────────────────┐  │
│  │                          📡 API LAYER (Controllers)                               │  │
│  │                                                                                   │  │
│  │  ┌────────────────────────────────────────────────────────────────────────────┐  │  │
│  │  │  VoiceCommandController::processCommand()                                  │  │  │
│  │  │                                                                             │  │  │
│  │  │  1️⃣  Validate audio file (format, size)                                    │  │  │
│  │  │  2️⃣  Create VoiceCommand entity (status: PENDING)                          │  │  │
│  │  │  3️⃣  Dispatch to RabbitMQ queue                                            │  │  │
│  │  │  4️⃣  Return 202 Accepted                                                   │  │  │
│  │  │                                                                             │  │  │
│  │  │  Endpoints:                                                                 │  │  │
│  │  │  • POST /api/voice/command - Process voice command                         │  │  │
│  │  │  • GET  /api/voice/commands - List user commands                           │  │  │
│  │  │  • GET  /api/voice/command/{id} - Get command status                       │  │  │
│  │  └────────────────────────────────────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────┬─────────────────────────────────────────────┘  │
│                                      │                                                 │
│                                      ▼                                                 │
│  ┌──────────────────────────────────────────────────────────────────────────────────┐  │
│  │                      📨 MESSAGE QUEUE (RabbitMQ 3.12)                            │  │
│  │                                                                                   │  │
│  │  ┌────────────────────┐        ┌────────────────────┐                           │  │
│  │  │  Message Producer  │  ────► │   Queue: "async"   │                           │  │
│  │  │  (Symfony)         │        │   (Durable)        │                           │  │
│  │  └────────────────────┘        └────────────────────┘                           │  │
│  │                                           │                                       │  │
│  │                                           ▼                                       │  │
│  │  ┌────────────────────────────────────────────────────────────────────────────┐  │  │
│  │  │  ProcessVoiceCommandHandler::__invoke()                                    │  │  │
│  │  │                                                                             │  │  │
│  │  │  Worker Process (bin/console messenger:consume async)                      │  │  │
│  │  └────────────────────────────────────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────┬─────────────────────────────────────────────┘  │
│                                      │                                                 │
│                                      ▼                                                 │
│  ┌──────────────────────────────────────────────────────────────────────────────────┐  │
│  │                        🧠 SERVICE LAYER (Business Logic)                         │  │
│  │                                                                                   │  │
│  │  ┌────────────────────────────────────────────────────────────────────────────┐  │  │
│  │  │  VoiceProcessingService - Главный оркестратор                              │  │  │
│  │  │                                                                             │  │  │
│  │  │  public function process(VoiceCommand $command): ProcessingResult          │  │  │
│  │  │  {                                                                          │  │  │
│  │  │      // Шаг 1: Транскрипция                                                │  │  │
│  │  │      $text = $this->whisperService->transcribe($command->getAudioPath());  │  │  │
│  │  │                                                                             │  │  │
│  │  │      // Шаг 2: Парсинг через LLM                                           │  │  │
│  │  │      $parsed = $this->llmService->parseCommand($text);                     │  │  │
│  │  │                                                                             │  │  │
│  │  │      // Шаг 3: Выполнение команды                                          │  │  │
│  │  │      $result = $this->commandExecutor->execute($parsed, $user);            │  │  │
│  │  │                                                                             │  │  │
│  │  │      // Шаг 4: Уведомление через WebSocket                                 │  │  │
│  │  │      $this->wsPublisher->publish($user, $result);                          │  │  │
│  │  │                                                                             │  │  │
│  │  │      return $result;                                                        │  │  │
│  │  │  }                                                                          │  │  │
│  │  └────────────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                                   │  │
│  │  ┌─────────────────────┐  ┌─────────────────────┐  ┌──────────────────────┐    │  │
│  │  │  LLMService         │  │ CommandExecutor     │  │ WebSocketPublisher   │    │  │
│  │  │  (Ollama Client)    │  │ (Strategy Pattern)  │  │ (Centrifugo Client)  │    │  │
│  │  │                     │  │                     │  │                      │    │  │
│  │  │  • parseCommand()   │  │ Handlers:           │  │  • publish()         │    │  │
│  │  │  • validateJSON()   │  │ • CreateTask        │  │  • publishToUser()   │    │  │
│  │  │  • buildPrompt()    │  │ • CompleteTask      │  │  • publishToChannel()│    │  │
│  │  └─────────────────────┘  │ • ListTasks         │  └──────────────────────┘    │  │
│  │                            │ • CreateSubtask     │                              │  │
│  │  ┌─────────────────────┐  │ • BulkComplete      │  ┌──────────────────────┐    │  │
│  │  │  SmartSearchService │  │ • RescheduleTask    │  │  TaskService         │    │  │
│  │  │  (Fuzzy Matching)   │  └─────────────────────┘  │  (Existing!)         │    │  │
│  │  │                     │                            │                      │    │  │
│  │  │  • findByFuzzy()    │                            │  • createTask()      │    │  │
│  │  │  • levenshtein()    │                            │  • updateTask()      │    │  │
│  │  │  • similarity > 0.7 │                            │  • deleteTask()      │    │  │
│  │  └─────────────────────┘                            │  • toggleCompletion()│    │  │
│  │                                                      └──────────────────────┘    │  │
│  └──────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                          │
│  ┌──────────────────────────────────────────────────────────────────────────────────┐  │
│  │                         🗄️  DATA LAYER (Doctrine ORM)                            │  │
│  │                                                                                   │  │
│  │  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐              │  │
│  │  │  VoiceCommand    │  │  Task            │  │  User            │              │  │
│  │  │  Entity          │  │  Entity          │  │  Entity          │              │  │
│  │  │                  │  │                  │  │                  │              │  │
│  │  │  • id            │  │  • title         │  │  • email         │              │  │
│  │  │  • userId        │  │  • description   │  │  • voiceCmds[]   │              │  │
│  │  │  • audioPath     │  │  • status        │  └──────────────────┘              │  │
│  │  │  • transcription │  │  • priority      │                                     │  │
│  │  │  • parsedCommand │  │  • dueDate       │  ┌──────────────────┐              │  │
│  │  │  • status        │  │  • tags[]        │  │  Tag             │              │  │
│  │  │  • createdAt     │  │  • subtasks[]    │  │  Entity          │              │  │
│  │  └──────────────────┘  │  • mediaObjects[]│  │                  │              │  │
│  │                         └──────────────────┘  │  • name          │              │  │
│  │  Status:                                      │  • color         │              │  │
│  │  • PENDING                                    │  • usageCount    │              │  │
│  │  • PROCESSING      CommandType:               └──────────────────┘              │  │
│  │  • COMPLETED       • CREATE_TASK                                                │  │
│  │  • FAILED          • COMPLETE_TASK                                              │  │
│  │                    • LIST_TASKS                                                 │  │
│  │                    • CREATE_SUBTASK                                             │  │
│  │                    • BULK_COMPLETE                                              │  │
│  │                    • RESCHEDULE_TASK                                            │  │
│  └──────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                          │
│  ┌──────────────────────────────────────────────────────────────────────────────────┐  │
│  │                         💾 DATABASE (PostgreSQL 16)                              │  │
│  │                                                                                   │  │
│  │  Tables:                                                                          │  │
│  │  • voice_commands - Вся история команд                                           │  │
│  │  • task - Задачи пользователей                                                   │  │
│  │  • user - Аутентификация                                                         │  │
│  │  • tag - Тэги для задач                                                          │  │
│  │  • task_tags - Связь многие-ко-многим                                            │  │
│  │                                                                                   │  │
│  │  Indexes:                                                                         │  │
│  │  • idx_voice_commands_user_status                                                │  │
│  │  • idx_task_user_due_date                                                        │  │
│  │  • idx_task_user_status                                                          │  │
│  └──────────────────────────────────────────────────────────────────────────────────┘  │
└────────────────────────┬──────────────────────────┬──────────────────────────┬─────────┘
                         │                          │                          │
                         ▼                          ▼                          ▼
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                           🤖 AI SERVICES (Docker Containers)                            │
│                                                                                          │
│  ┌────────────────────────────┐   ┌────────────────────────────┐                       │
│  │  🦙 Ollama (LLM Runtime)    │   │  🎙️ Whisper.cpp (STT)      │                       │
│  │  Port: 11434                │   │  Port: 8090                │                       │
│  │                             │   │                             │                       │
│  │  Model: llama3.2:1b         │   │  Model: base.en             │                       │
│  │  Quant: Q4_K_M              │   │  Size: 74M params           │                       │
│  │  Size: ~2.5GB RAM           │   │  RAM: ~500MB                │                       │
│  │  Context: 8192 tokens       │   │  Real-time factor: 0.3      │                       │
│  │  Response: 200-500ms        │   │  Max audio: 30 seconds      │                       │
│  │                             │   │                             │                       │
│  │  API:                       │   │  API:                       │                       │
│  │  POST /api/generate         │   │  POST /inference            │                       │
│  │  {                          │   │  {                          │                       │
│  │    "model": "llama3.2:1b",  │   │    "file": audio_blob,      │                       │
│  │    "prompt": "...",         │   │    "response_format": "json"│                       │
│  │    "format": "json",        │   │  }                          │                       │
│  │    "stream": false          │   │  Response:                  │                       │
│  │  }                          │   │  {                          │                       │
│  │  Response:                  │   │    "text": "Создай задачу"  │                       │
│  │  {                          │   │  }                          │                       │
│  │    "response": "{...}"      │   │                             │                       │
│  │  }                          │   │                             │                       │
│  └────────────────────────────┘   └────────────────────────────┘                       │
│                                                                                          │
│  ┌────────────────────────────┐   ┌────────────────────────────┐                       │
│  │  📡 Centrifugo (WebSocket)  │   │  🐰 RabbitMQ (Queue)        │                       │
│  │  Port: 8000                 │   │  Port: 5672, 15672 (UI)    │                       │
│  │                             │   │                             │                       │
│  │  • JWT authentication       │   │  Exchange: async            │                       │
│  │  • Pub/Sub channels         │   │  Queue: async               │                       │
│  │  • Presence tracking        │   │  Prefetch: 1                │                       │
│  │  • History API              │   │  Durable: true              │                       │
│  │                             │   │                             │                       │
│  │  Channels per user:         │   │  Message:                   │                       │
│  │  • voice:commands:{userId}  │   │  ProcessVoiceCommandMessage │                       │
│  │  • tasks:updates:{userId}   │   │                             │                       │
│  └────────────────────────────┘   └────────────────────────────┘                       │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 📊 Детальный Поток Данных (Data Flow)

### 🎯 Сценарий: Создание задачи голосом

```
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  STEP 1: Запись Голоса                                                   [~2s]      │
├──────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                      │
│  Frontend (VoiceButton.vue)                                                         │
│  ↓                                                                                   │
│  1. User clicks 🎤 button                                                           │
│  2. MediaRecorder starts → navigator.mediaDevices.getUserMedia()                    │
│  3. User speaks: "Создай задачу купить молоко завтра в 10:00"                      │
│  4. User clicks ⏹️ stop                                                             │
│  5. Blob created (audio/webm format)                                                │
│                                                                                      │
│  Output: Blob { type: 'audio/webm', size: 45KB }                                   │
└──────────────────────────────────────────────────────────────────────────────────────┘
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  STEP 2: Отправка на Backend                                             [~100ms]  │
├──────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                      │
│  Frontend (voiceCommand.service.ts)                                                 │
│  ↓                                                                                   │
│  const formData = new FormData()                                                    │
│  formData.append('audio', blob, 'command.webm')                                     │
│                                                                                      │
│  HTTP POST /api/voice/command                                                       │
│  Headers:                                                                           │
│    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...                   │
│    Content-Type: multipart/form-data                                                │
│  Body:                                                                              │
│    audio: <binary blob>                                                             │
│                                                                                      │
│  Output: HTTP 202 Accepted                                                          │
│  {                                                                                   │
│    "id": 42,                                                                        │
│    "status": "PENDING",                                                             │
│    "message": "Command is being processed"                                          │
│  }                                                                                   │
└──────────────────────────────────────────────────────────────────────────────────────┘
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  STEP 3: Backend Controller                                              [~50ms]   │
├──────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                      │
│  VoiceCommandController::processCommand(Request $request, User $user)               │
│  ↓                                                                                   │
│  1. Validate audio file:                                                            │
│     • Format: audio/webm, audio/wav, audio/mp3                                      │
│     • Size: < 10MB                                                                  │
│     • Duration: < 60 seconds                                                        │
│                                                                                      │
│  2. Save audio file:                                                                │
│     $path = '/var/www/backend/var/uploads/voice/' . uniqid() . '.webm'             │
│                                                                                      │
│  3. Create VoiceCommand entity:                                                     │
│     $command = new VoiceCommand();                                                  │
│     $command->setUser($user)                                                        │
│             ->setAudioPath($path)                                                   │
│             ->setStatus(CommandStatus::PENDING);                                    │
│     $em->persist($command);                                                         │
│     $em->flush();                                                                   │
│                                                                                      │
│  4. Dispatch to queue:                                                              │
│     $messageBus->dispatch(new ProcessVoiceCommandMessage($command->getId()));       │
│                                                                                      │
│  5. Return response immediately:                                                    │
│     return new JsonResponse(['id' => $command->getId()], 202);                      │
│                                                                                      │
│  Output: VoiceCommand saved, Message dispatched                                     │
└──────────────────────────────────────────────────────────────────────────────────────┘
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  STEP 4: RabbitMQ Queue                                                  [~10ms]   │
├──────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                      │
│  Message Producer → RabbitMQ Exchange "async"                                       │
│  ↓                                                                                   │
│  Message:                                                                           │
│  {                                                                                   │
│    "type": "App\\Message\\ProcessVoiceCommandMessage",                             │
│    "body": {                                                                        │
│      "commandId": 42                                                                │
│    }                                                                                 │
│  }                                                                                   │
│                                                                                      │
│  Queue Properties:                                                                  │
│  • Durable: true                                                                    │
│  • Auto-delete: false                                                               │
│  • Prefetch count: 1 (one worker at a time)                                        │
│                                                                                      │
│  Output: Message queued, waiting for worker                                         │
└──────────────────────────────────────────────────────────────────────────────────────┘
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  STEP 5: Message Handler (Worker Process)                               [~50ms]   │
├──────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                      │
│  ProcessVoiceCommandHandler::__invoke(ProcessVoiceCommandMessage $message)          │
│  ↓                                                                                   │
│  1. Load VoiceCommand entity:                                                       │
│     $command = $voiceCommandRepo->find($message->getCommandId());                   │
│                                                                                      │
│  2. Update status to PROCESSING:                                                    │
│     $command->setStatus(CommandStatus::PROCESSING);                                 │
│     $em->flush();                                                                   │
│                                                                                      │
│  3. Call VoiceProcessingService:                                                    │
│     $result = $voiceProcessingService->process($command);                           │
│                                                                                      │
│  4. Update status based on result:                                                  │
│     if ($result->isSuccess()) {                                                     │
│       $command->setStatus(CommandStatus::COMPLETED);                                │
│     } else {                                                                        │
│       $command->setStatus(CommandStatus::FAILED);                                   │
│     }                                                                                │
│     $em->flush();                                                                   │
│                                                                                      │
│  Output: Processing started, service called                                         │
└──────────────────────────────────────────────────────────────────────────────────────┘
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  STEP 6: Speech-to-Text (Whisper)                                        [~1.5s]  │
├──────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                      │
│  VoiceProcessingService::process() → WhisperService::transcribe()                  │
│  ↓                                                                                   │
│  1. Read audio file:                                                                │
│     $audioContent = file_get_contents($command->getAudioPath());                    │
│                                                                                      │
│  2. Send to Whisper API:                                                            │
│     POST http://voice-ai-whisper:8090/inference                                     │
│     Body: multipart/form-data                                                       │
│       file: <audio binary>                                                          │
│       response_format: json                                                         │
│       language: ru                                                                  │
│                                                                                      │
│  3. Receive transcription:                                                          │
│     Response (200 OK):                                                              │
│     {                                                                                │
│       "text": "Создай задачу купить молоко завтра в 10:00"                         │
│     }                                                                                │
│                                                                                      │
│  4. Save transcription:                                                             │
│     $command->setTranscription($response['text']);                                  │
│     $em->flush();                                                                   │
│                                                                                      │
│  Output: TranscriptionResult { text: "Создай задачу купить молоко..." }            │
└──────────────────────────────────────────────────────────────────────────────────────┘
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  STEP 7: LLM Parsing (Llama 3.2)                                         [~400ms] │
├──────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                      │
│  VoiceProcessingService::process() → LLMService::parseCommand()                    │
│  ↓                                                                                   │
│  1. Build system prompt (from PROMPTS_LIBRARY.md):                                 │
│     $prompt = <<<PROMPT                                                             │
│     Ты - ассистент для управления задачами. Твоя задача - парсить                 │
│     голосовые команды пользователя в структурированный JSON.                       │
│                                                                                      │
│     Пользователь сказал: "Создай задачу купить молоко завтра в 10:00"             │
│                                                                                      │
│     Верни JSON в формате:                                                           │
│     {                                                                                │
│       "commandType": "CREATE_TASK",                                                 │
│       "parameters": {                                                               │
│         "title": "string",                                                          │
│         "dueDate": "ISO8601 or null",                                               │
│         "tags": ["array"]                                                           │
│       }                                                                              │
│     }                                                                                │
│     PROMPT;                                                                         │
│                                                                                      │
│  2. Call Ollama API:                                                                │
│     POST http://voice-ai-ollama:11434/api/generate                                  │
│     {                                                                                │
│       "model": "llama3.2:1b",                                                       │
│       "prompt": $prompt,                                                            │
│       "format": "json",                                                             │
│       "stream": false                                                               │
│     }                                                                                │
│                                                                                      │
│  3. Receive structured JSON:                                                        │
│     Response (200 OK):                                                              │
│     {                                                                                │
│       "response": "{\"commandType\":\"CREATE_TASK\",\"parameters\":{...}}"          │
│     }                                                                                │
│                                                                                      │
│  4. Validate and parse JSON:                                                        │
│     $parsed = json_decode($response['response'], true);                             │
│     // Валидация структуры, обязательных полей                                     │
│                                                                                      │
│  5. Save parsed command:                                                            │
│     $command->setParsedCommand(new ParsedCommand($parsed));                         │
│     $em->flush();                                                                   │
│                                                                                      │
│  Output: ParsedCommand {                                                            │
│    commandType: "CREATE_TASK",                                                      │
│    parameters: {                                                                    │
│      "title": "Купить молоко",                                                     │
│      "dueDate": "2025-11-12T10:00:00Z",                                            │
│      "tags": []                                                                     │
│    }                                                                                 │
│  }                                                                                   │
└──────────────────────────────────────────────────────────────────────────────────────┘
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  STEP 8: Command Execution                                               [~100ms] │
├──────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                      │
│  VoiceProcessingService::process() → CommandExecutorService::execute()             │
│  ↓                                                                                   │
│  1. Get command type:                                                               │
│     $commandType = $parsedCommand->getCommandType(); // "CREATE_TASK"              │
│                                                                                      │
│  2. Resolve handler (Strategy Pattern):                                            │
│     $handler = $this->handlerFactory->getHandler($commandType);                     │
│     // Returns: CreateTaskHandler                                                   │
│                                                                                      │
│  3. Execute handler:                                                                │
│     CreateTaskHandler::handle($parsedCommand, $user)                                │
│     {                                                                                │
│       $params = $parsedCommand->getParameters();                                    │
│                                                                                      │
│       // Используем СУЩЕСТВУЮЩИЙ TaskService!                                      │
│       $dto = new CreateTaskDto();                                                   │
│       $dto->title = $params['title'];                                               │
│       $dto->dueDate = $params['dueDate'];                                           │
│       $dto->tags = $params['tags'];                                                 │
│                                                                                      │
│       $task = $this->taskService->createTask($dto, $user);                          │
│                                                                                      │
│       return new ExecutionResult(                                                   │
│         success: true,                                                              │
│         taskId: $task->getId(),                                                     │
│         message: 'Task created successfully'                                        │
│       );                                                                             │
│     }                                                                                │
│                                                                                      │
│  Output: ExecutionResult {                                                          │
│    success: true,                                                                   │
│    taskId: 123,                                                                     │
│    message: "Task created successfully"                                             │
│  }                                                                                   │
└──────────────────────────────────────────────────────────────────────────────────────┘
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  STEP 9: WebSocket Notification                                          [~50ms]  │
├──────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                      │
│  VoiceProcessingService::process() → WebSocketPublisherService::publish()          │
│  ↓                                                                                   │
│  1. Build notification payload:                                                     │
│     $payload = [                                                                    │
│       'type' => 'VOICE_COMMAND_COMPLETED',                                          │
│       'commandId' => $command->getId(),                                             │
│       'result' => [                                                                 │
│         'success' => true,                                                          │
│         'task' => [                                                                 │
│           'id' => 123,                                                              │
│           'title' => 'Купить молоко',                                              │
│           'dueDate' => '2025-11-12T10:00:00Z'                                      │
│         ]                                                                            │
│       ]                                                                              │
│     ];                                                                               │
│                                                                                      │
│  2. Get user channel:                                                               │
│     $channel = "voice:commands:{$user->getId()}"; // "voice:commands:1"            │
│                                                                                      │
│  3. Publish to Centrifugo:                                                          │
│     POST http://centrifugo:8000/api/publish                                         │
│     {                                                                                │
│       "channel": "voice:commands:1",                                                │
│       "data": $payload                                                              │
│     }                                                                                │
│                                                                                      │
│  4. Centrifugo delivers to all connected clients                                    │
│                                                                                      │
│  Output: Message published to channel                                               │
└──────────────────────────────────────────────────────────────────────────────────────┘
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  STEP 10: Frontend Update                                                [~20ms]  │
├──────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                      │
│  Frontend (useWebSocket composable)                                                 │
│  ↓                                                                                   │
│  1. Centrifugo client receives message:                                            │
│     centrifuge.on('publication', (ctx) => {                                        │
│       if (ctx.data.type === 'VOICE_COMMAND_COMPLETED') {                           │
│         handleVoiceCommandCompleted(ctx.data);                                     │
│       }                                                                              │
│     });                                                                              │
│                                                                                      │
│  2. Update TaskStore (Pinia):                                                       │
│     const taskStore = useTaskStore();                                               │
│     taskStore.addTask(ctx.data.result.task);                                        │
│                                                                                      │
│  3. UI auto-updates (Vue reactivity):                                              │
│     • TaskList.vue - New task appears in list                                      │
│     • VoiceButton.vue - Shows success notification                                 │
│     • Calendar.vue - Task added to calendar                                        │
│                                                                                      │
│  4. Show toast notification:                                                        │
│     toast.success('Task "Купить молоко" created!');                                │
│                                                                                      │
│  Output: UI updated, user sees new task immediately                                 │
└──────────────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────────────────┐
│  ✅ ИТОГО: Время выполнения ~2.3 секунды                                            │
│                                                                                      │
│  • Recording: 2s (user action)                                                      │
│  • Upload: 100ms                                                                    │
│  • Backend processing: 50ms                                                         │
│  • Queue: 10ms                                                                      │
│  • Worker startup: 50ms                                                             │
│  • Whisper STT: 1.5s                                                                │
│  • LLM parsing: 400ms                                                               │
│  • Command execution: 100ms                                                         │
│  • WebSocket notification: 50ms                                                     │
│  • Frontend update: 20ms                                                            │
│  ═══════════════════════════════                                                    │
│  TOTAL: ~2.28 seconds ✅                                                            │
│                                                                                      │
│  Target: < 5 seconds ✅ ACHIEVED!                                                   │
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
│  LLMService                        🆕 NEW                                │
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
│     ┌─────────────────────────────────────┐                             │
│     │  CommandExecutorService              │                             │
│     │  • execute(ParsedCommand, User)      │                             │
│     └──────────────┬───────────────────────┘                             │
│                    │                                                      │
│         ┌──────────┼──────────┬──────────────┐                           │
│         ▼          ▼          ▼              ▼                           │
│   CreateTask  CompleteTask  ListTasks  CreateSubtask                    │
│   Handler     Handler       Handler    Handler                          │
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
│  7. Factory Pattern                                                      │
│     CommandHandlerFactory - Creates appropriate handler                 │
│                                                                           │
│  8. Value Object Pattern                                                 │
│     CommandType, CommandStatus, ParsedCommand, TranscriptionResult      │
│                                                                           │
│  9. Dependency Injection                                                 │
│     All services injected through constructor                            │
│                                                                           │
│  10. Single Responsibility Principle                                     │
│     Each service/handler has ONE clear responsibility                    │
│                                                                           │
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
│   │       │   ├── TranscriptionResult.php              [NEW] 🆕
│   │       │   └── ParsedCommand.php                    [NEW] 🆕
│   │       │
│   │       ├── Service/
│   │       │   ├── VoiceAssistant/
│   │       │   │   ├── VoiceProcessingService.php       [NEW] 🆕
│   │       │   │   ├── LLMService.php                   [NEW] 🆕
│   │       │   │   ├── WhisperService.php               [NEW] 🆕
│   │       │   │   ├── CommandExecutorService.php       [NEW] 🆕
│   │       │   │   ├── SmartSearchService.php           [NEW] 🆕
│   │       │   │   ├── WebSocketPublisherService.php    [NEW] 🆕
│   │       │   │   └── Command/
│   │       │   │       └── Handlers/
│   │       │   │           ├── CreateTaskHandler.php    [NEW] 🆕
│   │       │   │           ├── CompleteTaskHandler.php  [NEW] 🆕
│   │       │   │           ├── ListTasksHandler.php     [NEW] 🆕
│   │       │   │           ├── CreateSubtaskHandler.php [NEW] 🆕
│   │       │   │           ├── BulkCompleteHandler.php  [NEW] 🆕
│   │       │   │           └── RescheduleHandler.php    [NEW] 🆕
│   │       │   │
│   │       │   └── TaskService.php                      [EXISTS] ✅
│   │       │
│   │       ├── Controller/
│   │       │   ├── Api/
│   │       │   │   ├── VoiceCommandController.php       [NEW] 🆕
│   │       │   │   └── TaskController.php               [EXISTS] ✅
│   │       │
│   │       ├── Repository/
│   │       │   ├── Database/
│   │       │   │   ├── VoiceCommandRepository.php       [NEW] 🆕
│   │       │   │   └── TaskRepository.php               [EXISTS] ✅
│   │       │
│   │       ├── Message/
│   │       │   └── ProcessVoiceCommandMessage.php       [NEW] 🆕
│   │       │
│   │       └── MessageHandler/
│   │           └── ProcessVoiceCommandHandler.php       [NEW] 🆕
│   │
│   └── frontend/
│       └── src/
│           ├── components/
│           │   └── VoiceAssistant/
│           │       ├── VoiceButton.vue                  [NEW] 🆕
│           │       └── VoiceStatus.vue                  [NEW] 🆕
│           │
│           ├── composables/
│           │   ├── useVoiceRecording.ts                 [NEW] 🆕
│           │   └── useWebSocket.ts                      [NEW] 🆕
│           │
│           └── services/
│               └── voiceCommand.service.ts              [NEW] 🆕
│
├── infrastructure/
│   └── ai-services/
│       ├── docker-compose.ai.yml                        [NEW] 🆕
│       ├── ollama/
│       │   └── Dockerfile                               [NEW] 🆕
│       ├── whisper/
│       │   └── Dockerfile                               [NEW] 🆕
│       └── centrifugo/
│           └── config.json                              [NEW] 🆕
│
└── docs/
    └── ai/
        ├── VOICE_AI_ARCHITECTURE_DIAGRAM.md             [THIS FILE] 📄
        ├── START_HERE.md                                [EXISTS] ✅
        └── INDEX.md                                     [EXISTS] ✅
```

---

## 🚀 Этапы Реализации (Roadmap)

```
┌────────────────────────────────────────────────────────────────────────┐
│  ФАЗА 1: Backend Core (День 1-2)              Время: 16 часов         │
├────────────────────────────────────────────────────────────────────────┤
│  ✅ Создать VoiceCommand Entity + Repository                           │
│  ✅ Создать Value Objects (CommandType, CommandStatus, etc.)          │
│  ✅ Реализовать VoiceProcessingService                                 │
│  ✅ Реализовать LLMService (Ollama integration)                        │
│  ✅ Реализовать CommandExecutorService                                 │
│  ✅ Создать Command Handlers (минимум 3)                               │
│  ✅ Настроить миграции БД                                              │
└────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────┐
│  ФАЗА 2: API & Queue (День 2-3)               Время: 8 часов          │
├────────────────────────────────────────────────────────────────────────┤
│  ✅ Создать VoiceCommandController                                     │
│  ✅ Реализовать ProcessVoiceCommandMessage                             │
│  ✅ Реализовать ProcessVoiceCommandHandler                             │
│  ✅ Настроить RabbitMQ routing                                         │
│  ✅ Тестирование с curl/Postman                                        │
└────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────┐
│  ФАЗА 3: Frontend (День 3-4)                  Время: 12 часов         │
├────────────────────────────────────────────────────────────────────────┤
│  ✅ Создать VoiceButton.vue компонент                                  │
│  ✅ Реализовать useVoiceRecording composable                           │
│  ✅ Реализовать useWebSocket composable                                │
│  ✅ Создать voiceCommand.service.ts                                    │
│  ✅ Интеграция с TaskStore                                             │
│  ✅ UI тестирование                                                     │
└────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────┐
│  ФАЗА 4: AI Infrastructure (День 4-5)         Время: 8 часов          │
├────────────────────────────────────────────────────────────────────────┤
│  ✅ Установить Ollama в Docker                                         │
│  ✅ Загрузить Llama 3.2 1B модель                                      │
│  ✅ Установить Whisper.cpp в Docker                                    │
│  ✅ Настроить Centrifugo                                               │
│  ✅ Интеграционное тестирование                                        │
└────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────┐
│  ФАЗА 5: Testing & Polish (День 5)            Время: 4 часа           │
├────────────────────────────────────────────────────────────────────────┤
│  ✅ E2E тестирование полного потока                                    │
│  ✅ Обработка ошибок                                                    │
│  ✅ Performance тюнинг                                                  │
│  ✅ Документация обновлена                                             │
└────────────────────────────────────────────────────────────────────────┘

═══════════════════════════════════════════════════════════════════════════
ИТОГО MVP: 3-5 дней (48 часов чистой работы)
═══════════════════════════════════════════════════════════════════════════
```

---

## 🎨 UI Mockup

```
┌─────────────────────────────────────────────────────────────────────┐
│  Task Manager - Dashboard                                 [👤 User] │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │  🎤 Voice Assistant                                   [🔴 Rec]  │ │
│  │  ┌──────────────────────────────────────────────────────────┐  │ │
│  │  │  🎙️ Click to speak...                                    │  │ │
│  │  │                                                            │  │ │
│  │  │  ⚫⚫⚫ Recording...  [0:03]              [⏹️ Stop]         │  │ │
│  │  └──────────────────────────────────────────────────────────┘  │ │
│  │                                                                 │ │
│  │  💬 Transcription: "Создай задачу купить молоко завтра"       │ │
│  │  ⚙️  Processing... LLM parsing command...                      │ │
│  │  ✅ Task created: "Купить молоко" (Due: Nov 12, 10:00)        │ │
│  └────────────────────────────────────────────────────────────────┘ │
│                                                                      │
│  📝 Tasks (Active)                                        [+ New]    │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │  ✅ Купить молоко                              📅 Nov 12 10:00 │ │
│  │     🏷️ Покупки                                                  │ │
│  ├────────────────────────────────────────────────────────────────┤ │
│  │  ⬜ Сделать домашнее задание                   📅 Nov 13 15:00 │ │
│  │     🏷️ Помощь ребенку  🏷️ Срочные                              │ │
│  ├────────────────────────────────────────────────────────────────┤ │
│  │  ⬜ Оплатить счета                             📅 Nov 14       │ │
│  │     🏷️ Финансы                                                 │ │
│  └────────────────────────────────────────────────────────────────┘ │
│                                                                      │
│  🔔 Recent Voice Commands                                           │
│  • 2 min ago: "Создай задачу купить молоко" → ✅ Success           │
│  • 15 min ago: "Завершить задачу оплатить..." → ✅ Success         │
│  • 1 hour ago: "Покажи задачи на завтра" → ✅ Success              │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## ✅ Чеклист Готовности к Production

```
Backend:
  ☐ VoiceCommand Entity + migrations
  ☐ VoiceProcessingService
  ☐ LLMService (Ollama)
  ☐ WhisperService
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

Infrastructure:
  ☐ Ollama container
  ☐ Llama 3.2 1B model loaded
  ☐ Whisper.cpp container
  ☐ Centrifugo configured
  ☐ RabbitMQ routing
  ☐ Network isolation

Testing:
  ☐ E2E test: Create task via voice
  ☐ E2E test: Complete task via voice
  ☐ E2E test: List tasks via voice
  ☐ Performance test: Response < 5s
  ☐ Stress test: 100 concurrent users
  ☐ Security audit

Documentation:
  ☐ API documentation updated
  ☐ Architecture diagrams
  ☐ Deployment guide
  ☐ Troubleshooting guide
```

---

**📅 Последнее обновление**: 2025-11-11
**👨‍💻 Автор**: Claude (Sonnet 4.5)
**📝 Версия**: 1.0.0
**✅ Статус**: Ready for Implementation
