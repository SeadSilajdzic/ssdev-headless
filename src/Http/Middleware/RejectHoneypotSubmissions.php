<?php

namespace Ssdev\Headless\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RejectHoneypotSubmissions
{
    public function __construct(private Repository $config) {}

    public function handle(Request $request, Closure $next): Response
    {
        $value = $request->input((string) $this->config->get('headless.honeypot.field'));

        if ($value !== null && $value !== '') {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $next($request);
    }
}
