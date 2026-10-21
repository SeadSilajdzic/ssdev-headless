<?php

namespace Ssdev\Headless\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ssdev\Headless\Account\ApiTokenAuthenticator;
use Ssdev\Headless\Http\Requests\Api\IssueTokenRequest;
use Ssdev\Headless\Http\Resources\NewAccessTokenResource;

final class AuthTokenController
{
    public function store(IssueTokenRequest $request, ApiTokenAuthenticator $authenticator): JsonResponse
    {
        $token = $authenticator->issue(
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('device_name'),
        );

        return NewAccessTokenResource::make($token)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Request $request, ApiTokenAuthenticator $authenticator): Response
    {
        $authenticator->revokeCurrentToken($request->user());

        return response()->noContent();
    }
}
