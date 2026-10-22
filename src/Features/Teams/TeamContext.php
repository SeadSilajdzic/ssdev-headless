<?php

namespace Ssdev\Headless\Features\Teams;

use Ssdev\Headless\Permissions\GlobalAssignments;

final class TeamContext
{
    // Spatie's teams mode stores every assignment under a team; system-wide ones use this one.
    public const GLOBAL = GlobalAssignments::TEAM;
}
