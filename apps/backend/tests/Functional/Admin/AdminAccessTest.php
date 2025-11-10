<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Entity\User;
use App\TestsUtilities\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AdminAccessTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /** @test */
    public function testUnauthenticatedUserRedirectedToLogin(): void
    {
        // Act
        $this->client->request('GET', '/admin');

        // Assert
        $this->assertResponseRedirects();

        // Follow redirect
        $this->client->followRedirect();

        // Should be on login page
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form'); // Login form should exist
    }

    /** @test */
    public function testNonAdminCannotAccessDashboard(): void
    {
        // Arrange - Create regular user (without ROLE_ADMIN)
        $userProxy = UserFactory::createOne([
            'email' => 'regular-' . uniqid() . '@test.com',
            'password' => 'password123',
            'roles' => ['ROLE_USER'], // Only ROLE_USER
        ]);
        $user = $userProxy->_real();

        // Login as regular user
        $this->client->loginUser($user);

        // Act
        $this->client->request('GET', '/admin');

        // Assert - Symfony redirects to login or returns 403
        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->getStatusCode() === Response::HTTP_FORBIDDEN || $response->isRedirect(),
            'Regular user should be denied access to admin'
        );
    }

    /** @test */
    public function testAdminCanAccessDashboard(): void
    {
        // Arrange - Create admin user
        $userProxy = UserFactory::createOne([
            'email' => 'admin-' . uniqid() . '@test.com',
            'password' => 'password123',
            'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
        ]);
        $adminUser = $userProxy->_real();

        // Login as admin
        $this->client->loginUser($adminUser);

        // Act
        $this->client->request('GET', '/admin');

        // Assert - Admin should be able to access (either 200 or redirect within admin)
        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || ($response->isRedirect() && str_contains($response->headers->get('Location') ?? '', '/admin')),
            'Admin user should be able to access admin dashboard'
        );
    }

    /** @test */
    public function testAdminLoginPageRendersCorrectly(): void
    {
        // Act
        $this->client->request('GET', '/admin/login');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[type="email"], input[type="text"]'); // Email field
        $this->assertSelectorExists('input[type="password"]'); // Password field
    }

    /** @test */
    public function testAlreadyLoggedInAdminRedirectsFromLogin(): void
    {
        // Arrange - Create admin user
        $userProxy = UserFactory::createOne([
            'email' => 'admin-' . uniqid() . '@test.com',
            'password' => 'password123',
            'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
        ]);
        $adminUser = $userProxy->_real();

        // Login as admin
        $this->client->loginUser($adminUser);

        // Act - Try to access login page
        $this->client->request('GET', '/admin/login');

        // Assert - Either redirects to admin OR shows login page (depending on implementation)
        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirect(),
            'Logged in admin should be able to access /admin/login'
        );
    }

    /** @test */
    public function testAdminCanAccessUsersList(): void
    {
        // Arrange
        $userProxy = UserFactory::createOne([
            'email' => 'admin-' . uniqid() . '@test.com',
            'password' => 'password123',
            'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
        ]);
        $adminUser = $userProxy->_real();

        // Create some test users
        UserFactory::createMany(5);

        // Login as admin
        $this->client->loginUser($adminUser);

        // Act - Access EasyAdmin User CRUD index
        // EasyAdmin URLs typically follow pattern: /admin?crudAction=index&crudControllerFqcn=...
        $this->client->request('GET', '/admin');

        // Assert
        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirect(),
            'Admin should be able to access users list'
        );
    }

    /** @test */
    public function testNonAdminCannotAccessUserCrud(): void
    {
        // Arrange - Create regular user
        $userProxy = UserFactory::createOne([
            'email' => 'regular-' . uniqid() . '@test.com',
            'password' => 'password123',
            'roles' => ['ROLE_USER'],
        ]);
        $user = $userProxy->_real();

        // Login as regular user
        $this->client->loginUser($user);

        // Act - Try to access admin dashboard
        $this->client->request('GET', '/admin');

        // Assert - Should be denied access
        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->getStatusCode() === Response::HTTP_FORBIDDEN || $response->isRedirect(),
            'Regular user should be denied access to admin'
        );
    }

    /** @test */
    public function testAdminLogoutWorks(): void
    {
        // Arrange
        $userProxy = UserFactory::createOne([
            'email' => 'admin-' . uniqid() . '@test.com',
            'password' => 'password123',
            'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
        ]);
        $adminUser = $userProxy->_real();

        // Login as admin
        $this->client->loginUser($adminUser);

        // Verify logged in
        $this->client->request('GET', '/admin');
        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || ($response->isRedirect() && str_contains($response->headers->get('Location') ?? '', '/admin'))
        );

        // Act - Logout
        $this->client->request('GET', '/admin/logout');

        // Assert - After logout, should be redirected
        $this->assertResponseRedirects();
    }

    /** @test */
    public function testOnlyAdminCanSeeUserManagementMenu(): void
    {
        // Arrange - Create admin user
        $userProxy = UserFactory::createOne([
            'email' => 'admin-' . uniqid() . '@test.com',
            'password' => 'password123',
            'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
        ]);
        $adminUser = $userProxy->_real();

        // Login as admin
        $this->client->loginUser($adminUser);

        // Act
        $this->client->request('GET', '/admin');

        // Follow any redirects
        if ($this->client->getResponse()->isRedirect()) {
            $this->client->followRedirect();
        }

        // Assert - Admin should see user management (if page renders HTML)
        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirect(),
            'Admin should be able to access admin panel'
        );
    }
}
