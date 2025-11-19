# 🎤 Voice AI Assistant - План Реализации

> **Руководство Быстрого Старта**: Упрощенный пошаговый план на основе полного анализа MVP документации

---

## 📋 Что Мы Строим

**MVP Voice AI Assistant** для Менеджера Задач:
- Пользователь говорит команду на русском → Whisper транскрибирует → Llama парсит → Задача создана/обновлена
- Обновления в реальном времени через WebSocket (Centrifugo)
- Веб-интерфейс с кнопкой голосовой записи
- Асинхронная обработка с очередью RabbitMQ

**Технологический Стек (Оптимизирован для VPS с 4GB RAM):**
- LLM: Llama 3.2 1B через Ollama (помещается в 2-3GB памяти)
- STT: Базовая модель Whisper (легковесная, точная для русского)
- WebSocket: Centrifugo + Redis
- Очередь: RabbitMQ (уже в проекте)
- Бэкенд: Symfony 7.1 + PHP 8.3
- Фронтенд: Vue.js 3 + Composition API

---

## 🚀 Шаги Реализации (5-Дневный MVP План)

### Шаг 0: Сначала Прочитай Документацию! ⭐

**КРИТИЧНО - Начни отсюда (15 мин):**
1. [`docs/ai/START_HERE.md`](START_HERE.md) - Быстрый обзор + чеклист
2. [`docs/ai/REFERENCE/PROMPTS_LIBRARY.md`](REFERENCE/PROMPTS_LIBRARY.md) - **⭐ САМОЕ КРИТИЧНОЕ** - Скопируй точные промпты для Llama!
3. [`docs/ai/INDEX.md`](INDEX.md) - Полная карта навигации

**Почему PROMPTS_LIBRARY критично:**
- Содержит точные системные промпты для Llama 3.2 1B
- Определяет структуру JSON ответа (action, parameters, confidence)
- Включает 7 протестированных шаблонов команд на русском
- Без этих промптов LLM будет возвращать мусор!

---

### День 1: Доменный Слой Бэкенда (Утро, 2-3ч)

**Цель:** Создать схему базы данных для голосовых команд

**Прочитай сначала:**
- 📖 [`docs/ai/02_BACKEND/01_DOMAIN_MODEL.md`](02_BACKEND/01_DOMAIN_MODEL.md) - Полные спецификации сущностей

**Порядок реализации:**

**1. Value Objects (30 мин)** - Простые енумы и структуры данных:
```
backend/src/ValueObject/
├── CommandType.php           # Enum: VOICE_AUDIO, VOICE_TEXT
├── CommandStatus.php         # Enum: PENDING, PROCESSING, COMPLETED, FAILED
├── TranscriptionResult.php   # Data: text, language, confidence
└── ParsedCommand.php         # Data: action, parameters, confidence
```

**2. Основная Сущность (1ч)** - VoiceCommand с переходами состояний:
```
backend/src/Entity/VoiceCommand.php
```

**Ключевые моменты:**
- Используй UUID (уже в проекте)
- Связь ManyToOne с сущностью User
- Переходы статусов: `pending → processing → executing → completed|failed`
- Хранить: rawText, transcription, parsedCommand, result, error

**3. Миграция (30 мин)**:
```bash
# Создать миграцию
docker exec backend-php83 php bin/console make:migration

# Просмотреть SQL
docker exec backend-php83 cat backend/migrations/VersionXXX.php

# Применить
docker exec backend-php83 php bin/console doctrine:migrations:migrate --no-interaction
```

**Проверка:**
```bash
# Проверить что таблица создана
docker exec backend-psql16 psql -U user -d backend-app -c "\d voice_commands"
```

---

### День 1: Сервисы Бэкенда (День, 3-4ч)

**Цель:** Реализовать 5 основных сервисов (SOLID архитектура)

**Прочитай сначала:**
- 📖 [`docs/ai/02_BACKEND/02_SERVICES.md`](02_BACKEND/02_SERVICES.md) - Паттерны сервисного слоя
- ⭐ [`docs/ai/REFERENCE/PROMPTS_LIBRARY.md`](REFERENCE/PROMPTS_LIBRARY.md) - **Скопируй точные промпты!**

