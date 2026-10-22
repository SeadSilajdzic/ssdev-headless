<?php

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Features\Tenancy\Notifications\TenantOwnerInvitation;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Features\Tenancy\TenantOwnerService;
use Ssdev\Headless\Features\Tenancy\TenantService;
use Workbench\App\Models\TenantUser;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\travel;

beforeEach(fn () => Notification::fake());

/**
 * @return array{0: Tenant, 1: User}
 */
function tenantWithOwner(string $subdomain = 'acme'): array
{
    $tenant = app(TenantService::class)->create(TenantUser::factory()->create(), ucfirst($subdomain), $subdomain, "owner@{$subdomain}.test");

    return [$tenant, app(TenantOwnerService::class)->owner($tenant) ?? throw new LogicException('No owner.')];
}

/**
 * The link of the last invitation mailed to the owner.
 */
function ownerInvitationUrl(User $owner): string
{
    $url = '';

    Notification::assertSentTo($owner, TenantOwnerInvitation::class, function (TenantOwnerInvitation $notification) use (&$url): bool {
        $url = $notification->url;

        return true;
    });

    return $url;
}

/**
 * @return array<string, string>
 */
function ownerForm(string $url, string $email): array
{
    return ['token' => basename((string) parse_url($url, PHP_URL_PATH)), 'email' => $email, 'name' => 'Ada Lovelace', 'password' => 'S3cure-password!', 'password_confirmation' => 'S3cure-password!'];
}

function storedOwner(User $owner): object
{
    return DB::table('users')->where('id', $owner->getKey())->first() ?? throw new LogicException('No owner row.');
}

it('mails the owner a link on the tenant\'s host', function () {
    [, $owner] = tenantWithOwner();

    expect(ownerInvitationUrl($owner))->toStartWith('http://acme.localhost/owner-invitation/');
});

it('lets the owner choose a name and password', function () {
    [$tenant, $owner] = tenantWithOwner();
    $url = ownerInvitationUrl($owner);

    get($url)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Tenancy/AcceptOwnerInvitation'), false)
        ->where('email', 'owner@acme.test'));
    post(tenantUrl($tenant, 'owner-invitation'), ownerForm($url, 'owner@acme.test'))->assertRedirect(tenantUrl($tenant, 'login'));

    $row = storedOwner($owner);
    expect($row->name)->toBe('Ada Lovelace')
        ->and($row->email_verified_at)->not->toBeNull()
        ->and(Hash::check('S3cure-password!', $row->password))->toBeTrue();
});

it('works once', function () {
    [$tenant, $owner] = tenantWithOwner();
    $form = ownerForm(ownerInvitationUrl($owner), 'owner@acme.test');

    post(tenantUrl($tenant, 'owner-invitation'), $form)->assertSessionHasNoErrors();
    post(tenantUrl($tenant, 'owner-invitation'), [...$form, 'password' => 'An0ther-password!', 'password_confirmation' => 'An0ther-password!'])->assertSessionHasErrors('email');

    expect(Hash::check('S3cure-password!', storedOwner($owner)->password))->toBeTrue();
});

it('refuses the link on another tenant\'s host', function () {
    [, $owner] = tenantWithOwner();
    $other = makeTenant('globex');

    post(tenantUrl($other, 'owner-invitation'), ownerForm(ownerInvitationUrl($owner), 'owner@acme.test'))->assertSessionHasErrors('email');

    expect(storedOwner($owner)->email_verified_at)->toBeNull();
});

it('refuses an expired link', function () {
    [$tenant, $owner] = tenantWithOwner();
    $form = ownerForm(ownerInvitationUrl($owner), 'owner@acme.test');

    travel((int) config('headless.tenancy.owner_invitation_expire_minutes') + 1)->minutes();

    post(tenantUrl($tenant, 'owner-invitation'), $form)->assertSessionHasErrors('email');
});

it('sends the invitation again and the old link stops working', function () {
    [$tenant, $owner] = tenantWithOwner();
    $old = ownerForm(ownerInvitationUrl($owner), 'owner@acme.test');

    travel(2)->minutes();
    app(TenantOwnerService::class)->resend($tenant);
    $new = ownerForm(ownerInvitationUrl($owner), 'owner@acme.test');

    post(tenantUrl($tenant, 'owner-invitation'), $old)->assertSessionHasErrors('email');
    post(tenantUrl($tenant, 'owner-invitation'), $new)->assertSessionHasNoErrors();
});

it('refuses resending once the owner accepted', function () {
    [$tenant, $owner] = tenantWithOwner();

    post(tenantUrl($tenant, 'owner-invitation'), ownerForm(ownerInvitationUrl($owner), 'owner@acme.test'))->assertSessionHasNoErrors();

    expect(fn () => app(TenantOwnerService::class)->resend($tenant))->toThrow(failsOn('owner'));
});
