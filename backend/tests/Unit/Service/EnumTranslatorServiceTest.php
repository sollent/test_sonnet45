<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Service\EnumTranslatorService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class EnumTranslatorServiceTest extends TestCase
{
    private TranslatorInterface $translator;
    private EnumTranslatorService $service;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->service = new EnumTranslatorService($this->translator);
    }

    /** @test */
    public function testTranslatePriorityLow(): void
    {
        // Arrange
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('task.priority.low', [], 'enums', null)
            ->willReturn('Низкий');

        // Act
        $result = $this->service->translatePriority(TaskPriority::LOW);

        // Assert
        $this->assertEquals('Низкий', $result);
    }

    /** @test */
    public function testTranslatePriorityMedium(): void
    {
        // Arrange
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('task.priority.medium', [], 'enums', null)
            ->willReturn('Средний');

        // Act
        $result = $this->service->translatePriority(TaskPriority::MEDIUM);

        // Assert
        $this->assertEquals('Средний', $result);
    }

    /** @test */
    public function testTranslatePriorityHigh(): void
    {
        // Arrange
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('task.priority.high', [], 'enums', null)
            ->willReturn('Высокий');

        // Act
        $result = $this->service->translatePriority(TaskPriority::HIGH);

        // Assert
        $this->assertEquals('Высокий', $result);
    }

    /** @test */
    public function testTranslatePriorityUrgent(): void
    {
        // Arrange
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('task.priority.urgent', [], 'enums', null)
            ->willReturn('Срочно');

        // Act
        $result = $this->service->translatePriority(TaskPriority::URGENT);

        // Assert
        $this->assertEquals('Срочно', $result);
    }

    /** @test */
    public function testTranslatePriorityWithLocale(): void
    {
        // Arrange
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('task.priority.high', [], 'enums', 'en')
            ->willReturn('High');

        // Act
        $result = $this->service->translatePriority(TaskPriority::HIGH, 'en');

        // Assert
        $this->assertEquals('High', $result);
    }

    /** @test */
    public function testTranslateStatusPending(): void
    {
        // Arrange
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('task.status.pending', [], 'enums', null)
            ->willReturn('В ожидании');

        // Act
        $result = $this->service->translateStatus(TaskStatus::PENDING);

        // Assert
        $this->assertEquals('В ожидании', $result);
    }

    /** @test */
    public function testTranslateStatusInProgress(): void
    {
        // Arrange
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('task.status.in_progress', [], 'enums', null)
            ->willReturn('В работе');

        // Act
        $result = $this->service->translateStatus(TaskStatus::IN_PROGRESS);

        // Assert
        $this->assertEquals('В работе', $result);
    }

    /** @test */
    public function testTranslateStatusCompleted(): void
    {
        // Arrange
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('task.status.completed', [], 'enums', null)
            ->willReturn('Завершено');

        // Act
        $result = $this->service->translateStatus(TaskStatus::COMPLETED);

        // Assert
        $this->assertEquals('Завершено', $result);
    }

    /** @test */
    public function testTranslateStatusWithLocale(): void
    {
        // Arrange
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('task.status.completed', [], 'enums', 'uk')
            ->willReturn('Завершено');

        // Act
        $result = $this->service->translateStatus(TaskStatus::COMPLETED, 'uk');

        // Assert
        $this->assertEquals('Завершено', $result);
    }

    /** @test */
    public function testGetAllPriorities(): void
    {
        // Arrange
        $this->translator
            ->method('trans')
            ->willReturnCallback(function ($key) {
                return match ($key) {
                    'task.priority.low' => 'Low',
                    'task.priority.medium' => 'Medium',
                    'task.priority.high' => 'High',
                    'task.priority.urgent' => 'Urgent',
                    default => $key,
                };
            });

        // Act
        $result = $this->service->getAllPriorities();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('low', $result); // Keys are enum->value (lowercase)
        $this->assertArrayHasKey('medium', $result);
        $this->assertArrayHasKey('high', $result);
        $this->assertArrayHasKey('urgent', $result);
        $this->assertEquals('Low', $result['low']);
        $this->assertEquals('Medium', $result['medium']);
        $this->assertEquals('High', $result['high']);
        $this->assertEquals('Urgent', $result['urgent']);
    }

    /** @test */
    public function testGetAllStatuses(): void
    {
        // Arrange
        $this->translator
            ->method('trans')
            ->willReturnCallback(function ($key) {
                return match ($key) {
                    'task.status.pending' => 'Pending',
                    'task.status.in_progress' => 'In Progress',
                    'task.status.completed' => 'Completed',
                    'task.status.cancelled' => 'Cancelled',
                    default => $key,
                };
            });

        // Act
        $result = $this->service->getAllStatuses();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('pending', $result); // Keys are enum->value (lowercase with underscore)
        $this->assertArrayHasKey('in_progress', $result);
        $this->assertArrayHasKey('completed', $result);
        $this->assertArrayHasKey('cancelled', $result);
        $this->assertEquals('Pending', $result['pending']);
        $this->assertEquals('In Progress', $result['in_progress']);
        $this->assertEquals('Completed', $result['completed']);
        $this->assertEquals('Cancelled', $result['cancelled']);
    }

    /** @test */
    public function testGetAllPrioritiesWithLocale(): void
    {
        // Arrange
        $this->translator
            ->method('trans')
            ->willReturnCallback(function ($key, $params, $domain, $locale) {
                if ($locale === 'ru') {
                    return match ($key) {
                        'task.priority.low' => 'Низкий',
                        'task.priority.medium' => 'Средний',
                        'task.priority.high' => 'Высокий',
                        'task.priority.urgent' => 'Срочно',
                        default => $key,
                    };
                }
                return $key;
            });

        // Act
        $result = $this->service->getAllPriorities('ru');

        // Assert
        $this->assertEquals('Низкий', $result['low']);
        $this->assertEquals('Средний', $result['medium']);
        $this->assertEquals('Высокий', $result['high']);
        $this->assertEquals('Срочно', $result['urgent']);
    }

    /** @test */
    public function testGetAllStatusesReturnsAllStatusCases(): void
    {
        // Arrange
        $this->translator
            ->method('trans')
            ->willReturn('translated');

        // Act
        $result = $this->service->getAllStatuses();

        // Assert
        $expectedCount = count(TaskStatus::cases());
        $this->assertCount($expectedCount, $result);
    }

    /** @test */
    public function testGetAllPrioritiesReturnsAllPriorityCases(): void
    {
        // Arrange
        $this->translator
            ->method('trans')
            ->willReturn('translated');

        // Act
        $result = $this->service->getAllPriorities();

        // Assert
        $expectedCount = count(TaskPriority::cases());
        $this->assertCount($expectedCount, $result);
    }
}
