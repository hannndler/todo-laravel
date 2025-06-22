<?php

namespace App\Http\Controllers\Api;

use App\Domain\Models\Team;
use App\Domain\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('teams.read')) {
            return response()->json(['message' => 'No tienes permisos para ver equipos'], 403);
        }

        $query = Team::with(['leader', 'members']);

        // Si no es admin, solo ver equipos donde es miembro
        if (!$user->isAdmin()) {
            $query->whereHas('members', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // Filtros
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('leader_id')) {
            $query->where('leader_id', $request->leader_id);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $teams = $query->paginate($perPage);

        return response()->json([
            'data' => $teams->items(),
            'pagination' => [
                'current_page' => $teams->currentPage(),
                'last_page' => $teams->lastPage(),
                'per_page' => $teams->perPage(),
                'total' => $teams->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('teams.manage')) {
            return response()->json(['message' => 'No tienes permisos para crear equipos'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'leader_id' => 'nullable|exists:users,id',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-F]{6}$/i',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id',
        ]);

        $team = Team::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'leader_id' => $validated['leader_id'] ?? $user->id,
            'color' => $validated['color'] ?? '#3b82f6',
        ]);

        // Agregar creador como miembro admin
        $team->addMember($user, 'admin');

        // Agregar otros miembros si se especificaron
        if (isset($validated['members'])) {
            foreach ($validated['members'] as $memberId) {
                $member = User::find($memberId);
                if ($member && $member->id !== $user->id) {
                    $team->addMember($member, 'member');
                }
            }
        }

        $team->load(['leader', 'members']);

        return response()->json([
            'message' => 'Equipo creado exitosamente',
            'data' => $team
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('teams.read')) {
            return response()->json(['message' => 'No tienes permisos para ver equipos'], 403);
        }

        $team = Team::with(['leader', 'members', 'tasks'])->find($id);

        if (!$team) {
            return response()->json(['message' => 'Equipo no encontrado'], 404);
        }

        // Verificar acceso
        if (!$user->isAdmin() && !$team->hasMember($user)) {
            return response()->json(['message' => 'No tienes acceso a este equipo'], 403);
        }

        return response()->json(['data' => $team]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('teams.manage')) {
            return response()->json(['message' => 'No tienes permisos para editar equipos'], 403);
        }

        $team = Team::find($id);

        if (!$team) {
            return response()->json(['message' => 'Equipo no encontrado'], 404);
        }

        // Verificar si puede gestionar el equipo
        if (!$team->canBeManagedBy($user)) {
            return response()->json(['message' => 'No puedes editar este equipo'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'leader_id' => 'nullable|exists:users,id',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-F]{6}$/i',
            'is_active' => 'sometimes|boolean',
        ]);

        // Verificar que el nuevo líder sea miembro del equipo
        if (isset($validated['leader_id'])) {
            $newLeader = User::find($validated['leader_id']);
            if (!$newLeader || !$team->hasMember($newLeader)) {
                return response()->json(['message' => 'El líder debe ser miembro del equipo'], 400);
            }
        }

        $team->update($validated);
        $team->load(['leader', 'members']);

        return response()->json([
            'message' => 'Equipo actualizado exitosamente',
            'data' => $team
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('teams.manage')) {
            return response()->json(['message' => 'No tienes permisos para eliminar equipos'], 403);
        }

        $team = Team::withCount('tasks')->find($id);

        if (!$team) {
            return response()->json(['message' => 'Equipo no encontrado'], 404);
        }

        // Verificar si puede eliminar el equipo
        if (!$team->canBeManagedBy($user)) {
            return response()->json(['message' => 'No puedes eliminar este equipo'], 403);
        }

        // Verificar si tiene tareas asociadas
        if ($team->tasks_count > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el equipo porque tiene tareas asociadas',
                'tasks_count' => $team->tasks_count
            ], 400);
        }

        $team->delete();

        return response()->json(['message' => 'Equipo eliminado exitosamente']);
    }

    /**
     * Add member to team.
     */
    public function addMember(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('teams.manage')) {
            return response()->json(['message' => 'No tienes permisos para gestionar equipos'], 403);
        }

        $team = Team::find($id);

        if (!$team) {
            return response()->json(['message' => 'Equipo no encontrado'], 404);
        }

        if (!$team->canBeManagedBy($user)) {
            return response()->json(['message' => 'No puedes gestionar este equipo'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|in:member,lead,admin',
        ]);

        $member = User::find($validated['user_id']);

        if ($team->hasMember($member)) {
            return response()->json(['message' => 'El usuario ya es miembro del equipo'], 400);
        }

        $role = $validated['role'] ?? 'member';
        $team->addMember($member, $role);

        $team->load(['leader', 'members']);

        return response()->json([
            'message' => 'Miembro agregado exitosamente',
            'data' => $team
        ]);
    }

    /**
     * Remove member from team.
     */
    public function removeMember(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('teams.manage')) {
            return response()->json(['message' => 'No tienes permisos para gestionar equipos'], 403);
        }

        $team = Team::find($id);

        if (!$team) {
            return response()->json(['message' => 'Equipo no encontrado'], 404);
        }

        if (!$team->canBeManagedBy($user)) {
            return response()->json(['message' => 'No puedes gestionar este equipo'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $member = User::find($validated['user_id']);

        if (!$team->hasMember($member)) {
            return response()->json(['message' => 'El usuario no es miembro del equipo'], 400);
        }

        // No permitir remover al líder
        if ($team->isLeader($member)) {
            return response()->json(['message' => 'No se puede remover al líder del equipo'], 400);
        }

        $team->removeMember($member);

        $team->load(['leader', 'members']);

        return response()->json([
            'message' => 'Miembro removido exitosamente',
            'data' => $team
        ]);
    }
}
