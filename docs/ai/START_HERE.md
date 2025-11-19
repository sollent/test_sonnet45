# 🎯 НАЧНИ ЗДЕСЬ - Реализация Voice AI Assistant

> **Для AI (Opus 4.1, Sonnet 4.5)**: Это твоя стартовая точка. Прочитай это первым!

## 📍 Текущее Местоположение

Ты находишься в: `test_sonnet45/` проекте
Документация: `docs/ai/` директория

## 🎯 Миссия

Реализовать Voice AI Assistant для Task Manager:
- Пользователь говорит → AI понимает → Задача создана/обновлена
- Работает в веб-приложении + Telegram
- Использует локальную LLM (Llama 3.2) + Whisper STT

## 🚦 Чеклист Реализации

### ✅ Перед Началом

- [ ] Прочитай [INDEX.md](INDEX.md) - Полная карта документации
- [ ] Прочитай [PROMPTS_LIBRARY.md](REFERENCE/PROMPTS_LIBRARY.md) - ⭐ САМОЕ ВАЖНОЕ
- [ ] Проверь существующий код в `backend/src/` и `frontend/src/`

### 📝 Пошаговая Реализация

#### Фаза 1: Бэкенд (2-3 дня)

**День 1: Домен и Сервисы**
- [ ] 1.1: [Доменная Модель](02_BACKEND/01_DOMAIN_MODEL.md) - Создай VoiceCommand сущность
- [ ] 1.2: Запусти миграцию для создания таблиц
- [ ] 1.3: [Сервисы](02_BACKEND/02_SERVICES.md) - Реализуй 5 основных сервисов
- [ ] 1.4: Протестируй LLMService с Ollama (используй промпты из библиотеки!)

**День 2: Обработчики и API**
- [ ] 2.1: [Обработчики Команд](02_BACKEND/03_COMMAND_HANDLERS.md) - Начни с CreateTaskHandler
- [ ] 2.2: [API Endpoints](02_BACKEND/04_API_ENDPOINTS.md) - Создай 3 endpoints
- [ ] 2.3: Протестируй с curl/Postman

**День 3: Очередь и Обработка**
- [ ] 3.1: [Обработка Очереди](02_BACKEND/05_QUEUE_PROCESSING.md) - Настрой асинхронный worker
- [ ] 3.2: Протестируй end-to-end: API → Очередь → WebSocket

#### Фаза 2: Фронтенд (1-2 дня)

**День 4: Голосовой UI**
- [ ] 4.1: [Голосовая Запись](03_FRONTEND/01_VOICE_RECORDING.md) - Создай VoiceButton компонент
- [ ] 4.2: Реализуй WebSocket listener
- [ ] 4.3: Протестируй запись → бэкенд → отображение результата

#### Фаза 3: Инфраструктура (1 день)

**День 5: AI Сервисы**
- [ ] 5.1: [AI Сервисы](01_INFRASTRUCTURE/03_AI_SERVICES.md) - Установи Ollama + Whisper
- [ ] 5.2: Загрузи модель Qwen 2.5 3B
- [ ] 5.3: Протестируй транскрипцию Whisper
- [ ] 5.4: Интеграционный тест полного потока

## 🎯 MVP Границы (Что Строить)

### Обязательно (Ядро)
1. ✅ **Создать задачу** - "Создай задачу купить молоко завтра"
2. ✅ **Завершить задачу** - "Отметь задачу купить молоко как выполненную"
3. ✅ **Фильтровать задачи** - "Покажи все задачи на завтра"

### Желательно (Добавить при наличии времени)
4. ⚪ **Создать подзадачу** - "Добавь подзадачу к проекту"
5. ⚪ **Массовые операции** - "Заверши три задачи"

### Пропустить Пока
- ❌ Интеграция с Telegram (реализуй позже)
- ❌ Сложная аналитика
- ❌ Поддержка нескольких языков

## 📋 Обзор Структуры Кода

```
backend/src/
├── Entity/
│   └── VoiceCommand.php               [СОЗДАЙ ЭТО]
├── ValueObject/
│   ├── CommandType.php                [СОЗДАЙ ЭТО]
│   ├── CommandStatus.php              [СОЗДАЙ ЭТО]
│   ├── TranscriptionResult.php        [СОЗДАЙ ЭТО]
│   └── ParsedCommand.php              [СОЗДАЙ ЭТО]
├── Service/
│   └── VoiceAssistant/
│       ├── VoiceProcessingService.php [СОЗДАЙ ЭТО]
│       ├── LLMService.php             [СОЗДАЙ ЭТО - ИСПОЛЬЗУЙ ПРОМПТЫ!]
│       ├── CommandExecutorService.php [СОЗДАЙ ЭТО]
│       ├── WebSocketPublisherService.php
│       ├── SmartSearchService.php
│       └── Command/
│           └── Handlers/
│               ├── CreateTaskHandler.php
│               └── CompleteTaskHandler.php
├── Controller/
│   └── VoiceCommandController.php     [СОЗДАЙ ЭТО]
├── Message/
│   └── ProcessVoiceCommandMessage.php [СОЗДАЙ ЭТО]
└── MessageHandler/
    └── ProcessVoiceCommandHandler.php [СОЗДАЙ ЭТО]

frontend/src/
├── composables/
│   ├── useVoiceRecording.ts           [СОЗДАЙ ЭТО]
│   └── useWebSocket.ts                [СОЗДАЙ ЭТО]
├── services/
│   └── voiceCommand.service.ts        [СОЗДАЙ ЭТО]
└── components/
    └── VoiceAssistant/
        └── VoiceButton.vue             [СОЗДАЙ ЭТО]
```

