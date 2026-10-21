<?php

namespace Ssdev\Headless\Features\Roles\Criteria;

use Illuminate\Database\Eloquent\Builder;

final class SearchRolesByName implements RoleCriteria
{
    public function __construct(private string $search) {}

    public function apply(Builder $query): Builder
    {
        // Escaped, so % and _ in a search are matched as text.
        return $query->where('name', 'like', '%'.addcslashes($this->search, '\\%_').'%');
    }
}
