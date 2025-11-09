<?php

declare(strict_types=1);

namespace App\Dto\Request\User;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateProfileDto
{
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[Assert\Length(max: 10)]
    private ?string $language = null;

    #[Assert\Timezone]
    private ?string $timezone = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): void
    {
        $this->timezone = $timezone;
    }
}
