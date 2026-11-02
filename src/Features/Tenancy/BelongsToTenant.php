<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * For models kept per tenant: reads see only the current tenant, and new rows always get it, whatever the input said.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        TenantModels::scope(static::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, TenantContext::COLUMN);
    }
}
