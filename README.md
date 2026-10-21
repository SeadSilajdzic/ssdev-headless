# Laravel Headless

An installable Laravel foundation: authentication, user management, roles and permissions, teams, two-factor authentication and multi-tenancy — each shipped with a Vue + Inertia UI, a JSON API and tests.

The code stays in the package, so security fixes and improvements reach your app through `composer update`. Anything you want to change, you publish and own.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Node 20+

## Installation

Start from a fresh Laravel application:

```bash
laravel new my-app
cd my-app
composer require ssdev/laravel-headless
php artisan headless:install
```

The installer asks which features you want, wires up Inertia, Vue, Tailwind and the error pages, and never overwrites a file you changed without asking first. It is safe to run again.

It also publishes the migrations, adds `HasRoles` (and `MustVerifyEmail` when email verification is on) to `app/Models/User.php`, checks your session settings, and offers to run the migrations and create the first admin account.

Non-interactive installs (CI, scripts):

```bash
php artisan headless:install --features=teams,two-factor --no-interaction --force --migrate --npm
php artisan headless:create-admin
```

`headless:create-admin` always asks for the password in a hidden prompt, so it never ends up in your shell history.

## What the core gives you

- Sign in, registration, forgot and reset password, email verification and password confirmation, with Laravel Fortify doing the work behind package routes.
- Profile settings: name, email (confirmed through a link sent to the new address), photo, password and account deletion.
- Security settings: browser sessions, with log out per device or for every other device. Sessions are listed when `SESSION_DRIVER=database`.
- Permissions with `spatie/laravel-permission`, always available, so `can('...')` works with or without the roles feature.
- A JSON API (`/api/v1`) for everything above, with personal API tokens managed from the settings page.

Registration and password reset never reveal whether an email has an account. Passwords follow `Password::defaults()`, which you configure in your `AppServiceProvider`.

## Configuration

`config/headless.php` covers:

- `api.enabled`, `api.prefix`, `api.token_expiration` — turn the JSON API off, move it, or make new tokens expire after a number of minutes.
- `registration.enabled` — turn public sign-up off.
- `email_verification` — require a verified email for the dashboard.
- `honeypot.field` — name of the hidden field that traps bots.
- `rate_limits` — attempts per minute for login (per email and IP, and per IP), registration, password reset, verification, password checks, email changes and the API in general.
- `avatars` — disk, directory, maximum size and dimensions, allowed formats.
- `permissions` — your own permission enums (see below).

## Permissions

Permissions are defined in code as backed enums, so every permission the UI offers is one the code actually checks:

```php
use Ssdev\Headless\Permissions\HeadlessPermission;

enum ReportPermission: string implements HeadlessPermission
{
    case View = 'reports.view';
    case Export = 'reports.export';

    public function group(): string
    {
        return 'reports';
    }
}
```

Register the enum in `config/headless.php` (`'permissions' => [ReportPermission::class]`), then:

```bash
php artisan headless:permissions:sync
php artisan headless:permissions:grant ada@example.com reports.view
php artisan headless:permissions:grant ada@example.com --all
```

Check access with permissions, never with role names: `$user->can('reports.view')` in PHP and `useCan()` in Vue:

```ts
import { useCan } from '@headless/composables/useCan';

const can = useCan();
// v-if="can('reports.view')"
```

## JSON API

Everything lives under `/api/v1` and authenticates with Sanctum bearer tokens.

```bash
curl -X POST https://app.test/api/v1/auth/token \
  -H "Accept: application/json" \
  -d email=ada@example.com -d password=secret -d device_name=cli
# {"data":{"token":"1|…","token_type":"Bearer","expires_at":null}}

curl https://app.test/api/v1/me -H "Authorization: Bearer 1|…"
```

| Method | Path | |
|---|---|---|
| POST | `auth/token` | sign in, returns a token |
| DELETE | `auth/token` | revoke the current token |
| POST | `auth/register` | sign up (answers the same for new and existing emails) |
| POST | `auth/forgot-password` | send a reset link (answers the same for every email) |
| GET, PUT, DELETE | `me` | read, update or delete your account (`current_password` / `password` where needed) |
| PUT | `me/password` | change your password |
| POST, DELETE | `me/avatar` | upload or remove your photo |
| POST | `me/email/verification-notification` | resend the verification email |
| GET, POST | `tokens` | list tokens, create one (`password` required) |
| DELETE | `tokens/{id}` | revoke one of your tokens |

Token abilities are permission names. A token never has more rights than its owner: it can use a permission only when it was given that permission and its owner still has it. Tokens created at sign-in get `*`, which means "whatever the owner may do".

Every error has one shape: `{"message": "..."}`, plus `"errors"` for 422 validation errors. Errors never include class names, SQL or stack traces outside debug mode, and the API answers in JSON even without an `Accept` header.

A single-page app on the same domain can use Sanctum's cookie mode instead of tokens; enable it with `$middleware->statefulApi()` in `bootstrap/app.php`.

The installer adds `ssdev/laravel-api-contracts` to your project with `tests/Feature/HeadlessApiContractTest.php` and its snapshots, so a customisation that changes an API response shows up as a failing contract.

## Customization

From the lightest touch to the heaviest:

1. **Config** — publish `config/headless.php` and adjust routes, middleware and feature options.
2. **Swap a class** — services, actions and form requests are resolved through the container, so you can bind your own implementation without copying anything.
3. **Publish a file** — copy a single page, component or layout into your project:

   ```bash
   php artisan headless:publish pages/Dashboard.vue
   ```

   The copy lands in `resources/js/vendor/headless/` and replaces the package version everywhere it is used. A published file no longer receives package updates.

Translations and the root Blade view can be published with `vendor:publish --tag=headless-lang` and `--tag=headless-views`. The package ships English only; add your language by translating the published files.

## Updating

```bash
composer update ssdev/laravel-headless
php artisan headless:upgrade
php artisan migrate
npm run build
```

`headless:upgrade` publishes the migrations a new version added. Everything you have not published is updated. Changes that need action on your side are listed in `UPGRADE.md` and `CHANGELOG.md`. The package follows semantic versioning: breaking changes to the API, config or swappable classes only happen in major versions.

## Security

See [SECURITY.md](SECURITY.md) for how to report a vulnerability and how the package handles each item of its security checklist.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT. See [LICENSE.md](LICENSE.md).
