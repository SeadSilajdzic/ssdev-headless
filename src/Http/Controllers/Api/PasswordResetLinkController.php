<?php

namespace Ssdev\Headless\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Ssdev\Headless\Account\PasswordResetLinkSender;
use Ssdev\Headless\Http\Requests\Auth\SendPasswordResetLinkRequest;

final class PasswordResetLinkController
{
    /**
     * Answers the same way whether or not the email has an account; the mail goes out after the response.
     */
    public function store(SendPasswordResetLinkRequest $request, PasswordResetLinkSender $links): JsonResponse
    {
        $links->send($request->validated('email'));

        return response()->json(['message' => __('headless::auth.passwords.link_sent')], Response::HTTP_ACCEPTED);
    }
}
