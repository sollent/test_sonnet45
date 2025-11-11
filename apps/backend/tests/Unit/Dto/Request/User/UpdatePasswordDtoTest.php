<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto\Request\User;

use App\Dto\Request\User\UpdatePasswordDto;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdatePasswordDtoTest extends KernelTestCase
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
        $dto = new UpdatePasswordDto();
        $dto->setCurrentPassword('OldPassword123');
        $dto->setNewPassword('NewPassword123');
        $dto->setConfirmPassword('NewPassword123');

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'Valid DTO should not have validation errors');
    }

    /** @test */
    public function testNewPasswordIsRequired(): void
    {
        // Arrange
        $dto = new UpdatePasswordDto();
        $dto->setCurrentPassword('OldPassword123');
        $dto->setConfirmPassword('NewPassword123');
        // newPassword not set

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundNewPasswordError = false;

        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'newPassword') {
                $foundNewPasswordError = true;
                break;
            }
        }
        $this->assertTrue($foundNewPasswordError, 'New password should be required');
    }

    /** @test */
    public function testNewPasswordMinLength(): void
    {
        // Arrange - Password with 7 characters (min is 8)
        $dto = new UpdatePasswordDto();
        $dto->setNewPassword('1234567');
        $dto->setConfirmPassword('1234567');

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count(), 'Password less than 8 chars should have violations');
        $foundNewPasswordError = false;

        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'newPassword') {
                $foundNewPasswordError = true;
                break;
            }
        }
        $this->assertTrue($foundNewPasswordError, 'New password should have validation error');
    }

    /** @test */
    public function testConfirmPasswordIsRequired(): void
    {
        // Arrange
        $dto = new UpdatePasswordDto();
        $dto->setNewPassword('NewPassword123');
        // confirmPassword not set

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundConfirmPasswordError = false;

        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'confirmPassword') {
                $foundConfirmPasswordError = true;
                break;
            }
        }
        $this->assertTrue($foundConfirmPasswordError, 'Confirm password should be required');
    }

    /** @test */
    public function testConfirmPasswordMustMatchNewPassword(): void
    {
        // Arrange
        $dto = new UpdatePasswordDto();
        $dto->setNewPassword('NewPassword123');
        $dto->setConfirmPassword('DifferentPassword123');

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count());
        $foundMismatchError = false;

        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'confirmPassword') {
                $foundMismatchError = true;
                break;
            }
        }
        $this->assertTrue($foundMismatchError, 'Confirm password must match new password');
    }

    /** @test */
    public function testCurrentPasswordIsOptional(): void
    {
        // Arrange - currentPassword can be null (e.g., for OAuth users)
        $dto = new UpdatePasswordDto();
        $dto->setCurrentPassword(null);
        $dto->setNewPassword('NewPassword123');
        $dto->setConfirmPassword('NewPassword123');

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertCount(0, $violations, 'Current password should be optional');
    }

    /** @test */
    public function testCurrentPasswordMinLengthWhenProvided(): void
    {
        // Arrange - Current password with 7 characters (min is 8)
        $dto = new UpdatePasswordDto();
        $dto->setCurrentPassword('1234567');
        $dto->setNewPassword('NewPassword123');
        $dto->setConfirmPassword('NewPassword123');

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count(), 'Current password less than 8 chars should have violations');
        $foundCurrentPasswordError = false;

        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'currentPassword') {
                $foundCurrentPasswordError = true;
                break;
            }
        }
        $this->assertTrue($foundCurrentPasswordError, 'Current password should have validation error');
    }

    /** @test */
    public function testNewPasswordMaxLength(): void
    {
        // Arrange - Password with 256 characters (max is 255)
        $longPassword = str_repeat('a', 256);
        $dto = new UpdatePasswordDto();
        $dto->setNewPassword($longPassword);
        $dto->setConfirmPassword($longPassword);

        // Act
        $violations = $this->validator->validate($dto);

        // Assert
        $this->assertGreaterThan(0, $violations->count(), 'Password exceeding 255 chars should have violations');
        $foundNewPasswordError = false;

        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'newPassword') {
                $foundNewPasswordError = true;
                break;
            }
        }
        $this->assertTrue($foundNewPasswordError, 'New password should have validation error');
    }
}
