<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Support\Isolation;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

it('keeps cache entries apart per tenant', function () {
    // The array store has no prefix, so the test uses one that does.
    config(['cache.default' => 'database']);
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');

    $acme->execute(fn () => Cache::put('plan', 'acme'));

    expect($globex->execute(fn () => Cache::get('plan')))->toBeNull()
        ->and(Cache::get('plan'))->toBeNull()
        ->and($acme->execute(fn () => Cache::get('plan')))->toBe('acme');
});

it('counts sign-in attempts per tenant', function () {
    config(['headless.rate_limits.login_per_ip' => 2]);
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');
    tenantUser($acme, ['email' => 'ada@example.com']);
    tenantUser($globex, ['email' => 'ada@example.com']);
    $signIn = fn (Tenant $tenant, string $password) => post(tenantUrl($tenant, 'login'), ['email' => 'ada@example.com', 'password' => $password]);

    $signIn($acme, 'nope');
    $signIn($acme, 'nope');

    $signIn($acme, 'password')->assertSessionHasErrors('email');
    $signIn($globex, 'password')->assertSessionHasNoErrors();
});

it('stores avatars under the tenant\'s directory', function () {
    Storage::fake('public');
    $acme = makeTenant('acme');
    $ada = tenantUser($acme);

    actingAs($ada)->post(tenantUrl($acme, 'settings/profile/avatar'), ['avatar' => UploadedFile::fake()->image('ada.png', 200, 200)])->assertSessionHasNoErrors();

    expect(DB::table('users')->where('id', $ada->getKey())->value('avatar_path'))->toStartWith("tenants/{$acme->id}/avatars/");
});

it('leaves central paths and keys unchanged', function () {
    $isolation = app(Isolation::class);
    $acme = makeTenant('acme');

    expect([$isolation->path('avatars'), $isolation->key('login|ip')])->toBe(['avatars', 'login|ip'])
        ->and($acme->execute(fn () => [$isolation->path('avatars'), $isolation->key('login|ip')]))->toBe(["tenants/{$acme->id}/avatars", "tenant:{$acme->id}|login|ip"]);
});
