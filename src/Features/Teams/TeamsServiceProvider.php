<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Ssdev\Headless\Features\Teams\Listeners\CreatePersonalTeam;

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

    public function boot(Dispatcher $events): void
    {
        $events->listen(Registered::class, CreatePersonalTeam::class);
    }
}
