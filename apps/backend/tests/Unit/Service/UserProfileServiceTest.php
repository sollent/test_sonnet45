<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\Request\User\UpdatePasswordDto;
use App\Dto\Request\User\UpdateProfileDto;
use App\Entity\User;
use App\Service\UserProfileService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserProfileServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    private UserPasswordHasherInterface $passwordHasher;

    private UserProfileService $service;

    private User $user;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $this->service = new UserProfileService(
            $this->entityManager,
            $this->passwordHasher,
        );

        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setPassword('hashed_password');
        $this->user->setName('John Doe');
        $this->user->setLanguage('en');
        $this->user->setTimezone('UTC');
    }

    /** @test */
    public function testUpdateProfileWithName(): void
    {
        // Arrange
        $dto = new UpdateProfileDto();
        $dto->setName('Jane Smith');

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $result = $this->service->updateProfile($this->user, $dto);

        // Assert
        $this->assertEquals('Jane Smith', $result->getName());
        $this->assertEquals('en', $result->getLanguage()); // Unchanged
        $this->assertEquals('UTC', $result->getTimezone()); // Unchanged
    }

    /** @test */
    public function testUpdateProfileWithLanguage(): void
    {
        // Arrange
        $dto = new UpdateProfileDto();
        $dto->setLanguage('ru');

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $result = $this->service->updateProfile($this->user, $dto);

        // Assert
        $this->assertEquals('ru', $result->getLanguage());
        $this->assertEquals('John Doe', $result->getName()); // Unchanged
    }

    /** @test */
    public function testUpdateProfileWithTimezone(): void
    {
        // Arrange
        $dto = new UpdateProfileDto();
        $dto->setTimezone('America/New_York');

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $result = $this->service->updateProfile($this->user, $dto);

        // Assert
        $this->assertEquals('America/New_York', $result->getTimezone());
        $this->assertEquals('John Doe', $result->getName()); // Unchanged
    }

    /** @test */
    public function testUpdateProfileWithAllFields(): void
    {
        // Arrange
        $dto = new UpdateProfileDto();
        $dto->setName('Alice Wonder');
        $dto->setLanguage('uk');
        $dto->setTimezone('Europe/Kiev');

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $result = $this->service->updateProfile($this->user, $dto);

        // Assert
        $this->assertEquals('Alice Wonder', $result->getName());
        $this->assertEquals('uk', $result->getLanguage());
        $this->assertEquals('Europe/Kiev', $result->getTimezone());
    }

    /** @test */
    public function testUpdateProfileWithNoChanges(): void
    {
        // Arrange
        $dto = new UpdateProfileDto(); // All fields null

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $result = $this->service->updateProfile($this->user, $dto);

        // Assert - nothing changed
        $this->assertEquals('John Doe', $result->getName());
        $this->assertEquals('en', $result->getLanguage());
        $this->assertEquals('UTC', $result->getTimezone());
    }

    /** @test */
    public function testUpdatePasswordSuccessWithCurrentPassword(): void
    {
        // Arrange
        $dto = new UpdatePasswordDto();
        $dto->setCurrentPassword('old_password');
        $dto->setNewPassword('new_secure_password');

        $this->passwordHasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->user, 'old_password')
            ->willReturn(true);

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($this->user, 'new_secure_password')
            ->willReturn('hashed_new_password');

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $this->service->updatePassword($this->user, $dto);

        // Assert
        $this->assertEquals('hashed_new_password', $this->user->getPassword());
    }

    /** @test */
    public function testUpdatePasswordForUserWithoutPassword(): void
    {
        // Arrange - OAuth user without password
        $oauthUser = new User();
        $oauthUser->setEmail('oauth@example.com');
        // No password set

        $dto = new UpdatePasswordDto();
        $dto->setNewPassword('new_password');
        // No current password needed for OAuth users

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($oauthUser, 'new_password')
            ->willReturn('hashed_new_password');

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $this->service->updatePassword($oauthUser, $dto);

        // Assert
        $this->assertEquals('hashed_new_password', $oauthUser->getPassword());
    }

    /** @test */
    public function testUpdatePasswordThrowsExceptionForWrongCurrentPassword(): void
    {
        // Arrange
        $dto = new UpdatePasswordDto();
        $dto->setCurrentPassword('wrong_password');
        $dto->setNewPassword('new_password');

        $this->passwordHasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->user, 'wrong_password')
            ->willReturn(false);

        // Assert
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Неверный текущий пароль');

        // Act
        $this->service->updatePassword($this->user, $dto);
    }

    /** @test */
    public function testUpdatePasswordThrowsExceptionWhenCurrentPasswordMissing(): void
    {
        // Arrange
        $dto = new UpdatePasswordDto();
        $dto->setNewPassword('new_password');
        // currentPassword is null

        // Assert
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Текущий пароль обязателен');

        // Act
        $this->service->updatePassword($this->user, $dto);
    }

    /** @test */
    public function testUpdateNotifications(): void
    {
        // Arrange
        $notifications = [
            'emailNotifications' => true,
            'pushNotifications'  => false,
            'dailyDigest'        => true,
        ];

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $result = $this->service->updateNotifications($this->user, $notifications);

        // Assert
        $this->assertEquals($notifications, $result->getNotificationSettings());
    }

    /** @test */
    public function testUpdateNotificationsWithEmptyArray(): void
    {
        // Arrange
        $notifications = [];

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $result = $this->service->updateNotifications($this->user, $notifications);

        // Assert
        $this->assertEquals([], $result->getNotificationSettings());
    }

    /** @test */
    public function testUpdateProfileReturnsUserInstance(): void
    {
        // Arrange
        $dto = new UpdateProfileDto();
        $dto->setName('Test User');

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $result = $this->service->updateProfile($this->user, $dto);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertSame($this->user, $result);
    }

    /** @test */
    public function testUpdateNotificationsReturnsUserInstance(): void
    {
        // Arrange
        $notifications = ['test' => true];

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $result = $this->service->updateNotifications($this->user, $notifications);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertSame($this->user, $result);
    }
}
