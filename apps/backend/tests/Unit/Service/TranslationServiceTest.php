<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Service\TranslationService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslationServiceTest extends TestCase
{
    private TranslatorInterface $translator;

    private TranslationService $service;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->service = new TranslationService($this->translator);
    }

    /** @test */
    public function testTranslatePriority(): void
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
    public function testTranslatePriorityWithLocale(): void
    {
        // Arrange
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('task.priority.urgent', [], 'enums', 'en')
            ->willReturn('Urgent');

        // Act
        $result = $this->service->translatePriority(TaskPriority::URGENT, 'en');

        // Assert
        $this->assertEquals('Urgent', $result);
    }

    /** @test */
    public function testTranslateStatus(): void
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
            ->with('task.status.in_progress', [], 'enums', 'uk')
            ->willReturn('В роботі');

        // Act
        $result = $this->service->translateStatus(TaskStatus::IN_PROGRESS, 'uk');

        // Assert
        $this->assertEquals('В роботі', $result);
    }

    /** @test */
    public function testGetAllPriorityTranslations(): void
    {
        // Arrange
        $this->translator
            ->method('trans')
            ->willReturnCallback(function ($key) {
                return match ($key) {
                    'task.priority.low'    => 'Low',
                    'task.priority.medium' => 'Medium',
                    'task.priority.high'   => 'High',
                    'task.priority.urgent' => 'Urgent',
                    default                => $key,
                };
            });

        // Act
        $result = $this->service->getAllPriorityTranslations();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('low', $result); // Keys are enum->value (lowercase)
        $this->assertArrayHasKey('medium', $result);
        $this->assertArrayHasKey('high', $result);
        $this->assertArrayHasKey('urgent', $result);

        // Check structure
        $this->assertArrayHasKey('value', $result['low']);
        $this->assertArrayHasKey('label', $result['low']);
        $this->assertArrayHasKey('color', $result['low']);

        $this->assertEquals('low', $result['low']['value']);
        $this->assertEquals('Low', $result['low']['label']);
        $this->assertEquals('#94a3b8', $result['low']['color']);
    }

    /** @test */
    public function testGetAllStatusTranslations(): void
    {
        // Arrange
        $this->translator
            ->method('trans')
            ->willReturnCallback(function ($key) {
                return match ($key) {
                    'task.status.pending'     => 'Pending',
                    'task.status.in_progress' => 'In Progress',
                    'task.status.completed'   => 'Completed',
                    'task.status.cancelled'   => 'Cancelled',
                    'task.status.archived'    => 'Archived',
                    default                   => $key,
                };
            });

        // Act
        $result = $this->service->getAllStatusTranslations();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('pending', $result); // Keys are enum->value (lowercase with underscore)
        $this->assertArrayHasKey('completed', $result);

        // Check structure
        $this->assertArrayHasKey('value', $result['completed']);
        $this->assertArrayHasKey('label', $result['completed']);
        $this->assertArrayHasKey('color', $result['completed']);

        $this->assertEquals('completed', $result['completed']['value']);
        $this->assertEquals('Completed', $result['completed']['label']);
        $this->assertEquals('#10b981', $result['completed']['color']);
    }

    /** @test */
    public function testGetAllEnumTranslations(): void
    {
        // Arrange
        $this->translator
            ->method('trans')
            ->willReturn('translated');

        // Act
        $result = $this->service->getAllEnumTranslations();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('priorities', $result);
        $this->assertArrayHasKey('statuses', $result);
        $this->assertIsArray($result['priorities']);
        $this->assertIsArray($result['statuses']);
    }

    /** @test */
    public function testPriorityColorsAreCorrect(): void
    {
        // Arrange
        $this->translator->method('trans')->willReturn('translated');

        // Act
        $result = $this->service->getAllPriorityTranslations();

        // Assert
        $this->assertEquals('#94a3b8', $result['low']['color']);
        $this->assertEquals('#3b82f6', $result['medium']['color']);
        $this->assertEquals('#f59e0b', $result['high']['color']);
        $this->assertEquals('#ef4444', $result['urgent']['color']);
    }

    /** @test */
    public function testStatusColorsAreCorrect(): void
    {
        // Arrange
        $this->translator->method('trans')->willReturn('translated');

        // Act
        $result = $this->service->getAllStatusTranslations();

        // Assert
        $this->assertEquals('#94a3b8', $result['pending']['color']);
        $this->assertEquals('#3b82f6', $result['in_progress']['color']);
        $this->assertEquals('#10b981', $result['completed']['color']);
        $this->assertEquals('#ef4444', $result['cancelled']['color']);
    }

    /** @test */
    public function testSetLocale(): void
    {
        $this->markTestSkipped('TranslatorInterface::setLocale() is not mockable (final or does not exist in interface)');
    }

    /** @test */
    public function testGetLocale(): void
    {
        // Arrange
        $this->translator
            ->expects($this->once())
            ->method('getLocale')
            ->willReturn('en');

        // Act
        $result = $this->service->getLocale();

        // Assert
        $this->assertEquals('en', $result);
    }

    /** @test */
    public function testSetAndGetLocale(): void
    {
        $this->markTestSkipped('TranslatorInterface::setLocale() is not mockable (final or does not exist in interface)');
    }

    /** @test */
    public function testGetAllPriorityTranslationsReturnsAllCases(): void
    {
        // Arrange
        $this->translator->method('trans')->willReturn('translated');

        // Act
        $result = $this->service->getAllPriorityTranslations();

        // Assert
        $expectedCount = count(TaskPriority::cases());
        $this->assertCount($expectedCount, $result);
    }

    /** @test */
    public function testGetAllStatusTranslationsReturnsAllCases(): void
    {
        // Arrange
        $this->translator->method('trans')->willReturn('translated');

        // Act
        $result = $this->service->getAllStatusTranslations();

        // Assert
        $expectedCount = count(TaskStatus::cases());
        $this->assertCount($expectedCount, $result);
    }

    /** @test */
    public function testTranslationStructureIncludesAllFields(): void
    {
        // Arrange
        $this->translator->method('trans')->willReturn('Test Label');

        // Act
        $priorities = $this->service->getAllPriorityTranslations();
        $statuses = $this->service->getAllStatusTranslations();

        // Assert - each translation has required fields
        foreach ($priorities as $priority) {
            $this->assertArrayHasKey('value', $priority);
            $this->assertArrayHasKey('label', $priority);
            $this->assertArrayHasKey('color', $priority);
            $this->assertIsString($priority['value']);
            $this->assertIsString($priority['label']);
            $this->assertIsString($priority['color']);
            $this->assertStringStartsWith('#', $priority['color']); // Color is hex
        }

        foreach ($statuses as $status) {
            $this->assertArrayHasKey('value', $status);
            $this->assertArrayHasKey('label', $status);
            $this->assertArrayHasKey('color', $status);
            $this->assertIsString($status['value']);
            $this->assertIsString($status['label']);
            $this->assertIsString($status['color']);
            $this->assertStringStartsWith('#', $status['color']); // Color is hex
        }
    }
}
