<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service for translating enum values
 * Centralizes enum translations for API responses
 */
final readonly class EnumTranslatorService
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Translate TaskPriority enum
     */
    public function translatePriority(TaskPriority $priority, ?string $locale = null): string
    {
        $key = sprintf('task.priority.%s', strtolower($priority->value));

        return $this->translator->trans($key, [], 'enums', $locale);
    }

    /**
     * Translate TaskStatus enum
     */
    public function translateStatus(TaskStatus $status, ?string $locale = null): string
    {
        $key = sprintf('task.status.%s', strtolower($status->value));

        return $this->translator->trans($key, [], 'enums', $locale);
    }

    /**
     * Get all priority translations
     *
     * @return array<string, string>
     */
    public function getAllPriorities(?string $locale = null): array
    {
        $priorities = [];

        foreach (TaskPriority::cases() as $priority) {
            $priorities[$priority->value] = $this->translatePriority($priority, $locale);
        }

        return $priorities;
    }

    /**
     * Get all status translations
     *
     * @return array<string, string>
     */
    public function getAllStatuses(?string $locale = null): array
    {
        $statuses = [];

        foreach (TaskStatus::cases() as $status) {
            $statuses[$status->value] = $this->translateStatus($status, $locale);
        }

        return $statuses;
    }
}
