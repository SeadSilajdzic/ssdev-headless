<?php

namespace Ssdev\Headless\Features\Users;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Auth\User;
use Spatie\Permission\PermissionRegistrar;
use Ssdev\Headless\Features\Users\Criteria\SortUsers;
use Ssdev\Headless\Features\Users\Criteria\UserCriteria;

final class ManagedUserRepository
{
    public const MAX_PER_PAGE = 50;

    public const DEFAULT_PER_PAGE = 15;

    public function __construct(
        private Repository $config,
        private PermissionRegistrar $registrar,
    ) {}

    /**
     * @param  list<UserCriteria>  $criteria
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(array $criteria, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = $this->query();
        $sorted = false;

        foreach ($criteria as $criterion) {
            $query = $criterion->apply($query);
            $sorted = $sorted || $criterion instanceof SortUsers;
        }

        if (! $sorted) {
            $query = (new SortUsers(null, null))->apply($query);
        }

        return $query->paginate(min(max($perPage, 1), self::MAX_PER_PAGE));
    }

    public function findOrFail(int|string $id): User
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * Keys of users who are not blocked and hold the permission, directly or through a role. Call inside a transaction:
     * the rows stay locked until it ends.
     *
     * @return list<int|string>
     */
    public function lockActiveHoldersOf(string $permission): array
    {
        $query = $this->query();
        $model = $query->getModel();
        $tables = $this->config->get('permission.table_names');
        $morphKey = $this->config->get('permission.column_names.model_morph_key');
        $permissionIds = fn (QueryBuilder $ids) => $ids->select('id')->from($tables['permissions'])->where('name', $permission);

        return $query
            ->whereNull('blocked_at')
            ->where(fn (Builder $holders) => $holders
                ->whereIn($model->getQualifiedKeyName(), fn (QueryBuilder $direct) => $direct
                    ->select($morphKey)
                    ->from($tables['model_has_permissions'])
                    ->whereIn($this->registrar->pivotPermission, $permissionIds)
                    ->where('model_type', $model->getMorphClass()))
                ->orWhereIn($model->getQualifiedKeyName(), fn (QueryBuilder $viaRoles) => $viaRoles
                    ->select($tables['model_has_roles'].'.'.$morphKey)
                    ->from($tables['model_has_roles'])
                    ->join($tables['role_has_permissions'], $tables['role_has_permissions'].'.'.$this->registrar->pivotRole, '=', $tables['model_has_roles'].'.'.$this->registrar->pivotRole)
                    ->whereIn($tables['role_has_permissions'].'.'.$this->registrar->pivotPermission, $permissionIds)
                    ->where($tables['model_has_roles'].'.model_type', $model->getMorphClass())))
            ->lockForUpdate()
            ->pluck($model->getQualifiedKeyName())
            ->all();
    }

    /**
     * @return Builder<User>
     */
    private function query(): Builder
    {
        /** @var class-string<User> $model */
        $model = $this->config->get('auth.providers.users.model');

        return $model::query();
    }
}
