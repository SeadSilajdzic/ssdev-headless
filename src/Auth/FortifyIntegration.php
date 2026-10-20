<?php

namespace Ssdev\Headless\Auth;

use Illuminate\Contracts\Config\Repository;
use Laravel\Fortify\Fortify;

final class FortifyIntegration
{
    public function __construct(private Repository $config) {}

    public function register(): void
    {
        // The package registers Fortify's controllers itself, with its own prefix and middleware.
        Fortify::ignoreRoutes();

        $this->config->set([
            'fortify.guard' => 'web',
            'fortify.passwords' => 'users',
            'fortify.username' => 'email',
            'fortify.email' => 'email',
            'fortify.lowercase_usernames' => true,
            'fortify.features' => [],
        ]);
    }

    public function boot(): void {}
}
