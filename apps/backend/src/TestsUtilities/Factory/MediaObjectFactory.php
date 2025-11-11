<?php

declare(strict_types=1);

namespace App\TestsUtilities\Factory;

use App\Entity\MediaObject;
use App\Entity\User;
use DateTimeImmutable;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<MediaObject>
 */
final class MediaObjectFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return MediaObject::class;
    }

    /**
     * Create an image media object
     */
    public function image(): self
    {
        return $this->with([
            'fileName'      => self::faker()->uuid() . '.jpg',
            'originalName'  => self::faker()->word() . '.jpg',
            'mimeType'      => 'image/jpeg',
            'fileType'      => 'image',
            'filePath'      => '/uploads/media/' . self::faker()->uuid() . '.jpg',
            'thumbnailPath' => '/uploads/media/thumbnails/' . self::faker()->uuid() . '.jpg',
        ]);
    }

    /**
     * Create a PNG image media object
     */
    public function png(): self
    {
        return $this->with([
            'fileName'     => self::faker()->uuid() . '.png',
            'originalName' => self::faker()->word() . '.png',
            'mimeType'     => 'image/png',
            'fileType'     => 'image',
            'filePath'     => '/uploads/media/' . self::faker()->uuid() . '.png',
        ]);
    }

    /**
     * Create a PDF document media object
     */
    public function pdf(): self
    {
        return $this->with([
            'fileName'     => self::faker()->uuid() . '.pdf',
            'originalName' => self::faker()->word() . '.pdf',
            'mimeType'     => 'application/pdf',
            'fileType'     => 'document',
            'filePath'     => '/uploads/media/' . self::faker()->uuid() . '.pdf',
        ]);
    }

    /**
     * Create a video media object
     */
    public function video(): self
    {
        return $this->with([
            'fileName'     => self::faker()->uuid() . '.mp4',
            'originalName' => self::faker()->word() . '.mp4',
            'mimeType'     => 'video/mp4',
            'fileType'     => 'video',
            'filePath'     => '/uploads/media/' . self::faker()->uuid() . '.mp4',
            'fileSize'     => self::faker()->numberBetween(1000000, 10000000),
        ]);
    }

    /**
     * Create a document media object
     */
    public function document(): self
    {
        return $this->with([
            'fileName'     => self::faker()->uuid() . '.docx',
            'originalName' => self::faker()->word() . '.docx',
            'mimeType'     => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'fileType'     => 'document',
            'filePath'     => '/uploads/media/' . self::faker()->uuid() . '.docx',
        ]);
    }

    /**
     * Create media object uploaded by specific user
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

    protected function defaults(): array|callable
    {
        return [
            'fileName'      => self::faker()->uuid() . '.txt',
            'originalName'  => self::faker()->word() . '.txt',
            'mimeType'      => 'text/plain',
            'fileSize'      => self::faker()->numberBetween(1000, 100000),
            'fileType'      => 'other',
            'filePath'      => '/uploads/media/' . self::faker()->uuid() . '.txt',
            'thumbnailPath' => null,
            'uploadedBy'    => UserFactory::new(),
            'createdAt'     => DateTimeImmutable::createFromMutable(
                self::faker()->dateTimeBetween('-30 days', 'now'),
            ),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}
