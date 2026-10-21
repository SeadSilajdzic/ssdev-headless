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

    'two_factor' => [
        // Users who must turn on two-factor authentication before they can use the app: everyone, or anyone with one of these permissions.
        'required' => false,
        'required_for_permissions' => [],
        // Minutes an API sign-in challenge stays valid.
        'challenge_expiration' => 5,
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
        'two_factor' => 5,
    ],

    'session_locations' => [
        // A class implementing Ssdev\Headless\Account\Location\IpLocator, e.g. MaxMindLocator::class; null keeps the session map off.
        'locator' => null,
        'maxmind_database' => storage_path('app/geoip/GeoLite2-City.mmdb'),
        'cache_minutes' => 1440,
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
