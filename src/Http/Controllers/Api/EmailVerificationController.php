<?php

namespace Ssdev\Headless\Http\Controllers\Api;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class EmailVerificationController
{
    public function store(Request $request): JsonResponse|Response
    {
        $user = $request->user();

        if (! $user instanceof MustVerifyEmail || $user->hasVerifiedEmail()) {
            return response()->noContent();
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => __('headless::auth.verification.link_sent')], Response::HTTP_ACCEPTED);
    }
}
