<?php

namespace Ssdev\Headless\Features\Roles\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ssdev\Headless\Account\PersonalAccessTokenService;
use Ssdev\Headless\Features\Roles\RolePermission;

final class PermissionController
{
    public function index(Request $request, PersonalAccessTokenService $permissions): JsonResponse
    {
        abort_unless($request->user()->canAny([RolePermission::View->value, RolePermission::Manage->value]), 403);

        return response()->json(['data' => $permissions->grantableGroups($request->user())]);
    }
}
