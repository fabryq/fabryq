# Fabryq

Fabryq is an opinionated Symfony backend toolkit: **a CLI + runtime + generators** that enforce **deterministic app structures** and produce **repeatable reports**.

The goal is not “more flexibility”. The goal is **less drift**.

* **One standard path** for structure and modules
* **Deterministic output** (same inputs → same structure)
* **Guardrails first** (fail fast instead of “it kinda works”)

> Status: **v0.3 (pre-release)**. Expect breaking changes until the first stable release.

---

## Why Fabryq

Symfony is powerful because it is flexible. That flexibility also creates drift:

* different folder structures
* different patterns per team or project
* hidden coupling across modules
* reviews that devolve into style debates

Fabryq turns conventions into **gates**.

---

## Core principles

* **One Way Only** — exactly one standard path for structure, naming, and wiring.
* **Removable Apps** — an app can be removed without breaking the runtime.
* **No Silent Coupling** — apps own their data; avoid cross-app ORM relations.
* **Core stays clean** — infrastructure belongs in Core; business logic stays in Apps.

---

## What you get

* **CLI** to verify, review, and generate scaffolding
* **Runtime** that defines the “standard path” for bootstrapping
* **Generators** for apps/components that follow the same rules every time
* **Reports** (current and planned) to make structure and coupling visible

---

## Requirements

* PHP **>= 8.4**

---

## Installation

### Monorepo development (recommended for contributors)

```bash
composer install
```

### Package-only usage

```bash
cd packages/runtime
composer install
```

---

## Quickstart

Run the quality gates:

```bash
vendor/bin/fabryq verify
vendor/bin/fabryq review
```

Create an app and a component:

```bash
vendor/bin/fabryq app:create billing --mount=/billing
vendor/bin/fabryq component:create billing Checkout
```

---

## Repository layout

This repository is currently organized as a monorepo:

* `src/` — framework/tooling code
* `packages/` — packaged building blocks (runtime, etc.)
* `skeleton/` — project bootstrap scaffolding
* `examples/` — demo / reference setups
* `tools/` — internal tooling
* `tests/` — test suite
* `doc/` — local documentation entry points

---

## Documentation

* In-repo docs: `doc/README.md`
* Public docs repo: `fabryq/docs` (recommended for narrative documentation and roadmap)

---

## Development

```bash
composer test
vendor/bin/phpunit
vendor/bin/fabryq verify
vendor/bin/fabryq review
```

---

## Contributing

Fabryq is still evolving rapidly. If you want to contribute, open an issue describing:

* your intended use-case
* the drift/problem you are trying to remove
* what the “one way” should enforce

Then we can align on guardrails and implementation.

---

## License

Proprietary. See package metadata for details.
