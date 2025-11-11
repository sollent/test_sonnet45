<?php

declare(strict_types=1);

namespace App\TestsUtilities\Story;

use App\TestsUtilities\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class AdminUserStory extends Story
{
    public function build(): void
    {
        // Create admin user
        UserFactory::createOne([
            'email'    => 'admin@example.com',
            'password' => 'admin123',
            'roles'    => ['ROLE_ADMIN'],
        ]);

        // Create regular user
        UserFactory::createOne([
            'email'    => 'user@example.com',
            'password' => 'user123',
            'roles'    => ['ROLE_USER'],
        ]);

        // Create Google user with admin rights
        UserFactory::createOne([
            'email'          => 'google-admin@example.com',
            'password'       => null,
            'googleId'       => 'google-admin-id-123',
            'googleUserName' => 'Google Admin User',
            'roles'          => ['ROLE_ADMIN'],
        ]);
    }
}
