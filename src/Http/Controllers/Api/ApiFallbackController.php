<?php

namespace Ssdev\Headless\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * This route answers every method, so Laravel never gets to report a wrong method by itself.
 */
final class ApiFallbackController
{
    public function __invoke(Request $request, Router $router): never
    {
        $allowed = array_values(array_filter(Router::$verbs, fn (string $verb) => $this->answers($router, $verb, $request)));

        if ($allowed !== []) {
            throw new MethodNotAllowedHttpException($allowed);
        }

        abort(Response::HTTP_NOT_FOUND);
    }

    private function answers(Router $router, string $verb, Request $request): bool
    {
        return collect($router->getRoutes()->get($verb))
            ->contains(fn (Route $route) => ! $route->isFallback && $route->matches($request, false));
    }
}
