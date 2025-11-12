# 🧪 Voice AI Assistant - Testing Strategy & Critical Points

> **Дата создания**: 2025-11-08
> **Автор**: Claude Code (Opus 4.1)
> **Статус**: Рекомендации для будущей реализации

## 📋 Оглавление

1. [Критические Компоненты для Тестирования](#критические-компоненты-для-тестирования)
2. [Стратегия Тестирования по Уровням](#стратегия-тестирования-по-уровням)
3. [Примеры Тестов для Каждого Компонента](#примеры-тестов-для-каждого-компонента)
4. [Тестирование Производительности](#тестирование-производительности)
5. [Стратегия Интеграционного Тестирования](#стратегия-интеграционного-тестирования)

---

## 🎯 Критические Компоненты для Тестирования

### Приоритет 1: Основная Бизнес-Логика (ОБЯЗАТЕЛЬНО ТЕСТИРОВАТЬ)

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

### Приоритет 2: Точки Интеграции

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

### Модульные Тесты (70% покрытие)

```yaml
Области Фокуса:
  Сервисы:
    - Изоляция бизнес-логики
    - Мокирование внешних зависимостей
    - Обработка граничных случаев

  Обработчики Команд:
    - Валидация входных данных
    - Форматирование вывода
    - Сценарии ошибок

  DTO:
    - Сериализация/десериализация
    - Правила валидации
    - Типобезопасность

Инструменты:
  - PHPUnit 9.6
  - Mockery для мокирования
  - Faker для тестовых данных
```

### Интеграционные Тесты (20% покрытие)

```yaml
Области Фокуса:
  API Эндпоинты:
    - Формат Request/Response
    - Аутентификация
    - Ответы об ошибках

  База Данных:
    - Методы репозиториев
    - Целостность транзакций
    - Производительность запросов

  Внешние Сервисы:
    - Вызовы Ollama API
    - Whisper STT
    - Публикация в Centrifugo

Инструменты:
  - Symfony WebTestCase
  - Тестовая база данных с фикстурами
  - WireMock для внешних API
```

### Функциональные Тесты (10% покрытие)

```yaml
Критические Пользовательские Сценарии:
  1. Полный Поток Голосовой Команды:
     - Загрузка аудио
     - Обработка STT
     - Парсинг LLM
     - Выполнение команды
     - Уведомление через WebSocket

  2. Взаимодействие с Telegram Ботом:
     - Получение голосового сообщения
     - Обработка команды
     - Отправка ответа

  3. Восстановление после Ошибок:
     - Обработка отказов сервисов
     - Механизмы повтора
     - Обратная связь с пользователем

Инструменты:
  - Symfony Functional Tests
  - Реальные сервисы (staging окружение)
  - Selenium для UI тестов
```

---

## 🔬 Примеры Тестов для Каждого Компонента

### 1. Тест VoiceCommandController

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
        // Загрузка .txt файла как аудио
        // Проверка 400 Bad Request с понятным сообщением об ошибке
    }

    public function testRateLimiting(): void
    {
        // Отправка 10 запросов за 1 секунду
        // Проверка 429 Too Many Requests после превышения лимита
    }
}
```

### 2. Тест CommandParser

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

### 3. Тест Smart Search

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

        // Загрузка фикстур
        $this->loadFixtures([TaskFixtures::class]);
    }

    public function testFuzzySearch(): void
    {
        $user = $this->createUser();

        // Создание задач с похожими названиями
        $this->createTask($user, 'Написать отчет по проекту');
        $this->createTask($user, 'Написать email клиенту');
        $this->createTask($user, 'Прочитать отчет');

        // Поиск с частичным совпадением
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

## ⚡ Тестирование Производительности

### Критические Метрики Производительности

```yaml
Конвейер Обработки Голоса:
  Обработка STT:
    Цель: < 2с для 30с аудио
    Максимум: 5с

  Парсинг Команд LLM:
    Цель: < 500мс
    Максимум: 2с

  Выполнение Команды:
    Цель: < 100мс
    Максимум: 500мс

  Доставка через WebSocket:
    Цель: < 50мс
    Максимум: 200мс

Общее Время End-to-End:
  Цель: < 3с
  Максимум: 8с
```

### Сценарии Нагрузочного Тестирования

```php
namespace Tests\Performance;

class VoiceAssistantLoadTest extends PerformanceTestCase
{
    public function testConcurrentVoiceCommands(): void
    {
        // Симуляция 100 одновременных пользователей
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
        // 10 запросов в секунду в течение 5 минут
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

## 🔄 Стратегия Интеграционного Тестирования

### Мокирование Внешних Сервисов

```php
// Мокирование ответов Ollama
class OllamaMockService implements LLMServiceInterface
{
    private array $responses = [
        'создай задачу' => [
            'action' => 'create_task',
            'parameters' => ['title' => 'New Task'],
            'confidence' => 0.95
        ],
        // ... больше мок-ответов
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

### Тестирование WebSocket

```javascript
// Интеграционные тесты WebSocket на фронтенде
describe('VoiceAssistant WebSocket', () => {
    let centrifugo: CentrifugoClient;

    beforeEach(() => {
        centrifugo = new CentrifugoClient({
            url: 'ws://localhost:8000/connection/websocket',
            token: getTestToken()
        });
    });

    it('получает обновления о выполнении команд', async () => {
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

## 📈 Цели по Покрытию Тестами

### Минимальные Требования к Покрытию

```yaml
Общее: 75%

По Компонентам:
  CommandExecutorService: 95%
  SmartSearchService: 90%
  LLMService: 85%
  WebSocketPublisherService: 80%
  VoiceProcessingService: 85%
  Обработчики Команд: 90%
  DTO: 100%
  Контроллеры: 70%

По Типу Тестов:
  Модульные Тесты: 80%
  Интеграционные Тесты: 60%
  Функциональные Тесты: 40%
```

### Интеграция с CI/CD

```yaml
# .github/workflows/test.yml
name: Voice AI Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2

      - name: Модульные Тесты
        run: |
          docker-compose -f docker/docker-compose.test.yml up -d
          docker exec test-php bin/phpunit --testsuite unit

      - name: Интеграционные Тесты
        run: |
          docker exec test-php bin/phpunit --testsuite integration

      - name: Отчет о Покрытии
        run: |
          docker exec test-php bin/phpunit --coverage-html coverage

      - name: Тесты Производительности
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