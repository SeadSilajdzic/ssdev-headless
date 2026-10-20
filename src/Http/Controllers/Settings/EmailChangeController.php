<?php

namespace Ssdev\Headless\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Ssdev\Headless\Account\EmailChangeService;
use Ssdev\Headless\Account\UserRepository;
use Ssdev\Headless\Support\Flash;

final class EmailChangeController
{
    public function __invoke(Request $request, string $user, UserRepository $users, EmailChangeService $emailChanges): RedirectResponse
    {
        $account = $users->find($user) ?? abort(404);

        $confirmed = $emailChanges->confirm($account, (string) $request->query('email'), (string) $request->query('current'));
        $message = __($confirmed ? 'headless::settings.email_change.confirmed' : 'headless::settings.email_change.invalid');

        if ($request->user() !== null) {
            return redirect()->route('headless.profile.edit')->with($confirmed ? Flash::SUCCESS : Flash::ERROR, $message);
        }

        return redirect()->route('login')->with(Flash::STATUS, $message);
    }
}