**Порядок реализации:**

**1. LLMService (1.5ч)** - ⚠️ САМЫЙ ВАЖНЫЙ:
```php
backend/src/Service/VoiceAssistant/LLMService.php

Критично:
- Скопируй системный промпт ТОЧНО из PROMPTS_LIBRARY.md
- Используй Ollama HTTP client (Symfony\Contracts\HttpClient\HttpClientInterface)
- Запрос: {"model": "mistral:7b", "prompt": "...", "format": "json", "options": {"temperature": 0.3}}
- Парси JSON ответ
- Валидируй структуру: {action, parameters, confidence}
- Резервный парсинг если JSON невалиден
```

**2. SmartSearchService (45 мин)** - Найти задачи по нечеткому тексту:
```php
backend/src/Service/VoiceAssistant/SmartSearchService.php

Использует триграммное сходство PostgreSQL:
- SELECT * FROM tasks WHERE similarity(title, ?) > 0.3
- Order by similarity DESC
- Требуется расширение pg_trgm (проверь установлено!)
```

**3. WebSocketPublisherService (30 мин)** - Интеграция с Centrifugo:
```php
backend/src/Service/VoiceAssistant/WebSocketPublisherService.php

Публикует события в:
- Канал: "voice:user#{userId}"
- События: command.received, transcribed, parsed, executing, completed, failed
```

**4. CommandExecutorService (45 мин)** - Оркестрирует обработчики:
```php
backend/src/Service/VoiceAssistant/CommandExecutorService.php

Делегирует CommandHandlers (создадим следующими)
```

**5. VoiceProcessingService (30 мин)** - Точка входа:
```php
backend/src/Service/VoiceAssistant/VoiceProcessingService.php

Простой координатор, отправляет в очередь
```

**Сначала протестируй Ollama!**
```bash
# Проверь что Ollama запущена (должна быть установлена отдельно - см. доку Infrastructure)
curl http://localhost:11434/api/tags

# Тест с русской командой
curl -X POST http://localhost:11434/api/generate -d '{
  "model": "mistral:7b",
  "prompt": "Ты - ассистент для задач. Конвертируй эту русскую команду в JSON: Создай задачу купить молоко завтра",
  "format": "json",
  "stream": false
}'
```

---

### День 2: Обработчики Команд (Утро, 2ч)

**Цель:** Реализовать 3 основных обработчика команд (границы MVP)

**Прочитай сначала:**
- 📖 [`docs/ai/02_BACKEND/03_COMMAND_HANDLERS.md`](02_BACKEND/03_COMMAND_HANDLERS.md) - Паттерн обработчика

**Порядок реализации:**

**1. Интерфейс (15 мин)**:
```php
backend/src/Service/VoiceAssistant/Command/CommandHandlerInterface.php

interface CommandHandlerInterface {
    public function supports(string $action): bool;
    public function handle(VoiceCommand $command): array;
}
```

**2. CreateTaskHandler (1ч)** - Самый распространенный:
```php
backend/src/Service/VoiceAssistant/Command/Handlers/CreateTaskHandler.php

⚠️ КРИТИЧНО: Используй существующий TaskService!
- supports(): return $action === 'create_task';
- handle(): Извлечь параметры, вызвать taskService->createTask()
- Параметры: title (required), description, dueDate, priority, tags
- Возвращает: ['success' => true, 'task_id' => $id, 'message' => '...']
```

**3. CompleteTaskHandler (30 мин)** - С умным поиском:
```php
backend/src/Service/VoiceAssistant/Command/Handlers/CompleteTaskHandler.php

Использует SmartSearchService для поиска задачи по описанию:
- Парс: task_id ИЛИ task_description
- Если task_id: прямой поиск
- Если description: используй SmartSearchService->findTask()
- Вызвать taskService->completeTask($taskId)
```

