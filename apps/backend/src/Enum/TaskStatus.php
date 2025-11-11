<?php

declare(strict_types=1);

namespace App\Enum;

enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING     => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED   => 'Completed',
            self::CANCELLED   => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING     => '#6B7280',     // Gray
            self::IN_PROGRESS => '#3B82F6',  // Blue
            self::COMPLETED   => '#10B981',    // Green
            self::CANCELLED   => '#EF4444',    // Red
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING     => 'pi pi-clock',
            self::IN_PROGRESS => 'pi pi-play',
            self::COMPLETED   => 'pi pi-check',
            self::CANCELLED   => 'pi pi-times',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
