<?php

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Workbench\App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(fn () => Notification::fake());

it('returns the current user without sensitive fields', function () {
    $user = apiAs(User::factory()->create());

    $response = getJson('/api/v1/me')->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.email_verified', true)
        ->assertJsonPath('data.permissions', []);

    expect($response->getContent())->not->toContain('password')->not->toContain('remember_token');
});

it('requires a token for the account endpoints', function () {
    getJson('/api/v1/me')->assertUnauthorized();
});

it('updates the name through the api', function () {
    $user = apiAs(User::factory()->create());

    putJson('/api/v1/me', ['name' => 'Grace Hopper', 'email' => $user->email])
        ->assertOk()
        ->assertJsonPath('data.name', 'Grace Hopper')
        ->assertJsonPath('meta.email_confirmation_sent', false);
});

it('sends an email confirmation through the api', function () {
    $user = apiAs(User::factory()->create(['email' => 'old@example.com']));

    putJson('/api/v1/me', ['name' => $user->name, 'email' => 'new@example.com', 'current_password' => 'password'])
        ->assertOk()
        ->assertJsonPath('meta.email_confirmation_sent', true)
        ->assertJsonPath('data.email', 'old@example.com');
});

it('changes the password and ends web sessions', function () {
    useDatabaseSessions();
    $user = apiAs(User::factory()->create());
    DB::table('sessions')->insert(['id' => 'browser', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

    putJson('/api/v1/me/password', ['current_password' => 'password', 'password' => 'N3w-password!', 'password_confirmation' => 'N3w-password!'])
        ->assertNoContent();

    expect(Hash::check('N3w-password!', $user->fresh()->password))->toBeTrue()
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
});

it('uploads and removes the avatar', function () {
    Storage::fake('public');
    apiAs(User::factory()->create());

    expect(postJson('/api/v1/me/avatar', ['avatar' => UploadedFile::fake()->image('me.png', 100, 100)])->assertOk()->json('data.avatar_url'))->toBeString();
    deleteJson('/api/v1/me/avatar')->assertOk()->assertJsonPath('data.avatar_url', null);
});

it('deletes the account and its tokens', function () {
    $user = apiAs(User::factory()->create());
    $user->createToken('other device');

    deleteJson('/api/v1/me', ['password' => 'password'])->assertNoContent();

    expect(User::find($user->id))->toBeNull()
        ->and(PersonalAccessToken::where('tokenable_id', $user->id)->count())->toBe(0);
});

it('rejects a wrong password with a 422', function () {
    $user = apiAs(User::factory()->create());

    deleteJson('/api/v1/me', ['password' => 'nope'])->assertStatus(422)->assertJsonValidationErrors('password');

    expect(User::find($user->id))->not->toBeNull();
});

it('resends the verification email through the api', function () {
    $user = apiAs(User::factory()->unverified()->create());

    postJson('/api/v1/me/email/verification-notification')->assertAccepted();

    Notification::assertSentTo($user, VerifyEmail::class);
});
