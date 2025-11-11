<?php

declare(strict_types=1);

namespace App\TestsUtilities\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return User::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'email'    => self::faker()->unique()->safeEmail(),
            'password' => self::faker()->text(),
            'roles'    => [],
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (User $user): void {
                // Хешируем пароль только если он не null (для Google users пароль null)
                if ($user->getPassword() !== null) {
                    $user->setPassword(
                        $this->passwordHasher->hashPassword($user, $user->getPassword()),
                    );
                }
            });
    }
}
