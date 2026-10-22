<?php

namespace Ssdev\Headless\Features\Teams\Http\Controllers\Api;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ssdev\Headless\Features\Teams\Http\Requests\TransferTeamRequest;
use Ssdev\Headless\Features\Teams\Http\Resources\TeamMemberResource;
use Ssdev\Headless\Features\Teams\Http\Resources\TeamResource;
use Ssdev\Headless\Features\Teams\TeamMembershipService;
use Ssdev\Headless\Features\Teams\TeamRepository;

final class TeamMemberController
{
    public function __construct(
        private TeamRepository $teams,
        private TeamMembershipService $memberships,
        private Gate $gate,
    ) {}

    public function index(Request $request, string $team): JsonResponse
    {
        $model = $this->teams->findForMember($request->user(), $team);
        $members = $model->members()->orderBy('name')->get();

        return response()->json(['data' => $members->map(fn (User $member) => (new TeamMemberResource($member, $model))->resolve($request))->all()]);
    }

    public function destroy(Request $request, string $team, string $user): Response
    {
        $model = $this->teams->findForMember($request->user(), $team);
        $this->gate->authorize('manageMembers', $model);

        $this->memberships->remove($model, $model->members()->findOrFail($user));

        return response()->noContent();
    }

    public function leave(Request $request, string $team): Response
    {
        $this->memberships->leave($request->user(), $this->teams->findForMember($request->user(), $team));

        return response()->noContent();
    }

    public function transfer(TransferTeamRequest $request, string $team): TeamResource
    {
        $model = $this->teams->findForMember($request->user(), $team);
        $this->gate->authorize('manageMembers', $model);

        $this->memberships->transfer($model, $model->members()->findOrFail($request->userId()));

        return TeamResource::make($model);
    }
}
