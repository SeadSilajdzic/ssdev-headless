<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Notifications\Dispatcher as Notifications;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Ssdev\Headless\Account\UserRepository;
use Ssdev\Headless\Features\Roles\RoleRepository;
use Ssdev\Headless\Features\Teams\Notifications\TeamInvitationNotification;

/**
 * The link is signed and expires; it works once and only for the address it was sent to.
 */
final class TeamInvitationService
{
    public function __construct(
        private TeamRepository $teams,
        private TeamRoleService $teamRoles,
        private RoleRepository $roles,
        private UserRepository $users,
        private ConnectionInterface $database,
        private Notifications $notifications,
        private Repository $config,
    ) {}

    /**
     * Inviting the same address again refreshes its invitation instead of adding another one.
     */
    public function invite(User $owner, Team $team, string $email, ?int $roleId): TeamInvitation
    {
        $email = mb_strtolower(trim($email));
        $invitee = $this->users->findByEmail($email);

        if ($invitee !== null && $this->teams->isMember($team, $invitee)) {
            throw ValidationException::withMessages(['email' => __('headless::teams.errors.already_member')]);
        }

        if ($roleId !== null && $this->roles->findAssignableInTeams($roleId) === null) {
            throw ValidationException::withMessages(['role' => __('headless::teams.errors.role_not_assignable')]);
        }

        // One statement, so two invitations to the same address at once cannot collide on the unique index.
        $team->invitations()->upsert(
            [['team_id' => $team->getKey(), 'email' => $email, 'role_id' => $roleId, 'invited_by' => $owner->getKey()]],
            ['team_id', 'email'],
            ['role_id', 'invited_by'],
        );
        $invitation = $team->invitations()->where('email', $email)->firstOrFail();

        $this->notifications->send(
            (new AnonymousNotifiable)->route('mail', $email),
            new TeamInvitationNotification($team->name, $this->link($invitation)),
        );

        return $invitation;
    }

    public function accept(User $user, int|string $invitationId): Team
    {
        return $this->database->transaction(function () use ($user, $invitationId): Team {
            // Locked, so two clicks on the same link cannot both join.
            $invitation = TeamInvitation::query()->lockForUpdate()->findOrFail($invitationId);

            if (! $invitation->isFor($user)) {
                throw ValidationException::withMessages(['invitation' => __('headless::teams.errors.invitation_other_email')]);
            }

            $team = $invitation->team()->firstOrFail();

            if (! $this->teams->isMember($team, $user)) {
                $team->members()->attach($user->getKey());
            }

            $role = $invitation->role_id === null ? null : $this->roles->findAssignableInTeams($invitation->role_id);

            if ($role !== null) {
                $this->teamRoles->grant($team, $user, $role);
            }

            $invitation->delete();
            $user->forceFill(['current_team_id' => $team->getKey()])->save();

            return $team;
        });
    }

    public function cancel(Team $team, TeamInvitation $invitation): void
    {
        $team->invitations()->findOrFail($invitation->getKey())->delete();
    }

    public function link(TeamInvitation $invitation): string
    {
        $expires = now()->addMinutes((int) $this->config->get('headless.teams.invitation_expire_minutes'));

        return URL::temporarySignedRoute('headless.team-invitations.show', $expires, ['invitation' => $invitation->getKey()]);
    }
}
