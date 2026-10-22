<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

/**
 * The signed-in user's current team, or the global context when there is none. An application may still
 * pin a team with setPermissionsTeamId().
 */
final class CurrentTeamResolver implements PermissionsTeamResolver
{
    private int|string|null $teamId = null;

    public function setPermissionsTeamId(int|string|Model|null $id): void
    {
        $this->teamId = $id instanceof Model ? $id->getKey() : $id;
    }

    public function getPermissionsTeamId(): int|string
    {
        $currentTeam = auth()->user()?->getAttribute('current_team_id');

        return $this->teamId ?? $currentTeam ?? TeamContext::GLOBAL;
    }
}
