<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\Database\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity('email')]
#[ORM\Table(name: '`users`')]
class User extends AbstractEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(type: 'string', unique: true)]
    protected string $email;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $password = null;

    protected ?string $plainPassword = null;

    #[ORM\Column(type: 'json')]
    protected array $roles = [];

    #[ORM\Column(type: 'string', nullable: true)]
    protected mixed $googleId = null;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $googleUserName = null;

    public function __toString(): string
    {
        return $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): User
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): User
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): User
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): User
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getGoogleId(): mixed
    {
        return $this->googleId;
    }

    public function setGoogleId(mixed $googleId): User
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getGoogleUserName(): ?string
    {
        return $this->googleUserName;
    }

    public function setGoogleUserName(?string $googleUserName): User
    {
        $this->googleUserName = $googleUserName;

        return $this;
    }

    /**
     * Check if user is authenticated via Google
     */
    public function hasGoogleAuth(): bool
    {
        return $this->googleId !== null;
    }
}
