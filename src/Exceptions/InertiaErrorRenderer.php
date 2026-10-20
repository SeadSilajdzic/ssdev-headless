<?php

namespace Ssdev\Headless\Exceptions;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Ssdev\Headless\Http\Inertia\HeadlessPage;
use Ssdev\Headless\Http\Inertia\SharedProps;
use Ssdev\Headless\Support\Flash;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class InertiaErrorRenderer
{
    public const STATUSES = [403, 404, 419, 429, 500, 503];

    private const PAGE_EXPIRED = 419;

    public function __construct(private SharedProps $sharedProps) {}

    public static function register(Exceptions $exceptions): void
    {
        $exceptions->respond(
            fn (Response $response, Throwable $exception, Request $request) => app(self::class)($response, $exception, $request),
        );
    }

    public function __invoke(Response $response, Throwable $exception, Request $request): Response
    {
        if (config('app.debug') || $request->expectsJson() || $request->is('api/*')) {
            return $response;
        }

        $status = $response->getStatusCode();

        if ($status === self::PAGE_EXPIRED) {
            return back()->with(Flash::ERROR, __('headless::errors.419.description'));
        }

        if (! in_array($status, self::STATUSES, true)) {
            return $response;
        }

        // Unmatched routes never pass through the web middleware, so shared props are added here.
        Inertia::setRootView('headless::app');
        Inertia::share($this->sharedProps->toArray($request));

        return HeadlessPage::render('Errors/Error', ['status' => $status])
            ->toResponse($request)
            ->setStatusCode($status);
    }
}
