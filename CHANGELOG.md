# Changelog

All notable changes to this project are documented in this file. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Changed

- The package archive no longer contains the `phpstan/` directory, which only the package's own analysis uses.

### Fixed

- On the pages, changing a user and sending them a password reset link count towards `rate_limits.invitations`, and creating a tenant towards `rate_limits.owner_invitations`. Each sends mail, and a new email on an invited user sent a new invitation past the limit.

## [0.5.2] - 2026-11-06

### Added

- `PUT users/{user}/roles` takes an optional `known_roles`, the role ids the client last read, and answers 422 when they no longer match. The user page sends it, so two managers saving at once cannot overwrite each other.

### Changed

- Only the package's own guest pages send signed-in users to its dashboard; the application's own `guest` routes keep Laravel's default redirect.
- A wrong method on an existing API route answers 405 with an `Allow` header instead of 404.
- The role filter on the users list is ignored without `roles.view`.
- New installs turn off the font fallbacks in `vite.config.ts` that need the optional `fontaine` package, which removes the warning during builds.

### Fixed

- On a tenant's host, signing in, the two-factor challenge, confirming a password and verifying an email return to the page that was asked for instead of the dashboard, and an invitee who has to sign in comes back to the team invitation. Inside a tenant only its own host counts as internal, and on the central level the central domain counts instead of `app.url`.
- An intended URL with control characters or a backslash no longer counts as internal after sign-in: `https://evil.test\@example.com` reads as `example.com` to PHP and as `evil.test` to a browser.
- `headless:install` and `headless:add` edit a `User.php` with Windows line endings, and keep them.
- A crafted request can no longer log out the current browser session through the single-session route.
- A failed save no longer leaves the new avatar file behind.
- With a custom API prefix, an error raised before the API middleware stays JSON on a request that does not ask for it.
- Copying a new API token without HTTPS no longer raises an error; the token stays on screen.
- A recovery code works once also when two challenges arrive at the same moment, on the web and over the API.
- Downloading recovery codes works in older Firefox.
- An invitation whose mail cannot be sent leaves no account behind, and two invitations for one email at the same moment answer a validation error instead of a server error.
- On the session map, a location without a city or country reads "Unknown location", rows can be selected with the keyboard, the current-device pulse respects `prefers-reduced-motion`, and IPv4 addresses written as IPv6 (`::ffff:81.2.69.160`) get a location.

## [0.5.1] - 2026-11-05

### Fixed

- `headless:install` and `headless:add` run `composer require` in the project instead of in the directory the command started from.

## [0.5.0] - 2026-11-05

### Added

- Multi-tenancy (`tenancy`) on `spatie/laravel-multitenancy`: tenants on subdomains, one database with `tenant_id` and the `BelongsToTenant` scope, accounts, sessions, API tokens, reset links, cache, rate limits, files and queued jobs kept apart per tenant, closed sign-up inside tenants, and suspended tenants.
- `Ssdev\Headless\Support\Isolation` for keeping files and keys apart.
- Tenants panel on the central domain (`tenants.view`, `tenants.manage`): create tenants on a checked subdomain with an owner who sets up their account through an invitation, rename, suspend and resume, delete for good after typing the subdomain again (`TenantDeleting` lets the application delete its own rows), the same over the API (`tenants`), and `TenantCreated`, `TenantSuspended`, `TenantResumed` and `TenantDeleted` events.
- User management, roles and teams inside tenants, separate from the central level: roles and teams per tenant (`TenantRole`), Spatie's permission cache per tenant, and the tenant owner in the tenant's `Administrator` role when roles are on.
- A team invitation lets someone without an account create one from the link, also with sign-up closed.

### Changed

- With tenancy on, sign-up on the central domain is closed; central accounts come from `headless:create-admin` and user management invitations.

### Fixed

- Two sign-ups with the same email at once no longer break an application's own surrounding database transaction on PostgreSQL.

## [0.4.0] - 2026-11-04

### Added

- Two-factor authentication, the first selectable feature (`two-factor`): setup with a QR code and a confirmation code, recovery codes shown once, a sign-in challenge on the web and over the API (`auth/two-factor-challenge`), management through `me/two-factor`, and an optional requirement for everyone or for users with given permissions.
- `headless:add` command that adds a feature to an installed project.
- Features declare their migrations, `User` model traits and API contract tests; `headless:install`, `headless:add` and `headless:upgrade` publish them.
- Roles and permissions (`roles`): a roles page under Administration, roles built only from permissions the actor holds, members added by email, a guard that keeps the last role that manages roles, the same over the API (`permissions`, `roles`, `roles/{role}/members`), `RoleAssigned` and `RoleRevoked` events, and `headless:roles:assign`.
- With roles on, `headless:create-admin` gives the admin an `Administrator` role that holds every permission.
- User management (`users`): a users list with search, filters and sorting; invitations that let people choose their own password; changing the name and email; blocking that ends every session and token; password reset links; signing someone out everywhere; soft deletes; roles on the user page; the same over the API (`users`); `UserInvited`, `UserBlocked`, `UserUnblocked` and `UserDeleted` events.
- The roles and users pages ask for the password first.
- Teams (`teams`): create teams and switch between them, invite people with signed links, hand teams over, team roles with a global context for application-wide permissions (`GlobalPermission`), personal teams on registration, and the same over the API.
- Session map on the security page: a world map with a point for every browser session, log out from the map, and the city next to each session. Locations come from an `IpLocator` you configure, with a built-in `MaxMindLocator` for a local GeoLite2 City database; off by default.

