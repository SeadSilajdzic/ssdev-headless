<?php

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Workbench\App\Models\TenantUser;

use function Pest\Laravel\post;

beforeEach(function () {
    Notification::fake();
    config(['headless.rate_limits.password_reset' => 100]);
});

function mailedResetToken(TenantUser $user): string
{
    $token = '';

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
        $token = $notification->token;

        return true;
    });

    return $token;
}

function storedPassword(TenantUser $user): string
{
    return (string) DB::table('users')->where('id', $user->getKey())->value('password');
}

it('keeps reset links of the same email apart per tenant', function () {
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');
    $acmeAda = tenantUser($acme, ['email' => 'ada@example.com']);
    $globexAda = tenantUser($globex, ['email' => 'ada@example.com']);
    $reset = fn (Tenant $tenant, string $token) => post(tenantUrl($tenant, 'reset-password'), ['token' => $token, 'email' => 'ada@example.com', 'password' => 'N3w-password!', 'password_confirmation' => 'N3w-password!']);

    post(tenantUrl($acme, 'forgot-password'), ['email' => 'ada@example.com']);
    post(tenantUrl($globex, 'forgot-password'), ['email' => 'ada@example.com']);
    $acmeToken = mailedResetToken($acmeAda);
    $globexToken = mailedResetToken($globexAda);

    $reset($globex, $acmeToken)->assertSessionHasErrors('email');
    expect(Hash::check('password', storedPassword($globexAda)))->toBeTrue();

    $reset($acme, $acmeToken)->assertSessionHasNoErrors();
    $reset($globex, $globexToken)->assertSessionHasNoErrors();
    expect(Hash::check('N3w-password!', storedPassword($acmeAda)))->toBeTrue()
        ->and(Hash::check('N3w-password!', storedPassword($globexAda)))->toBeTrue();
});

it('keeps central reset tokens keyed by the plain email', function () {
    TenantUser::factory()->create(['email' => 'central@example.com']);

    post('http://localhost/forgot-password', ['email' => 'central@example.com']);

    expect(DB::table('password_reset_tokens')->pluck('email')->all())->toBe(['central@example.com']);
});

it('never accepts a central account\'s reset token for a tenant account', function () {
    $acme = makeTenant('acme');
    $ada = tenantUser($acme, ['email' => 'ada@example.com']);
    // A central email shaped like a tenant's token key.
    $central = TenantUser::factory()->create(['email' => "{$acme->id}|ada@example.com"]);

    post('http://localhost/forgot-password', ['email' => $central->email]);
    $centralToken = mailedResetToken($central);

    post(tenantUrl($acme, 'reset-password'), ['token' => $centralToken, 'email' => 'ada@example.com', 'password' => 'N3w-password!', 'password_confirmation' => 'N3w-password!'])
        ->assertSessionHasErrors('email');
    expect(Hash::check('password', storedPassword($ada)))->toBeTrue();
});