**4. FilterTasksHandler (30 мин)** - Построитель запросов:
```php
backend/src/Service/VoiceAssistant/Command/Handlers/FilterTasksHandler.php

Строит фильтр из параметров:
- status, priority, tags, date_from, date_to
- Используй TaskRepository->findByFilters()
- Возвращает список задач (макс 20)
```

**⚠️ НЕ ДЕЛАЙ:**
```php
// ❌ Не создавай новые методы репозитория
$this->taskRepository->createTaskFromVoice($data);

// ✅ Используй существующий TaskService
$this->taskService->createTask($user, $title, $description);
```

---

### День 2: API Endpoints (День, 1.5ч)

**Цель:** Создать 3 REST endpoint для голосовых команд

**Прочитай сначала:**
- 📖 [`docs/ai/02_BACKEND/04_API_ENDPOINTS.md`](02_BACKEND/04_API_ENDPOINTS.md) - Спецификация API

**Реализация:**

**1. Контроллер (1ч)**:
```php
backend/src/Controller/VoiceCommandController.php

#[Route('/api/voice', name: 'api_voice_')]
class VoiceCommandController extends AbstractController
{
    // POST /api/voice/command - Отправить аудио или текст
    #[Route('/command', name: 'submit', methods: ['POST'])]
    public function submitCommand(Request $request): JsonResponse
    {
        // Принять: аудио файл ИЛИ текст
        // Создать сущность VoiceCommand
        // Отправить в очередь (возвращает 202 Accepted)
        // Вернуть: {command_id, status: 'queued'}
    }

    // GET /api/voice/command/{id} - Получить статус
    #[Route('/command/{id}', name: 'status', methods: ['GET'])]
    public function getStatus(string $id): JsonResponse
    {
        // Найти VoiceCommand по UUID
        // Вернуть: {id, status, result, created_at}
    }

    // GET /api/voice/history - Получить недавние команды
    #[Route('/history', name: 'history', methods: ['GET'])]
    public function getHistory(Request $request): JsonResponse
    {
        // Query: ?days=7&limit=20
        // Вернуть историю команд пользователя
    }
}
```

**2. Конфигурация маршрутов (авто-обнаруживается Symfony)**

**3. Тест с curl (30 мин)**:
```bash
# Сначала получи JWT токен
TOKEN=$(curl -X POST http://localhost:8089/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"test123"}' \
  | jq -r '.token')

# Тест текстовой команды
curl -X POST http://localhost:8089/api/voice/command \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"text":"Создай задачу купить молоко","source":"web"}' | jq

# Проверь статус
COMMAND_ID="..." # из предыдущего ответа
curl -X GET "http://localhost:8089/api/voice/command/$COMMAND_ID" \
  -H "Authorization: Bearer $TOKEN" | jq

# Получи историю
curl -X GET "http://localhost:8089/api/voice/history?days=7" \
  -H "Authorization: Bearer $TOKEN" | jq
```

---

### День 3: Обработка Очереди (Утро, 1.5ч)

**Цель:** Асинхронная обработка голосовых команд с RabbitMQ

**Прочитай сначала:**
- 📖 [`docs/ai/02_BACKEND/05_QUEUE_PROCESSING.md`](02_BACKEND/05_QUEUE_PROCESSING.md) - Настройка очереди

**Реализация:**

**1. Класс сообщения (10 мин)**:
```php
backend/src/Message/ProcessVoiceCommandMessage.php

Простое DTO:
class ProcessVoiceCommandMessage {
    public function __construct(private string $commandId) {}
    public function getCommandId(): string { return $this->commandId; }
}
```

**2. Обработчик сообщений (1ч)** - Основная логика обработки:
```php
backend/src/MessageHandler/ProcessVoiceCommandHandler.php

#[AsMessageHandler]
class ProcessVoiceCommandHandler {
    public function __invoke(ProcessVoiceCommandMessage $message): void {
        // 1. Найти VoiceCommand по ID
        // 2. Начать обработку
        // 3. Транскрибировать если аудио (вызвать Whisper HTTP API)
        // 4. Парсить с LLM (вызвать LLMService)
        // 5. Выполнить команду (вызвать CommandExecutorService)
        // 6. Отметить завершенным
        // 7. Отправить обновления WebSocket на каждом шаге
        // 8. Обработать ошибки gracefully
    }
}
```

