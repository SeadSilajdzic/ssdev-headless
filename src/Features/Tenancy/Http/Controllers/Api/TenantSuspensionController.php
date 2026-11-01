<?php

namespace Ssdev\Headless\Features\Tenancy\Http\Controllers\Api;

use Illuminate\Http\Request;
use Ssdev\Headless\Features\Tenancy\Http\Resources\TenantResource;
use Ssdev\Headless\Features\Tenancy\TenantRepository;
use Ssdev\Headless\Features\Tenancy\TenantService;

final class TenantSuspensionController
{
    public function __construct(
        private TenantRepository $tenants,
        private TenantService $service,
    ) {}

    public function store(Request $request, string $tenant): TenantResource
    {
        $found = $this->tenants->findOrFail($tenant);
        $this->service->suspend($request->user(), $found);

        return TenantResource::make($found);
    }

    public function destroy(Request $request, string $tenant): TenantResource
    {
        $found = $this->tenants->findOrFail($tenant);
        $this->service->resume($request->user(), $found);

        return TenantResource::make($found);
    }
}
