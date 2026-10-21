<?php

namespace Ssdev\Headless\Http\Controllers\Api;

use Illuminate\Http\Response;
use Ssdev\Headless\Account\PasswordService;
use Ssdev\Headless\Account\PersonalAccessTokenService;
use Ssdev\Headless\Http\Requests\Settings\UpdatePasswordRequest;

final class PasswordController
{
    public function update(UpdatePasswordRequest $request, PasswordService $passwords, PersonalAccessTokenService $tokens): Response
    {
        // API requests carry no session, so every browser session ends; only the token making this request stays.
        $passwords->change($request->user(), $request->validated('password'), null, $tokens->currentToken($request->user())?->getKey());

        return response()->noContent();
    }
}
