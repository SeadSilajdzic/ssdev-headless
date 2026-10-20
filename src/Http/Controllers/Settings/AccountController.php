<?php

namespace Ssdev\Headless\Http\Controllers\Settings;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Ssdev\Headless\Account\AccountDeletionService;
use Ssdev\Headless\Http\Requests\Settings\DeleteAccountRequest;

final class AccountController
{
    public function destroy(DeleteAccountRequest $request, AccountDeletionService $accounts, StatefulGuard $guard): RedirectResponse
    {
        $user = $request->user();

        // Logging out saves a new remember token, so it has to happen while the user still exists.
        $guard->logout();
        $accounts->delete($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
