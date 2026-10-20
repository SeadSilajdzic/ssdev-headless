<?php

namespace Ssdev\Headless\Http\Controllers\Settings;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Ssdev\Headless\Account\AvatarService;
use Ssdev\Headless\Account\ProfileService;
use Ssdev\Headless\Http\Inertia\HeadlessPage;
use Ssdev\Headless\Http\Requests\Settings\UpdateProfileRequest;
use Ssdev\Headless\Support\Flash;

final class ProfileController
{
    public function edit(Request $request, AvatarService $avatars): Response
    {
        return HeadlessPage::render('Settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail && (bool) config('headless.email_verification'),
            'avatar' => $avatars->constraints(),
            'updateUrl' => route('headless.profile.update'),
            'avatarUrl' => route('headless.avatar.store'),
            'verificationUrl' => route('verification.send'),
        ]);
    }

    public function update(UpdateProfileRequest $request, ProfileService $profile): RedirectResponse
    {
        $outcome = $profile->update($request->user(), $request->validated('name'), $request->validated('email'));

        return back()->with(Flash::SUCCESS, $outcome->message());
    }
}
