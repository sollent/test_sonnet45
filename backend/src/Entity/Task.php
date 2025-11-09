<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Repository\Database\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: '`task`')]
#[ORM\Index(name: 'idx_task_user_parent', columns: ['user_id', 'parent_task_id'])]
#[ORM\Index(name: 'idx_task_user_status', columns: ['user_id', 'status'])]
#[ORM\Index(name: 'idx_task_user_priority', columns: ['user_id', 'priority'])]
#[ORM\Index(name: 'idx_task_user_archived', columns: ['user_id', 'is_archived'])]
#[ORM\Index(name: 'idx_task_user_due_date', columns: ['user_id', 'due_date'])]
#[ORM\Index(name: 'idx_task_user_completed_at', columns: ['user_id', 'completed_at'])]
#[ORM\Index(name: 'idx_task_user_created_at', columns: ['user_id', 'created_at'])]
#[ORM\Index(name: 'idx_task_user_parent_archived', columns: ['user_id', 'parent_task_id', 'is_archived'])]
#[ORM\Index(name: 'idx_task_user_parent_status', columns: ['user_id', 'parent_task_id', 'status'])]
#[ORM\Index(name: 'idx_task_user_status_archived', columns: ['user_id', 'status', 'is_archived'])]
#[ORM\Index(name: 'idx_task_user_due_status', columns: ['user_id', 'due_date', 'status'])]
#[ORM\HasLifecycleCallbacks]
class Task extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['task:read', 'task:list'])]
    protected ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'task.title.not_blank')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'task.title.min_length',
        maxMessage: 'task.title.max_length'
    )]
    #[Groups(['task:read', 'task:write', 'task:list'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 5000,
        maxMessage: 'task.description.max_length'
    )]
    #[Groups(['task:read', 'task:write'])]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 20, enumType: TaskStatus::class)]
    #[Groups(['task:read', 'task:write', 'task:list'])]
    private TaskStatus $status = TaskStatus::PENDING;

    #[ORM\Column(type: 'string', length: 20, enumType: TaskPriority::class)]
    #[Groups(['task:read', 'task:write', 'task:list'])]
    private TaskPriority $priority = TaskPriority::MEDIUM;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['task:read', 'task:write', 'task:list'])]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['task:read', 'task:write', 'task:list'])]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['task:read'])]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['task:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subtasks')]
    #[Groups(['task:read'])]
    private ?self $parentTask = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parentTask', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['task:read'])]
    private Collection $subtasks;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'tasks', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'task_tags')]
    #[Groups(['task:read', 'task:write', 'task:list'])]
    private Collection $tags;

    #[ORM\ManyToMany(targetEntity: MediaObject::class)]
    #[ORM\JoinTable(name: 'task_media')]
    #[Groups(['task:read', 'task:write'])]
    private Collection $mediaObjects;

    #[ORM\OneToMany(targetEntity: TaskAttachment::class, mappedBy: 'task', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['task:read'])]
    private Collection $attachments;

    #[ORM\Column]
    #[Groups(['task:read'])]
    private int $sortOrder = 0;

    #[ORM\Column]
    #[Groups(['task:read'])]
    private bool $isArchived = false;

    #[ORM\OneToOne(targetEntity: RecurrenceRule::class, mappedBy: 'templateTask', cascade: ['persist', 'remove'])]
    private ?RecurrenceRule $recurrenceRule = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['task:read'])]
    private bool $isRecurringTemplate = false;

    #[ORM\ManyToOne(targetEntity: RecurrenceRule::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?RecurrenceRule $generatedFromRule = null;

    public function __construct()
    {
        parent::__construct();
        $this->subtasks = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->mediaObjects = new ArrayCollection();
        $this->attachments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function setStatus(TaskStatus $status): static
    {
        $this->status = $status;
        
        // Auto-set completedAt when status changes to completed
        if ($status === TaskStatus::COMPLETED && $this->completedAt === null) {
            $this->completedAt = new \DateTimeImmutable();
        } elseif ($status !== TaskStatus::COMPLETED) {
            $this->completedAt = null;
        }
        
        return $this;
    }

    public function getPriority(): TaskPriority
    {
        return $this->priority;
    }

    public function setPriority(TaskPriority $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;
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

    public function getParentTask(): ?self
    {
        return $this->parentTask;
    }

    public function setParentTask(?self $parentTask): static
    {
        $this->parentTask = $parentTask;
        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSubtasks(): Collection
    {
        return $this->subtasks;
    }

    public function addSubtask(self $subtask): static
    {
        if (!$this->subtasks->contains($subtask)) {
            $this->subtasks->add($subtask);
            $subtask->setParentTask($this);
        }

        return $this;
    }

    public function removeSubtask(self $subtask): static
    {
        if ($this->subtasks->removeElement($subtask)) {
            // Set the owning side to null
            if ($subtask->getParentTask() === $this) {
                $subtask->setParentTask(null);
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
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function isArchived(): bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived): static
    {
        $this->isArchived = $isArchived;
        return $this;
    }

    /**
     * @return Collection<int, MediaObject>
     */
    public function getMediaObjects(): Collection
    {
        return $this->mediaObjects;
    }

    public function addMediaObject(MediaObject $mediaObject): static
    {
        if (!$this->mediaObjects->contains($mediaObject)) {
            $this->mediaObjects->add($mediaObject);
        }

        return $this;
    }

    public function removeMediaObject(MediaObject $mediaObject): static
    {
        $this->mediaObjects->removeElement($mediaObject);

        return $this;
    }
    
    public function clearMediaObjects(): static
    {
        $this->mediaObjects->clear();

        return $this;
    }

    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(TaskAttachment $attachment): static
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setTask($this);
        }

        return $this;
    }

    public function removeAttachment(TaskAttachment $attachment): static
    {
        if ($this->attachments->removeElement($attachment)) {
            if ($attachment->getTask() === $this) {
                $attachment->setTask(null);
            }
        }

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->status === TaskStatus::COMPLETED;
    }

    public function isOverdue(): bool
    {
        if ($this->dueDate === null || $this->isCompleted()) {
            return false;
        }
        
        return $this->dueDate < new \DateTimeImmutable();
    }

    public function getCompletionProgress(): float
    {
        if ($this->subtasks->isEmpty()) {
            return $this->isCompleted() ? 100.0 : 0.0;
        }
        
        $completed = $this->subtasks->filter(fn($task) => $task->isCompleted())->count();
        return ($completed / $this->subtasks->count()) * 100;
    }

    public function getRecurrenceRule(): ?RecurrenceRule
    {
        return $this->recurrenceRule;
    }

    public function setRecurrenceRule(?RecurrenceRule $recurrenceRule): self
    {
        $this->recurrenceRule = $recurrenceRule;
        return $this;
    }

    public function isRecurringTemplate(): bool
    {
        return $this->isRecurringTemplate;
    }

    public function setIsRecurringTemplate(bool $isRecurringTemplate): self
    {
        $this->isRecurringTemplate = $isRecurringTemplate;
        return $this;
    }

    public function getGeneratedFromRule(): ?RecurrenceRule
    {
        return $this->generatedFromRule;
    }

    public function setGeneratedFromRule(?RecurrenceRule $generatedFromRule): self
    {
        $this->generatedFromRule = $generatedFromRule;
        return $this;
    }
}
