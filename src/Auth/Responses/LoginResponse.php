<?php

namespace Ssdev\Headless\Auth\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Ssdev\Headless\Auth\SafeRedirect;
use Symfony\Component\HttpFoundation\Response;

final class LoginResponse implements LoginResponseContract, TwoFactorLoginResponse
{
    public function __construct(private SafeRedirect $redirect) {}

    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return response()->noContent();
        }

        return $this->redirect->intended($request, route('headless.dashboard'));
    }
}
