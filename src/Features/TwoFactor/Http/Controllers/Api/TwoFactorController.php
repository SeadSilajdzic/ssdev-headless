<?php

namespace Ssdev\Headless\Features\TwoFactor\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ssdev\Headless\Features\TwoFactor\Http\Requests\ConfirmTwoFactorRequest;
use Ssdev\Headless\Features\TwoFactor\Http\Requests\TwoFactorPasswordRequest;
use Ssdev\Headless\Features\TwoFactor\Http\Resources\RecoveryCodesResource;
use Ssdev\Headless\Features\TwoFactor\Http\Resources\TwoFactorSetupResource;
use Ssdev\Headless\Features\TwoFactor\Http\Resources\TwoFactorStatusResource;
use Ssdev\Headless\Features\TwoFactor\TwoFactorService;

final class TwoFactorController
{
    public function show(Request $request, TwoFactorService $twoFactor): TwoFactorStatusResource
    {
        return TwoFactorStatusResource::make($twoFactor->status($request->user()));
    }

    public function enable(TwoFactorPasswordRequest $request, TwoFactorService $twoFactor): TwoFactorSetupResource
    {
        return TwoFactorSetupResource::make($twoFactor->enable($request->user()));
    }

    public function confirm(ConfirmTwoFactorRequest $request, TwoFactorService $twoFactor): RecoveryCodesResource
    {
        return RecoveryCodesResource::make($twoFactor->confirm($request->user(), $request->validated('code')));
    }

    public function regenerateRecoveryCodes(TwoFactorPasswordRequest $request, TwoFactorService $twoFactor): RecoveryCodesResource
    {
        return RecoveryCodesResource::make($twoFactor->regenerateRecoveryCodes($request->user()));
    }

    public function disable(TwoFactorPasswordRequest $request, TwoFactorService $twoFactor): Response
    {
        $twoFactor->disable($request->user());

        return response()->noContent();
    }
}
