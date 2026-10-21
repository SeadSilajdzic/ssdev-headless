<?php

namespace Ssdev\Headless\Features\Roles;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Ssdev\Headless\Features\Roles\Criteria\RoleCriteria;
use Ssdev\Headless\Features\Roles\Criteria\SortRoles;

final class RoleRepository
{
    public const MAX_PER_PAGE = 50;

    public const DEFAULT_PER_PAGE = 15;

    public function __construct(
        private PermissionRegistrar $registrar,
        private Repository $config,
    ) {}

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

    public function findOrFail(int|string $id): Role
    {
        return $this->query()->with('permissions')->withCount(['users', 'permissions'])->findOrFail($id);
    }

    /**
     * @return Builder<Role>
     */
    private function query(): Builder
    {
        /** @var class-string<Role> $roleClass */
        $roleClass = $this->registrar->getRoleClass();

        return $roleClass::query()->where('guard_name', $this->config->get('auth.defaults.guard'));
    }
}
