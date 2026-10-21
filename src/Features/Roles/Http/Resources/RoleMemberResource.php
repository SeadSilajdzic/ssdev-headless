<?php

namespace Ssdev\Headless\Features\Roles\Http\Resources;

use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ssdev\Headless\Account\AvatarService;

/**
 * @property User $resource
 */
final class RoleMemberResource extends JsonResource
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
            'avatar_url' => app(AvatarService::class)->url($this->resource),
        ];
    }
}
