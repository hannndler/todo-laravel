<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'leader_id',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relación con el líder del equipo
     */
    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /**
     * Relación muchos a muchos con usuarios
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_team')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Relación con las tareas del equipo
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Obtener miembros con rol específico
     */
    public function getMembersByRole(string $role): \Illuminate\Database\Eloquent\Collection
    {
        return $this->members()->wherePivot('role', $role)->get();
    }

    /**
     * Verificar si un usuario es miembro del equipo
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Verificar si un usuario es líder del equipo
     */
    public function isLeader(User $user): bool
    {
        return $this->leader_id === $user->id;
    }

    /**
     * Verificar si un usuario es admin del equipo
     */
    public function isAdmin(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    /**
     * Verificar si un usuario puede gestionar el equipo
     */
    public function canBeManagedBy(User $user): bool
    {
        return $this->isLeader($user) || $this->isAdmin($user);
    }

    /**
     * Agregar miembro al equipo
     */
    public function addMember(User $user, string $role = 'member'): void
    {
        $this->members()->attach($user->id, [
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    /**
     * Remover miembro del equipo
     */
    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);
    }

    /**
     * Scope para equipos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para equipos inactivos
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
}
