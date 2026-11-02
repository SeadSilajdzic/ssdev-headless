<?php

namespace Ssdev\Headless\Features\Tenancy\Listeners;

use Ssdev\Headless\Features\Teams\Team;
use Ssdev\Headless\Features\Teams\TeamService;
use Ssdev\Headless\Features\Tenancy\Events\TenantDeleting;

/**
 * Runs inside the tenant being deleted, so the team scope finds only its teams.
 */
final class DeleteTenantTeams
{
    public function __construct(private TeamService $teams) {}

    public function handle(TenantDeleting $event): void
    {
        Team::query()->each(fn (Team $team) => $this->teams->delete($team));
    }
}
