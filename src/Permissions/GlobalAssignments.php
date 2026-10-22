<?php

namespace Ssdev\Headless\Permissions;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * With Spatie's teams mode every role and permission assignment belongs to a team. Assignments outside any team
 * use team 0; the roles page and user management only ever read and write those.
 */
final class GlobalAssignments
{
    public const TEAM = 0;

    // Spatie's column for the assigned model's class; unlike the other column names it is not configurable.
    public const MODEL_TYPE = 'model_type';

    public function __construct(private Repository $config) {}

    /**
     * Pivot values for a new global assignment.
     *
     * @return array<string, int>
     */
    public function pivot(): array
    {
        return $this->teamsEnabled() ? [$this->column() => self::TEAM] : [];
    }

    /**
     * @template TRelation of BelongsToMany
     *
     * @param  TRelation  $relation
     * @return TRelation
     */
    public function onRelation(BelongsToMany $relation): BelongsToMany
    {
        return $this->teamsEnabled() ? $relation->wherePivot($this->column(), self::TEAM) : $relation;
    }

    /**
     * @template TQuery of Builder|QueryBuilder
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    public function onTable(Builder|QueryBuilder $query, string $table): Builder|QueryBuilder
    {
        return $this->teamsEnabled() ? $query->where($table.'.'.$this->column(), self::TEAM) : $query;
    }

    private function teamsEnabled(): bool
    {
        return (bool) $this->config->get('permission.teams');
    }

    private function column(): string
    {
        return (string) $this->config->get('permission.column_names.team_foreign_key', 'team_id');
    }
}
