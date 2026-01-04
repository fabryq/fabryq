# Changelog

All notable changes to this repository will be documented in this file.

## Unreleased

## v0.4.0 - 2026-01-03

Added:

- Project-level `fabryq.yaml` defaults for controller generation and report links.
- CRUD generator (`fabryq:crud:create`) with use case-first scaffolding and controller defaults.
- Optional pruning of unresolvable imports after `fix:crossing`.
- Cross-app entity type replacement to contracts interfaces with auto-created interfaces.
- Markdown report links for PhpStorm/file schemes.
- Remove commands for apps/components with dependency blocking.
- Component scaffolding commands for templates/translations.
- CLI hardening: error classes, exit codes, global handler, dry-run, and write locking.
- CI: run `fabryq:verify`, `fabryq:doctor`, and `fabryq:graph` on the demo project.

Changed:

- Demo: add StockService import to the Test HelloWorld controller.
- Ignore top-level `AGENTS.md` in git.
- Docs: update install instructions and CLI documentation for v0.4.0.
- Install instructions updated to use `composer create-project fabryq/fabryq:0.4.0`.

Notes:

- Fabryq v0.4.0 is pre-1.0; breaking changes remain possible.
- The monorepo stays intact for v0.4.x; packages will split to separate repos in v1.

## v0.3.0 - 2026-01-03

Added:

- CLI commands: `verify`, `review`, `doctor`, `graph`, `app:create`, `component:create`, `assets:install`, `fix`, `fix:assets`, `fix:crossing`.
- Verification gates for cross-app references, service locator usage, entity base enforcement, asset collisions, Doctrine table prefixes, component slug
  validation, and capability id validation.
- Capability provider registration via `#[FabryqProvider]` with winner resolution and contract aliasing.
- Reports and deterministic finding ids under `state/reports/`.
- Fix planning and logging under `state/fix/`.
- Runtime discovery of apps and components, mountpoint-based routing, and resource discovery.
- Asset publishing to `public/fabryq`.
- Base runtime abstractions (`FabryqContext`, base controller/command/use case, entity base).
- Root `composer.json` now requires Symfony framework/console, Doctrine bundles, and Webpack Encore bundle for the monorepo runtime.

Changed:

- Root `composer.json` description aligned with README positioning.
- Docs navigation improved with cross-links and an index pointer across core pages.
- Install instructions updated to use `composer create-project fabryq/fabryq:0.3.0`.
- CLI command examples updated to use `bin/console fabryq:*` in the repo root.
- The CLI wrapper script has been removed; use `bin/console` commands instead.

Notes:

- Fabryq v0.3.0 is pre-1.0; breaking changes remain possible.
- The monorepo stays intact for v0.3.x; packages will split to separate repos in v1.
