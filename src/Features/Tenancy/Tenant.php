<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Carbon;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

/**
 * @property int $id
 * @property string $name
 * @property string $subdomain
 * @property int|null $owner_id
 * @property Carbon|null $suspended_at
 * @property Carbon|null $created_at
 * @property-read User|null $owner
 */
final class Tenant extends SpatieTenant
{
    protected $table = 'tenants';

    protected $casts = ['suspended_at' => 'datetime'];

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * Read past the tenant scope, since the panel lists owners from the central level.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        /** @var class-string<User> $model */
        $model = config('auth.providers.users.model');

        return $this->belongsTo($model, 'owner_id')->withoutGlobalScope(TenantScope::class);
    }

    public function execute(callable $callable): mixed
    {
        return parent::execute(function (self $tenant) use ($callable): mixed {
            $result = $callable($tenant);

            // A pending dispatch is sent when it is destroyed, so it must not outlive this tenant: fn () => Job::dispatch().
            return $result instanceof PendingDispatch ? null : $result;
        });
    }
}
