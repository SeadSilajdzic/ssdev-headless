# Contributing

Thanks for helping out! Bug reports, fixes and new features are all welcome.

## Local setup

```bash
composer install
npm install
```

## Checks

Every pull request must pass:

```bash
vendor/bin/pest
vendor/bin/phpstan analyse
vendor/bin/pint --test
npm run typecheck
npm test
```

## Guidelines

- Write the test first. Every change needs tests, including the security cases (other users' resources, missing permissions, rate limits).
- Check access with permissions (`$user->can('users.update')`), never with role names.
- Keep controllers thin; business logic belongs in services and actions.
- All UI text goes through translation keys in `lang/en`.
- Package Vue files import each other through `@headless/...`, never with relative paths, so published overrides keep working.
- Commit messages are in English and describe what changed in the code.

## Security

Please do not open public issues for security vulnerabilities. Report them privately to the maintainer instead.