### Changed

- Page actions (the `actions` slot of `AppLayout`) sit next to the page title instead of in the top bar.
- Deleting your own account removes it for good, also when the `User` model uses `SoftDeletes`.
- Searches ignore case and treat `%` and `_` as text on SQLite, MySQL and PostgreSQL alike.
- `headless:install` keeps installed features when it runs again and preselects them.
- `ApiTokenAuthenticator::issue()` is split into `authenticate()` and `issueToken()`.

## [0.3.1] - 2026-10-21

### Fixed

- Settings that a release adds to a config group (for example `rate_limits.api`) now apply to applications that published `config/headless.php` earlier, instead of a limit of zero.

## [0.3.0] - 2026-10-21

### Added

- JSON API under `/api/v1` with Laravel Sanctum: token sign-in and sign-out, account (`me`), password, photo, account deletion, verification email, registration, forgot password and personal API tokens.
- API tokens settings page: create tokens with chosen permissions, see the new token once, revoke tokens.
- Token abilities are permission names; a token never has more rights than its owner.
- A password change revokes every other API token, a password reset revokes all of them.
- One JSON error format for the API (`{message}`, plus `errors` for 422) without class names, SQL or stack traces.
- API contracts: every API response is snapshotted in strict mode with `ssdev/laravel-api-contracts`; the installer sets up contract tests in the application.
- Installer publishes the Sanctum token table, adds `HasApiTokens` to the `User` model and registers the API error renderer.
- `api` options and an `api` rate limit in `config/headless.php`.

## [0.2.0] - 2026-10-21

### Added

- Sign in, registration, forgot and reset password, email verification and password confirmation through Laravel Fortify, served by package routes.
- Registration and password reset that never reveal whether an email has an account.
- Rate limits for login (per email and IP, and per IP), registration, password reset, verification, password checks and email changes, configured in `config/headless.php`.
- Honeypot field on public forms.
- Profile settings: name, email change confirmed through the new address, photo upload, password change and account deletion.
- Security settings: browser sessions with log out per device and for every other device.
- Code-defined permissions on `spatie/laravel-permission`, shared with the frontend as `auth.permissions` and checked with `useCan()`.
- Commands `headless:permissions:sync`, `headless:permissions:grant`, `headless:create-admin` and `headless:upgrade`.
- Installer steps that publish migrations, prepare the `User` model and check session settings; `--migrate` runs the migrations and offers to create the first admin.
- Events `PasswordChanged`, `EmailChanged` and `AccountDeleted`.
- `SECURITY.md` with the security matrix and `UPGRADE.md`.

### Changed

- Account links (profile, security) are no longer repeated in the app sidebar; they live in the user menu and the settings navigation.

### Fixed

- Form fields now pass their `id` and ARIA attributes to the input element, so labels and error messages are linked for screen readers.
- Sign-in pages fill the full screen height; the toast region no longer adds an empty row to the page grid.
- Buttons and the user menu show a pointer cursor again (Tailwind 4 defaults buttons to the arrow cursor).

## [0.1.1] - 2026-10-21

### Changed

- `headless:install` explains that no optional features are available yet instead of silently skipping the feature selection.

## [0.1.0] - 2026-10-21

### Added

- `headless:install` command: feature selection, Inertia middleware, Inertia error pages, Vite/Vue/TypeScript scaffolding and npm dependencies; idempotent and never overwrites changed files without confirmation.
- `headless:publish` command to copy a single page, component or layout into the application.
- Feature registry for optional systems, enabled through `config/headless.php`.
- Vite plugin that resolves `@headless/...` imports, preferring published copies in `resources/js/vendor/headless`.
- Theme for Tailwind CSS 4 and a UI kit built on Reka UI.
- App, auth and settings layouts, dashboard page and error pages for 403, 404, 419, 429, 500 and 503.
- Shared Inertia props: authenticated user, navigation, enabled features, translations and flash messages.

[Unreleased]: https://github.com/SeadSilajdzic/ssdev-headless/compare/v0.5.2...HEAD
[0.5.2]: https://github.com/SeadSilajdzic/ssdev-headless/compare/v0.5.1...v0.5.2
[0.5.1]: https://github.com/SeadSilajdzic/ssdev-headless/compare/v0.5.0...v0.5.1
[0.5.0]: https://github.com/SeadSilajdzic/ssdev-headless/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/SeadSilajdzic/ssdev-headless/compare/v0.3.1...v0.4.0
[0.3.1]: https://github.com/SeadSilajdzic/ssdev-headless/compare/v0.3.0...v0.3.1
[0.3.0]: https://github.com/SeadSilajdzic/ssdev-headless/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/SeadSilajdzic/ssdev-headless/compare/v0.1.1...v0.2.0
[0.1.1]: https://github.com/SeadSilajdzic/ssdev-headless/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/SeadSilajdzic/ssdev-headless/releases/tag/v0.1.0