## 🚨 Критические Файлы для Чтения

1. **[PROMPTS_LIBRARY.md](REFERENCE/PROMPTS_LIBRARY.md)** ⭐⭐⭐
   - Системный промпт для LLM
   - Структура JSON
   - Тестовые кейсы

2. **[Руководство по Сервисам](02_BACKEND/02_SERVICES.md)** ⭐⭐
   - Реализация LLMService
   - Интеграция с Ollama
   - Обработка ошибок

3. **[Доменная Модель](02_BACKEND/01_DOMAIN_MODEL.md)** ⭐
   - Структура сущностей
   - Value objects
   - Схема БД

## 💡 Быстрые Советы для AI

### При Реализации Сервисов

```php
// ВСЕГДА используй промпты из PROMPTS_LIBRARY.md
private function buildPrompt(string $text): string
{
    return <<<PROMPT
Ты - ассистент для управления задачами.
[... скопируй ТОЧНЫЙ промпт из библиотеки ...]
PROMPT;
}
```

### При Создании Обработчиков

```php
// Делай просто - используй существующий TaskService
public function handle(VoiceCommand $command): array
{
    $params = $command->getParsedCommand()->getParameters();

    // Используй СУЩЕСТВУЮЩИЙ TaskService - не создавай новые методы!
    $task = $this->taskService->createTask(
        $command->getUser(),
        $params['title'],
        $params['description'] ?? null
    );

    return ['success' => true, 'task_id' => $task->getId()];
}
```

### При Тестировании

```bash
# Тест LLM
curl -X POST http://localhost:11434/api/generate \
  -d '{"model":"qwen2.5:1.5b","prompt":"Создай задачу купить молоко"}'

# Тест API
curl -X POST http://localhost:8089/api/voice/command \
  -H "Authorization: Bearer TOKEN" \
  -d '{"text":"Создай задачу тест"}'
```

## 🚨 Распространенные Ошибки Которых Следует Избегать

### ❌ Не Делай Так

```php
// Не создавай новые методы репозитория если они не нужны
$this->taskRepository->findByVoiceDescription($text); // НЕТ!

// Не создавай сложную логику парсинга
$this->parseComplexCommand($text); // НЕТ!

// Не добавляй ненужные сервисы
class VoiceCommandAnalyticsService {} // НЕТ! (не для MVP)
```

### ✅ Делай Так Вместо

```php
// Используй существующие методы
$task = $this->searchService->findTaskByDescription($text, $user);

// Оставляй парсинг в LLM
$parsed = $this->llmService->parseCommand($text);

// Создавай только то, что нужно для MVP
```

## 📊 Критерии Успеха

Твоя реализация завершена когда:

1. ✅ Пользователь может нажать кнопку и записать голос
2. ✅ Голос транскрибирован в текст (Whisper)
3. ✅ Текст распарсен в JSON (LLM с промптами из библиотеки)
4. ✅ Команда выполнена (задача создана/завершена)
5. ✅ Пользователь видит результат через WebSocket
6. ✅ Работает для русского языка
7. ✅ Время отклика < 5 секунд

## 🆘 Если Застрял

### Проблема: LLM возвращает невалидный JSON
→ Проверь [PROMPTS_LIBRARY.md](REFERENCE/PROMPTS_LIBRARY.md)
→ Убедись что `format: 'json'` в запросе Ollama

### Проблема: Задача не найдена по голосу
→ Проверь SmartSearchService в [Сервисах](02_BACKEND/02_SERVICES.md)
→ Настрой порог сходства

### Проблема: WebSocket не обновляется
→ Проверь что Centrifugo запущен: `docker ps | grep centrifugo`
→ Проверь API ключ в .env

### Проблема: Очередь не обрабатывается
→ Проверь что worker запущен: `docker exec backend-php83 php bin/console messenger:consume async`
→ Проверь RabbitMQ: `docker ps | grep rabbitmq`

## 📚 Индекс Документации

Полная карта: [INDEX.md](INDEX.md)

Быстрые ссылки:
- Бэкенд: [02_BACKEND/](02_BACKEND/)
- Фронтенд: [03_FRONTEND/](03_FRONTEND/)
- Инфраструктура: [01_INFRASTRUCTURE/](01_INFRASTRUCTURE/)
- Справочник: [REFERENCE/](REFERENCE/)

## ✅ Финальный Чеклист

Перед отметкой как завершено:

- [ ] Вся Фаза 1 (Бэкенд) реализована
- [ ] Вся Фаза 2 (Фронтенд) реализована
- [ ] AI сервисы (Ollama, Whisper) установлены
- [ ] End-to-end тест проходит
- [ ] Можно создать задачу через голос
- [ ] Можно завершить задачу через голос
- [ ] Обновления WebSocket работают
- [ ] Русский язык работает
- [ ] Код следует принципам SOLID (проверь документацию)

## 🎯 Помни

**Для MVP:**
- ✅ Простое и рабочее > Сложное и сломанное
- ✅ Используй существующие сервисы (TaskService)
- ✅ Следуй библиотеке промптов точно
- ✅ Тестируй каждую фазу перед переходом дальше

**Приоритет:**
1. Заставь это работать
2. Сделай это правильно (SOLID)
3. Сделай это быстро (позже)

---

**Удачи! Начни с [INDEX.md](INDEX.md) для полного контекста.**
