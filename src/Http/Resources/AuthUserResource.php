<?php

namespace Ssdev\Headless\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ssdev\Headless\Account\AvatarService;

final class AuthUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'email_verified' => $this->resource->email_verified_at !== null,
            'avatar_url' => app(AvatarService::class)->url($this->resource),
        ];
    }
}
