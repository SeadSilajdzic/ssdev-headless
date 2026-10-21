<?php

use Ssdev\Headless\Permissions\PermissionRegistry;

it('registers the role permissions', function () {
    expect(app(PermissionRegistry::class)->names())->toContain('roles.view', 'roles.manage');
});
