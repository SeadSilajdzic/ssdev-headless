# Changelog

All notable changes to this project are documented in this file. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Two-factor authentication, the first selectable feature (`two-factor`): setup with a QR code and a confirmation code, recovery codes shown once, a sign-in challenge on the web and over the API (`auth/two-factor-challenge`), management through `me/two-factor`, and an optional requirement for everyone or for users with given permissions.
- `headless:add` command that adds a feature to an installed project.
- Features declare their migrations, `User` model traits and API contract tests; `headless:install`, `headless:add` and `headless:upgrade` publish them.

### Changed

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

[Unreleased]: https://github.com/SeadSilajdzic/ssdev-headless/compare/v0.3.1...HEAD
[0.3.1]: https://github.com/SeadSilajdzic/ssdev-headless/compare/v0.3.0...v0.3.1
[0.3.0]: https://github.com/SeadSilajdzic/ssdev-headless/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/SeadSilajdzic/ssdev-headless/compare/v0.1.1...v0.2.0
[0.1.1]: https://github.com/SeadSilajdzic/ssdev-headless/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/SeadSilajdzic/ssdev-headless/releases/tag/v0.1.0
