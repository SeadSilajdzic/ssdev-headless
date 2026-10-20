# Upgrade Guide

Upgrading `ssdev/laravel-headless` is always the same three commands:

```bash
composer update ssdev/laravel-headless
php artisan headless:upgrade
php artisan migrate
```

`headless:upgrade` publishes the migrations a new version added and leaves the ones you already have alone. Anything else that needs your attention is listed below, per version.

## Unreleased

- New migrations: `create_permission_tables` (spatie/laravel-permission) and `add_avatar_path_to_users_table`.
- Run `php artisan headless:install` again (it skips everything already in place) so it can add `HasRoles` and `MustVerifyEmail` to `app/Models/User.php` and check your session settings.
