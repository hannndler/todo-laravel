<?php

namespace App\Application\Services;

use App\Domain\Enums\TaskPriority;
use App\Domain\Enums\TaskStatus;
use App\Domain\Models\Task;
use App\Domain\Models\Team;
use App\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Get paginated tasks with filters and permission-based access control
     *
     * @param User $user The authenticated user
     * @param array $filters Array of filters to apply (status, priority, category_id, etc.)
     * @return LengthAwarePaginator Paginated collection of tasks
     */
    public function getTasks(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Task::with(['creator', 'assignee', 'category', 'team']);

        if (!$user->isAdmin()) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('assigned_to', $user->id);
            });
        }

        $this->applyFilters($query, $filters);

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Create a new task with business logic validation
     *
     * @param User $user The user creating the task
     * @param array $data Task data to create
     * @return Task The created task
     * @throws \Exception When user lacks permissions
     */
    public function createTask(User $user, array $data): Task
    {
        if (isset($data['assigned_to']) && $data['assigned_to'] !== $user->id) {
            if (!$user->hasPermission('tasks.assign')) {
                throw new \Exception('No tienes permisos para asignar tareas');
            }
        }

        if (isset($data['team_id'])) {
            $team = Team::find($data['team_id']);
            if (!$team || !$team->hasMember($user)) {
                throw new \Exception('No tienes acceso a este equipo');
            }
        }

        $task = Task::create([
            ...$data,
            'created_by' => $user->id,
            'status' => $data['status'] ?? TaskStatus::PENDING,
            'priority' => $data['priority'] ?? TaskPriority::MEDIUM,
        ]);

        $task->load(['creator', 'assignee', 'category', 'team']);

        if (isset($data['assigned_to']) && $data['assigned_to'] !== $user->id) {
            $assignee = User::find($data['assigned_to']);
            if ($assignee) {
                $this->notificationService->sendTaskAssignmentNotification($task, $assignee);
            }
        }

        return $task;
    }

    /**
     * Update an existing task with business logic validation
     *
     * @param Task $task The task to update
     * @param User $user The user updating the task
     * @param array $data Data to update
     * @return Task The updated task
     * @throws \Exception When user lacks permissions
     */
    public function updateTask(Task $task, User $user, array $data): Task
    {
        if (!$task->canBeEditedBy($user)) {
            throw new \Exception('No puedes editar esta tarea');
        }

        if (isset($data['assigned_to']) && $data['assigned_to'] !== $user->id) {
            if (!$user->hasPermission('tasks.assign')) {
                throw new \Exception('No tienes permisos para asignar tareas');
            }
        }

        if (isset($data['team_id'])) {
            $team = Team::find($data['team_id']);
            if (!$team || !$team->hasMember($user)) {
                throw new \Exception('No tienes acceso a este equipo');
            }
        }

        $oldStatus = $task->status;
        $oldAssigneeId = $task->assigned_to;

        if (isset($data['status']) && $data['status'] === TaskStatus::COMPLETED) {
            $data['completed_at'] = now();
        }

        $task->update($data);
        $task->load(['creator', 'assignee', 'category', 'team']);

        $this->handleTaskUpdateNotifications($task, $oldStatus, $oldAssigneeId, $data);

        return $task;
    }

    /**
     * Delete a task with permission validation
     *
     * @param Task $task The task to delete
     * @param User $user The user deleting the task
     * @return void
     * @throws \Exception When user lacks permissions
     */
    public function deleteTask(Task $task, User $user): void
    {
        if (!$task->canBeDeletedBy($user)) {
            throw new \Exception('No puedes eliminar esta tarea');
        }

        $task->delete();
    }

    /**
     * Mark a task as completed
     *
     * @param Task $task The task to mark as completed
     * @param User $user The user marking the task
     * @return Task The updated task
     * @throws \Exception When user lacks permissions or task is already completed
     */
    public function markAsCompleted(Task $task, User $user): Task
    {
        if (!$task->canBeEditedBy($user)) {
            throw new \Exception('No puedes editar esta tarea');
        }

        if ($task->isCompleted()) {
            throw new \Exception('La tarea ya está completada');
        }

        $oldStatus = $task->status;
        $task->markAsCompleted();
        $task->load(['creator', 'assignee', 'category', 'team']);

        $this->notificationService->sendTaskStatusChangeNotification($task, $oldStatus, TaskStatus::COMPLETED);

        return $task;
    }

    /**
     * Mark a task as in progress
     *
     * @param Task $task The task to mark as in progress
     * @param User $user The user marking the task
     * @return Task The updated task
     * @throws \Exception When user lacks permissions or task is already in progress
     */
    public function markAsInProgress(Task $task, User $user): Task
    {
        if (!$task->canBeEditedBy($user)) {
            throw new \Exception('No puedes editar esta tarea');
        }

        if ($task->status === TaskStatus::IN_PROGRESS) {
            throw new \Exception('La tarea ya está en progreso');
        }

        $oldStatus = $task->status;
        $task->markAsInProgress();
        $task->load(['creator', 'assignee', 'category', 'team']);

        $this->notificationService->sendTaskStatusChangeNotification($task, $oldStatus, TaskStatus::IN_PROGRESS);

        return $task;
    }

    /**
     * Mark a task as cancelled
     *
     * @param Task $task The task to mark as cancelled
     * @param User $user The user marking the task
     * @return Task The updated task
     * @throws \Exception When user lacks permissions or task is already cancelled
     */
    public function markAsCancelled(Task $task, User $user): Task
    {
        if (!$task->canBeEditedBy($user)) {
            throw new \Exception('No puedes editar esta tarea');
        }

        if ($task->isCancelled()) {
            throw new \Exception('La tarea ya está cancelada');
        }

        $oldStatus = $task->status;
        $task->markAsCancelled();
        $task->load(['creator', 'assignee', 'category', 'team']);

        $this->notificationService->sendTaskStatusChangeNotification($task, $oldStatus, TaskStatus::CANCELLED);

        return $task;
    }

    /**
     * Get task statistics for dashboard
     *
     * @param User $user The user to get statistics for
     * @return array Array of task statistics
     */
    public function getTaskStats(User $user): array
    {
        $query = Task::query();

        if (!$user->isAdmin()) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('assigned_to', $user->id);
            });
        }

        $total = $query->count();
        $completed = (clone $query)->where('status', TaskStatus::COMPLETED)->count();
        $pending = (clone $query)->where('status', TaskStatus::PENDING)->count();
        $inProgress = (clone $query)->where('status', TaskStatus::IN_PROGRESS)->count();
        $cancelled = (clone $query)->where('status', TaskStatus::CANCELLED)->count();
        $overdue = (clone $query)->where('due_date', '<', now())
            ->where('status', '!=', TaskStatus::COMPLETED)
            ->count();

        $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'overdue' => $overdue,
            'completion_rate' => $completionRate,
        ];
    }

    /**
     * Handle notifications for task updates
     *
     * @param Task $task The updated task
     * @param string $oldStatus Previous status
     * @param int|null $oldAssigneeId Previous assignee ID
     * @param array $data Update data
     * @return void
     */
    private function handleTaskUpdateNotifications(Task $task, string $oldStatus, ?int $oldAssigneeId, array $data): void
    {
        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            $this->notificationService->sendTaskStatusChangeNotification($task, $oldStatus, $data['status']);
        }

        if (isset($data['assigned_to']) && $data['assigned_to'] !== $oldAssigneeId && $data['assigned_to'] !== auth()->id()) {
            $assignee = User::find($data['assigned_to']);
            if ($assignee) {
                $this->notificationService->sendTaskAssignmentNotification($task, $assignee);
            }
        }
    }

    /**
     * Apply filters to the task query
     *
     * @param Builder $query The query builder
     * @param array $filters Array of filters to apply
     * @return void
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['team_id'])) {
            $query->where('team_id', $filters['team_id']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
    }
}
