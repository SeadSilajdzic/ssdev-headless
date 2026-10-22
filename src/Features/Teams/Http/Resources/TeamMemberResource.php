<?php

namespace Ssdev\Headless\Features\Teams\Http\Resources;

use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ssdev\Headless\Account\AvatarService;
use Ssdev\Headless\Features\Teams\Team;

/**
 * @property User $resource
 */
final class TeamMemberResource extends JsonResource
{
    public function __construct(User $member, private Team $team)
    {
        parent::__construct($member);
    }

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
            'is_owner' => $this->team->isOwnedBy($this->resource),
        ];
    }
}
