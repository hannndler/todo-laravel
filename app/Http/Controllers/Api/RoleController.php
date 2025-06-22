<?php

namespace App\Http\Controllers\Api;

use App\Domain\Models\Permission;
use App\Domain\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('roles.read')) {
            return response()->json(['message' => 'No tienes permisos para ver roles'], 403);
        }

        $query = Role::with(['permissions', 'users']);

        // Filtros
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('is_system')) {
            $query->where('is_system', $request->boolean('is_system'));
        }

        if ($request->has('permission')) {
            $query->whereHas('permissions', function ($q) use ($request) {
                $q->where('slug', $request->permission);
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $roles = $query->paginate($perPage);

        return response()->json([
            'data' => $roles->items(),
            'pagination' => [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('roles.manage')) {
            return response()->json(['message' => 'No tienes permisos para crear roles'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'slug' => 'required|string|max:255|unique:roles',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_system' => false, // Los roles creados por usuarios no son del sistema
        ]);

        // Asignar permisos si se especificaron
        if (isset($validated['permissions'])) {
            $role->assignPermissions($validated['permissions']);
        }

        $role->load(['permissions', 'users']);

        return response()->json([
            'message' => 'Rol creado exitosamente',
            'data' => $role
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('roles.read')) {
            return response()->json(['message' => 'No tienes permisos para ver roles'], 403);
        }

        $role = Role::with(['permissions', 'users'])->find($id);

        if (!$role) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }

        return response()->json(['data' => $role]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('roles.manage')) {
            return response()->json(['message' => 'No tienes permisos para editar roles'], 403);
        }

        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }

        // No permitir editar roles del sistema
        if ($role->isSystemRole()) {
            return response()->json(['message' => 'No se pueden editar roles del sistema'], 400);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:roles,name,' . $id,
            'slug' => 'sometimes|required|string|max:255|unique:roles,slug,' . $id,
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update($validated);

        // Actualizar permisos si se especificaron
        if (isset($validated['permissions'])) {
            $role->assignPermissions($validated['permissions']);
        }

        $role->load(['permissions', 'users']);

        return response()->json([
            'message' => 'Rol actualizado exitosamente',
            'data' => $role
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('roles.manage')) {
            return response()->json(['message' => 'No tienes permisos para eliminar roles'], 403);
        }

        $role = Role::withCount('users')->find($id);

        if (!$role) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }

        // No permitir eliminar roles del sistema
        if ($role->isSystemRole()) {
            return response()->json(['message' => 'No se pueden eliminar roles del sistema'], 400);
        }

        // Verificar si tiene usuarios asociados
        if ($role->users_count > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el rol porque tiene usuarios asociados',
                'users_count' => $role->users_count
            ], 400);
        }

        $role->delete();

        return response()->json(['message' => 'Rol eliminado exitosamente']);
    }

    /**
     * Get all permissions for role management.
     */
    public function permissions(): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('roles.manage')) {
            return response()->json(['message' => 'No tienes permisos para ver permisos'], 403);
        }

        $permissions = Permission::orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy('module');

        return response()->json(['data' => $permissions]);
    }
}
