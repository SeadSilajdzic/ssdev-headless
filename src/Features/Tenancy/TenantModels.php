<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Keeps a model per tenant: reads see only the current tenant, and new rows always get it, whatever the input said.
 */
final class TenantModels
{
    /**
     * @param  class-string<Model>  $model
     */
    public static function scope(string $model): void
    {
        $model::addGlobalScope(new TenantScope);

        $model::creating(fn (Model $row) => $row->setAttribute(TenantContext::COLUMN, TenantContext::currentKey()));

        $model::updating(function (Model $row): void {
            if ($row->isDirty(TenantContext::COLUMN)) {
                throw new LogicException('A row cannot move to another tenant.');
            }
        });
    }
}
