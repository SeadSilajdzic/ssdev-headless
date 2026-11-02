<?php

namespace Ssdev\Headless\Account;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Notifications\Dispatcher as Notifications;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Ssdev\Headless\Notifications\AccountCreatedNotification;
use Ssdev\Headless\Notifications\RegistrationAttemptedNotification;

final class RegistrationService
{
    public function __construct(
        private UserRepository $users,
        private Hasher $hasher,
        private Dispatcher $events,
        private Notifications $notifications,
        private ConnectionInterface $database,
    ) {}

    /**
     * Every outcome looks the same to the visitor, so registration never reveals which emails have accounts.
     */
    public function register(RegistrationData $data): void
    {
        // Hashed for existing emails too, so both paths take about as long.
        $password = $this->hasher->make($data->password);

        if ($this->users->findByEmail($data->email) !== null) {
            $this->notifyExistingAccount($data->email);

            return;
        }

        try {
            // The savepoint keeps an outer PostgreSQL transaction usable when a parallel request took the email first.
            $user = $this->database->transaction(fn () => $this->users->create(['name' => $data->name, 'email' => $data->email, 'password' => $password]));
        } catch (UniqueConstraintViolationException) {
            $this->notifyExistingAccount($data->email);

            return;
        }

        $this->events->dispatch(new Registered($user));

        if (! $user instanceof MustVerifyEmail) {
            $this->notifications->send($user, new AccountCreatedNotification);
        }
    }

    private function notifyExistingAccount(string $email): void
    {
        $existing = $this->users->findByEmail($email);

        if ($existing !== null) {
            $this->notifications->send($existing, new RegistrationAttemptedNotification);
        }
    }
}
