<?php

namespace Ssdev\Headless\Features\Roles\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Account\PersonalAccessTokenService;
use Ssdev\Headless\Features\Roles\Http\Requests\ListRolesRequest;
use Ssdev\Headless\Features\Roles\Http\Requests\SaveRoleRequest;
use Ssdev\Headless\Features\Roles\Http\Resources\RoleResource;
use Ssdev\Headless\Features\Roles\RolePermission;
use Ssdev\Headless\Features\Roles\RoleRepository;
use Ssdev\Headless\Features\Roles\RoleService;
use Ssdev\Headless\Http\Inertia\HeadlessPage;
use Ssdev\Headless\Support\Flash;

final class RoleController
{
    public function __construct(
        private RoleRepository $roles,
        private RoleService $service,
        private PersonalAccessTokenService $permissions,
    ) {}

    public function index(ListRolesRequest $request): Response
    {
        $roles = $this->roles->paginate($request->criteria(), $request->perPage())->withQueryString();

        return HeadlessPage::render('Roles/Index', [
            'roles' => RoleResource::collection($roles)->response()->getData(true),
            'filters' => $request->filters(),
            'can' => ['manage' => $request->user()->can(RolePermission::Manage->value)],
            'indexUrl' => route('headless.roles.index'),
            'createUrl' => route('headless.roles.create'),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->editPage($request, null);
    }

    public function store(SaveRoleRequest $request): RedirectResponse
    {
        $role = $this->service->create($request->user(), $request->toData());

        return redirect()->route('headless.roles.edit', $role->getKey())->with(Flash::SUCCESS, __('headless::roles.created'));
    }

    public function edit(Request $request, string $role): Response
    {
        return $this->editPage($request, $this->roles->findOrFail($role));
    }

    public function update(SaveRoleRequest $request, string $role): RedirectResponse
    {
        $this->service->update($request->user(), $this->roles->findOrFail($role), $request->toData());

        return back()->with(Flash::SUCCESS, __('headless::roles.updated'));
    }

    public function destroy(Request $request, string $role): RedirectResponse
    {
        $this->service->delete($request->user(), $this->roles->findOrFail($role));

        return redirect()->route('headless.roles.index')->with(Flash::SUCCESS, __('headless::roles.deleted'));
    }

    private function editPage(Request $request, ?Role $role): Response
    {
        $canManage = $request->user()->can(RolePermission::Manage->value);

        return HeadlessPage::render('Roles/Edit', [
            'role' => $role === null ? null : RoleResource::make($role)->resolve($request),
            // Only what the actor holds can go into a role; viewers see the role's own list instead.
            'permissions' => $canManage ? $this->permissions->grantableGroups($request->user()) : [],
            'can' => ['manage' => $canManage],
            'indexUrl' => route('headless.roles.index'),
            'saveUrl' => $role === null ? route('headless.roles.store') : route('headless.roles.update', $role->getKey()),
            'deleteUrl' => $role === null ? null : route('headless.roles.destroy', $role->getKey()),
        ]);
    }
}
