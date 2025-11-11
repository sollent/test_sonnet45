<?php

declare(strict_types=1);

namespace App\TestsUtilities\Factory;

use App\Entity\Tag;
use App\Entity\User;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Tag>
 */
final class TagFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Tag::class;
    }

    /**
     * Create a tag for specific user
     */
    public function forUser(User $user): self
    {
        return $this->with([
            'user' => $user,
        ]);
    }

    /**
     * Create a tag with specific name
     */
    public function withName(string $name): self
    {
        return $this->with([
            'name' => $name,
        ]);
    }

    /**
     * Create a tag with specific color
     */
    public function withColor(string $color): self
    {
        return $this->with([
            'color' => $color,
        ]);
    }

    /**
     * Create a frequently used tag
     */
    public function frequentlyUsed(): self
    {
        return $this->with([
            'usageCount' => self::faker()->numberBetween(50, 200),
        ]);
    }

    protected function defaults(): array|callable
    {
        return [
            'name'       => self::faker()->unique()->word(),
            'color'      => self::faker()->hexColor(),
            'user'       => UserFactory::new(),
            'usageCount' => self::faker()->numberBetween(0, 50),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}
