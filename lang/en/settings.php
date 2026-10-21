<?php

return [

    'profile' => [
        'title' => 'Profile',
        'information_title' => 'Profile information',
        'information_description' => "Update your account's name and email address.",
        'save' => 'Save changes',
        'saved' => 'Profile saved.',
        'email_confirmation_sent' => 'We sent a confirmation link to your new email address. Your current email stays active until you confirm it.',
        'current_password_hint' => 'Enter your password to change your email address.',
        'unverified' => 'Your email address is unverified.',
        'resend_verification' => 'Resend verification email',
    ],

    'avatar' => [
        'title' => 'Photo',
        'change' => 'Change photo',
        'remove' => 'Remove photo',
        'hint' => ':formats. Max :size KB.',
        'updated' => 'Photo updated.',
        'removed' => 'Photo removed.',
    ],

    'password' => [
        'title' => 'Update password',
        'description' => 'Use a long, random password to keep your account secure.',
        'new' => 'New password',
        'submit' => 'Update password',
        'updated' => 'Password updated. Your other sessions have been signed out.',
    ],

    'delete' => [
        'title' => 'Delete account',
        'description' => 'Permanently delete your account and all of its data. This cannot be undone.',
        'button' => 'Delete account',
        'confirm_title' => 'Delete account?',
        'confirm_description' => 'This cannot be undone. Enter your password to confirm.',
    ],

    'security' => [
        'title' => 'Security',
    ],

    'sessions' => [
        'title' => 'Browser sessions',
        'description' => 'Manage and log out your active sessions on other browsers and devices.',
        'unsupported' => 'Browser sessions are listed only when the application stores sessions in the database (SESSION_DRIVER=database).',
        'this_device' => 'This device',
        'unknown' => 'Unknown device',
        'log_out' => 'Log out',
        'log_out_others' => 'Log out other sessions',
        'log_out_others_hint' => 'Sign out of all sessions except this device.',
        'confirm_title' => 'Log out other sessions?',
        'confirm_description' => 'Enter your password to sign out of every other browser and device.',
        'logged_out' => 'The session has been logged out.',
        'others_logged_out' => 'Your other sessions have been logged out.',
    ],

    'api_tokens' => [
        'title' => 'API tokens',
        'create_title' => 'Create API token',
        'create_description' => 'API tokens let other apps and scripts use the API on your behalf.',
        'name' => 'Token name',
        'permissions' => 'Permissions',
        'no_permissions' => 'You have no permissions to give a token yet. A token can still read and update your own account.',
        'create' => 'Create token',
        'created' => 'Copy your new token now. For your security it will not be shown again.',
        'copy' => 'Copy',
        'copied' => 'Copied',
        'created_flash' => 'Token created.',
        'list_title' => 'Manage tokens',
        'list_description' => 'Revoke tokens you no longer need.',
        'empty' => 'You have no API tokens yet.',
        'last_used' => 'Last used :date',
        'never_used' => 'Never used',
        'expires' => 'Expires :date',
        'revoke' => 'Revoke',
        'revoke_title' => 'Revoke this token?',
        'revoke_description' => 'Apps that use this token stop working right away.',
        'revoked' => 'Token revoked.',
    ],

    'email_change' => [
        'confirmed' => 'Your email address has been changed.',
        'invalid' => 'This confirmation link is invalid or has expired.',
    ],

];
