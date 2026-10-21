<?php

namespace Ssdev\Headless\Features\Users\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Ssdev\Headless\Features\Users\UserStatus;

final class FilterUsersByStatus implements UserCriteria
{
    public function __construct(private UserStatus $status) {}

    public function apply(Builder $query): Builder
    {
        return match ($this->status) {
            UserStatus::Blocked => $query->whereNotNull('blocked_at'),
            UserStatus::Invited => $query->whereNull('blocked_at')->whereNotNull('invited_at'),
            UserStatus::Active => $query->whereNull('blocked_at')->whereNull('invited_at'),
        };
    }
}
