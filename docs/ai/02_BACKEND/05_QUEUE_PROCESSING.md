# ⚡ Фаза 2.5: Обработка Очереди - Простая Асинхронность

> **Для AI**: Супер простая настройка RabbitMQ для асинхронной обработки голоса.

## 🎯 Зачем Очередь?

Обработка голоса медленная (2-5 секунд). Очередь позволяет:
- ✅ Вернуть ответ немедленно (202 Accepted)
- ✅ Обработать в фоне
- ✅ Отправить WebSocket обновления когда готово

---

## 📦 Шаг 1: Класс Сообщения

**AI**: Создайте сообщение для очереди:

```php
<?php
// File: apps/backend/src/Message/ProcessVoiceCommandMessage.php

namespace App\Message;

class ProcessVoiceCommandMessage
{
    public function __construct(
        private string $commandId
    ) {}

    public function getCommandId(): string
    {
        return $this->commandId;
    }
}
```

---

## 🔧 Шаг 2: Обработчик Сообщения

**AI**: Это выполняется асинхронно в воркере:

```php
<?php
// File: apps/backend/src/MessageHandler/ProcessVoiceCommandHandler.php

namespace App\MessageHandler;

use App\Message\ProcessVoiceCommandMessage;
use App\Repository\VoiceCommandRepository;
use App\Service\VoiceAssistant\LLMService;
use App\Service\VoiceAssistant\CommandExecutorService;
use App\Service\VoiceAssistant\WebSocketPublisherService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class ProcessVoiceCommandHandler
{
    public function __construct(
        private VoiceCommandRepository $repository,
        private LLMService $llmService,
        private CommandExecutorService $executor,
        private WebSocketPublisherService $wsPublisher,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $whisperUrl = 'http://localhost:8090'
    ) {}

    public function __invoke(ProcessVoiceCommandMessage $message): void
    {
        $commandId = $message->getCommandId();
        $command = $this->repository->find($commandId);

        if (!$command) {
            $this->logger->error('Command not found', ['id' => $commandId]);
            return;
        }

        try {
            // 1. Start processing
            $command->startProcessing();
            $this->repository->save($command, true);

            $this->wsPublisher->sendCommandUpdate(
                $command->getUser()->getId()->toRfc4122(),
                $commandId,
                'processing'
            );

            // 2. Transcribe if audio (skip if text)
            if ($command->getType()->requiresTranscription()) {
                $transcription = $this->transcribeAudio($command->getAudioFilePath());
                $command->setTranscription($transcription);
                $this->repository->save($command, true);

                $this->wsPublisher->sendCommandUpdate(
                    $command->getUser()->getId()->toRfc4122(),
                    $commandId,
                    'transcribed',
                    ['text' => $transcription->getText()]
                );
            }

            // 3. Parse with LLM
            $text = $command->getTranscription()?->getText() ?? $command->getRawText();

            $parsed = $this->llmService->parseCommand($text, [
                'date' => date('Y-m-d'),
                'time' => date('H:i'),
                'timezone' => 'UTC'
            ]);

            $parsedCommand = new \App\ValueObject\ParsedCommand(
                $parsed['action'],
                $parsed['parameters'],
                $parsed['confidence']
            );

            $command->setParsedCommand($parsedCommand);
            $this->repository->save($command, true);

            $this->wsPublisher->sendCommandUpdate(
                $command->getUser()->getId()->toRfc4122(),
                $commandId,
                'parsed',
                ['action' => $parsed['action']]
            );

            // 4. Execute command
            $command->markAsExecuting();
            $this->repository->save($command, true);

            $result = $this->executor->execute($command);

            // 5. Complete
            $command->complete($result);
            $this->repository->save($command, true);

            $this->wsPublisher->sendCommandUpdate(
                $command->getUser()->getId()->toRfc4122(),
                $commandId,
                'completed',
                $result
            );

        } catch (\Exception $e) {
            $this->logger->error('Command processing failed', [
                'command_id' => $commandId,
                'error' => $e->getMessage()
            ]);

            $command->fail($e->getMessage());
            $this->repository->save($command, true);

            $this->wsPublisher->sendCommandUpdate(
                $command->getUser()->getId()->toRfc4122(),
                $commandId,
                'failed',
                ['error' => $e->getMessage()]
            );
        }
    }

    private function transcribeAudio(string $audioPath): \App\ValueObject\TranscriptionResult
    {
        // Call Whisper service
        $response = $this->httpClient->request('POST', $this->whisperUrl . '/transcribe', [
            'body' => [
                'file' => fopen($audioPath, 'r')
            ]
        ]);

        $data = $response->toArray();

        return new \App\ValueObject\TranscriptionResult(
            $data['text'],
            $data['language'] ?? 'ru',
            $data['confidence'] ?? 0.9,
            $data['segments'] ?? null,
            $data['model_used'] ?? 'whisper-base'
        );
    }
}
```

