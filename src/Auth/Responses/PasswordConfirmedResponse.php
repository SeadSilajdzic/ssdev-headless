<?php

namespace Ssdev\Headless\Auth\Responses;

use Laravel\Fortify\Contracts\PasswordConfirmedResponse as PasswordConfirmedResponseContract;
use Ssdev\Headless\Auth\SafeRedirect;
use Symfony\Component\HttpFoundation\Response;

final class PasswordConfirmedResponse implements PasswordConfirmedResponseContract
{
    public function __construct(private SafeRedirect $redirect) {}

    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return response()->noContent(Response::HTTP_CREATED);
        }

        return $this->redirect->intended($request, route('headless.dashboard'));
    }
}
