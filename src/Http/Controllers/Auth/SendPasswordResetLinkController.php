<?php

namespace Ssdev\Headless\Http\Controllers\Auth;

use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Ssdev\Headless\Account\PasswordResetLinkSender;
use Ssdev\Headless\Http\Requests\Auth\SendPasswordResetLinkRequest;

final class SendPasswordResetLinkController
{
    /**
     * Sent, unknown and throttled requests all get the same response.
     */
    public function store(
        SendPasswordResetLinkRequest $request,
        PasswordResetLinkSender $links,
        SuccessfulPasswordResetLinkRequestResponse $response,
    ): SuccessfulPasswordResetLinkRequestResponse {
        $links->send($request->validated('email'));

        return $response;
    }
}
