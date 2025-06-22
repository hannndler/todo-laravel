<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'module',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Relación muchos a muchos con roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }

    /**
     * Relación muchos a muchos con usuarios (a través de roles)
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role', 'permission_id', 'user_id')
            ->using(Role::class);
    }

    /**
     * Verificar si es un permiso del sistema
     */
    public function isSystemPermission(): bool
    {
        return $this->is_system;
    }

    /**
     * Scope para permisos del sistema
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope para permisos personalizados
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope para filtrar por módulo
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Obtener todos los módulos disponibles
     */
    public static function getModules(): array
    {
        return static::distinct()->pluck('module')->filter()->toArray();
    }
}
