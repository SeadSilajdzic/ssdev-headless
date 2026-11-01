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

Add a feature to a project that already has the core:

```bash
php artisan headless:add two-factor --migrate
npm run build
```

`headless:add` runs the same steps as the installer for that feature: config, migrations, `User` model traits and its API contract test. Features installed before stay installed; running it twice changes nothing.

## What the core gives you

- Sign in, registration, forgot and reset password, email verification and password confirmation, with Laravel Fortify doing the work behind package routes.
- Profile settings: name, email (confirmed through a link sent to the new address), photo, password and account deletion.
- Security settings: browser sessions, with log out per device or for every other device. Sessions are listed when `SESSION_DRIVER=database`.
- Permissions with `spatie/laravel-permission`, always available, so `can('...')` works with or without the roles feature.
- A JSON API (`/api/v1`) for everything above, with personal API tokens managed from the settings page.

Registration and password reset never reveal whether an email has an account. Passwords follow `Password::defaults()`, which you configure in your `AppServiceProvider`.

## Configuration

`config/headless.php` covers:

- `features` — the features installed in this project, e.g. `two-factor`.
- `api.enabled`, `api.prefix`, `api.token_expiration` — turn the JSON API off, move it, or make new tokens expire after a number of minutes.
- `two_factor.required`, `two_factor.required_for_permissions`, `two_factor.challenge_expiration` — make two-factor authentication mandatory for everyone or for users with one of the listed permissions, and set how many minutes an API sign-in challenge stays valid.
- `registration.enabled` — turn public sign-up off.
- `email_verification` — require a verified email for the dashboard.
- `honeypot.field` — name of the hidden field that traps bots.
- `rate_limits` — attempts per minute for login (per email and IP, and per IP), registration, password reset, verification, password checks, email changes, two-factor codes and the API in general.
- `avatars` — disk, directory, maximum size and dimensions, allowed formats.
- `session_locations.locator`, `session_locations.maxmind_database`, `session_locations.cache_minutes` — turn on the session map (see below).
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

Abilities limit permission checks only: every token can read and update its owner's account and list or revoke the owner's tokens, while changing the email or password, deleting the account and creating a token still need the password. A password change revokes every other token, and a password reset revokes all of them.

Every error has one shape: `{"message": "..."}`, plus `"errors"` for 422 validation errors. Errors never include class names, SQL or stack traces outside debug mode, and the API answers in JSON even without an `Accept` header.

A single-page app on the same domain can use Sanctum's cookie mode instead of tokens; enable it with `$middleware->statefulApi()` in `bootstrap/app.php`.

The installer adds `ssdev/laravel-api-contracts` to your project with `tests/Feature/HeadlessApiContractTest.php` and its snapshots, so a customisation that changes an API response shows up as a failing contract.

## Two-factor authentication

Select it in `headless:install` or add it later with `php artisan headless:add two-factor --migrate`. It adds `Laravel\Fortify\TwoFactorAuthenticatable` to `app/Models/User.php` and publishes Fortify's `add_two_factor_columns_to_users_table` migration.

Users turn it on under **Settings → Two-factor authentication**, after confirming their password: they scan the QR code (or type the setup key), confirm with a code from their app, and see their recovery codes once. Regenerating the recovery codes and turning it off sit behind the same password confirmation. Every code works once, a used recovery code is replaced, and code entry is rate limited.

To make it mandatory, set `two_factor.required` to `true`, or list permissions in `two_factor.required_for_permissions`. Until those users turn it on, every page sends them to the setup and every API request answers 403, except signing out, password confirmation and the two-factor endpoints. This covers your own routes too.

Over the API, a user with two-factor authentication gets a challenge instead of a token:

```bash
curl -X POST https://app.test/api/v1/auth/token -H "Accept: application/json" \
  -d email=ada@example.com -d password=secret -d device_name=cli
# 202 {"data":{"two_factor":true,"challenge_token":"…","expires_at":"…"}}

curl -X POST https://app.test/api/v1/auth/two-factor-challenge -H "Accept: application/json" \
  -d challenge_token=… -d code=123456
# 201 {"data":{"token":"2|…","token_type":"Bearer","expires_at":null}}
```

Send `recovery_code` instead of `code` to use a recovery code. A challenge stays valid for `two_factor.challenge_expiration` minutes and lives in the cache, so several servers need a shared cache store.

| Method | Path | |
|---|---|---|
| POST | `auth/two-factor-challenge` | answer a sign-in challenge with `code` or `recovery_code` |
| GET | `me/two-factor` | status: `disabled`, `pending` or `enabled` |
| POST | `me/two-factor` | start the setup (`password`); returns the secret, `otpauth_url` and a QR code |
| POST | `me/two-factor/confirm` | finish the setup with a `code`; returns the recovery codes |
| POST | `me/two-factor/recovery-codes` | create new recovery codes (`password`) |
| DELETE | `me/two-factor` | turn it off (`password`) |

