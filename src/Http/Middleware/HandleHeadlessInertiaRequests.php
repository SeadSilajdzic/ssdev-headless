<?php

namespace Ssdev\Headless\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Ssdev\Headless\Http\Inertia\SharedProps;

class HandleHeadlessInertiaRequests extends Middleware
{
    protected $rootView = 'headless::app';

    public function __construct(private SharedProps $sharedProps) {}

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), $this->sharedProps->toArray($request));
    }
}
