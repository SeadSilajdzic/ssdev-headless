<?php

namespace Ssdev\Headless\Features\Users;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Ssdev\Headless\Features\Users\Listeners\CompletePendingInvitation;
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

    public function boot(Dispatcher $events): void
    {
        $events->listen(PasswordReset::class, CompletePendingInvitation::class);

        PackageRoutes::web(__DIR__.'/../../../routes/users.php');
    }
}
