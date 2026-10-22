# Upgrade Guide

Within a minor version, upgrading is always the same three commands:

```bash
composer update ssdev/laravel-headless
php artisan headless:upgrade
php artisan migrate
```

`headless:upgrade` publishes the migrations a new version added and leaves the ones you already have alone. Anything else that needs your attention is listed below, per version.

## Upgrading to 0.4 from 0.3

```bash
composer require ssdev/laravel-headless:^0.4
php artisan headless:install
npm run build
```

Two-factor authentication is optional. Add it with `php artisan headless:add two-factor --migrate`, then run `npm run build`.

Roles are optional too. Add them with `php artisan headless:add roles --migrate` and run `npm run build`. Admins created before have their permissions directly, but not the new `roles.view` and `roles.manage`; give them every permission with `php artisan headless:permissions:grant admin@example.com --all`, or create a new admin with `php artisan headless:create-admin`, who gets the `Administrator` role.

User management is optional as well. Add it with `php artisan headless:add users --migrate` and run `npm run build`; it adds `SoftDeletes` to `app/Models/User.php`, the `invited_at`, `blocked_at` and `deleted_at` columns and a `user_invitation_tokens` table. Give existing admins the new `users.*` permissions with `php artisan headless:permissions:grant admin@example.com --all`.

- Deleting your own account now removes it for good even if your `User` model already used `SoftDeletes`.
- The roles and users pages ask for the password before they open, like the API tokens page.

- If your code calls `Ssdev\Headless\Account\ApiTokenAuthenticator::issue()`, call `authenticate()` and then `issueToken()` instead.
- New config keys: `two_factor`, `users`, `rate_limits.two_factor` and `rate_limits.invitations`. A published `config/headless.php` picks up their defaults; add them only to change them.

## Upgrading to 0.3 from 0.2

```bash
composer require ssdev/laravel-headless:^0.3
php artisan headless:install --migrate
npm run build
```

For 0.3, `headless:install`:

- publishes the Sanctum migration `create_personal_access_tokens_table` (skipped if `php artisan install:api` already published it);
- adds `Laravel\Sanctum\HasApiTokens` to `app/Models/User.php`;
- adds `\Ssdev\Headless\Api\ApiErrorRenderer::register($exceptions);` to `bootstrap/app.php`;
- installs `ssdev/laravel-api-contracts` as a dev dependency and creates `tests/Feature/HeadlessApiContractTest.php` with its snapshots.

The API lives under `/api/v1`. If your application already serves routes there, move one of them with `api.prefix` in `config/headless.php`.

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
