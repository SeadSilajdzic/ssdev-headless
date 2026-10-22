<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Auth\User;
use Illuminate\Validation\ValidationException;

final class TeamMembershipService
{
    public function __construct(
        private TeamRepository $teams,
        private ConnectionInterface $database,
        private Repository $config,
    ) {}

    public function switchTo(User $user, Team $team): void
    {
        $this->ensureMember($team, $user);

        $user->forceFill(['current_team_id' => $team->getKey()])->save();
    }

    public function leave(User $user, Team $team): void
    {
        if ($team->isOwnedBy($user)) {
            throw ValidationException::withMessages(['team' => __('headless::teams.errors.owner_cannot_leave')]);
        }

        $this->detach($team, $user);
    }

    public function remove(Team $team, User $member): void
    {
        if ($team->isOwnedBy($member)) {
            throw ValidationException::withMessages(['team' => __('headless::teams.errors.cannot_remove_owner')]);
        }

        $this->detach($team, $member);
    }

    /**
     * The previous owner stays in the team as a member.
     */
    public function transfer(Team $team, User $newOwner): void
    {
        $this->database->transaction(function () use ($team, $newOwner): void {
            $locked = Team::query()->lockForUpdate()->findOrFail($team->getKey());
            $this->ensureMember($locked, $newOwner);

            $locked->forceFill(['owner_id' => $newOwner->getKey()])->save();
            $team->setRawAttributes($locked->getAttributes(), true);
        });
    }

    /**
     * A user whose current team is gone continues in another of their teams, or in none.
     */
    public function moveAwayFrom(User $user, Team $team): void
    {
        $user->refresh();

        $current = $user->getAttribute('current_team_id');

        if ($current !== null && (string) $current !== (string) $team->getKey()) {
            return;
        }

        $user->forceFill(['current_team_id' => $this->teams->forMember($user)->first()?->getKey()])->save();
    }

    /**
     * Role and permission assignments made inside the team.
     */
    public function forgetTeamRoles(Team $team, ?User $member = null): void
    {
        $tables = $this->config->get('permission.table_names');
        $teamColumn = $this->config->get('permission.column_names.team_foreign_key');
        $morphKey = $this->config->get('permission.column_names.model_morph_key');

        foreach ([$tables['model_has_roles'], $tables['model_has_permissions']] as $table) {
            $this->database->table($table)
                ->where($teamColumn, $team->getKey())
                ->when($member !== null, fn ($query) => $query->where($morphKey, $member?->getKey())->where('model_type', $member?->getMorphClass()))
                ->delete();
        }
    }

    private function detach(Team $team, User $member): void
    {
        $this->database->transaction(function () use ($team, $member): void {
            $team->members()->detach($member->getKey());
            $this->forgetTeamRoles($team, $member);
            $this->moveAwayFrom($member, $team);
        });
    }

    private function ensureMember(Team $team, User $user): void
    {
        if (! $this->teams->isMember($team, $user)) {
            throw ValidationException::withMessages(['team' => __('headless::teams.errors.not_a_member')]);
        }
    }
}
