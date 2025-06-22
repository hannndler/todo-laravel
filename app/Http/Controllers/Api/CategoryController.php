<?php

namespace App\Http\Controllers\Api;

use App\Domain\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('categories.read')) {
            return response()->json(['message' => 'No tienes permisos para ver categorías'], 403);
        }

        $query = Category::with(['creator']);

        // Si no es admin, solo ver categorías que creó
        if (!$user->isAdmin()) {
            $query->where('created_by', $user->id);
        }

        // Filtros
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $categories = $query->paginate($perPage);

        return response()->json([
            'data' => $categories->items(),
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('categories.manage')) {
            return response()->json(['message' => 'No tienes permisos para crear categorías'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-F]{6}$/i',
            'icon' => 'nullable|string|max:50',
        ]);

        $category = Category::create([
            ...$validated,
            'created_by' => $user->id,
            'color' => $validated['color'] ?? '#6b7280',
        ]);

        $category->load(['creator']);

        return response()->json([
            'message' => 'Categoría creada exitosamente',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('categories.read')) {
            return response()->json(['message' => 'No tienes permisos para ver categorías'], 403);
        }

        $category = Category::with(['creator', 'tasks'])->find($id);

        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada'], 404);
        }

        // Verificar acceso
        if (!$user->isAdmin() && $category->created_by !== $user->id) {
            return response()->json(['message' => 'No tienes acceso a esta categoría'], 403);
        }

        return response()->json(['data' => $category]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('categories.manage')) {
            return response()->json(['message' => 'No tienes permisos para editar categorías'], 403);
        }

        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada'], 404);
        }

        // Verificar si puede editar la categoría
        if (!$user->isAdmin() && $category->created_by !== $user->id) {
            return response()->json(['message' => 'No puedes editar esta categoría'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-F]{6}$/i',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        $category->update($validated);
        $category->load(['creator']);

        return response()->json([
            'message' => 'Categoría actualizada exitosamente',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('categories.manage')) {
            return response()->json(['message' => 'No tienes permisos para eliminar categorías'], 403);
        }

        $category = Category::withCount('tasks')->find($id);

        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada'], 404);
        }

        // Verificar si puede eliminar la categoría
        if (!$user->isAdmin() && $category->created_by !== $user->id) {
            return response()->json(['message' => 'No puedes eliminar esta categoría'], 403);
        }

        // Verificar si tiene tareas asociadas
        if ($category->tasks_count > 0) {
            return response()->json([
                'message' => 'No se puede eliminar la categoría porque tiene tareas asociadas',
                'tasks_count' => $category->tasks_count
            ], 400);
        }

        $category->delete();

        return response()->json(['message' => 'Categoría eliminada exitosamente']);
    }
}
