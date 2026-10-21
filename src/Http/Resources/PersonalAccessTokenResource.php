<?php

namespace Ssdev\Headless\Http\Resources;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Describes a token without ever exposing its secret or hash.
 *
 * @property PersonalAccessToken $resource
 */
final class PersonalAccessTokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->getAttribute('name'),
            'abilities' => $this->resource->getAttribute('abilities'),
            'last_used_at' => $this->date('last_used_at'),
            'expires_at' => $this->date('expires_at'),
            'created_at' => $this->date('created_at'),
        ];
    }

    private function date(string $attribute): ?string
    {
        $value = $this->resource->getAttribute($attribute);

        return $value instanceof CarbonInterface ? $value->toIso8601String() : null;
    }
}
