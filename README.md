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

Non-interactive installs (CI, scripts):

```bash
php artisan headless:install --features=teams,two-factor --no-interaction --force --npm
```

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
npm run build
```

Everything you have not published is updated. Changes that need action on your side are listed in `CHANGELOG.md`. The package follows semantic versioning: breaking changes to the API, config or swappable classes only happen in major versions.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT. See [LICENSE.md](LICENSE.md).