Fortify's events fire for the web and the API alike, so you can log them: `TwoFactorAuthenticationEnabled`, `TwoFactorAuthenticationConfirmed`, `TwoFactorAuthenticationDisabled`, `RecoveryCodesGenerated`, `RecoveryCodeReplaced`, `TwoFactorAuthenticationChallenged`, `TwoFactorAuthenticationFailed` and `ValidTwoFactorAuthenticationCodeProvided`.

## Roles

Select it in `headless:install` or add it later with `php artisan headless:add roles --migrate`. A role groups permissions, so many people can get the same access at once. People with `roles.view` see **Administration → Roles**; people with `roles.manage` as well also create, change and delete roles and add or remove their members by email. The pages ask for the password first, like the API tokens page.

Three rules keep roles from becoming a way around permissions:

- Nobody can give a role a permission they do not hold themselves, and only someone who holds every permission of a role can change it, delete it or hand it out.
- Some role that manages roles always keeps a member, so the last person who can manage roles cannot be removed or demoted.
- Access is still checked with permissions, never with role names: `$user->can('reports.view')` and `useCan()` work the same whether a permission comes from a role or was granted directly.

With roles on, `headless:create-admin` puts the new admin into an `Administrator` role that holds every registered permission. Give someone an existing role from the console with:

```bash
php artisan headless:roles:assign ada@example.com Editor
```

Over the API (`auth:sanctum`; token abilities limit what a token may do):

| Method | Path | |
|---|---|---|
| GET | `permissions` | the permissions you can give a role, grouped (`roles.view` or `roles.manage`) |
| GET | `roles` | roles with member and permission counts; `search`, `sort` (`name`, `users_count`, `created_at`), `direction`, `per_page` (`roles.view`) |
| POST | `roles` | create a role from `name` and `permissions` (`roles.manage`) |
| GET | `roles/{role}` | one role with its permissions (`roles.view`) |
| PUT | `roles/{role}` | change the `name` and `permissions` (`roles.manage`) |
| DELETE | `roles/{role}` | delete the role (`roles.manage`) |
| GET | `roles/{role}/members` | the members, 20 per page (`roles.view`) |
| POST | `roles/{role}/members` | add a member by `email` (`roles.manage`) |
| DELETE | `roles/{role}/members/{user}` | remove a member (`roles.manage`) |

`Ssdev\Headless\Features\Roles\Events\RoleAssigned` and `RoleRevoked` fire whenever someone gets or loses a role, with the role, the user and the actor (`null` when it came from the console). Inside a database transaction they wait for the commit, so a change that is rolled back is never announced.

## User management

Select it in `headless:install` or add it later with `php artisan headless:add users --migrate`. It adds `Illuminate\Database\Eloquent\SoftDeletes` to `app/Models/User.php`, three columns to `users` (`invited_at`, `blocked_at` and `deleted_at`) and a `user_invitation_tokens` table, so invitation links never mix with password reset links. People with `users.view` see **Administration → Users**: a list with search, filters for status, verified email, role and registration date, sorting and paging. The pages ask for the password first.

| Permission | Lets you |
|---|---|
| `users.view` | see the list and the details of a user |
| `users.create` | invite people and send an invitation again |
| `users.update` | change the name and email, send a password reset link, sign someone out everywhere (needs the `database` session driver) |
| `users.block` | block and unblock |
| `users.delete` | delete |

- **Invitations.** An admin enters a name and an email; the person gets a link to choose their own password, so nobody else ever knows it. The link works once, only for that email, and expires after `users.invitation_expire_minutes` (a week); sending it again replaces the old one. Invitations are limited by `rate_limits.invitations` per minute.
- **Blocking** signs the person out everywhere at once, revokes their API tokens and ends "remember me". They cannot sign in again, on the web or over the API, until someone unblocks them.
- **Deleting** is a soft delete: the account stays in the database, cannot sign in and keeps its email taken. Deleting your own account from the profile page still removes it for good.
- Nobody can block or delete themselves. The last active user with `users.block` cannot be deleted, and with roles on, neither can the last member of the last role that manages roles.
- With roles on, the user page also shows the user's roles; people with `roles.manage` can change those they fully hold.

Over the API (`auth:sanctum`, same permissions):

