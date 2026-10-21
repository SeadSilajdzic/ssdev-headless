<?php

namespace Ssdev\Headless\Features\Users\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Ssdev\Headless\Features\Users\ManagedUserRepository;
use Ssdev\Headless\Features\Users\UserBlockService;
use Ssdev\Headless\Support\Flash;

final class UserBlockController
{
    public function __construct(
        private ManagedUserRepository $users,
        private UserBlockService $blocks,
    ) {}

    public function store(Request $request, string $user): RedirectResponse
    {
        $this->blocks->block($request->user(), $this->users->findOrFail($user));

        return back()->with(Flash::SUCCESS, __('headless::users.blocked'));
    }

    public function destroy(Request $request, string $user): RedirectResponse
    {
        $this->blocks->unblock($request->user(), $this->users->findOrFail($user));

        return back()->with(Flash::SUCCESS, __('headless::users.unblocked'));
    }
}
