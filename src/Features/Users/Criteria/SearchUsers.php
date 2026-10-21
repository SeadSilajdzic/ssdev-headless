<?php

namespace Ssdev\Headless\Features\Users\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Ssdev\Headless\Support\TextSearch;

final class SearchUsers implements UserCriteria
{
    public function __construct(private string $search) {}

    public function apply(Builder $query): Builder
    {
        return TextSearch::apply($query, ['name', 'email'], $this->search);
    }
}
