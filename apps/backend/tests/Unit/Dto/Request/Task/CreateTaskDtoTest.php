<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto\Request\Task;

use App\Dto\Request\Task\CreateTaskDto;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateTaskDtoTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    /** @test */
    public function testValidDtoPassesValidation(): void
    {
        // Arrange
        $dto = new CreateTaskDto();
        $dto->title = 'Valid Task Title';
        $dto->description = 'Valid description';
        $dto->status = TaskStatus::PENDING;
        $dto->priority = TaskPriority::HIGH;

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'Valid DTO should not have validation errors');
    }

    /** @test */
    public function testTitleIsRequired(): void
    {
        // Arrange
        $dto = new CreateTaskDto();
        // title not set (uninitialized property will cause error)

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count(), 'Title should be required');
        $this->assertStringContainsString('title', $violations[0]->getPropertyPath());
    }

    /** @test */
    public function testTitleCannotBeEmpty(): void
    {
        // Arrange
        $dto = new CreateTaskDto();
        $dto->title = '';

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundTitleError = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'title') {
                $foundTitleError = true;
                break;
            }
        }
        $this->assertTrue($foundTitleError, 'Empty title should cause validation error');
    }

    /** @test */
    public function testTitleMaxLength(): void
    {
        // Arrange
        $dto = new CreateTaskDto();
        $dto->title = str_repeat('a', 256); // 256 characters (max is 255)

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count(), 'Title exceeding 255 chars should have violations');
        $foundTitleError = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'title') {
                $foundTitleError = true;
                break;
            }
        }
        $this->assertTrue($foundTitleError, 'Title validation error should be found');
    }

    /** @test */
    public function testDescriptionIsOptional(): void
    {
        // Arrange
        $dto = new CreateTaskDto();
        $dto->title = 'Valid Title';
        $dto->description = null;

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'Description should be optional');
    }

    /** @test */
    public function testDescriptionMaxLength(): void
    {
        // Arrange
        $dto = new CreateTaskDto();
        $dto->title = 'Valid Title';
        $dto->description = str_repeat('a', 5001); // 5001 characters (max is 5000)

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundMaxLengthError = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'description') {
                $foundMaxLengthError = true;
                break;
            }
        }
        $this->assertTrue($foundMaxLengthError, 'Description exceeding 5000 chars should fail');
    }

    /** @test */
    public function testDefaultsAreValid(): void
    {
        // Arrange
        $dto = new CreateTaskDto();
        $dto->title = 'Task with defaults';
        // Use default status, priority, etc.

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'Default values should be valid');
        $this->assertEquals(TaskStatus::PENDING, $dto->status);
        $this->assertEquals(TaskPriority::MEDIUM, $dto->priority);
        $this->assertEquals(0, $dto->sortOrder);
        $this->assertFalse($dto->isArchived);
    }

    /** @test */
    public function testAllOptionalFieldsCanBeNull(): void
    {
        // Arrange
        $dto = new CreateTaskDto();
        $dto->title = 'Task Title';
        $dto->description = null;
        $dto->startDate = null;
        $dto->dueDate = null;
        $dto->parentTaskId = null;
        $dto->recurrence = null;

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'All optional fields can be null');
    }

    /** @test */
    public function testTagsArrayCanBeEmpty(): void
    {
        // Arrange
        $dto = new CreateTaskDto();
        $dto->title = 'Task Title';
        $dto->tags = [];

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'Empty tags array should be valid');
    }

    /** @test */
    public function testMediaIdsArrayCanBeEmpty(): void
    {
        // Arrange
        $dto = new CreateTaskDto();
        $dto->title = 'Task Title';
        $dto->mediaIds = [];

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'Empty mediaIds array should be valid');
    }
}
