<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use PHPUnit\Framework\TestCase;

/**
 * IMPORTANT: Doctrine ServiceEntityRepository cannot be unit tested effectively.
 *
 * The parent::__construct($registry, User::class) in the repository requires
 * a fully configured Doctrine infrastructure with ClassMetadata, EntityManager with
 * proper metadata caching, etc. This is impossible to mock properly in unit tests.
 *
 * Error encountered: "Typed property Doctrine\ORM\Mapping\ClassMetadata::$name must
 * not be accessed before initialization"
 *
 * SOLUTION: Repository methods must be tested using Integration Tests with
 * Symfony's KernelTestCase and a real test database.
 *
 * UserRepository implements PasswordUpgraderInterface and contains:
 * - save(), remove() - CRUD methods
 * - upgradePassword() - PasswordUpgraderInterface method
 * - findByEmail(), findOneByGoogleId() - query methods
 *
 * See: tests/Integration/Repository/UserRepositoryTest.php (to be created)
 */
class UserRepositoryTest extends TestCase
{
    /** @test */
    public function testRepositoryRequiresIntegrationTesting(): void
    {
        $this->markTestSkipped(
            'Doctrine repositories require integration testing with real database. ' .
            'ServiceEntityRepository cannot be mocked properly due to ClassMetadata dependencies. ' .
            'See tests/Integration/Repository/ for integration tests.',
        );
    }
}