**3. Настроить Messenger (20 мин)**:
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

**4. Запустить worker:**
```bash
# Запустить consumer
docker exec backend-php83 php bin/console messenger:consume async -vv

# Проверить здоровье очереди
docker exec backend-rabbitmq rabbitmqctl list_queues

# Проверить логи
docker logs -f backend-php83 | grep "ProcessVoiceCommandHandler"
```

---

### День 3-4: Голосовой UI Фронтенда (День + Утро, 3ч)

**Цель:** Кнопка голосовой записи с обновлениями в реальном времени

**Прочитай сначала:**
- 📖 [`docs/ai/03_FRONTEND/01_VOICE_RECORDING.md`](03_FRONTEND/01_VOICE_RECORDING.md) - Полная реализация

**Порядок реализации:**

**1. Сервис Голосовых Команд (30 мин)**:
```typescript
frontend/src/services/voiceCommand.service.ts

class VoiceCommandService {
    async submitAudioCommand(audioBlob: Blob): Promise<string> {
        // FormData с аудио файлом
        // POST /api/voice/command
        // Возвращает command_id
    }

    async submitTextCommand(text: string): Promise<string> {
        // JSON с текстом
        // POST /api/voice/command
    }

    async getCommandStatus(commandId: string) {
        // GET /api/voice/command/{id}
    }
}
```

**2. Composable Голосовой Записи (45 мин)**:
```typescript
frontend/src/composables/useVoiceRecording.ts

export function useVoiceRecording() {
    // Использует MediaRecorder API
    // Состояния записи: idle, recording, processing
    // startRecording(), stopRecording(), sendTextCommand()
    // Возвращает: {isRecording, isProcessing, error, ...}
}
```

**3. Composable WebSocket (45 мин)**:
```typescript
frontend/src/composables/useWebSocket.ts

export function useWebSocket() {
    // Использует клиентскую библиотеку Centrifuge
    // Подписка на: "voice:user#{userId}"
    // Слушать события: transcribed, parsed, completed, failed
    // emit() события в компонент
}
```

**4. Компонент Голосовой Кнопки (45 мин)**:
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
    // Показать сообщение успеха
})
</script>
```

**5. Добавить в основной view (15 мин)**:
```vue
<!-- В твоем view задач -->
<VoiceButton />
```

**Тест в браузере (Chrome/Firefox):**
1. Открыть консоль разработчика (F12)
2. Кликнуть микрофон → Разрешить разрешение
3. Сказать: "Создай задачу купить молоко"
4. Кликнуть стоп
5. Следить в консоли за событиями WebSocket
6. Проверить список задач на новую задачу

---

### День 4-5: Настройка Инфраструктуры (День + Полный День, 4ч)

**Цель:** Установить и настроить AI сервисы (Ollama, Whisper, Centrifugo)

**Прочитай сначала:**
- 📖 [`docs/ai/01_INFRASTRUCTURE/01_SETUP.md`](01_INFRASTRUCTURE/01_SETUP.md) - Системные требования
- 📖 [`docs/ai/01_INFRASTRUCTURE/03_AI_SERVICES.md`](01_INFRASTRUCTURE/03_AI_SERVICES.md) - ⚠️ КРИТИЧНО - Полное руководство по настройке

**Порядок Настройки:**

**1. Ollama + Llama 3.2 (1ч)**:
```bash
# Установить Ollama
curl -fsSL https://ollama.com/install.sh | sh

# Загрузить модель Llama 3.2 1B (~ 2GB скачивание)
ollama pull mistral:7b

# Запустить Ollama сервер
ollama serve

# Тест
curl http://localhost:11434/api/tags
# Должен показать: {"models": [{"name": "mistral:7b", ...}]}

