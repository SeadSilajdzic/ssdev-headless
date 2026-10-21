<?php

namespace Ssdev\Headless\Features\Roles\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Ssdev\Headless\Account\UserRepository;
use Ssdev\Headless\Features\Roles\Http\Requests\AddRoleMemberRequest;
use Ssdev\Headless\Features\Roles\Http\Resources\RoleMemberResource;
use Ssdev\Headless\Features\Roles\RoleAssignmentService;
use Ssdev\Headless\Features\Roles\RoleRepository;

final class RoleMemberController
{
    public function __construct(
        private RoleRepository $roles,
        private RoleAssignmentService $assignments,
    ) {}

    public function index(string $role): AnonymousResourceCollection
    {
        return RoleMemberResource::collection($this->roles->members($this->roles->findOrFail($role)));
    }

    public function store(AddRoleMemberRequest $request, string $role): JsonResponse
    {
        $this->assignments->assign($request->user(), $this->roles->findOrFail($role), $request->member());

        return RoleMemberResource::make($request->member())->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Request $request, string $role, string $user, UserRepository $users): Response
    {
        $model = $this->roles->findOrFail($role);
        $member = $users->find($user);

        abort_if($member === null || ! $this->assignments->isMember($model, $member), 404);

        $this->assignments->revoke($request->user(), $model, $member);

        return response()->noContent();
    }
}
