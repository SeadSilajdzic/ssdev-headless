<?php

namespace Ssdev\Headless\Auth;

/**
 * The host an intended URL may point at: configured, never the request's Host header, which a client controls.
 */
interface RedirectHost
{
    public function current(): ?string;
}
