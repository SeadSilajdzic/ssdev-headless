<?php

namespace Ssdev\Headless\Features\TwoFactor\Http\Controllers;

use Illuminate\Database\ConnectionInterface;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;
use Laravel\Fortify\Http\Requests\TwoFactorLoginRequest;
use Ssdev\Headless\Account\UserRepository;

final class TwoFactorChallengeController
{
    /**
     * Fortify reads the recovery codes and replaces one in separate steps, so the account stays locked in between.
     */
    public function store(TwoFactorLoginRequest $request, TwoFactorAuthenticatedSessionController $sessions, ConnectionInterface $database, UserRepository $users): mixed
    {
        return $database->transaction(function () use ($request, $sessions, $users): mixed {
            $userId = $request->session()->get('login.id');

            if (is_int($userId) || is_string($userId)) {
                $users->findForUpdate($userId);
            }

            return $sessions->store($request);
        });
    }
}
