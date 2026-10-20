<?php

namespace Ssdev\Headless\Http\Controllers;

use Inertia\Response;
use Ssdev\Headless\Http\Inertia\HeadlessPage;

final class DashboardController
{
    public const PATH = 'dashboard';

    public function __invoke(): Response
    {
        return HeadlessPage::render('Dashboard');
    }
}
