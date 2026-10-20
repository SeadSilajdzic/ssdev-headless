<?php

namespace Ssdev\Headless\Account;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Ssdev\Headless\Events\EmailChanged;
use Ssdev\Headless\Notifications\ConfirmEmailChangeNotification;
use Ssdev\Headless\Notifications\EmailChangedNotification;

/**
 * The confirmation link carries everything it needs, signed: the user, the new email and a
 * fingerprint of the email it was issued for, so a link becomes useless once the email changes again.
 */
final class EmailChangeService
{
    public const CONFIRM_ROUTE = 'headless.email-change.confirm';

    public function __construct(
        private UserRepository $users,
        private ConnectionInterface $database,
        private Dispatcher $events,
        private Repository $config,
    ) {}

    public function request(User $user, string $newEmail): void
    {
        $url = URL::temporarySignedRoute(
            self::CONFIRM_ROUTE,
            now()->addMinutes((int) $this->config->get('auth.verification.expire', 60)),
            ['user' => $user->getKey(), 'email' => $newEmail, 'current' => $this->fingerprint((string) $user->getAttribute('email'))],
        );

        Notification::route('mail', $newEmail)->notify(new ConfirmEmailChangeNotification($url));
    }

    public function confirm(User $user, string $newEmail, string $currentFingerprint): bool
    {
        $oldEmail = $this->database->transaction(function () use ($user, $newEmail, $currentFingerprint): ?string {
            $locked = $this->users->findForUpdate($user->getKey());

            if ($locked === null
                || ! hash_equals($this->fingerprint((string) $locked->getAttribute('email')), $currentFingerprint)
                || $this->users->emailTaken($newEmail, $locked->getKey())) {
                return null;
            }

            $oldEmail = (string) $locked->getAttribute('email');
            $locked->forceFill(['email' => $newEmail, 'email_verified_at' => now()])->save();

            return $oldEmail;
        });

        if ($oldEmail === null) {
            return false;
        }

        Notification::route('mail', $oldEmail)->notify(new EmailChangedNotification($newEmail));
        $this->events->dispatch(new EmailChanged($user->fresh() ?? $user, $oldEmail, $newEmail));

        return true;
    }

    /**
     * Keyed so whoever receives the link at the new address cannot recover the current one.
     */
    private function fingerprint(string $email): string
    {
        return hash_hmac('sha256', $email, (string) $this->config->get('app.key'));
    }
}
