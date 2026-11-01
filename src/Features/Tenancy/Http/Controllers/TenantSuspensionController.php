<?php

namespace Ssdev\Headless\Features\Tenancy\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Ssdev\Headless\Features\Tenancy\TenantRepository;
use Ssdev\Headless\Features\Tenancy\TenantService;
use Ssdev\Headless\Support\Flash;

final class TenantSuspensionController
{
    public function __construct(
        private TenantRepository $tenants,
        private TenantService $service,
    ) {}

    public function store(Request $request, string $tenant): RedirectResponse
    {
        $this->service->suspend($request->user(), $this->tenants->findOrFail($tenant));

        return back()->with(Flash::SUCCESS, __('headless::tenancy.suspension.suspended'));
    }

    public function destroy(Request $request, string $tenant): RedirectResponse
    {
        $this->service->resume($request->user(), $this->tenants->findOrFail($tenant));

        return back()->with(Flash::SUCCESS, __('headless::tenancy.suspension.resumed'));
    }
}
