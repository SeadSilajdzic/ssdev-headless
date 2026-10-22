<?php

namespace Ssdev\Headless\Features\Roles;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Features\Roles\Criteria\RoleCriteria;
use Ssdev\Headless\Features\Roles\Criteria\SortRoles;
use Ssdev\Headless\Permissions\GlobalAssignments;
use Ssdev\Headless\Permissions\PermissionRegistry;

final class RoleRepository
{
    public const MAX_PER_PAGE = 50;

    public const DEFAULT_PER_PAGE = 15;

    public const MEMBERS_PER_PAGE = 20;

    public const MEMBERS_PAGE = 'members_page';

    public function __construct(
        private RoleGuard $guard,
        private GlobalAssignments $assignments,
        private Repository $config,
        private PermissionRegistry $permissions,
    ) {}

    /**
     * @param  list<RoleCriteria>  $criteria
     * @return LengthAwarePaginator<int, Role>
     */
    public function paginate(array $criteria, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = $this->query()->withCount(['users' => fn (Builder $members) => $this->assignments->onTable($members, $this->config->get('permission.table_names.model_has_roles')), 'permissions']);

        $sorted = false;

        foreach ($criteria as $criterion) {
            $query = $criterion->apply($query);
            $sorted = $sorted || $criterion instanceof SortRoles;
        }

        if (! $sorted) {
            $query = (new SortRoles(null, null))->apply($query);
        }

        return $query->paginate(min(max($perPage, 1), self::MAX_PER_PAGE));
    }

    /**
     * @return LengthAwarePaginator<int, Model&object{pivot: Pivot}>
     */
    public function members(Role $role, string $pageName = 'page', int $perPage = self::MEMBERS_PER_PAGE): LengthAwarePaginator
    {
        return $this->guard->members($role)->orderBy('name')->orderBy('id')->paginate(min(max($perPage, 1), self::MAX_PER_PAGE), pageName: $pageName);
    }

    /**
     * @return Collection<int, Role>
     */
    public function all(): Collection
    {
        return $this->query()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Role>
     */
    public function assignedTo(User $user): Collection
    {
        return $this->query()->whereHas('users', fn (Builder $members) => $this->assignments->onTable($members->whereKey($user->getKey()), $this->config->get('permission.table_names.model_has_roles')))->orderBy('name')->get();
    }

    /**
     * Roles a team owner may give inside a team: none of their permissions is global, and they are not tied to
     * a team, which Spatie's Role::create does and which keeps a role from counting in any other team.
     *
     * @return Collection<int, Role>
     */
    public function assignableInTeams(): Collection
    {
        $roles = $this->query();

        return $roles
            ->whereNull($roles->qualifyColumn((string) $this->config->get('permission.column_names.team_foreign_key')))
            ->whereDoesntHave('permissions', fn (Builder $permissions) => $permissions->whereIn('name', $this->permissions->globalNames()))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Role>
     */
    public function assignedInTeam(User $user, int|string $teamId): Collection
    {
        $table = $this->config->get('permission.table_names.model_has_roles');
        $column = $this->config->get('permission.column_names.team_foreign_key');

        return $this->query()
            ->whereHas('users', fn (Builder $members) => $members->whereKey($user->getKey())->where($table.'.'.$column, $teamId))
            ->orderBy('name')
            ->get();
    }

    public function findByName(string $name): ?Role
    {
        return $this->query()->where('name', $name)->first();
    }

    public function findOrFail(int|string $id): Role
    {
        return $this->query()->with('permissions')->withCount(['users' => fn (Builder $members) => $this->assignments->onTable($members, $this->config->get('permission.table_names.model_has_roles')), 'permissions'])->findOrFail($id);
    }

    /**
     * @return Builder<Role>
     */
    private function query(): Builder
    {
        return $this->guard->roles();
    }
}
