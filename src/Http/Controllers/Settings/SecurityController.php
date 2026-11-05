<?php

namespace Ssdev\Headless\Http\Controllers\Settings;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Inertia\Response;
use Ssdev\Headless\Account\BrowserSessionService;
use Ssdev\Headless\Account\SessionRepository;
use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Features\TwoFactor\TwoFactorService;
use Ssdev\Headless\Http\Inertia\HeadlessPage;
use Ssdev\Headless\Http\Resources\BrowserSessionResource;

final class SecurityController
{
    public function edit(Request $request, BrowserSessionService $browserSessions, SessionRepository $sessions, FeatureRegistry $features, Container $container): Response
    {
        $props = [
            'sessionsSupported' => $sessions->supportsBrowserSessions(),
            'sessions' => BrowserSessionResource::collection($browserSessions->forUser($request->user(), $request->session()->getId()))->resolve($request),
            'logoutOthersUrl' => route('headless.sessions.destroy-others'),
        ];

        if ($features->isEnabled(Feature::TwoFactor->value)) {
            $props['twoFactor'] = [
                'status' => $container->make(TwoFactorService::class)->status($request->user())->value,
                'manageUrl' => route('headless.two-factor.edit'),
            ];
        }

        return HeadlessPage::render('Settings/Security', $props);
    }
}
