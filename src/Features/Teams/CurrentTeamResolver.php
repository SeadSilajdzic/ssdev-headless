<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

final class CurrentTeamResolver implements PermissionsTeamResolver
{
    private int|string|null $teamId = null;

    public function setPermissionsTeamId(int|string|Model|null $id): void
    {
        $this->teamId = $id instanceof Model ? $id->getKey() : $id;
    }

    public function getPermissionsTeamId(): int|string
    {
        return $this->teamId ?? TeamContext::GLOBAL;
    }
}
