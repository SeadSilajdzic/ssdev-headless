<?php

namespace Ssdev\Headless\Features\Teams\Http\Controllers;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Features\Roles\RoleRepository;
use Ssdev\Headless\Features\Teams\Http\Requests\SaveTeamRequest;
use Ssdev\Headless\Features\Teams\Http\Resources\TeamInvitationResource;
use Ssdev\Headless\Features\Teams\Http\Resources\TeamMemberResource;
use Ssdev\Headless\Features\Teams\Http\Resources\TeamResource;
use Ssdev\Headless\Features\Teams\Team;
use Ssdev\Headless\Features\Teams\TeamRepository;
use Ssdev\Headless\Features\Teams\TeamRoleService;
use Ssdev\Headless\Features\Teams\TeamService;
use Ssdev\Headless\Http\Inertia\HeadlessPage;
use Ssdev\Headless\Support\Flash;

final class TeamController
{
    public function __construct(
        private TeamRepository $teams,
        private TeamService $service,
        private Gate $gate,
        private FeatureRegistry $features,
        private RoleRepository $roles,
        private TeamRoleService $teamRoles,
    ) {}

    public function create(): Response
    {
        return HeadlessPage::render('Teams/Create', ['storeUrl' => route('headless.teams.store')]);
    }

    public function store(SaveTeamRequest $request): RedirectResponse
    {
        $team = $this->service->create($request->user(), $request->name());

        return redirect()->route('headless.teams.show', $team->getKey())->with(Flash::SUCCESS, __('headless::teams.created'));
    }

    public function show(Request $request, string $team): Response
    {
        $model = $this->teams->findForMember($request->user(), $team);
        $isOwner = $model->isOwnedBy($request->user());
        $members = $model->members()->orderBy('name')->get();

        return HeadlessPage::render('Teams/Show', [
            'team' => TeamResource::make($model)->resolve($request),
            'isOwner' => $isOwner,
            'members' => $members->map(fn (User $member) => (new TeamMemberResource($member, $model))->resolve($request))->all(),
            'invitations' => $isOwner ? TeamInvitationResource::collection($model->invitations()->latest('id')->get())->resolve($request) : [],
            'updateUrl' => route('headless.teams.update', $model->getKey()),
            'deleteUrl' => route('headless.teams.destroy', $model->getKey()),
            'leaveUrl' => route('headless.teams.membership.destroy', $model->getKey()),
            'ownerUrl' => route('headless.teams.owner.update', $model->getKey()),
            'invitationsUrl' => route('headless.teams.invitations.store', $model->getKey()),
            'removeMemberUrls' => $members->mapWithKeys(fn (User $member) => [$member->getKey() => route('headless.teams.members.destroy', [$model->getKey(), $member->getKey()])])->all(),
            ...$this->roleProps($model, $members, $isOwner),
        ]);
    }

    /**
     * @param  Collection<int, User>  $members
     * @return array{roles: list<array{id: int, name: string}>, memberRoles: array<int|string, list<int>>, memberRolesUrls: array<int|string, string>}
     */
    private function roleProps(Team $team, Collection $members, bool $isOwner): array
    {
        if (! $this->features->isEnabled(Feature::Roles->value)) {
            return ['roles' => [], 'memberRoles' => [], 'memberRolesUrls' => []];
        }

        return [
            'roles' => $this->roles->assignableInTeams()->map(fn (Role $role): array => ['id' => (int) $role->getKey(), 'name' => $role->name])->values()->all(),
            'memberRoles' => $members->mapWithKeys(fn (User $member) => [$member->getKey() => $this->teamRoles->rolesOf($team, $member)->map(fn (Role $role): int => (int) $role->getKey())->values()->all()])->all(),
            'memberRolesUrls' => $isOwner ? $members->mapWithKeys(fn (User $member) => [$member->getKey() => route('headless.teams.members.roles.update', [$team->getKey(), $member->getKey()])])->all() : [],
        ];
    }

    public function update(SaveTeamRequest $request, string $team): RedirectResponse
    {
        $model = $this->teams->findForMember($request->user(), $team);
        $this->gate->authorize('update', $model);

        $this->service->rename($model, $request->name());

        return back()->with(Flash::SUCCESS, __('headless::teams.updated'));
    }

    public function destroy(Request $request, string $team): RedirectResponse
    {
        $model = $this->teams->findForMember($request->user(), $team);
        $this->gate->authorize('delete', $model);

        $this->service->delete($model);

        return redirect()->route('headless.dashboard')->with(Flash::SUCCESS, __('headless::teams.deleted'));
    }
}
