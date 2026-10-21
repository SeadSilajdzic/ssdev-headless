<?php

namespace Ssdev\Headless\Features\Roles\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Ssdev\Headless\Support\TextSearch;

final class SearchRolesByName implements RoleCriteria
{
    public function __construct(private string $search) {}

    public function apply(Builder $query): Builder
    {
        return TextSearch::apply($query, ['name'], $this->search);
    }
}
