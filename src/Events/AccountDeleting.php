<?php

namespace Ssdev\Headless\Events;

use Illuminate\Foundation\Auth\User;

/**
 * Dispatched inside the deletion's transaction, so a listener can still refuse it with a ValidationException.
 */
final class AccountDeleting
{
    public function __construct(
        public readonly User $user,
        // False for user management's soft delete, which can be undone in the database.
        public readonly bool $permanent,
    ) {}
}
