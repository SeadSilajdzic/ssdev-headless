<?php

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Features\Users\Notifications\UserInvitationNotification;
use Ssdev\Headless\Features\Users\UserInvitationService;
use Ssdev\Headless\Features\Users\UserStatus;
use Ssdev\Headless\Support\Flash;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travel;

beforeEach(fn () => confirmPassword());

it('lists users to users.view holders and echoes the filters', function () {
    $viewer = permittedUser('users.view');
    User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@example.com']);

    actingAs($viewer)->get(route('headless.users.index', ['search' => 'grace', 'status' => 'active']))->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Users/Index'), false)
        ->where('users.data.0.email', 'grace@example.com')
        ->where('users.meta.total', 1)
        ->where('filters.search', 'grace')
        ->where('filters.status', 'active')
        ->where('roles', [])
        ->where('can.create', false));
});

it('ignores the role filter without the roles feature', function () {
    User::factory()->create();

    actingAs(permittedUser('users.view'))->get(route('headless.users.index', ['role' => 999]))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('users.meta.total', 2));
});

it('forbids the users pages without users.view', function () {
    $user = permittedUser();

    actingAs($user)->get(route('headless.users.index'))->assertForbidden();
    actingAs($user)->get(route('headless.users.show', User::factory()->create()->id))->assertForbidden();
});

it('shows the users navigation item only with users.view', function () {
    expect(navigationKeys(permittedUser('users.view')))->toContain('users')
        ->and(navigationKeys(permittedUser()))->not->toContain('users');
});

it('invites a user from the page', function () {
    Notification::fake();

    $response = actingAs(usersAdmin())->post(route('headless.users.store'), ['name' => 'Ada Lovelace', 'email' => 'Ada@Example.com']);

    $user = User::where('email', 'ada@example.com')->firstOrFail();
    $response->assertRedirect(route('headless.users.show', $user->id))->assertSessionHas(Flash::SUCCESS);
    Notification::assertSentTo($user, UserInvitationNotification::class);
});

it('rejects an email that is taken, also by a deleted user', function () {
    $admin = usersAdmin();
    User::factory()->create(['email' => 'taken@example.com']);
    User::factory()->create(['email' => 'gone@example.com'])->delete();

    actingAs($admin)->post(route('headless.users.store'), ['name' => 'Ada', 'email' => 'Taken@example.com'])->assertSessionHasErrors('email');
    actingAs($admin)->post(route('headless.users.store'), ['name' => 'Ada', 'email' => 'gone@example.com'])->assertSessionHasErrors('email');
});

it('shows a user without sensitive fields', function () {
    $user = User::factory()->create(['two_factor_secret' => 'top-secret-value']);

    $response = actingAs(usersAdmin())->get(route('headless.users.show', $user->id));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Users/Show'), false)
        ->where('user.email', $user->email)
        ->where('user.status', UserStatus::Active->value)
        ->missing('user.password')
        ->missing('user.remember_token')
        ->missing('user.two_factor_secret')
        ->where('can.block', true)
        ->where('isSelf', false));
    expect(json_encode($response->viewData('page')['props']))->not->toContain('top-secret-value');
    expectNoSensitiveProps($response, $user);
});

it('updates, blocks, unblocks and deletes a user from the page', function () {
    $admin = usersAdmin();
    $user = User::factory()->create();

    actingAs($admin)->put(route('headless.users.update', $user->id), ['name' => 'New Name', 'email' => $user->email])->assertSessionHas(Flash::SUCCESS);
    expect($user->fresh()->name)->toBe('New Name');

    actingAs($admin)->post(route('headless.users.block.store', $user->id))->assertSessionHas(Flash::SUCCESS);
    expect(UserStatus::of($user->fresh()))->toBe(UserStatus::Blocked);

    actingAs($admin)->delete(route('headless.users.block.destroy', $user->id))->assertSessionHas(Flash::SUCCESS);
    expect(UserStatus::of($user->fresh()))->toBe(UserStatus::Active);

    actingAs($admin)->delete(route('headless.users.destroy', $user->id))->assertRedirect(route('headless.users.index'));
    expect($user->fresh()->trashed())->toBeTrue();
});

it('explains why you cannot block yourself', function () {
    $admin = usersAdmin();

    actingAs($admin)->post(route('headless.users.block.store', $admin->id))->assertSessionHasErrors('user');
});