| Method | Path | |
|---|---|---|
| GET | `users` | users; `search`, `status` (`active`, `invited`, `blocked`), `verified`, `role`, `registered_from`, `registered_to` (`Y-m-d`), `sort` (`name`, `email`, `created_at`), `direction`, `per_page` |
| POST | `users` | invite (`name`, `email`) |
| GET | `users/{user}` | one user |
| PUT | `users/{user}` | change `name` and `email`; a new email has to be verified again |
| DELETE | `users/{user}` | delete |
| POST, DELETE | `users/{user}/block` | block, unblock |
| POST | `users/{user}/invitation` | send the invitation again |
| POST | `users/{user}/password-reset` | send a password reset link |
| DELETE | `users/{user}/sessions` | sign out everywhere |
| GET, PUT | `users/{user}/roles` | the user's roles; set them with `roles` (role ids). Only with roles on |

`Ssdev\Headless\Features\Users\Events\UserInvited`, `UserBlocked`, `UserUnblocked` and `UserDeleted` carry the user and the admin who acted.

## Teams

Select it in `headless:install` or add it later with `php artisan headless:add teams --migrate`. It adds the `teams`, `team_user` and `team_invitations` tables, a `current_team_id` column on `users`, and a `team_id` column to Spatie's permission tables; assignments made before stay global.

- Anyone can create teams and switch between them with the switcher under the logo. The current team is the user's `current_team_id`; scope your own data to it.
- The owner renames and deletes the team, invites people by email, removes members and hands the team over to another member. The owner cannot leave without handing it over, and an account that owns a team with other members cannot be deleted until it does.
- Only the owner sees the members' email addresses; other members see names, and their own address.
- An invitation is a signed link that expires after `teams.invitation_expire_minutes` (a week), works once and only for the address it was sent to, once that address is verified; another account that opens it sees neither the team nor the address. Inviting the same address again refreshes the invitation. Invitations are limited by `rate_limits.team_invitations` per minute.
- With `teams.personal_team` on, every new account gets a team of its own.

With roles on, permissions follow Spatie's teams mode with a global context:

- Roles given on the roles page or the user page are global and count in every team. Roles the owner gives a member on the team page count only in that team.
- A permission that implements `Ssdev\Headless\Permissions\GlobalPermission` (all of `users.*` and `roles.*`) counts only when given globally. Inside a team it can neither be given nor used, so owning a team never leads to managing users or roles. Implement the interface on your own permission enums for permissions that must work the same way.
- Create global roles through the roles page or `Role::query()->create()`; Spatie's `Role::create()` stamps the current team (0) on the role, and such a role cannot be given inside a team.

Over the API (`auth:sanctum`):

| Method | Path | |
|---|---|---|
| GET, POST | `teams` | your teams; create one (`name`) |
| GET, PUT, DELETE | `teams/{team}` | one team; rename; delete (owner) |
| PUT | `me/current-team` | switch (`team_id`) |
| GET | `teams/{team}/members` | the members; `email` is null unless you own the team or it is yours |
| DELETE | `teams/{team}/members/{user}` | remove a member (owner) |
| DELETE | `teams/{team}/membership` | leave the team |
| PUT | `teams/{team}/owner` | hand the team over (`user_id`, owner) |
| GET, POST | `teams/{team}/invitations` | pending invitations; invite (`email`, `role` with roles on) |
| DELETE | `teams/{team}/invitations/{invitation}` | cancel an invitation |
| POST | `team-invitations/{invitation}/accept` | accept, sending the `expires` and `signature` of the mailed link |
| PUT | `teams/{team}/members/{user}/roles` | set a member's team roles (`roles`, owner, roles on) |

`Ssdev\Headless\Features\Teams\Events\TeamMemberRolesUpdated` fires after a member's team roles change.

## Multi-tenancy

Select it in `headless:install` or add it later with `php artisan headless:add tenancy --migrate`. It installs `spatie/laravel-multitenancy`, adds a `tenants` table and a `tenant_id` column to `users`, and adds `BelongsToTenant` to `app/Models/User.php`.

Every tenant lives on a subdomain of `tenancy.base_domain`, such as `acme.example.com`, and `tenancy.central_domain` is the platform's own domain; set both in `config/headless.php`. Any other host, an IP address included, answers 404, except the paths in `tenancy.any_host_paths` (`up`, for health checks), which run on the central level. Leave `SESSION_DOMAIN` empty, so a session cookie stays on its own subdomain.

