<?php

namespace Ssdev\Headless\Api;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Ssdev\Headless\Exceptions\ErrorResponseRenderer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives every API error one shape, {"message"} or {"message", "errors"}, without class names, SQL or traces.
 */
final class ApiErrorRenderer
{
    public const STATUSES = [401, 403, 404, 405, 429, 500, 503];

    public function __construct(private Repository $config) {}

    public static function register(Exceptions $exceptions): void
    {
        ErrorResponseRenderer::register($exceptions);
    }

    public function __invoke(Response $response, Request $request): Response
    {
        $status = $response->getStatusCode();

        if ($this->config->get('app.debug') || ! $this->isApiRequest($request) || ! in_array($status, self::STATUSES, true)) {
            return $response;
        }

        // Keeps headers such as Retry-After, drops the ones that described the old body.
        $headers = Arr::except($response->headers->all(), ['content-type', 'content-length']);

        return new JsonResponse(['message' => __("headless::api.errors.{$status}")], $status, $headers);
    }

    private function isApiRequest(Request $request): bool
    {
        $prefix = trim((string) $this->config->get('headless.api.prefix'), '/');

        return $request->is($prefix) || $request->is($prefix.'/*');
    }
}
