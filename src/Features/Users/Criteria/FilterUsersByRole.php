<?php

namespace Ssdev\Headless\Features\Users\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Spatie\Permission\PermissionRegistrar;

final class FilterUsersByRole implements UserCriteria
{
    public function __construct(private int $roleId) {}

    public function apply(Builder $query): Builder
    {
        $model = $query->getModel();

        return $query->whereIn($model->getQualifiedKeyName(), fn (QueryBuilder $members) => $members
            ->select(config('permission.column_names.model_morph_key'))
            ->from(config('permission.table_names.model_has_roles'))
            ->where(app(PermissionRegistrar::class)->pivotRole, $this->roleId)
            ->where('model_type', $model->getMorphClass()));
    }
}
