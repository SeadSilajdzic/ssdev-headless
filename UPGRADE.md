# Upgrade Guide

Within a minor version, upgrading is always the same three commands:

```bash
composer update ssdev/laravel-headless
php artisan headless:upgrade
php artisan migrate
```

`headless:upgrade` publishes the migrations a new version added and leaves the ones you already have alone. Anything else that needs your attention is listed below, per version.

## Upgrading to 0.2 from 0.1

Require the new minor version explicitly, because `^0.1` never installs 0.2:

```bash
composer require ssdev/laravel-headless:^0.2
php artisan headless:install
npm run build
```

`headless:install` skips everything already in place. For 0.2 it also:

- publishes the new migrations `create_permission_tables` (spatie/laravel-permission) and `add_avatar_path_to_users_table`, and offers to run them;
- adds `HasRoles` and `MustVerifyEmail` to `app/Models/User.php`;
- checks your session settings (browser sessions need `SESSION_DRIVER=database`);
- offers to create the first admin account.

Remove any temporary `login` route from your own `routes/web.php`: the package now registers `login`, `register`, `logout` and the password routes itself.
