<?php

declare(strict_types=1);

namespace App\Dto\Request\User;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateThemeDto
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['light', 'dark', 'auto'])]
    private string $theme;

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): void
    {
        $this->theme = $theme;
    }
}
