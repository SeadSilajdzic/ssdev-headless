<?php

namespace Ssdev\Headless\Features\Tenancy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Features\Tenancy\TenantHosts;
use Ssdev\Headless\Features\Tenancy\TenantStatus;

/**
 * @property Tenant $resource
 */
final class TenantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $owner = $this->resource->owner;

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'subdomain' => $this->resource->subdomain,
            'url' => app(TenantHosts::class)->rootUrlFor($this->resource),
            'status' => TenantStatus::of($this->resource)->value,
            'suspended_at' => $this->resource->suspended_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'owner' => $owner === null ? null : [
                'email' => $owner->getAttribute('email'),
                // The owner confirms the address by accepting the invitation.
                'accepted' => $owner->getAttribute('email_verified_at') !== null,
            ],
        ];
    }
}
