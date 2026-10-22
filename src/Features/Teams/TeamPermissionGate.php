<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Ssdev\Headless\Permissions\PermissionRegistry;

/**
 * Runs before Spatie's check, which only sees the current team: a permission given outside any team counts in
 * every team, and a global permission counts only when given outside any team.
 */
final class TeamPermissionGate
{
    public function __construct(
        private Gate $gate,
        private PermissionRegistry $permissions,
        private GlobalPermissionGrants $grants,
    ) {}

    public function register(): void
    {
        $this->gate->before(function (mixed $user, string $ability): ?bool {
            if (! $user instanceof Model || ! in_array($ability, $this->permissions->names(), true)) {
                return null;
            }

            $grantedGlobally = in_array($ability, $this->grants->of($user), true);

            if (in_array($ability, $this->permissions->globalNames(), true)) {
                return $grantedGlobally;
            }

            return $grantedGlobally ? true : null;
        });
    }
}
