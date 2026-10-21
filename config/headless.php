<?php

return [

    'features' => [],

    'routes' => [
        'prefix' => '',
        'middleware' => ['web'],
    ],

    'api' => [
        'enabled' => true,
        'prefix' => 'api/v1',
        // Minutes until a new token expires; null keeps tokens until they are revoked.
        'token_expiration' => null,
    ],

    'registration' => [
        'enabled' => true,
    ],

    'email_verification' => true,

    'honeypot' => [
        'field' => 'my_name',
    ],

    // Attempts per minute.
    'rate_limits' => [
        'login' => 5,
        'login_per_ip' => 30,
        'registration' => 5,
        'password_reset' => 5,
        'verification' => 6,
        'reauthentication' => 5,
        'email_change' => 6,
        'api' => 60,
    ],

    'avatars' => [
        'disk' => 'public',
        'directory' => 'avatars',
        'max_kilobytes' => 2048,
        'max_dimension' => 4096,
        'mimes' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    ],

    // Backed enums implementing Ssdev\Headless\Permissions\HeadlessPermission.
    'permissions' => [],

];
