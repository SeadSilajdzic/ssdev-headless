<?php

namespace Ssdev\Headless\Features\Users\Criteria;

use Illuminate\Database\Eloquent\Builder;

final class FilterUsersByVerifiedEmail implements UserCriteria
{
    public function __construct(private bool $verified) {}

    public function apply(Builder $query): Builder
    {
        return $this->verified ? $query->whereNotNull('email_verified_at') : $query->whereNull('email_verified_at');
    }
}
