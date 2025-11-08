<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\Database\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: '`tag`')]
#[ORM\UniqueConstraint(name: 'unique_tag_per_user', columns: ['name', 'user_id'])]
#[ORM\Index(name: 'idx_tag_user_name', columns: ['user_id', 'name'])]
#[ORM\Index(name: 'idx_tag_user_usage', columns: ['user_id', 'usage_count'])]
#[UniqueEntity(
    fields: ['name', 'user'],
    message: 'tag.name.already_exists',
    errorPath: 'name'
)]
#[ORM\HasLifecycleCallbacks]
class Tag extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['tag:read', 'tag:list', 'task:read', 'task:list'])]
    protected ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'tag.name.not_blank')]
    #[Assert\Length(
        min: 1,
        max: 50,
        minMessage: 'tag.name.min_length',
        maxMessage: 'tag.name.max_length'
    )]
    #[Assert\Regex(
        pattern: '/^[\w\s\-]+$/u',
        message: 'tag.name.invalid_format'
    )]
    #[Groups(['tag:read', 'tag:write', 'tag:list', 'task:read', 'task:list'])]
    private ?string $name = null;

    #[ORM\Column(length: 7)]
    #[Assert\NotBlank(message: 'tag.color.not_blank')]
    #[Assert\Regex(
        pattern: '/^#[0-9A-Fa-f]{6}$/',
        message: 'tag.color.invalid_format'
    )]
    #[Groups(['tag:read', 'tag:write', 'tag:list', 'task:read', 'task:list'])]
    private string $color = '#3B82F6'; // Default blue color

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['tag:read', 'tag:write'])]
    private ?string $icon = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tags')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToMany(targetEntity: Task::class, mappedBy: 'tags')]
    private Collection $tasks;

    #[ORM\Column]
    #[Groups(['tag:read'])]
    private int $usageCount = 0;

    public function __construct()
    {
        parent::__construct();
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
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
            $task->addTag($this);
            $this->updateUsageCount();
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            $task->removeTag($this);
            $this->updateUsageCount();
        }

        return $this;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function updateUsageCount(): static
    {
        $this->usageCount = $this->tasks->count();
        return $this;
    }

    public function setUsageCount(int $usageCount): static
    {
        $this->usageCount = $usageCount;
        return $this;
    }
}