# Тест русской команды
curl -X POST http://localhost:11434/api/generate -d '{
  "model": "mistral:7b",
  "prompt": "Конвертируй в JSON: Создай задачу купить молоко",
  "format": "json",
  "stream": false
}'
```

**2. Сервис Whisper STT (2ч)**:

**Вариант A - Использование Docker (Рекомендуется)**:
```bash
# Собрать контейнер Whisper
cd infrastructure/ai-services
docker build -f configs/whisper/Dockerfile -t voice-ai/whisper:latest .

# Запустить сервис Whisper
docker run -d \
  --name voice-ai-whisper \
  -p 8090:8090 \
  -e MODEL_SIZE=base \
  -e LANGUAGE=ru \
  voice-ai/whisper:latest

# Тест
curl http://localhost:8090/health
```

**Вариант B - Прямой Python**:
```bash
# Установить зависимости
pip install openai-whisper fastapi uvicorn python-multipart

# Скопировать whisper_api.py из docs/ai/01_INFRASTRUCTURE/03_AI_SERVICES.md

# Запустить
uvicorn whisper_api:app --host 0.0.0.0 --port 8090
```

**3. Centrifugo WebSocket (1ч)**:
```bash
# Сгенерировать секреты
CENTRIFUGO_SECRET=$(openssl rand -hex 32)
CENTRIFUGO_API_KEY=$(openssl rand -hex 32)

# Создать конфиг
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

# Запустить Centrifugo
docker run -d \
  --name voice-ai-centrifugo \
  -p 8000:8000 \
  -v $(pwd)/centrifugo.json:/centrifugo/config.json \
  centrifugo/centrifugo:v5 \
  centrifugo -c /centrifugo/config.json

# Тест
curl http://localhost:8000/health
```

**4. Интеграционный Тест (30 мин)**:
```bash
# Тест полного потока
python3 scripts/integration_test.py

# Должен показать:
# ✅ Ollama запущена
# ✅ Whisper здоров
# ✅ Centrifugo здоров
```

**Добавить в .env:**
```env
OLLAMA_URL=http://localhost:11434
WHISPER_URL=http://localhost:8090
CENTRIFUGO_URL=http://localhost:8000
CENTRIFUGO_API_KEY=<твой-api-key>
CENTRIFUGO_SECRET=<твой-секрет>
```

---

## 🎯 Критерии Успеха MVP

**Твой MVP ГОТОВ когда все это работает:**

1. ✅ Пользователь кликает кнопку голоса → начинается запись
2. ✅ Пользователь говорит русскую команду → останавливает запись
3. ✅ Аудио отправлено на бэкенд → в очереди (ответ 202)
4. ✅ Whisper транскрибирует аудио в текст
5. ✅ Llama парсит текст в JSON (используя промпты из библиотеки!)
6. ✅ Команда выполнена (задача создана/завершена/отфильтрована)
7. ✅ WebSocket отправляет обновления в реальном времени
8. ✅ Пользователь видит результат в UI (< 5 секунд всего)
9. ✅ Работает надежно для 3 основных команд:
   - "Создай задачу купить молоко завтра"
   - "Отметь задачу купить молоко как выполненную"
   - "Покажи все задачи на завтра"

---

## ⚠️ Критично Помнить

### 1. ⭐ ВСЕГДА Используй Точные Промпты из Библиотеки
**Местоположение:** [`docs/ai/REFERENCE/PROMPTS_LIBRARY.md`](REFERENCE/PROMPTS_LIBRARY.md)

**Почему критично:** Без точных промптов Llama будет возвращать мусор или некорректный JSON!

```php
// ❌ НЕ создавай свои промпты
private function buildPrompt(string $text): string {
    return "Парси эту команду: " . $text; // НЕ БУДЕТ РАБОТАТЬ!
}

