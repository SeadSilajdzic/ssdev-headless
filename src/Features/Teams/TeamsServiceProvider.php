<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Support\ServiceProvider;

final class TeamsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Set before Spatie's registrar is first resolved, which reads these once.
        $this->app['config']->set([
            'permission.teams' => true,
            'permission.team_resolver' => CurrentTeamResolver::class,
            'permission.column_names.team_foreign_key' => 'team_id',
        ]);
    }
}
