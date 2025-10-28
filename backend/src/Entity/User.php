<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\Database\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'user', cascade: ['remove'], orphanRemoval: true)]
    private Collection $tasks;

    #[ORM\OneToMany(targetEntity: Tag::class, mappedBy: 'user', cascade: ['remove'], orphanRemoval: true)]
    private Collection $tags;

    public function __construct()
    {
        parent::__construct();
        $this->tasks = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setUser($this);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            // Set the owning side to null
            if ($task->getUser() === $this) {
                $task->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->setUser($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        if ($this->tags->removeElement($tag)) {
            // Set the owning side to null
            if ($tag->getUser() === $this) {
                $tag->setUser(null);
            }
        }

        return $this;
    }
}
