<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Relación muchos a muchos con usuarios
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role');
    }

    /**
     * Relación muchos a muchos con permisos
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    /**
     * Verificar si el rol tiene un permiso específico
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('slug', $permission)->exists();
    }

    /**
     * Verificar si el rol tiene cualquiera de los permisos dados
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->permissions()->whereIn('slug', $permissions)->exists();
    }

    /**
     * Verificar si el rol tiene todos los permisos dados
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $rolePermissions = $this->permissions()->whereIn('slug', $permissions)->pluck('slug');
        return count(array_intersect($permissions, $rolePermissions->toArray())) === count($permissions);
    }

    /**
     * Asignar permisos al rol
     */
    public function assignPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    /**
     * Verificar si es un rol del sistema
     */
    public function isSystemRole(): bool
    {
        return $this->is_system;
    }

    /**
     * Scope para roles del sistema
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope para roles personalizados
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }
}
