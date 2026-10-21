<?php

namespace Ssdev\Headless\Features\TwoFactor\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ssdev\Headless\Features\TwoFactor\PendingTwoFactorChallenge;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property PendingTwoFactorChallenge $resource
 */
final class TwoFactorChallengeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'two_factor' => true,
            'challenge_token' => $this->resource->token,
            'expires_at' => $this->resource->expiresAt->toIso8601String(),
        ];
    }

    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
