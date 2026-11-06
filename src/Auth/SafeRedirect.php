<?php

namespace Ssdev\Headless\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SafeRedirect
{
    private const INTENDED_KEY = 'url.intended';

    public function __construct(private RedirectHost $host) {}

    public function intended(Request $request, string $fallback): RedirectResponse
    {
        $intended = $request->session()->pull(self::INTENDED_KEY);

        return redirect()->to(is_string($intended) && $this->isInternal($intended) ? $intended : $fallback);
    }

    private function isInternal(string $url): bool
    {
        // Browsers strip control characters and read a backslash as a slash, so "https://evil.test\@app.test" goes to evil.test.
        if (preg_match('/[\x00-\x1F\x7F\\\\]/', $url) === 1) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//');
        }

        $host = parse_url($url, PHP_URL_HOST);
        $trustedHost = $this->host->current();

        return is_string($host) && $trustedHost !== null && strcasecmp($host, $trustedHost) === 0
            && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
    }
}
