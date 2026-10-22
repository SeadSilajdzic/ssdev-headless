<?php

namespace Ssdev\Headless\Features\Teams\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ssdev\Headless\Features\Teams\Team;

/**
 * @property Team $resource
 */
final class TeamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'personal' => $this->resource->personal,
            'is_owner' => $request->user() !== null && $this->resource->isOwnedBy($request->user()),
            'is_current' => (string) $request->user()?->getAttribute('current_team_id') === (string) $this->resource->getKey(),
        ];
    }
}
