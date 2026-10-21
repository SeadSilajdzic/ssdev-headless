<?php

namespace Ssdev\Headless\Features\Roles\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

/**
 * @property Role $resource
 */
final class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'permissions' => $this->whenLoaded('permissions', fn () => $this->resource->permissions->pluck('name')->sort()->values()->all()),
            'users_count' => $this->resource->getAttribute('users_count'),
            'permissions_count' => $this->resource->getAttribute('permissions_count'),
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
