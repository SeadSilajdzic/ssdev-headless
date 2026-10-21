<?php

namespace Ssdev\Headless\Http\Controllers\Api;

use Illuminate\Http\Response;
use Ssdev\Headless\Account\AccountDeletionService;
use Ssdev\Headless\Http\Requests\Settings\DeleteAccountRequest;

final class AccountController
{
    public function destroy(DeleteAccountRequest $request, AccountDeletionService $accounts): Response
    {
        $accounts->delete($request->user());

        return response()->noContent();
    }
}
