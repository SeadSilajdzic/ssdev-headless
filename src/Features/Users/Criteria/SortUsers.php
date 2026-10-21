<?php

namespace Ssdev\Headless\Features\Users\Criteria;

use Illuminate\Database\Eloquent\Builder;

final class SortUsers implements UserCriteria
{
    public const COLUMNS = ['name', 'email', 'created_at'];

    public const DIRECTIONS = ['asc', 'desc'];

    private const DEFAULT_COLUMN = 'created_at';

    private const DEFAULT_DIRECTION = 'desc';

    public function __construct(
        private ?string $column,
        private ?string $direction,
    ) {}

    public function apply(Builder $query): Builder
    {
        $column = in_array($this->column, self::COLUMNS, true) ? $this->column : self::DEFAULT_COLUMN;
        $direction = in_array($this->direction, self::DIRECTIONS, true) ? $this->direction : self::DEFAULT_DIRECTION;

        return $query->orderBy($column, $direction)->orderByDesc('id');
    }
}
