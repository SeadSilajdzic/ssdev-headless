<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Ssdev\Headless\Events\AccountDeleted;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;

it('deletes the account after confirming the password', function () {
    useDatabaseSessions();
    Storage::fake('public');
    Event::fake([AccountDeleted::class]);
    $user = User::factory()->create(['avatar_path' => 'avatars/me.png']);
    Storage::disk('public')->put('avatars/me.png', 'x');
    DB::table('sessions')->insert(['id' => 'other', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

    actingAs($user)->delete(route('headless.account.destroy'), ['password' => 'password'])->assertRedirect('/');

    assertGuest();
    expect(User::find($user->id))->toBeNull()
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
    Storage::disk('public')->assertMissing('avatars/me.png');
    Event::assertDispatched(AccountDeleted::class, fn (AccountDeleted $event) => $event->userKey === $user->id && $event->email === $user->email);
});

it('keeps the account when the password is wrong', function () {
    $user = User::factory()->create();

    actingAs($user)->delete(route('headless.account.destroy'), ['password' => 'nope'])->assertSessionHasErrors('password');

    expect(User::find($user->id))->not->toBeNull();
    assertAuthenticatedAs($user);
});
