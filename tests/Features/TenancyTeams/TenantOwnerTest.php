<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Ssdev\Headless\Features\Roles\RolePermission;
use Ssdev\Headless\Features\Teams\TeamContext;
use Ssdev\Headless\Features\Teams\TeamService;
use Ssdev\Headless\Features\Tenancy\TenantOwnerService;
use Ssdev\Headless\Features\Tenancy\TenantService;
use Workbench\App\Models\TenantUser;

use function Pest\Laravel\actingAs;

it('gives the owner global permissions, not in the creating admin\'s team', function () {
    Notification::fake();
    $admin = TenantUser::factory()->create();
    app(TeamService::class)->create($admin, 'Platform');
    actingAs($admin->fresh());

    $tenant = app(TenantService::class)->create($admin, 'Acme', 'acme', 'owner@acme.test');
    $owner = app(TenantOwnerService::class)->owner($tenant) ?? throw new LogicException('No owner.');

    expect($admin->fresh()?->getAttribute('current_team_id'))->not->toBeNull()
        ->and(DB::table('model_has_permissions')->where('model_id', $owner->getKey())->distinct()->pluck('team_id')->all())->toBe([TeamContext::GLOBAL])
        ->and($tenant->execute(function () use ($owner): bool {
            actingAs($owner);

            return $owner->can(RolePermission::Manage->value);
        }))->toBeTrue();
});