---

## ⚙️ Шаг 3: Конфигурация Messenger

**AI**: Добавьте в `backend/config/packages/messenger.yaml`:

```yaml
framework:
    messenger:
        failure_transport: failed

        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    exchange:
                        name: messages
                        type: direct
                    queues:
                        messages: ~

            failed: 'doctrine://default?queue_name=failed'

        routing:
            App\Message\ProcessVoiceCommandMessage: async
```

**Добавьте в `.env`**:
```env
MESSENGER_TRANSPORT_DSN=amqp://user:password@localhost:5672/%2f/messages
WHISPER_URL=http://localhost:8090
```

---

## 🚀 Шаг 4: Запуск Воркера

**AI**: Сообщите пользователю запустить воркер:

```bash
# В отдельном терминале или supervisor
docker exec backend-php83 php bin/console messenger:consume async -vv
```

Или с supervisor (продакшн):

```ini
# /etc/supervisor/conf.d/messenger-worker.conf
[program:messenger-consume]
command=php /var/www/backend/bin/console messenger:consume async --time-limit=3600
user=www-data
numprocs=2
autostart=true
autorestart=true
```

---

## 🧪 Тестирование Очереди

**AI**: Быстрый тест:

```php
// In VoiceProcessingService or Controller
$this->messageBus->dispatch(new ProcessVoiceCommandMessage($command->getId()));
```

Проверьте логи:
```bash
docker exec backend-php83 tail -f /var/www/backend/var/log/dev.log
```

---

## 🎯 Поток Очереди

```
Пользователь отправляет команду
       ↓
API возвращает 202 (в очереди)
       ↓
Сообщение → RabbitMQ
       ↓
Воркер подхватывает
       ↓
Обработка (STT → LLM → Execute)
       ↓
WebSocket обновление → Frontend
```

---

## ✅ Чеклист

- [ ] Создан ProcessVoiceCommandMessage
- [ ] Создан ProcessVoiceCommandHandler
- [ ] Настроен messenger.yaml
- [ ] Добавлен MESSENGER_TRANSPORT_DSN в .env
- [ ] Запущен процесс воркера
- [ ] Протестирован end-to-end поток

---

## 🚨 Устранение Неполадок

**Воркер не обрабатывает**:
```bash
# Проверьте RabbitMQ
docker ps | grep rabbitmq

# Проверьте очередь
docker exec backend-rabbitmq rabbitmqctl list_queues

# Перезапустите воркер
docker exec backend-php83 pkill -f messenger:consume
docker exec backend-php83 php bin/console messenger:consume async
```

**Проваленные сообщения**:
```bash
# Просмотр проваленных
php bin/console messenger:failed:show

# Повтор
php bin/console messenger:failed:retry
```

---

**Время на Реализацию**: 30 минут
**Сложность**: Низкая (RabbitMQ уже в проекте)
