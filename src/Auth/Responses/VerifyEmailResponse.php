<?php

namespace Ssdev\Headless\Auth\Responses;

use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Ssdev\Headless\Auth\SafeRedirect;
use Ssdev\Headless\Support\Flash;
use Symfony\Component\HttpFoundation\Response;

final class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function __construct(private SafeRedirect $redirect) {}

    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return response()->noContent();
        }

        return $this->redirect->intended($request, route('headless.dashboard'))
            ->with(Flash::SUCCESS, __('headless::auth.verification.verified'));
    }
}
