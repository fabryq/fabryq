# Fabryq

Fabryq is a PHP/Symfony architecture toolkit for building multiple apps inside one runtime. It combines a runtime bundle, CLI gates, and contracts so structure
and dependencies are explicit and machine-checkable.

Fabryq targets backend engineers and tech leads who want predictable boundaries between apps, deterministic reports, and a minimal set of shared abstractions.
Current line: v0.3.x (pre-release).

## Core principles

- One Way structure: apps live in `src/Apps/<App>` with `manifest.php`; components are directories inside apps; global components live in `src/Components`.
  Enforced by discovery and slug checks; deeper structure rules are planned.
- Gates over guidelines: `fabryq verify`, `review`, `doctor`, and `graph` emit findings and exit codes for CI.
- Removability and no silent coupling: direct app-to-app references are blocked (`FABRYQ.APP.CROSSING`), and global components may not reference app classes (
  `FABRYQ.GLOBAL_COMPONENT.REFERENCES_APP`).
- Explicit capabilities: apps declare `provides`/`consumes` in manifests; providers are declared with `#[FabryqProvider]`; missing required providers are
  blockers.
- No service locator: container typehints and `container->get()` are blocked (`FABRYQ.RUNTIME.SERVICE_LOCATOR_FORBIDDEN`).
- Deterministic entities: Doctrine entities must extend `AbstractFabryqEntity` or use the interface+trait exception path.

## Installation (package)

Install the runtime and CLI in an existing Symfony project:

```bash
composer require fabryq/fabryq
```

Package: https://packagist.org/packages/fabryq/fabryq

## Quickstart (repo)

Requirements:

- PHP 8.4.x
- Composer
- For tests and fixtures: `pdo_sqlite` and `sqlite3`

Install (repo):

```bash
composer install
```

Run:

```bash
vendor/bin/fabryq verify
```

## First success (minimal)

```bash
vendor/bin/fabryq app:create Billing --mount=/billing
vendor/bin/fabryq component:create Billing Payments
vendor/bin/fabryq verify
```

Expected output (verify):

```
Fabryq Verification
No issues found.
```

## Repo structure

- `packages/contracts`: manifest and capability contracts.
- `packages/runtime`: runtime bundle, discovery, routing, resources, entities.
- `packages/cli`: CLI gates and fixers.
- `packages/provider-http-client`: example capability provider.
- `skeleton`: starter project template.
- `examples/demo`: sample project with multiple apps.
- `docs`: canonical documentation (this release).

## Versioning and stability

Fabryq v0.3.x is pre-release. Breaking changes are possible, and there is no compatibility guarantee between pre-release tags. Treat rule keys, report schemas,
and CLI output as subject to change.

## Documentation

Start with [docs/INDEX.md](docs/INDEX.md).

## Support

Use the issue tracker for this repository if available. There is no public SLA.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT. See [LICENSE.md](LICENSE.md).
