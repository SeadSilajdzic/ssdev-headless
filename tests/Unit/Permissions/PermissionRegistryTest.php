<?php

use Ssdev\Headless\Permissions\PermissionRegistry;
use Ssdev\Headless\Tests\Fixtures\Permissions\FakePermission;

it('lists registered permission names once', function () {
    $registry = new PermissionRegistry([FakePermission::class]);
    $registry->register(FakePermission::class);

    expect($registry->names())->toBe(['reports.view', 'reports.export'])
        ->and(array_keys($registry->grouped()))->toBe(['reports'])
        ->and($registry->grouped()['reports'])->toBe([FakePermission::ReportsView, FakePermission::ReportsExport]);
});

it('rejects classes that are not headless permissions', fn () => (new PermissionRegistry([]))->register(stdClass::class))
    ->throws(InvalidArgumentException::class);
