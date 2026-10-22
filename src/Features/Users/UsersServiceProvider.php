<?php

namespace Ssdev\Headless\Features\Users;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Features\Users\Http\Middleware\LogOutBlockedUsers;
use Ssdev\Headless\Features\Users\Listeners\CompletePendingInvitation;
use Ssdev\Headless\Features\Users\Listeners\RejectBlockedSignIn;
use Ssdev\Headless\Navigation\NavigationGroup;
use Ssdev\Headless\Navigation\NavigationItem;
use Ssdev\Headless\Navigation\NavigationRegistry;
use Ssdev\Headless\Routing\PackageRoutes;

final class UsersServiceProvider extends ServiceProvider
{
    private const INVITATION_THROTTLE_SECONDS = 60;

    public function register(): void
    {
        $config = $this->app['config'];

        // Invitations share the reset token table but live longer, so they get a broker of their own.
        $config->set('auth.passwords.'.UserInvitationService::BROKER, [
            'provider' => $config->get('auth.passwords.users.provider', 'users'),
            'table' => $config->get('auth.passwords.users.table', 'password_reset_tokens'),
            'expire' => (int) $config->get('headless.users.invitation_expire_minutes'),
            'throttle' => self::INVITATION_THROTTLE_SECONDS,
        ]);
    }

    public function boot(Dispatcher $events, HttpKernel $kernel, InvitationRateLimit $rateLimit, NavigationRegistry $navigation, FeatureRegistry $features): void
    {
        $rateLimit->register();

        $events->listen(PasswordReset::class, CompletePendingInvitation::class);
        $events->listen(Login::class, RejectBlockedSignIn::class);

        // Added to the kernel rather than the router, which the kernel overwrites whenever its own groups change.
        if ($kernel instanceof Kernel) {
            $kernel->appendMiddlewareToGroup('web', LogOutBlockedUsers::class);
            $kernel->appendMiddlewareToGroup('api', LogOutBlockedUsers::class.':sanctum');
        }

        PackageRoutes::web(__DIR__.'/../../../routes/users.php');
        PackageRoutes::api(__DIR__.'/../../../routes/users-api.php');

        if ($features->isEnabled(Feature::Roles->value)) {
            PackageRoutes::web(__DIR__.'/../../../routes/user-roles.php');
            PackageRoutes::api(__DIR__.'/../../../routes/user-roles-api.php');
        }

        $navigation->add(new NavigationItem(
            'users',
            'headless::navigation.users',
            'headless.users.index',
            'users',
            NavigationGroup::Administration,
            permission: UserPermission::View->value,
        ));
    }
}
