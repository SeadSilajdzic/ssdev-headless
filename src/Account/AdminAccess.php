<?php

namespace Ssdev\Headless\Account;

use Illuminate\Foundation\Auth\User;

/**
 * How an admin gets every registered permission; the roles feature swaps in a role for the direct grant.
 */
interface AdminAccess
{
    public function grant(User $admin): void;
}
