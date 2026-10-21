<?php

namespace Ssdev\Headless\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Ssdev\Headless\Account\PersonalAccessTokenService;
use Ssdev\Headless\Http\Requests\Api\CreateTokenRequest;
use Ssdev\Headless\Http\Resources\PersonalAccessTokenResource;

final class ApiTokenController
{
    public function index(Request $request, PersonalAccessTokenService $tokens): AnonymousResourceCollection
    {
        return PersonalAccessTokenResource::collection($tokens->forUser($request->user()));
    }

    public function store(CreateTokenRequest $request, PersonalAccessTokenService $tokens): JsonResponse
    {
        $token = $tokens->create($request->user(), $request->validated('name'), $request->validated('abilities'));

        return response()->json([
            'data' => [
                'token' => PersonalAccessTokenResource::make($token->accessToken)->resolve($request),
                'plain_text_token' => $token->plainTextToken,
            ],
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request, string $token, PersonalAccessTokenService $tokens): Response
    {
        abort_unless($tokens->revoke($request->user(), $token), 404);

        return response()->noContent();
    }
}
