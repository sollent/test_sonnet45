<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AI\Service;

use App\Enum\TaskStatus;
use App\Service\AI\Service\StatusMapper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit тесты для StatusMapper
 *
 * Тестирует маппинг текстовых значений статуса в TaskStatus enum.
 */
class StatusMapperTest extends TestCase
{
    private StatusMapper $mapper;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->mapper = new StatusMapper($this->logger);
    }

    /**
     * @dataProvider validStatusProvider
     */
    public function testMapValidStatus(string $input, TaskStatus $expected): void
    {
        $result = $this->mapper->map($input);
        $this->assertSame($expected, $result);
    }

    public static function validStatusProvider(): array
    {
        return [
            // English values
            ['pending', TaskStatus::PENDING],
            ['PENDING', TaskStatus::PENDING],
            ['in_progress', TaskStatus::IN_PROGRESS],
            ['IN_PROGRESS', TaskStatus::IN_PROGRESS],
            ['completed', TaskStatus::COMPLETED],
            ['COMPLETED', TaskStatus::COMPLETED],

            // Russian values - pending
            ['ожидание', TaskStatus::PENDING],
            ['в ожидании', TaskStatus::PENDING],
            ['запланировано', TaskStatus::PENDING],
            ['новая', TaskStatus::PENDING],

            // Russian values - in progress
            ['в работе', TaskStatus::IN_PROGRESS],
            ['в процессе', TaskStatus::IN_PROGRESS],
            ['выполняется', TaskStatus::IN_PROGRESS],

            // Russian values - completed
            ['завершена', TaskStatus::COMPLETED],
            ['завершено', TaskStatus::COMPLETED],
            ['выполнена', TaskStatus::COMPLETED],
            ['готово', TaskStatus::COMPLETED],
        ];
    }

    public function testMapInvalidStatusReturnsNull(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning');

        $result = $this->mapper->map('invalid_status');
        $this->assertNull($result);
    }

    public function testMapEmptyStringReturnsNull(): void
    {
        $result = $this->mapper->map('');
        $this->assertNull($result);
    }

    public function testMapNullReturnsNull(): void
    {
        $result = $this->mapper->map(null);
        $this->assertNull($result);
    }

    public function testMapWithWhitespace(): void
    {
        $result = $this->mapper->map(' completed ');
        $this->assertSame(TaskStatus::COMPLETED, $result);
    }

    public function testMapCaseInsensitive(): void
    {
        $this->assertSame(TaskStatus::PENDING, $this->mapper->map('Pending'));
        $this->assertSame(TaskStatus::IN_PROGRESS, $this->mapper->map('In_Progress'));
        $this->assertSame(TaskStatus::COMPLETED, $this->mapper->map('Completed'));
    }

    public function testMapOrDefaultWithValidStatus(): void
    {
        $result = $this->mapper->mapOrDefault('completed', TaskStatus::PENDING);
        $this->assertSame(TaskStatus::COMPLETED, $result);
    }

    public function testMapOrDefaultWithInvalidStatus(): void
    {
        $result = $this->mapper->mapOrDefault('invalid', TaskStatus::PENDING);
        $this->assertSame(TaskStatus::PENDING, $result);
    }

    public function testIsSupported(): void
    {
        $this->assertTrue($this->mapper->isSupported('pending'));
        $this->assertTrue($this->mapper->isSupported('в работе'));
        $this->assertFalse($this->mapper->isSupported('invalid'));
    }

    public function testGetSupportedValues(): void
    {
        $values = $this->mapper->getSupportedValues();
        $this->assertContains('pending', $values);
        $this->assertContains('в работе', $values);
        $this->assertContains('completed', $values);
    }
}
