<?php

namespace Ssdev\Headless\Support;

use Illuminate\Contracts\Session\Session;

final class Flash
{
    public const SUCCESS = 'headless.flash.success';

    public const ERROR = 'headless.flash.error';

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
