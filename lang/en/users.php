<?php

return [

    'group' => 'Users',

    'invitation' => [
        'title' => 'Welcome',
        'subtitle' => 'Choose a password to finish setting up your account.',
        'submit' => 'Set password',
        'accepted' => 'Your password is set. You can sign in now.',
    ],

    'errors' => [
        'self' => 'You cannot do this to your own account.',
        'last_manager' => 'At least one active user must be able to block and unblock users.',
        'blocked' => 'This account is blocked. Contact an administrator.',
        'not_invited' => 'This user has already accepted their invitation.',
        'invitation_throttled' => 'An invitation was just sent. Wait a minute before sending another one.',
        'invitation_invalid' => 'This invitation link is invalid or has expired.',
    ],

];
