<?php

namespace Ssdev\Headless\Features\Teams\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use Ssdev\Headless\Features\Teams\TeamService;

final class CreatePersonalTeam
{
    public function __construct(
        private TeamService $teams,
        private Repository $config,
    ) {}

    public function handle(Registered $event): void
    {
        if (! $this->config->get('headless.teams.personal_team') || ! $event->user instanceof User) {
            return;
        }

        $firstName = Str::before(trim((string) $event->user->getAttribute('name')), ' ');

        $this->teams->create($event->user, __('headless::teams.personal_name', ['name' => $firstName]), personal: true);
    }
}
