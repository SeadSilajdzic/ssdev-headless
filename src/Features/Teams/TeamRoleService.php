<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Features\Roles\RoleRepository;
use Ssdev\Headless\Features\Teams\Events\TeamMemberRolesUpdated;

/**
 * The owner gives members roles inside the team. A role with a global permission is never one of them,
 * so owning a team never leads to managing users or roles.
 */
final class TeamRoleService
{
    public function __construct(
        private RoleRepository $roles,
        private TeamRepository $teams,
        private ConnectionInterface $database,
        private Dispatcher $events,
        private Repository $config,
    ) {}

    /**
     * @return Collection<int, Role>
     */
    public function rolesOf(Team $team, User $member): Collection
    {
        return $this->roles->assignedInTeam($member, $team->getKey());
    }

    /**
     * @param  list<int>  $roleIds
     */
    public function sync(User $owner, Team $team, User $member, array $roleIds): void
    {
        if (! $team->isOwnedBy($owner)) {
            throw new AuthorizationException;
        }

        if (! $this->teams->isMember($team, $member)) {
            throw ValidationException::withMessages(['team' => __('headless::teams.errors.not_a_member')]);
        }

        $assignable = $this->roles->assignableInTeams()->keyBy(fn (Role $role): int|string => $role->getKey());

        if (array_diff($roleIds, $assignable->keys()->all()) !== []) {
            throw ValidationException::withMessages(['roles' => __('headless::teams.errors.role_not_assignable')]);
        }

        $current = $this->rolesOf($team, $member)->map(fn (Role $role): int => (int) $role->getKey())->all();
        $added = array_values(array_diff($roleIds, $current));
        $removed = array_values(array_diff($current, $roleIds));
        $column = (string) $this->config->get('permission.column_names.team_foreign_key');

        $this->database->transaction(function () use ($assignable, $member, $team, $added, $removed, $column): void {
            foreach ($added as $roleId) {
                $assignable[$roleId]->users()->attach($member->getKey(), [$column => $team->getKey()]);
            }

            foreach ($removed as $roleId) {
                Role::query()->findOrFail($roleId)->users()->wherePivot($column, $team->getKey())->detach($member->getKey());
            }
        });

        if ($added !== [] || $removed !== []) {
            $this->events->dispatch(new TeamMemberRolesUpdated($team, $member, $owner, $added, $removed));
        }
    }
}
