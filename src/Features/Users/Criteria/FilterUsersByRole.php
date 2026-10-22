<?php

namespace Ssdev\Headless\Features\Users\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Spatie\Permission\PermissionRegistrar;
use Ssdev\Headless\Permissions\GlobalAssignments;

final class FilterUsersByRole implements UserCriteria
{
    public function __construct(private int $roleId) {}

    public function apply(Builder $query): Builder
    {
        $model = $query->getModel();

        $table = config('permission.table_names.model_has_roles');

        return $query->whereIn($model->getQualifiedKeyName(), fn (QueryBuilder $members) => app(GlobalAssignments::class)->onTable($members
            ->select(config('permission.column_names.model_morph_key'))
            ->from($table)
            ->where(app(PermissionRegistrar::class)->pivotRole, $this->roleId)
            ->where(GlobalAssignments::MODEL_TYPE, $model->getMorphClass()), $table));
    }
}
