<?php

namespace Ssdev\Headless\Features\Tenancy\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Ssdev\Headless\Features\Tenancy\Http\Requests\CreateTenantRequest;
use Ssdev\Headless\Features\Tenancy\Http\Requests\DeleteTenantRequest;
use Ssdev\Headless\Features\Tenancy\Http\Requests\ListTenantsRequest;
use Ssdev\Headless\Features\Tenancy\Http\Requests\RenameTenantRequest;
use Ssdev\Headless\Features\Tenancy\Http\Resources\TenantResource;
use Ssdev\Headless\Features\Tenancy\TenantDeletionService;
use Ssdev\Headless\Features\Tenancy\TenantHosts;
use Ssdev\Headless\Features\Tenancy\TenantPermission;
use Ssdev\Headless\Features\Tenancy\TenantRepository;
use Ssdev\Headless\Features\Tenancy\TenantService;
use Ssdev\Headless\Features\Tenancy\TenantStatus;
use Ssdev\Headless\Http\Inertia\HeadlessPage;
use Ssdev\Headless\Support\Flash;

final class TenantController
{
    public function __construct(
        private TenantRepository $tenants,
        private TenantService $service,
        private TenantDeletionService $deletion,
        private TenantHosts $hosts,
    ) {}

    public function index(ListTenantsRequest $request): Response
    {
        $tenants = $this->tenants->paginate($request->criteria(), $request->perPage())->withQueryString();

        return HeadlessPage::render('Tenants/Index', [
            'tenants' => TenantResource::collection($tenants)->response()->getData(true),
            'filters' => $request->filters(),
            'statuses' => array_column(TenantStatus::cases(), 'value'),
            'can' => ['manage' => $request->user()->can(TenantPermission::Manage->value)],
            'indexUrl' => route('headless.tenants.index'),
            'createUrl' => route('headless.tenants.create'),
        ]);
    }

    public function create(): Response
    {
        return HeadlessPage::render('Tenants/Create', [
            'baseDomain' => $this->hosts->baseDomain(),
            'indexUrl' => route('headless.tenants.index'),
            'storeUrl' => route('headless.tenants.store'),
        ]);
    }

    public function store(CreateTenantRequest $request): RedirectResponse
    {
        $tenant = $this->service->create($request->user(), $request->name(), $request->subdomain(), $request->ownerEmail());

        return redirect()->route('headless.tenants.show', $tenant->getKey())->with(Flash::SUCCESS, __('headless::tenancy.created'));
    }

    public function show(Request $request, string $tenant): Response
    {
        $found = $this->tenants->findOrFail($tenant);

        return HeadlessPage::render('Tenants/Show', [
            'tenant' => TenantResource::make($found)->resolve($request),
            'can' => ['manage' => $request->user()->can(TenantPermission::Manage->value)],
            'indexUrl' => route('headless.tenants.index'),
            'updateUrl' => route('headless.tenants.update', $found->getKey()),
            'suspensionUrl' => route('headless.tenants.suspension.store', $found->getKey()),
            'ownerInvitationUrl' => route('headless.tenants.owner-invitation.store', $found->getKey()),
            'deleteUrl' => route('headless.tenants.destroy', $found->getKey()),
        ]);
    }

    public function update(RenameTenantRequest $request, string $tenant): RedirectResponse
    {
        $this->service->rename($this->tenants->findOrFail($tenant), $request->name());

        return back()->with(Flash::SUCCESS, __('headless::tenancy.renamed'));
    }

    public function destroy(DeleteTenantRequest $request, string $tenant): RedirectResponse
    {
        $this->deletion->delete($request->user(), $this->tenants->findOrFail($tenant));

        return redirect()->route('headless.tenants.index')->with(Flash::SUCCESS, __('headless::tenancy.deleted'));
    }
}
