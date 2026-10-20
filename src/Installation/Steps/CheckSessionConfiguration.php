<?php

namespace Ssdev\Headless\Installation\Steps;

use Illuminate\Contracts\Config\Repository;
use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\InstallStep;
use Ssdev\Headless\Installation\StepResult;

final class CheckSessionConfiguration implements InstallStep
{
    private const DESCRIPTION = 'Check session configuration';

    public function __construct(private Repository $config) {}

    public function handle(InstallContext $context): StepResult
    {
        $problems = array_filter([
            $this->config->get('session.driver') !== 'database'
                ? 'Browser sessions need SESSION_DRIVER=database.' : null,
            $this->config->get('session.http_only') !== true
                ? 'Set SESSION_HTTP_ONLY=true so scripts cannot read the session cookie.' : null,
            in_array($this->config->get('session.same_site'), [null, 'none'], true)
                ? 'Set SESSION_SAME_SITE=lax (or strict) so the session cookie is not sent with cross-site requests.' : null,
            str_starts_with((string) $this->config->get('app.url'), 'https://') && $this->config->get('session.secure') !== true
                ? 'Set SESSION_SECURE_COOKIE=true so the session cookie is only sent over HTTPS.' : null,
        ]);

        if ($problems === []) {
            return StepResult::skipped(self::DESCRIPTION);
        }

        return StepResult::manual(self::DESCRIPTION, implode(' ', $problems));
    }
}
