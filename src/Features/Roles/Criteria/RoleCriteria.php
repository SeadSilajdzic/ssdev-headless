<?php

namespace Ssdev\Headless\Features\Roles\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

interface RoleCriteria
{
    /**
     * @param  Builder<Role>  $query
     * @return Builder<Role>
     */
    public function apply(Builder $query): Builder;
}
