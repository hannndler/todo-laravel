<?php

namespace App\Domain\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar',
        'bio',
        'phone',
        'position',
        'department',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Relación muchos a muchos con roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    /**
     * Relación muchos a muchos con equipos
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'user_team')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Relación con tareas creadas por el usuario
     */
    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    /**
     * Relación con tareas asignadas al usuario
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /**
     * Relación con equipos donde el usuario es líder
     */
    public function ledTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'leader_id');
    }

    /**
     * Relación con categorías creadas por el usuario
     */
    public function createdCategories(): HasMany
    {
        return $this->hasMany(Category::class, 'created_by');
    }

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('slug', $role)->exists();
    }

    /**
     * Verificar si el usuario tiene cualquiera de los roles dados
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    /**
     * Verificar si el usuario tiene todos los roles dados
     */
    public function hasAllRoles(array $roles): bool
    {
        $userRoles = $this->roles()->whereIn('slug', $roles)->pluck('slug');
        return count(array_intersect($roles, $userRoles->toArray())) === count($roles);
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('slug', $permission);
            })
            ->exists();
    }

    /**
     * Verificar si el usuario tiene cualquiera de los permisos dados
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissions) {
                $query->whereIn('slug', $permissions);
            })
            ->exists();
    }

    /**
     * Verificar si el usuario tiene todos los permisos dados
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $userPermissions = $this->roles()
            ->with('permissions')
            ->get()
            ->flatMap(function ($role) {
                return $role->permissions->pluck('slug');
            })
            ->unique();

        return count(array_intersect($permissions, $userPermissions->toArray())) === count($permissions);
    }

    /**
     * Asignar roles al usuario
     */
    public function assignRoles(array $roleIds): void
    {
        $this->roles()->sync($roleIds);
    }

    /**
     * Agregar un rol al usuario
     */
    public function addRole(Role $role): void
    {
        $this->roles()->attach($role->id);
    }

    /**
     * Remover un rol del usuario
     */
    public function removeRole(Role $role): void
    {
        $this->roles()->detach($role->id);
    }

    /**
     * Verificar si el usuario es administrador
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('super_admin');
    }

    /**
     * Verificar si el usuario es super administrador
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Verificar si el usuario está activo
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Actualizar la fecha de último login
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Obtener el nombre completo del usuario
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Obtener las iniciales del usuario
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';

        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return substr($initials, 0, 2);
    }

    /**
     * Scope para usuarios activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para usuarios inactivos
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope para filtrar por departamento
     */
    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }
}
