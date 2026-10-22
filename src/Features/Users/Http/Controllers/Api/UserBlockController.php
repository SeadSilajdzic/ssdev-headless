<?php

namespace Ssdev\Headless\Features\Users\Http\Controllers\Api;

use Illuminate\Http\Request;
use Ssdev\Headless\Features\Users\Http\Resources\ManagedUserResource;
use Ssdev\Headless\Features\Users\ManagedUserRepository;
use Ssdev\Headless\Features\Users\UserBlockService;

final class UserBlockController
{
    public function __construct(
        private ManagedUserRepository $users,
        private UserBlockService $blocks,
    ) {}

    public function store(Request $request, string $user): ManagedUserResource
    {
        $managed = $this->users->findOrFail($user);
        $this->blocks->block($request->user(), $managed);

        return ManagedUserResource::make($managed);
    }

    public function destroy(Request $request, string $user): ManagedUserResource
    {
        $managed = $this->users->findOrFail($user);
        $this->blocks->unblock($request->user(), $managed);

        return ManagedUserResource::make($managed);
    }
}
