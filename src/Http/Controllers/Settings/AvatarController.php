<?php

namespace Ssdev\Headless\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Ssdev\Headless\Account\AvatarService;
use Ssdev\Headless\Http\Requests\Settings\UpdateAvatarRequest;
use Ssdev\Headless\Support\Flash;

final class AvatarController
{
    public function store(UpdateAvatarRequest $request, AvatarService $avatars): RedirectResponse
    {
        $avatars->replace($request->user(), $request->file('avatar'));

        return back()->with(Flash::SUCCESS, __('headless::settings.avatar.updated'));
    }

    public function destroy(Request $request, AvatarService $avatars): RedirectResponse
    {
        $avatars->remove($request->user());

        return back()->with(Flash::SUCCESS, __('headless::settings.avatar.removed'));
    }
}
