<?php

use Ssdev\Headless\Features\Users\UsersModule;
use Ssdev\Headless\Permissions\PermissionRegistry;

it('registers the user permissions', function () {
    expect(app(PermissionRegistry::class)->names())->toContain('users.view', 'users.create', 'users.update', 'users.block', 'users.delete');
});

it('publishes the user management columns and adds soft deletes to the user model', function () {
    $module = new UsersModule;

    expect($module->migrations())->toHaveKey(UsersModule::MIGRATION)
        ->and($module->migrations()[UsersModule::MIGRATION])->toBeFile()
        ->and($module->userTraits())->toBe(['SoftDeletes' => 'use Illuminate\Database\Eloquent\SoftDeletes;']);
});
