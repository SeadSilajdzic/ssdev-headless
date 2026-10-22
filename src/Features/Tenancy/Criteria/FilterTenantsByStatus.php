<?php

namespace Ssdev\Headless\Features\Tenancy\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Ssdev\Headless\Features\Tenancy\TenantStatus;

final class FilterTenantsByStatus implements TenantCriteria
{
    public function __construct(private TenantStatus $status) {}

    public function apply(Builder $query): Builder
    {
        return match ($this->status) {
            TenantStatus::Suspended => $query->whereNotNull('suspended_at'),
            TenantStatus::Active => $query->whereNull('suspended_at'),
        };
    }
}
