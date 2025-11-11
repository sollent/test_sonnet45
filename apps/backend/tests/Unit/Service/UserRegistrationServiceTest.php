<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\Request\User\UserRegistrationRequestDto;
use App\Entity\User;
use App\Exception\User\UserRegistrationException;
use App\Repository\Database\UserRepository;
use App\Service\UserRegistrationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserRegistrationServiceTest extends TestCase
{
    private UserRepository $userRepository;

    private UserPasswordHasherInterface $passwordHasher;

    private TranslatorInterface $translator;

    private UserRegistrationService $userRegistrationService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->userRegistrationService = new UserRegistrationService(
            $this->userRepository,
            $this->passwordHasher,
            $this->translator,
        );
    }

    public function testRegisterSuccessfully(): void
    {
        // Arrange
        $dto = new UserRegistrationRequestDto(
            email: 'newuser@example.com',
            password: 'SecurePassword123',
        );

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'newuser@example.com'])
            ->willReturn(null);

        $hashedPassword = '$2y$13$hashedpassword';
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with(
                $this->callback(fn ($user) => $user instanceof User && $user->getEmail() === 'newuser@example.com'),
                'SecurePassword123',
            )
            ->willReturn($hashedPassword);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(function (User $user) use ($hashedPassword) {
                    return $user->getEmail() === 'newuser@example.com'
                        && $user->getPassword() === $hashedPassword;
                }),
                true,
            );

        // Act
        $result = $this->userRegistrationService->register($dto);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('newuser@example.com', $result->getEmail());
        $this->assertEquals($hashedPassword, $result->getPassword());
    }

    public function testRegisterThrowsExceptionWhenUserExists(): void
    {
        // Arrange
        $dto = new UserRegistrationRequestDto(
            email: 'existing@example.com',
            password: 'password123',
        );

        $existingUser = new User();
        $existingUser->setEmail('existing@example.com');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'existing@example.com'])
            ->willReturn($existingUser);

        $errorMessage = 'A user with this email already exists.';
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('user_registration.messages.exists_with_such_email')
            ->willReturn($errorMessage);

        $this->passwordHasher
            ->expects($this->never())
            ->method('hashPassword');

        $this->userRepository
            ->expects($this->never())
            ->method('save');

        // Assert
        $this->expectException(UserRegistrationException::class);
        $this->expectExceptionMessage($errorMessage);

        // Act
        $this->userRegistrationService->register($dto);
    }

    public function testRegisterHashesPasswordCorrectly(): void
    {
        // Arrange
        $plainPassword = 'MyPlainPassword123!';
        $dto = new UserRegistrationRequestDto(
            email: 'hashtest@example.com',
            password: $plainPassword,
        );

        $this->userRepository
            ->method('findOneBy')
            ->willReturn(null);

        $expectedHashedPassword = '$2y$13$verysecurehash';
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with(
                $this->isInstanceOf(User::class),
                $plainPassword,
            )
            ->willReturn($expectedHashedPassword);

        $savedUser = null;
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class), true)
            ->willReturnCallback(function (User $user) use (&$savedUser) {
                $savedUser = $user;
            });

        // Act
        $result = $this->userRegistrationService->register($dto);

        // Assert
        $this->assertEquals($expectedHashedPassword, $result->getPassword());
    }

    public function testRegisterCallsEraseCredentials(): void
    {
        // Arrange
        $dto = new UserRegistrationRequestDto(
            email: 'credentials@example.com',
            password: 'testpass',
        );

        $this->userRepository
            ->method('findOneBy')
            ->willReturn(null);

        $this->passwordHasher
            ->method('hashPassword')
            ->willReturn('$hashed$');

        $capturedUser = null;
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (User $user) use (&$capturedUser) {
                $capturedUser = $user;
            });

        // Act
        $result = $this->userRegistrationService->register($dto);

        // Assert
        // После eraseCredentials() plainPassword должен быть null
        $this->assertNull($result->getPlainPassword());
    }

    public function testRegisterSavesWithFlushTrue(): void
    {
        // Arrange
        $dto = new UserRegistrationRequestDto(
            email: 'flush@example.com',
            password: 'password',
        );

        $this->userRepository
            ->method('findOneBy')
            ->willReturn(null);

        $this->passwordHasher
            ->method('hashPassword')
            ->willReturn('$hashed$');

        // Assert
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with(
                $this->isInstanceOf(User::class),
                true, // Проверяем что flush = true
            );

        // Act
        $this->userRegistrationService->register($dto);
    }
}
