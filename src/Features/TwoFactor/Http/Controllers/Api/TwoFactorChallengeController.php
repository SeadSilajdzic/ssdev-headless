<?php

namespace Ssdev\Headless\Features\TwoFactor\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Ssdev\Headless\Features\TwoFactor\ApiTwoFactorChallengeService;
use Ssdev\Headless\Features\TwoFactor\Http\Requests\TwoFactorChallengeRequest;
use Ssdev\Headless\Http\Resources\NewAccessTokenResource;

final class TwoFactorChallengeController
{
    public function store(TwoFactorChallengeRequest $request, ApiTwoFactorChallengeService $challenges): JsonResponse
    {
        $token = $challenges->complete(
            $request->validated('challenge_token'),
            $request->validated('code'),
            $request->validated('recovery_code'),
        );

        return NewAccessTokenResource::make($token)->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
