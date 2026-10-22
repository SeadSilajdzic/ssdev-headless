<?php

namespace Ssdev\Headless\Features\Tenancy\Queue;

use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobRetryRequested;
use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction;
use Spatie\Multitenancy\Contracts\IsTenant;

/**
 * A job dispatched on the central level carries no tenant and runs centrally; Spatie would delete it as broken.
 */
final class KeepQueuedJobsInTheirTenantAction extends MakeQueueTenantAwareAction
{
    protected function bindOrForgetCurrentTenant(JobProcessing|JobRetryRequested $event): void
    {
        if ($this->resolveTenantId($event) === null) {
            app(IsTenant::class)::forgetCurrent();

            return;
        }

        parent::bindOrForgetCurrentTenant($event);
    }
}
