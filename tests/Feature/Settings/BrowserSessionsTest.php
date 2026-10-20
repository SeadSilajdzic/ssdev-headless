<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Account\BrowserSessionService;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

const FIREFOX_ON_WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0';

function insertSession(string $id, User $user, ?string $ip = '10.0.0.1', ?string $userAgent = FIREFOX_ON_WINDOWS): void
{
    DB::table('sessions')->insert(['id' => $id, 'user_id' => $user->id, 'ip_address' => $ip, 'user_agent' => $userAgent, 'payload' => '', 'last_activity' => time()]);
}

function publicSessionId(string $id): string
{
    return app(BrowserSessionService::class)->publicId($id);
}

beforeEach(fn () => useDatabaseSessions());

it('lists only the current user sessions without raw ids', function () {
    $user = User::factory()->create();
    insertSession('mine-raw-id', $user);
    insertSession('their-raw-id', User::factory()->create(), '10.0.0.2', null);

    $response = actingAs($user)->get(route('headless.security.edit'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Settings/Security'), false)
        ->where('sessionsSupported', true)
        ->has('sessions', 1)
        ->where('sessions.0.id', publicSessionId('mine-raw-id'))
        ->where('sessions.0.platform', 'Windows')
        ->where('sessions.0.browser', 'Firefox')
        ->where('sessions.0.ip_address', '10.0.0.1')
        ->where('sessions.0.is_current', false));

    expect($response->getContent())->not->toContain('mine-raw-id')->not->toContain('their-raw-id')->not->toContain('10.0.0.2');
});

it('marks the current session', function () {
    $user = User::factory()->create();
    insertSession('current-id', $user);
    insertSession('other-id', $user);

    $sessions = app(BrowserSessionService::class)->forUser($user, 'current-id');
    $current = array_values(array_filter($sessions, fn ($session) => $session->isCurrent));

    expect($current)->toHaveCount(1)
        ->and($current[0]->publicId)->toBe(publicSessionId('current-id'))
        ->and($current[0]->publicId)->not->toBe('current-id');
});

it('logs out a single session of the current user', function () {
    $user = User::factory()->create();
    insertSession('mine', $user);

    actingAs($user)->delete(route('headless.sessions.destroy', publicSessionId('mine')))->assertRedirect();

    expect(DB::table('sessions')->where('id', 'mine')->exists())->toBeFalse();
});

it('returns not found for another user session', function () {
    $user = User::factory()->create();
    insertSession('theirs', User::factory()->create());

    actingAs($user)->delete(route('headless.sessions.destroy', publicSessionId('theirs')))->assertNotFound();

    expect(DB::table('sessions')->where('id', 'theirs')->exists())->toBeTrue();
});

it('logs out other sessions after confirming the password', function () {
    $user = User::factory()->create();
    insertSession('other-1', $user);
    insertSession('other-2', $user);

    actingAs($user)->delete(route('headless.sessions.destroy-others'), ['password' => 'password'])->assertSessionHasNoErrors();

    expect(DB::table('sessions')->whereIn('id', ['other-1', 'other-2'])->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(1);
});

it('rejects logging out other sessions with a wrong password', function () {
    $user = User::factory()->create();
    insertSession('other-1', $user);

    actingAs($user)->delete(route('headless.sessions.destroy-others'), ['password' => 'nope'])->assertSessionHasErrors('password');

    expect(DB::table('sessions')->where('id', 'other-1')->exists())->toBeTrue();
});

it('explains that sessions need the database driver', function () {
    config(['session.driver' => 'array']);

    actingAs(User::factory()->create())->get(route('headless.security.edit'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('sessionsSupported', false)
        ->where('sessions', []));
});

it('adds the security page to the account navigation', function () {
    actingAs(User::factory()->create())->get(route('headless.security.edit'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('headless.navigation.1.items.1.key', 'security')
        ->where('headless.navigation.1.items.1.active', true));
});
