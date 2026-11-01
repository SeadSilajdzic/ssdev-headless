<?php

namespace Ssdev\Headless\Features\Tenancy\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Ssdev\Headless\Features\Tenancy\Http\Requests\CreateTenantRequest;
use Ssdev\Headless\Features\Tenancy\Http\Requests\DeleteTenantRequest;
use Ssdev\Headless\Features\Tenancy\Http\Requests\ListTenantsRequest;
use Ssdev\Headless\Features\Tenancy\Http\Requests\RenameTenantRequest;
use Ssdev\Headless\Features\Tenancy\Http\Resources\TenantResource;
use Ssdev\Headless\Features\Tenancy\TenantDeletionService;
use Ssdev\Headless\Features\Tenancy\TenantRepository;
use Ssdev\Headless\Features\Tenancy\TenantService;

final class TenantController
{
    public function __construct(
        private TenantRepository $tenants,
        private TenantService $service,
    ) {}

    public function index(ListTenantsRequest $request): AnonymousResourceCollection
    {
        return TenantResource::collection($this->tenants->paginate($request->criteria(), $request->perPage())->withQueryString());
    }

    public function store(CreateTenantRequest $request): JsonResponse
    {
        $tenant = $this->service->create($request->user(), $request->name(), $request->subdomain(), $request->ownerEmail());

        return TenantResource::make($tenant)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $tenant): TenantResource
    {
        return TenantResource::make($this->tenants->findOrFail($tenant));
    }

    public function update(RenameTenantRequest $request, string $tenant): TenantResource
    {
        $found = $this->tenants->findOrFail($tenant);
        $this->service->rename($found, $request->name());

        return TenantResource::make($found);
    }

    public function destroy(DeleteTenantRequest $request, string $tenant, TenantDeletionService $deletion): Response
    {
        $deletion->delete($request->user(), $this->tenants->findOrFail($tenant));

        return response()->noContent();
    }
}
