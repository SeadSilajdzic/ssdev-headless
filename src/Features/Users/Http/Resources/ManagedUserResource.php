<?php

namespace Ssdev\Headless\Features\Users\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ssdev\Headless\Account\AvatarService;
use Ssdev\Headless\Features\Users\UserStatus;

/**
 * @property User $resource
 */
final class ManagedUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->getAttribute('name'),
            'email' => $this->resource->getAttribute('email'),
            'avatar_url' => app(AvatarService::class)->url($this->resource),
            'status' => UserStatus::of($this->resource)->value,
            'email_verified' => $this->resource->hasVerifiedEmail(),
            'created_at' => $this->iso($this->resource->getAttribute('created_at')),
            'blocked_at' => $this->iso($this->resource->getAttribute('blocked_at')),
        ];
    }

    // The application's User model need not cast the user management columns.
    private function iso(mixed $value): ?string
    {
        return $value === null ? null : CarbonImmutable::parse($value)->toIso8601String();
    }
}
