<?php

namespace Ssdev\Headless\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Ssdev\Headless\Account\RegistrationData;
use Ssdev\Headless\Account\RegistrationService;
use Ssdev\Headless\Http\Inertia\HeadlessPage;
use Ssdev\Headless\Http\Requests\Auth\RegisterUserRequest;
use Ssdev\Headless\Support\Flash;

final class RegistrationController
{
    public function create(): Response
    {
        return HeadlessPage::render('Auth/Register', [
            'submitUrl' => route('register.store'),
            'loginUrl' => route('login'),
        ]);
    }

    public function store(RegisterUserRequest $request, RegistrationService $registration): RedirectResponse
    {
        $registration->register(RegistrationData::fromRequest($request));

        return redirect()->route('login')->with(Flash::STATUS, __('headless::auth.registration.check_inbox'));
    }
}
