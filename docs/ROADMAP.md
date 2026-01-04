# Roadmap

This roadmap is intentionally short and factual. Items under Next and Later are planned and not yet implemented.

## Now (v0.4.x, implemented)
- Runtime discovery of apps, components, and manifests.
- CLI gates: `verify`, `review`, `doctor`, `graph`.
- Cross-app reference detection and automated bridge generation.
- Asset scanning and publishing to `public/fabryq`.
- Entity base enforcement and Doctrine table prefix checks.
- Capability provider registration via `#[FabryqProvider]`.

## Next (planned)
- Auto-import `Resources/config` files discovered in apps and components.
- Expanded guardrails for component scope and cross-app data coupling.
- Additional CLI fixers for common violations.

## Later (planned)
- Formal stability and upgrade policy for system components.
- Structured release tooling and automated changelog generation.
- Expanded examples that cover multi-app capability wiring.

## Related docs
- [Docs Index](INDEX.md)
- [FAQ](FAQ.md)
- [Workflows](WORKFLOWS.md)
- [Concepts](CONCEPTS.md)
