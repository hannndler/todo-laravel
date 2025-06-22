<?php

namespace App\Http\Controllers\Api;

use App\Domain\Models\Role;
use App\Domain\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('users.read')) {
            return response()->json(['message' => 'No tienes permisos para ver usuarios'], 403);
        }

        $query = User::with(['roles', 'teams']);

        // Si no es admin, solo ver usuarios de sus equipos
        if (!$user->isAdmin()) {
            $query->whereHas('teams', function ($q) use ($user) {
                $q->whereHas('members', function ($subQ) use ($user) {
                    $subQ->where('user_id', $user->id);
                });
            });
        }

        // Filtros
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('department')) {
            $query->where('department', $request->department);
        }

        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('slug', $request->role);
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json([
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('users.manage')) {
            return response()->json(['message' => 'No tienes permisos para crear usuarios'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', Password::defaults()],
            'avatar' => 'nullable|string|url',
            'bio' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $newUser = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'avatar' => $validated['avatar'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'position' => $validated['position'] ?? null,
            'department' => $validated['department'] ?? null,
        ]);

        // Asignar roles si se especificaron
        if (isset($validated['roles'])) {
            $newUser->assignRoles($validated['roles']);
        } else {
            // Asignar rol por defecto (user)
            $defaultRole = Role::where('slug', 'user')->first();
            if ($defaultRole) {
                $newUser->addRole($defaultRole);
            }
        }

        $newUser->load(['roles', 'teams']);

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'data' => $newUser
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('users.read')) {
            return response()->json(['message' => 'No tienes permisos para ver usuarios'], 403);
        }

        $targetUser = User::with(['roles', 'teams', 'createdTasks', 'assignedTasks'])->find($id);

        if (!$targetUser) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Verificar acceso
        if (!$user->isAdmin() && $targetUser->id !== $user->id) {
            // Verificar si están en el mismo equipo
            $hasCommonTeam = $user->teams()->whereHas('members', function ($q) use ($targetUser) {
                $q->where('user_id', $targetUser->id);
            })->exists();

            if (!$hasCommonTeam) {
                return response()->json(['message' => 'No tienes acceso a este usuario'], 403);
            }
        }

        return response()->json(['data' => $targetUser]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('users.manage')) {
            return response()->json(['message' => 'No tienes permisos para editar usuarios'], 403);
        }

        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Verificar si puede editar el usuario
        if (!$user->isAdmin() && $targetUser->id !== $user->id) {
            return response()->json(['message' => 'No puedes editar este usuario'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username,' . $id,
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'avatar' => 'nullable|string|url',
            'bio' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        // Solo admins pueden cambiar roles
        if (isset($validated['roles']) && !$user->isAdmin()) {
            unset($validated['roles']);
        }

        // Actualizar contraseña si se proporcionó
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $targetUser->update($validated);

        // Actualizar roles si se especificaron y el usuario es admin
        if (isset($validated['roles']) && $user->isAdmin()) {
            $targetUser->assignRoles($validated['roles']);
        }

        $targetUser->load(['roles', 'teams']);

        return response()->json([
            'message' => 'Usuario actualizado exitosamente',
            'data' => $targetUser
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('users.manage')) {
            return response()->json(['message' => 'No tienes permisos para eliminar usuarios'], 403);
        }

        $targetUser = User::withCount(['createdTasks', 'assignedTasks'])->find($id);

        if (!$targetUser) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Verificar si puede eliminar el usuario
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'No puedes eliminar usuarios'], 403);
        }

        // No permitir eliminar al usuario actual
        if ($targetUser->id === $user->id) {
            return response()->json(['message' => 'No puedes eliminar tu propia cuenta'], 400);
        }

        // Verificar si tiene tareas asociadas
        if ($targetUser->created_tasks_count > 0 || $targetUser->assigned_tasks_count > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el usuario porque tiene tareas asociadas',
                'created_tasks_count' => $targetUser->created_tasks_count,
                'assigned_tasks_count' => $targetUser->assigned_tasks_count
            ], 400);
        }

        $targetUser->delete();

        return response()->json(['message' => 'Usuario eliminado exitosamente']);
    }

    /**
     * Get current user profile.
     */
    public function profile(): JsonResponse
    {
        $user = Auth::user();
        $user->load(['roles', 'teams', 'createdTasks', 'assignedTasks']);

        return response()->json(['data' => $user]);
    }

    /**
     * Update current user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username,' . $user->id,
            'avatar' => 'nullable|string|url',
            'bio' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
        ]);

        $user->update($validated);
        $user->load(['roles', 'teams']);

        return response()->json([
            'message' => 'Perfil actualizado exitosamente',
            'data' => $user
        ]);
    }
}
