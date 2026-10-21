<?php

namespace Ssdev\Headless\Http\Controllers\Api;

use Illuminate\Http\Request;
use Ssdev\Headless\Account\AvatarService;
use Ssdev\Headless\Http\Requests\Settings\UpdateAvatarRequest;
use Ssdev\Headless\Http\Resources\UserResource;

final class AvatarController
{
    public function store(UpdateAvatarRequest $request, AvatarService $avatars): UserResource
    {
        $avatars->replace($request->user(), $request->file('avatar'));

        return UserResource::make($request->user());
    }

    public function destroy(Request $request, AvatarService $avatars): UserResource
    {
        $avatars->remove($request->user());

        return UserResource::make($request->user());
    }
}
