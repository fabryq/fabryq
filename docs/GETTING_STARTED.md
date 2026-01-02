# Getting Started

This guide walks you through a minimal, working Fabryq setup from the repo.

## Requirements
- PHP 8.4.x
- Composer
- For tests and fixtures: `pdo_sqlite` and `sqlite3`

## Install (repo)
```bash
git clone https://github.com/fabryq/fabryq.git
cd fabryq
composer install
```

Optional environment gate:
```bash
php tools/env-check.php
```
Expected output:
```
Environment gate passed.
```

## Create a first app and component
```bash
vendor/bin/fabryq app:create Billing --mount=/billing
vendor/bin/fabryq component:create Billing Payments
```
Expected output:
```
App "billing" created at <project>/src/Apps/Billing.
Component "Payments" created in app billing.
```

## Run the verification gate
```bash
vendor/bin/fabryq verify
```
Expected output:
```
Fabryq Verification
No issues found.
```

## Inspect reports
Verification writes artifacts to:
- `state/reports/verify/latest.json`
- `state/reports/verify/latest.md`

## Next steps
- Learn the architecture vocabulary: `CONCEPTS.md`
- Understand enforced rules: `GUARDRAILS.md`
- Use the CLI effectively: `CLI.md`
- Follow end-to-end tasks: `WORKFLOWS.md`

## Optional: start from the skeleton
The `skeleton/` directory is a project template. You can copy it into a new directory and run `composer install`. If you have access to a Composer package feed that contains `fabryq/skeleton`, you can also use:

```bash
composer create-project fabryq/skeleton my-project
```

Note: the demo project in `examples/demo` contains intentional rule violations for testing, so `fabryq verify` is expected to report blockers there.
