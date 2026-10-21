<?php

namespace Ssdev\Headless\Features\TwoFactor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Ssdev\Headless\Features\TwoFactor\Http\Requests\ConfirmTwoFactorRequest;
use Ssdev\Headless\Features\TwoFactor\Http\Resources\TwoFactorSetupResource;
use Ssdev\Headless\Features\TwoFactor\TwoFactorService;
use Ssdev\Headless\Http\Inertia\HeadlessPage;
use Ssdev\Headless\Support\Flash;

final class TwoFactorSettingsController
{
    public function edit(Request $request, TwoFactorService $twoFactor): Response
    {
        $setup = $twoFactor->setup($request->user());

        return HeadlessPage::render('TwoFactor/Settings', [
            'status' => $twoFactor->status($request->user())->value,
            'setup' => $setup === null ? null : TwoFactorSetupResource::make($setup)->resolve($request),
            // Flashed once by confirm() and regenerateRecoveryCodes(), so the codes are shown on a single page load.
            'recoveryCodes' => $request->session()->get(Flash::RECOVERY_CODES),
            'enableUrl' => route('headless.two-factor.enable'),
            'confirmUrl' => route('headless.two-factor.confirm'),
            'recoveryCodesUrl' => route('headless.two-factor.recovery-codes'),
            'disableUrl' => route('headless.two-factor.disable'),
        ]);
    }

    public function enable(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $twoFactor->enable($request->user());

        return redirect()->route('headless.two-factor.edit');
    }

    public function confirm(ConfirmTwoFactorRequest $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $codes = $twoFactor->confirm($request->user(), $request->validated('code'));

        return redirect()->route('headless.two-factor.edit')
            ->with(Flash::RECOVERY_CODES, $codes)
            ->with(Flash::SUCCESS, __('headless::two_factor.enabled_flash'));
    }

    public function regenerateRecoveryCodes(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $codes = $twoFactor->regenerateRecoveryCodes($request->user());

        return redirect()->route('headless.two-factor.edit')
            ->with(Flash::RECOVERY_CODES, $codes)
            ->with(Flash::SUCCESS, __('headless::two_factor.recovery_codes.regenerated'));
    }

    public function disable(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $twoFactor->disable($request->user());

        return redirect()->route('headless.two-factor.edit')->with(Flash::SUCCESS, __('headless::two_factor.disabled_flash'));
    }
}
