<?php

declare(strict_types=1);

namespace App\Dto\Response\Tag;

use App\Entity\Tag;
use DateTimeImmutable;

final class TagResponseDto
{
    public int $id;

    public string $name;

    public string $color;

    public ?string $icon;

    public int $usageCount;

    public DateTimeImmutable $createdAt;

    public DateTimeImmutable $updatedAt;

    public static function fromEntity(Tag $tag): self
    {
        $dto = new self();
        $dto->id = $tag->getId();
        $dto->name = $tag->getName();
        $dto->color = $tag->getColor();
        $dto->icon = $tag->getIcon();
        $dto->usageCount = $tag->getUsageCount();
        $dto->createdAt = $tag->getCreatedAt();
        $dto->updatedAt = $tag->getUpdatedAt();

        return $dto;
    }
}
