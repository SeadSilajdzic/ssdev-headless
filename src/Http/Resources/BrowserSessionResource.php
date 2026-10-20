<?php

namespace Ssdev\Headless\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ssdev\Headless\Account\BrowserSession;

/**
 * @property BrowserSession $resource
 */
final class BrowserSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->publicId,
            'platform' => $this->resource->platform,
            'browser' => $this->resource->browser,
            'ip_address' => $this->resource->ipAddress,
            'last_active' => $this->resource->lastActiveAt->diffForHumans(),
            'is_current' => $this->resource->isCurrent,
            'logout_url' => route('headless.sessions.destroy', $this->resource->publicId),
        ];
    }
}
