<?php

namespace Ssdev\Headless\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Ssdev\Headless\Account\PasswordService;
use Ssdev\Headless\Http\Requests\Settings\UpdatePasswordRequest;
use Ssdev\Headless\Support\Flash;

final class PasswordController
{
    public function update(UpdatePasswordRequest $request, PasswordService $passwords): RedirectResponse
    {
        $passwords->change($request->user(), $request->validated('password'), $request->session()->getId());

        return back()->with(Flash::SUCCESS, __('headless::settings.password.updated'));
    }
}
