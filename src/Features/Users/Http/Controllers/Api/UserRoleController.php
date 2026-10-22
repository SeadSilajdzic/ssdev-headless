<?php

namespace Ssdev\Headless\Features\Users\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Ssdev\Headless\Features\Roles\RoleRepository;
use Ssdev\Headless\Features\Roles\UserRoleService;
use Ssdev\Headless\Features\Users\Http\Requests\SyncUserRolesRequest;
use Ssdev\Headless\Features\Users\Http\Resources\UserRoleResource;
use Ssdev\Headless\Features\Users\ManagedUserRepository;

final class UserRoleController
{
    public function __construct(
        private ManagedUserRepository $users,
        private RoleRepository $roles,
    ) {}

    public function index(Request $request, string $user): AnonymousResourceCollection
    {
        return UserRoleResource::collection($this->roles->assignedTo($this->users->findOrFail($user)));
    }

    public function update(SyncUserRolesRequest $request, string $user, UserRoleService $roles): AnonymousResourceCollection
    {
        $managed = $this->users->findOrFail($user);
        $roles->sync($request->user(), $managed, $request->roleIds());

        return UserRoleResource::collection($this->roles->assignedTo($managed));
    }
}
