<?php

use Illuminate\Support\Facades\Notification;
use Ssdev\Headless\Features\Teams\Notifications\TeamInvitationNotification;
use Ssdev\Headless\Features\Teams\TeamInvitationService;
use Ssdev\Headless\Features\Teams\TeamRoleService;
use Ssdev\Headless\Features\Teams\TeamService;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(fn () => Notification::fake());

it('joins with the invited role when roles are on', function () {
    roleManager();
    $owner = User::factory()->create();
    $team = app(TeamService::class)->create($owner, 'Research');
    $editor = makeRole('Editor', ['reports.view']);
    $ada = User::factory()->create(['email' => 'ada@example.com']);

    app(TeamInvitationService::class)->invite($owner, $team, 'ada@example.com', $editor->id);

    $link = '';
    Notification::assertSentOnDemand(TeamInvitationNotification::class, function (TeamInvitationNotification $notification) use (&$link) {
        $link = $notification->url;

        return true;
    });
    actingAs($ada)->post($link)->assertRedirect();

    expect(app(TeamRoleService::class)->rolesOf($team, $ada)->pluck('name')->all())->toBe(['Editor']);
});

it('refuses a role that cannot be given inside a team', function () {
    roleManager();
    $owner = User::factory()->create();
    $team = app(TeamService::class)->create($owner, 'Research');
    $admins = makeRole('Admins', ['roles.view']);

    expect(fn () => app(TeamInvitationService::class)->invite($owner, $team, 'ada@example.com', $admins->id))->toThrow(failsOn('role'));
});
