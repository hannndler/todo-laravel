<?php

namespace App\Http\Controllers\Api;

use App\Application\Services\TaskService;
use App\Domain\Enums\TaskPriority;
use App\Domain\Enums\TaskStatus;
use App\Domain\Models\Task;
use App\Helpers\RouteHelper;
use App\Http\Controllers\Controller;
use App\Traits\HasRouteHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    use HasRouteHelpers;

    public function __construct(
        private TaskService $taskService
    ) {}

    /**
     * Display a paginated list of tasks with filters
     *
     * @param Request $request The HTTP request
     * @return JsonResponse JSON response with tasks and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('tasks.read')) {
            return response()->json(['message' => 'No tienes permisos para ver tareas'], 403);
        }

        $filters = $request->only([
            'status', 'priority', 'category_id', 'team_id',
            'assigned_to', 'search', 'sort_by', 'sort_order', 'per_page'
        ]);

        $tasks = $this->taskService->getTasks($user, $filters);

        return $this->apiResponse($tasks->items(), 200, [
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ],
            'links' => $this->generateApiPaginationLinks($tasks),
            'api_url' => $this->getApiUrl(),
            'route_name' => $this->getRouteName('index')
        ]);
    }

    /**
     * Store a newly created task
     *
     * @param Request $request The HTTP request
     * @return JsonResponse JSON response with created task
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('tasks.create')) {
            return response()->json(['message' => 'No tienes permisos para crear tareas'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'due_date' => 'nullable|date|after:today',
            'assigned_to' => 'nullable|exists:users,id',
            'category_id' => 'nullable|exists:categories,id',
            'team_id' => 'nullable|exists:teams,id',
            'estimated_hours' => 'nullable|integer|min:1',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        try {
            $task = $this->taskService->createTask($user, $validated);

            return response()->json([
                'message' => 'Tarea creada exitosamente',
                'data' => $task
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Display the specified task
     *
     * @param string $id The task ID
     * @return JsonResponse JSON response with task data
     */
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('tasks.read')) {
            return response()->json(['message' => 'No tienes permisos para ver tareas'], 403);
        }

        $task = Task::with(['creator', 'assignee', 'category', 'team'])->find($id);

        if (!$task) {
            return response()->json(['message' => 'Tarea no encontrada'], 404);
        }

        if (!$user->isAdmin() && $task->created_by !== $user->id && $task->assigned_to !== $user->id) {
            return response()->json(['message' => 'No tienes acceso a esta tarea'], 403);
        }

        return response()->json(['data' => $task]);
    }

    /**
     * Update the specified task
     *
     * @param Request $request The HTTP request
     * @param string $id The task ID
     * @return JsonResponse JSON response with updated task
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('tasks.update')) {
            return response()->json(['message' => 'No tienes permisos para editar tareas'], 403);
        }

        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Tarea no encontrada'], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'category_id' => 'nullable|exists:categories,id',
            'team_id' => 'nullable|exists:teams,id',
            'estimated_hours' => 'nullable|integer|min:1',
            'actual_hours' => 'nullable|integer|min:1',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        try {
            $task = $this->taskService->updateTask($task, $user, $validated);

            return response()->json([
                'message' => 'Tarea actualizada exitosamente',
                'data' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Remove the specified task
     *
     * @param string $id The task ID
     * @return JsonResponse JSON response with success message
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('tasks.delete')) {
            return response()->json(['message' => 'No tienes permisos para eliminar tareas'], 403);
        }

        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Tarea no encontrada'], 404);
        }

        try {
            $this->taskService->deleteTask($task, $user);

            return response()->json(['message' => 'Tarea eliminada exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Mark a task as completed
     *
     * @param string $id The task ID
     * @return JsonResponse JSON response with updated task
     */
    public function markAsCompleted(string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('tasks.update')) {
            return response()->json(['message' => 'No tienes permisos para actualizar tareas'], 403);
        }

        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Tarea no encontrada'], 404);
        }

        try {
            $task = $this->taskService->markAsCompleted($task, $user);

            return response()->json([
                'message' => 'Tarea marcada como completada',
                'data' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Mark a task as in progress
     *
     * @param string $id The task ID
     * @return JsonResponse JSON response with updated task
     */
    public function markAsInProgress(string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('tasks.update')) {
            return response()->json(['message' => 'No tienes permisos para actualizar tareas'], 403);
        }

        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Tarea no encontrada'], 404);
        }

        try {
            $task = $this->taskService->markAsInProgress($task, $user);

            return response()->json([
                'message' => 'Tarea marcada como en progreso',
                'data' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Mark a task as cancelled
     *
     * @param string $id The task ID
     * @return JsonResponse JSON response with updated task
     */
    public function markAsCancelled(string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('tasks.update')) {
            return response()->json(['message' => 'No tienes permisos para actualizar tareas'], 403);
        }

        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Tarea no encontrada'], 404);
        }

        try {
            $task = $this->taskService->markAsCancelled($task, $user);

            return response()->json([
                'message' => 'Tarea marcada como cancelada',
                'data' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
