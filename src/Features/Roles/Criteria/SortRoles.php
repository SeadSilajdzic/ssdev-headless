<?php

namespace Ssdev\Headless\Features\Roles\Criteria;

use Illuminate\Database\Eloquent\Builder;

final class SortRoles implements RoleCriteria
{
    public const COLUMNS = ['name', 'users_count', 'created_at'];

    public const DIRECTIONS = ['asc', 'desc'];

    private const DEFAULT_COLUMN = 'name';

    private const DEFAULT_DIRECTION = 'asc';

    public function __construct(
        private ?string $column,
        private ?string $direction,
    ) {}

    public function apply(Builder $query): Builder
    {
        $column = in_array($this->column, self::COLUMNS, true) ? $this->column : self::DEFAULT_COLUMN;
        $direction = in_array($this->direction, self::DIRECTIONS, true) ? $this->direction : self::DEFAULT_DIRECTION;

        return $query->orderBy($column, $direction)->orderBy('id');
    }
}
