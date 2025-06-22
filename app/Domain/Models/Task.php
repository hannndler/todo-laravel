<?php

namespace App\Domain\Models;

use App\Domain\Enums\TaskPriority;
use App\Domain\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'completed_at',
        'created_by',
        'assigned_to',
        'category_id',
        'team_id',
        'estimated_hours',
        'actual_hours',
        'tags',
        'attachments',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'tags' => 'array',
        'attachments' => 'array',
        'status' => TaskStatus::class,
        'priority' => TaskPriority::class,
    ];

    /**
     * Relación con el usuario que creó la tarea
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con el usuario asignado a la tarea
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Relación con la categoría de la tarea
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relación con el equipo de la tarea
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Verificar si la tarea está completada
     */
    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    /**
     * Verificar si la tarea está cancelada
     */
    public function isCancelled(): bool
    {
        return $this->status->isCancelled();
    }

    /**
     * Verificar si la tarea está vencida
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->isCompleted();
    }

    /**
     * Verificar si la tarea está próxima a vencer (dentro de 3 días)
     */
    public function isDueSoon(): bool
    {
        return $this->due_date &&
               $this->due_date->isFuture() &&
               $this->due_date->diffInDays(now()) <= 3 &&
               !$this->isCompleted();
    }

    /**
     * Marcar la tarea como completada
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => TaskStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Marcar la tarea como en progreso
     */
    public function markAsInProgress(): void
    {
        $this->update(['status' => TaskStatus::IN_PROGRESS]);
    }

    /**
     * Marcar la tarea como cancelada
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => TaskStatus::CANCELLED]);
    }

    /**
     * Verificar si un usuario puede editar la tarea
     */
    public function canBeEditedBy(User $user): bool
    {
        return $this->created_by === $user->id ||
               $this->assigned_to === $user->id ||
               $user->hasRole('admin') ||
               $user->hasRole('manager');
    }

    /**
     * Verificar si un usuario puede eliminar la tarea
     */
    public function canBeDeletedBy(User $user): bool
    {
        return $this->created_by === $user->id ||
               $user->hasRole('admin');
    }

    /**
     * Obtener el progreso de la tarea en porcentaje
     */
    public function getProgressPercentage(): int
    {
        if ($this->estimated_hours && $this->actual_hours) {
            return min(100, round(($this->actual_hours / $this->estimated_hours) * 100));
        }

        return $this->isCompleted() ? 100 : 0;
    }

    /**
     * Scope para tareas completadas
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', TaskStatus::COMPLETED);
    }

    /**
     * Scope para tareas pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', TaskStatus::PENDING);
    }

    /**
     * Scope para tareas en progreso
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', TaskStatus::IN_PROGRESS);
    }

    /**
     * Scope para tareas vencidas
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->where('status', '!=', TaskStatus::COMPLETED);
    }

    /**
     * Scope para tareas asignadas a un usuario
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope para tareas creadas por un usuario
     */
    public function scopeCreatedBy($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope para tareas de un equipo
     */
    public function scopeByTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope para tareas de una categoría
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}
