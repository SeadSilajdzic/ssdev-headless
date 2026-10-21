<?php

namespace Ssdev\Headless\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Ssdev\Headless\Account\RegistrationData;
use Ssdev\Headless\Account\RegistrationService;
use Ssdev\Headless\Http\Requests\Auth\RegisterUserRequest;

final class RegistrationController
{
    /**
     * Answers the same way whether or not the email already has an account.
     */
    public function store(RegisterUserRequest $request, RegistrationService $registration): JsonResponse
    {
        $registration->register(RegistrationData::fromRequest($request));

        return response()->json(['message' => __('headless::auth.registration.check_inbox')], Response::HTTP_ACCEPTED);
    }
}
