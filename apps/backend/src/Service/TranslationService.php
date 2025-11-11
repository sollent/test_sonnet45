<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TranslationService
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Get translated task priority
     */
    public function translatePriority(TaskPriority $priority, ?string $locale = null): string
    {
        $key = sprintf('task.priority.%s', strtolower($priority->value));

        return $this->translator->trans($key, [], 'enums', $locale);
    }

    /**
     * Get translated task status
     */
    public function translateStatus(TaskStatus $status, ?string $locale = null): string
    {
        $key = sprintf('task.status.%s', strtolower(str_replace('_', '_', $status->value)));

        return $this->translator->trans($key, [], 'enums', $locale);
    }

    /**
     * Get all priority translations
     */
    public function getAllPriorityTranslations(?string $locale = null): array
    {
        $translations = [];

        foreach (TaskPriority::cases() as $priority) {
            $translations[$priority->value] = [
                'value' => $priority->value,
                'label' => $this->translatePriority($priority, $locale),
                'color' => $this->getPriorityColor($priority),
            ];
        }

        return $translations;
    }

    /**
     * Get all status translations
     */
    public function getAllStatusTranslations(?string $locale = null): array
    {
        $translations = [];

        foreach (TaskStatus::cases() as $status) {
            $translations[$status->value] = [
                'value' => $status->value,
                'label' => $this->translateStatus($status, $locale),
                'color' => $this->getStatusColor($status),
            ];
        }

        return $translations;
    }

    /**
     * Get all enum translations
     */
    public function getAllEnumTranslations(?string $locale = null): array
    {
        return [
            'priorities' => $this->getAllPriorityTranslations($locale),
            'statuses'   => $this->getAllStatusTranslations($locale),
        ];
    }

    /**
     * Set locale for translator
     */
    public function setLocale(string $locale): void
    {
        $this->translator->setLocale($locale);
    }

    /**
     * Get current locale
     */
    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    /**
     * Get priority color for UI consistency
     */
    private function getPriorityColor(TaskPriority $priority): string
    {
        return match ($priority) {
            TaskPriority::LOW    => '#94a3b8',
            TaskPriority::MEDIUM => '#3b82f6',
            TaskPriority::HIGH   => '#f59e0b',
            TaskPriority::URGENT => '#ef4444',
        };
    }

    /**
     * Get status color for UI consistency
     */
    private function getStatusColor(TaskStatus $status): string
    {
        return match ($status) {
            TaskStatus::PENDING     => '#94a3b8',
            TaskStatus::IN_PROGRESS => '#3b82f6',
            TaskStatus::COMPLETED   => '#10b981',
            TaskStatus::CANCELLED   => '#ef4444',
            TaskStatus::ARCHIVED    => '#6b7280',
        };
    }
}
