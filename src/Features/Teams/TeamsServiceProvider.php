<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Ssdev\Headless\Events\AccountDeleting;
use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Features\Teams\Listeners\CheckOwnedTeamsBeforeDeletion;
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

    public function boot(Dispatcher $events, Gate $gate, TeamInvitationRateLimit $rateLimit, FeatureRegistry $features): void
    {
        $events->listen(Registered::class, CreatePersonalTeam::class);
        $events->listen(AccountDeleting::class, CheckOwnedTeamsBeforeDeletion::class);
        $gate->policy(Team::class, TeamPolicy::class);
        $rateLimit->register();

        Inertia::share('teams', fn () => $this->app->make(TeamSwitcherProps::class)->for(request()->user()));

        PackageRoutes::web(__DIR__.'/../../../routes/teams.php');
        PackageRoutes::api(__DIR__.'/../../../routes/teams-api.php');

        if ($features->isEnabled(Feature::Roles->value)) {
            PackageRoutes::web(__DIR__.'/../../../routes/team-roles.php');
            PackageRoutes::api(__DIR__.'/../../../routes/team-roles-api.php');
        }
    }
}
