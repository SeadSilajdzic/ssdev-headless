<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Foundation\Auth\User;

/**
 * What every page needs for the team switcher.
 */
final class TeamSwitcherProps
{
    public function __construct(private TeamRepository $teams) {}

    /**
     * @return array{current: array{id: int, name: string}|null, all: list<array{id: int, name: string}>, switchUrl: string, createUrl: string, settingsUrl: string|null}|null
     */
    public function for(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $teams = $this->teams->forMember($user);
        $current = $teams->first(fn (Team $team): bool => (string) $team->getKey() === (string) $user->getAttribute('current_team_id'));

        return [
            'current' => $current === null ? null : $this->summary($current),
            'all' => $teams->map(fn (Team $team): array => $this->summary($team))->values()->all(),
            'switchUrl' => route('headless.current-team.update'),
            'createUrl' => route('headless.teams.create'),
            'settingsUrl' => $current === null ? null : route('headless.teams.show', $current->getKey()),
        ];
    }

    /**
     * @return array{id: int, name: string}
     */
    private function summary(Team $team): array
    {
        return ['id' => (int) $team->getKey(), 'name' => $team->name];
    }
}
