<?php

namespace Ssdev\Headless\Features\Roles;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Features\Roles\Criteria\RoleCriteria;
use Ssdev\Headless\Features\Roles\Criteria\SortRoles;

final class RoleRepository
{
    public const MAX_PER_PAGE = 50;

    public const DEFAULT_PER_PAGE = 15;

    public const MEMBERS_PER_PAGE = 20;

    public const MEMBERS_PAGE = 'members_page';

    public function __construct(private RoleGuard $guard) {}

    /**
     * @param  list<RoleCriteria>  $criteria
     * @return LengthAwarePaginator<int, Role>
     */
    public function paginate(array $criteria, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = $this->query()->withCount(['users', 'permissions']);

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
        return $role->users()->orderBy('name')->orderBy('id')->paginate(min(max($perPage, 1), self::MAX_PER_PAGE), pageName: $pageName);
    }

    /**
     * @return Collection<int, Role>
     */
    public function all(): Collection
    {
        return $this->query()->orderBy('name')->get();
    }

    public function findByName(string $name): ?Role
    {
        return $this->query()->where('name', $name)->first();
    }

    public function findOrFail(int|string $id): Role
    {
        return $this->query()->with('permissions')->withCount(['users', 'permissions'])->findOrFail($id);
    }

    /**
     * @return Builder<Role>
     */
    private function query(): Builder
    {
        return $this->guard->roles();
    }
}
