<?php

use Illuminate\Auth\Events\Registered;
use Ssdev\Headless\Features\Teams\TeamRepository;
use Workbench\App\Models\User;

it('creates a personal team on registration only when configured', function () {
    $without = User::factory()->create(['name' => 'Grace Hopper']);
    event(new Registered($without));

    config(['headless.teams.personal_team' => true]);
    $with = User::factory()->create(['name' => 'Ada Lovelace']);
    event(new Registered($with));

    $team = app(TeamRepository::class)->forMember($with)->first();

    expect(app(TeamRepository::class)->forMember($without))->toBeEmpty()
        ->and($team->name)->toBe("Ada's Team")
        ->and($team->personal)->toBeTrue()
        ->and($with->fresh()->current_team_id)->toBe($team->id);
});
