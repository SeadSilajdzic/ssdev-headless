<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Features\Roles\RoleRepository;

/**
 * What the team page needs for team roles; empty without the roles feature.
 */
final class TeamRoleProps
{
    public function __construct(
        private FeatureRegistry $features,
        private RoleRepository $roles,
        private TeamRoleService $teamRoles,
    ) {}

    /**
     * @param  Collection<int, User>  $members
     * @return array{roles: list<array{id: int, name: string}>, memberRoles: array<int|string, list<int>>, memberRolesUrls: array<int|string, string>}
     */
    public function for(Team $team, Collection $members, bool $isOwner): array
    {
        if (! $this->features->isEnabled(Feature::Roles->value)) {
            return ['roles' => [], 'memberRoles' => [], 'memberRolesUrls' => []];
        }

        return [
            'roles' => $this->roles->assignableInTeams()->map(fn (Role $role): array => ['id' => (int) $role->getKey(), 'name' => $role->name])->values()->all(),
            'memberRoles' => $this->teamRoles->roleIdsOf($team, $members),
            'memberRolesUrls' => $isOwner ? $members->mapWithKeys(fn (User $member) => [$member->getKey() => route('headless.teams.members.roles.update', [$team->getKey(), $member->getKey()])])->all() : [],
        ];
    }
}
