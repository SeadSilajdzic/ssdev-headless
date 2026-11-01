<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Ssdev\Headless\Features\Tenancy\Criteria\SortTenants;
use Ssdev\Headless\Features\Tenancy\Criteria\TenantCriteria;

final class TenantRepository
{
    public const MAX_PER_PAGE = 50;

    public const DEFAULT_PER_PAGE = 15;

    /**
     * @param  list<TenantCriteria>  $criteria
     * @return LengthAwarePaginator<int, Tenant>
     */
    public function paginate(array $criteria, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = Tenant::query()->with('owner');
        $sorted = false;

        foreach ($criteria as $criterion) {
            $query = $criterion->apply($query);
            $sorted = $sorted || $criterion instanceof SortTenants;
        }

        if (! $sorted) {
            $query = (new SortTenants(null, null))->apply($query);
        }

        return $query->paginate(min(max($perPage, 1), self::MAX_PER_PAGE));
    }

    public function findOrFail(int|string $id): Tenant
    {
        return Tenant::query()->findOrFail($id);
    }
}
