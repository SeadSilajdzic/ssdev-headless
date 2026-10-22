<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Ssdev\Headless\Features\Teams\Listeners\CreatePersonalTeam;
use Ssdev\Headless\Routing\PackageRoutes;

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

        $this->app->scoped(GlobalPermissionGrants::class);

        // On booting, so it follows the token ability gate and precedes Spatie's, which registers while booting.
        $this->app->booting(fn () => $this->app->make(TeamPermissionGate::class)->register());
    }

    public function boot(Dispatcher $events, Gate $gate, TeamInvitationRateLimit $rateLimit): void
    {
        $events->listen(Registered::class, CreatePersonalTeam::class);
        $gate->policy(Team::class, TeamPolicy::class);
        $rateLimit->register();

        Inertia::share('teams', fn () => $this->app->make(TeamSwitcherProps::class)->for(request()->user()));

        PackageRoutes::web(__DIR__.'/../../../routes/teams.php');
    }
}
