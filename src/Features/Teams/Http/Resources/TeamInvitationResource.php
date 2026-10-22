<?php

namespace Ssdev\Headless\Features\Teams\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ssdev\Headless\Features\Teams\TeamInvitation;

/**
 * @property TeamInvitation $resource
 */
final class TeamInvitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'email' => $this->resource->email,
            'role_id' => $this->resource->role_id,
            'created_at' => $this->resource->getAttribute('created_at')?->toIso8601String(),
        ];
    }
}
