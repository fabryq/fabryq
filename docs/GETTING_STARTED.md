# Getting Started

This guide walks you through a minimal, working Fabryq setup from the repo.

Docs index: [INDEX](INDEX.md).

## Requirements

- PHP 8.2.x
- Composer
- For tests and fixtures: `pdo_sqlite` and `sqlite3`

## Install (repo)

```bash
composer create-project fabryq/fabryq . -s dev
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
bin/console fabryq:app:create Billing --mount=/billing
bin/console fabryq:component:create Billing Payments
```

Expected output:

```
App "billing" created at <project>/src/Apps/Billing.
Component "Payments" created in app billing.
```

## Run the verification gate

```bash
bin/console fabryq:verify
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

- Learn the architecture vocabulary: [Concepts](CONCEPTS.md)
- Understand enforced rules: [Guardrails](GUARDRAILS.md)
- Use the CLI effectively: [CLI](CLI.md)
- Follow end-to-end tasks: [Workflows](WORKFLOWS.md) 
