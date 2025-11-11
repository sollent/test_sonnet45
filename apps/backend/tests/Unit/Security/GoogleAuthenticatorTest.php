<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Repository\Database\UserRepository;
use App\Security\GoogleAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class GoogleAuthenticatorTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    private UserRepository $userRepository;

    private GoogleAuthenticator $googleAuthenticator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->entityManager
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        $this->googleAuthenticator = new GoogleAuthenticator($this->entityManager);
    }

    public function testLoadUserFromDecodedJwtWithExistingUser(): void
    {
        // Arrange
        $jwt = new stdClass();
        $jwt->email = 'existing@example.com';
        $jwt->sub = 'google-id-123';
        $jwt->name = 'Existing User';

        $existingUser = new User();
        $existingUser->setEmail('existing@example.com');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'existing@example.com'])
            ->willReturn($existingUser);

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        // Act
        $result = $this->googleAuthenticator->loadUserFromDecodedJwt($jwt);

        // Assert
        $this->assertSame($existingUser, $result);
        $this->assertEquals('existing@example.com', $result->getEmail());
    }

    public function testLoadUserFromDecodedJwtCreatesNewUser(): void
    {
        // Arrange
        $jwt = new stdClass();
        $jwt->email = 'new@example.com';
        $jwt->sub = 'google-id-456';
        $jwt->name = 'New User';

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'new@example.com'])
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (User $user) {
                return $user->getEmail() === 'new@example.com'
                    && $user->getGoogleId() === 'google-id-456'
                    && $user->getGoogleUserName() === 'New User';
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Act
        $result = $this->googleAuthenticator->loadUserFromDecodedJwt($jwt);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('new@example.com', $result->getEmail());
        $this->assertEquals('google-id-456', $result->getGoogleId());
        $this->assertEquals('New User', $result->getGoogleUserName());
    }

    public function testLoadUserFromDecodedJwtWithMissingName(): void
    {
        // Arrange
        $jwt = new stdClass();
        $jwt->email = 'noname@example.com';
        $jwt->sub = 'google-id-789';
        // Намеренно не устанавливаем $jwt->name

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (User $user) {
                return $user->getGoogleUserName() === 'Google User';
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Act
        $result = $this->googleAuthenticator->loadUserFromDecodedJwt($jwt);

        // Assert
        $this->assertEquals('Google User', $result->getGoogleUserName());
    }

    public function testLoadUserFromDecodedJwtThrowsExceptionWhenEmailMissing(): void
    {
        // Arrange
        $jwt = new stdClass();
        $jwt->sub = 'google-id-999';
        // Намеренно не устанавливаем $jwt->email

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Email not found in Google token');

        // Act
        $this->googleAuthenticator->loadUserFromDecodedJwt($jwt);
    }

    public function testLoadUserFromDecodedJwtWithMissingGoogleId(): void
    {
        // Arrange
        $jwt = new stdClass();
        $jwt->email = 'nosub@example.com';
        $jwt->name = 'User Without Sub';
        // Намеренно не устанавливаем $jwt->sub

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (User $user) {
                return $user->getGoogleId() === null;
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Act
        $result = $this->googleAuthenticator->loadUserFromDecodedJwt($jwt);

        // Assert
        $this->assertNull($result->getGoogleId());
    }
}
