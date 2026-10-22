<?php

namespace Ssdev\Headless\Features\Teams\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Auth\User;
use Ssdev\Headless\Features\Teams\Team;

final class TeamMemberRolesUpdated implements ShouldDispatchAfterCommit
{
    /**
     * @param  list<int>  $added
     * @param  list<int>  $removed
     */
    public function __construct(
        public readonly Team $team,
        public readonly User $member,
        public readonly User $actor,
        public readonly array $added,
        public readonly array $removed,
    ) {}
}