// ✅ СКОПИРУЙ ТОЧНЫЙ промпт из PROMPTS_LIBRARY.md
private function buildPrompt(string $text, array $context): string {
    $date = $context['date'] ?? date('Y-m-d');
    $time = $context['time'] ?? date('H:i');

    return <<<PROMPT
Ты - ассистент для управления задачами для русскоязычных пользователей.
Твоя задача: Конвертировать голосовые команды в валидный JSON.

ВАЖНЫЕ ПРАВИЛА:
1. ВСЕГДА возвращай ТОЛЬКО валидный JSON (никакого дополнительного текста!)
2. Понимай команды на русском языке
3. Извлекай: действие (action), параметры (parameters), уверенность (confidence)
4. Текущая дата: {$date}, время: {$time}

[... полный промпт из библиотеки - 50+ строк ...]
PROMPT;
}
```

### 2. Держи Просто - Используй Существующие Сервисы
```php
// ❌ НЕ создавай новые методы репозитория
$this->taskRepository->findByVoiceDescription($text); // НЕТ!
$this->taskRepository->createFromVoiceCommand($data); // НЕТ!

// ✅ Используй существующие сервисы
$task = $this->smartSearchService->findTask($text, $user);
$task = $this->taskService->createTask($user, $title, $description);
```

### 3. Не Обходи TaskService
```php
// ❌ НЕ обходи TaskService
$task = new Task();
$task->setTitle($params['title']);
$this->entityManager->persist($task); // НЕТ!

// ✅ Используй TaskService (уже имеет всю логику!)
$task = $this->taskService->createTask(
    $user,
    $params['title'],
    $params['description'] ?? null,
    $params['dueDate'] ?? null,
    $params['priority'] ?? null
);
```

### 4. Тестируй Каждую Фазу Перед Переходом Дальше
```bash
# После Фазы 1 (Домен)
docker exec backend-psql16 psql -U user -d backend-app -c "\d voice_commands"

# После Фазы 2 (Сервисы)
curl -X POST http://localhost:11434/api/generate -d '{"model":"mistral:7b","prompt":"тест"}'

# После Фазы 3 (API)
curl -X POST http://localhost:8089/api/voice/command -H "Authorization: Bearer TOKEN" -d '{"text":"тест"}'

# После Фазы 4 (Очередь)
docker exec backend-php83 php bin/console messenger:consume async --limit=1

# После Фазы 5 (Фронтенд)
# Открой браузер, кликни кнопку, проверь консоль

# После Фазы 6 (Инфраструктура)
curl http://localhost:11434/api/tags
curl http://localhost:8090/health
curl http://localhost:8000/health
```

---

## 🚨 Решение Проблем

### Проблема: LLM возвращает невалидный JSON
→ Проверь [`PROMPTS_LIBRARY.md`](REFERENCE/PROMPTS_LIBRARY.md)
→ Убедись что `format: 'json'` в запросе Ollama
→ Проверь `temperature: 0.3` (не слишком высоко!)

### Проблема: Задача не найдена по голосу
→ Проверь `SmartSearchService` в [`02_SERVICES.md`](02_BACKEND/02_SERVICES.md)
→ Настрой порог сходства (по умолчанию: 0.3)
→ Проверь что расширение триграмм PostgreSQL установлено

### Проблема: WebSocket не обновляется
→ Проверь что Centrifugo запущен: `docker ps | grep centrifugo`
→ Убедись в API ключе в `.env`
→ Проверь консоль браузера на ошибки WebSocket

### Проблема: Очередь не обрабатывается
→ Проверь что worker запущен: `docker exec backend-php83 php bin/console messenger:consume async`
→ Проверь RabbitMQ: `docker ps | grep rabbitmq`
→ Посмотри логи: `docker logs -f backend-php83`

---

## 📊 График Реализации 5 Дней

```
День 1 (6ч):
 Утро [3ч]  → Доменный Слой (Сущность + Value Objects + Миграция)
 День [3ч]  → Сервисы (LLM, SmartSearch, WebSocket, Executor, Processing)

День 2 (6ч):
 Утро [2ч]  → Обработчики Команд (Интерфейс + 3 обработчика)
 День [2ч]  → API Endpoints (Контроллер + 3 endpoints + тестирование)
 Вечер [2ч] → Очередь (Message + Handler + Config)

День 3 (6ч):
 Утро [2ч]  → Тестирование и отладка очереди
 День [3ч]  → Фронтенд (Сервисы + Composables)
 Вечер [1ч] → Компонент VoiceButton фронтенда

