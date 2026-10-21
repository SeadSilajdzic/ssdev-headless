<?php

namespace Ssdev\Headless\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A case-insensitive "contains" that behaves the same on SQLite, MySQL and PostgreSQL.
 */
final class TextSearch
{
    // Backslash is no escape character for LIKE on SQLite and needs doubling in MySQL strings; "!" is plain everywhere.
    private const ESCAPE = '!';

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $columns
     * @return Builder<TModel>
     */
    public static function apply(Builder $query, array $columns, string $search): Builder
    {
        $escaped = str_replace([self::ESCAPE, '%', '_'], [self::ESCAPE.self::ESCAPE, self::ESCAPE.'%', self::ESCAPE.'_'], mb_strtolower($search));
        $grammar = $query->getQuery()->getGrammar();

        return $query->where(function (Builder $query) use ($columns, $escaped, $grammar) {
            foreach ($columns as $column) {
                $query->orWhereRaw('lower('.$grammar->wrap($query->qualifyColumn($column)).") like ? escape '".self::ESCAPE."'", ['%'.$escaped.'%']);
            }
        });
    }
}
