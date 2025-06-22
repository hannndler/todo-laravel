<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'color',
        'icon',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relación con el usuario que creó la categoría
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con las tareas de esta categoría
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Obtener el número de tareas en esta categoría
     */
    public function getTasksCount(): int
    {
        return $this->tasks()->count();
    }

    /**
     * Obtener el número de tareas completadas en esta categoría
     */
    public function getCompletedTasksCount(): int
    {
        return $this->tasks()->where('status', 'completed')->count();
    }

    /**
     * Obtener el porcentaje de tareas completadas
     */
    public function getCompletionPercentage(): float
    {
        $total = $this->getTasksCount();
        if ($total === 0) {
            return 0;
        }

        return round(($this->getCompletedTasksCount() / $total) * 100, 2);
    }

    /**
     * Verificar si la categoría está activa
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Scope para categorías activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para categorías inactivas
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope para filtrar por creador
     */
    public function scopeByCreator($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }
}
