<?php

return [

    'group' => 'Tenants',

    'errors' => [
        'subdomain_format' => 'Use 3 to 63 lowercase letters, digits and hyphens, not starting or ending with a hyphen.',
        'subdomain_reserved' => 'This subdomain is reserved.',
        'subdomain_taken' => 'This subdomain is already taken.',
        'owner_invitation_invalid' => 'This invitation link is invalid or has expired.',
        'owner_accepted' => 'The owner has already set up the account.',
        'owner_invitation_throttled' => 'An invitation was just sent. Wait a minute before sending another one.',
    ],

    'owner_invitation' => [
        'title' => 'Set up your account',
        'subtitle' => 'Choose your name and a password to start managing your organization.',
        'name' => 'Your name',
        'submit' => 'Save and continue',
        'accepted' => 'Your account is ready. Sign in with your new password.',
    ],

    'suspended' => [
        'title' => 'Account suspended',
        'description' => 'This account is suspended. Contact the administrator to restore access.',
    ],

];
