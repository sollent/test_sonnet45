<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AI;

use App\Service\AI\LLMService;
use App\ValueObject\ParsedCommand;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Unit тесты для LLMService
 *
 * Тестирует парсинг команд через LLM без реального вызова API
 */
class LLMServiceTest extends TestCase
{
    private HttpClientInterface $httpClient;

    private LoggerInterface $logger;

    private ParameterBagInterface $params;

    private LLMService $service;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);

        // Настраиваем параметры (URL для нативного Ollama)
        $this->params->method('has')->willReturn(true);
        $this->params->method('get')
            ->willReturnMap([
                ['ollama_url', 'http://host.docker.internal:11434'],
                ['llm_model', 'qwen2.5:14b'],
            ]);

        $this->service = new LLMService(
            $this->httpClient,
            $this->logger,
            $this->params,
        );
    }

    /**
     * Тест успешного парсинга команды создания задачи
     */
    public function testParseCreateTaskCommand(): void
    {
        // Arrange
        $commandText = 'Создай задачу купить молоко завтра';

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'response' => json_encode([
                'action'     => 'create_task',
                'parameters' => [
                    'title'    => 'Купить молоко',
                    'due_date' => 'tomorrow',
                ],
                'confidence' => 0.95,
            ]),
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'http://host.docker.internal:11434/api/generate',
                $this->callback(function ($options) {
                    return isset($options['json']['model'])
                        && $options['json']['model'] === 'qwen2.5:14b'
                        && isset($options['json']['prompt'])
                        && str_contains($options['json']['prompt'], 'Создай задачу купить молоко завтра');
                }),
            )
            ->willReturn($mockResponse);

        // Act
        $result = $this->service->parseCommand($commandText);

        // Assert
        $this->assertInstanceOf(ParsedCommand::class, $result);
        $this->assertEquals('create_task', $result->action);
        $this->assertEquals('Купить молоко', $result->getParameter('title'));
        $this->assertEquals('tomorrow', $result->getParameter('due_date'));
        $this->assertEquals(0.95, $result->confidence);
        $this->assertTrue($result->isExecutable());
    }

    /**
     * Тест парсинга команды завершения задачи
     */
    public function testParseCompleteTaskCommand(): void
    {
        // Arrange
        $commandText = 'Отметь задачу отчет как выполненную';

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'response' => json_encode([
                'action'     => 'complete_task',
                'parameters' => [
                    'search' => 'отчет',
                ],
                'confidence' => 0.88,
            ]),
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        // Act
        $result = $this->service->parseCommand($commandText);

        // Assert
        $this->assertEquals('complete_task', $result->action);
        $this->assertEquals('отчет', $result->getParameter('search'));
        $this->assertEquals(0.88, $result->confidence);
        $this->assertTrue($result->isExecutable());
    }

    /**
     * Тест обработки команды с низкой уверенностью
     */
    public function testParseLowConfidenceCommand(): void
    {
        // Arrange
        $commandText = 'что-то непонятное';

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'response' => json_encode([
                'action'     => 'unknown',
                'parameters' => [],
                'confidence' => 0.3,
            ]),
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        // Act
        $result = $this->service->parseCommand($commandText);

        // Assert
        $this->assertEquals('unknown', $result->action);
        $this->assertEquals(0.3, $result->confidence);
        $this->assertFalse($result->isExecutable());
        $this->assertTrue($result->needsClarification());
    }

    /**
     * Тест обработки ошибки API
     */
    public function testHandleApiError(): void
    {
        // Arrange
        $commandText = 'Создай задачу';

        $this->httpClient->expects($this->exactly(3)) // 3 retry attempts
            ->method('request')
            ->willThrowException(new Exception('Connection failed'));

        // Act
        $result = $this->service->parseCommand($commandText);

        // Assert
        $this->assertEquals(ParsedCommand::ACTION_CLARIFICATION_NEEDED, $result->action);
        $this->assertEquals(0.1, $result->confidence);
        $this->assertFalse($result->isExecutable());
        $this->assertTrue($result->needsClarification());
    }

    /**
     * Тест валидации пустой команды
     */
    public function testEmptyCommandValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Command text cannot be empty');

        $this->service->parseCommand('');
    }

    /**
     * Тест извлечения JSON из ответа с дополнительным текстом
     */
    public function testExtractJsonFromVerboseResponse(): void
    {
        // Arrange
        $commandText = 'Покажи все задачи на завтра';

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'response' => 'Вот результат парсинга: {"action":"filter_tasks","parameters":{"filters":{"date":"tomorrow"}},"confidence":0.9} Готово!',
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        // Act
        $result = $this->service->parseCommand($commandText);

        // Assert
        $this->assertEquals('filter_tasks', $result->action);
        $this->assertArrayHasKey('filters', $result->parameters);
        $this->assertEquals(0.9, $result->confidence);
    }

    /**
     * Тест проверки доступности сервиса
     */
    public function testIsAvailable(): void
    {
        // Arrange - сервис доступен
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'http://host.docker.internal:11434/api/tags')
            ->willReturn($mockResponse);

        // Act & Assert
        $this->assertTrue($this->service->isAvailable());
    }

    /**
     * Тест проверки недоступности сервиса
     */
    public function testIsNotAvailable(): void
    {
        // Arrange - сервис недоступен
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new Exception('Connection refused'));

        // Act & Assert
        $this->assertFalse($this->service->isAvailable());
    }

    /**
     * Тест получения списка моделей
     */
    public function testGetAvailableModels(): void
    {
        // Arrange
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn([
            'models' => [
                ['name' => 'qwen2.5:14b'],
                ['name' => 'qwen2.5:7b'],
                ['name' => 'llama3.2:1b'],
            ],
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'http://host.docker.internal:11434/api/tags')
            ->willReturn($mockResponse);

        // Act
        $models = $this->service->getAvailableModels();

        // Assert
        $this->assertCount(3, $models);
        $this->assertContains('qwen2.5:14b', $models);
        $this->assertContains('qwen2.5:7b', $models);
    }
}
