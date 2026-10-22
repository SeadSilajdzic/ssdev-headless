<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User;

final class TeamRepository
{
    /**
     * @return Collection<int, Team>
     */
    public function forMember(User $user): Collection
    {
        return $this->ofMember($user)->orderBy('name')->orderBy('id')->get();
    }

    /**
     * Anyone outside the team gets a 404, as if the team did not exist.
     */
    public function findForMember(User $user, int|string $id): Team
    {
        return $this->ofMember($user)->findOrFail($id);
    }

    public function isMember(Team $team, User $user): bool
    {
        return $team->members()->whereKey($user->getKey())->exists();
    }

    /**
     * @return Builder<Team>
     */
    private function ofMember(User $user): Builder
    {
        return Team::query()->whereHas('members', fn (Builder $members) => $members->whereKey($user->getKey()));
    }
}
