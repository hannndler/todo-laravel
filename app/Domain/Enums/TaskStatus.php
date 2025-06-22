<?php
namespace App\Domain\Enums;
enum TaskStatus: string {
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::IN_PROGRESS => 'En progreso',
            self::COMPLETED => 'Completado',
            self::CANCELLED => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'bg-yellow-500',
            self::IN_PROGRESS => 'bg-blue-500',
            self::COMPLETED => 'bg-green-500',
            self::CANCELLED => 'bg-red-500',
        };
    }
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }
    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }
    public function canTransitionTo(self $status): bool
    {
        return match($this){
            self::PENDING =>in_array($status, [self::IN_PROGRESS, self::CANCELLED]),
            self::IN_PROGRESS => in_array($status, [self::COMPLETED, self::CANCELLED]),
            self::COMPLETED =>in_array($status,[self::IN_PROGRESS]),
            self::CANCELLED =>in_array($status,[self::PENDING]),
        };
    }
}