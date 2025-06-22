<?php

namespace App\Application\Services;

use App\Domain\Models\Task;
use App\Domain\Models\Team;
use App\Domain\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send notification when a task is assigned to a user
     *
     * @param Task $task The task that was assigned
     * @param User $assignee The user who was assigned the task
     * @return void
     */
    public function sendTaskAssignmentNotification(Task $task, User $assignee): void
    {
        try {
            // Aquí iría la lógica para enviar notificaciones
            // Por ejemplo: email, push notification, in-app notification, etc.

            Log::info('Task assignment notification sent', [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'assignee_id' => $assignee->id,
                'assignee_email' => $assignee->email,
            ]);

            // Ejemplo de lógica de notificación:
            // - Enviar email al asignado
            // - Crear notificación en la base de datos
            // - Enviar push notification si está configurado
            // - Enviar notificación por Slack/Discord si está integrado

        } catch (\Exception $e) {
            Log::error('Failed to send task assignment notification', [
                'task_id' => $task->id,
                'assignee_id' => $assignee->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notifications when task status changes
     *
     * @param Task $task The task that changed status
     * @param string $oldStatus Previous status of the task
     * @param string $newStatus New status of the task
     * @return void
     */
    public function sendTaskStatusChangeNotification(Task $task, string $oldStatus, string $newStatus): void
    {
        try {
            $notifyUsers = collect();

            // Notificar al creador
            if ($task->creator && $task->creator->id !== auth()->id()) {
                $notifyUsers->push($task->creator);
            }

            // Notificar al asignado
            if ($task->assignee && $task->assignee->id !== auth()->id()) {
                $notifyUsers->push($task->assignee);
            }

            // Notificar a miembros del equipo
            if ($task->team) {
                $teamMembers = $task->team->members()
                    ->where('user_id', '!=', auth()->id())
                    ->get();
                $notifyUsers = $notifyUsers->merge($teamMembers);
            }

            // Enviar notificaciones
            foreach ($notifyUsers->unique('id') as $user) {
                $this->sendStatusChangeNotification($task, $user, $oldStatus, $newStatus);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send task status change notification', [
                'task_id' => $task->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notifications for all overdue tasks
     *
     * @return void
     */
    public function sendOverdueTaskNotifications(): void
    {
        try {
            $overdueTasks = Task::where('due_date', '<', now())
                ->where('status', '!=', 'completed')
                ->where('status', '!=', 'cancelled')
                ->with(['assignee', 'creator', 'team'])
                ->get();

            foreach ($overdueTasks as $task) {
                $this->sendOverdueTaskNotification($task);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send overdue task notifications', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification when a user is invited to a team
     *
     * @param Team $team The team the user was invited to
     * @param User $invitedUser The user who was invited
     * @param User $invitedBy The user who sent the invitation
     * @return void
     */
    public function sendTeamInvitationNotification(Team $team, User $invitedUser, User $invitedBy): void
    {
        try {
            Log::info('Team invitation notification sent', [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'invited_user_id' => $invitedUser->id,
                'invited_by_id' => $invitedBy->id,
            ]);

            // Aquí iría la lógica para enviar la invitación
            // - Email con link de aceptación
            // - Notificación in-app
            // - SMS si está configurado

        } catch (\Exception $e) {
            Log::error('Failed to send team invitation notification', [
                'team_id' => $team->id,
                'invited_user_id' => $invitedUser->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send daily summary of tasks to a user
     *
     * @param User $user The user to send the summary to
     * @return void
     */
    public function sendDailyTaskSummary(User $user): void
    {
        try {
            $today = now()->toDateString();

            // Obtener tareas del usuario para hoy
            $tasks = Task::where(function ($query) use ($user) {
                $query->where('created_by', $user->id)
                      ->orWhere('assigned_to', $user->id);
            })
            ->whereDate('due_date', $today)
            ->with(['category', 'team'])
            ->get();

            $summary = [
                'total_tasks' => $tasks->count(),
                'completed_tasks' => $tasks->where('status', 'completed')->count(),
                'pending_tasks' => $tasks->where('status', 'pending')->count(),
                'overdue_tasks' => $tasks->where('due_date', '<', now())->count(),
                'tasks' => $tasks->take(5)->toArray(), // Solo las primeras 5
            ];

            Log::info('Daily task summary sent', [
                'user_id' => $user->id,
                'summary' => $summary,
            ]);

            // Aquí iría la lógica para enviar el resumen
            // - Email diario
            // - Notificación push
            // - Dashboard widget

        } catch (\Exception $e) {
            Log::error('Failed to send daily task summary', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send weekly report to all team members
     *
     * @param Team $team The team to send the report to
     * @return void
     */
    public function sendWeeklyTeamReport(Team $team): void
    {
        try {
            $weekStart = now()->startOfWeek();
            $weekEnd = now()->endOfWeek();

            // Obtener estadísticas del equipo
            $stats = [
                'total_tasks' => $team->tasks()->whereBetween('created_at', [$weekStart, $weekEnd])->count(),
                'completed_tasks' => $team->tasks()->whereBetween('completed_at', [$weekStart, $weekEnd])->count(),
                'overdue_tasks' => $team->tasks()->where('due_date', '<', now())->where('status', '!=', 'completed')->count(),
                'team_members' => $team->members()->count(),
                'most_active_member' => $this->getMostActiveMember($team, $weekStart, $weekEnd),
            ];

            Log::info('Weekly team report sent', [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'stats' => $stats,
            ]);

            // Enviar reporte a todos los miembros del equipo
            foreach ($team->members as $member) {
                $this->sendTeamReportToMember($team, $member, $stats);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send weekly team report', [
                'team_id' => $team->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the most active team member based on tasks created
     *
     * @param Team $team The team to analyze
     * @param \Carbon\Carbon $weekStart Start of the week
     * @param \Carbon\Carbon $weekEnd End of the week
     * @return array|null Member data or null if no members
     */
    private function getMostActiveMember(Team $team, $weekStart, $weekEnd): ?array
    {
        $member = $team->members()
            ->withCount(['createdTasks' => function ($query) use ($weekStart, $weekEnd) {
                $query->whereBetween('created_at', [$weekStart, $weekEnd]);
            }])
            ->orderBy('created_tasks_count', 'desc')
            ->first();

        if (!$member) {
            return null;
        }

        return [
            'id' => $member->id,
            'name' => $member->name,
            'tasks_created' => $member->created_tasks_count,
        ];
    }

    /**
     * Send status change notification to a specific user
     *
     * @param Task $task The task that changed
     * @param User $user The user to notify
     * @param string $oldStatus Previous status
     * @param string $newStatus New status
     * @return void
     */
    private function sendStatusChangeNotification(Task $task, User $user, string $oldStatus, string $newStatus): void
    {
        Log::info('Status change notification sent', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        // Aquí iría la lógica específica para cada usuario
    }

    /**
     * Send overdue task notification
     *
     * @param Task $task The overdue task
     * @return void
     */
    private function sendOverdueTaskNotification(Task $task): void
    {
        Log::info('Overdue task notification sent', [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'due_date' => $task->due_date,
        ]);

        // Aquí iría la lógica para notificar tareas vencidas
    }

    /**
     * Send team report to a specific member
     *
     * @param Team $team The team
     * @param User $member The team member
     * @param array $stats Team statistics
     * @return void
     */
    private function sendTeamReportToMember(Team $team, User $member, array $stats): void
    {
        Log::info('Team report sent to member', [
            'team_id' => $team->id,
            'member_id' => $member->id,
            'stats' => $stats,
        ]);

        // Aquí iría la lógica para enviar el reporte al miembro específico
    }
}
