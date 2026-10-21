<?php

namespace Ssdev\Headless\Http\Resources;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\NewAccessToken;

/**
 * The only response that ever carries the plain-text token.
 *
 * @property NewAccessToken $resource
 */
final class NewAccessTokenResource extends JsonResource
{
    private const TOKEN_TYPE = 'Bearer';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $expiresAt = $this->resource->accessToken->getAttribute('expires_at');

        return [
            'token' => $this->resource->plainTextToken,
            'token_type' => self::TOKEN_TYPE,
            'expires_at' => $expiresAt instanceof CarbonInterface ? $expiresAt->toIso8601String() : null,
        ];
    }
}
