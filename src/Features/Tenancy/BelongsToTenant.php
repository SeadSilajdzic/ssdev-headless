<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * For models kept per tenant: reads see only the current tenant, and new rows always get it, whatever the input said.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(fn (Model $model) => $model->setAttribute(TenantContext::COLUMN, TenantContext::currentKey()));

        static::updating(function (Model $model): void {
            if ($model->isDirty(TenantContext::COLUMN)) {
                throw new LogicException('A row cannot move to another tenant.');
            }
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, TenantContext::COLUMN);
    }
}
