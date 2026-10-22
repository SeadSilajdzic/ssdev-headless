<?php

namespace Ssdev\Headless\Features\Teams\Http\Controllers\Api;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Ssdev\Headless\Features\Teams\Http\Requests\SaveTeamRequest;
use Ssdev\Headless\Features\Teams\Http\Requests\SwitchTeamRequest;
use Ssdev\Headless\Features\Teams\Http\Resources\TeamResource;
use Ssdev\Headless\Features\Teams\TeamMembershipService;
use Ssdev\Headless\Features\Teams\TeamRepository;
use Ssdev\Headless\Features\Teams\TeamService;

final class TeamController
{
    public function __construct(
        private TeamRepository $teams,
        private TeamService $service,
        private Gate $gate,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return TeamResource::collection($this->teams->forMember($request->user()));
    }

    public function store(SaveTeamRequest $request): JsonResponse
    {
        $team = $this->service->create($request->user(), $request->name());

        return TeamResource::make($team)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, string $team): TeamResource
    {
        return TeamResource::make($this->teams->findForMember($request->user(), $team));
    }

    public function update(SaveTeamRequest $request, string $team): TeamResource
    {
        $model = $this->teams->findForMember($request->user(), $team);
        $this->gate->authorize('update', $model);

        return TeamResource::make($this->service->rename($model, $request->name()));
    }

    public function destroy(Request $request, string $team): Response
    {
        $model = $this->teams->findForMember($request->user(), $team);
        $this->gate->authorize('delete', $model);

        $this->service->delete($model);

        return response()->noContent();
    }

    public function switch(SwitchTeamRequest $request, TeamMembershipService $memberships): TeamResource
    {
        $team = $this->teams->findForMember($request->user(), $request->teamId());
        $memberships->switchTo($request->user(), $team);

        return TeamResource::make($team);
    }
}
