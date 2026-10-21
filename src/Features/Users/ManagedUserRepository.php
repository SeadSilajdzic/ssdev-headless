<?php

namespace Ssdev\Headless\Features\Users;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Ssdev\Headless\Features\Users\Criteria\SortUsers;
use Ssdev\Headless\Features\Users\Criteria\UserCriteria;

final class ManagedUserRepository
{
    public const MAX_PER_PAGE = 50;

    public const DEFAULT_PER_PAGE = 15;

    public function __construct(private Repository $config) {}

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
     * @return Builder<User>
     */
    private function query(): Builder
    {
        /** @var class-string<User> $model */
        $model = $this->config->get('auth.providers.users.model');

        return $model::query();
    }
}
