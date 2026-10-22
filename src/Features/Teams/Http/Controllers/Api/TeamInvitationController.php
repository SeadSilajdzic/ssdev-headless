<?php

namespace Ssdev\Headless\Features\Teams\Http\Controllers\Api;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Ssdev\Headless\Features\Teams\Http\Requests\InviteTeamMemberRequest;
use Ssdev\Headless\Features\Teams\Http\Resources\TeamInvitationResource;
use Ssdev\Headless\Features\Teams\Http\Resources\TeamResource;
use Ssdev\Headless\Features\Teams\TeamInvitation;
use Ssdev\Headless\Features\Teams\TeamInvitationService;
use Ssdev\Headless\Features\Teams\TeamRepository;

final class TeamInvitationController
{
    public function __construct(
        private TeamRepository $teams,
        private TeamInvitationService $invitations,
        private Gate $gate,
    ) {}

    public function index(Request $request, string $team): AnonymousResourceCollection
    {
        $model = $this->teams->findForMember($request->user(), $team);
        $this->gate->authorize('manageMembers', $model);

        return TeamInvitationResource::collection($model->invitations()->latest('id')->get());
    }

    public function store(InviteTeamMemberRequest $request, string $team): JsonResponse
    {
        $model = $this->teams->findForMember($request->user(), $team);
        $this->gate->authorize('manageMembers', $model);

        $invitation = $this->invitations->invite($request->user(), $model, $request->email(), $request->roleId());

        return TeamInvitationResource::make($invitation)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Request $request, string $team, string $invitation): Response
    {
        $model = $this->teams->findForMember($request->user(), $team);
        $this->gate->authorize('manageMembers', $model);

        $this->invitations->cancel($model, TeamInvitation::query()->findOrFail($invitation));

        return response()->noContent();
    }

    /**
     * The mail links to the web page; an API client sends that link's expires and signature along instead.
     */
    public function accept(Request $request, string $invitation): TeamResource
    {
        $signed = Request::create(route('headless.team-invitations.show', ['invitation' => $invitation]).'?'.http_build_query($request->only(['expires', 'signature'])));

        abort_unless(URL::hasValidSignature($signed), Response::HTTP_FORBIDDEN);

        return TeamResource::make($this->invitations->accept($request->user(), $invitation));
    }
}
