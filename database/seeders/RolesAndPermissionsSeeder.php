<?php

namespace Database\Seeders;

use App\Domain\Models\Permission;
use App\Domain\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos del sistema
        $permissions = [
            // Permisos de tareas
            ['name' => 'Crear Tareas', 'slug' => 'tasks.create', 'description' => 'Permite crear nuevas tareas', 'module' => 'tasks', 'is_system' => true],
            ['name' => 'Ver Tareas', 'slug' => 'tasks.read', 'description' => 'Permite ver tareas', 'module' => 'tasks', 'is_system' => true],
            ['name' => 'Editar Tareas', 'slug' => 'tasks.update', 'description' => 'Permite editar tareas existentes', 'module' => 'tasks', 'is_system' => true],
            ['name' => 'Eliminar Tareas', 'slug' => 'tasks.delete', 'description' => 'Permite eliminar tareas', 'module' => 'tasks', 'is_system' => true],
            ['name' => 'Asignar Tareas', 'slug' => 'tasks.assign', 'description' => 'Permite asignar tareas a otros usuarios', 'module' => 'tasks', 'is_system' => true],

            // Permisos de usuarios
            ['name' => 'Gestionar Usuarios', 'slug' => 'users.manage', 'description' => 'Permite gestionar usuarios del sistema', 'module' => 'users', 'is_system' => true],
            ['name' => 'Ver Usuarios', 'slug' => 'users.read', 'description' => 'Permite ver información de usuarios', 'module' => 'users', 'is_system' => true],

            // Permisos de roles
            ['name' => 'Gestionar Roles', 'slug' => 'roles.manage', 'description' => 'Permite gestionar roles del sistema', 'module' => 'roles', 'is_system' => true],
            ['name' => 'Ver Roles', 'slug' => 'roles.read', 'description' => 'Permite ver roles del sistema', 'module' => 'roles', 'is_system' => true],

            // Permisos de equipos
            ['name' => 'Gestionar Equipos', 'slug' => 'teams.manage', 'description' => 'Permite gestionar equipos', 'module' => 'teams', 'is_system' => true],
            ['name' => 'Ver Equipos', 'slug' => 'teams.read', 'description' => 'Permite ver equipos', 'module' => 'teams', 'is_system' => true],

            // Permisos de categorías
            ['name' => 'Gestionar Categorías', 'slug' => 'categories.manage', 'description' => 'Permite gestionar categorías', 'module' => 'categories', 'is_system' => true],
            ['name' => 'Ver Categorías', 'slug' => 'categories.read', 'description' => 'Permite ver categorías', 'module' => 'categories', 'is_system' => true],

            // Permisos de dashboard
            ['name' => 'Ver Dashboard', 'slug' => 'dashboard.read', 'description' => 'Permite acceder al dashboard', 'module' => 'dashboard', 'is_system' => true],
            ['name' => 'Ver Reportes', 'slug' => 'reports.read', 'description' => 'Permite ver reportes del sistema', 'module' => 'reports', 'is_system' => true],
        ];

        foreach ($permissions as $permissionData) {
            Permission::create($permissionData);
        }

        // Crear roles del sistema
        $roles = [
            [
                'name' => 'Super Administrador',
                'slug' => 'super_admin',
                'description' => 'Acceso total al sistema',
                'is_system' => true,
                'permissions' => Permission::all()->pluck('id')->toArray(),
            ],
            [
                'name' => 'Administrador',
                'slug' => 'admin',
                'description' => 'Gestión de usuarios y tareas',
                'is_system' => true,
                'permissions' => Permission::whereNotIn('slug', ['roles.manage'])->pluck('id')->toArray(),
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Gestión de tareas de su equipo',
                'is_system' => true,
                'permissions' => [
                    'tasks.create', 'tasks.read', 'tasks.update', 'tasks.assign',
                    'teams.read', 'categories.read', 'dashboard.read',
                ],
            ],
            [
                'name' => 'Usuario',
                'slug' => 'user',
                'description' => 'Gestión de sus propias tareas',
                'is_system' => true,
                'permissions' => [
                    'tasks.create', 'tasks.read', 'tasks.update',
                    'categories.read', 'dashboard.read',
                ],
            ],
            [
                'name' => 'Visualizador',
                'slug' => 'viewer',
                'description' => 'Solo lectura de tareas asignadas',
                'is_system' => true,
                'permissions' => [
                    'tasks.read', 'dashboard.read',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::create($roleData);

            // Asignar permisos al rol
            if (is_array($permissions)) {
                $permissionIds = Permission::whereIn('slug', $permissions)->pluck('id')->toArray();
                $role->assignPermissions($permissionIds);
            }
        }
    }
}
