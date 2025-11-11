<?php

declare(strict_types=1);

namespace App\Tests\Unit\Serializer\Normalizer;

use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Serializer\Normalizer\TaskEnumNormalizer;
use App\Service\EnumTranslatorService;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class TaskEnumNormalizerTest extends TestCase
{
    private TaskEnumNormalizer $normalizer;

    private EnumTranslatorService $enumTranslator;

    private RequestStack $requestStack;

    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        // Mock TranslatorInterface (used by EnumTranslatorService)
        $this->translator = $this->createMock(TranslatorInterface::class);

        // Create real EnumTranslatorService with mocked translator
        $this->enumTranslator = new EnumTranslatorService($this->translator);

        // Mock RequestStack
        $this->requestStack = $this->createMock(RequestStack::class);

        // Create TaskEnumNormalizer with real service and mocked request stack
        $this->normalizer = new TaskEnumNormalizer($this->enumTranslator, $this->requestStack);
    }

    /** @test */
    public function testNormalizesTaskPriority(): void
    {
        // Arrange
        $priority = TaskPriority::HIGH;

        $request = new Request();
        $request->setLocale('en');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('task.priority.high', [], 'enums', 'en')
            ->willReturn('High');

        // Act
        $result = $this->normalizer->normalize($priority);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('color', $result);
        $this->assertArrayHasKey('icon', $result);
        $this->assertSame('high', $result['value']);
        $this->assertSame('High', $result['label']);
    }

    /** @test */
    public function testNormalizesTaskStatus(): void
    {
        // Arrange
        $status = TaskStatus::IN_PROGRESS;

        $request = new Request();
        $request->setLocale('ru');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('task.status.in_progress', [], 'enums', 'ru')
            ->willReturn('В процессе');

        // Act
        $result = $this->normalizer->normalize($status);

        // Assert
        $this->assertIsArray($result);
        $this->assertSame('in_progress', $result['value']);
        $this->assertSame('В процессе', $result['label']);
        $this->assertIsString($result['color']);
        $this->assertIsString($result['icon']);
    }

    /** @test */
    public function testSupportsNormalizationForEnums(): void
    {
        // Arrange
        $priority = TaskPriority::LOW;
        $status = TaskStatus::PENDING;

        // Act & Assert
        $this->assertTrue($this->normalizer->supportsNormalization($priority));
        $this->assertTrue($this->normalizer->supportsNormalization($status));
    }

    /** @test */
    public function testDoesNotSupportOtherTypes(): void
    {
        // Arrange
        $notEnum = new stdClass();
        $string = 'test';
        $array = ['test'];

        // Act & Assert
        $this->assertFalse($this->normalizer->supportsNormalization($notEnum));
        $this->assertFalse($this->normalizer->supportsNormalization($string));
        $this->assertFalse($this->normalizer->supportsNormalization($array));
    }

    /** @test */
    public function testUsesLocaleFromRequest(): void
    {
        // Arrange
        $priority = TaskPriority::URGENT;

        $request = new Request();
        $request->setLocale('ru');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('task.priority.urgent', [], 'enums', 'ru') // Should use 'ru' from request
            ->willReturn('Срочно');

        // Act
        $result = $this->normalizer->normalize($priority);

        // Assert
        $this->assertSame('Срочно', $result['label']);
    }

    /** @test */
    public function testHandlesNullLocale(): void
    {
        // Arrange
        $priority = TaskPriority::MEDIUM;

        // No current request
        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('task.priority.medium', [], 'enums', null) // Should pass null
            ->willReturn('Medium');

        // Act
        $result = $this->normalizer->normalize($priority);

        // Assert
        $this->assertSame('Medium', $result['label']);
    }

    /** @test */
    public function testGetSupportedTypes(): void
    {
        // Act
        $supportedTypes = $this->normalizer->getSupportedTypes(null);

        // Assert
        $this->assertIsArray($supportedTypes);
        $this->assertArrayHasKey(TaskPriority::class, $supportedTypes);
        $this->assertArrayHasKey(TaskStatus::class, $supportedTypes);
        $this->assertTrue($supportedTypes[TaskPriority::class]);
        $this->assertTrue($supportedTypes[TaskStatus::class]);
    }
}
