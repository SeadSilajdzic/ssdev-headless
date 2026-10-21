<?php

use Spatie\Permission\Models\Role;
use Ssdev\Headless\Features\Roles\Criteria\SearchRolesByName;
use Ssdev\Headless\Features\Roles\Criteria\SortRoles;
use Ssdev\Headless\Features\Roles\RoleGuard;
use Ssdev\Headless\Features\Roles\RoleRepository;

beforeEach(function () {
    foreach (['Editor', 'Administrator', 'Support'] as $name) {
        Role::create(['name' => $name, 'guard_name' => 'web']);
    }
});

it('searches roles by name', function () {
    $names = app(RoleRepository::class)->paginate([new SearchRolesByName('adm')])->pluck('name')->all();

    expect($names)->toBe(['Administrator']);
});

it('treats wildcards in a search as text', function () {
    expect(app(RoleRepository::class)->paginate([new SearchRolesByName('%')])->total())->toBe(0);
});

it('sorts by name by default and by whitelisted columns only', function () {
    $repository = app(RoleRepository::class);

    expect($repository->paginate([new SortRoles(null, null)])->pluck('name')->all())->toBe(['Administrator', 'Editor', 'Support'])
        ->and($repository->paginate([new SortRoles('name', 'desc')])->pluck('name')->all())->toBe(['Support', 'Editor', 'Administrator'])
        ->and($repository->paginate([new SortRoles('password', 'asc')])->pluck('name')->all())->toBe(['Administrator', 'Editor', 'Support']);
});

it('counts members and permissions', function () {
    $role = app(RoleRepository::class)->paginate([new SearchRolesByName('Editor')])->first();

    expect($role->users_count)->toBe(0)->and($role->permissions_count)->toBe(0);
});

it('caps the page size', function () {
    expect(app(RoleRepository::class)->paginate([], 500)->perPage())->toBe(RoleRepository::MAX_PER_PAGE);
});

it('explains a missing user model instead of guessing the role guard', function () {
    config(['auth.providers.users.model' => null]);

    expect(fn () => app(RoleGuard::class)->name())->toThrow(LogicException::class, 'auth.providers.users.model');
});