it('sends a password reset link, an invitation again and logs out all sessions', function () {
    Notification::fake();
    useDatabaseSessions();
    confirmPassword();
    $admin = usersAdmin();
    $user = User::factory()->create();
    insertSession('their-session', $user);

    actingAs($admin)->post(route('headless.users.password-reset.store', $user->id))->assertSessionHas(Flash::SUCCESS);
    Notification::assertSentTo($user, ResetPassword::class);

    actingAs($admin)->delete(route('headless.users.sessions.destroy', $user->id))->assertSessionHas(Flash::SUCCESS);
    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);

    $invitee = app(UserInvitationService::class)->invite($admin, 'Ada', 'ada@example.com');
    travel(61)->seconds();
    actingAs($admin)->post(route('headless.users.invitation.store', $invitee->id))->assertSessionHas(Flash::SUCCESS);
    Notification::assertSentToTimes($invitee, UserInvitationNotification::class, 2);
});

it('forbids each change without its permission', function (string $method, string $route) {
    $user = User::factory()->create(['invited_at' => now()]);

    actingAs(permittedUser('users.view'))->{$method}(route($route, $user->id), ['name' => 'X', 'email' => 'x@example.com'])->assertForbidden();
})->with([
    'update' => ['put', 'headless.users.update'],
    'block' => ['post', 'headless.users.block.store'],
    'unblock' => ['delete', 'headless.users.block.destroy'],
    'delete' => ['delete', 'headless.users.destroy'],
    'resend the invitation' => ['post', 'headless.users.invitation.store'],
    'send a reset link' => ['post', 'headless.users.password-reset.store'],
    'log out sessions' => ['delete', 'headless.users.sessions.destroy'],
]);

it('forbids inviting without users.create and changes without users.view', function () {
    $viewer = permittedUser('users.view');

    actingAs($viewer)->get(route('headless.users.create'))->assertForbidden();
    actingAs($viewer)->post(route('headless.users.store'), ['name' => 'Ada', 'email' => 'ada@example.com'])->assertForbidden();
    actingAs(permittedUser('users.block'))->post(route('headless.users.block.store', User::factory()->create()->id))->assertForbidden();
});

it('throttles invitations', function () {
    Notification::fake();
    config(['headless.rate_limits.invitations' => 2]);
    $admin = usersAdmin();

    foreach ([1, 2] as $number) {
        actingAs($admin)->post(route('headless.users.store'), ['name' => "User {$number}", 'email' => "user{$number}@example.com"])->assertRedirect();
    }

    actingAs($admin)->post(route('headless.users.store'), ['name' => 'User 3', 'email' => 'user3@example.com'])->assertTooManyRequests();
});

it('throttles password reset links', function () {
    Notification::fake();
    config(['headless.rate_limits.invitations' => 1]);
    $admin = usersAdmin();
    $user = User::factory()->create();

    actingAs($admin)->post(route('headless.users.password-reset.store', $user->id))->assertRedirect();

    actingAs($admin)->post(route('headless.users.password-reset.store', $user->id))->assertTooManyRequests();
});

it('throttles changes to a user, because a new email sends mail', function () {
    Notification::fake();
    config(['headless.rate_limits.invitations' => 1]);
    $admin = usersAdmin();
    $user = User::factory()->create();

    actingAs($admin)->put(route('headless.users.update', $user->id), ['name' => 'Ada', 'email' => 'first@example.com'])->assertRedirect();

    actingAs($admin)->put(route('headless.users.update', $user->id), ['name' => 'Ada', 'email' => 'second@example.com'])->assertTooManyRequests();
    expect($user->fresh()->email)->toBe('first@example.com');
});

it('answers not found for a deleted user', function () {
    $user = User::factory()->create();
    $user->delete();

    actingAs(usersAdmin())->get(route('headless.users.show', $user->id))->assertNotFound();
});

it('offers no roles without the roles feature', function () {
    $user = User::factory()->create();

    actingAs(usersAdmin())->get(route('headless.users.show', $user->id))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('roles', null)
        ->where('rolesUrl', null));

    expect(Route::has('headless.users.roles.update'))->toBeFalse();
});

it('asks for the password before the users pages', function () {
    session()->forget('auth.password_confirmed_at');

    actingAs(usersAdmin())->get(route('headless.users.index'))->assertRedirect(route('password.confirm'));
});

it('refuses to sign out everywhere without database sessions', function () {
    $user = User::factory()->create();

    actingAs(usersAdmin())->delete(route('headless.users.sessions.destroy', $user->id))->assertSessionHasErrors(['user' => __('headless::users.errors.sessions_unsupported')]);
});
