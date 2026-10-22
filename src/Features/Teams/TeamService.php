<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Auth\User;

final class TeamService
{
    public function __construct(
        private TeamMembershipService $memberships,
        private ConnectionInterface $database,
    ) {}

    public function create(User $owner, string $name, bool $personal = false): Team
    {
        return $this->database->transaction(function () use ($owner, $name, $personal): Team {
            $team = new Team;
            $team->forceFill([Team::OWNER_KEY => $owner->getKey(), 'name' => $name, 'personal' => $personal])->save();
            $team->members()->attach($owner->getKey());

            $owner->forceFill(['current_team_id' => $team->getKey()])->save();

            return $team;
        });
    }

    public function rename(Team $team, string $name): Team
    {
        $team->forceFill(['name' => $name])->save();

        return $team;
    }

    public function delete(Team $team): void
    {
        $this->database->transaction(function () use ($team): void {
            $members = $team->members()->get();

            // Explicit, so it does not depend on the database enforcing the cascades.
            $this->memberships->forgetTeamRoles($team);
            $team->invitations()->delete();
            $team->members()->detach();
            $team->delete();

            foreach ($members as $member) {
                $this->memberships->moveAwayFrom($member, $team);
            }
        });
    }
}
