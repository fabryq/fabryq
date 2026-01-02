# Project Structure

## Repository layout (monorepo)
- `bin/`: Symfony console entry point for the repo.
- `config/`: Symfony configuration and routing.
- `src/`: Runtime app root (apps and global components).
- `packages/`: Internal packages (contracts, runtime, CLI, providers).
- `skeleton/`: Project template.
- `examples/demo/`: Sample project with intentional rule violations.
- `docs/`: Canonical documentation (start with [INDEX](INDEX.md)).
- `tests/`: PHPUnit tests.
- `tools/`: Environment and lint scripts.

## Runtime project layout
Fabryq expects applications and components under `src/`:

```
src/
  Apps/
    Billing/
      manifest.php
      Resources/
        config/
        public/
        templates/
        translations/
      Payments/
        Controller/
        Service/
        Entity/           (optional)
        Resources/
          config/
          public/
          templates/
          translations/
          migrations/     (optional)
  Components/
    Reporting/
      Controller/
      Service/
      Resources/
        config/
        public/
        templates/
        translations/
```

### Apps
- Each app has a `manifest.php` that declares capabilities and the `mountpoint`.
- `appId` is kebab-case and acts as a stable identifier.
- `mountpoint` is optional. If it is `null`, the app is not routed.

### Components
- Any directory under an app (except `Resources/` and `Doc/`) is treated as a component.
- Component slugs are derived from the directory name (kebab-case). Collisions are blockers.
- Global components live under `src/Components/` and must not reference app classes.

### Resources
- `Resources/config` is reserved for `services.*` and `routes.*` files. The runtime does not auto-import them yet (planned).
- `Resources/templates` and `Resources/translations` are registered automatically.
- `Resources/public` is the only location scanned for assets.
- `Resources/migrations` is scanned for Doctrine migrations per component.

### State artifacts
CLI gates and fixers write outputs under `state/`:
- `state/reports/` (verify, review, doctor)
- `state/graph/`
- `state/assets/`
- `state/fix/`

## Naming conventions (recommended)
These are conventions used by the CLI generators and examples. Not all of them are enforced as rules.
- App folder: PascalCase (e.g., `Billing`)
- Component folder: PascalCase (e.g., `Payments`)
- `appId`: kebab-case (e.g., `billing`)

## Do / Don't examples
Do:
```
src/Apps/Billing/Payments/Controller/InvoiceController.php
src/Apps/Billing/Payments/Resources/public/invoices.js
```

Don't (ignored by Fabryq tooling):
```
src/Apps/Billing/Controller/InvoiceController.php
src/Apps/Billing/Payments/public/invoices.js
public/invoices.js
```

## Related docs
- [Docs Index](INDEX.md)
- [Concepts](CONCEPTS.md)
- [Getting Started](GETTING_STARTED.md)
- [Workflows](WORKFLOWS.md)
