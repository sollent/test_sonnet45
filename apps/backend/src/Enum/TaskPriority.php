<?php

declare(strict_types=1);

namespace App\Enum;

enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function getLabel(): string
    {
        return match ($this) {
            self::LOW    => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH   => 'High',
            self::URGENT => 'Urgent',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::LOW    => '#6B7280',     // Gray
            self::MEDIUM => '#3B82F6',   // Blue
            self::HIGH   => '#F59E0B',     // Amber
            self::URGENT => '#EF4444',   // Red
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::LOW    => 'pi pi-chevron-down',
            self::MEDIUM => 'pi pi-minus',
            self::HIGH   => 'pi pi-chevron-up',
            self::URGENT => 'pi pi-exclamation-triangle',
        };
    }

    public function getWeight(): int
    {
        return match ($this) {
            self::LOW    => 1,
            self::MEDIUM => 2,
            self::HIGH   => 3,
            self::URGENT => 4,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
