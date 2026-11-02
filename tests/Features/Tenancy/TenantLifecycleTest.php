<?php

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Ssdev\Headless\Features\Tenancy\Events\TenantDeleted;
use Ssdev\Headless\Features\Tenancy\Events\TenantDeleting;
use Ssdev\Headless\Features\Tenancy\Events\TenantResumed;
use Ssdev\Headless\Features\Tenancy\Events\TenantSuspended;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Features\Tenancy\TenantDeletionService;
use Ssdev\Headless\Features\Tenancy\TenantService;
use Ssdev\Headless\Http\Controllers\DashboardController;
use Ssdev\Headless\Support\Isolation;
use Ssdev\Headless\Tests\Fixtures\Permissions\FakePermission;
use Workbench\App\Models\Note;
use Workbench\App\Models\TenantUser;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\travel;

beforeEach(function () {
    Notification::fake();
    Storage::fake('public');
    useDatabaseSessions();
});

/**
 * An account with an API token, a session, a reset token and an avatar file, wherever it is created.
 */
function accountWithAccess(?Tenant $tenant, string $email): User
{
    $create = function () use ($email): User {
        $account = TenantUser::factory()->create(['email' => $email]);
        $account->createToken('cli', ['*']);
        insertSession('session-'.$account->id, $account);
        Password::broker()->createToken($account);
        Storage::disk('public')->put(app(Isolation::class)->path('avatars/'.$email.'.png'), 'photo');

        return $account;
    };

    return $tenant === null ? $create() : $tenant->execute($create);
}

it('suspends and resumes a tenant once', function () {
    Event::fake([TenantSuspended::class, TenantResumed::class]);
    $admin = TenantUser::factory()->create();
    $tenant = makeTenant('acme');
    $tenants = app(TenantService::class);

    $tenants->suspend($admin, $tenant);
    $suspendedAt = $tenant->fresh()?->suspended_at?->toDateTimeString();
    travel(5)->minutes();
    $tenants->suspend($admin, $tenant);

    expect($suspendedAt)->not->toBeNull()
        ->and($tenant->fresh()?->suspended_at?->toDateTimeString())->toBe($suspendedAt);
    Event::assertDispatchedTimes(TenantSuspended::class, 1);
    Event::assertDispatched(TenantSuspended::class, fn (TenantSuspended $event) => $event->tenant->is($tenant) && $event->actor->is($admin));

    $tenants->resume($admin, $tenant);
    $tenants->resume($admin, $tenant);

    expect($tenant->fresh()?->isSuspended())->toBeFalse()
        ->and($tenant->isSuspended())->toBeFalse();
    Event::assertDispatchedTimes(TenantResumed::class, 1);
    Event::assertDispatched(TenantResumed::class, fn (TenantResumed $event) => $event->tenant->is($tenant) && $event->actor->is($admin));
});

it('deletes a tenant with its accounts, tokens, sessions and files', function () {
    Event::fake([TenantDeleted::class]);
    usePermissions(FakePermission::class);
    $admin = TenantUser::factory()->create();
    $tenant = app(TenantService::class)->create($admin, 'Acme', 'acme', 'owner@acme.test');
    accountWithAccess($tenant, 'ada@acme.test');
    accountWithAccess($tenant, 'bob@acme.test')->delete();
    $tenantId = $tenant->id;

    app(TenantDeletionService::class)->delete($admin, $tenant);

    expect(Tenant::query()->whereKey($tenantId)->exists())->toBeFalse()
        ->and(DB::table('users')->where('tenant_id', $tenantId)->count())->toBe(0)
        ->and(DB::table('personal_access_tokens')->count())->toBe(0)
        ->and(DB::table('sessions')->count())->toBe(0)
        ->and(DB::table('model_has_permissions')->count())->toBe(0)
        ->and(DB::table('password_reset_tokens')->count())->toBe(0)
        ->and(DB::table('tenant_owner_tokens')->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
    Event::assertDispatched(TenantDeleted::class, fn (TenantDeleted $event) => $event->tenantId === $tenantId
        && $event->subdomain === 'acme'
        && $event->actor->is($admin));
});

it('leaves other tenants and central accounts alone', function () {
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');
    accountWithAccess($acme, 'ada@example.com');
    $globexAccount = accountWithAccess($globex, 'ada@example.com');
    $central = accountWithAccess(null, 'admin@example.com');

    app(TenantDeletionService::class)->delete($central, $acme);

    expect(DB::table('users')->pluck('id')->all())->toEqualCanonicalizing([$globexAccount->id, $central->id])
        ->and(DB::table('personal_access_tokens')->pluck('tokenable_id')->all())->toEqualCanonicalizing([$globexAccount->id, $central->id])
        ->and(DB::table('sessions')->pluck('user_id')->all())->toEqualCanonicalizing([$globexAccount->id, $central->id])
        ->and(DB::table('password_reset_tokens')->pluck('email')->map(fn (string $key) => Str::before($key, '|'))->all())->toEqualCanonicalizing([(string) $globex->id, 'admin@example.com'])
        ->and(Storage::disk('public')->allFiles())->toEqualCanonicalizing(["tenants/{$globex->id}/avatars/ada@example.com.png", 'avatars/admin@example.com.png'])
        ->and($globex->fresh())->not->toBeNull();
});

it('lets the application clean up on TenantDeleting', function () {
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');
    $acme->execute(fn () => Note::query()->create(['title' => 'Acme plan']));
    $globex->execute(fn () => Note::query()->create(['title' => 'Globex plan']));
    $currentWhileDeleting = null;
    Event::listen(TenantDeleting::class, function (TenantDeleting $event) use (&$currentWhileDeleting): void {
        $currentWhileDeleting = Tenant::current();
        Note::query()->delete();
    });

    app(TenantDeletionService::class)->delete(TenantUser::factory()->create(), $acme);

    expect($currentWhileDeleting?->is($acme))->toBeTrue()
        ->and(DB::table('notes')->pluck('title')->all())->toBe(['Globex plan'])
        ->and(Tenant::current())->toBeNull();
});

it('stops a deleted tenant\'s token and session working', function () {
    $acme = makeTenant('acme');
    $token = tenantUser($acme, ['email' => 'ada@example.com'])->createToken('cli', ['*'])->plainTextToken;
    post(tenantUrl($acme, 'login'), ['email' => 'ada@example.com', 'password' => 'password'])->assertRedirect();
    Auth::forgetGuards();

    app(TenantDeletionService::class)->delete(TenantUser::factory()->create(), $acme);
    // A new tenant may take the freed subdomain; nothing of the old one may reach it.
    $newAcme = makeTenant('acme');

    get(tenantUrl($newAcme, DashboardController::PATH))->assertRedirect(tenantUrl($newAcme, 'login'));
    Auth::forgetGuards();
    $this->withToken($token)->getJson(tenantUrl($newAcme, 'api/v1/me'))->assertUnauthorized();
});

it('deletes the tenant\'s directory on the avatars disk, the default disk and the listed disks', function () {
    config(['headless.tenancy.disks' => ['media']]);
    Storage::fake('local');
    Storage::fake('media');
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');

    foreach (['public', 'local', 'media'] as $disk) {
        Storage::disk($disk)->put("tenants/{$acme->id}/report.pdf", 'acme');
        Storage::disk($disk)->put("tenants/{$globex->id}/report.pdf", 'globex');
    }

    app(TenantDeletionService::class)->delete(TenantUser::factory()->create(), $acme);

    foreach (['public', 'local', 'media'] as $disk) {
        expect(Storage::disk($disk)->allFiles())->toBe(["tenants/{$globex->id}/report.pdf"]);
    }
});
