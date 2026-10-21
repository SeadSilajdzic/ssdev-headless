<?php

return [

    'title' => 'Two-factor authentication',
    'description' => "Add an extra layer of security. When it is on, you'll be asked for a code from your authenticator app when you sign in.",

    'status' => [
        'disabled' => 'Disabled',
        'pending' => 'Setup pending',
        'enabled' => 'Enabled',
    ],

    'enable' => 'Turn on',
    'set_up' => 'Set up',
    'manage' => 'Manage',
    'enabled_flash' => 'Two-factor authentication is on.',
    'disabled_flash' => 'Two-factor authentication is off.',

    'setup' => [
        'title' => 'Finish the setup',
        'scan' => 'Scan this QR code with Google Authenticator, 1Password or Authy, or type the setup key into the app.',
        'qr_alt' => 'QR code for your authenticator app',
        'key' => 'Setup key',
        'code' => 'Code from your app',
        'confirm' => 'Confirm',
        'cancel' => 'Cancel setup',
    ],

    'recovery_codes' => [
        'title' => 'Recovery codes',
        'description' => "Store these codes in a safe place. Each one signs you in once if you lose your device. They won't be shown again.",
        'copy' => 'Copy',
        'copied' => 'Copied',
        'download' => 'Download',
        'regenerate' => 'Regenerate recovery codes',
        'regenerated' => 'New recovery codes are ready. The old ones no longer work.',
    ],

    'challenge' => [
        'code_title' => 'Two-factor code',
        'code_description' => 'Enter the 6-digit code from your authenticator app.',
        'recovery_title' => 'Recovery code',
        'recovery_description' => 'Lost access to your authenticator? Use one of the recovery codes you saved when you set up two-factor authentication.',
        'recovery_code' => 'Recovery code',
        'submit' => 'Verify code',
        'use_recovery_code' => 'Use a recovery code instead',
        'use_code' => 'Use authenticator code instead',
        'back_to_login' => 'Back to sign in',
    ],

    'disable' => [
        'button' => 'Turn off',
        'confirm_title' => 'Turn off two-factor authentication?',
        'confirm_description' => 'Signing in will only need your password again.',
    ],

    'errors' => [
        'already_enabled' => 'Two-factor authentication is already on.',
        'not_enabled' => 'Two-factor authentication is off.',
        'invalid_code' => 'The code is invalid or has already been used.',
        'invalid_recovery_code' => 'The recovery code is invalid.',
        'challenge_expired' => 'This sign-in has expired. Please sign in again.',
    ],

    'required' => 'Turn on two-factor authentication to continue.',

];
