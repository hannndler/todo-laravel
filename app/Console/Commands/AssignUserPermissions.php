<?php

namespace App\Console\Commands;

use App\Domain\Models\User;
use App\Domain\Models\Role;
use App\Domain\Models\Permission;
use Illuminate\Console\Command;

class AssignUserPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:assign-permissions {email} {--role=user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign permissions to a user by email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $roleName = $this->option('role');

        // Buscar el usuario
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("Usuario con email {$email} no encontrado.");
            return 1;
        }

        // Buscar el rol
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            $this->error("Rol '{$roleName}' no encontrado.");
            return 1;
        }

        // Asignar el rol al usuario
        $user->roles()->sync([$role->id]);

        $this->info("Rol '{$roleName}' asignado exitosamente al usuario {$user->name} ({$user->email})");

        // Mostrar permisos del rol
        $permissions = $role->permissions;
        if ($permissions->count() > 0) {
            $this->info("Permisos del rol '{$roleName}':");
            foreach ($permissions as $permission) {
                $this->line("  - {$permission->name}");
            }
        } else {
            $this->warn("El rol '{$roleName}' no tiene permisos asignados.");
        }

        return 0;
    }
}
