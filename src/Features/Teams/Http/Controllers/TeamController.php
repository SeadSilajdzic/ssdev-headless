<?php

namespace Ssdev\Headless\Features\Teams\Http\Controllers;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Ssdev\Headless\Features\Teams\Http\Requests\SaveTeamRequest;
use Ssdev\Headless\Features\Teams\Http\Resources\TeamInvitationResource;
use Ssdev\Headless\Features\Teams\Http\Resources\TeamMemberResource;
use Ssdev\Headless\Features\Teams\Http\Resources\TeamResource;
use Ssdev\Headless\Features\Teams\TeamRepository;
use Ssdev\Headless\Features\Teams\TeamRoleProps;
use Ssdev\Headless\Features\Teams\TeamService;
use Ssdev\Headless\Http\Inertia\HeadlessPage;
use Ssdev\Headless\Support\Flash;

final class TeamController
{
    public function __construct(
        private TeamRepository $teams,
        private TeamService $service,
        private Gate $gate,
        private TeamRoleProps $roleProps,
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
            ...$this->roleProps->for($model, $members, $isOwner),
        ]);
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
