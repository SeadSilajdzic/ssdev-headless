<?php

namespace Ssdev\Headless\Features\Teams\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Features\Teams\Http\Requests\SyncTeamMemberRolesRequest;
use Ssdev\Headless\Features\Teams\TeamRepository;
use Ssdev\Headless\Features\Teams\TeamRoleService;

final class TeamMemberRoleController
{
    public function update(SyncTeamMemberRolesRequest $request, string $team, string $user, TeamRepository $teams, TeamRoleService $roles): JsonResponse
    {
        $model = $teams->findForMember($request->user(), $team);
        $member = $model->members()->findOrFail($user);

        $roles->sync($request->user(), $model, $member, $request->roleIds());

        return response()->json(['data' => $roles->rolesOf($model, $member)->map(fn (Role $role): array => ['id' => $role->getKey(), 'name' => $role->name])->values()->all()]);
    }
}
