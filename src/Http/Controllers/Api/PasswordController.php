<?php

namespace Ssdev\Headless\Http\Controllers\Api;

use Illuminate\Http\Response;
use Ssdev\Headless\Account\PasswordService;
use Ssdev\Headless\Http\Requests\Settings\UpdatePasswordRequest;

final class PasswordController
{
    public function update(UpdatePasswordRequest $request, PasswordService $passwords): Response
    {
        // API requests carry no session, so every browser session ends.
        $passwords->change($request->user(), $request->validated('password'), null);

        return response()->noContent();
    }
}
