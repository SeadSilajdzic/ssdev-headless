<?php

namespace Ssdev\Headless\Features\TwoFactor\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Only returned right after the codes were created; they are never listed again.
 *
 * @property list<string> $resource
 */
final class RecoveryCodesResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'recovery_codes' => $this->resource,
        ];
    }
}
