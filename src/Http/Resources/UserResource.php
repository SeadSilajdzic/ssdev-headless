<?php

namespace Ssdev\Headless\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ssdev\Headless\Account\AvatarService;
use Ssdev\Headless\Permissions\PermissionRegistry;

final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->getAttribute('name'),
            'email' => $this->resource->getAttribute('email'),
            'email_verified' => $this->resource->getAttribute('email_verified_at') !== null,
            'avatar_url' => app(AvatarService::class)->url($this->resource),
            // For a token request this reflects what the token may do, not only what the owner may do.
            'permissions' => array_values(array_filter(
                app(PermissionRegistry::class)->names(),
                fn (string $permission) => $this->resource->can($permission),
            )),
        ];
    }
}
