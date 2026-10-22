<?php

namespace Ssdev\Headless\Http\Inertia;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * A page that renders whether or not the request went through the Inertia middleware, e.g. an error or a suspended tenant.
 */
final class StandalonePage
{
    public function __construct(private SharedProps $sharedProps) {}

    /**
     * @param  array<string, mixed>  $props
     */
    public function render(Request $request, string $page, array $props, int $status): Response
    {
        Inertia::setRootView('headless::app');
        Inertia::share($this->sharedProps->toArray($request));

        return HeadlessPage::render($page, $props)
            ->toResponse($request)
            ->setStatusCode($status);
    }
}
