<?php

namespace Ssdev\Headless\Features\Tenancy\Http\Controllers\Api;

use Illuminate\Http\Response;
use Ssdev\Headless\Features\Tenancy\TenantOwnerService;
use Ssdev\Headless\Features\Tenancy\TenantRepository;

final class TenantOwnerInvitationController
{
    public function store(string $tenant, TenantRepository $tenants, TenantOwnerService $owners): Response
    {
        $owners->resend($tenants->findOrFail($tenant));

        return response()->noContent();
    }
}
