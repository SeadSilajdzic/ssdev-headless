<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Foundation\Auth\User;

/**
 * Members see their team; only the owner changes it. Teams are reached through TeamRepository::findForMember(),
 * so someone outside the team gets a 404 before any of this runs.
 */
final class TeamPolicy
{
    public function update(User $user, Team $team): bool
    {
        return $team->isOwnedBy($user);
    }

    public function delete(User $user, Team $team): bool
    {
        return $team->isOwnedBy($user);
    }

    public function manageMembers(User $user, Team $team): bool
    {
        return $team->isOwnedBy($user);
    }
}
