<?php

namespace Ssdev\Headless\Features\Roles\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Ssdev\Headless\Features\Roles\Http\Requests\ListRolesRequest;
use Ssdev\Headless\Features\Roles\Http\Requests\SaveRoleRequest;
use Ssdev\Headless\Features\Roles\Http\Resources\RoleResource;
use Ssdev\Headless\Features\Roles\RoleRepository;
use Ssdev\Headless\Features\Roles\RoleService;

final class RoleController
{
    public function __construct(
        private RoleRepository $roles,
        private RoleService $service,
    ) {}

    public function index(ListRolesRequest $request): AnonymousResourceCollection
    {
        return RoleResource::collection($this->roles->paginate($request->criteria(), $request->perPage())->withQueryString());
    }

    public function store(SaveRoleRequest $request): JsonResponse
    {
        $role = $this->service->create($request->user(), $request->toData());

        return RoleResource::make($this->roles->findOrFail($role->getKey()))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $role): RoleResource
    {
        return RoleResource::make($this->roles->findOrFail($role));
    }

    public function update(SaveRoleRequest $request, string $role): RoleResource
    {
        $this->service->update($request->user(), $this->roles->findOrFail($role), $request->toData());

        return RoleResource::make($this->roles->findOrFail($role));
    }

    public function destroy(Request $request, string $role): Response
    {
        $this->service->delete($request->user(), $this->roles->findOrFail($role));

        return response()->noContent();
    }
}
