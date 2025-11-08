# 🧪 Voice AI Assistant - Testing Strategy & Critical Points

> **Дата создания**: 2025-11-08
> **Автор**: Claude Code (Opus 4.1)
> **Статус**: Рекомендации для будущей реализации

## 📋 Оглавление

1. [Критические Компоненты для Тестирования](#критические-компоненты-для-тестирования)
2. [Стратегия Тестирования по Уровням](#стратегия-тестирования-по-уровням)
3. [Примеры Тестов для Каждого Компонента](#примеры-тестов-для-каждого-компонента)
4. [Performance Testing](#performance-testing)
5. [Integration Testing Strategy](#integration-testing-strategy)

---

## 🎯 Критические Компоненты для Тестирования

### Priority 1: Core Business Logic (MUST TEST)

#### 1. CommandExecutorService
**Почему критично**: Центральный компонент, выполняющий все действия

```php
namespace Tests\Unit\Service\VoiceAssistant;

class CommandExecutorServiceTest extends TestCase
{
    public function testExecuteCreateTaskCommand(): void
    {
        // Test: Создание задачи с валидными данными
        // Assert: Задача создана, WebSocket событие отправлено
    }

    public function testRollbackOnFailure(): void
    {
        // Test: Ошибка при выполнении команды
        // Assert: Транзакция откатилась, данные не изменены
    }

    public function testConcurrentCommandsFromSameUser(): void
    {
        // Test: Две команды от одного юзера одновременно
        // Assert: Обе выполнены последовательно без конфликтов
    }
}
```

#### 2. SmartSearchService
**Почему критично**: От точности поиска зависит UX

```php
class SmartSearchServiceTest extends TestCase
{
    public function testFindTaskByPartialName(): void
    {
        // Test: Поиск "Сделать домашку" находит "Сделать домашнее задание"
    }

    public function testFindTaskWithTypos(): void
    {
        // Test: Поиск "Сдлеать дамашку" находит правильную задачу
    }

    public function testSimilarityScoring(): void
    {
        // Test: Scoring algorithm возвращает правильные веса
    }
}
```

#### 3. LLMService
**Почему критично**: Парсинг голосовых команд в действия

```php
class LLMServiceTest extends TestCase
{
    public function testParseCreateTaskCommand(): void
    {
        // Test: "Создай задачу на завтра" → правильный JSON
    }

    public function testHandleAmbiguousCommands(): void
    {
        // Test: "Завершить задачу" без указания какую
        // Assert: Возвращает запрос уточнения
    }

    public function testFallbackOnLLMTimeout(): void
    {
        // Test: Ollama не отвечает 5 секунд
        // Assert: Graceful degradation, user notification
    }
}
```

### Priority 2: Integration Points

#### 4. WebSocketPublisherService
**Почему критично**: Real-time обновления UI

```php
class WebSocketPublisherServiceTest extends TestCase
{
    public function testPublishToUserChannel(): void
    {
        // Test: Отправка события конкретному юзеру
    }

    public function testHandleCentrifugoDowntime(): void
    {
        // Test: Centrifugo недоступен
        // Assert: События сохранены в очередь для retry
    }
}
```

#### 5. VoiceProcessingService
**Почему критично**: Entry point для всех голосовых команд

```php
class VoiceProcessingServiceTest extends TestCase
{
    public function testProcessLargeAudioFile(): void
    {
        // Test: Файл > 10MB
        // Assert: Обработан асинхронно через queue
    }

    public function testUnsupportedAudioFormat(): void
    {
        // Test: Отправлен .mp3 вместо .wav
        // Assert: Конвертация или понятная ошибка
    }
}
```

---

## 📊 Стратегия Тестирования по Уровням

### Unit Tests (70% coverage)

```yaml
Focus Areas:
  Services:
    - Business logic isolation
    - Mocking external dependencies
    - Edge cases handling

  Command Handlers:
    - Input validation
    - Output formatting
    - Error scenarios

  DTOs:
    - Serialization/deserialization
    - Validation rules
    - Type safety

Tools:
  - PHPUnit 9.6
  - Mockery for mocking
  - Faker for test data
```

### Integration Tests (20% coverage)

```yaml
Focus Areas:
  API Endpoints:
    - Request/Response format
    - Authentication
    - Error responses

  Database:
    - Repository methods
    - Transaction integrity
    - Query performance

  External Services:
    - Ollama API calls
    - Whisper STT
    - Centrifugo publishing

Tools:
  - Symfony WebTestCase
  - Test database with fixtures
  - WireMock for external APIs
```

### Functional Tests (10% coverage)

```yaml
Critical User Journeys:
  1. Complete Voice Command Flow:
     - Upload audio
     - STT processing
     - LLM parsing
     - Command execution
     - WebSocket notification

  2. Telegram Bot Interaction:
     - Receive voice message
     - Process command
     - Send response

  3. Error Recovery:
     - Handle service failures
     - Retry mechanisms
     - User feedback

Tools:
  - Symfony Functional Tests
  - Real services (staging env)
  - Selenium for UI tests
```

---

## 🔬 Примеры Тестов для Каждого Компонента

### 1. VoiceCommandController Test

```php
namespace Tests\Functional\Controller;

class VoiceCommandControllerTest extends WebTestCase
{
    public function testSubmitVoiceCommand(): void
    {
        $client = static::createClient();
        $audioFile = new UploadedFile(
            __DIR__ . '/fixtures/test_command.wav',
            'command.wav',
            'audio/wav'
        );

        $client->request('POST', '/api/voice/command', [], [
            'audio' => $audioFile
        ], [
            'HTTP_Authorization' => 'Bearer ' . $this->getValidToken()
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('command_id', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('processing', $response['status']);
    }

    public function testInvalidAudioFormat(): void
    {
        // Upload .txt file as audio
        // Assert 400 Bad Request with clear error message
    }

    public function testRateLimiting(): void
    {
        // Send 10 requests in 1 second
        // Assert 429 Too Many Requests after limit
    }
}
```

### 2. CommandParser Test

```php
namespace Tests\Unit\Service\VoiceAssistant;

class CommandParserTest extends TestCase
{
    private CommandParserInterface $parser;

    protected function setUp(): void
    {
        $this->parser = new CommandParser();
    }

    /**
     * @dataProvider commandProvider
     */
    public function testParseValidCommands(string $llmResponse, array $expected): void
    {
        $result = $this->parser->parse($llmResponse);

        $this->assertEquals($expected['action'], $result->getAction());
        $this->assertEquals($expected['parameters'], $result->getParameters());
    }

    public function commandProvider(): array
    {
        return [
            'create_task' => [
                '{"action": "create_task", "parameters": {"title": "Test"}}',
                ['action' => 'create_task', 'parameters' => ['title' => 'Test']]
            ],
            'complete_task' => [
                '{"action": "complete_task", "parameters": {"task_id": "123"}}',
                ['action' => 'complete_task', 'parameters' => ['task_id' => '123']]
            ],
        ];
    }

    public function testInvalidJsonHandling(): void
    {
        $result = $this->parser->parse('not a json');

        $this->assertNull($result);
        $this->assertTrue($this->parser->hasErrors());
    }
}
```

### 3. Smart Search Test

```php
namespace Tests\Integration\Service;

class SmartSearchServiceTest extends KernelTestCase
{
    private SmartSearchService $searchService;
    private TaskRepository $taskRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->searchService = $container->get(SmartSearchService::class);
        $this->taskRepository = $container->get(TaskRepository::class);

        // Load fixtures
        $this->loadFixtures([TaskFixtures::class]);
    }

    public function testFuzzySearch(): void
    {
        $user = $this->createUser();

        // Create tasks with similar names
        $this->createTask($user, 'Написать отчет по проекту');
        $this->createTask($user, 'Написать email клиенту');
        $this->createTask($user, 'Прочитать отчет');

        // Search with partial match
        $results = $this->searchService->findSimilarTasks(
            'написать отчёт', // с ё вместо е
            $user,
            5
        );

        $this->assertCount(2, $results);
        $this->assertEquals('Написать отчет по проекту', $results[0]->getTitle());
    }

    public function testSearchWithSynonyms(): void
    {
        $user = $this->createUser();

        $this->createTask($user, 'Сделать домашнее задание');

        $results = $this->searchService->findSimilarTasks(
            'выполнить домашку',
            $user,
            5
        );

        $this->assertCount(1, $results);
        $this->assertStringContainsString('домашнее', $results[0]->getTitle());
    }
}
```

---

## ⚡ Performance Testing

### Critical Performance Metrics

```yaml
Voice Processing Pipeline:
  STT Processing:
    Target: < 2s for 30s audio
    Max: 5s

  LLM Command Parsing:
    Target: < 500ms
    Max: 2s

  Command Execution:
    Target: < 100ms
    Max: 500ms

  WebSocket Delivery:
    Target: < 50ms
    Max: 200ms

Total End-to-End:
  Target: < 3s
  Max: 8s
```

### Load Testing Scenarios

```php
namespace Tests\Performance;

class VoiceAssistantLoadTest extends PerformanceTestCase
{
    public function testConcurrentVoiceCommands(): void
    {
        // Simulate 100 concurrent users
        $this->runConcurrent(100, function ($userId) {
            $response = $this->sendVoiceCommand(
                $this->generateAudioFile(),
                $userId
            );

            $this->assertLessThan(8000, $response->getTime());
        });
    }

    public function testSustainedLoad(): void
    {
        // 10 requests per second for 5 minutes
        $this->runSustained(
            requestsPerSecond: 10,
            duration: 300,
            assertion: function ($response) {
                $this->assertLessThan(5000, $response->getTime());
                $this->assertEquals(200, $response->getStatusCode());
            }
        );
    }
}
```

---

## 🔄 Integration Testing Strategy

### External Service Mocking

```php
// Mock Ollama responses
class OllamaMockService implements LLMServiceInterface
{
    private array $responses = [
        'создай задачу' => [
            'action' => 'create_task',
            'parameters' => ['title' => 'New Task'],
            'confidence' => 0.95
        ],
        // ... more mock responses
    ];

    public function parseCommand(string $text, array $context): CommandDto
    {
        foreach ($this->responses as $pattern => $response) {
            if (stripos($text, $pattern) !== false) {
                return new CommandDto($response);
            }
        }

        throw new UnknownCommandException($text);
    }
}
```

### WebSocket Testing

```javascript
// Frontend WebSocket integration tests
describe('VoiceAssistant WebSocket', () => {
    let centrifugo: CentrifugoClient;

    beforeEach(() => {
        centrifugo = new CentrifugoClient({
            url: 'ws://localhost:8000/connection/websocket',
            token: getTestToken()
        });
    });

    it('receives command execution updates', async () => {
        const updates = [];

        centrifugo.on('voice.action.executed', (data) => {
            updates.push(data);
        });

        await sendVoiceCommand('создай задачу тест');

        await waitFor(() => {
            expect(updates).toHaveLength(1);
            expect(updates[0].action).toBe('create_task');
            expect(updates[0].status).toBe('completed');
        });
    });
});
```

---

## 📈 Coverage Goals

### Minimum Coverage Requirements

```yaml
Overall: 75%

By Component:
  CommandExecutorService: 95%
  SmartSearchService: 90%
  LLMService: 85%
  WebSocketPublisherService: 80%
  VoiceProcessingService: 85%
  Command Handlers: 90%
  DTOs: 100%
  Controllers: 70%

By Test Type:
  Unit Tests: 80%
  Integration Tests: 60%
  Functional Tests: 40%
```

### CI/CD Integration

```yaml
# .github/workflows/test.yml
name: Voice AI Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2

      - name: Unit Tests
        run: |
          docker-compose -f docker/docker-compose.test.yml up -d
          docker exec test-php bin/phpunit --testsuite unit

      - name: Integration Tests
        run: |
          docker exec test-php bin/phpunit --testsuite integration

      - name: Coverage Report
        run: |
          docker exec test-php bin/phpunit --coverage-html coverage

      - name: Performance Tests
        if: github.ref == 'refs/heads/main'
        run: |
          docker exec test-php bin/phpunit --testsuite performance
```

---

## 🔗 Связанные документы

- [Voice AI Assistant Plan](VOICE_AI_ASSISTANT_PLAN.md)
- [Testing Guide](TESTING.md)
- [Backend Architecture](../backend/ARCHITECTURE.md)

---

**Автор**: Claude Code (Opus 4.1)
**Дата создания**: 2025-11-08
**Версия**: 1.0.0