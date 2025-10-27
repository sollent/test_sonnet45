<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\User;
use App\Repository\Database\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserRepositoryTest extends TestCase
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        // Создаем partial mock UserRepository - мокаем только метод getEntityManager
        $this->userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityManager'])
            ->getMock();
            
        $this->userRepository
            ->method('getEntityManager')
            ->willReturn($this->entityManager);
    }

    public function testSaveWithFlushTrue(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Act
        $this->userRepository->save($user, true);
    }

    public function testSaveWithFlushFalse(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        // Act
        $this->userRepository->save($user, false);
    }

    public function testSaveDefaultFlushFalse(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        // Act
        $this->userRepository->save($user);
    }

    public function testRemoveWithFlushTrue(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('remove@example.com');

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($user);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Act
        $this->userRepository->remove($user, true);
    }

    public function testRemoveWithFlushFalse(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('remove@example.com');

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($user);

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        // Act
        $this->userRepository->remove($user, false);
    }

    public function testUpgradePasswordSuccessfully(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('upgrade@example.com');
        $user->setPassword('oldHashedPassword');

        $newHashedPassword = '$2y$13$newHashedPassword';

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Act
        $this->userRepository->upgradePassword($user, $newHashedPassword);

        // Assert
        $this->assertEquals($newHashedPassword, $user->getPassword());
    }

    public function testUpgradePasswordThrowsExceptionForUnsupportedUser(): void
    {
        // Arrange
        $unsupportedUser = $this->createMock(PasswordAuthenticatedUserInterface::class);

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        // Assert
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessageMatches('/Instances of .* are not supported/');

        // Act
        $this->userRepository->upgradePassword($unsupportedUser, 'newPassword');
    }
}
