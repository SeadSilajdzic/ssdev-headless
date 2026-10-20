<?php

namespace Ssdev\Headless\Auth\Responses;

use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;
use Ssdev\Headless\Support\Flash;
use Symfony\Component\HttpFoundation\Response;

final class PasswordResetResponse implements PasswordResetResponseContract
{
    public function toResponse($request): Response
    {
        $message = __('headless::auth.passwords.reset');

        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('login')->with(Flash::STATUS, $message);
    }
}
