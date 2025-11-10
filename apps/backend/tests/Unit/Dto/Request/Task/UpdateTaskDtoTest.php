<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto\Request\Task;

use App\Dto\Request\Task\UpdateTaskDto;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateTaskDtoTest extends KernelTestCase
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
        $dto = new UpdateTaskDto();
        $dto->title = 'Updated Task Title';
        $dto->description = 'Updated description';
        $dto->status = TaskStatus::IN_PROGRESS;
        $dto->priority = TaskPriority::URGENT;

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'Valid DTO should not have validation errors');
    }

    /** @test */
    public function testAllFieldsAreOptional(): void
    {
        // Arrange - Empty DTO (all fields null)
        $dto = new UpdateTaskDto();

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'All fields should be optional for partial updates');
    }

    /** @test */
    public function testTitleMinLengthWhenProvided(): void
    {
        // Arrange
        $dto = new UpdateTaskDto();
        $dto->title = ''; // Empty string (less than min: 1)

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundError = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'title') {
                $foundError = true;
                break;
            }
        }
        $this->assertTrue($foundError, 'Empty title should fail validation when provided');
    }

    /** @test */
    public function testTitleMaxLengthWhenProvided(): void
    {
        // Arrange
        $dto = new UpdateTaskDto();
        $dto->title = str_repeat('a', 256); // 256 characters (max is 255)

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundMaxLengthError = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'title') {
                $foundMaxLengthError = true;
                break;
            }
        }
        $this->assertTrue($foundMaxLengthError, 'Title exceeding 255 chars should fail');
    }

    /** @test */
    public function testDescriptionMaxLengthWhenProvided(): void
    {
        // Arrange
        $dto = new UpdateTaskDto();
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
    public function testCanUpdateOnlyOneField(): void
    {
        // Arrange - Only update title
        $dto = new UpdateTaskDto();
        $dto->title = 'New Title';

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'Should be able to update only one field');
    }

    /** @test */
    public function testCanUpdateStatusAndPriority(): void
    {
        // Arrange
        $dto = new UpdateTaskDto();
        $dto->status = TaskStatus::COMPLETED;
        $dto->priority = TaskPriority::LOW;

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'Should be able to update status and priority');
    }

    /** @test */
    public function testNullValuesAreValidForOptionalFields(): void
    {
        // Arrange
        $dto = new UpdateTaskDto();
        $dto->title = 'Valid Title';
        $dto->description = null;
        $dto->startDate = null;
        $dto->dueDate = null;
        $dto->tags = null;
        $dto->mediaIds = null;

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'Null values should be valid for optional fields');
    }
}
