# Changelog

All notable changes to this repository will be documented in this file.

## Unreleased

- No documented changes yet.

## v0.3.x

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
