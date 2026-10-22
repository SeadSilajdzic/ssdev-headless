<?php

namespace Ssdev\Headless\Features\Users\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Ssdev\Headless\Features\Users\Http\Requests\ListUsersRequest;
use Ssdev\Headless\Features\Users\Http\Requests\SaveUserRequest;
use Ssdev\Headless\Features\Users\Http\Resources\ManagedUserResource;
use Ssdev\Headless\Features\Users\ManagedUserRepository;
use Ssdev\Headless\Features\Users\UserInvitationService;
use Ssdev\Headless\Features\Users\UserService;

final class UserController
{
    public function __construct(
        private ManagedUserRepository $users,
        private UserService $service,
    ) {}

    public function index(ListUsersRequest $request): AnonymousResourceCollection
    {
        return ManagedUserResource::collection($this->users->paginate($request->criteria(), $request->perPage())->withQueryString());
    }

    public function store(SaveUserRequest $request, UserInvitationService $invitations): JsonResponse
    {
        $user = $invitations->invite($request->user(), $request->name(), $request->email());

        return ManagedUserResource::make($user)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $user): ManagedUserResource
    {
        return ManagedUserResource::make($this->users->findOrFail($user));
    }

    public function update(SaveUserRequest $request, string $user): ManagedUserResource
    {
        return ManagedUserResource::make($this->service->update($this->users->findOrFail($user), $request->name(), $request->email()));
    }

    public function destroy(Request $request, string $user): Response
    {
        $this->service->delete($request->user(), $this->users->findOrFail($user));

        return response()->noContent();
    }
}
