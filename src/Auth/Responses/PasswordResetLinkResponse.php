<?php

namespace Ssdev\Headless\Auth\Responses;

use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Ssdev\Headless\Support\Flash;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sent, unknown and throttled requests get the same answer, so the form never reveals which emails have accounts.
 */
final class PasswordResetLinkResponse implements FailedPasswordResetLinkRequestResponse, SuccessfulPasswordResetLinkRequestResponse
{
    public function toResponse($request): Response
    {
        $message = __('headless::auth.passwords.link_sent');

        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with(Flash::STATUS, $message);
    }
}
