<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Ssdev\Headless\Events\PasswordChanged;
use Ssdev\Headless\Support\Flash;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;

it('changes the password and ends the other sessions', function () {
    useDatabaseSessions();
    Event::fake([PasswordChanged::class]);
    $user = User::factory()->create();
    DB::table('sessions')->insert([
        ['id' => 'other-1', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()],
        ['id' => 'other-2', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()],
    ]);

    actingAs($user)->put(route('headless.password.update'), [
        'current_password' => 'password', 'password' => 'N3w-password!', 'password_confirmation' => 'N3w-password!',
    ])->assertSessionHasNoErrors()->assertSessionHas(Flash::SUCCESS, __('headless::settings.password.updated'));

    expect(Hash::check('N3w-password!', $user->fresh()->password))->toBeTrue()
        ->and(DB::table('sessions')->whereIn('id', ['other-1', 'other-2'])->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(1);
    assertAuthenticatedAs($user);
    Event::assertDispatched(PasswordChanged::class);
});

it('revokes every api token on a password change', function () {
    $user = User::factory()->create();
    $user->createToken('stolen');

    actingAs($user)->put(route('headless.password.update'), [
        'current_password' => 'password', 'password' => 'N3w-password!', 'password_confirmation' => 'N3w-password!',
    ])->assertSessionHasNoErrors();

    expect($user->tokens()->count())->toBe(0);
});

it('rejects a wrong current password', function () {
    $user = User::factory()->create();

    actingAs($user)->put(route('headless.password.update'), [
        'current_password' => 'nope', 'password' => 'N3w-password!', 'password_confirmation' => 'N3w-password!',
    ])->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

it('rejects a mismatched confirmation', function () {
    $user = User::factory()->create();

    actingAs($user)->put(route('headless.password.update'), [
        'current_password' => 'password', 'password' => 'N3w-password!', 'password_confirmation' => 'other',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});
