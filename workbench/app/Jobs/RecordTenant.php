<?php

namespace Workbench\App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Ssdev\Headless\Features\Tenancy\TenantContext;

/**
 * Records the tenant a queued job runs in and the links it would build there.
 */
class RecordTenant implements ShouldQueue
{
    use Queueable;

    /**
     * @var list<array{0: int, 1: string}>
     */
    public static array $seen = [];

    public function handle(): void
    {
        self::$seen[] = [TenantContext::currentKey(), route('login')];
    }
}
