<?php

namespace Ssdev\Headless\Features\Users\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;

interface UserCriteria
{
    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function apply(Builder $query): Builder;
}
