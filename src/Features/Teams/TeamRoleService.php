<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
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
                $this->grant($team, $member, $assignable[$roleId]);
            }

            foreach ($removed as $roleId) {
                Role::query()->findOrFail($roleId)->users()->wherePivot($column, $team->getKey())->detach($member->getKey());
            }
        });

        if ($added !== [] || $removed !== []) {
            $this->events->dispatch(new TeamMemberRolesUpdated($team, $member, $owner, $added, $removed));
        }
    }

    /**
     * For trusted callers, such as accepting an invitation that names a role.
     */
    public function grant(Team $team, User $member, Role $role): void
    {
        $column = (string) $this->config->get('permission.column_names.team_foreign_key');
        $members = $role->users()->wherePivot($column, $team->getKey());

        if ($members->whereKey($member->getKey())->exists()) {
            return;
        }

        try {
            // The savepoint keeps an outer PostgreSQL transaction usable when a parallel request gave the role first.
            $this->database->transaction(fn () => $role->users()->attach($member->getKey(), [$column => $team->getKey()]));
        } catch (UniqueConstraintViolationException) {
            // The member already has the role.
        }
    }
}
