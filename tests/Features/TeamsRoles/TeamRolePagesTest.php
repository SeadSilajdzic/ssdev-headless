<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Features\Teams\Team;
use Ssdev\Headless\Features\Teams\TeamRoleService;
use Ssdev\Headless\Features\Teams\TeamService;
use Ssdev\Headless\Support\Flash;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: Team, 1: User, 2: User}
 */
function teamRolesFixture(): array
{
    roleManager();
    $owner = User::factory()->create();
    $team = app(TeamService::class)->create($owner, 'Research');
    $member = User::factory()->create();
    $team->members()->attach($member);

    return [$team, $owner, $member];
}

it('shows the assignable roles and the members\' roles to the owner', function () {
    [$team, $owner, $member] = teamRolesFixture();
    $editor = makeRole('Editor', ['reports.view']);
    makeRole('Admins', ['roles.view']);
    app(TeamRoleService::class)->sync($owner, $team, $member, [$editor->id]);

    actingAs($owner)->get(route('headless.teams.show', $team->id))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('roles', [['id' => $editor->id, 'name' => 'Editor']])
        ->where("memberRoles.{$member->id}", [$editor->id])
        ->where("memberRolesUrls.{$member->id}", route('headless.teams.members.roles.update', [$team->id, $member->id])));
});

it('reads the team roles of every member at once', function () {
    [$team, $owner] = teamRolesFixture();
    $roleQueries = function () use ($team, $owner): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        actingAs($owner)->get(route('headless.teams.show', $team->id))->assertOk();

        return collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains($query['query'], 'model_has_roles'))->count();
    };

    // The first load also reads the owner's own permissions, which later loads reuse.
    $roleQueries();
    $withOneMember = $roleQueries();
    $team->members()->attach(User::factory()->count(3)->create());

    expect($roleQueries())->toBe($withOneMember);
});

it('sets a member\'s team roles from the page', function () {
    [$team, $owner, $member] = teamRolesFixture();
    $editor = makeRole('Editor', ['reports.view']);

    actingAs($owner)->put(route('headless.teams.members.roles.update', [$team->id, $member->id]), ['roles' => [$editor->id]])->assertSessionHas(Flash::SUCCESS);

    expect(app(TeamRoleService::class)->rolesOf($team, $member)->pluck('name')->all())->toBe(['Editor']);
});

it('refuses a system role from the page', function () {
    [$team, $owner, $member] = teamRolesFixture();
    $admins = makeRole('Admins', ['roles.view']);

    actingAs($owner)->put(route('headless.teams.members.roles.update', [$team->id, $member->id]), ['roles' => [$admins->id]])->assertSessionHasErrors('roles');
});

it('forbids members to change roles', function () {
    [$team, $owner, $member] = teamRolesFixture();

    actingAs($member)->put(route('headless.teams.members.roles.update', [$team->id, $owner->id]), ['roles' => []])->assertForbidden();
});
