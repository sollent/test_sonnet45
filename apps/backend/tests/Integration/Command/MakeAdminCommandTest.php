<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\TestsUtilities\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\ResetDatabase;

class MakeAdminCommandTest extends KernelTestCase
{
    use ResetDatabase;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();

        $application = new Application(self::$kernel);
        $command = $application->find('app:make-admin');
        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function testMakeUserAdmin(): void
    {
        // Arrange - Create regular user
        $userProxy = UserFactory::createOne([
            'email' => 'user@test.com',
            'password' => 'password123',
            'roles' => ['ROLE_USER'],
        ]);

        // Act - Execute command
        $exitCode = $this->commandTester->execute([
            'email' => 'user@test.com',
        ]);

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('ROLE_ADMIN granted', $this->commandTester->getDisplay());

        // Verify user has ROLE_ADMIN
        $user = $userProxy->_refresh()->_real();
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
    }

    /** @test */
    public function testCannotMakeNonExistentUserAdmin(): void
    {
        // Act - Execute command with non-existent email
        $exitCode = $this->commandTester->execute([
            'email' => 'nonexistent@test.com',
        ]);

        // Assert
        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('not found', $this->commandTester->getDisplay());
    }

    /** @test */
    public function testUserAlreadyAdmin(): void
    {
        // Arrange - Create user who is already admin
        UserFactory::createOne([
            'email' => 'admin@test.com',
            'password' => 'password123',
            'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
        ]);

        // Act - Execute command
        $exitCode = $this->commandTester->execute([
            'email' => 'admin@test.com',
        ]);

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('already has ROLE_ADMIN', $this->commandTester->getDisplay());
    }

    /** @test */
    public function testCommandRequiresEmailArgument(): void
    {
        // Act & Assert - Execute without email argument should throw exception
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments');

        $this->commandTester->execute([]);
    }

    /** @test */
    public function testCommandPersistsChangesToDatabase(): void
    {
        // Arrange - Create regular user
        $userProxy = UserFactory::createOne([
            'email' => 'persist@test.com',
            'password' => 'password123',
            'roles' => ['ROLE_USER'],
        ]);
        $userId = $userProxy->_real()->getId();

        // Act - Execute command
        $this->commandTester->execute([
            'email' => 'persist@test.com',
        ]);

        // Assert - Clear entity manager and reload user from DB
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $entityManager->clear();

        $userRepository = self::getContainer()->get('App\Repository\Database\UserRepository');
        $reloadedUser = $userRepository->find($userId);

        $this->assertNotNull($reloadedUser);
        $this->assertContains('ROLE_ADMIN', $reloadedUser->getRoles());
    }
}
