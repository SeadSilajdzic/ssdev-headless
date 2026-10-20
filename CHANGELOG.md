# Changelog

All notable changes to this project are documented in this file. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- `headless:install` command: feature selection, Inertia middleware, Inertia error pages, Vite/Vue/TypeScript scaffolding and npm dependencies; idempotent and never overwrites changed files without confirmation.
- `headless:publish` command to copy a single page, component or layout into the application.
- Feature registry for optional systems, enabled through `config/headless.php`.
- Vite plugin that resolves `@headless/...` imports, preferring published copies in `resources/js/vendor/headless`.
- Theme for Tailwind CSS 4 and a UI kit built on Reka UI.
- App, auth and settings layouts, dashboard page and error pages for 403, 404, 419, 429, 500 and 503.
- Shared Inertia props: authenticated user, navigation, enabled features, translations and flash messages.
