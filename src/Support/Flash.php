<?php

namespace Ssdev\Headless\Support;

use Illuminate\Contracts\Session\Session;

final class Flash
{
    public const SUCCESS = 'headless.flash.success';

    public const ERROR = 'headless.flash.error';

    // Fortify flashes its own status messages under this key.
    public const STATUS = 'status';

    // A new API token's plain text, shown on the next page load only.
    public const TOKEN = 'headless.flash.token';

    // Two-factor recovery codes, shown on the next page load only.
    public const RECOVERY_CODES = 'headless.flash.recovery_codes';

    public function __construct(private Session $session) {}

    public function success(string $message): void
    {
        $this->session->flash(self::SUCCESS, $message);
    }

    public function error(string $message): void
    {
        $this->session->flash(self::ERROR, $message);
    }
}
