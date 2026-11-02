<?php

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Features\Tenancy\TenancyModule;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Features\Tenancy\TenantContext;
use Workbench\App\Models\TenantUser;

it('registers tenancy as a selectable feature', function () {
    $module = new TenancyModule;

    expect(app(FeatureRegistry::class)->isEnabled(Feature::Tenancy->value))->toBeTrue()
        ->and($module->composerPackages())->toBe(['spatie/laravel-multitenancy' => '^4.2'])
        ->and(array_keys($module->migrations()))->toBe([TenancyModule::TENANTS_MIGRATION, TenancyModule::USERS_MIGRATION, TenancyModule::OWNER_TOKENS_MIGRATION, TenancyModule::ROLES_MIGRATION])
        ->and(array_filter($module->migrations(), 'is_file'))->toHaveCount(4);
});

it('makes a tenant current', function () {
    $tenant = makeTenant('acme');

    $tenant->makeCurrent();
    expect(TenantContext::currentKey())->toBe($tenant->id);

    Tenant::forgetCurrent();
    expect(TenantContext::currentKey())->toBe(TenantContext::CENTRAL);
});

it('allows an email once per tenant', function () {
    $account = fn (int $tenant): array => ['tenant_id' => $tenant, 'name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()];

    DB::table('users')->insert($account(1));
    DB::table('users')->insert($account(2));

    expect(fn () => DB::table('users')->insert($account(1)))->toThrow(UniqueConstraintViolationException::class);
});

it('keeps accounts central by default', function () {
    expect(TenantUser::factory()->create()->fresh()->tenant_id)->toBe(TenantContext::CENTRAL);
});

it('refuses to roll the users migration back while an email is used in two tenants', function () {
    tenantUser(makeTenant('acme'), ['email' => 'ada@example.com']);
    tenantUser(makeTenant('globex'), ['email' => 'ada@example.com']);
    $migration = require dirname(__DIR__, 3).'/database/migrations/tenancy/add_tenant_to_users_table.php';

    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'more than one tenant')
        ->and(Schema::hasColumn('users', 'tenant_id'))->toBeTrue();
});
