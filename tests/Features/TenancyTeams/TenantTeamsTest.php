<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Ssdev\Headless\Features\Teams\Team;
use Ssdev\Headless\Features\Teams\TeamInvitationService;
use Ssdev\Headless\Features\Teams\TeamService;
use Ssdev\Headless\Features\Tenancy\TenantDeletionService;
use Workbench\App\Models\TenantUser;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('keeps teams apart per tenant and from the central level', function () {
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');
    app(TeamService::class)->create(TenantUser::factory()->create(), 'Platform');
    $acmeOwner = tenantUser($acme);
    $acme->execute(fn () => app(TeamService::class)->create($acmeOwner, 'Platform'));
    $globex->execute(fn () => app(TeamService::class)->create(tenantUser($globex), 'Platform'));
    $tenants = fn (): array => Team::query()->pluck('tenant_id')->map(fn ($tenant) => (int) $tenant)->all();

    expect($tenants())->toBe([0])
        ->and($acme->execute($tenants))->toBe([$acme->id])
        ->and($globex->execute($tenants))->toBe([$globex->id]);
});

it('answers 404 for another tenant\'s team', function () {
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');
    $acmeOwner = tenantUser($acme);
    $acmeTeam = $acme->execute(fn () => app(TeamService::class)->create($acmeOwner, 'Acme team'));
    apiAs(tenantUser($globex));

    getJson(tenantUrl($globex, "api/v1/teams/{$acmeTeam->id}"))->assertNotFound();
    putJson(tenantUrl($globex, 'api/v1/me/current-team'), ['team_id' => $acmeTeam->id])->assertNotFound();
});

it('refuses a team invitation link on another tenant\'s host', function () {
    Notification::fake();
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');
    $acmeOwner = tenantUser($acme);
    $acmeTeam = $acme->execute(fn () => app(TeamService::class)->create($acmeOwner, 'Acme team'));
    $invitation = $acme->execute(fn () => app(TeamInvitationService::class)->invite($acmeOwner, $acmeTeam, 'grace@example.com', null));
    parse_str((string) parse_url($acme->execute(fn () => app(TeamInvitationService::class)->link($invitation)), PHP_URL_QUERY), $signature);
    apiAs(tenantUser($globex, ['email' => 'grace@example.com']));

    postJson(tenantUrl($globex, "api/v1/team-invitations/{$invitation->id}/accept"), $signature)->assertForbidden();

    expect($acme->execute(fn () => $acmeTeam->members()->count()))->toBe(1);
});

it('deletes the tenant\'s teams with the tenant', function () {
    $acme = makeTenant('acme');
    $central = app(TeamService::class)->create(TenantUser::factory()->create(), 'Platform');
    $acmeOwner = tenantUser($acme);
    $acme->execute(fn () => app(TeamService::class)->create($acmeOwner, 'Acme team'));

    app(TenantDeletionService::class)->delete(TenantUser::factory()->create(), $acme);

    expect(DB::table('teams')->pluck('id')->all())->toBe([$central->id]);
});
