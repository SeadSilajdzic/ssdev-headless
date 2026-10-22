<?php

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Ssdev\Headless\Features\Users\Notifications\UserInvitationNotification;
use Ssdev\Headless\Features\Users\UserInvitationService;
use Ssdev\Headless\Features\Users\UserStatus;
use Workbench\App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\travel;

it('lists users with filters', function () {
    apiAs(permittedUser('users.view'));
    User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@example.com']);
    User::factory()->create(['email' => 'blocked@example.com', 'blocked_at' => now()]);

    getJson('/api/v1/users?search=grace')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.email', 'grace@example.com');
    getJson('/api/v1/users?status=blocked')->assertOk()->assertJsonPath('data.0.email', 'blocked@example.com');
    getJson('/api/v1/users?status=nope')->assertUnprocessable()->assertJsonValidationErrors('status');
});

it('forbids users without users.view', function () {
    apiAs(permittedUser());

    getJson('/api/v1/users')->assertForbidden()->assertJsonStructure(['message']);
});

it('respects token abilities', function () {
    apiAs(usersAdmin(), ['users.view']);

    postJson('/api/v1/users', ['name' => 'Ada', 'email' => 'ada@example.com'])->assertForbidden();
});

it('invites, updates, blocks, unblocks and deletes a user', function () {
    Notification::fake();
    apiAs(usersAdmin());

    $id = postJson('/api/v1/users', ['name' => 'Ada Lovelace', 'email' => 'Ada@Example.com'])
        ->assertCreated()
        ->assertJsonPath('data.email', 'ada@example.com')
        ->assertJsonPath('data.status', UserStatus::Invited->value)
        ->json('data.id');
    Notification::assertSentTo(User::find($id), UserInvitationNotification::class);

    putJson("/api/v1/users/{$id}", ['name' => 'Ada Byron', 'email' => 'ada@example.com'])->assertOk()->assertJsonPath('data.name', 'Ada Byron');
    postJson("/api/v1/users/{$id}/block")->assertOk()->assertJsonPath('data.status', UserStatus::Blocked->value);
    deleteJson("/api/v1/users/{$id}/block")->assertOk()->assertJsonPath('data.status', UserStatus::Invited->value);
    deleteJson("/api/v1/users/{$id}")->assertNoContent();

    expect(User::withTrashed()->find($id)->trashed())->toBeTrue();
});

it('rejects blocking or deleting yourself', function () {
    $admin = apiAs(usersAdmin());

    postJson("/api/v1/users/{$admin->getKey()}/block")->assertUnprocessable()->assertJsonValidationErrors('user');
    deleteJson("/api/v1/users/{$admin->getKey()}")->assertUnprocessable()->assertJsonValidationErrors('user');
});

it('answers 404 for a missing or deleted user', function () {
    apiAs(usersAdmin());
    $deleted = User::factory()->create();
    $deleted->delete();

    getJson('/api/v1/users/999')->assertNotFound()->assertJsonStructure(['message']);
    getJson("/api/v1/users/{$deleted->getKey()}")->assertNotFound();
});

it('sends a password reset link, an invitation and logs out sessions', function () {
    Notification::fake();
    useDatabaseSessions();
    $admin = apiAs(usersAdmin());
    $user = User::factory()->create();
    insertSession('their-session', $user);

    postJson("/api/v1/users/{$user->getKey()}/password-reset")->assertNoContent();
    Notification::assertSentTo($user, ResetPassword::class);

    deleteJson("/api/v1/users/{$user->getKey()}/sessions")->assertNoContent();
    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);

    $invitee = app(UserInvitationService::class)->invite($admin, 'Ada', 'ada@example.com');
    travel(61)->seconds();
    postJson("/api/v1/users/{$invitee->getKey()}/invitation")->assertNoContent();
    Notification::assertSentToTimes($invitee, UserInvitationNotification::class, 2);
});

it('has no roles endpoints without the roles feature', function () {
    apiAs(usersAdmin());

    getJson('/api/v1/users/'.User::factory()->create()->getKey().'/roles')->assertNotFound();
});
