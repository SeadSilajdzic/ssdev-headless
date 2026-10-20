<?php

namespace Ssdev\Headless\Tests\Fixtures\Permissions;

use Ssdev\Headless\Permissions\HeadlessPermission;

enum FakePermission: string implements HeadlessPermission
{
    case ReportsView = 'reports.view';
    case ReportsExport = 'reports.export';

    public function group(): string
    {
        return 'reports';
    }
}
