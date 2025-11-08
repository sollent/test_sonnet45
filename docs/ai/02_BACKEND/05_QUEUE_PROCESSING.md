# ⚡ Phase 2.5: Queue Processing - Simple Async

> **For AI**: Super simple RabbitMQ setup for async voice processing.

## 🎯 Why Queue?

Voice processing is slow (2-5 seconds). Queue lets us:
- ✅ Return response immediately (202 Accepted)
- ✅ Process in background
- ✅ Send WebSocket updates when done

---

## 📦 Step 1: Message Class

**AI**: Create message for queue:

```php
<?php
// File: backend/src/Message/ProcessVoiceCommandMessage.php

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

## 🔧 Step 2: Message Handler

**AI**: This runs asynchronously in worker:

```php
<?php
// File: backend/src/MessageHandler/ProcessVoiceCommandHandler.php

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

## ⚙️ Step 3: Configure Messenger

**AI**: Add to `backend/config/packages/messenger.yaml`:

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

**Add to `.env`**:
```env
MESSENGER_TRANSPORT_DSN=amqp://user:password@localhost:5672/%2f/messages
WHISPER_URL=http://localhost:8090
```

---

## 🚀 Step 4: Run Worker

**AI**: Tell user to run worker:

```bash
# In separate terminal or supervisor
docker exec backend-php83 php bin/console messenger:consume async -vv
```

Or with supervisor (production):

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

## 🧪 Test Queue

**AI**: Quick test:

```php
// In VoiceProcessingService or Controller
$this->messageBus->dispatch(new ProcessVoiceCommandMessage($command->getId()));
```

Check logs:
```bash
docker exec backend-php83 tail -f /var/www/backend/var/log/dev.log
```

---

## 🎯 Queue Flow

```
User submits command
       ↓
API returns 202 (queued)
       ↓
Message → RabbitMQ
       ↓
Worker picks up
       ↓
Process (STT → LLM → Execute)
       ↓
WebSocket update → Frontend
```

---

## ✅ Checklist

- [ ] Created ProcessVoiceCommandMessage
- [ ] Created ProcessVoiceCommandHandler
- [ ] Configured messenger.yaml
- [ ] Added MESSENGER_TRANSPORT_DSN to .env
- [ ] Started worker process
- [ ] Tested end-to-end flow

---

## 🚨 Troubleshooting

**Worker not processing**:
```bash
# Check RabbitMQ
docker ps | grep rabbitmq

# Check queue
docker exec backend-rabbitmq rabbitmqctl list_queues

# Restart worker
docker exec backend-php83 pkill -f messenger:consume
docker exec backend-php83 php bin/console messenger:consume async
```

**Failed messages**:
```bash
# View failed
php bin/console messenger:failed:show

# Retry
php bin/console messenger:failed:retry
```

---

**Time to Implement**: 30 minutes
**Complexity**: Low (RabbitMQ already in project)