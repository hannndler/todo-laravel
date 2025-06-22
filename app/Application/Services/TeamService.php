<?php

namespace App\Application\Services;

use App\Domain\Models\Team;
use App\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class TeamService
{
    /**
     * Get paginated teams with permission-based access control
     *
     * @param User $user The authenticated user
     * @param array $filters Array of filters to apply
     * @return LengthAwarePaginator Paginated collection of teams
     */
    public function getTeams(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Team::with(['members', 'owner']);

        if (!$user->isAdmin()) {
            $query->whereHas('members', function (Builder $q) use ($user) {
                $q->where('user_id', $user->id);
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
     * Create a new team with business logic validation
     *
     * @param User $user The user creating the team
     * @param array $data Team data to create
     * @return Team The created team
     * @throws \Exception When user lacks permissions
     */
    public function createTeam(User $user, array $data): Team
    {
        if (!$user->hasPermission('teams.create')) {
            throw new \Exception('No tienes permisos para crear equipos');
        }

        $team = Team::create([
            ...$data,
            'owner_id' => $user->id,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $team->members()->attach($user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        if (isset($data['member_ids']) && is_array($data['member_ids'])) {
            $this->addMembersToTeam($team, $user, $data['member_ids']);
        }

        $team->load(['members', 'owner']);

        return $team;
    }

    /**
     * Update an existing team with permission validation
     *
     * @param Team $team The team to update
     * @param User $user The user updating the team
     * @param array $data Data to update
     * @return Team The updated team
     * @throws \Exception When user lacks permissions
     */
    public function updateTeam(Team $team, User $user, array $data): Team
    {
        if (!$team->canBeEditedBy($user)) {
            throw new \Exception('No puedes editar este equipo');
        }

        $team->update($data);
        $team->load(['members', 'owner']);

        return $team;
    }

    /**
     * Delete a team with permission validation and business rules
     *
     * @param Team $team The team to delete
     * @param User $user The user deleting the team
     * @return void
     * @throws \Exception When user lacks permissions or team has active tasks
     */
    public function deleteTeam(Team $team, User $user): void
    {
        if (!$team->canBeDeletedBy($user)) {
            throw new \Exception('No puedes eliminar este equipo');
        }

        if ($team->tasks()->where('status', '!=', 'completed')->exists()) {
            throw new \Exception('No se puede eliminar un equipo con tareas activas');
        }

        $team->members()->detach();
        $team->delete();
    }

    /**
     * Add members to a team with permission validation
     *
     * @param Team $team The team to add members to
     * @param User $user The user adding members
     * @param array $memberIds Array of user IDs to add
     * @return void
     * @throws \Exception When user lacks permissions or users don't exist
     */
    public function addMembersToTeam(Team $team, User $user, array $memberIds): void
    {
        if (!$team->canBeEditedBy($user)) {
            throw new \Exception('No puedes editar este equipo');
        }

        if (!$user->hasPermission('teams.manage_members')) {
            throw new \Exception('No tienes permisos para gestionar miembros');
        }

        $users = User::whereIn('id', $memberIds)->get();
        if ($users->count() !== count($memberIds)) {
            throw new \Exception('Algunos usuarios no existen');
        }

        foreach ($users as $member) {
            if (!$team->hasMember($member)) {
                $team->members()->attach($member->id, [
                    'role' => 'member',
                    'joined_at' => now(),
                ]);
            }
        }
    }

    /**
     * Remove members from a team with permission validation
     *
     * @param Team $team The team to remove members from
     * @param User $user The user removing members
     * @param array $memberIds Array of user IDs to remove
     * @return void
     * @throws \Exception When user lacks permissions or trying to remove owner
     */
    public function removeMembersFromTeam(Team $team, User $user, array $memberIds): void
    {
        if (!$team->canBeEditedBy($user)) {
            throw new \Exception('No puedes editar este equipo');
        }

        if (!$user->hasPermission('teams.manage_members')) {
            throw new \Exception('No tienes permisos para gestionar miembros');
        }

        $memberIds = array_filter($memberIds, function ($id) use ($team) {
            return $id !== $team->owner_id;
        });

        if (empty($memberIds)) {
            throw new \Exception('No se puede remover al dueño del equipo');
        }

        $team->members()->detach($memberIds);
    }

    /**
     * Change a team member's role
     *
     * @param Team $team The team
     * @param User $user The user changing the role
     * @param int $memberId The member's user ID
     * @param string $role The new role
     * @return void
     * @throws \Exception When user lacks permissions or member doesn't exist
     */
    public function changeMemberRole(Team $team, User $user, int $memberId, string $role): void
    {
        if (!$team->canBeEditedBy($user)) {
            throw new \Exception('No puedes editar este equipo');
        }

        if (!$user->hasPermission('teams.manage_members')) {
            throw new \Exception('No tienes permisos para gestionar miembros');
        }

        if (!$team->hasMember($memberId)) {
            throw new \Exception('El usuario no es miembro de este equipo');
        }

        if ($memberId === $team->owner_id) {
            throw new \Exception('No se puede cambiar el rol del dueño del equipo');
        }

        $team->members()->updateExistingPivot($memberId, ['role' => $role]);
    }

    /**
     * Transfer team ownership to another member
     *
     * @param Team $team The team
     * @param User $user The user transferring ownership
     * @param int $newOwnerId The new owner's user ID
     * @return void
     * @throws \Exception When user lacks permissions or new owner is not a member
     */
    public function transferOwnership(Team $team, User $user, int $newOwnerId): void
    {
        if (!$team->canBeEditedBy($user)) {
            throw new \Exception('No puedes editar este equipo');
        }

        if (!$user->hasPermission('teams.transfer_ownership')) {
            throw new \Exception('No tienes permisos para transferir propiedad');
        }

        if (!$team->hasMember($newOwnerId)) {
            throw new \Exception('El nuevo dueño debe ser miembro del equipo');
        }

        $team->update(['owner_id' => $newOwnerId]);

        $team->members()->updateExistingPivot($user->id, ['role' => 'member']);
        $team->members()->updateExistingPivot($newOwnerId, ['role' => 'owner']);
    }

    /**
     * Get team statistics for dashboard
     *
     * @param User $user The user to get statistics for
     * @return array Array of team statistics
     */
    public function getTeamStats(User $user): array
    {
        $query = Team::query();

        if (!$user->isAdmin()) {
            $query->whereHas('members', function (Builder $q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return [
            'total' => $query->count(),
            'active' => (clone $query)->where('is_active', true)->count(),
            'inactive' => (clone $query)->where('is_active', false)->count(),
        ];
    }

    /**
     * Apply filters to the team query
     *
     * @param Builder $query The query builder
     * @param array $filters Array of filters to apply
     * @return void
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
    }
}
