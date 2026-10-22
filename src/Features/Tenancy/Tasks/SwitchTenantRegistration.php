<?php

namespace Ssdev\Headless\Features\Tenancy\Tasks;

use Illuminate\Contracts\Config\Repository;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

/**
 * Inside a tenant, sign-up follows the tenancy setting; the central level keeps the application's own.
 */
final class SwitchTenantRegistration implements SwitchTenantTask
{
    private const ENABLED = 'headless.registration.enabled';

    private bool $centralSetting;

    public function __construct(private Repository $config)
    {
        $this->centralSetting = (bool) $config->get(self::ENABLED);
    }

    public function makeCurrent(IsTenant $tenant): void
    {
        $this->config->set(self::ENABLED, (bool) $this->config->get('headless.tenancy.registration'));
    }

    public function forgetCurrent(): void
    {
        $this->config->set(self::ENABLED, $this->centralSetting);
    }
}
