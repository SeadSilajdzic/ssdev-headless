<?php

use Ssdev\Headless\Features\Teams\Team;
use Ssdev\Headless\Features\Teams\TeamService;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

it('refuses to delete an owner whose team has other members', function () {
    $owner = User::factory()->create();
    $team = app(TeamService::class)->create($owner, 'Research');
    $team->members()->attach(User::factory()->create());

    actingAs($owner)->delete(route('headless.account.destroy'), ['password' => 'password'])
        ->assertSessionHasErrors(['account' => __('headless::teams.errors.owns_teams')]);

    expect(User::find($owner->id))->not->toBeNull();
});

it('deletes the solo teams of a deleted account', function () {
    $owner = User::factory()->create();
    app(TeamService::class)->create($owner, 'Solo');

    actingAs($owner)->delete(route('headless.account.destroy'), ['password' => 'password'])->assertRedirect('/');

    expect(User::withTrashed()->find($owner->id))->toBeNull()
        ->and(Team::count())->toBe(0);
});
