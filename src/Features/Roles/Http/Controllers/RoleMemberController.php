<?php

namespace Ssdev\Headless\Features\Roles\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Ssdev\Headless\Account\UserRepository;
use Ssdev\Headless\Features\Roles\Http\Requests\AddRoleMemberRequest;
use Ssdev\Headless\Features\Roles\RoleAssignmentService;
use Ssdev\Headless\Features\Roles\RoleRepository;
use Ssdev\Headless\Support\Flash;

final class RoleMemberController
{
    public function __construct(
        private RoleRepository $roles,
        private RoleAssignmentService $assignments,
    ) {}

    public function store(AddRoleMemberRequest $request, string $role): RedirectResponse
    {
        $this->assignments->assign($request->user(), $this->roles->findOrFail($role), $request->member());

        return back()->with(Flash::SUCCESS, __('headless::roles.members.added'));
    }

    public function destroy(Request $request, string $role, string $user, UserRepository $users): RedirectResponse
    {
        $model = $this->roles->findOrFail($role);
        $member = $users->find($user);

        abort_if($member === null || ! $this->assignments->isMember($model, $member), 404);

        $this->assignments->revoke($request->user(), $model, $member);

        return back()->with(Flash::SUCCESS, __('headless::roles.members.removed'));
    }
}
