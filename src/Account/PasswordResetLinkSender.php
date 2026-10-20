<?php

namespace Ssdev\Headless\Account;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use Illuminate\Contracts\Config\Repository;

use function Illuminate\Support\defer;

final class PasswordResetLinkSender
{
    public function __construct(
        private PasswordBrokerFactory $brokers,
        private Repository $config,
    ) {}

    /**
     * The mail goes out after the response, so known and unknown emails take equally long to answer.
     */
    public function send(string $email): void
    {
        $this->brokers->broker($this->config->get('fortify.passwords'))->sendResetLink(
            ['email' => $email],
            fn (CanResetPassword $user, string $token) => defer(fn () => $user->sendPasswordResetNotification($token)),
        );
    }
}
