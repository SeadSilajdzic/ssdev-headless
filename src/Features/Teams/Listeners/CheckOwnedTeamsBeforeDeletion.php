<?php

namespace Ssdev\Headless\Features\Teams\Listeners;

use Illuminate\Validation\ValidationException;
use Ssdev\Headless\Events\AccountDeleting;
use Ssdev\Headless\Features\Teams\Team;
use Ssdev\Headless\Features\Teams\TeamService;

/**
 * An owner hands over every team that has other members first; teams with nobody else go with a deleted account.
 */
final class CheckOwnedTeamsBeforeDeletion
{
    public function __construct(private TeamService $teams) {}

    public function handle(AccountDeleting $event): void
    {
        $owned = Team::query()->where('owner_id', $event->user->getKey())->withCount('members')->get();

        if ($owned->contains(fn (Team $team): bool => $team->getAttribute('members_count') > 1)) {
            throw ValidationException::withMessages(['account' => __('headless::teams.errors.owns_teams')]);
        }

        if (! $event->permanent) {
            return;
        }

        foreach ($owned as $team) {
            $this->teams->delete($team);
        }
    }
}
