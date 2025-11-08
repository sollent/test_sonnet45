# 🌐 Phase 2.4: API Endpoints - Quick Implementation

> **For AI**: Simple REST API endpoints for voice assistant. Copy, adapt, test.

## 📍 What You're Building

3 essential endpoints for MVP:

1. `POST /api/voice/command` - Submit audio/text command
2. `GET /api/voice/command/{id}` - Get command status
3. `GET /api/voice/history` - Get command history

---

## 🚀 Step 1: Create Controller

**AI**: Create controller file:

```php
<?php
// File: backend/src/Controller/VoiceCommandController.php

namespace App\Controller;

use App\Service\VoiceAssistant\VoiceProcessingService;
use App\Repository\VoiceCommandRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/voice', name: 'api_voice_')]
#[IsGranted('ROLE_USER')]
class VoiceCommandController extends AbstractController
{
    public function __construct(
        private VoiceProcessingService $voiceService,
        private VoiceCommandRepository $repository
    ) {}

    /**
     * Submit voice command (audio or text)
     */
    #[Route('/command', name: 'submit', methods: ['POST'])]
    public function submitCommand(Request $request): JsonResponse
    {
        $user = $this->getUser();

        // Check if audio file or text
        /** @var UploadedFile|null $audioFile */
        $audioFile = $request->files->get('audio');
        $textCommand = $request->request->get('text');

        try {
            if ($audioFile) {
                // Audio command
                $command = $this->voiceService->processAudioCommand(
                    $audioFile,
                    $user,
                    $request->request->get('source', 'web')
                );
            } elseif ($textCommand) {
                // Text command
                $command = $this->voiceService->processTextCommand(
                    $textCommand,
                    $user,
                    $request->request->get('source', 'web')
                );
            } else {
                return $this->json([
                    'error' => 'No audio or text provided'
                ], 400);
            }

            return $this->json([
                'success' => true,
                'command_id' => $command->getId()->toRfc4122(),
                'status' => $command->getStatus()->value,
                'message' => 'Command received and processing'
            ], 202); // 202 Accepted

        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 400);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to process command',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get command status
     */
    #[Route('/command/{id}', name: 'status', methods: ['GET'])]
    public function getCommandStatus(string $id): JsonResponse
    {
        $command = $this->repository->find($id);

        if (!$command) {
            return $this->json(['error' => 'Command not found'], 404);
        }

        // Security check
        if ($command->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        return $this->json([
            'id' => $command->getId()->toRfc4122(),
            'status' => $command->getStatus()->value,
            'type' => $command->getType()->value,
            'confidence' => $command->getConfidence(),
            'result' => $command->getCommandResult(),
            'error' => $command->getErrorMessage(),
            'created_at' => $command->getCreatedAt()->format('c'),
            'completed_at' => $command->getCompletedAt()?->format('c'),
            'processing_time_ms' => $command->getProcessingTimeMs(),

            // Add transcription if available
            'transcription' => $command->getTranscription() ? [
                'text' => $command->getTranscription()->getText(),
                'language' => $command->getTranscription()->getLanguage(),
                'confidence' => $command->getTranscription()->getConfidence(),
            ] : null,

            // Add parsed command if available
            'parsed_command' => $command->getParsedCommand() ? [
                'action' => $command->getParsedCommand()->getAction(),
                'parameters' => $command->getParsedCommand()->getParameters(),
                'confidence' => $command->getParsedCommand()->getConfidence(),
            ] : null,
        ]);
    }

    /**
     * Get user's command history
     */
    #[Route('/history', name: 'history', methods: ['GET'])]
    public function getHistory(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $days = (int)$request->query->get('days', 7);
        $limit = (int)$request->query->get('limit', 50);

        $commands = $this->repository->findUserRecentCommands($user, $days, $limit);

        return $this->json([
            'commands' => array_map(function($cmd) {
                return [
                    'id' => $cmd->getId()->toRfc4122(),
                    'status' => $cmd->getStatus()->value,
                    'type' => $cmd->getType()->value,
                    'text' => $cmd->getRawText(),
                    'confidence' => $cmd->getConfidence(),
                    'created_at' => $cmd->getCreatedAt()->format('c'),
                    'result' => $cmd->getCommandResult(),
                ];
            }, $commands),
            'total' => count($commands)
        ]);
    }

    /**
     * Get user statistics
     */
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function getStatistics(): JsonResponse
    {
        $user = $this->getUser();
        $stats = $this->repository->getUserStatistics($user);

        return $this->json($stats);
    }
}
```

---

## 🔐 Step 2: Security Configuration

**AI**: Make sure JWT is configured in `backend/config/packages/security.yaml`:

```yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            jwt: ~

    access_control:
        - { path: ^/api/auth, roles: PUBLIC_ACCESS }
        - { path: ^/api/voice, roles: ROLE_USER }
```

---

## 📤 Step 3: Request Examples

### Submit Audio Command

```bash
curl -X POST http://localhost:8089/api/voice/command \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "audio=@recording.wav" \
  -F "source=web"
```

Response:
```json
{
  "success": true,
  "command_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "pending",
  "message": "Command received and processing"
}
```

### Submit Text Command

```bash
curl -X POST http://localhost:8089/api/voice/command \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "text": "Создай задачу купить молоко завтра",
    "source": "web"
  }'
```

### Get Command Status

```bash
curl -X GET http://localhost:8089/api/voice/command/550e8400-e29b-41d4-a716-446655440000 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

Response:
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "completed",
  "type": "text",
  "confidence": 0.92,
  "result": {
    "success": true,
    "task_id": "123",
    "message": "Task created"
  },
  "transcription": null,
  "parsed_command": {
    "action": "create_task",
    "parameters": {
      "title": "Купить молоко",
      "due_date": "2025-01-09"
    },
    "confidence": 0.92
  },
  "created_at": "2025-01-08T10:30:00+00:00",
  "completed_at": "2025-01-08T10:30:02+00:00",
  "processing_time_ms": 2000
}
```

---

## ✅ Step 4: Frontend Integration Example

**AI**: This is how frontend will use the API:

```typescript
// frontend/src/services/voiceCommand.service.ts

import axios from 'axios';

class VoiceCommandService {
    private baseUrl = '/api/voice';

    async submitAudioCommand(audioBlob: Blob): Promise<string> {
        const formData = new FormData();
        formData.append('audio', audioBlob, 'recording.wav');
        formData.append('source', 'web');

        const response = await axios.post(`${this.baseUrl}/command`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        return response.data.command_id;
    }

    async submitTextCommand(text: string): Promise<string> {
        const response = await axios.post(`${this.baseUrl}/command`, {
            text,
            source: 'web'
        });

        return response.data.command_id;
    }

    async getCommandStatus(commandId: string) {
        const response = await axios.get(`${this.baseUrl}/command/${commandId}`);
        return response.data;
    }

    async getHistory(days: number = 7) {
        const response = await axios.get(`${this.baseUrl}/history`, {
            params: { days }
        });
        return response.data.commands;
    }
}

export default new VoiceCommandService();
```

---

## 🧪 Step 5: Test Your API

**AI**: Create simple test:

```php
<?php
// File: backend/tests/Controller/VoiceCommandControllerTest.php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class VoiceCommandControllerTest extends WebTestCase
{
    public function testSubmitTextCommand(): void
    {
        $client = static::createClient();

        // Login first (adapt to your auth system)
        // $token = $this->getAuthToken();

        $client->request('POST', '/api/voice/command', [], [], [
            // 'HTTP_AUTHORIZATION' => "Bearer $token",
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'text' => 'Создай задачу тест',
            'source' => 'test'
        ]));

        $this->assertResponseStatusCodeSame(202);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('command_id', $data);
    }
}
```

---

## 🚨 Error Handling

**AI**: Controller handles these errors automatically:

| Error | Status | Response |
|-------|--------|----------|
| No audio/text | 400 | `{"error": "No audio or text provided"}` |
| Invalid file format | 400 | `{"error": "Invalid audio format"}` |
| File too large | 400 | `{"error": "Audio file too large"}` |
| Command not found | 404 | `{"error": "Command not found"}` |
| Access denied | 403 | `{"error": "Access denied"}` |
| Server error | 500 | `{"error": "Failed to process command"}` |

---

## 📊 API Flow Diagram

```
Frontend
   ↓
POST /api/voice/command (audio/text)
   ↓
VoiceCommandController
   ↓
VoiceProcessingService
   ↓
Queue (RabbitMQ)
   ↓
[Async Processing]
   ↓
WebSocket Update → Frontend
   ↓
GET /api/voice/command/{id} (if needed)
```

---

## ✅ AI Implementation Checklist

- [ ] Created VoiceCommandController.php
- [ ] Verified security configuration
- [ ] Tested POST /api/voice/command with text
- [ ] Tested GET /api/voice/command/{id}
- [ ] (Optional) Tested audio upload

---

## 🎯 Next Steps

**API ready!** Now set up queue processing:

→ [Queue Processing](05_QUEUE_PROCESSING.md) - Async command processing with RabbitMQ

---

**AI Quick Tips**:
- Controller is simple - just calls services
- Security is handled by Symfony
- Use 202 status for async processing
- Return command_id immediately
- WebSocket will notify when done

**Time to Implement**: 30 minutes