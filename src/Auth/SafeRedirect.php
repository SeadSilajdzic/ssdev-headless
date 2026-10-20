<?php

namespace Ssdev\Headless\Auth;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SafeRedirect
{
    private const INTENDED_KEY = 'url.intended';

    public function __construct(private Repository $config) {}

    public function intended(Request $request, string $fallback): RedirectResponse
    {
        $intended = $request->session()->pull(self::INTENDED_KEY);

        return redirect()->to(is_string($intended) && $this->isInternal($intended) ? $intended : $fallback);
    }

    private function isInternal(string $url): bool
    {
        if (str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//') && ! str_starts_with($url, '/\\');
        }

        // Compared with the configured app URL, never the request Host header, which a client controls.
        $host = parse_url($url, PHP_URL_HOST);
        $appHost = parse_url((string) $this->config->get('app.url'), PHP_URL_HOST);

        return is_string($host) && is_string($appHost) && strcasecmp($host, $appHost) === 0
            && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
    }
}
