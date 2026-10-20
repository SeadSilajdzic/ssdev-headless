<?php

return [

    'registration_attempted' => [
        'subject' => 'Someone tried to sign up with your email',
        'intro' => 'Someone tried to create a new account with this email address, but an account already exists.',
        'action' => 'Sign in',
        'outro' => 'If this was you, sign in or reset your password from the sign-in page. If not, you can ignore this email.',
    ],

    'confirm_email_change' => [
        'subject' => 'Confirm your new email address',
        'intro' => 'Click the button below to use this address for your account.',
        'action' => 'Confirm email address',
        'outro' => 'If you did not ask for this change, you can ignore this email and nothing will change.',
    ],

    'email_changed' => [
        'subject' => 'Your email address was changed',
        'intro' => 'The email address of your account was changed to :email.',
        'outro' => 'If you did not make this change, reset your password and contact support right away.',
    ],

    'account_created' => [
        'subject' => 'Your account is ready',
        'intro' => 'Thanks for signing up. Your account is ready to use.',
        'action' => 'Sign in',
    ],

];
