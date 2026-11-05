<?php

namespace Ssdev\Headless\Exceptions;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Ssdev\Headless\Api\ApiErrorRenderer;
use Ssdev\Headless\Http\Inertia\StandalonePage;
use Ssdev\Headless\Support\Flash;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class InertiaErrorRenderer
{
    public const STATUSES = [403, 404, 419, 429, 500, 503];

    private const PAGE_EXPIRED = 419;

    public function __construct(
        private StandalonePage $pages,
        private ApiErrorRenderer $apiErrors,
    ) {}

    public static function register(Exceptions $exceptions): void
    {
        ErrorResponseRenderer::register($exceptions);
    }

    public function __invoke(Response $response, Throwable $exception, Request $request): Response
    {
        // The application's own API usually lives under api/, whatever the package's prefix is.
        if (config('app.debug') || $request->expectsJson() || $request->is('api/*') || $this->apiErrors->isApiRequest($request)) {
            return $response;
        }

        $status = $response->getStatusCode();

        if ($status === self::PAGE_EXPIRED) {
            return back()->with(Flash::ERROR, __('headless::errors.419.description'));
        }

        if (! in_array($status, self::STATUSES, true)) {
            return $response;
        }

        try {
            // Unmatched routes never pass through the web middleware, so the page brings its own shared props.
            return $this->pages->render($request, 'Errors/Error', ['status' => $status], $status);
        } catch (Throwable) {
            // The error page must never break error handling itself, e.g. while the database is down.
            return $response;
        }
    }
}