- An account belongs to one tenant: the same email in another tenant is a separate account with its own password, sessions, API tokens and reset links. `tenant_id` 0 is the central level, so accounts that existed before become central accounts.
- `tenant_id` never comes from a request. `BelongsToTenant` limits every query to the current tenant and fills new rows from it, and saving a model never moves it to another tenant. A mass `update()` skips model events, so never pass `tenant_id` to one. Use it on your own models with an indexed `unsignedBigInteger('tenant_id')->default(0)` column.
- Kept apart per tenant: queries, sessions, API tokens, password reset links, the default cache store, rate limits, avatars (under `tenants/{id}/`) and queued jobs, which run in the tenant that dispatched them. Use `Ssdev\Headless\Support\Isolation` for your own files (`path()`) and keys (`key()`).
- Run code for one tenant with `$tenant->execute(fn () => SendReport::dispatch())`; jobs dispatched there run in that tenant.
- Sign-up inside a tenant is off; turn it on with `tenancy.registration`.
- A suspended tenant shows a suspension page, its API answers 403 and nobody can sign in.
- User management, roles and teams do not work inside tenants yet.

### Tenants panel

Platform admins manage tenants on the central domain under **Administration → Tenants**. Sign-up on the central domain is closed: central accounts come from `php artisan headless:create-admin`, who gets every permission including `tenants.*`, and from user management invitations. The panel and its API answer 404 on a tenant's host, and `tenants.*` never count inside a tenant, even when given to an account there.

| Permission | Lets you |
|---|---|
| `tenants.view` | see the list and the details of a tenant |
| `tenants.manage` | create, rename, suspend, resume and delete tenants, and send the owner's invitation again |

- **Creating** takes a name, a subdomain and the owner's email. The subdomain is 3 to 63 lowercase letters, digits and hyphens, not starting or ending with a hyphen and not one of `tenancy.reserved_subdomains` (`www`, `admin`, `api`…). It never changes afterwards; only the name does.
- **The owner** is the tenant's first account. It holds every permission of the tenant except `tenants.*`, so it manages everyone else. It gets an invitation to choose a name and a password on the tenant's subdomain; the link works once, only for that email, and expires after `tenancy.owner_invitation_expire_minutes` (a week). Sending it again replaces the old link, at most `rate_limits.owner_invitations` times a minute.
- **Suspending** closes the tenant's site and API until it is resumed.
- **Deleting** is permanent and needs the subdomain typed again. It removes the tenant's accounts with their API tokens, sessions, role assignments and reset links, and the tenant's directory (`tenants/{id}/`) on the avatars disk. Delete your own rows on `TenantDeleting`, which runs inside the tenant and the deletion's transaction, so a model with `BelongsToTenant` sees only that tenant's rows:

```php
use Illuminate\Support\Facades\Event;
use Ssdev\Headless\Features\Tenancy\Events\TenantDeleting;

Event::listen(TenantDeleting::class, fn () => Project::query()->delete());
```

Over the API on the central domain (`auth:sanctum`, same permissions):

| Method | Path | |
|---|---|---|
| GET | `tenants` | tenants; `search`, `status` (`active`, `suspended`), `sort` (`name`, `subdomain`, `created_at`), `direction`, `per_page` |
| POST | `tenants` | create (`name`, `subdomain`, `owner_email`) |
| GET | `tenants/{tenant}` | one tenant, with its URL and owner |
| PUT | `tenants/{tenant}` | rename (`name`) |
| DELETE | `tenants/{tenant}` | delete, sending `subdomain` to confirm |
| POST, DELETE | `tenants/{tenant}/suspension` | suspend, resume |
| POST | `tenants/{tenant}/owner-invitation` | send the owner's invitation again |

`Ssdev\Headless\Features\Tenancy\Events\TenantCreated`, `TenantSuspended` and `TenantResumed` carry the tenant and the admin who acted; `TenantDeleted` carries the former tenant's id and subdomain and the admin.

## Session map

The security page can show where a user is signed in: a world map with a point for every browser session, a popup to log a device out, and the city next to each session in the list. It stays off until you choose where locations come from, so no IP address leaves your server unless you decide it should.

To use a local MaxMind GeoLite2 City database:

```bash
composer require geoip2/geoip2
```

Create a free MaxMind account, download GeoLite2 City with MaxMind's [`geoipupdate`](https://dev.maxmind.com/geoip/updating-databases/) tool (schedule it to keep the database current) and point the config at the file:

```php
// config/headless.php
'session_locations' => [
    'locator' => \Ssdev\Headless\Account\Location\MaxMindLocator::class,
    'maxmind_database' => storage_path('app/geoip/GeoLite2-City.mmdb'),
    'cache_minutes' => 1440,
],
```

Any class that implements `Ssdev\Headless\Account\Location\IpLocator` works as a locator too, for example one that asks a service you already use. Local and private addresses are never looked up, and every address the locator answers for is looked up once per `cache_minutes`. A locator that fails (for example because the database file is missing) is reported once per page load and shows the sessions as "Unknown location" instead of breaking the page. Locations are as precise as a city; a VPN shows up where its server is.

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
