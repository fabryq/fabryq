# Rule: SERVICE_LOCATOR_FORBIDDEN

## Purpose
Block service locator/container usage in apps, components, and bridges.

## Inputs
- src/Apps/**
- src/Components/** (including Bridge components)

## Outputs
- FABRYQ.RUNTIME.SERVICE_LOCATOR_FORBIDDEN findings (BLOCKER)

## Flags
- N/A

## Examples
```bash
vendor/bin/fabryq app:create Demo --mount=/demo
vendor/bin/fabryq component:create Demo Core
```
Add a container or service locator dependency in the component to reproduce the blocker.

## Exit Codes
- N/A

## Failure Cases
- Typehinting container interfaces (ContainerInterface, ServiceProviderInterface, ServiceLocator)
- Calling container->get() or static::getContainer()

## Notes
- Runtime internals may still use container access when required.
- Use FabryqContext or explicit dependencies instead.
