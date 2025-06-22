<?php

namespace App\Domain\Enums;

enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function label(): string
    {
        return match($this) {
            self::LOW => 'Baja',
            self::MEDIUM => 'Media',
            self::HIGH => 'Alta',
            self::URGENT => 'Urgente',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::LOW => 'green',
            self::MEDIUM => 'blue',
            self::HIGH => 'orange',
            self::URGENT => 'red',
        };
    }

    public function weight(): int
    {
        return match($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::URGENT => 4,
        };
    }

    public function isUrgent(): bool
    {
        return $this === self::URGENT;
    }

    public function isHigh(): bool
    {
        return in_array($this, [self::HIGH, self::URGENT]);
    }
}
