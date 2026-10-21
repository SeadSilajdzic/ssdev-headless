<?php

namespace Ssdev\Headless\Http\Controllers\Api;

use Illuminate\Http\Request;
use Ssdev\Headless\Account\ProfileService;
use Ssdev\Headless\Account\ProfileUpdateOutcome;
use Ssdev\Headless\Http\Requests\Settings\UpdateProfileRequest;
use Ssdev\Headless\Http\Resources\UserResource;

final class ProfileController
{
    public function show(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }

    public function update(UpdateProfileRequest $request, ProfileService $profile): UserResource
    {
        $outcome = $profile->update($request->user(), $request->validated('name'), $request->validated('email'));

        return UserResource::make($request->user()->fresh())
            ->additional(['meta' => ['email_confirmation_sent' => $outcome === ProfileUpdateOutcome::EmailConfirmationSent]]);
    }
}
