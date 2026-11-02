<?php

use Illuminate\Support\Facades\Notification;
use Ssdev\Headless\Features\Teams\TeamInvitationService;
use Ssdev\Headless\Features\Teams\TeamService;
use Workbench\App\Models\User;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('creates the account from an invitation on a single-level app', function () {
    Notification::fake();
    config(['headless.registration.enabled' => false]);
    $owner = User::factory()->create();
    $team = app(TeamService::class)->create($owner, 'Research');
    $link = app(TeamInvitationService::class)->link(app(TeamInvitationService::class)->invite($owner, $team, 'grace@example.com', null));

    $submitUrl = (string) get($link)->assertOk()->viewData('page')['props']['submitUrl'];
    post($submitUrl, ['name' => 'Grace Hopper', 'password' => 'S3cure-password!', 'password_confirmation' => 'S3cure-password!'])
        ->assertRedirect(route('headless.teams.show', $team->id));

    $grace = User::query()->where('email', 'grace@example.com')->firstOrFail();
    expect($grace->hasVerifiedEmail())->toBeTrue()
        ->and($team->members()->whereKey($grace->id)->exists())->toBeTrue();
});

it('sends a deactivated account\'s address to sign in instead of the account form', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $team = app(TeamService::class)->create($owner, 'Research');
    User::factory()->create(['email' => 'grace@example.com'])->delete();
    $link = app(TeamInvitationService::class)->link(app(TeamInvitationService::class)->invite($owner, $team, 'grace@example.com', null));

    get($link)->assertRedirect(route('login'));
});