День 4 (6ч):
 Утро [2ч]  → Интеграция и тестирование фронтенда
 День [4ч]  → Инфраструктура (Ollama + Whisper)

День 5 (4ч):
 Утро [2ч]  → Инфраструктура (Centrifugo + Redis)
 День [2ч]  → End-to-end тестирование + исправление багов

ВСЕГО: ~28 часов сфокусированной работы
```

**Реалистичный MVP:** 3-5 дней для solo разработчика с помощью AI

---

## 📚 Навигация по Документации

### Начни Здесь
- 📄 [`docs/ai/START_HERE.md`](START_HERE.md) - Быстрый обзор + полный чеклист
- 📄 [`docs/ai/INDEX.md`](INDEX.md) - Полная карта навигации

### Бэкенд (5 документов)
1. [`docs/ai/02_BACKEND/01_DOMAIN_MODEL.md`](02_BACKEND/01_DOMAIN_MODEL.md) - Сущность VoiceCommand
2. [`docs/ai/02_BACKEND/02_SERVICES.md`](02_BACKEND/02_SERVICES.md) - 5 основных сервисов
3. [`docs/ai/02_BACKEND/03_COMMAND_HANDLERS.md`](02_BACKEND/03_COMMAND_HANDLERS.md) - Паттерн Command
4. [`docs/ai/02_BACKEND/04_API_ENDPOINTS.md`](02_BACKEND/04_API_ENDPOINTS.md) - REST API
5. [`docs/ai/02_BACKEND/05_QUEUE_PROCESSING.md`](02_BACKEND/05_QUEUE_PROCESSING.md) - Асинхронная очередь

### Фронтенд (1 документ)
- [`docs/ai/03_FRONTEND/01_VOICE_RECORDING.md`](03_FRONTEND/01_VOICE_RECORDING.md) - Полная реализация UI

### Инфраструктура (4 документа)
1. [`docs/ai/01_INFRASTRUCTURE/01_SETUP.md`](01_INFRASTRUCTURE/01_SETUP.md) - Системные требования
2. [`docs/ai/01_INFRASTRUCTURE/02_DOCKER.md`](01_INFRASTRUCTURE/02_DOCKER.md) - Конфиги Docker (опционально)
3. [`docs/ai/01_INFRASTRUCTURE/03_AI_SERVICES.md`](01_INFRASTRUCTURE/03_AI_SERVICES.md) - ⚠️ Ollama + Whisper + Centrifugo
4. [`docs/ai/01_INFRASTRUCTURE/04_SECURITY.md`](01_INFRASTRUCTURE/04_SECURITY.md) - Безопасность (опционально для MVP)

### Справочник (Критично!)
- ⭐⭐⭐ [`docs/ai/REFERENCE/PROMPTS_LIBRARY.md`](REFERENCE/PROMPTS_LIBRARY.md) - **САМОЕ КРИТИЧНОЕ - LLM промпты**
- [`docs/ai/REFERENCE/TESTING_STRATEGY.md`](docs/ai/REFERENCE/TESTING_STRATEGY.md) - Руководство по тестированию (опционально)

---

## ✅ Финальный Чеклист

Перед отметкой как завершено:

- [ ] Все сервисы бэкенда реализованы
- [ ] Все обработчики команд работают
- [ ] API endpoints протестированы с curl
- [ ] Queue worker запущен
- [ ] Кнопка голоса фронтенда работает
- [ ] Обновления WebSocket получены
- [ ] Ollama + Llama 3.2 установлены
- [ ] Транскрипция Whisper работает
- [ ] End-to-end тест проходит (голос → создана задача)
- [ ] Русский язык работает
- [ ] Код следует принципам SOLID

---

**Удачи! Начни с [`docs/ai/START_HERE.md`](START_HERE.md) для детальных инструкций.**

---

**Создано:** 2025-01-08
**Для:** Реализация MVP Voice AI Assistant
**Технологический Стек:** Symfony + Vue.js + Ollama + Whisper + Centrifugo
