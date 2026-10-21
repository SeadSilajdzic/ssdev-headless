<?php

namespace Ssdev\Headless\Exceptions;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Ssdev\Headless\Api\ApiErrorRenderer;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class ErrorResponseRenderer
{
    public function __construct(
        private ApiErrorRenderer $apiErrors,
        private InertiaErrorRenderer $errorPages,
    ) {}

    public static function register(Exceptions $exceptions): void
    {
        // Laravel keeps only the last respond() callback, so every package renderer runs from this one.
        $exceptions->respond(
            fn (Response $response, Throwable $exception, Request $request) => app(self::class)($response, $exception, $request),
        );
    }

    public function __invoke(Response $response, Throwable $exception, Request $request): Response
    {
        return ($this->errorPages)(($this->apiErrors)($response, $request), $exception, $request);
    }
}
