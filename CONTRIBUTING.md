# Contributing

This repository is pre-release (v0.3.x). Contributions should stay consistent with existing structure and guardrails.

## Dev setup
Requirements:
- PHP 8.4.x
- Composer
- For tests and fixtures: `pdo_sqlite` and `sqlite3`

Setup:
```bash
composer create-project fabryq/fabryq . -s dev
php tools/env-check.php
```

## Coding standards
- Use `declare(strict_types=1);` in PHP files.
- Prefer `final` and `readonly` where appropriate (value objects, definitions).
- Follow existing namespace and folder conventions.
- Avoid service locator usage; prefer explicit dependencies or `FabryqContext`.
- Keep public API changes documented in `CHANGELOG.md`.

## Tests and gates
- PHPUnit:
```bash
composer test
# or
vendor/bin/phpunit
```

- PHP lint:
```bash
composer lint
```

- Fabryq gates:
```bash
vendor/bin/fabryq verify
vendor/bin/fabryq review
vendor/bin/fabryq doctor
```

## PR checklist
- [ ] `composer test` passes
- [ ] `composer lint` passes
- [ ] `fabryq verify` and `fabryq doctor` pass or failures are explained
- [ ] Docs updated for behavioral changes
- [ ] `CHANGELOG.md` updated when behavior changes

## Release checklist (manual)
- [ ] Update version numbers in `composer.json` files under `packages/`
- [ ] Move items from Unreleased to the new version in `CHANGELOG.md`
- [ ] Run `composer test`, `composer lint`, `fabryq verify`, and `fabryq doctor`
- [ ] Tag the release and push tags
