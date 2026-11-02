<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Features\Teams\Team;
use Ssdev\Headless\Features\Teams\TeamInvitationService;
use Ssdev\Headless\Features\Teams\TeamService;
use Ssdev\Headless\Features\Tenancy\Tenant;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\travel;

beforeEach(fn () => Notification::fake());

/**
 * @return array{0: Team, 1: string}
 */
function tenantTeamInvitation(Tenant $tenant, string $email): array
{
    $owner = tenantUser($tenant);

    return $tenant->execute(function () use ($owner, $email): array {
        $team = app(TeamService::class)->create($owner, 'Research');
        $invitation = app(TeamInvitationService::class)->invite($owner, $team, $email, null);

        return [$team, app(TeamInvitationService::class)->link($invitation)];
    });
}

function invitationAccountUrl(TestResponse $page): string
{
    return (string) $page->viewData('page')['props']['submitUrl'];
}

function newAccount(): array
{
    return ['name' => 'Grace Hopper', 'password' => 'S3cure-password!', 'password_confirmation' => 'S3cure-password!'];
}

it('creates the account in the tenant and joins the team', function () {
    $acme = makeTenant('acme');
    [$team, $link] = tenantTeamInvitation($acme, 'grace@example.com');

    $page = get($link)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Teams/InvitationAccount'), false)
        ->where('email', 'grace@example.com')
        ->where('team.name', 'Research'));
    post(invitationAccountUrl($page), newAccount())->assertRedirect(tenantUrl($acme, "teams/{$team->id}"));

    $grace = DB::table('users')->where('email', 'grace@example.com')->first();
    expect($grace->tenant_id)->toBe($acme->id)
        ->and($grace->email_verified_at)->not->toBeNull()
        ->and($acme->execute(fn () => $team->members()->whereKey($grace->id)->exists()))->toBeTrue()
        ->and(Auth::id())->toBe($grace->id);
});

it('sends an invitee with an account to sign in', function () {
    $acme = makeTenant('acme');
    [, $link] = tenantTeamInvitation($acme, 'grace@example.com');
    tenantUser($acme, ['email' => 'grace@example.com']);

    get($link)->assertRedirect(tenantUrl($acme, 'login'));
});

it('works once', function () {
    $acme = makeTenant('acme');
    [, $link] = tenantTeamInvitation($acme, 'grace@example.com');
    $submitUrl = invitationAccountUrl(get($link));

    post($submitUrl, newAccount())->assertRedirect();
    Auth::logout();

    post($submitUrl, newAccount())->assertNotFound();
    expect(DB::table('users')->where('email', 'grace@example.com')->count())->toBe(1);
});

it('refuses an expired link', function () {
    $acme = makeTenant('acme');
    [, $link] = tenantTeamInvitation($acme, 'grace@example.com');
    travel((int) config('headless.teams.invitation_expire_minutes') + 1)->minutes();

    get($link)->assertForbidden();
});
