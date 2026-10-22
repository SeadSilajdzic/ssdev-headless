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

    'users' => [
        // Minutes an invitation link stays valid.
        'invitation_expire_minutes' => 10080,
    ],

    'teams' => [
        // Give every new account a team of its own when it registers.
        'personal_team' => false,
        // Minutes a team invitation link stays valid.
        'invitation_expire_minutes' => 10080,
    ],

    'tenancy' => [
        // Tenants live on subdomains of this domain, e.g. acme.example.com for example.com.
        'base_domain' => 'localhost',
        // The platform's own domain, where central accounts sign in; often the base domain itself.
        'central_domain' => 'localhost',
        // Lets people sign up inside a tenant; otherwise they join through invitations.
        'registration' => false,
        // Directory on every disk under which each tenant's files are kept apart.
        'storage_directory' => 'tenants',
        // Paths answered on any host, on the central level: health checks often call the server by its IP address.
        'any_host_paths' => ['up'],
        // Minutes an owner's invitation link stays valid.
        'owner_invitation_expire_minutes' => 10080,
        // Subdomains no tenant may take.
        'reserved_subdomains' => ['www', 'admin', 'api', 'app', 'mail', 'ftp', 'smtp', 'imap', 'static', 'assets', 'cdn', 'status', 'help', 'support', 'docs', 'blog'],
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
        'invitations' => 10,
        'team_invitations' => 10,
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
