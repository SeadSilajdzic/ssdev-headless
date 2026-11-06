<?php

namespace Ssdev\Headless\Auth;

use Illuminate\Contracts\Config\Repository;

final class AppRedirectHost implements RedirectHost
{
    public function __construct(private Repository $config) {}

    public function current(): ?string
    {
        $host = parse_url((string) $this->config->get('app.url'), PHP_URL_HOST);

        return is_string($host) ? $host : null;
    }
}
