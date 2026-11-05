<?php

namespace Ssdev\Headless\Features\Users\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Ssdev\Headless\Features\Roles\UserRoleService;
use Ssdev\Headless\Features\Users\Http\Requests\SyncUserRolesRequest;
use Ssdev\Headless\Features\Users\ManagedUserRepository;
use Ssdev\Headless\Support\Flash;

final class UserRoleController
{
    public function update(SyncUserRolesRequest $request, string $user, ManagedUserRepository $users, UserRoleService $roles): RedirectResponse
    {
        $roles->sync($request->user(), $users->findOrFail($user), $request->roleIds(), $request->knownRoleIds());

        return back()->with(Flash::SUCCESS, __('headless::users.roles.saved'));
    }
}
