<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AI\Response;

use App\Service\AI\Response\CommandResponse;
use PHPUnit\Framework\TestCase;

/**
 * Unit тесты для CommandResponse DTO
 *
 * Тестирует создание и методы доступа к данным ответа команды.
 */
class CommandResponseTest extends TestCase
{
    public function testSuccessFactoryMethod(): void
    {
        $response = CommandResponse::success(
            'task_created',
            'Задача успешно создана',
            ['task_id' => 123]
        );

        $this->assertTrue($response->isSuccess());
        $this->assertSame('task_created', $response->getType());
        $this->assertSame('Задача успешно создана', $response->getMessage());
        $this->assertSame(['task_id' => 123], $response->getData());
        $this->assertEmpty($response->getErrors());
    }

    public function testFailureFactoryMethod(): void
    {
        $response = CommandResponse::failure(
            'task_not_found',
            'Задача не найдена',
            ['search' => 'test'],
            ['Not found in database']
        );

        $this->assertFalse($response->isSuccess());
        $this->assertSame('task_not_found', $response->getType());
        $this->assertSame('Задача не найдена', $response->getMessage());
        $this->assertSame(['search' => 'test'], $response->getData());
        $this->assertSame(['Not found in database'], $response->getErrors());
    }

    public function testSuccessWithEmptyData(): void
    {
        $response = CommandResponse::success('completed', 'Готово');

        $this->assertTrue($response->isSuccess());
        $this->assertEmpty($response->getData());
    }

    public function testFailureWithEmptyErrors(): void
    {
        $response = CommandResponse::failure('error', 'Ошибка');

        $this->assertFalse($response->isSuccess());
        $this->assertEmpty($response->getErrors());
    }

    public function testDataImmutability(): void
    {
        $data = ['key' => 'value'];
        $response = CommandResponse::success('test', 'Test', $data);

        // Изменение оригинального массива не должно влиять на response
        $data['key'] = 'changed';

        $this->assertSame('value', $response->getData()['key']);
    }

    public function testToArray(): void
    {
        $response = CommandResponse::success(
            'task_completed',
            'Задача завершена',
            ['task_id' => 42]
        );

        $array = $response->toArray();

        $this->assertIsArray($array);
        $this->assertTrue($array['success']);
        $this->assertSame('task_completed', $array['type']);
        $this->assertSame('Задача завершена', $array['message']);
        // Данные распаковываются в корень массива
        $this->assertSame(42, $array['task_id']);
    }

    public function testComplexData(): void
    {
        $complexData = [
            'task' => [
                'id' => 1,
                'title' => 'Test Task',
                'subtasks' => [
                    ['id' => 2, 'title' => 'Subtask 1'],
                    ['id' => 3, 'title' => 'Subtask 2'],
                ],
            ],
            'metadata' => [
                'created_at' => '2025-01-01',
                'tags' => ['work', 'important'],
            ],
        ];

        $response = CommandResponse::success('complex', 'Complex data', $complexData);

        $this->assertSame($complexData, $response->getData());
        $this->assertSame('Test Task', $response->getData()['task']['title']);
        $this->assertCount(2, $response->getData()['task']['subtasks']);
    }

    public function testMultipleErrors(): void
    {
        $errors = [
            'Validation error 1',
            'Validation error 2',
            'Database error',
        ];

        $response = CommandResponse::failure('validation_failed', 'Ошибка валидации', [], $errors);

        $this->assertCount(3, $response->getErrors());
        $this->assertSame($errors, $response->getErrors());
    }
}