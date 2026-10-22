<?php

namespace Ssdev\Headless\Features\Tenancy\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Ssdev\Headless\Support\TextSearch;

final class SearchTenants implements TenantCriteria
{
    public function __construct(private string $search) {}

    public function apply(Builder $query): Builder
    {
        return TextSearch::apply($query, ['name', 'subdomain'], $this->search);
    }
}
