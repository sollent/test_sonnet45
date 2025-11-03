<?php

declare(strict_types=1);

namespace App\Dto\Request\User;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateNotificationsDto
{
    #[Assert\NotNull]
    private array $notifications;

    public function getNotifications(): array
    {
        return $this->notifications;
    }

    public function setNotifications(array $notifications): void
    {
        $this->notifications = $notifications;
    }
}
