<?php

namespace Ssdev\Headless\Features\Teams\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Ssdev\Headless\Features\Teams\Http\Requests\SyncTeamMemberRolesRequest;
use Ssdev\Headless\Features\Teams\TeamRepository;
use Ssdev\Headless\Features\Teams\TeamRoleService;
use Ssdev\Headless\Support\Flash;

final class TeamMemberRoleController
{
    public function update(SyncTeamMemberRolesRequest $request, string $team, string $user, TeamRepository $teams, TeamRoleService $roles): RedirectResponse
    {
        $model = $teams->findForMember($request->user(), $team);

        $roles->sync($request->user(), $model, $model->members()->findOrFail($user), $request->roleIds());

        return back()->with(Flash::SUCCESS, __('headless::teams.members.roles_saved'));
    }
}
