<?php

declare(strict_types=1);

namespace App\TestsUtilities\Factory;

use App\Entity\TaskAttachment;
use App\Entity\Task;
use App\Entity\User;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<TaskAttachment>
 */
final class TaskAttachmentFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return TaskAttachment::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'task' => TaskFactory::new(),
            'fileName' => self::faker()->uuid() . '.txt',
            'originalName' => self::faker()->word() . '.txt',
            'mimeType' => 'text/plain',
            'fileSize' => self::faker()->numberBetween(1000, 100000),
            'fileType' => 'document',
            'filePath' => 'uploads/' . self::faker()->uuid() . '.txt',
            'uploadedBy' => UserFactory::new(),
            'uploadedAt' => \DateTimeImmutable::createFromMutable(
                self::faker()->dateTimeBetween('-30 days', 'now')
            ),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }

    /**
     * Create an image attachment
     */
    public function image(): self
    {
        return $this->with([
            'fileName' => self::faker()->uuid() . '.jpg',
            'originalName' => self::faker()->word() . '.jpg',
            'mimeType' => 'image/jpeg',
            'fileType' => 'image',
            'filePath' => 'uploads/' . self::faker()->uuid() . '.jpg',
        ]);
    }

    /**
     * Create a PDF document attachment
     */
    public function pdf(): self
    {
        return $this->with([
            'fileName' => self::faker()->uuid() . '.pdf',
            'originalName' => self::faker()->word() . '.pdf',
            'mimeType' => 'application/pdf',
            'fileType' => 'document',
            'filePath' => 'uploads/' . self::faker()->uuid() . '.pdf',
        ]);
    }

    /**
     * Create a video attachment
     */
    public function video(): self
    {
        return $this->with([
            'fileName' => self::faker()->uuid() . '.mp4',
            'originalName' => self::faker()->word() . '.mp4',
            'mimeType' => 'video/mp4',
            'fileType' => 'video',
            'filePath' => 'uploads/' . self::faker()->uuid() . '.mp4',
            'fileSize' => self::faker()->numberBetween(1000000, 10000000),
        ]);
    }

    /**
     * Create attachment for specific task
     */
    public function forTask(Task $task): self
    {
        return $this->with([
            'task' => $task,
        ]);
    }

    /**
     * Create attachment uploaded by specific user
     */
    public function uploadedBy(User $user): self
    {
        return $this->with([
            'uploadedBy' => $user,
        ]);
    }

    /**
     * Create a small file
     */
    public function small(): self
    {
        return $this->with([
            'fileSize' => self::faker()->numberBetween(100, 5000),
        ]);
    }

    /**
     * Create a large file
     */
    public function large(): self
    {
        return $this->with([
            'fileSize' => self::faker()->numberBetween(5000000, 10000000),
        ]);
    }
}
