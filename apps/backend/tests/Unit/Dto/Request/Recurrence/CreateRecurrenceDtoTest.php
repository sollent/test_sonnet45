<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto\Request\Recurrence;

use App\Dto\Request\Recurrence\CreateRecurrenceDto;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateRecurrenceDtoTest extends KernelTestCase
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
        $dto = new CreateRecurrenceDto();
        $dto->recurrenceType = 'daily';

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'Valid DTO should not have validation errors');
    }

    /** @test */
    public function testRecurrenceTypeIsRequired(): void
    {
        // Arrange
        $dto = new CreateRecurrenceDto();
        // recurrenceType not set

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundError = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'recurrenceType') {
                $foundError = true;
                break;
            }
        }
        $this->assertTrue($foundError, 'RecurrenceType should be required');
    }

    /** @test */
    public function testRecurrenceTypeMustBeValid(): void
    {
        // Arrange
        $dto = new CreateRecurrenceDto();
        $dto->recurrenceType = 'invalid_type';

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundChoiceError = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'recurrenceType') {
                $foundChoiceError = true;
                break;
            }
        }
        $this->assertTrue($foundChoiceError, 'Invalid recurrence type should fail validation');
    }

    /** @test */
    public function testAllValidRecurrenceTypesAccepted(): void
    {
        // Arrange & Act & Assert
        $validTypes = ['daily', 'weekly', 'monthly', 'yearly', 'custom'];

        foreach ($validTypes as $type) {
            $dto = new CreateRecurrenceDto();
            $dto->recurrenceType = $type;

            $violations = $this->validator->validate($dto);

            $this->assertCount(0, $violations, "Recurrence type '{$type}' should be valid");
        }
    }

    /** @test */
    public function testIntervalMustBePositiveWhenProvided(): void
    {
        // Arrange
        $dto = new CreateRecurrenceDto();
        $dto->recurrenceType = 'custom';
        $dto->interval = 0; // Must be positive

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundError = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'interval') {
                $foundError = true;
                break;
            }
        }
        $this->assertTrue($foundError, 'Interval must be positive');
    }

    /** @test */
    public function testDaysOfWeekMustBeInRange(): void
    {
        // Arrange - Day 8 is invalid (must be 1-7)
        $dto = new CreateRecurrenceDto();
        $dto->recurrenceType = 'weekly';
        $dto->daysOfWeek = [1, 8]; // 8 is out of range

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundRangeError = false;
        foreach ($violations as $violation) {
            if (str_contains($violation->getPropertyPath(), 'daysOfWeek')) {
                $foundRangeError = true;
                break;
            }
        }
        $this->assertTrue($foundRangeError, 'Days of week must be in range 1-7');
    }

    /** @test */
    public function testDayOfMonthMustBeInRange(): void
    {
        // Arrange - Day 32 is invalid (must be 1-31)
        $dto = new CreateRecurrenceDto();
        $dto->recurrenceType = 'monthly';
        $dto->dayOfMonth = 32;

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundRangeError = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'dayOfMonth') {
                $foundRangeError = true;
                break;
            }
        }
        $this->assertTrue($foundRangeError, 'Day of month must be in range 1-31');
    }

    /** @test */
    public function testMonthOfYearMustBeInRange(): void
    {
        // Arrange - Month 13 is invalid (must be 1-12)
        $dto = new CreateRecurrenceDto();
        $dto->recurrenceType = 'yearly';
        $dto->monthOfYear = 13;

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundRangeError = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'monthOfYear') {
                $foundRangeError = true;
                break;
            }
        }
        $this->assertTrue($foundRangeError, 'Month of year must be in range 1-12');
    }

    /** @test */
    public function testMaxOccurrencesMustBePositiveWhenProvided(): void
    {
        // Arrange
        $dto = new CreateRecurrenceDto();
        $dto->recurrenceType = 'daily';
        $dto->maxOccurrences = 0; // Must be positive

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundError = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'maxOccurrences') {
                $foundError = true;
                break;
            }
        }
        $this->assertTrue($foundError, 'Max occurrences must be positive');
    }

    /** @test */
    public function testOptionalFieldsCanBeNull(): void
    {
        // Arrange
        $dto = new CreateRecurrenceDto();
        $dto->recurrenceType = 'daily';
        $dto->interval = null;
        $dto->daysOfWeek = null;
        $dto->dayOfMonth = null;
        $dto->monthOfYear = null;
        $dto->endDate = null;
        $dto->maxOccurrences = null;
        $dto->timeOfDay = null;

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'All optional fields can be null');
    }
}
