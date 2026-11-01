<?php

namespace Ssdev\Headless\Features\Tenancy\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Ssdev\Headless\Features\Tenancy\TenantOwnerService;
use Ssdev\Headless\Features\Tenancy\TenantRepository;
use Ssdev\Headless\Support\Flash;

final class TenantOwnerInvitationController
{
    public function store(string $tenant, TenantRepository $tenants, TenantOwnerService $owners): RedirectResponse
    {
        $owners->resend($tenants->findOrFail($tenant));

        return back()->with(Flash::SUCCESS, __('headless::tenancy.owner.invitation_sent'));
    }
}
