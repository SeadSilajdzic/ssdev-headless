<?php

namespace Ssdev\Headless\Features\TwoFactor\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ssdev\Headless\Features\TwoFactor\TwoFactorStatus;

/**
 * @property TwoFactorStatus $resource
 */
final class TwoFactorStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->resource->value,
        ];
    }
}
