# Changelog

All notable changes to this repository will be documented in this file.

## Unreleased
Added:
- Root `composer.json` now requires Symfony framework/console, Doctrine bundles, and Webpack Encore bundle for the monorepo runtime.

Changed:
- Root `composer.json` description aligned with README positioning.
- Docs navigation improved with cross-links and an index pointer across core pages.
- Install instructions updated to use `composer create-project fabryq/fabryq . -s dev`.
- CLI command examples updated to use `bin/fabryq` in the repo root.
- Wrapper help output reflects the invocation path (`bin/fabryq` vs `vendor/bin/fabryq`).

## v0.3.0
Added:
- CLI commands: `verify`, `review`, `doctor`, `graph`, `app:create`, `component:create`, `assets:install`, `fix`, `fix:assets`, `fix:crossing`.
- Verification gates for cross-app references, service locator usage, entity base enforcement, asset collisions, Doctrine table prefixes, component slug validation, and capability id validation.
- Capability provider registration via `#[FabryqProvider]` with winner resolution and contract aliasing.
- Reports and deterministic finding ids under `state/reports/`.
- Fix planning and logging under `state/fix/`.
- Runtime discovery of apps and components, mountpoint-based routing, and resource discovery.
- Asset publishing to `public/fabryq`.
- Base runtime abstractions (`FabryqContext`, base controller/command/use case, entity base).
