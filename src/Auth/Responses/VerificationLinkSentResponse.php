<?php

namespace Ssdev\Headless\Auth\Responses;

use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse;
use Ssdev\Headless\Support\Flash;
use Symfony\Component\HttpFoundation\Response;

final class VerificationLinkSentResponse implements EmailVerificationNotificationSentResponse
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return response()->noContent(Response::HTTP_ACCEPTED);
        }

        return back()->with(Flash::STATUS, __('headless::auth.verification.link_sent'));
    }
}
