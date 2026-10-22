<?php

namespace Ssdev\Headless\Features\Users\Http\Controllers;

use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Features\Roles\RolePermission;
use Ssdev\Headless\Features\Roles\RoleRepository;
use Ssdev\Headless\Features\Roles\RoleService;
use Ssdev\Headless\Features\Users\AccessRevoker;
use Ssdev\Headless\Features\Users\Http\Requests\ListUsersRequest;
use Ssdev\Headless\Features\Users\Http\Requests\SaveUserRequest;
use Ssdev\Headless\Features\Users\Http\Resources\ManagedUserResource;
use Ssdev\Headless\Features\Users\ManagedUserRepository;
use Ssdev\Headless\Features\Users\UserInvitationService;
use Ssdev\Headless\Features\Users\UserPermission;
use Ssdev\Headless\Features\Users\UserService;
use Ssdev\Headless\Features\Users\UserStatus;
use Ssdev\Headless\Http\Inertia\HeadlessPage;
use Ssdev\Headless\Support\Flash;

final class UserController
{
    public function __construct(
        private ManagedUserRepository $users,
        private UserService $service,
        private UserInvitationService $invitations,
        private FeatureRegistry $features,
        private RoleRepository $roles,
        private RoleService $roleService,
        private AccessRevoker $access,
    ) {}

    public function index(ListUsersRequest $request): Response
    {
        $users = $this->users->paginate($request->criteria(), $request->perPage())->withQueryString();

        return HeadlessPage::render('Users/Index', [
            'users' => ManagedUserResource::collection($users)->response()->getData(true),
            'filters' => $request->filters(),
            'statuses' => array_column(UserStatus::cases(), 'value'),
            'roles' => $this->roleOptions($request->user()),
            'can' => [
                'create' => $request->user()->can(UserPermission::Create->value),
                'update' => $request->user()->can(UserPermission::Update->value),
            ],
            'indexUrl' => route('headless.users.index'),
            'createUrl' => route('headless.users.create'),
        ]);
    }

    public function create(): Response
    {
        return HeadlessPage::render('Users/Create', [
            'indexUrl' => route('headless.users.index'),
            'storeUrl' => route('headless.users.store'),
        ]);
    }

    public function store(SaveUserRequest $request): RedirectResponse
    {
        $user = $this->invitations->invite($request->user(), $request->name(), $request->email());

        return redirect()->route('headless.users.show', $user->getKey())->with(Flash::SUCCESS, __('headless::users.invited'));
    }

    public function show(Request $request, string $user): Response
    {
        $managed = $this->users->findOrFail($user);
        $actor = $request->user();
        $rolesEnabled = $this->features->isEnabled(Feature::Roles->value);

        return HeadlessPage::render('Users/Show', [
            'user' => ManagedUserResource::make($managed)->resolve($request),
            'isSelf' => $actor->is($managed),
            'sessionsSupported' => $this->access->supportsSigningOut(),
            'can' => [
                'update' => $actor->can(UserPermission::Update->value),
                'block' => $actor->can(UserPermission::Block->value),
                'delete' => $actor->can(UserPermission::Delete->value),
                'invite' => $actor->can(UserPermission::Create->value),
                'manageRoles' => $rolesEnabled && $actor->can(RolePermission::Manage->value),
            ],
            'roles' => $rolesEnabled && $actor->can(RolePermission::View->value) ? $this->roleChoices($actor, $managed) : null,
            'rolesUrl' => $rolesEnabled ? route('headless.users.roles.update', $managed->getKey()) : null,
            'indexUrl' => route('headless.users.index'),
            'updateUrl' => route('headless.users.update', $managed->getKey()),
            'blockUrl' => route('headless.users.block.store', $managed->getKey()),
            'invitationUrl' => route('headless.users.invitation.store', $managed->getKey()),
            'passwordResetUrl' => route('headless.users.password-reset.store', $managed->getKey()),
            'sessionsUrl' => route('headless.users.sessions.destroy', $managed->getKey()),
            'deleteUrl' => route('headless.users.destroy', $managed->getKey()),
        ]);
    }

    public function update(SaveUserRequest $request, string $user): RedirectResponse
    {
        $this->service->update($this->users->findOrFail($user), $request->name(), $request->email());

        return back()->with(Flash::SUCCESS, __('headless::users.updated'));
    }

    public function destroy(Request $request, string $user): RedirectResponse
    {
        $this->service->delete($request->user(), $this->users->findOrFail($user));

        return redirect()->route('headless.users.index')->with(Flash::SUCCESS, __('headless::users.deleted'));
    }

    /**
     * A role is manageable only for someone who may manage roles and holds every permission in it.
     *
     * @return list<array{id: int|string, name: string, assigned: bool, manageable: bool}>
     */
    private function roleChoices(User $actor, User $user): array
    {
        $assigned = $this->roles->assignedTo($user)->map(fn (Role $role) => $role->getKey())->all();
        $managesRoles = $actor->can(RolePermission::Manage->value);

        return $this->roles->all()->load('permissions')->map(fn (Role $role): array => [
            'id' => $role->getKey(),
            'name' => $role->name,
            'assigned' => in_array($role->getKey(), $assigned, true),
            'manageable' => $managesRoles && $this->roleService->canManage($actor, $role),
        ])->values()->all();
    }

    /**
     * @return list<array{id: int|string, name: string}>
     */
    private function roleOptions(User $actor): array
    {
        if (! $this->features->isEnabled(Feature::Roles->value) || ! $actor->can(RolePermission::View->value)) {
            return [];
        }

        return $this->roles->all()->map(fn (Role $role): array => ['id' => $role->getKey(), 'name' => $role->name])->values()->all();
    }
}
