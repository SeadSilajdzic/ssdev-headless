<?php

namespace Ssdev\Headless\Features\TwoFactor\Http\Responses;

use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\FailedTwoFactorLoginResponse as FailedTwoFactorLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

final class FailedTwoFactorLoginResponse implements FailedTwoFactorLoginResponseContract
{
    private const CODE_FIELD = 'code';

    private const RECOVERY_CODE_FIELD = 'recovery_code';

    public function toResponse($request): Response
    {
        [$field, $message] = $request->filled(self::RECOVERY_CODE_FIELD)
            ? [self::RECOVERY_CODE_FIELD, __('headless::two_factor.errors.invalid_recovery_code')]
            : [self::CODE_FIELD, __('headless::two_factor.errors.invalid_code')];

        if ($request->wantsJson()) {
            throw ValidationException::withMessages([$field => $message]);
        }

        return redirect()->route('two-factor.login')->withErrors([$field => $message]);
    }
}
