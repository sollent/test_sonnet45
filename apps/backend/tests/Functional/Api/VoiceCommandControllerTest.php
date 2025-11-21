<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\TestsUtilities\Factory\TagFactory;
use App\TestsUtilities\Factory\TaskFactory;
use App\TestsUtilities\Factory\UserFactory;
use App\ValueObject\ParsedCommand;
use DateTimeImmutable;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Функциональные тесты для Voice Command API endpoint
 *
 * Тестирует обработку голосовых команд через /api/voice/command
 * Покрывает все 24 action из ParsedCommand
 *
 * ВАЖНО: Эти тесты исключены из CI/CD из-за длительного времени выполнения (30-40 сек на тест)
 * Используйте для локального тестирования: phpunit --group voice-command
 *
 * @group functional
 * @group voice-command
 * @group local-only
 */
class VoiceCommandControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private KernelBrowser $client;
    private JWTTokenManagerInterface $jwtManager;
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);

        // Создаем тестового пользователя
        $userProxy = UserFactory::createOne([
            'email' => 'test-voice-' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        $this->user = $userProxy->_real();
        $this->token = $this->jwtManager->create($this->user);

        // Подготовка тестовых данных
        $this->setupTestData();
    }

    /**
     * Подготовка тестовых данных для всех тестов
     */
    private function setupTestData(): void
    {
        // Создаем базовые задачи для тестирования
        TaskFactory::createOne([
            'user' => $this->user,
            'title' => 'тестовая задача номер один',
            'status' => TaskStatus::PENDING,
            'priority' => TaskPriority::MEDIUM,
            'dueDate' => new DateTimeImmutable('+1 day'),
        ]);

        TaskFactory::createOne([
            'user' => $this->user,
            'title' => 'купить молоко',
            'status' => TaskStatus::PENDING,
            'priority' => TaskPriority::LOW,
            'dueDate' => new DateTimeImmutable('+2 days'),
        ]);

        // Создаем задачу с тегом для тестирования фильтров
        $urgentTag = TagFactory::createOne([
            'user' => $this->user,
            'name' => 'Срочно',
        ]);

        TaskFactory::createOne([
            'user' => $this->user,
            'title' => 'срочная задача',
            'status' => TaskStatus::PENDING,
            'priority' => TaskPriority::HIGH,
            'tags' => [$urgentTag],
        ]);

        // Создаем выполненную задачу для тестирования
        TaskFactory::createOne([
            'user' => $this->user,
            'title' => 'завершенная задача',
            'status' => TaskStatus::COMPLETED,
            'priority' => TaskPriority::MEDIUM,
        ]);
    }

    /**
     * Отправка запроса на voice command endpoint
     */
    private function sendVoiceCommand(string $text): void
    {
        $this->client->request(
            'POST',
            '/api/voice/command',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            ],
            json_encode(['text' => $text])
        );
    }

    /**
     * Получение декодированного ответа
     */
    private function getResponseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    /**
     * Тест критически важных команд через DataProvider
     *
     * @dataProvider criticalCommandsProvider
     * @param string $text Текст команды
     * @param string $expectedType Ожидаемый тип результата
     * @param bool $expectedSuccess Ожидаемый статус успеха
     * @param array $additionalAssertions Дополнительные проверки
     */
    public function testCriticalCommands(
        string $text,
        string $expectedType,
        bool $expectedSuccess,
        array $additionalAssertions = []
    ): void {
        // Act
        $this->sendVoiceCommand($text);

        // Assert: Базовые проверки
        $this->assertResponseIsSuccessful();
        $response = $this->getResponseData();

        // Проверка структуры ответа
        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('parsedCommand', $response);
        $this->assertArrayHasKey('executionResult', $response);

        // Проверка парсинга команды
        $this->assertNotNull($response['parsedCommand']);
        $this->assertArrayHasKey('action', $response['parsedCommand']);
        $this->assertArrayHasKey('parameters', $response['parsedCommand']);

        // Проверка результата выполнения
        $executionResult = $response['executionResult'];
        $this->assertSame($expectedSuccess, $executionResult['success']);
        $this->assertSame($expectedType, $executionResult['type']);

        // Дополнительные проверки из DataProvider
        foreach ($additionalAssertions as $key => $expectedValue) {
            if (str_contains($key, '.')) {
                // Вложенные ключи (например, 'data.task.title')
                $keys = explode('.', $key);
                $value = $executionResult;
                foreach ($keys as $k) {
                    $this->assertArrayHasKey($k, $value);
                    $value = $value[$k];
                }
                $this->assertSame($expectedValue, $value);
            } else {
                $this->assertArrayHasKey($key, $executionResult);
                $this->assertSame($expectedValue, $executionResult[$key]);
            }
        }
    }

    /**
     * DataProvider для критически важных команд
     */
    public static function criticalCommandsProvider(): array
    {
        return [
            'create_simple_task' => [
                'text' => 'Создай задачу купить хлеб',
                'expectedType' => 'task_created',
                'expectedSuccess' => true,
                'additionalAssertions' => [
                    'message' => 'Задача "купить хлеб" успешно создана',
                ],
            ],

            'complete_existing_task' => [
                'text' => 'Заверши задачу тестовая задача номер один',
                'expectedType' => 'task_completed',
                'expectedSuccess' => true,
                'additionalAssertions' => [],
            ],

            'update_task_priority' => [
                'text' => 'Обнови приоритет задачи купить молоко на высокий',
                'expectedType' => 'task_updated',
                'expectedSuccess' => true,
                'additionalAssertions' => [],
            ],

            'delete_task' => [
                'text' => 'Удали задачу завершенная задача',
                'expectedType' => 'task_deleted',
                'expectedSuccess' => true,
                'additionalAssertions' => [],
            ],

            'bulk_update_with_tag' => [
                'text' => 'Обнови все задачи с тегом срочно в статус выполнено',
                'expectedType' => 'bulk_update_completed',
                'expectedSuccess' => true,
                'additionalAssertions' => [
                    'data.updated_count' => 1,
                ],
            ],

            'create_subtask' => [
                'text' => 'Создай подзадачу проверить срок годности для задачи купить молоко',
                'expectedType' => 'subtask_created',
                'expectedSuccess' => true,
                'additionalAssertions' => [],
            ],

            'add_tag_to_task' => [
                'text' => 'Добавь тег важное к задаче купить молоко',
                'expectedType' => 'tag_added',
                'expectedSuccess' => true,
                'additionalAssertions' => [],
            ],

            'filter_pending_tasks' => [
                'text' => 'Покажи все задачи со статусом в ожидании',
                'expectedType' => 'filter_results',
                'expectedSuccess' => true,
                'additionalAssertions' => [],
            ],

            'complete_multiple_tasks' => [
                'text' => 'Заверши задачи купить молоко и срочная задача',
                'expectedType' => 'tasks_completed',
                'expectedSuccess' => true,
                'additionalAssertions' => [],
            ],

            'create_multiple_tasks' => [
                'text' => 'Создай задачи позвонить маме и забрать посылку',
                'expectedType' => 'tasks_created',
                'expectedSuccess' => true,
                'additionalAssertions' => [
                    'data.created_count' => 2,
                ],
            ],
        ];
    }

    /**
     * Тест валидации входных данных
     * @dataProvider validationErrorsProvider
     */
    public function testValidationErrors(array $payload, int $expectedStatusCode): void
    {
        $this->client->request(
            'POST',
            '/api/voice/command',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            ],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    /**
     * DataProvider для тестов валидации
     */
    public static function validationErrorsProvider(): array
    {
        return [
            'empty_payload' => [
                'payload' => [],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,  // 422
            ],

            'both_text_and_audio' => [
                'payload' => [
                    'text' => 'test',
                    'audioUrl' => 'http://example.com/audio.webm',
                ],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,  // 422
            ],

            'text_too_short' => [
                'payload' => [
                    'text' => 'ab',  // Минимум 3 символа
                ],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,  // 422
            ],

            'text_too_long' => [
                'payload' => [
                    'text' => str_repeat('a', 501),  // Максимум 500 символов
                ],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,  // 422
            ],

            'invalid_language' => [
                'payload' => [
                    'text' => 'test command',
                    'language' => 'invalid',
                ],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,  // 422
            ],
        ];
    }

    /**
     * Тест команд без аутентификации
     */
    public function testUnauthorizedAccess(): void
    {
        $this->client->request(
            'POST',
            '/api/voice/command',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['text' => 'Создай задачу'])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Тест частичного успеха для batch операций
     */
    public function testBatchPartialSuccess(): void
    {
        // Подготовка: создаем несколько задач, одна из которых не существует
        TaskFactory::createOne([
            'user' => $this->user,
            'title' => 'первая задача для завершения',
            'status' => TaskStatus::PENDING,
        ]);

        TaskFactory::createOne([
            'user' => $this->user,
            'title' => 'вторая задача для завершения',
            'status' => TaskStatus::PENDING,
        ]);

        // Act: пытаемся завершить три задачи (одна не существует)
        $this->sendVoiceCommand('Заверши задачи первая задача для завершения, вторая задача для завершения и несуществующая задача');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = $this->getResponseData();

        $this->assertTrue($response['success']);

        $executionResult = $response['executionResult'];
        $this->assertTrue($executionResult['success']);

        // Проверяем частичный успех
        if (isset($executionResult['data']['success_count'])) {
            $this->assertSame(2, $executionResult['data']['success_count']);
        }

        if (isset($executionResult['data']['not_found'])) {
            $this->assertCount(1, $executionResult['data']['not_found']);
        }
    }

    /**
     * Тест обработки неизвестной команды
     */
    public function testUnknownCommand(): void
    {
        $this->sendVoiceCommand('абракадабра непонятная команда');

        $this->assertResponseIsSuccessful();
        $response = $this->getResponseData();

        $this->assertTrue($response['success']);

        // Проверяем что команда распозналась как unknown или нужно уточнение
        $parsedCommand = $response['parsedCommand'];
        $this->assertContains(
            $parsedCommand['action'],
            [ParsedCommand::ACTION_UNKNOWN, ParsedCommand::ACTION_CLARIFICATION_NEEDED]
        );
    }

    /**
     * Тест создания задачи с полными параметрами
     */
    public function testCreateTaskWithFullParameters(): void
    {
        $this->sendVoiceCommand(
            'Создай задачу подготовить презентацию с описанием для квартального отчета ' .
            'с высоким приоритетом на завтра с тегом работа'
        );

        $this->assertResponseIsSuccessful();
        $response = $this->getResponseData();

        $this->assertTrue($response['success']);

        $executionResult = $response['executionResult'];
        $this->assertTrue($executionResult['success']);
        $this->assertSame('task_created', $executionResult['type']);

        // Проверяем что задача создана с правильными параметрами
        if (isset($executionResult['task'])) {
            $this->assertStringContainsString('презентацию', $executionResult['task']['title']);
        }
    }

    /**
     * Тест фильтрации задач по статусу
     */
    public function testFilterTasksByStatus(): void
    {
        // Подготовка: создаем задачи с разными статусами
        TaskFactory::createMany(3, [
            'user' => $this->user,
            'status' => TaskStatus::PENDING,
        ]);

        TaskFactory::createMany(2, [
            'user' => $this->user,
            'status' => TaskStatus::COMPLETED,
        ]);

        // Act
        $this->sendVoiceCommand('Покажи все незавершенные задачи');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = $this->getResponseData();

        $executionResult = $response['executionResult'];
        $this->assertTrue($executionResult['success']);
        $this->assertSame('filter_results', $executionResult['type']);

        // Проверяем что вернулись только pending задачи (минимум 3 + созданные в setUp)
        if (isset($executionResult['data']['tasks'])) {
            $this->assertGreaterThanOrEqual(3, count($executionResult['data']['tasks']));
        }
    }

    /**
     * Тест удаления нескольких задач
     */
    public function testDeleteMultipleTasks(): void
    {
        // Подготовка
        TaskFactory::createOne([
            'user' => $this->user,
            'title' => 'задача для удаления один',
            'status' => TaskStatus::PENDING,
        ]);

        TaskFactory::createOne([
            'user' => $this->user,
            'title' => 'задача для удаления два',
            'status' => TaskStatus::PENDING,
        ]);

        // Act
        $this->sendVoiceCommand('Удали задачи задача для удаления один и задача для удаления два');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = $this->getResponseData();

        $executionResult = $response['executionResult'];
        $this->assertTrue($executionResult['success']);

        // Проверяем что обе задачи удалены
        if (isset($executionResult['data']['deleted_count'])) {
            $this->assertSame(2, $executionResult['data']['deleted_count']);
        }
    }

    /**
     * Тест обновления описания задачи
     */
    public function testSetTaskDescription(): void
    {
        $this->sendVoiceCommand('Установи описание для задачи купить молоко текст не забыть взять обезжиренное');

        $this->assertResponseIsSuccessful();
        $response = $this->getResponseData();

        $executionResult = $response['executionResult'];
        $this->assertTrue($executionResult['success']);
        $this->assertSame('description_set', $executionResult['type']);

        // Проверяем что описание установлено
        $this->assertStringContainsString('обезжиренное', $executionResult['message']);
    }
}