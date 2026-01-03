# FAQ

## What is Fabryq?
A PHP/Symfony architecture toolkit that enforces app boundaries and capability wiring through a runtime bundle and CLI gates.

## Is Fabryq tied to Symfony?
Yes. The runtime and CLI are Symfony bundles and rely on Symfony configuration and DI.

## What is an app vs a component?
An app is a domain boundary under `src/Apps/<App>`. A component is any directory inside an app that groups controllers, services, and resources.

## How do apps talk to each other?
Through capabilities. Direct app-to-app class references are blocked by `FABRYQ.APP.CROSSING`.

## Where do I define app metadata?
In `src/Apps/<App>/manifest.php`.

## Where do reports go?
Under `state/`, for example `state/reports/verify/latest.json`.

## How do I publish assets?
Place them under `Resources/public` and run `bin/console fabryq:assets:install`.

## Can I inject the service container?
No. Container typehints and `container->get()` calls are blocked. Use explicit dependencies or `FabryqContext`.

## Is Fabryq stable?
v0.3.x is pre-release. Breaking changes are possible.

## Related docs
- [Docs Index](INDEX.md)
- [Getting Started](GETTING_STARTED.md)
- [Concepts](CONCEPTS.md)
- [Guardrails](GUARDRAILS.md)
