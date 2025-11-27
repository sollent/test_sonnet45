<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AI\Service;

use App\Enum\TaskPriority;
use App\Service\AI\Service\PriorityMapper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit тесты для PriorityMapper
 *
 * Тестирует маппинг текстовых значений приоритета в TaskPriority enum.
 */
class PriorityMapperTest extends TestCase
{
    private PriorityMapper $mapper;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->mapper = new PriorityMapper($this->logger);
    }

    /**
     * @dataProvider validPriorityProvider
     */
    public function testMapValidPriority(string $input, TaskPriority $expected): void
    {
        $result = $this->mapper->map($input);
        $this->assertSame($expected, $result);
    }

    public static function validPriorityProvider(): array
    {
        return [
            // English values
            ['low', TaskPriority::LOW],
            ['LOW', TaskPriority::LOW],
            ['Low', TaskPriority::LOW],
            ['medium', TaskPriority::MEDIUM],
            ['MEDIUM', TaskPriority::MEDIUM],
            ['high', TaskPriority::HIGH],
            ['HIGH', TaskPriority::HIGH],
            ['urgent', TaskPriority::URGENT],
            ['URGENT', TaskPriority::URGENT],

            // Russian values
            ['низкий', TaskPriority::LOW],
            ['низкая', TaskPriority::LOW],
            ['средний', TaskPriority::MEDIUM],
            ['средняя', TaskPriority::MEDIUM],
            ['высокий', TaskPriority::HIGH],
            ['высокая', TaskPriority::HIGH],
            ['срочный', TaskPriority::URGENT],
            ['срочная', TaskPriority::URGENT],
        ];
    }

    public function testMapInvalidPriorityReturnsMedium(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning');

        $result = $this->mapper->map('invalid_priority');
        $this->assertSame(TaskPriority::MEDIUM, $result);
    }

    public function testMapEmptyStringReturnsMedium(): void
    {
        $result = $this->mapper->map('');
        $this->assertSame(TaskPriority::MEDIUM, $result);
    }

    public function testMapNullReturnsMedium(): void
    {
        $result = $this->mapper->map(null);
        $this->assertSame(TaskPriority::MEDIUM, $result);
    }

    public function testMapWithWhitespace(): void
    {
        $result = $this->mapper->map(' high ');
        $this->assertSame(TaskPriority::HIGH, $result);
    }

    public function testMapCaseInsensitive(): void
    {
        $this->assertSame(TaskPriority::LOW, $this->mapper->map('Low'));
        $this->assertSame(TaskPriority::MEDIUM, $this->mapper->map('Medium'));
        $this->assertSame(TaskPriority::HIGH, $this->mapper->map('High'));
        $this->assertSame(TaskPriority::URGENT, $this->mapper->map('Urgent'));
    }

    public function testIsSupported(): void
    {
        $this->assertTrue($this->mapper->isSupported('low'));
        $this->assertTrue($this->mapper->isSupported('высокий'));
        $this->assertFalse($this->mapper->isSupported('invalid'));
    }

    public function testGetSupportedValues(): void
    {
        $values = $this->mapper->getSupportedValues();
        $this->assertContains('low', $values);
        $this->assertContains('высокий', $values);
        $this->assertContains('urgent', $values);
    }
}
