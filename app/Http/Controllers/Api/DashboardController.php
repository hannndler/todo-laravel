<?php

namespace App\Http\Controllers\Api;

use App\Application\Services\TaskService;
use App\Domain\Enums\TaskStatus;
use App\Domain\Models\Category;
use App\Domain\Models\Task;
use App\Domain\Models\Team;
use App\Domain\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        private TaskService $taskService
    ) {}

    /**
     * Get dashboard data
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('dashboard.view')) {
            return response()->json(['message' => 'No tienes permisos para ver el dashboard'], 403);
        }

        // Obtener estadísticas de tareas usando el service
        $taskStats = $this->taskService->getTaskStats($user);

        // Obtener datos adicionales
        $recentTasks = $this->getRecentTasks($user);
        $upcomingDeadlines = $this->getUpcomingDeadlines($user);
        $teamStats = $this->getTeamStats($user);

        return response()->json([
            'data' => [
                'task_stats' => $taskStats,
                'recent_tasks' => $recentTasks,
                'upcoming_deadlines' => $upcomingDeadlines,
                'team_stats' => $teamStats,
            ]
        ]);
    }

    /**
     * Get recent tasks
     */
    private function getRecentTasks(User $user): array
    {
        $query = \App\Domain\Models\Task::with(['creator', 'assignee', 'category'])
            ->orderBy('created_at', 'desc')
            ->limit(5);

        if (!$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('assigned_to', $user->id);
            });
        }

        return $query->get()->toArray();
    }

    /**
     * Get upcoming deadlines
     */
    private function getUpcomingDeadlines(User $user): array
    {
        $query = \App\Domain\Models\Task::with(['creator', 'assignee', 'category'])
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(7))
            ->where('status', '!=', \App\Domain\Enums\TaskStatus::COMPLETED)
            ->orderBy('due_date', 'asc')
            ->limit(10);

        if (!$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('assigned_to', $user->id);
            });
        }

        return $query->get()->toArray();
    }

    /**
     * Get team statistics
     */
    private function getTeamStats(User $user): array
    {
        if ($user->isAdmin()) {
            $teams = Team::withCount('members')->get();
        } else {
            $teams = $user->teams()->withCount('members')->get();
        }

        return [
            'total_teams' => $teams->count(),
            'teams' => $teams->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'member_count' => $team->members_count,
                ];
            }),
        ];
    }

    /**
     * Get tasks summary for charts.
     */
    public function tasksSummary(): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('dashboard.read')) {
            return response()->json(['message' => 'No tienes permisos para ver el dashboard'], 403);
        }

        $taskQuery = Task::query();

        if (!$user->isAdmin()) {
            $taskQuery->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('assigned_to', $user->id);
            });
        }

        // Tareas por estado
        $tasksByStatus = (clone $taskQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // Tareas por prioridad
        $tasksByPriority = (clone $taskQuery)
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get();

        // Tareas por mes (últimos 6 meses)
        $tasksByMonth = (clone $taskQuery)
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        return response()->json([
            'data' => [
                'by_status' => $tasksByStatus,
                'by_priority' => $tasksByPriority,
                'by_month' => $tasksByMonth,
            ]
        ]);
    }

    /**
     * Get team performance data.
     */
    public function teamPerformance(): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasPermission('dashboard.read')) {
            return response()->json(['message' => 'No tienes permisos para ver el dashboard'], 403);
        }

        if (!$user->isAdmin() && !$user->hasRole('manager')) {
            return response()->json(['message' => 'No tienes permisos para ver estadísticas de equipos'], 403);
        }

        $teamQuery = Team::query();

        if (!$user->isAdmin()) {
            $teamQuery->whereHas('members', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $teams = $teamQuery->with(['tasks', 'members'])->get();

        $teamPerformance = $teams->map(function ($team) {
            $totalTasks = $team->tasks->count();
            $completedTasks = $team->tasks->where('status', TaskStatus::COMPLETED)->count();
            $overdueTasks = $team->tasks->where('due_date', '<', now())
                ->where('status', '!=', TaskStatus::COMPLETED)
                ->count();

            return [
                'id' => $team->id,
                'name' => $team->name,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'overdue_tasks' => $overdueTasks,
                'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
                'member_count' => $team->members->count(),
            ];
        });

        return response()->json([
            'data' => $teamPerformance
        ]);
    }
}
