<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto\Request\User;

use App\Dto\Request\User\UserRegistrationRequestDto;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserRegistrationRequestDtoTest extends KernelTestCase
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
        $dto = new UserRegistrationRequestDto(
            email: 'user@example.com',
            password: 'ValidPassword123'
        );

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'Valid DTO should not have validation errors');
    }

    /** @test */
    public function testEmailIsRequired(): void
    {
        // Arrange
        $dto = new UserRegistrationRequestDto(
            email: '',
            password: 'ValidPassword123'
        );

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundEmailError = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'email') {
                $foundEmailError = true;
                break;
            }
        }
        $this->assertTrue($foundEmailError, 'Empty email should cause validation error');
    }

    /** @test */
    public function testEmailMustBeValid(): void
    {
        // Arrange
        $dto = new UserRegistrationRequestDto(
            email: 'invalid-email',
            password: 'ValidPassword123'
        );

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundEmailError = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'email') {
                $foundEmailError = true;
                break;
            }
        }
        $this->assertTrue($foundEmailError, 'Invalid email format should fail validation');
    }

    /** @test */
    public function testPasswordIsRequired(): void
    {
        // Arrange
        $dto = new UserRegistrationRequestDto(
            email: 'user@example.com',
            password: ''
        );

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundPasswordError = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'password') {
                $foundPasswordError = true;
                break;
            }
        }
        $this->assertTrue($foundPasswordError, 'Empty password should cause validation error');
    }

    /** @test */
    public function testPasswordMinLength(): void
    {
        // Arrange - Password with 5 characters (min is 6)
        $dto = new UserRegistrationRequestDto(
            email: 'user@example.com',
            password: '12345'
        );

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundMinLengthError = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'password' &&
                str_contains((string)$violation->getMessage(), 'min')) {
                $foundMinLengthError = true;
                break;
            }
        }
        $this->assertTrue($foundMinLengthError, 'Password less than 6 chars should fail');
    }

    /** @test */
    public function testPasswordMaxLength(): void
    {
        // Arrange - Password with 41 characters (max is 40)
        $dto = new UserRegistrationRequestDto(
            email: 'user@example.com',
            password: str_repeat('a', 41)
        );

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count(), 'Password exceeding 40 chars should have violations');
        $foundPasswordError = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'password') {
                $foundPasswordError = true;
                break;
            }
        }
        $this->assertTrue($foundPasswordError, 'Password should have validation error');
    }

    /** @test */
    public function testPasswordWithMinLengthIsValid(): void
    {
        // Arrange - Password with exactly 6 characters (minimum)
        $dto = new UserRegistrationRequestDto(
            email: 'user@example.com',
            password: '123456'
        );

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'Password with minimum length should be valid');
    }

    /** @test */
    public function testPasswordWithMaxLengthIsValid(): void
    {
        // Arrange - Password with exactly 40 characters (maximum)
        $dto = new UserRegistrationRequestDto(
            email: 'user@example.com',
            password: str_repeat('a', 40)
        );

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'Password with maximum length should be valid');
    }
}
