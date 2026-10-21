<?php

namespace Ssdev\Headless\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Ssdev\Headless\Account\PersonalAccessTokenService;
use Ssdev\Headless\Http\Inertia\HeadlessPage;
use Ssdev\Headless\Http\Requests\Settings\CreateApiTokenRequest;
use Ssdev\Headless\Http\Resources\PersonalAccessTokenResource;
use Ssdev\Headless\Support\Flash;

final class ApiTokenController
{
    public function index(Request $request, PersonalAccessTokenService $tokens): Response
    {
        return HeadlessPage::render('Settings/ApiTokens', [
            'tokens' => PersonalAccessTokenResource::collection($tokens->forUser($request->user()))->resolve($request),
            'permissions' => $tokens->grantableGroups($request->user()),
            // Flashed once by store(), so the plain-text token is shown on a single page load.
            'createdToken' => $request->session()->get(Flash::TOKEN),
            'tokensUrl' => route('headless.api-tokens.store'),
        ]);
    }

    public function store(CreateApiTokenRequest $request, PersonalAccessTokenService $tokens): RedirectResponse
    {
        $token = $tokens->create($request->user(), $request->validated('name'), $request->validated('abilities'));

        return redirect()->route('headless.api-tokens.index')
            ->with(Flash::TOKEN, $token->plainTextToken)
            ->with(Flash::SUCCESS, __('headless::settings.api_tokens.created_flash'));
    }

    public function destroy(Request $request, string $token, PersonalAccessTokenService $tokens): RedirectResponse
    {
        abort_unless($tokens->revoke($request->user(), $token), 404);

        return back()->with(Flash::SUCCESS, __('headless::settings.api_tokens.revoked'));
    }
}
