<?php

namespace Ssdev\Headless\Features\Tenancy\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Ssdev\Headless\Features\Tenancy\Tenant;

interface TenantCriteria
{
    /**
     * @param  Builder<Tenant>  $query
     * @return Builder<Tenant>
     */
    public function apply(Builder $query): Builder;
}
