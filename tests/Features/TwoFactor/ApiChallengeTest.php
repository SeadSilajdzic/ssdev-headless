<?php

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Workbench\App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

function tokenSignIn(User $user): TestResponse
{
    return postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'password', 'device_name' => 'cli']);
}

function challengeToken(User $user): string
{
    return tokenSignIn($user)->assertStatus(202)->json('data.challenge_token');
}

/**
 * @param  array<string, string>  $answer
 */
function answerChallenge(string $challenge, array $answer): TestResponse
{
    return postJson('/api/v1/auth/two-factor-challenge', ['challenge_token' => $challenge, ...$answer]);
}

it('answers token sign-in with a challenge when two-factor authentication is on', function () {
    Event::fake([Login::class, TwoFactorAuthenticationChallenged::class]);
    $user = enabledTwoFactorUser();

    tokenSignIn($user)
        ->assertStatus(202)
        ->assertJsonPath('data.two_factor', true)
        ->assertJsonStructure(['data' => ['challenge_token', 'expires_at']]);

    expect($user->tokens()->count())->toBe(0);
    Event::assertNotDispatched(Login::class);
    Event::assertDispatched(TwoFactorAuthenticationChallenged::class);
});

it('issues a token for a valid code once', function () {
    Event::fake([Login::class, ValidTwoFactorAuthenticationCodeProvided::class]);
    $user = enabledTwoFactorUser();
    $challenge = challengeToken($user);

    $token = answerChallenge($challenge, ['code' => currentOtp($user)])->assertCreated()->json('data.token');

    getJson('/api/v1/me', ['Authorization' => "Bearer {$token}"])->assertOk();
    expect($user->tokens()->sole()->name)->toBe('cli');
    Event::assertDispatched(Login::class);
    Event::assertDispatched(ValidTwoFactorAuthenticationCodeProvided::class);

    answerChallenge($challenge, ['code' => '123456'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['challenge_token' => __('headless::two_factor.errors.challenge_expired')]);
});

it('issues a token for a recovery code and replaces it', function () {
    $user = enabledTwoFactorUser();
    $code = $user->recoveryCodes()[0];

    answerChallenge(challengeToken($user), ['recovery_code' => $code])->assertCreated();

    expect($user->fresh()->recoveryCodes())->not->toContain($code);
});

it('rejects a wrong code', function () {
    Event::fake([TwoFactorAuthenticationFailed::class]);
    $user = enabledTwoFactorUser();
    $challenge = challengeToken($user);

    answerChallenge($challenge, ['code' => '000000'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code' => __('headless::two_factor.errors.invalid_code')]);
    Event::assertDispatched(TwoFactorAuthenticationFailed::class);

    answerChallenge($challenge, ['code' => currentOtp($user)])->assertCreated();
});

it('rejects a wrong recovery code', function () {
    answerChallenge(challengeToken(enabledTwoFactorUser()), ['recovery_code' => 'nope'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['recovery_code' => __('headless::two_factor.errors.invalid_recovery_code')]);
});

it('requires a code or a recovery code', function () {
    answerChallenge(challengeToken(enabledTwoFactorUser()), [])->assertStatus(422)->assertJsonValidationErrors('code');
});

it('rejects a code that was already used', function () {
    $user = enabledTwoFactorUser();
    $code = currentOtp($user);

    answerChallenge(challengeToken($user), ['code' => $code])->assertCreated();

    answerChallenge(challengeToken($user), ['code' => $code])->assertStatus(422)->assertJsonValidationErrors('code');
});

it('rejects an expired challenge', function () {
    $user = enabledTwoFactorUser();
    $challenge = challengeToken($user);

    $this->travel(6)->minutes();

    answerChallenge($challenge, ['code' => currentOtp($user)])->assertStatus(422)->assertJsonValidationErrors('challenge_token');
});

it('throttles challenge attempts', function () {
    $challenge = challengeToken(enabledTwoFactorUser());

    foreach (range(1, 5) as $attempt) {
        answerChallenge($challenge, ['code' => '000000']);
    }

    answerChallenge($challenge, ['code' => '000000'])->assertStatus(429);
});

it('issues tokens directly while setup is pending', function () {
    tokenSignIn(enabledTwoFactorUser(confirmed: false))->assertCreated()->assertJsonStructure(['data' => ['token']]);
});
